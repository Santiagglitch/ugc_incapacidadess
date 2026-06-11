<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/empleadoModel.php';
require_once __DIR__ . '/../models/solicitudModel.php';
require_once __DIR__ . '/../models/adminRolesModel.php';
require_once __DIR__ . '/../models/adminAuditoriaModel.php';
require_once __DIR__ . '/../services/notaAdminService.php';
require_once __DIR__ . '/../services/sesionActivaService.php';

class adminController
{
    public function empleados(): void
    {
        requiere_rol([ROL_ADMIN]);
        $user        = usuario_actual();
        $busqueda    = mb_strtolower(limpiar_texto($_GET['q']   ?? ''), 'UTF-8');
        $filtroRol   = limpiar_texto($_GET['rol']  ?? '');
        $filtroCC    = limpiar_texto($_GET['cc']   ?? '');
        $ordenCol    = limpiar_texto($_GET['ord']  ?? 'nombre');
        $ordenDir    = strtolower(limpiar_texto($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $pagina      = max(1, (int)($_GET['pagina']    ?? 1));
        $porPagina   = in_array((int)($_GET['pp'] ?? 6), [6, 12, 24], true) ? (int)($_GET['pp'] ?? 6) : 6;

        $empleadoModel     = new empleadoModel();
        $adminRoles        = new adminRolesModel();
        $solicitudModel    = new solicitudModel();
        $adminsAdicionales = $adminRoles->getAdminsAdicionales();
        $todos             = $empleadoModel->getTodos();

        $stats        = $this->calcularStats($todos, $adminsAdicionales);
        $centrosCosto = array_values(array_unique(array_filter(array_column($todos, 'CENTRO_COSTO'))));
        sort($centrosCosto);

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

        if ($filtroCC !== '') {
            $todos = array_values(array_filter($todos, fn($emp) => ($emp['CENTRO_COSTO'] ?? '') === $filtroCC));
        }

        $columnaValor = static function (array $emp, string $col): string {
            return match ($col) {
                'nit'   => (string)($emp['NIT']            ?? ''),
                'nivel' => (string)($emp['NIVEL']          ?? ''),
                'cc'    => (string)($emp['CENTRO_COSTO']   ?? ''),
                default => mb_strtolower((string)($emp['NOMBRE_COMPLETO'] ?? ''), 'UTF-8'),
            };
        };
        usort($todos, static function ($a, $b) use ($ordenCol, $ordenDir, $columnaValor) {
            $cmp = strnatcmp($columnaValor($a, $ordenCol), $columnaValor($b, $ordenCol));
            return $ordenDir === 'desc' ? -$cmp : $cmp;
        });

        $total       = count($todos);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $pagina      = min($pagina, $totalPaginas);
        $empleados   = array_slice($todos, ($pagina - 1) * $porPagina, $porPagina);
        $esSuperAdmin = ($user['cedula'] ?? '') === SUPER_ADMIN_NIT;

        $nitsPage      = array_column($empleados, 'NIT');
        $conteoSolicitudes = $solicitudModel->contarPorNits($nitsPage);

        render_view('admin/empleados/index', compact(
            'user', 'empleados', 'total', 'pagina', 'totalPaginas',
            'busqueda', 'filtroRol', 'filtroCC', 'centrosCosto',
            'ordenCol', 'ordenDir', 'porPagina',
            'stats', 'adminsAdicionales', 'esSuperAdmin', 'conteoSolicitudes'
        ));
    }

    public function exportEmpleadosCsv(): void
    {
        requiere_rol([ROL_ADMIN]);
        $user        = usuario_actual();
        $busqueda    = mb_strtolower(limpiar_texto($_GET['q']  ?? ''), 'UTF-8');
        $filtroRol   = limpiar_texto($_GET['rol'] ?? '');
        $filtroCC    = limpiar_texto($_GET['cc']  ?? '');

        $empleadoModel     = new empleadoModel();
        $adminRoles        = new adminRolesModel();
        $adminsAdicionales = $adminRoles->getAdminsAdicionales();
        $todos             = $empleadoModel->getTodos();

        if ($busqueda !== '') {
            $todos = array_values(array_filter($todos, function ($emp) use ($busqueda) {
                $texto = mb_strtolower(($emp['NOMBRE_COMPLETO'] ?? '') . ' ' . ($emp['NIT'] ?? ''), 'UTF-8');
                return mb_strpos($texto, $busqueda) !== false;
            }));
        }
        if ($filtroRol !== '') {
            $todos = array_values(array_filter($todos, fn($emp) => $this->rolEmpleado($emp, $adminsAdicionales) === $filtroRol));
        }
        if ($filtroCC !== '') {
            $todos = array_values(array_filter($todos, fn($emp) => ($emp['CENTRO_COSTO'] ?? '') === $filtroCC));
        }

        $rolLabel = ['admin' => 'Administrador', 'jefe' => 'Jefe', 'empleado' => 'Empleado'];
        $cabeceras = ['NIT', 'Nombre', 'Centro Costo', 'Nivel', 'Rol'];
        $escape = fn(string $v): string => (strpbrk($v, ",\"\r\n") !== false) ? '"' . str_replace('"', '""', $v) . '"' : $v;

        $lineas = [implode(',', array_map($escape, $cabeceras))];
        foreach ($todos as $emp) {
            $rol = $this->rolEmpleado($emp, $adminsAdicionales);
            $lineas[] = implode(',', array_map($escape, [
                (string)($emp['NIT']            ?? ''),
                (string)($emp['NOMBRE_COMPLETO'] ?? ''),
                (string)($emp['CENTRO_COSTO']   ?? ''),
                (string)($emp['NIVEL']          ?? ''),
                $rolLabel[$rol] ?? $rol,
            ]));
        }

        $filename = 'empleados_' . date('Ymd_His') . '.csv';
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo "\xEF\xBB\xBF" . implode("\r\n", $lineas);
        exit;
    }

    public function empleadoDetalle(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $user           = usuario_actual();
        $empleadoModel  = new empleadoModel();
        $solicitudModel = new solicitudModel();
        $adminRoles     = new adminRolesModel();

        $empleado = $empleadoModel->getByNit($nit);
        if (!$empleado) {
            flash_set('error', 'Empleado no encontrado.');
            redirect_to(url_view('admin_empleados'));
        }

        $jefeInmediato     = $empleadoModel->getJefeInmediato($nit);
        $esAprendiz        = $empleadoModel->esAprendiz((string)($empleado['CENTRO_COSTO'] ?? ''));
        $adminsAdicionales = $adminRoles->getAdminsAdicionales();
        $esAdminAdicional  = in_array($nit, $adminsAdicionales, true);
        $esSuperAdmin      = ($user['cedula'] ?? '') === SUPER_ADMIN_NIT;
        $puedeGestionarAdmin = $esSuperAdmin && $nit !== ($user['cedula'] ?? '');

        $fechaIngreso  = (string)($empleado['FECHA_INGRESO'] ?? '');
        $antiguedad    = $this->calcularAntiguedad($fechaIngreso);

        $solicitudes = $solicitudModel->getByEmpleado($nit);
        $stats = [
            'total'      => count($solicitudes),
            'pendientes' => count(array_filter($solicitudes, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE)),
            'aprobadas'  => count(array_filter($solicitudes, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true))),
            'rechazadas' => count(array_filter($solicitudes, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH], true))),
        ];

