<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/controlIncapacidadesService.php';

// Integracion del portal. Los estados se cambian exclusivamente en control-incapacidades/.
function app_feature_enabled(string $key): bool
{
    try {
        return controlIncapacidadesService::enabled($key);
    } catch (Throwable $exception) {
        error_log('No fue posible leer las funcionalidades del portal: ' . $exception->getMessage());
        http_response_code(503);
        exit('El portal no esta disponible temporalmente. Intenta nuevamente mas tarde.');
    }
}

function app_route_feature(string $view, string $action, string $method): ?string
{
    $views = ['solicitud_crear' => 'SOLICITUDES_CREAR', 'solicitud_editar' => 'SOLICITUDES_EDITAR',
        'admin_empleados_export' => 'EXPORTACIONES', 'superadmin_auditoria_export' => 'EXPORTACIONES'];
    $actions = ['solicitud_create' => 'SOLICITUDES_CREAR', 'solicitud_update' => 'SOLICITUDES_EDITAR',
        'solicitud_delete' => 'SOLICITUDES_ELIMINAR', 'solicitud_cancelar_agenda' => 'SOLICITUDES_CANCELAR',
        'solicitud_jefe' => 'GESTION_JEFES', 'solicitud_rrhh' => 'GESTION_RRHH'];
    return $method === 'GET' ? ($views[$view] ?? null) : ($actions[$action] ?? null);
}

function app_require_feature(string $key): void
{
    if (!app_feature_enabled($key)) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        exit('<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title></title></head><body></body></html>');
    }
}
