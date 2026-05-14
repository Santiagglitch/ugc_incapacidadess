<?php
$historialTipo = $historialTipo ?? 'gestionadas';
$historialTitulo = $historialTitulo ?? 'Historial gestionado';
$historialSolicitudes = $historialSolicitudes ?? ($gestionadas ?? []);
$q = $q ?? '';
$tipoFiltro = $tipoFiltro ?? '';

$historialUrl = static function (string $tipo) use ($q, $tipoFiltro): string {
  $params = array_filter([
    'historial' => $tipo,
    'q' => $q,
    'tipo_solicitud' => $tipoFiltro,
  ], static fn($value): bool => $value !== '' && $value !== null);

  return url_view('dashboard') . ($params ? '&' . http_build_query($params) : '');
};
?>
<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Panel de Jefe Inmediato</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Gestiona las solicitudes de tu equipo</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
  <a href="<?= e(url_view('solicitud_crear')) ?>" class="btn btn-green">
    + Nueva solicitud
  </a>

  <a href="Export/Jefe/ExportController.php?download=1" class="btn btn-green">
    + Exportar datos
  </a>
</div>
</section>
<?php
$dashboardKpiCards = [
  ['tipo'=>'gestionadas',     'color'=>'#6366f1', 'bg'=>'#eef2ff', 'lbl'=>'Historial Gestionado',      'val'=>$stats['gestionadas'] ?? 0,
   'svg'=>'<path d="M3 12a9 9 0 0 1 15.2-6.4"/><polyline points="18 2 18 6 14 6"/><path d="M21 12a9 9 0 0 1-15.2 6.4"/><polyline points="6 22 6 18 10 18"/>'],
  ['tipo'=>'pendientes',      'color'=>'#f59e0b', 'bg'=>'#fffbeb', 'lbl'=>'Pendientes de Aprobacion', 'val'=>$stats['pendientes'] ?? 0,
   'svg'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
  ['tipo'=>'aprobadas',       'color'=>'#10b981', 'bg'=>'#ecfdf5', 'lbl'=>'Aprobadas por ti',         'val'=>$stats['aprobadas'] ?? 0,
   'svg'=>'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'],
  ['tipo'=>'rechazadas',      'color'=>'#ef4444', 'bg'=>'#fef2f2', 'lbl'=>'Rechazadas por ti',        'val'=>$stats['rechazadas'] ?? 0,
   'svg'=>'<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
  ['tipo'=>'canceladas',      'color'=>'#64748b', 'bg'=>'#f1f5f9', 'lbl'=>'Cancelado por el usuario', 'val'=>$stats['canceladas'] ?? 0,
   'svg'=>'<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'],
  ['tipo'=>'mis_solicitudes', 'color'=>'#7c3aed', 'bg'=>'#f5f3ff', 'lbl'=>'Mis Solicitudes',          'val'=>$stats['misSolicitudes'] ?? 0,
   'svg'=>'<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8"/><path d="M8 13h6"/><path d="M8 17h4"/>'],
];
$dashboardKpiActive = $historialTipo;
$dashboardKpiUrl = $historialUrl;
$dashboardKpiRowClass = '';
require dirname(__DIR__) . '/shared/dashboard_kpi_cards.php';
?>
<div id="pendientes-jefe" class="section-header"><h2>Solicitudes pendientes de tu aprobacion</h2></div>
<?php $solicitudes = $pendientes ?? []; $paginationData = $pendientesPaginationData ?? null; $paginationParam = 'pag_pendientes'; $paginationLabel = 'pendientes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div id="historial-gestionado" class="section-header section-header--spaced"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationData = $historialPaginationData ?? null; $paginationParam = 'pag_historial'; $paginationLabel = 'historial'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div id="mis-solicitudes-jefe" class="section-header section-header--spaced"><h2>Mis solicitudes personales</h2></div>
<?php $solicitudes = $misSolicitudes ?? []; $paginationData = $misPaginationData ?? null; $paginationParam = 'pag_mis'; $paginationLabel = 'mis solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
