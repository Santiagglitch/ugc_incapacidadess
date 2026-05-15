<?php
$historialTipo = $historialTipo ?? 'total';
$historialTitulo = $historialTitulo ?? 'Historial completo de mis solicitudes';
$historialSolicitudes = $historialSolicitudes ?? ($solicitudes ?? []);
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
  <div><h1 class="page-title">Hola, <?= e($user['nombre'] ?? 'Empleado') ?></h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Resumen de tus solicitudes registradas.</p></div>
  <a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a>
</section>
<?php
$dashboardKpiCards = [
  ['tipo'=>'total',      'color'=>'#6366f1', 'bg'=>'#eef2ff', 'lbl'=>'Historial completo', 'val'=>$stats['total'] ?? 0,
   'svg'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>'],
  ['tipo'=>'pendientes', 'color'=>'#f59e0b', 'bg'=>'#fffbeb', 'lbl'=>'Pendientes',          'val'=>$stats['pendientes'] ?? 0,
   'svg'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
  ['tipo'=>'aprobadas',  'color'=>'#10b981', 'bg'=>'#ecfdf5', 'lbl'=>'Aprobadas',           'val'=>$stats['aprobadas'] ?? 0,
   'svg'=>'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'],
  ['tipo'=>'rechazadas', 'color'=>'#ef4444', 'bg'=>'#fef2f2', 'lbl'=>'Rechazadas',          'val'=>$stats['rechazadas'] ?? 0,
   'svg'=>'<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
  ['tipo'=>'canceladas', 'color'=>'#64748b', 'bg'=>'#f1f5f9', 'lbl'=>'Cancelado por mi',    'val'=>$stats['canceladas'] ?? 0,
   'svg'=>'<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'],
];
$dashboardKpiActive = $historialTipo;
$dashboardKpiUrl = $historialUrl;
$dashboardKpiRowClass = '';
require dirname(__DIR__) . '/shared/dashboard_kpi_cards.php';
?>
<div id="historial-empleado" class="section-header"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationData = $historialPaginationData ?? null; $paginationParam = 'pag_mis'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
