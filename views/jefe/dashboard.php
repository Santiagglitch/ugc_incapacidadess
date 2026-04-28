<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Panel de Jefe Inmediato</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Gestiona las solicitudes de tu equipo</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><a href="<?= e(url_view('solicitud_crear')) ?>" class="btn btn-green">+ Nueva solicitud</a><a href="<?= e(url_view('solicitudes') . '&tipo=gestionadas') ?>" class="btn btn-outline">Historial</a></div>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e(url_view('solicitudes') . '&tipo=pendientes') ?>" class="stat-card stat-card-link"><div class="stat-icon">⏱</div><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes de Aprobacion</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=aprobadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">✓</div><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas por ti</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=rechazadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">×</div><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas por ti</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=mis_solicitudes') ?>" class="stat-card stat-card-link"><div class="stat-icon">□</div><div class="num"><?= e($stats['misSolicitudes'] ?? 0) ?></div><div class="lbl">Mis Solicitudes</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=gestionadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">↻</div><div class="num"><?= e($stats['gestionadas'] ?? 0) ?></div><div class="lbl">Historial Gestionado</div></a>
</div>
<div class="section-header"><h2>Solicitudes pendientes de tu aprobacion</h2></div>
<?php $solicitudes = $pendientes ?? []; $paginationParam = 'pag_pendientes'; $paginationLabel = 'pendientes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div class="section-header section-header--spaced"><h2>Historial gestionado</h2></div>
<?php $solicitudes = $gestionadas ?? []; $paginationParam = 'pag_gestionadas'; $paginationLabel = 'gestionadas'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div class="section-header section-header--spaced"><h2>Mis solicitudes personales</h2></div>
<?php $solicitudes = $misSolicitudes ?? []; $paginationParam = 'pag_mis'; $paginationLabel = 'mis solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>