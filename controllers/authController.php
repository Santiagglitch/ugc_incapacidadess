<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/authModel.php';
require_once __DIR__ . '/../models/empleadoModel.php';
require_once __DIR__ . '/../models/adminAuditoriaModel.php';
require_once __DIR__ . '/../models/adminRolesModel.php';
require_once __DIR__ . '/../services/loginBloqueoService.php';

class authController
{
    /**
     * Verifica si el usuario tiene acceso al selector de modo (Super Admin, Admin Adicional o TH fijo).
     */
    private function usuarioTieneSelectorModo(array $user): bool
    {
        $cedula = $user['cedula'] ?? '';
        if (es_super_admin_nit($cedula)) {
            return true;
        }
        if (in_array($cedula, NITS_TALENTO_HUMANO, true)) {
            return true;
        }
        return (new adminRolesModel())->esAdminAdicional($cedula);
    }

    /**
     * Determina si el usuario es Super Admin (para mostrar opciones específicas).
     */
    private function esSuperAdmin(array $user): bool
    {
        return es_super_admin_nit($user['cedula'] ?? '');
    }

    /**
     * Determina si el usuario es el Talento Humano fijo hardcodeado.
     */
    private function esTalentoHumanoFijo(array $user): bool
    {
        return in_array($user['cedula'] ?? '', NITS_TALENTO_HUMANO, true);
    }

