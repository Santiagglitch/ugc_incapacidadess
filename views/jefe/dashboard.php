<?php
$historialTipo = $historialTipo ?? 'gestionadas';
$historialTitulo = $historialTitulo ?? 'Historial gestionado';
$historialSolicitudes = $historialSolicitudes ?? ($gestionadas ?? []);
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
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e($historialUrl('pendientes')) ?>" class="<?= e($historialCardClass('pendientes')) ?>"><div class="stat-icon">&#9201;</div><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes de Aprobacion</div></a>
  <a href="<?= e($historialUrl('aprobadas')) ?>" class="<?= e($historialCardClass('aprobadas')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas por ti</div></a>
  <a href="<?= e($historialUrl('rechazadas')) ?>" class="<?= e($historialCardClass('rechazadas')) ?>"><div class="stat-icon">&times;</div><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas por ti</div></a>
  <a href="<?= e($historialUrl('mis_solicitudes')) ?>" class="<?= e($historialCardClass('mis_solicitudes')) ?>"><div class="stat-icon">&#9633;</div><div class="num"><?= e($stats['misSolicitudes'] ?? 0) ?></div><div class="lbl">Mis Solicitudes</div></a>
  <a href="<?= e($historialUrl('gestionadas')) ?>" class="<?= e($historialCardClass('gestionadas')) ?>"><div class="stat-icon">&#8635;</div><div class="num"><?= e($stats['gestionadas'] ?? 0) ?></div><div class="lbl">Historial Gestionado</div></a>
</div>
<div class="section-header"><h2>Solicitudes pendientes de tu aprobacion</h2></div>
<?php $solicitudes = $pendientes ?? []; $paginationParam = 'pag_pendientes'; $paginationLabel = 'pendientes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div id="historial-gestionado" class="section-header section-header--spaced"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationParam = 'pag_historial'; $paginationLabel = 'historial'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div class="section-header section-header--spaced"><h2>Mis solicitudes personales</h2></div>
<?php $solicitudes = $misSolicitudes ?? []; $paginationParam = 'pag_mis'; $paginationLabel = 'mis solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
