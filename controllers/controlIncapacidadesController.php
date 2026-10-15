<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/controlIncapacidadesService.php';

final class controlIncapacidadesController
{
    public function run(): void
    {
        ini_set('session.use_strict_mode', '1');
        session_name('UGC_CONTROL');
        $path = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/control-incapacidades/index.php')));
        session_set_cookie_params(['lifetime' => 0, 'path' => rtrim($path, '/') . '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Strict']);
        if (!@session_start()) {
            http_response_code(503);
            echo 'No fue posible iniciar la sesion del panel. Revisa los permisos de sesiones de PHP.';
            return;
        }
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }
        foreach (['control_action', 'username', 'password', 'password_confirmation', 'setup_key', 'csrf_token', 'confirm_critical', 'reauth_password', 'revision'] as $field) {
            if (isset($_POST[$field]) && !is_string($_POST[$field])) {
                http_response_code(400);
                exit('El formulario no es valido.');
            }
        }
        $error = null;
        $success = null;
        $username = '';
        try {
            if ($method === 'POST') {
                $action = (string)($_POST['control_action'] ?? '');
                if (!in_array($action, ['setup', 'login', 'update', 'logout'], true)) {
                    http_response_code(400);
                    exit('Accion no valida.');
                }
                if (in_array($action, ['update', 'logout'], true) && !$this->authenticated()) {
                    http_response_code(401);
                    exit('La sesion del control expiro. Ingresa nuevamente.');
                }
                if (!hash_equals($this->csrf(), (string)($_POST['csrf_token'] ?? ''))) {
                    http_response_code(403);
                    exit('La solicitud no es valida. Recarga la pagina.');
                }
                $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
                if ($action === 'setup' || $action === 'login') {
                    $username = trim((string)($_POST['username'] ?? ''));
                    $password = (string)($_POST['password'] ?? '');
                    if ($action === 'setup') {
                        if ($password !== (string)($_POST['password_confirmation'] ?? '')) {
                            throw new DomainException('Las contrasenas no coinciden.');
                        }
                        $result = controlIncapacidadesService::setup((string)($_POST['setup_key'] ?? ''), $username, $password, $ip);
                    } else {
                        $result = controlIncapacidadesService::login($username, $password, $ip);
                    }
                    if ($result['ok']) {
                        session_regenerate_id(true);
                        $account = controlIncapacidadesService::state()['account'];
                        $_SESSION = ['control_user' => $account['username'], 'control_fingerprint' => hash('sha256', $account['password_hash']),
                            'control_activity' => time(), 'control_csrf' => bin2hex(random_bytes(32)), 'control_csrf_time' => time()];
                        $this->redirect();
                    }
                    if ($result['blocked']) {
                        http_response_code(429);
                        header('Retry-After: 900');
                        $error = 'Demasiados intentos. Espera 15 minutos.';
                    } else {
                        $error = $action === 'setup' ? 'La llave de instalacion no es valida.' : 'Las credenciales suministradas no son validas.';
                    }
                } elseif ($action === 'logout') {
                    $_SESSION = [];
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'],
                        'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Strict']);
                    session_destroy();
                    $this->redirect();
                } elseif ($action === 'update') {
                    try {
                        $state = controlIncapacidadesService::state();
                        $values = $this->values($_POST['flags'] ?? null);
                        $revision = (string)($_POST['revision'] ?? '');
                        if (!preg_match('/^(0|[1-9][0-9]{0,9})$/D', $revision)) {
                            throw new DomainException('La version del formulario no es valida.');
                        }
                        if ($values !== $state['flags'] && ($_POST['confirm_critical'] ?? '') !== '1') {
                            throw new DomainException('Confirma los cambios antes de guardar.');
                        }
                        $result = controlIncapacidadesService::save($values, (int)$revision,
                            (string)$_SESSION['control_user'], (string)($_POST['reauth_password'] ?? ''), $ip);
                        if (!$result['ok']) {
                            throw new DomainException($result['blocked'] ? 'Demasiados intentos. Espera 15 minutos.' : 'La contrasena de confirmacion no es valida.');
                        }
                        $_SESSION['control_success'] = $result['changes'] > 0 ? 'Los controles fueron actualizados correctamente.' : 'No habia cambios pendientes.';
                    } catch (DomainException $exception) {
                        $_SESSION['control_error'] = $exception->getMessage();
                    }
                    $this->redirect();
                }
            }
            $state = controlIncapacidadesService::state();
            $configured = $state['account'] !== null;
            $setupAvailable = !$configured && controlIncapacidadesService::setupAvailable();
            $csrfToken = $this->csrf();
            if (!$this->authenticated()) {
                require dirname(__DIR__) . '/views/control-panel/login.php';
                return;
            }
            $success = $_SESSION['control_success'] ?? null;
            $error = $_SESSION['control_error'] ?? $error;
            unset($_SESSION['control_success'], $_SESSION['control_error']);
            $recentChanges = array_slice(array_reverse(array_values(array_filter($state['audit'],
                static fn(array $event): bool => $event['action'] === 'FEATURE_CHANGE'))), 0, 20);
            require dirname(__DIR__) . '/views/control-panel/panel.php';
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
            $configured = controlIncapacidadesService::isConfigured();
            $setupAvailable = !$configured && controlIncapacidadesService::setupAvailable();
            $csrfToken = $this->csrf();
            require dirname(__DIR__) . '/views/control-panel/login.php';
        } catch (Throwable $exception) {
            error_log('Control de Incapacidades: ' . $exception->getMessage());
            http_response_code(503);
            echo 'No fue posible abrir o guardar el panel. Revisa los permisos de su almacenamiento privado y vuelve a intentarlo.';
        }
    }

    private function csrf(): string
    {
        if (!is_string($_SESSION['control_csrf'] ?? null) || time() - (int)($_SESSION['control_csrf_time'] ?? 0) > 1800) {
            $_SESSION['control_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['control_csrf_time'] = time();
        }
        return $_SESSION['control_csrf'];
    }

    private function authenticated(): bool
    {
        $account = controlIncapacidadesService::state()['account'];
        $valid = $account !== null && is_string($_SESSION['control_user'] ?? null)
            && hash_equals($account['username'], $_SESSION['control_user'])
            && hash_equals(hash('sha256', $account['password_hash']), (string)($_SESSION['control_fingerprint'] ?? ''))
            && time() - (int)($_SESSION['control_activity'] ?? 0) < 1800;
        if ($valid) {
            $_SESSION['control_activity'] = time();
        } else {
            unset($_SESSION['control_user'], $_SESSION['control_fingerprint']);
        }
        return $valid;
    }

    private function values(mixed $posted): array
    {
        if (!is_array($posted) || count($posted) !== count(controlIncapacidadesService::CONTROLES)
            || array_diff_key(controlIncapacidadesService::CONTROLES, $posted)) {
            throw new DomainException('El formulario esta incompleto. Recarga la pagina.');
        }
        $values = [];
        foreach (controlIncapacidadesService::CONTROLES as $key => $_definition) {
            if (!in_array($posted[$key], ['0', '1'], true)) {
                throw new DomainException('El estado de un interruptor no es valido.');
            }
            $values[$key] = $posted[$key] === '1';
        }
        return $values;
    }

    private function redirect(): never
    {
        header('Location: index.php', true, 303);
        exit;
    }
}

function control_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function control_asset(string $file): string
{
    return $file . '?v=' . (string)filemtime(dirname(__DIR__) . '/control-incapacidades/' . $file);
}
