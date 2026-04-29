<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = defined('SESSION_SAVE_PATH') ? trim((string) SESSION_SAVE_PATH) : '';
    if ($sessionPath !== '' && !is_dir($sessionPath)) {
        @mkdir($sessionPath, 0755, true);
    }
    if ($sessionPath !== '' && is_dir($sessionPath) && is_writable($sessionPath)) {
        ini_set('session.save_path', $sessionPath);
    }
    session_name('UGC_SOL');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function aplicar_headers_seguridad(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Content-Security-Policy: default-src 'self' blob:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com; frame-src 'self' blob:; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'self' blob:");

    if (APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function csrf_token(): string
{
    iniciar_sesion_segura();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function validar_csrf(?string $token): bool
{
    iniciar_sesion_segura();

    if ($token === null || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function limpiar_texto($valor): string
{
    return trim(strip_tags((string) $valor));
}

function normalizar_documento($valor): string
{
    return preg_replace('/\D/', '', (string) $valor) ?? '';
}

function usuario_actual(): array
{
    iniciar_sesion_segura();
    return $_SESSION['usuario'] ?? [];
}

function usuario_autenticado(): bool
{
    return !empty(usuario_actual());
}

function rol_actual(): string
{
    $usuario = usuario_actual();
    return $usuario['rol'] ?? '';
}

function requiere_login(): void
{
    if (!usuario_autenticado()) {
        header('Location: ' . app_base_url('index.php?view=login'));
        exit;
    }
}

function requiere_rol(array $roles): void
{
    requiere_login();

    if (!in_array(rol_actual(), $roles, true)) {
        http_response_code(403);
        exit('Sin permiso.');
    }
}

function cerrar_sesion(): void
{
    iniciar_sesion_segura();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
