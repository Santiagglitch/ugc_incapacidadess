<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/notificacionService.php';

class notificacionController
{
    private notificacionService $service;

    public function __construct()
    {
        $this->service = new notificacionService();
    }

    public function contador(): void
    {
        requiere_login();
        $user = usuario_actual();
        $this->json(['contador' => $this->service->contarNoLeidas((string)$user['cedula'])]);
    }

    public function listar(): void
    {
        requiere_login();
        $user = usuario_actual();
        $this->json(['notificaciones' => $this->service->getNoLeidas((string)$user['cedula'])]);
    }

    public function marcarLeida(int $id): void
    {
        requiere_login();
        $this->validarAjaxCsrf();
        $user = usuario_actual();
        $this->json(['success' => $this->service->marcarLeida($id, (string)$user['cedula'])]);
    }

    public function marcarTodasLeidas(): void
    {
        requiere_login();
        $this->validarAjaxCsrf();
        $user = usuario_actual();
        $this->json(['success' => $this->service->marcarTodasLeidas((string)$user['cedula'])]);
    }

    private function validarAjaxCsrf(): void
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $token = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? ($_POST['_csrf_token'] ?? null);
        if (!validar_csrf($token)) {
            $this->json(['success' => false, 'error' => 'Token invalido'], 422);
        }
    }

    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}