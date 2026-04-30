<?php
$historialTipo = $historialTipo ?? 'total';
$historialTitulo = $historialTitulo ?? 'Mis solicitudes recientes';
$historialSolicitudes = $historialSolicitudes ?? ($solicitudes ?? []);
$q = $q ?? '';
$tipoFiltro = $tipoFiltro ?? '';

$historialUrl = static fn(string $tipo): string => url_view('dashboard') . '&' . http_build_query(array_filter([
  'historial' => $tipo,
  'q' => $q,
  'tipo_solicitud' => $tipoFiltro,
], static fn($value): bool => $value !== '' && $value !== null));
$historialCardClass = static fn(string $tipo): string => 'stat-card stat-card-link' . ($historialTipo === $tipo ? ' is-active' : '');
?>
<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Hola, <?= e($user['nombre'] ?? 'Empleado') ?></h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Resumen de tus solicitudes registradas.</p></div>
  <a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e($historialUrl('total')) ?>" class="<?= e($historialCardClass('total')) ?>"><div class="stat-icon">&#9633;</div><div class="num"><?= e($stats['total'] ?? 0) ?></div><div class="lbl">Total</div></a>
  <a href="<?= e($historialUrl('pendientes')) ?>" class="<?= e($historialCardClass('pendientes')) ?>"><div class="stat-icon">&#9201;</div><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes</div></a>
  <a href="<?= e($historialUrl('aprobadas')) ?>" class="<?= e($historialCardClass('aprobadas')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas</div></a>
  <a href="<?= e($historialUrl('rechazadas')) ?>" class="<?= e($historialCardClass('rechazadas')) ?>"><div class="stat-icon">&times;</div><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas</div></a>
</div>
<div id="historial-empleado" class="section-header"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationParam = 'pag_mis'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
