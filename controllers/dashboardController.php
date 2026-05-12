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
            if ($q === '') {
                $filtrosBase = $tipoFiltro !== '' ? ['tipo' => $tipoFiltro] : [];
                $counts = $model->contarPorEstado($filtrosBase);
                $stats = $counts;
                $stats['TOTAL'] = $this->sumarConteos($counts);
                $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
                $historialOpciones = [
                    'total' => ['titulo' => 'Historial completo', 'filtros' => $filtrosBase, 'orden' => 'FECHA_CREACION DESC'],
                    'pendiente_jefe' => ['titulo' => 'Solicitudes pendientes de jefe', 'filtros' => $filtrosBase + ['estado' => ESTADO_PENDIENTE_JEFE], 'orden' => 'FECHA_CREACION DESC'],
                    'aprobado_jefe' => ['titulo' => 'Solicitudes pendientes de RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_JEFE], 'orden' => 'FECHA_CREACION DESC'],
                    'aprobado_rrhh' => ['titulo' => 'Solicitudes aprobadas por RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                    'rechazado_rrhh' => ['titulo' => 'Solicitudes rechazadas por RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_RECHAZADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                    'canceladas' => ['titulo' => 'Canceladas por el usuario', 'filtros' => $filtrosBase + ['estado' => ESTADO_CANCELADO_AGENDA], 'orden' => 'FECHA_MODIFICACION DESC'],
                ];

                if (!array_key_exists($historialTipo, $historialOpciones)) {
                    $historialTipo = 'total';
                }

                $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
                $pagina = max(1, (int)($_GET['pag_admin'] ?? 1));
                $paginaHistorial = $model->getAllPaginado(
                    $historialOpciones[$historialTipo]['filtros'],
                    $pagina,
                    APP_ROWS_PER_PAGE,
                    $historialOpciones[$historialTipo]['orden']
                );
                $historialSolicitudes = $this->enriquecerSolicitudes($paginaHistorial['rows'], $empleadoModel);
                $historialPaginationData = $paginaHistorial['pagination'];
                $todas = [];
                render_view('admin/dashboard', compact('user', 'stats', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
                return;
            }

            $filtrosBase = $this->filtrosBase($q, $tipoFiltro);
            $counts = $model->contarPorEstado($filtrosBase);
            $stats = $counts;
            $stats['TOTAL'] = $this->sumarConteos($counts);
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
            $historialOpciones = [
                'total' => ['titulo' => 'Historial completo', 'filtros' => $filtrosBase, 'orden' => 'FECHA_CREACION DESC'],
                'pendiente_jefe' => ['titulo' => 'Solicitudes pendientes de jefe', 'filtros' => $filtrosBase + ['estado' => ESTADO_PENDIENTE_JEFE], 'orden' => 'FECHA_CREACION DESC'],
                'aprobado_jefe' => ['titulo' => 'Solicitudes pendientes de RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_JEFE], 'orden' => 'FECHA_CREACION DESC'],
                'aprobado_rrhh' => ['titulo' => 'Solicitudes aprobadas por RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                'rechazado_rrhh' => ['titulo' => 'Solicitudes rechazadas por RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_RECHAZADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                'canceladas' => ['titulo' => 'Canceladas por el usuario', 'filtros' => $filtrosBase + ['estado' => ESTADO_CANCELADO_AGENDA], 'orden' => 'FECHA_MODIFICACION DESC'],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'total';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            $paginaHistorial = $model->getAllPaginado(
                $historialOpciones[$historialTipo]['filtros'],
                max(1, (int)($_GET['pag_admin'] ?? 1)),
                APP_ROWS_PER_PAGE,
                $historialOpciones[$historialTipo]['orden']
            );
            $historialSolicitudes = $this->enriquecerSolicitudes($paginaHistorial['rows'], $empleadoModel);
            $historialPaginationData = $paginaHistorial['pagination'];
            $todas = [];
            render_view('admin/dashboard', compact('user', 'stats', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
            return;
        }

        if ($rol === ROL_RRHH) {
            if ($q === '') {
                $cedula = (string)($user['cedula'] ?? '');
                $filtrosBase = $tipoFiltro !== '' ? ['tipo' => $tipoFiltro] : [];
                $counts = $model->contarPorEstado($filtrosBase);
                $historialTipo = limpiar_texto($_GET['historial'] ?? 'historico');
                $historialOpciones = [
                    'pendientes' => ['titulo' => 'Aprobadas por jefe pendientes de RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_JEFE], 'orden' => 'ID ASC'],
                    'aprobadas' => ['titulo' => 'Aprobadas por Talento Humano', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                    'rechazadas' => ['titulo' => 'Rechazadas por Talento Humano', 'filtros' => $filtrosBase + ['estado' => ESTADO_RECHAZADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                    'canceladas' => ['titulo' => 'Canceladas por el usuario', 'filtros' => $filtrosBase + ['estado' => ESTADO_CANCELADO_AGENDA], 'orden' => 'FECHA_MODIFICACION DESC'],
                    'historico' => ['titulo' => 'Historial completo', 'filtros' => $filtrosBase, 'orden' => 'FECHA_CREACION DESC'],
                    'revision_jefe' => ['titulo' => 'En revision de jefe', 'filtros' => $filtrosBase + ['estado' => ESTADO_PENDIENTE_JEFE], 'orden' => 'FECHA_CREACION DESC'],
                    'mis_solicitudes' => ['titulo' => 'Mis solicitudes personales', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula], 'orden' => 'FECHA_CREACION DESC'],
                ];

                if (!array_key_exists($historialTipo, $historialOpciones)) {
                    $historialTipo = 'historico';
                }

                $pendientesPage = $model->getAllPaginado(
                    $filtrosBase + ['estado' => ESTADO_APROBADO_JEFE],
                    max(1, (int)($_GET['pag_pendientes'] ?? 1)),
                    APP_ROWS_PER_PAGE,
                    'ID ASC'
                );
                $pendientes = $this->enriquecerSolicitudes($pendientesPage['rows'], $empleadoModel);
                $pendientesPaginationData = $pendientesPage['pagination'];

                $misPage = $model->getByEmpleadoPaginado(
                    $cedula,
                    max(1, (int)($_GET['pag_mis'] ?? 1)),
                    APP_ROWS_PER_PAGE,
                    $filtrosBase
                );
                $misSolicitudes = $this->enriquecerSolicitudes($misPage['rows'], $empleadoModel);
                $misPaginationData = $misPage['pagination'];

                $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
                if (
                    $historialTipo === 'pendientes'
                    && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_pendientes'] ?? 1))
                ) {
                    $historialPage = $pendientesPage;
                } elseif (
                    $historialTipo === 'mis_solicitudes'
                    && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_mis'] ?? 1))
                ) {
                    $historialPage = $misPage;
                } else {
                    $historialPage = $model->getAllPaginado(
                        $historialOpciones[$historialTipo]['filtros'],
                        max(1, (int)($_GET['pag_historial'] ?? 1)),
                        APP_ROWS_PER_PAGE,
                        $historialOpciones[$historialTipo]['orden']
                    );
                }
                $historialSolicitudes = $this->enriquecerSolicitudes($historialPage['rows'], $empleadoModel);
                $historialPaginationData = $historialPage['pagination'];
                $todas = [];
                $stats = [
                    'pendientes' => (int)($counts[ESTADO_APROBADO_JEFE] ?? 0),
                    'aprobadas' => (int)($counts[ESTADO_APROBADO_RRHH] ?? 0),
                    'rechazadas' => (int)($counts[ESTADO_RECHAZADO_RRHH] ?? 0),
                    'canceladas' => (int)($counts[ESTADO_CANCELADO_AGENDA] ?? 0),
                    'historico' => $this->sumarConteos($counts),
                    'revisionJefe' => (int)($counts[ESTADO_PENDIENTE_JEFE] ?? 0),
                    'misSolicitudes' => $model->contarTotal($filtrosBase + ['nit_empleado' => $cedula]),
                ];
                render_view('rrhh/dashboard', compact('user', 'stats', 'pendientes', 'pendientesPaginationData', 'misSolicitudes', 'misPaginationData', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
                return;
            }

            $cedula = (string)($user['cedula'] ?? '');
            $filtrosBase = $this->filtrosBase($q, $tipoFiltro);
            $counts = $model->contarPorEstado($filtrosBase);
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'historico');
            $historialOpciones = [
                'pendientes' => ['titulo' => 'Aprobadas por jefe pendientes de RRHH', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_JEFE], 'orden' => 'ID ASC'],
                'aprobadas' => ['titulo' => 'Aprobadas por Talento Humano', 'filtros' => $filtrosBase + ['estado' => ESTADO_APROBADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                'rechazadas' => ['titulo' => 'Rechazadas por Talento Humano', 'filtros' => $filtrosBase + ['estado' => ESTADO_RECHAZADO_RRHH], 'orden' => 'FECHA_CREACION DESC'],
                'canceladas' => ['titulo' => 'Canceladas por el usuario', 'filtros' => $filtrosBase + ['estado' => ESTADO_CANCELADO_AGENDA], 'orden' => 'FECHA_MODIFICACION DESC'],
                'historico' => ['titulo' => 'Historial completo', 'filtros' => $filtrosBase, 'orden' => 'FECHA_CREACION DESC'],
                'revision_jefe' => ['titulo' => 'En revision de jefe', 'filtros' => $filtrosBase + ['estado' => ESTADO_PENDIENTE_JEFE], 'orden' => 'FECHA_CREACION DESC'],
                'mis_solicitudes' => ['titulo' => 'Mis solicitudes personales', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula], 'orden' => 'FECHA_CREACION DESC'],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'historico';
            }

            $pendientesPage = $model->getAllPaginado(
                $filtrosBase + ['estado' => ESTADO_APROBADO_JEFE],
                max(1, (int)($_GET['pag_pendientes'] ?? 1)),
                APP_ROWS_PER_PAGE,
                'ID ASC'
            );
            $pendientes = $this->enriquecerSolicitudes($pendientesPage['rows'], $empleadoModel);
            $pendientesPaginationData = $pendientesPage['pagination'];

            $misPage = $model->getByEmpleadoPaginado(
                $cedula,
                max(1, (int)($_GET['pag_mis'] ?? 1)),
                APP_ROWS_PER_PAGE,
                $filtrosBase
            );
            $misSolicitudes = $this->enriquecerSolicitudes($misPage['rows'], $empleadoModel);
            $misPaginationData = $misPage['pagination'];

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            if (
                $historialTipo === 'pendientes'
                && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_pendientes'] ?? 1))
            ) {
                $historialPage = $pendientesPage;
            } elseif (
                $historialTipo === 'mis_solicitudes'
                && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_mis'] ?? 1))
            ) {
                $historialPage = $misPage;
            } else {
                $historialPage = $model->getAllPaginado(
                    $historialOpciones[$historialTipo]['filtros'],
                    max(1, (int)($_GET['pag_historial'] ?? 1)),
                    APP_ROWS_PER_PAGE,
                    $historialOpciones[$historialTipo]['orden']
                );
            }
            $historialSolicitudes = $this->enriquecerSolicitudes($historialPage['rows'], $empleadoModel);
            $historialPaginationData = $historialPage['pagination'];
            $todas = [];
            $stats = [
                'pendientes' => (int)($counts[ESTADO_APROBADO_JEFE] ?? 0),
                'aprobadas' => (int) ($counts[ESTADO_APROBADO_RRHH] ?? 0),
                'rechazadas' => (int) ($counts[ESTADO_RECHAZADO_RRHH] ?? 0),
                'canceladas' => (int)($counts[ESTADO_CANCELADO_AGENDA] ?? 0),
                'historico' => $this->sumarConteos($counts),
                'revisionJefe' => (int)($counts[ESTADO_PENDIENTE_JEFE] ?? 0),
                'misSolicitudes' => $model->contarTotal($filtrosBase + ['nit_empleado' => $cedula]),
            ];
            render_view('rrhh/dashboard', compact('user', 'stats', 'pendientes', 'pendientesPaginationData', 'misSolicitudes', 'misPaginationData', 'todas', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
            return;
        }

        if ($rol === ROL_JEFE) {
            if ($q === '') {
                $cedula = (string)($user['cedula'] ?? '');
                $filtrosBase = $tipoFiltro !== '' ? ['tipo' => $tipoFiltro] : [];
                $gestionadasEstados = [ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH, ESTADO_CANCELADO_AGENDA];
                $aprobadasEstados = [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH];

                $pendientesPage = $model->getPendientesJefePaginado(
                    $cedula,
                    max(1, (int)($_GET['pag_pendientes'] ?? 1)),
                    APP_ROWS_PER_PAGE,
                    $filtrosBase
                );
                $pendientes = $this->enriquecerSolicitudes($pendientesPage['rows'], $empleadoModel);
                $pendientesPaginationData = $pendientesPage['pagination'];

                $misPage = $model->getByEmpleadoPaginado(
                    $cedula,
                    max(1, (int)($_GET['pag_mis'] ?? 1)),
                    APP_ROWS_PER_PAGE,
                    $filtrosBase
                );
                $misSolicitudes = $this->enriquecerSolicitudes($misPage['rows'], $empleadoModel);
                $misPaginationData = $misPage['pagination'];

                $historialTipo = limpiar_texto($_GET['historial'] ?? 'gestionadas');
                $historialOpciones = [
                    'pendientes' => [
                        'titulo' => 'Solicitudes pendientes de tu aprobacion',
                        'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estado' => ESTADO_PENDIENTE_JEFE],
                        'orden' => 'ID ASC',
                    ],
                    'aprobadas' => [
                        'titulo' => 'Solicitudes aprobadas por ti',
                        'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estados' => $aprobadasEstados],
                        'orden' => 'FECHA_GESTION_JEFE DESC',
                    ],
                    'rechazadas' => [
                        'titulo' => 'Solicitudes rechazadas por ti',
                        'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estado' => ESTADO_RECHAZADO_JEFE],
                        'orden' => 'FECHA_GESTION_JEFE DESC',
                    ],
                    'canceladas' => [
                        'titulo' => 'Solicitudes canceladas por el usuario',
                        'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estado' => ESTADO_CANCELADO_AGENDA],
                        'orden' => 'FECHA_MODIFICACION DESC',
                    ],
                    'mis_solicitudes' => [
                        'titulo' => 'Mis solicitudes personales',
                        'filtros' => $filtrosBase + ['nit_empleado' => $cedula],
                        'orden' => 'FECHA_CREACION DESC',
                    ],
                    'gestionadas' => [
                        'titulo' => 'Historial gestionado',
                        'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estados' => $gestionadasEstados],
                        'orden' => 'FECHA_GESTION_JEFE DESC',
                    ],
                ];

                if (!array_key_exists($historialTipo, $historialOpciones)) {
                    $historialTipo = 'gestionadas';
                }

                $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
                if (
                    $historialTipo === 'pendientes'
                    && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_pendientes'] ?? 1))
                ) {
                    $historialPage = $pendientesPage;
                } elseif (
                    $historialTipo === 'mis_solicitudes'
                    && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_mis'] ?? 1))
                ) {
                    $historialPage = $misPage;
                } else {
                    $historialPage = $model->getAllPaginado(
                        $historialOpciones[$historialTipo]['filtros'],
                        max(1, (int)($_GET['pag_historial'] ?? 1)),
                        APP_ROWS_PER_PAGE,
                        $historialOpciones[$historialTipo]['orden']
                    );
                }
                $historialSolicitudes = $this->enriquecerSolicitudes($historialPage['rows'], $empleadoModel);
                $historialPaginationData = $historialPage['pagination'];
                $gestionadas = $historialTipo === 'gestionadas' ? $historialSolicitudes : [];
                $stats = $this->estadisticasJefe($model, $filtrosBase, $cedula, $gestionadasEstados, $aprobadasEstados);
                render_view('jefe/dashboard', compact('user', 'pendientes', 'pendientesPaginationData', 'gestionadas', 'misSolicitudes', 'misPaginationData', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
                return;
            }

            $cedula = (string)($user['cedula'] ?? '');
            $filtrosBase = $this->filtrosBase($q, $tipoFiltro);
            $gestionadasEstados = [ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH, ESTADO_CANCELADO_AGENDA];
            $aprobadasEstados = [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH];

            $pendientesPage = $model->getPendientesJefePaginado(
                $cedula,
                max(1, (int)($_GET['pag_pendientes'] ?? 1)),
                APP_ROWS_PER_PAGE,
                $filtrosBase
            );
            $pendientes = $this->enriquecerSolicitudes($pendientesPage['rows'], $empleadoModel);
            $pendientesPaginationData = $pendientesPage['pagination'];

            $misPage = $model->getByEmpleadoPaginado(
                $cedula,
                max(1, (int)($_GET['pag_mis'] ?? 1)),
                APP_ROWS_PER_PAGE,
                $filtrosBase
            );
            $misSolicitudes = $this->enriquecerSolicitudes($misPage['rows'], $empleadoModel);
            $misPaginationData = $misPage['pagination'];

            $historialTipo = limpiar_texto($_GET['historial'] ?? 'gestionadas');
            $historialOpciones = [
                'pendientes' => [
                    'titulo' => 'Solicitudes pendientes de tu aprobacion',
                    'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estado' => ESTADO_PENDIENTE_JEFE],
                    'orden' => 'ID ASC',
                ],
                'aprobadas' => [
                    'titulo' => 'Solicitudes aprobadas por ti',
                    'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estados' => $aprobadasEstados],
                    'orden' => 'FECHA_GESTION_JEFE DESC',
                ],
                'rechazadas' => [
                    'titulo' => 'Solicitudes rechazadas por ti',
                    'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estado' => ESTADO_RECHAZADO_JEFE],
                    'orden' => 'FECHA_GESTION_JEFE DESC',
                ],
                'canceladas' => [
                    'titulo' => 'Solicitudes canceladas por el usuario',
                    'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estado' => ESTADO_CANCELADO_AGENDA],
                    'orden' => 'FECHA_MODIFICACION DESC',
                ],
                'mis_solicitudes' => [
                    'titulo' => 'Mis solicitudes personales',
                    'filtros' => $filtrosBase + ['nit_empleado' => $cedula],
                    'orden' => 'FECHA_CREACION DESC',
                ],
                'gestionadas' => [
                    'titulo' => 'Historial gestionado',
                    'filtros' => $filtrosBase + ['nit_jefe' => $cedula, 'excluir_empleado' => $cedula, 'estados' => $gestionadasEstados],
                    'orden' => 'FECHA_GESTION_JEFE DESC',
                ],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'gestionadas';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            if (
                $historialTipo === 'pendientes'
                && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_pendientes'] ?? 1))
            ) {
                $historialPage = $pendientesPage;
            } elseif (
                $historialTipo === 'mis_solicitudes'
                && max(1, (int)($_GET['pag_historial'] ?? 1)) === max(1, (int)($_GET['pag_mis'] ?? 1))
            ) {
                $historialPage = $misPage;
            } else {
                $historialPage = $model->getAllPaginado(
                    $historialOpciones[$historialTipo]['filtros'],
                    max(1, (int)($_GET['pag_historial'] ?? 1)),
                    APP_ROWS_PER_PAGE,
                    $historialOpciones[$historialTipo]['orden']
                );
            }
            $historialSolicitudes = $this->enriquecerSolicitudes($historialPage['rows'], $empleadoModel);
            $historialPaginationData = $historialPage['pagination'];
            $gestionadas = $historialTipo === 'gestionadas' ? $historialSolicitudes : [];
            $stats = $this->estadisticasJefe($model, $filtrosBase, $cedula, $gestionadasEstados, $aprobadasEstados);
            render_view('jefe/dashboard', compact('user', 'pendientes', 'pendientesPaginationData', 'gestionadas', 'misSolicitudes', 'misPaginationData', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
            return;
        }

        if ($q === '') {
            $cedula = (string)($user['cedula'] ?? '');
            $filtrosBase = $tipoFiltro !== '' ? ['tipo' => $tipoFiltro] : [];
            $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
            $historialOpciones = [
                'total' => ['titulo' => 'Historial completo de mis solicitudes', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula]],
                'pendientes' => ['titulo' => 'Mis solicitudes pendientes', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estado' => ESTADO_PENDIENTE_JEFE]],
                'aprobadas' => ['titulo' => 'Mis solicitudes aprobadas', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estados' => [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH]]],
                'rechazadas' => ['titulo' => 'Mis solicitudes rechazadas', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estados' => [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH]]],
                'canceladas' => ['titulo' => 'Mis solicitudes canceladas', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estado' => ESTADO_CANCELADO_AGENDA], 'orden' => 'FECHA_MODIFICACION DESC'],
            ];

            if (!array_key_exists($historialTipo, $historialOpciones)) {
                $historialTipo = 'total';
            }

            $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
            $page = $model->getAllPaginado(
                $historialOpciones[$historialTipo]['filtros'],
                max(1, (int)($_GET['pag_mis'] ?? 1)),
                APP_ROWS_PER_PAGE,
                $historialOpciones[$historialTipo]['orden'] ?? 'FECHA_CREACION DESC'
            );
            $historialSolicitudes = $this->enriquecerSolicitudes($page['rows'], $empleadoModel);
            $historialPaginationData = $page['pagination'];
            $solicitudes = $historialSolicitudes;
            $stats = $this->estadisticasEmpleado($model, $filtrosBase, $cedula);
            render_view('empleado/dashboard', compact('user', 'solicitudes', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
            return;
        }

        $cedula = (string)($user['cedula'] ?? '');
        $filtrosBase = $this->filtrosBase($q, $tipoFiltro);
        $historialTipo = limpiar_texto($_GET['historial'] ?? 'total');
        $historialOpciones = [
            'total' => ['titulo' => 'Historial completo de mis solicitudes', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula]],
            'pendientes' => ['titulo' => 'Mis solicitudes pendientes', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estado' => ESTADO_PENDIENTE_JEFE]],
            'aprobadas' => ['titulo' => 'Mis solicitudes aprobadas', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estados' => [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH]]],
            'rechazadas' => ['titulo' => 'Mis solicitudes rechazadas', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estados' => [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH]]],
            'canceladas' => ['titulo' => 'Mis solicitudes canceladas', 'filtros' => $filtrosBase + ['nit_empleado' => $cedula, 'estado' => ESTADO_CANCELADO_AGENDA], 'orden' => 'FECHA_MODIFICACION DESC'],
        ];

        if (!array_key_exists($historialTipo, $historialOpciones)) {
            $historialTipo = 'total';
        }

        $historialTitulo = $historialOpciones[$historialTipo]['titulo'];
        $page = $model->getAllPaginado(
            $historialOpciones[$historialTipo]['filtros'],
            max(1, (int)($_GET['pag_mis'] ?? 1)),
            APP_ROWS_PER_PAGE,
            $historialOpciones[$historialTipo]['orden'] ?? 'FECHA_CREACION DESC'
        );
        $historialSolicitudes = $this->enriquecerSolicitudes($page['rows'], $empleadoModel);
        $historialPaginationData = $page['pagination'];
        $solicitudes = $historialSolicitudes;
        $stats = $this->estadisticasEmpleado($model, $filtrosBase, $cedula);
        render_view('empleado/dashboard', compact('user', 'solicitudes', 'stats', 'historialTipo', 'historialTitulo', 'historialSolicitudes', 'historialPaginationData', 'q', 'tipoFiltro'));
    }

    private function estadisticasJefe(
        solicitudModel $model,
        array $filtrosBase,
        string $cedula,
        array $gestionadasEstados,
        array $aprobadasEstados
    ): array {
        $conteosJefe = $model->contarPorEstado($filtrosBase + [
            'nit_jefe' => $cedula,
            'excluir_empleado' => $cedula,
        ]);
        $conteosPropios = $model->contarPorEstado($filtrosBase + ['nit_empleado' => $cedula]);

        return [
            'pendientes' => (int)($conteosJefe[ESTADO_PENDIENTE_JEFE] ?? 0),
            'aprobadas' => $this->sumarConteos($conteosJefe, $aprobadasEstados),
            'rechazadas' => (int)($conteosJefe[ESTADO_RECHAZADO_JEFE] ?? 0),
            'canceladas' => (int)($conteosJefe[ESTADO_CANCELADO_AGENDA] ?? 0),
            'misSolicitudes' => $this->sumarConteos($conteosPropios),
            'gestionadas' => $this->sumarConteos($conteosJefe, $gestionadasEstados),
        ];
    }

    private function estadisticasEmpleado(solicitudModel $model, array $filtrosBase, string $cedula): array
    {
        $conteos = $model->contarPorEstado($filtrosBase + ['nit_empleado' => $cedula]);

        return [
            'total' => $this->sumarConteos($conteos),
            'pendientes' => (int)($conteos[ESTADO_PENDIENTE_JEFE] ?? 0),
            'aprobadas' => $this->sumarConteos($conteos, [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH]),
            'rechazadas' => $this->sumarConteos($conteos, [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH]),
            'canceladas' => (int)($conteos[ESTADO_CANCELADO_AGENDA] ?? 0),
        ];
    }

    private function sumarConteos(array $conteos, ?array $estados = null): int
    {
        if ($estados === null) {
            return array_sum(array_map('intval', $conteos));
        }

        $total = 0;

        foreach ($estados as $estado) {
            $total += (int)($conteos[$estado] ?? 0);
        }

        return $total;
    }

    private function filtrosBase(string $q, string $tipoFiltro): array
    {
        $filtros = [];

        if ($tipoFiltro !== '') {
            $filtros['tipo'] = $tipoFiltro;
        }

        if ($q !== '') {
            $filtros['q'] = $q;
        }

        return $filtros;
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
}
