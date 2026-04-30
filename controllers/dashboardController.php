<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/solicitudModel.php';
require_once __DIR__ . '/../models/empleadoModel.php';

class dashboardController
{
    private array $nombreEmpleadoCache = [];

    public function index(): void
    {
        requiere_login();
        $user = usuario_actual();
        $model = new solicitudModel();
        $empleadoModel = new empleadoModel();
        $rol = $user['rol'] ?? '';
        $q = limpiar_texto($_GET['q'] ?? '');
        $tipoFiltro = limpiar_texto($_GET['tipo_solicitud'] ?? '');

        if ($tipoFiltro !== '' && !array_key_exists($tipoFiltro, TIPOS_SOLICITUD)) {
            $tipoFiltro = '';
        }

        if ($rol === ROL_ADMIN) {
            $todas = $this->prepararSolicitudes($model->getAll(), $empleadoModel, $q, $tipoFiltro);
            $stats = $this->contarEstados($todas);
            $stats['TOTAL'] = count($todas);
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
            $historialOpciones = [
                'total' => [
                    'titulo' => 'Solicitudes recientes',
                    'solicitudes' => $todas,
                ],
                'pendiente_jefe' => [
                    'titulo' => 'Solicitudes pendientes de jefe',
                    'solicitudes' => $this->filtrarPorEstado($todas, ESTADO_PENDIENTE_JEFE),
                ],
                'aprobado_jefe' => [
                    'titulo' => 'Solicitudes pendientes de RRHH',
                    'solicitudes' => $this->filtrarPorEstado($todas, ESTADO_APROBADO_JEFE),
                ],
                'aprobado_rrhh' => [
                    'titulo' => 'Solicitudes aprobadas por RRHH',
                    'solicitudes' => $this->filtrarPorEstado($todas, ESTADO_APROBADO_RRHH),
                ],
                'rechazado_rrhh' => [
                    'titulo' => 'Solicitudes rechazadas por RRHH',
                    'solicitudes' => $this->filtrarPorEstado($todas, ESTADO_RECHAZADO_RRHH),
                ],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'total';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            $historialSolicitudes = $historialOpciones[$historialTipo]['solicitudes'];
            render_view('admin/dashboard', compact('user', 'stats', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'q', 'tipoFiltro'));
            return;
        }

        if ($rol === ROL_RRHH) {
            $todas = $this->prepararSolicitudes($model->getHistoricoRRHH(), $empleadoModel, $q, $tipoFiltro);
            $pendientes = $this->ordenarPorFechaCreacionAsc($this->filtrarPorEstado($todas, ESTADO_APROBADO_JEFE));
            $revisionJefe = $this->filtrarPorEstado($todas, ESTADO_PENDIENTE_JEFE);
            $counts = $this->contarEstados($todas);
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'historico');
            $historialOpciones = [
                'pendientes' => [
                    'titulo' => 'Aprobadas por jefe pendientes de RRHH',
                    'solicitudes' => $pendientes,
                ],
                'aprobadas' => [
                    'titulo' => 'Aprobadas por Talento Humano',
                    'solicitudes' => $this->filtrarPorEstado($todas, ESTADO_APROBADO_RRHH),
                ],
                'rechazadas' => [
                    'titulo' => 'Rechazadas por Talento Humano',
                    'solicitudes' => $this->filtrarPorEstado($todas, ESTADO_RECHAZADO_RRHH),
                ],
                'historico' => [
                    'titulo' => 'Historial completo',
                    'solicitudes' => $todas,
                ],
                'revision_jefe' => [
                    'titulo' => 'En revision de jefe',
                    'solicitudes' => $revisionJefe,
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
                'revisionJefe' => count($revisionJefe),
            ];
            render_view('rrhh/dashboard', compact('user', 'stats', 'pendientes', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'q', 'tipoFiltro'));
            return;
        }

        if ($rol === ROL_JEFE) {
            $pendientes = $this->prepararSolicitudes($model->getPendientesJefe($user['cedula']), $empleadoModel, $q, $tipoFiltro);
            $gestionadas = $this->prepararSolicitudes($model->getGestionadasByJefe($user['cedula']), $empleadoModel, $q, $tipoFiltro);
            $misSolicitudes = $this->prepararSolicitudes($model->getByEmpleado($user['cedula']), $empleadoModel, $q, $tipoFiltro);
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
            render_view('jefe/dashboard', compact('user', 'pendientes', 'gestionadas', 'misSolicitudes', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'q', 'tipoFiltro'));
            return;
        }

        $solicitudes = $this->prepararSolicitudes($model->getByEmpleado($user['cedula']), $empleadoModel, $q, $tipoFiltro);
        $stats = $this->resumenEmpleado($solicitudes);
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
        render_view('empleado/dashboard', compact('user', 'solicitudes', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'q', 'tipoFiltro'));
    }

    private function prepararSolicitudes(array $solicitudes, empleadoModel $empleadoModel, string $q, string $tipoFiltro): array
    {
        $solicitudes = $this->filtrarPorTipoSolicitud($solicitudes, $tipoFiltro);

        if (empty($solicitudes)) {
            return [];
        }

        $solicitudes = $this->enriquecerSolicitudes($solicitudes, $empleadoModel);

        return $this->filtrarPorBusqueda($solicitudes, $q);
    }

    private function enriquecerSolicitudes(array $solicitudes, empleadoModel $empleadoModel): array
    {
        $nitsPendientes = [];

        foreach ($solicitudes as $solicitud) {
            foreach (['NIT_EMPLEADO', 'NIT_JEFE'] as $nitCampo) {
                $nit = (string)($solicitud[$nitCampo] ?? '');
                if ($nit === '' || array_key_exists($nit, $this->nombreEmpleadoCache)) {
                    continue;
                }

                if (APP_ENV === 'development' && isset(USUARIOS_PRUEBA[$nit])) {
                    $this->nombreEmpleadoCache[$nit] = (string)(USUARIOS_PRUEBA[$nit]['nombre'] ?? $nit);
                    continue;
                }

                $nitsPendientes[$nit] = $nit;
            }
        }

        if (!empty($nitsPendientes)) {
            foreach ($empleadoModel->getNombresPorNit(array_values($nitsPendientes)) as $nit => $nombre) {
                $this->nombreEmpleadoCache[(string)$nit] = (string)$nombre;
            }

            foreach ($nitsPendientes as $nit) {
                $this->nombreEmpleadoCache[$nit] ??= '';
            }
        }

        foreach ($solicitudes as &$solicitud) {
            foreach (['NIT_EMPLEADO' => 'NOMBRE_EMPLEADO', 'NIT_JEFE' => 'NOMBRE_JEFE'] as $nitCampo => $nombreCampo) {
                $nit = (string)($solicitud[$nitCampo] ?? '');
                if ($nit === '') {
                    $solicitud[$nombreCampo] = '';
                    continue;
                }

                $solicitud[$nombreCampo] = $this->nombreEmpleadoCache[$nit] ?? '';
            }
        }
        unset($solicitud);

        return $solicitudes;
    }

    private function filtrarPorTipoSolicitud(array $solicitudes, string $tipoFiltro): array
    {
        if ($tipoFiltro === '') {
            return $solicitudes;
        }

        return array_values(array_filter(
            $solicitudes,
            static fn(array $solicitud): bool => (string)($solicitud['TIPO_SOLICITUD'] ?? '') === $tipoFiltro
        ));
    }

    private function filtrarPorBusqueda(array $solicitudes, string $q): array
    {
        $q = $this->normalizarBusqueda($q);

        if ($q === '') {
            return $solicitudes;
        }

        return array_values(array_filter($solicitudes, function (array $solicitud) use ($q): bool {
            return $this->coincideBusqueda($solicitud, $q);
        }));
    }

    private function coincideBusqueda(array $solicitud, string $qNormalizada): bool
    {
        $tipo = (string)($solicitud['TIPO_SOLICITUD'] ?? '');
        $texto = implode(' ', [
            $solicitud['ID'] ?? '',
            $solicitud['NIT_EMPLEADO'] ?? '',
            $solicitud['NIT_JEFE'] ?? '',
            $solicitud['NIT_RRHH'] ?? '',
            $solicitud['NOMBRE_EMPLEADO'] ?? '',
            $solicitud['NOMBRE_JEFE'] ?? '',
            $tipo,
            TIPOS_SOLICITUD[$tipo] ?? '',
            $solicitud['ESTADO'] ?? '',
            $solicitud['OBSERVACIONES'] ?? '',
        ]);

        return str_contains($this->normalizarBusqueda($texto), $qNormalizada);
    }

    private function normalizarBusqueda(string $texto): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        $texto = function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);

        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($ascii !== false) {
                $texto = $ascii;
            }
        }

        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/i', ' ', $texto) ?? '';

        return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    }

    private function contarEstados(array $solicitudes): array
    {
        $result = [];
        foreach ($solicitudes as $solicitud) {
            $estado = (string)($solicitud['ESTADO'] ?? '');
            if ($estado === '') {
                continue;
            }
            $result[$estado] = ($result[$estado] ?? 0) + 1;
        }

        return $result;
    }

    private function filtrarPorEstado(array $solicitudes, string $estado): array
    {
        return array_values(array_filter($solicitudes, static fn(array $s): bool => (string)($s['ESTADO'] ?? '') === $estado));
    }

    private function ordenarPorFechaCreacionAsc(array $solicitudes): array
    {
        usort($solicitudes, static function (array $a, array $b): int {
            $fecha = strcmp((string)($a['FECHA_CREACION'] ?? ''), (string)($b['FECHA_CREACION'] ?? ''));

            if ($fecha !== 0) {
                return $fecha;
            }

            return (int)($a['ID'] ?? 0) <=> (int)($b['ID'] ?? 0);
        });

        return $solicitudes;
    }

    private function resumenEmpleado(array $solicitudes): array
    {
        return [
            'total' => count($solicitudes),
            'pendientes' => count(array_filter($solicitudes, static fn(array $s): bool => (string)($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE)),
            'aprobadas' => count(array_filter($solicitudes, static fn(array $s): bool => in_array((string)($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true))),
            'rechazadas' => count(array_filter($solicitudes, static fn(array $s): bool => in_array((string)($s['ESTADO'] ?? ''), [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH], true))),
        ];
    }
}
