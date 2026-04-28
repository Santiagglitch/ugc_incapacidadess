<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/solicitudModel.php';
require_once __DIR__ . '/../models/empleadoModel.php';

class dashboardController
{
    public function index(): void
    {
        requiere_login();
        $user = usuario_actual();
        $model = new solicitudModel();
        $rol = $user['rol'] ?? '';

        if ($rol === ROL_ADMIN) {
            $stats = $model->contarPorEstado();
            $stats['TOTAL'] = array_sum($stats);
            $todas = $model->getAll();
            render_view('admin/dashboard', compact('user', 'stats', 'todas'));
            return;
        }

        if ($rol === ROL_RRHH) {
            $pendientes = $model->getPendientesRRHH();
            $todas = $model->getHistoricoRRHH();
            $counts = $model->contarPorEstado();
            $stats = [
                'pendientes' => count($pendientes),
                'aprobadas' => (int) ($counts[ESTADO_APROBADO_RRHH] ?? 0),
                'rechazadas' => (int) ($counts[ESTADO_RECHAZADO_RRHH] ?? 0),
                'historico' => (int) ($counts[ESTADO_APROBADO_RRHH] ?? 0) + (int) ($counts[ESTADO_RECHAZADO_RRHH] ?? 0),
                'revisionJefe' => (int) ($counts[ESTADO_PENDIENTE_JEFE] ?? 0),
            ];
            render_view('rrhh/dashboard', compact('user', 'stats', 'pendientes', 'todas'));
            return;
        }

        if ($rol === ROL_JEFE) {
            $pendientes = $model->getPendientesJefe($user['cedula']);
            $gestionadas = $model->getGestionadasByJefe($user['cedula']);
            $misSolicitudes = $model->getByEmpleado($user['cedula']);
            $stats = [
                'pendientes' => count($pendientes),
                'aprobadas' => count(array_filter($gestionadas, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true))),
                'rechazadas' => count(array_filter($gestionadas, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_RECHAZADO_JEFE)),
                'misSolicitudes' => count($misSolicitudes),
                'gestionadas' => count($gestionadas),
            ];
            render_view('jefe/dashboard', compact('user', 'pendientes', 'gestionadas', 'misSolicitudes', 'stats'));
            return;
        }

        $solicitudes = $model->getByEmpleado($user['cedula']);
        $stats = $model->getResumenEmpleado($user['cedula']);
        render_view('empleado/dashboard', compact('user', 'solicitudes', 'stats'));
    }
}
