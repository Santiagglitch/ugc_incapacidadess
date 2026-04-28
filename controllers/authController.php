<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/authModel.php';

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
            $_SESSION['login_error'] = 'Token de seguridad invalido. Recarga la pagina.';
            redirect_to(url_view('login'));
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
        redirect_to(url_view('dashboard'));
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