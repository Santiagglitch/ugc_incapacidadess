<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/authModel.php';
require_once __DIR__ . '/../models/empleadoModel.php';
require_once __DIR__ . '/../models/adminAuditoriaModel.php';

class authController
{
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

        $result = (new authModel())->login($cedula, $password);
        if (empty($result['ok'])) {
            $_SESSION['login_error'] = $result['error'] ?? 'No se pudo iniciar sesion.';
            redirect_to(url_view('login'));
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = $result['user'];
        $_SESSION['last_activity'] = time();
        $_SESSION['notificacion_sonar_login'] = true;

        // Si es Super Admin, redirigir a selección de modo
        if (($result['user']['cedula'] ?? '') === SUPER_ADMIN_NIT) {
            $_SESSION['super_admin_modo_pendiente'] = true;
            try {
                (new adminAuditoriaModel())->registrar(
                    SUPER_ADMIN_NIT,
                    'LOGIN_SA',
                    'Super Usuario inicio sesion',
                    [],
                    $result['user']['nombre'] ?? 'Super Usuario'
                );
            } catch (Throwable $e) {
            }
            redirect_to(url_view('seleccionar_modo'));
        }

        redirect_to(url_view('dashboard'));
    }

    public function seleccionarModoForm(): void
    {
        if (!usuario_autenticado() || empty($_SESSION['super_admin_modo_pendiente'])) {
            redirect_to(url_view('dashboard'));
        }

        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            unset($_SESSION['super_admin_modo_pendiente']);
            redirect_to(url_view('dashboard'));
        }

        $error = $_SESSION['modo_error'] ?? null;
        unset($_SESSION['modo_error']);

        render_view('auth/seleccionar_modo', [
            'user' => $user,
            'error' => $error,
        ], 'auth');
    }

    public function seleccionarModoPost(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            $_SESSION['modo_error'] = 'Token de seguridad invalido.';
            redirect_to(url_view('seleccionar_modo'));
        }

        if (!usuario_autenticado() || empty($_SESSION['super_admin_modo_pendiente'])) {
            redirect_to(url_view('dashboard'));
        }

        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            unset($_SESSION['super_admin_modo_pendiente']);
            redirect_to(url_view('dashboard'));
        }

        $modo = limpiar_texto($_POST['modo'] ?? '');

        if (!in_array($modo, ['super_usuario', 'empleado'], true)) {
            $_SESSION['modo_error'] = 'Selecciona un modo valido.';
            redirect_to(url_view('seleccionar_modo'));
        }

        // Guardar el modo seleccionado en sesión
        $_SESSION['super_admin_modo'] = $modo;
        unset($_SESSION['super_admin_modo_pendiente']);

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

        if (super_admin_modo_pendiente()) {
            redirect_to(url_view('seleccionar_modo'));
        }

        $user = usuario_actual();
        if (($user['cedula'] ?? '') !== SUPER_ADMIN_NIT) {
            http_response_code(403);
            flash_set('error', 'Solo el Super Admin puede cambiar de modo.');
            redirect_to(url_view('dashboard'));
        }

        $modoActual = $_SESSION['super_admin_modo'] ?? 'super_usuario';
        $modoDestino = limpiar_texto($_POST['modo_destino'] ?? '');

        if (!in_array($modoDestino, ['super_usuario', 'empleado'], true)) {
            $modoDestino = $modoActual === 'super_usuario' ? 'empleado' : 'super_usuario';
        }

        if ($modoDestino === $modoActual) {
            flash_set('success', 'El modo seleccionado ya esta activo.');
            redirect_to(url_view('dashboard'));
        }

        // Cambiar al modo solicitado. Esto evita que un doble envio revierta el cambio.
        if ($modoDestino === 'empleado') {
            // Cambiar a modo empleado
            $_SESSION['super_admin_modo'] = 'empleado';
            $empleadoModel = new empleadoModel();
            $empleado = $empleadoModel->getByNit($user['cedula']);
            $rolReal = $this->calcularRolOperativo($user['cedula'], $empleado);
            $_SESSION['usuario']['rol'] = $rolReal;
            flash_set('success', 'Modo cambiado a Empleado/Jefe. Permisos administrativos desactivados.');
            try {
                (new adminAuditoriaModel())->registrar(
                    (string)($user['cedula'] ?? ''),
                    'CAMBIO_MODO',
                    'Super Usuario cambio a modo Empleado',
                    ['modo_destino' => 'empleado'],
                    (string)($user['nombre'] ?? '')
                );
            } catch (Throwable $e) {
            }
        } else {
            // Cambiar a modo super usuario
            $_SESSION['super_admin_modo'] = 'super_usuario';
            $_SESSION['usuario']['rol'] = ROL_ADMIN;
            flash_set('success', 'Modo cambiado a Super Usuario. Acceso administrativo total activado.');
            try {
                (new adminAuditoriaModel())->registrar(
                    (string)($user['cedula'] ?? ''),
                    'CAMBIO_MODO',
                    'Super Usuario cambio a modo Super Usuario',
                    ['modo_destino' => 'super_usuario'],
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
            cerrar_sesion();
            redirect_to(url_view('login'));
        }

        cerrar_sesion();
        redirect_to(url_view('login'));
    }
}
