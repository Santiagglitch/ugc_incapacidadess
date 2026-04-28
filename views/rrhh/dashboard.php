<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Talento Humano</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Gestion de aprobaciones finales</p></div>
  <a href="<?= e(url_view('solicitudes') . '&tipo=historico') ?>" class="btn btn-green">Ver historial completo</a>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e(url_view('solicitudes') . '&tipo=pendientes') ?>" class="stat-card stat-card-link"><div class="stat-icon">✓</div><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes RRHH</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=aprobadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">✓</div><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas RRHH</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=rechazadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">×</div><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas RRHH</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=historico') ?>" class="stat-card stat-card-link"><div class="stat-icon">□</div><div class="num"><?= e($stats['historico'] ?? 0) ?></div><div class="lbl">Total Historico</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=revision_jefe') ?>" class="stat-card stat-card-link"><div class="stat-icon">⏱</div><div class="num"><?= e($stats['revisionJefe'] ?? 0) ?></div><div class="lbl">En Revision Jefe</div></a>
</div>
<div class="section-header"><h2>Aprobadas por jefe pendientes de RRHH</h2></div>
<?php $solicitudes = $pendientes ?? []; $paginationParam = 'pag_pendientes'; $paginationLabel = 'pendientes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
<div class="section-header section-header--spaced"><h2>Historial completo</h2></div>
<?php $solicitudes = $todas ?? []; $paginationParam = 'pag_historial'; $paginationLabel = 'historial'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>