        $rolLabel    = $this->rolEmpleado($empleado, $adminsAdicionales);
        $limiteAdmins = $adminRoles->getLimiteMaximo();
        $usadosAdmins = count($adminsAdicionales) + 1;

        $auditLog = [];
        if ($esSuperAdmin) {
            $auditLog = (new adminAuditoriaModel())->getByNitObjetivo($nit, 8);
        }

        render_view('admin/empleados/detalle', compact(
            'user', 'empleado', 'solicitudes', 'stats',
            'esAdminAdicional', 'puedeGestionarAdmin',
            'jefeInmediato', 'esAprendiz', 'antiguedad', 'rolLabel',
            'limiteAdmins', 'usadosAdmins', 'auditLog', 'esSuperAdmin'
        ));
    }

    public function hacerAdmin(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->soloSuperAdmin();
        $this->validarPost();
        $this->verificarRateLimit('hacer_admin_' . $nit);
        $user = usuario_actual();

        $adminRoles = new adminRolesModel();

        if ($adminRoles->alcanzaLimite() && !$adminRoles->esAdminAdicional($nit)) {
            flash_set('error', 'Limite maximo de administradores adicionales alcanzado (' . $adminRoles->getLimiteMaximo() . ').');
            redirect_to(url_view('admin_empleado') . '&nit=' . urlencode($nit));
        }

        $empleado = (new empleadoModel())->getByNit($nit);
        if (!$empleado) {
            flash_set('error', 'El NIT ' . $nit . ' no corresponde a ningun empleado activo.');
            redirect_to(url_view('admin_empleados'));
        }

        $ok = $adminRoles->agregarAdmin($nit);
        if ($ok) {
            (new adminAuditoriaModel())->registrar(
                (string)($user['cedula'] ?? ''),
                'ASIGNAR_ADMIN',
                'Se asigno rol de administrador al NIT ' . $nit . ' (' . trim((string)($empleado['NOMBRE_COMPLETO'] ?? $nit)) . ')',
                ['nit_objetivo' => $nit, 'nombre_objetivo' => $empleado['NOMBRE_COMPLETO'] ?? ''],
                (string)($user['nombre'] ?? '')
            );
            $this->regenerarCsrf();
        }
        flash_set($ok ? 'success' : 'error', $ok ? 'Administrador asignado.' : 'No se pudo asignar administrador.');
        redirect_to(url_view('admin_empleado') . '&nit=' . urlencode($nit));
    }

    public function quitarAdmin(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->soloSuperAdmin();
        $this->validarPost();
        $this->verificarRateLimit('quitar_admin_' . $nit);
        $user = usuario_actual();
        $ok = (new adminRolesModel())->quitarAdmin($nit);
        if ($ok) {
            $empleado = (new empleadoModel())->getByNit($nit);
            (new adminAuditoriaModel())->registrar(
                (string)($user['cedula'] ?? ''),
                'QUITAR_ADMIN',
                'Se removio rol de administrador al NIT ' . $nit . ' (' . trim((string)($empleado['NOMBRE_COMPLETO'] ?? $nit)) . ')',
                ['nit_objetivo' => $nit, 'nombre_objetivo' => $empleado['NOMBRE_COMPLETO'] ?? ''],
                (string)($user['nombre'] ?? '')
            );
            $this->regenerarCsrf();
        }
        flash_set($ok ? 'success' : 'error', $ok ? 'Administrador removido.' : 'No se pudo remover administrador.');
        redirect_to(url_view('admin_empleado') . '&nit=' . urlencode($nit));
    }

    public function intervenirSolicitud(int $id): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->validarPost();
        $user     = usuario_actual();
        $decision = limpiar_texto($_POST['decision'] ?? '');
        $obs      = limpiar_texto($_POST['observacion_admin'] ?? '');
        $returnTo = limpiar_texto($_POST['return_to'] ?? '');

        if (!in_array($decision, ['aprobar', 'rechazar'], true) || $obs === '') {
            flash_set('error', 'Datos invalidos para la intervención.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $model = new solicitudModel();
        $sol   = $model->getById($id);
        if (!$sol) {
            flash_set('error', 'Solicitud no encontrada.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $nitAdmin = (string)($user['cedula'] ?? '');
        if (normalizar_documento($sol['NIT_EMPLEADO'] ?? '') === normalizar_documento($nitAdmin)) {
            flash_set('error', 'No puedes intervenir en tu propia solicitud.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $ok = $model->adminIntervenir($id, $nitAdmin, $obs, $decision);
        if ($ok) {
            (new adminAuditoriaModel())->registrar(
                $nitAdmin,
                'ADMIN_INTERVENIR',
                'Intervención administrativa (' . $decision . ') en solicitud #' . $id,
                ['id_solicitud' => $id, 'decision' => $decision, 'obs' => $obs],
                (string)($user['nombre'] ?? '')
            );
            $this->regenerarCsrf();
        }

        flash_set($ok ? 'success' : 'error', $ok ? 'Solicitud actualizada por intervención administrativa.' : 'No se pudo aplicar la intervención.');
        redirect_to($returnTo ?: url_view('dashboard'));
    }

    public function forzarEstadoSolicitud(int $id): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->validarPost();
        $user       = usuario_actual();
        $nuevoEstado = limpiar_texto($_POST['nuevo_estado'] ?? '');
        $motivo     = limpiar_texto($_POST['motivo'] ?? '');
        $returnTo   = limpiar_texto($_POST['return_to'] ?? '');

        if ($nuevoEstado === '' || $motivo === '') {
            flash_set('error', 'Estado y motivo son obligatorios.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $model = new solicitudModel();
        $sol   = $model->getById($id);
        if (!$sol) {
            flash_set('error', 'Solicitud no encontrada.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $nitAdmin = (string)($user['cedula'] ?? '');
        if (normalizar_documento($sol['NIT_EMPLEADO'] ?? '') === normalizar_documento($nitAdmin)) {
            flash_set('error', 'No puedes modificar tu propia solicitud.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $ok = $model->adminForzarEstado($id, $nitAdmin, $nuevoEstado, $motivo);
        if ($ok) {
            (new adminAuditoriaModel())->registrar(
                $nitAdmin,
                'ADMIN_FORZAR_ESTADO',
                'Estado forzado a ' . $nuevoEstado . ' en solicitud #' . $id . ': ' . $motivo,
                ['id_solicitud' => $id, 'nuevo_estado' => $nuevoEstado, 'motivo' => $motivo],
                (string)($user['nombre'] ?? '')
            );
            $this->regenerarCsrf();
        }

        flash_set($ok ? 'success' : 'error', $ok ? 'Estado actualizado.' : 'No se pudo actualizar el estado.');
        redirect_to($returnTo ?: url_view('dashboard'));
    }

    public function guardarNotaAdmin(int $id): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->validarPost();
        $user   = usuario_actual();
        $nota   = limpiar_texto($_POST['nota_admin'] ?? '');
        $returnTo = limpiar_texto($_POST['return_to'] ?? '');

        if ($nota === '') {
            flash_set('error', 'La nota no puede estar vacía.');
            redirect_to($returnTo ?: url_view('dashboard'));
        }

        $ok = (new notaAdminService())->guardar($id, (string)($user['cedula'] ?? ''), (string)($user['nombre'] ?? ''), $nota);

        flash_set($ok ? 'success' : 'error', $ok ? 'Nota guardada.' : 'No se pudo guardar la nota.');
        redirect_to($returnTo ?: url_view('dashboard'));
    }

    public function superAdminPanel(): void
    {
        requiere_rol([ROL_ADMIN]);
        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            flash_set('error', 'Acceso restringido al Super Usuario.');
            redirect_to(url_view('dashboard'));
        }

        $adminRoles     = new adminRolesModel();
        $auditoria      = new adminAuditoriaModel();
        $solicitudModel = new solicitudModel();
        $empleadoModel  = new empleadoModel();

        $sesionesActivas = (new sesionActivaService())->getSesionesActivas();

        $adminsAdicionales = $adminRoles->getAdminsAdicionales();
        $limiteAdmins      = $adminRoles->getLimiteMaximo();

        $logFiltroAccion   = limpiar_texto($_GET['log_accion'] ?? '');
        $logPagina         = max(1, (int)($_GET['log_pagina'] ?? 1));
        $logResultado      = $auditoria->filtrar($logFiltroAccion, $logPagina, 20);
        $logAccionesUnicas = $auditoria->getAccionesUnicas();

        $countsSolicitudes = $solicitudModel->contarPorEstado([]);
        $totalSolicitudes  = array_sum(array_map('intval', $countsSolicitudes));
        $totalEmpleados    = count($empleadoModel->getTodos());
        $totalAdmins       = count($adminsAdicionales) + 1;
        $tendenciaMeses    = $solicitudModel->contarPorMes(6);

        $estadoSistema = $this->obtenerEstadoSistema();

        render_view('superadmin/panel', compact(
            'user',
            'adminsAdicionales',
            'limiteAdmins',
            'logResultado',
            'logFiltroAccion',
            'logAccionesUnicas',
            'countsSolicitudes',
            'totalSolicitudes',
            'totalEmpleados',
            'totalAdmins',
            'estadoSistema',
            'sesionesActivas',
            'tendenciaMeses'
        ));
    }

    public function buscarEmpleadoJson(string $q): void
    {
        requiere_rol([ROL_ADMIN]);
        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        header('Content-Type: application/json');

        $q = mb_strtolower(trim($q), 'UTF-8');
        if (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }

        $todos = (new empleadoModel())->getTodos();
        $adminsAdicionales = (new adminRolesModel())->getAdminsAdicionales();

        $resultados = [];
        foreach ($todos as $emp) {
            $nit    = (string)($emp['NIT'] ?? '');
            $nombre = (string)($emp['NOMBRE_COMPLETO'] ?? '');
            $hayuda = mb_strtolower($nit . ' ' . $nombre, 'UTF-8');

            if (mb_strpos($hayuda, $q, 0, 'UTF-8') === false) {
                continue;
            }

            $esAdminYa = $nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales, true);

            $resultados[] = [
                'nit'       => $nit,
                'nombre'    => $nombre,
                'cc'        => (string)($emp['CENTRO_COSTO'] ?? ''),
                'ya_admin'  => $esAdminYa,
            ];

            if (count($resultados) >= 8) {
                break;
            }
        }

        echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function auditoriaExportCsv(): void
    {
        requiere_rol([ROL_ADMIN]);
        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            flash_set('error', 'Acceso restringido al Super Usuario.');
            redirect_to(url_view('dashboard'));
        }

        $csv      = (new adminAuditoriaModel())->exportCsv();
        $filename = 'auditoria_admin_' . date('Ymd_His') . '.csv';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo "\xEF\xBB\xBF" . $csv;
        exit;
    }

    public function asignarAdminDesdePanel(string $nit): void
    {
        requiere_rol([ROL_ADMIN]);
        $this->soloSuperAdmin();
        $this->validarPost();
        $this->verificarRateLimit('hacer_admin_panel_' . $nit);
        $user = usuario_actual();

        $nit = preg_replace('/\D/', '', $nit);
        if ($nit === '') {
            flash_set('error', 'Ingresa un NIT valido.');
            redirect_to(url_view('superadmin_panel'));
        }

        if ($nit === SUPER_ADMIN_NIT) {
            flash_set('error', 'No puedes modificar el Super Usuario.');
            redirect_to(url_view('superadmin_panel'));
        }

        $adminRoles = new adminRolesModel();

        if ($adminRoles->alcanzaLimite() && !$adminRoles->esAdminAdicional($nit)) {
            flash_set('error', 'Limite maximo de administradores adicionales alcanzado (' . $adminRoles->getLimiteMaximo() . ').');
            redirect_to(url_view('superadmin_panel'));
        }

        $empleado = (new empleadoModel())->getByNit($nit);
        if (!$empleado) {
            flash_set('error', 'El NIT ' . $nit . ' no corresponde a ningun empleado activo.');
            redirect_to(url_view('superadmin_panel'));
        }

        $ok = $adminRoles->agregarAdmin($nit);
        if ($ok) {
            (new adminAuditoriaModel())->registrar(
                (string)($user['cedula'] ?? ''),
                'ASIGNAR_ADMIN',
                'Se asigno rol de administrador al NIT ' . $nit . ' (' . trim((string)($empleado['NOMBRE_COMPLETO'] ?? $nit)) . ') desde panel SA',
                ['nit_objetivo' => $nit, 'nombre_objetivo' => $empleado['NOMBRE_COMPLETO'] ?? '', 'origen' => 'panel_sa'],
                (string)($user['nombre'] ?? '')
            );
            $this->regenerarCsrf();
        }

        flash_set($ok ? 'success' : 'error', $ok
            ? 'Administrador asignado: ' . trim((string)($empleado['NOMBRE_COMPLETO'] ?? $nit)) . '.'
            : 'No se pudo asignar administrador.'
        );
        redirect_to(url_view('superadmin_panel'));
    }

    public function systemHealth(): void
    {
        requiere_rol([ROL_ADMIN]);
        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Sin permiso.']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode($this->obtenerEstadoSistema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function obtenerEstadoSistema(): array
    {
        $oracle = false;
        $oracleMsg = 'No disponible';
        try {
            require_once __DIR__ . '/../config/conexion.php';
            $oracle = Conexion::disponible();
            $oracleMsg = $oracle ? 'Conectado' : 'Error de conexion';
        } catch (Throwable $e) {
            $oracleMsg = 'Error: ' . $e->getMessage();
        }

        $storageUploads  = is_writable(dirname(__DIR__) . '/uploads');
        $storageSessions = is_writable(dirname(__DIR__) . '/storage/sessions');

        $phpVersion = PHP_VERSION;
        $ociHabilitado = function_exists('oci_connect');
        $ldapHabilitado = function_exists('ldap_connect');

        return [
            'oracle'            => ['ok' => $oracle, 'mensaje' => $oracleMsg],
            'php_version'       => $phpVersion,
            'oci_habilitado'    => $ociHabilitado,
            'ldap_habilitado'   => $ldapHabilitado,
            'storage_uploads'   => $storageUploads,
            'storage_sessions'  => $storageSessions,
            'app_env'           => APP_ENV,
            'app_version'       => defined('APP_VERSION') ? APP_VERSION : '—',
            'timezone'          => date_default_timezone_get(),
            'timestamp'         => date('d/m/Y h:i A'),
        ];
    }

    private function soloSuperAdmin(): void
    {
        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            flash_set('error', 'Solo el Super Usuario puede gestionar administradores.');
            redirect_to(url_view('admin_empleados'));
        }
    }

    private function validarPost(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            flash_set('error', 'Token de seguridad invalido. Recarga la pagina.');
            redirect_to(url_view('dashboard'));
        }
    }

    private function verificarRateLimit(string $clave): void
    {
        $key      = 'rate_limit_' . md5($clave);
        $cooldown = 5;
        $ahora    = time();

        if (isset($_SESSION[$key]) && ($ahora - (int)$_SESSION[$key]) < $cooldown) {
            flash_set('error', 'Accion ejecutada demasiado rapido. Espera unos segundos.');
            redirect_to(url_view('admin_empleados'));
        }

        $_SESSION[$key] = $ahora;
    }

    private function regenerarCsrf(): void
    {
        if (function_exists('regenerar_csrf')) {
            regenerar_csrf();
        } else {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function rolEmpleado(array $emp, array $adminsAdicionales): string
    {
        $nit = (string) ($emp['NIT'] ?? '');

        if ($nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales, true)) {
            return 'admin';
        }
        if (es_jefe_revisor_nit($nit)) {
            return 'jefe';
        }
        return 'empleado';
    }

    private function calcularStats(array $empleados, array $adminsAdicionales): array
    {
        $stats = ['total' => count($empleados), 'admin' => 0, 'jefe' => 0, 'empleado' => 0];
        foreach ($empleados as $emp) {
            $rol = $this->rolEmpleado($emp, $adminsAdicionales);
            $stats[$rol]++;
        }
        return $stats;
    }

    private function calcularAntiguedad(string $fechaIngreso): string
    {
        if ($fechaIngreso === '') {
            return '';
        }
        try {
            $inicio = new DateTimeImmutable($fechaIngreso);
            $hoy    = new DateTimeImmutable();
            $diff   = $inicio->diff($hoy);
            $partes = [];
            if ($diff->y > 0) {
                $partes[] = $diff->y . ' ' . ($diff->y === 1 ? 'año' : 'años');
            }
            if ($diff->m > 0) {
                $partes[] = $diff->m . ' ' . ($diff->m === 1 ? 'mes' : 'meses');
            }
            if (empty($partes) && $diff->d >= 0) {
                $partes[] = $diff->d . ' ' . ($diff->d === 1 ? 'día' : 'días');
            }
            return implode(', ', $partes);
        } catch (Throwable $e) {
            return '';
        }
    }
}