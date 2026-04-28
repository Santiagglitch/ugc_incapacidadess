<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/empleadoModel.php';
require_once __DIR__ . '/../models/solicitudModel.php';
require_once __DIR__ . '/../models/adminRolesModel.php';

class adminController
{
    public function empleados(): void
    {
        requiere_rol([ROL_ADMIN]);
        $user = usuario_actual();
        $busqueda = mb_strtolower(limpiar_texto($_GET['q'] ?? ''), 'UTF-8');
        $filtroRol = limpiar_texto($_GET['rol'] ?? '');
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = 12;

        $empleadoModel = new empleadoModel();
        $adminRoles = new adminRolesModel();
        $adminsAdicionales = $adminRoles->getAdminsAdicionales();
        $todos = $empleadoModel->getTodos();

        $stats = $this->calcularStats($todos, $adminsAdicionales);

        if ($busqueda !== '') {
            $todos = array_values(array_filter($todos, function ($emp) use ($busqueda) {
                $texto = mb_strtolower(($emp['NOMBRE_COMPLETO'] ?? '') . ' ' . ($emp['NIT'] ?? ''), 'UTF-8');
                return mb_strpos($texto, $busqueda) !== false;
            }));
        }

        if ($filtroRol !== '') {
            $todos = array_values(array_filter($todos, function ($emp) use ($filtroRol, $adminsAdicionales) {
                return $this->rolEmpleado($emp, $adminsAdicionales) === $filtroRol;
            }));
        }

        $total = count($todos);
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $empleados = array_slice($todos, ($pagina - 1) * $porPagina, $porPagina);
        $esSuperAdmin = ($user['cedula'] ?? '') === SUPER_ADMIN_NIT;

        render_view('admin/empleados/index', compact('user', 'empleados', 'total', 'pagina', 'totalPaginas', 'busqueda', 'filtroRol', 'stats', 'adminsAdicionales', 'esSuperAdmin'));
    }

    public function empleadoDetalle(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $user = usuario_actual();
        $empleadoModel = new empleadoModel();
        $solicitudModel = new solicitudModel();
        $adminRoles = new adminRolesModel();

        $empleado = $empleadoModel->getByNit($nit);
        if (!$empleado) {
            flash_set('error', 'Empleado no encontrado.');
            redirect_to(url_view('admin_empleados'));
        }

        $solicitudes = $solicitudModel->getByEmpleado($nit);
        $adminsAdicionales = $adminRoles->getAdminsAdicionales();
        $esAdminAdicional = in_array($nit, $adminsAdicionales, true);
        $esSuperAdmin = ($user['cedula'] ?? '') === SUPER_ADMIN_NIT;
        $puedeGestionarAdmin = $esSuperAdmin && $nit !== ($user['cedula'] ?? '');
        $stats = [
            'total' => count($solicitudes),
            'pendientes' => count(array_filter($solicitudes, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE)),
            'aprobadas' => count(array_filter($solicitudes, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true))),
            'rechazadas' => count(array_filter($solicitudes, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH], true))),
        ];

        render_view('admin/empleados/detalle', compact('user', 'empleado', 'solicitudes', 'stats', 'esAdminAdicional', 'puedeGestionarAdmin'));
    }

    public function hacerAdmin(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->soloSuperAdmin();
        $this->validarPost();
        $ok = (new adminRolesModel())->agregarAdmin($nit);
        flash_set($ok ? 'success' : 'error', $ok ? 'Administrador asignado.' : 'No se pudo asignar administrador.');
        redirect_to(url_view('admin_empleado') . '&nit=' . urlencode($nit));
    }

    public function quitarAdmin(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->soloSuperAdmin();
        $this->validarPost();
        $ok = (new adminRolesModel())->quitarAdmin($nit);
        flash_set($ok ? 'success' : 'error', $ok ? 'Administrador removido.' : 'No se pudo remover administrador.');
        redirect_to(url_view('admin_empleado') . '&nit=' . urlencode($nit));
    }

    private function soloSuperAdmin(): void
    {
        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            http_response_code(403);
            exit('Solo el super usuario puede gestionar administradores.');
        }
    }

    private function validarPost(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            http_response_code(422);
            exit('Token de seguridad invalido.');
        }
    }

    private function rolEmpleado(array $emp, array $adminsAdicionales): string
    {
        $nit = (string) ($emp['NIT'] ?? '');
        $nivel = (int) ($emp['NIVEL'] ?? 0);
        $cc = (string) ($emp['CENTRO_COSTO'] ?? '');

        if ($nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales, true)) {
            return 'admin';
        }
        if (in_array($cc, CC_RRHH, true)) {
            return 'rrhh';
        }
        if ($nivel >= NIVEL_MIN_JEFE) {
            return 'jefe';
        }
        return 'empleado';
    }

    private function calcularStats(array $empleados, array $adminsAdicionales): array
    {
        $stats = ['total' => count($empleados), 'admin' => 0, 'rrhh' => 0, 'jefe' => 0, 'empleado' => 0];
        foreach ($empleados as $emp) {
            $rol = $this->rolEmpleado($emp, $adminsAdicionales);
            $stats[$rol]++;
        }
        return $stats;
    }
}