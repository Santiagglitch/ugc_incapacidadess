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
     * Verifica si el usuario tiene acceso al selector de modo (Super Admin o Admin Adicional).
     */
    private function usuarioTieneSelectorModo(array $user): bool
    {
        $cedula = $user['cedula'] ?? '';
        if ($cedula === SUPER_ADMIN_NIT) {
            return true;
        }
        return (new adminRolesModel())->esAdminAdicional($cedula);
    }

    /**
     * Determina si el usuario es Super Admin (para mostrar opciones específicas).
     */
    private function esSuperAdmin(array $user): bool
    {
        return ($user['cedula'] ?? '') === SUPER_ADMIN_NIT;
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
            'devUsuarios' => APP_ENV === 'development' ? USUARIOS_PRUEBA : [],
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

    public function loginPost(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            http_response_code(422);
            render_view('auth/login', [
                'error' => 'Token de seguridad invalido. Recarga la pagina.',
                'devUsuarios' => APP_ENV === 'development' ? USUARIOS_PRUEBA : [],
            ], 'auth');
            return;
        }

        $cedula = limpiar_texto($_POST['cedula'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $bloqueo = $this->verificarBruteForce($cedula);
        if ($bloqueo !== null) {
            http_response_code(429);
            render_view('auth/login', [
                'error' => $bloqueo,
                'devUsuarios' => APP_ENV === 'development' ? USUARIOS_PRUEBA : [],
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
        session_regenerate_id(true);
        $_SESSION['usuario'] = $result['user'];
        $_SESSION['last_activity'] = time();
        $_SESSION['notificacion_sonar_login'] = true;

        // Si es Super Admin o Admin Adicional, redirigir a selección de modo
        if ($this->usuarioTieneSelectorModo($result['user'])) {
            $_SESSION['admin_modo_pendiente'] = true;
            $cedula = $result['user']['cedula'] ?? '';
            $esSuperAdmin = ($cedula === SUPER_ADMIN_NIT);
            try {
                (new adminAuditoriaModel())->registrar(
                    $cedula,
                    $esSuperAdmin ? 'LOGIN_SA' : 'LOGIN_ADMIN',
                    $esSuperAdmin ? 'Super Usuario inicio sesion' : 'Administrador adicional inicio sesion',
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
            'user' => $user,
            'error' => $error,
            'esSuperAdmin' => $this->esSuperAdmin($user),
        ], 'auth');
    }

    public function seleccionarModoPost(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            $_SESSION['modo_error'] = 'Token de seguridad invalido.';
            redirect_to(url_view('seleccionar_modo'));
        }

        if (!usuario_autenticado() || empty($_SESSION['admin_modo_pendiente'])) {
            redirect_to(url_view('dashboard'));
        }

        $user = usuario_actual();
        if (!$this->usuarioTieneSelectorModo($user)) {
            unset($_SESSION['admin_modo_pendiente']);
            redirect_to(url_view('dashboard'));
        }

        $modo = limpiar_texto($_POST['modo'] ?? '');
        $modosValidos = ['admin', 'empleado'];
        if ($this->esSuperAdmin($user)) {
            $modosValidos = ['super_usuario', 'empleado'];
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
        }

        redirect_to(url_view('dashboard'));
    }
    public function cambiarModo(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            flash_set('error', 'Token de seguridad invalido.');
            redirect_to(url_view('dashboard'));
        }

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
            // Cambiar a modo empleado
            $_SESSION['admin_modo'] = 'empleado';
            $empleadoModel = new empleadoModel();
            $empleado = $empleadoModel->getByNit($user['cedula']);
            $rolReal = $this->calcularRolOperativo($user['cedula'], $empleado);
            $_SESSION['usuario']['rol'] = $rolReal;
            flash_set('success', 'Modo cambiado a Empleado/Jefe. Permisos administrativos desactivados.');
            try {
                $tipoAdmin = $esSuperAdmin ? 'Super Usuario' : 'Administrador';
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
            // Cambiar a modo admin/super usuario
            $_SESSION['admin_modo'] = $modoAdminKey;
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

        redirect_to(url_view('dashboard'));
    }

    private function calcularRolOperativo(string $cedula, ?array $empleado): string
    {
        // Si tiene centro de costo de RRHH, es RRHH
        $centroCosto = (string) ($empleado['CENTRO_COSTO'] ?? '');
        if (in_array($centroCosto, CC_RRHH, true)) {
            return ROL_RRHH;
        }

        // Si tiene nivel de jefe o superior
        $nivel = (int) ($empleado['NIVEL'] ?? 0);
        if ($nivel >= NIVEL_MIN_JEFE) {
            return ROL_JEFE;
        }

        // Por defecto es empleado
        return ROL_EMPLEADO;
    }

    public function logout(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            redirect_to(url_view('login'));
        }

        cerrar_sesion();
        redirect_to(url_view('login'));
    }
}
