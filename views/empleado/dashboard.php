<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Hola, <?= e($user['nombre'] ?? 'Empleado') ?></h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Resumen de tus solicitudes registradas.</p></div>
  <a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e(url_view('solicitudes') . '&tipo=total') ?>" class="stat-card stat-card-link"><div class="stat-icon">□</div><div class="num"><?= e($stats['total'] ?? 0) ?></div><div class="lbl">Total</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=pendientes') ?>" class="stat-card stat-card-link"><div class="stat-icon">⏱</div><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=aprobadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">✓</div><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas</div></a>
  <a href="<?= e(url_view('solicitudes') . '&tipo=rechazadas') ?>" class="stat-card stat-card-link"><div class="stat-icon">×</div><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas</div></a>
</div>
<div class="section-header"><h2>Mis solicitudes recientes</h2></div>
<?php $solicitudes = $solicitudes ?? []; $paginationParam = 'pag_mis'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>