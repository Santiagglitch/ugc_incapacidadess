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