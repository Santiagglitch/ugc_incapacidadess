<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

// TTL del token CSRF en segundos. Se sincroniza con SESSION_IDLE_TIMEOUT
// para evitar que el token expire antes que la sesion por inactividad.
const CSRF_TOKEN_TTL = 900;

// Tiempo de gracia para el token anterior despues de un login (segundos).
const CSRF_GRACE_PERIOD = 90;

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
    ini_set('session.use_strict_mode', '1');
    ini_set('session.lazy_write', '1');
    ini_set('session.gc_maxlifetime', 3600);
    session_name('UGC_SOL');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

function aplicar_headers_seguridad(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Content-Security-Policy: default-src 'self' blob:; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com; frame-src 'self' blob:; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'self' blob:");

    if (APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function csrf_token(): string
{
    iniciar_sesion_segura();

    $now = time();

    if (empty($_SESSION['csrf_token']) || csrf_token_expirado($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = csrf_generar_token($now);
    }

    return $_SESSION['csrf_token'];
}

function csrf_generar_token(int $timestamp): string
{
    return bin2hex(random_bytes(32)) . ':' . $timestamp;
}

function csrf_token_expirado(string $token): bool
{
    $parts = explode(':', $token);
    if (count($parts) !== 2) {
        return true;
    }

    $ts = (int) $parts[1];
    return (time() - $ts) > CSRF_TOKEN_TTL;
}

function csrf_input(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function validar_csrf(?string $token): bool
{
    iniciar_sesion_segura();

    if ($token === null || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    // 1. Validar contra el token actual
    if (hash_equals($_SESSION['csrf_token'], $token) && !csrf_token_expirado($_SESSION['csrf_token'])) {
        return true;
    }

    // 2. Validar contra el token de gracia (guardado al hacer login)
    if (!empty($_SESSION['csrf_token_old'])) {
        $old = $_SESSION['csrf_token_old'];
        $oldTime = (int) ($_SESSION['csrf_token_old_time'] ?? 0);

        if (
            (time() - $oldTime) <= CSRF_GRACE_PERIOD &&
            hash_equals($old, $token) &&
            !csrf_token_expirado($old)
        ) {
            return true;
        }

        // Limpiar token de gracia si ya vencio
        if ((time() - $oldTime) > CSRF_GRACE_PERIOD) {
            unset($_SESSION['csrf_token_old'], $_SESSION['csrf_token_old_time']);
        }
    }

    return false;
}

function csrf_regenerate_after_login(): void
{
    iniciar_sesion_segura();

    // Guardar el token anterior por un tiempo de gracia
    if (!empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token_old'] = $_SESSION['csrf_token'];
        $_SESSION['csrf_token_old_time'] = time();
    }

    // Generar token nuevo
    $_SESSION['csrf_token'] = csrf_generar_token(time());
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

function admin_modo_pendiente(): bool
{
    return !empty($_SESSION['admin_modo_pendiente']);
}

function es_peticion_ajax(): bool
{
    $requested = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    return $requested === 'xmlhttprequest';
}

function responder_sesion_expirada(): void
{
    if (es_peticion_ajax()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'redirect' => app_base_url('index.php?view=login'),
            'message' => 'Tu sesion expiro. Ingresa nuevamente.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . app_base_url('index.php?view=login'));
    exit;
}

function csrf_requerido(): void
{
    iniciar_sesion_segura();

    $token = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (validar_csrf($token)) {
        return;
    }

    // Token no valido: cerrar la sesion de forma controlada y redirigir al login.
    cerrar_sesion();
    iniciar_sesion_segura();

    $_SESSION['login_error'] = 'Tu sesion expiro. Ingresa nuevamente.';

    responder_sesion_expirada();
}

function usuario_tiene_selector_modo(): bool
{
    $usuario = usuario_actual();
    $cedula = $usuario['cedula'] ?? '';
    if (es_super_admin_nit($cedula)) {
        return true;
    }
    if (in_array($cedula, NITS_TALENTO_HUMANO, true)) {
        return true;
    }
    require_once __DIR__ . '/../models/adminRolesModel.php';
    return (new adminRolesModel())->esAdminAdicional($cedula);
}

function rol_actual(): string
{
    $usuario = usuario_actual();
    return $usuario['rol'] ?? '';
}

function validar_vigencia_sesion(): void
{
    iniciar_sesion_segura();

    $now = time();
    $lastActivity = (int)($_SESSION['last_activity'] ?? $now);
    $idleTimeout = defined('SESSION_IDLE_TIMEOUT') ? (int)SESSION_IDLE_TIMEOUT : 1800;

    if ($idleTimeout > 0 && ($now - $lastActivity) > $idleTimeout) {
        cerrar_sesion();
        iniciar_sesion_segura();
        $_SESSION['login_error'] = 'Tu sesion expiro por inactividad. Ingresa nuevamente.';
        responder_sesion_expirada();
    }

    $_SESSION['last_activity'] = $now;

    $usuario = $_SESSION['usuario'] ?? [];
    if (!empty($usuario)) {
        require_once dirname(__DIR__) . '/services/sesionActivaService.php';
        (new sesionActivaService())->registrar($usuario);
    }
}

function requiere_login(): void
{
    if (!usuario_autenticado()) {
        iniciar_sesion_segura();
        $_SESSION['login_error'] = 'Tu sesion expiro. Ingresa nuevamente.';
        responder_sesion_expirada();
    }

    validar_vigencia_sesion();

    if (admin_modo_pendiente()) {
        header('Location: ' . app_base_url('index.php?view=seleccionar_modo'));
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
    require_once dirname(__DIR__) . '/services/sesionActivaService.php';
    (new sesionActivaService())->cerrar(session_id());
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}