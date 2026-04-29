<?php
$historialTipo = $historialTipo ?? 'historico';
$historialTitulo = $historialTitulo ?? 'Historial completo';
$historialSolicitudes = $historialSolicitudes ?? ($todas ?? []);

$historialUrl = static fn(string $tipo): string => url_view('dashboard') . '&historial=' . urlencode($tipo) . '#historial-rrhh';
$historialCardClass = static fn(string $tipo): string => 'stat-card stat-card-link' . ($historialTipo === $tipo ? ' is-active' : '');
?>
<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Talento Humano</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Gestion de aprobaciones finales</p></div>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e($historialUrl('pendientes')) ?>" class="<?= e($historialCardClass('pendientes')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes RRHH</div></a>
  <a href="<?= e($historialUrl('aprobadas')) ?>" class="<?= e($historialCardClass('aprobadas')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas RRHH</div></a>
  <a href="<?= e($historialUrl('rechazadas')) ?>" class="<?= e($historialCardClass('rechazadas')) ?>"><div class="stat-icon">&times;</div><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas RRHH</div></a>
  <a href="<?= e($historialUrl('historico')) ?>" class="<?= e($historialCardClass('historico')) ?>"><div class="stat-icon">&#9633;</div><div class="num"><?= e($stats['historico'] ?? 0) ?></div><div class="lbl">Total Historico</div></a>
  <a href="<?= e($historialUrl('revision_jefe')) ?>" class="<?= e($historialCardClass('revision_jefe')) ?>"><div class="stat-icon">&#9201;</div><div class="num"><?= e($stats['revisionJefe'] ?? 0) ?></div><div class="lbl">En Revision Jefe</div></a>
</div>
<div class="section-header"><h2>Aprobadas por jefe pendientes de RRHH</h2></div>
<?php $solicitudes = $pendientes ?? []; $paginationParam = 'pag_pendientes'; $paginationLabel = 'pendientes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div id="historial-rrhh" class="section-header section-header--spaced"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationParam = 'pag_historial'; $paginationLabel = 'historial'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