    public function loginForm(): void
    {
        if (usuario_autenticado()) {
            redirect_to(url_view('dashboard'));
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        render_view('auth/login', [
            'error' => $error,
        ], 'auth');
    }

    private function verificarBruteForce(string $cedula): ?string
    {
        try {
            return (new loginBloqueoService())->verificar($cedula);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function registrarIntentoFallido(string $cedula): void
    {
        try {
            $ip           = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $intentos     = (new loginBloqueoService())->registrarFallido($cedula);
            (new adminAuditoriaModel())->registrar(
                $cedula,
                'LOGIN_FALLIDO',
                'Intento de login fallido (intento ' . $intentos . ' de 3)',
                ['ip' => $ip, 'intentos' => $intentos]
            );
        } catch (Throwable $e) {}
    }

    private function limpiarIntentosFallidos(string $cedula): void
    {
        try {
            (new loginBloqueoService())->limpiar($cedula);
        } catch (Throwable $e) {}
    }

    private function esperaHtml(): bool
    {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return str_contains($accept, 'text/html');
    }

    public function loginPost(): void
    {
        csrf_requerido();

        $cedula = limpiar_texto($_POST['cedula'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $bloqueo = $this->verificarBruteForce($cedula);
        if ($bloqueo !== null) {
            http_response_code(429);
            render_view('auth/login', [
                'error' => $bloqueo,
            ], 'auth');
            return;
        }

        $result = (new authModel())->login($cedula, $password);
        if (empty($result['ok'])) {
            $this->registrarIntentoFallido($cedula);
            $_SESSION['login_error'] = 'Credenciales incorrectas.';
            redirect_to(url_view('login'));
        }

        $this->limpiarIntentosFallidos($cedula);
        session_regenerate_id(false);
        csrf_regenerate_after_login();
        $_SESSION['usuario'] = $result['user'];
        $_SESSION['last_activity'] = time();
        $_SESSION['notificacion_sonar_login'] = true;

        // Si es Super Admin, TH fijo o Admin Adicional, redirigir a selección de modo
        if ($this->usuarioTieneSelectorModo($result['user'])) {
            $_SESSION['admin_modo_pendiente'] = true;
            $cedula = $result['user']['cedula'] ?? '';
            $esSuperAdmin = es_super_admin_nit($cedula);
            $esTH = $this->esTalentoHumanoFijo($result['user']);
            if ($esSuperAdmin) {
                $tipoLog = 'LOGIN_SA';
                $msgLog  = 'Super Usuario inicio sesion';
            } elseif ($esTH) {
                $tipoLog = 'LOGIN_TH';
                $msgLog  = 'Talento Humano inicio sesion';
            } else {
                $tipoLog = 'LOGIN_ADMIN';
                $msgLog  = 'Administrador adicional inicio sesion';
            }
            try {
                (new adminAuditoriaModel())->registrar(
                    $cedula,
                    $tipoLog,
                    $msgLog,
                    [],
                    $result['user']['nombre'] ?? 'Usuario'
                );
            } catch (Throwable $e) {
            }
            redirect_to(url_view('seleccionar_modo'));
        }

        redirect_to(url_view('dashboard'));
    }

    public function seleccionarModoForm(): void
    {
        if (!usuario_autenticado() || empty($_SESSION['admin_modo_pendiente'])) {
            redirect_to(url_view('dashboard'));
        }

        $user = usuario_actual();
        if (!$this->usuarioTieneSelectorModo($user)) {
            unset($_SESSION['admin_modo_pendiente']);
            redirect_to(url_view('dashboard'));
        }

        $error = $_SESSION['modo_error'] ?? null;
        unset($_SESSION['modo_error']);

        render_view('auth/seleccionar_modo', [
            'user'                => $user,
            'error'               => $error,
            'esSuperAdmin'        => $this->esSuperAdmin($user),
            'esTalentoHumanoFijo' => $this->esTalentoHumanoFijo($user),
        ], 'auth');
    }

    public function seleccionarModoPost(): void
    {
        csrf_requerido();

        if (!usuario_autenticado() || empty($_SESSION['admin_modo_pendiente'])) {
            redirect_to(url_view('dashboard'));
        }

        $user = usuario_actual();
        if (!$this->usuarioTieneSelectorModo($user)) {
            unset($_SESSION['admin_modo_pendiente']);
            redirect_to(url_view('dashboard'));
        }

        $modo = limpiar_texto($_POST['modo'] ?? '');
        if ($this->esTalentoHumanoFijo($user)) {
            $modosValidos = ['admin', 'empleado'];
        } elseif ($this->esSuperAdmin($user)) {
            $modosValidos = ['super_usuario', 'empleado'];
        } else {
            $modosValidos = ['admin', 'empleado'];
        }

        if (!in_array($modo, $modosValidos, true)) {
            $_SESSION['modo_error'] = 'Selecciona un modo valido.';
            redirect_to(url_view('seleccionar_modo'));
        }

        // Guardar el modo seleccionado en sesión
        $_SESSION['admin_modo'] = $modo;
        unset($_SESSION['admin_modo_pendiente']);

        // Si elige modo empleado, recalcular su rol real según su jerarquía
        if ($modo === 'empleado') {
            $empleadoModel = new empleadoModel();
            $empleado = $empleadoModel->getByNit($user['cedula']);
            $rolReal = $this->calcularRolOperativo($user['cedula'], $empleado);
            $_SESSION['usuario']['rol'] = $rolReal;
        } elseif ($modo === 'admin' && $this->esTalentoHumanoFijo($user)) {
            $_SESSION['usuario']['rol'] = ROL_RRHH;
        }

        redirect_to(url_view('dashboard'));
    }

    public function cambiarModo(): void
    {
        csrf_requerido();

        if (!usuario_autenticado()) {
            redirect_to(url_view('login'));
        }

        if (admin_modo_pendiente()) {
            redirect_to(url_view('seleccionar_modo'));
        }

        $user = usuario_actual();
        if (!$this->usuarioTieneSelectorModo($user)) {
            http_response_code(403);
            flash_set('error', 'No tienes permiso para cambiar de modo.');
            redirect_to(url_view('dashboard'));
        }

        $esSuperAdmin = $this->esSuperAdmin($user);
        $esTalentoHumanoFijo = $this->esTalentoHumanoFijo($user);
        $modoAdminKey = $esSuperAdmin ? 'super_usuario' : 'admin';
        $modoActual = $_SESSION['admin_modo'] ?? $modoAdminKey;
        $modoDestino = limpiar_texto($_POST['modo_destino'] ?? '');
        $modosValidos = $esSuperAdmin ? ['super_usuario', 'empleado'] : ['admin', 'empleado'];

        if (!in_array($modoDestino, $modosValidos, true)) {
            $modoDestino = $modoActual === $modoAdminKey ? 'empleado' : $modoAdminKey;
        }

        if ($modoDestino === $modoActual) {
            flash_set('success', 'El modo seleccionado ya esta activo.');
            redirect_to(url_view('dashboard'));
        }

        // Cambiar al modo solicitado. Esto evita que un doble envio revierta el cambio.
        if ($modoDestino === 'empleado') {
            // Cambiar a modo empleado/jefe
            $_SESSION['admin_modo'] = 'empleado';
            $empleadoModel = new empleadoModel();
            $empleado = $empleadoModel->getByNit($user['cedula']);
            $rolReal = $this->calcularRolOperativo($user['cedula'], $empleado);
            $_SESSION['usuario']['rol'] = $rolReal;
            $tipoAdmin = $esSuperAdmin ? 'Super Usuario' : ($esTalentoHumanoFijo ? 'Talento Humano' : 'Administrador');
            flash_set('success', 'Modo cambiado a Empleado/Jefe. Permisos administrativos desactivados.');
            try {
                (new adminAuditoriaModel())->registrar(
                    (string)($user['cedula'] ?? ''),
                    'CAMBIO_MODO',
                    $tipoAdmin . ' cambio a modo Empleado',
                    ['modo_destino' => 'empleado'],
                    (string)($user['nombre'] ?? '')
                );
            } catch (Throwable $e) {
            }
        } else {
            // Cambiar a modo admin/super usuario/talento humano
            $_SESSION['admin_modo'] = $modoAdminKey;
            if ($esTalentoHumanoFijo) {
                $_SESSION['usuario']['rol'] = ROL_RRHH;
                flash_set('success', 'Modo cambiado a Talento Humano. Acceso RRHH activado.');
                try {
                    (new adminAuditoriaModel())->registrar(
                        (string)($user['cedula'] ?? ''),
                        'CAMBIO_MODO',
                        'Talento Humano cambio a modo Talento Humano',
                        ['modo_destino' => 'admin'],
                        (string)($user['nombre'] ?? '')
                    );
                } catch (Throwable $e) {
                }
            } else {
                $_SESSION['usuario']['rol'] = ROL_ADMIN;
                $tipoAdmin = $esSuperAdmin ? 'Super Usuario' : 'Administrador';
                flash_set('success', 'Modo cambiado a ' . $tipoAdmin . '. Acceso administrativo activado.');
                try {
                    (new adminAuditoriaModel())->registrar(
                        (string)($user['cedula'] ?? ''),
                        'CAMBIO_MODO',
                        $tipoAdmin . ' cambio a modo ' . $tipoAdmin,
                        ['modo_destino' => $modoAdminKey],
                        (string)($user['nombre'] ?? '')
                    );
                } catch (Throwable $e) {
                }
            }
        }

        redirect_to(url_view('dashboard'));
    }

    private function calcularRolOperativo(string $cedula, ?array $empleado): string
    {
        // Verificar directamente en NITS_JEFES_REVISORES — cubre Super Admin, usuarios TH
        // que también son jefe, y jefes normales. Es la fuente de verdad para modo empleado.
        $nitNormalizado = preg_replace('/\D+/', '', $cedula) ?? '';
        if (in_array($nitNormalizado, NITS_JEFES_REVISORES, true)) {
            return ROL_JEFE;
        }

        // Admins adicionales de BD que no están en NITS_JEFES_REVISORES → empleado
        return ROL_EMPLEADO;
    }

    public function logout(): void
    {
        csrf_requerido();

        cerrar_sesion();
        redirect_to(url_view('login'));
    }
}