<?php

declare(strict_types=1);

require_once __DIR__ . '/seguridad.php';

function url_view(string $view = 'login'): string
{
    return app_base_url('index.php?view=' . urlencode($view));
}

function url_action(string $action): string
{
    return app_base_url('index.php?action=' . urlencode($action));
}

function redirect_to(string $url): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Location: ' . $url);
    exit;
}

function app_url_interna(string $url): bool
{
    $url = trim($url);

    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return false;
    }

    if (!empty($parts['scheme']) || !empty($parts['host'])) {
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string)($parts['host'] ?? ''));
        $requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($requestHost !== '') {
            $hostWithPort = $host . (isset($parts['port']) ? ':' . (int)$parts['port'] : '');
            if ($host !== preg_replace('/:\d+$/', '', $requestHost) && $hostWithPort !== $requestHost) {
                return false;
            }
        } elseif ($host !== '') {
            return false;
        }
    }

    $basePath = '/' . trim((string)(parse_url(app_base_url(''), PHP_URL_PATH) ?: '/'), '/');
    $basePath = $basePath === '/' ? '/' : rtrim($basePath, '/');
    $path = (string)($parts['path'] ?? '');

    if ($path === '') {
        return false;
    }

    if ($basePath === '/') {
        return str_starts_with($path, '/');
    }

    return $path === $basePath || str_starts_with($path, $basePath . '/');
}

function url_retorno_segura(?string $url, string $fallback): string
{
    $url = (string)($url ?? '');

    return app_url_interna($url) ? $url : $fallback;
}

function flash_set(string $type, string $message): void
{
    iniciar_sesion_segura();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    iniciar_sesion_segura();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function render_view(string $view, array $data = [], string $layout = 'main'): void
{
    iniciar_sesion_segura();
    aplicar_headers_seguridad();

    $viewPath = dirname(__DIR__) . '/views/' . $view . '.php';
    $layoutPath = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';

    if (!is_file($viewPath)) {
        http_response_code(404);
        exit('Vista no encontrada.');
    }

    extract($data, EXTR_SKIP);
    $user = $user ?? usuario_actual();
    $flash = $flash ?? flash_get();

    ob_start();
    require $viewPath;
    $content = ob_get_clean();

    require $layoutPath;
}
