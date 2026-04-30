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
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
            $historialOpciones = [
                'total' => [
                    'titulo' => 'Solicitudes recientes',
                    'solicitudes' => $todas,
                ],
                'pendiente_jefe' => [
                    'titulo' => 'Solicitudes pendientes de jefe',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_PENDIENTE_JEFE]),
                ],
                'aprobado_jefe' => [
                    'titulo' => 'Solicitudes pendientes de RRHH',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_APROBADO_JEFE]),
                ],
                'aprobado_rrhh' => [
                    'titulo' => 'Solicitudes aprobadas por RRHH',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_APROBADO_RRHH]),
                ],
                'rechazado_rrhh' => [
                    'titulo' => 'Solicitudes rechazadas por RRHH',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_RECHAZADO_RRHH]),
                ],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'total';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            $historialSolicitudes = $historialOpciones[$historialTipo]['solicitudes'];
            render_view('admin/dashboard', compact('user', 'stats', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes'));
            return;
        }

        if ($rol === ROL_RRHH) {
            $pendientes = $model->getPendientesRRHH();
            $todas = $model->getHistoricoRRHH();
            $counts = $model->contarPorEstado();
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'historico');
            $historialOpciones = [
                'pendientes' => [
                    'titulo' => 'Aprobadas por jefe pendientes de RRHH',
                    'solicitudes' => $pendientes,
                ],
                'aprobadas' => [
                    'titulo' => 'Aprobadas por Talento Humano',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_APROBADO_RRHH]),
                ],
                'rechazadas' => [
                    'titulo' => 'Rechazadas por Talento Humano',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_RECHAZADO_RRHH]),
                ],
                'historico' => [
                    'titulo' => 'Historial completo',
                    'solicitudes' => $todas,
                ],
                'revision_jefe' => [
                    'titulo' => 'En revision de jefe',
                    'solicitudes' => $model->getAll(['estado' => ESTADO_PENDIENTE_JEFE]),
                ],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'historico';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            $historialSolicitudes = $historialOpciones[$historialTipo]['solicitudes'];
            $stats = [
                'pendientes' => count($pendientes),
                'aprobadas' => (int) ($counts[ESTADO_APROBADO_RRHH] ?? 0),
                'rechazadas' => (int) ($counts[ESTADO_RECHAZADO_RRHH] ?? 0),
                'historico' => count($todas),
                'revisionJefe' => (int) ($counts[ESTADO_PENDIENTE_JEFE] ?? 0),
            ];
            render_view('rrhh/dashboard', compact('user', 'stats', 'pendientes', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes'));
            return;
        }

        if ($rol === ROL_JEFE) {
            $pendientes = $model->getPendientesJefe($user['cedula']);
            $gestionadas = $model->getGestionadasByJefe($user['cedula']);
            $misSolicitudes = $model->getByEmpleado($user['cedula']);
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'gestionadas');
            $historialOpciones = [
                'pendientes' => [
                    'titulo' => 'Solicitudes pendientes de tu aprobacion',
                    'solicitudes' => $pendientes,
                ],
                'aprobadas' => [
                    'titulo' => 'Solicitudes aprobadas por ti',
                    'solicitudes' => array_values(array_filter($gestionadas, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH], true))),
                ],
                'rechazadas' => [
                    'titulo' => 'Solicitudes rechazadas por ti',
                    'solicitudes' => array_values(array_filter($gestionadas, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_RECHAZADO_JEFE)),
                ],
                'mis_solicitudes' => [
                    'titulo' => 'Mis solicitudes personales',
                    'solicitudes' => $misSolicitudes,
                ],
                'gestionadas' => [
                    'titulo' => 'Historial gestionado',
                    'solicitudes' => $gestionadas,
                ],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'gestionadas';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            $historialSolicitudes = $historialOpciones[$historialTipo]['solicitudes'];
            $stats = [
                'pendientes' => count($pendientes),
                'aprobadas' => count(array_filter($gestionadas, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH], true))),
                'rechazadas' => count(array_filter($gestionadas, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_RECHAZADO_JEFE)),
                'misSolicitudes' => count($misSolicitudes),
                'gestionadas' => count($gestionadas),
            ];
            render_view('jefe/dashboard', compact('user', 'pendientes', 'gestionadas', 'misSolicitudes', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes'));
            return;
        }

        $solicitudes = $model->getByEmpleado($user['cedula']);
        $stats = $model->getResumenEmpleado($user['cedula']);
        $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
        $historialOpciones = [
            'total' => [
                'titulo' => 'Mis solicitudes recientes',
                'solicitudes' => $solicitudes,
            ],
            'pendientes' => [
                'titulo' => 'Mis solicitudes pendientes',
                'solicitudes' => array_values(array_filter($solicitudes, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE)),
            ],
            'aprobadas' => [
                'titulo' => 'Mis solicitudes aprobadas',
                'solicitudes' => array_values(array_filter($solicitudes, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true))),
            ],
            'rechazadas' => [
                'titulo' => 'Mis solicitudes rechazadas',
                'solicitudes' => array_values(array_filter($solicitudes, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH], true))),
            ],
        ];

        if (!array_key_exists($historialTipo, $historialOpciones)) {
            $historialTipo = 'total';
        }

        $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
        $historialSolicitudes = $historialOpciones[$historialTipo]['solicitudes'];
        render_view('empleado/dashboard', compact('user', 'solicitudes', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes'));
    }
}
