<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Panel de Administrador</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Vista general del sistema de solicitudes</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a><a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">Gestionar empleados</a></div>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e(url_view('solicitudes')) ?>" class="stat-card stat-card-link"><div class="stat-icon">□</div><div class="num"><?= e($stats['TOTAL'] ?? 0) ?></div><div class="lbl">Total solicitudes</div></a>
  <a href="<?= e(url_view('solicitudes') . '&estado=' . ESTADO_PENDIENTE_JEFE) ?>" class="stat-card stat-card-link"><div class="stat-icon">⏱</div><div class="num"><?= e($stats[ESTADO_PENDIENTE_JEFE] ?? 0) ?></div><div class="lbl">Pendientes jefe</div></a>
  <a href="<?= e(url_view('solicitudes') . '&estado=' . ESTADO_APROBADO_JEFE) ?>" class="stat-card stat-card-link"><div class="stat-icon">✓</div><div class="num"><?= e($stats[ESTADO_APROBADO_JEFE] ?? 0) ?></div><div class="lbl">Pendientes RRHH</div></a>
  <a href="<?= e(url_view('solicitudes') . '&estado=' . ESTADO_APROBADO_RRHH) ?>" class="stat-card stat-card-link"><div class="stat-icon">✓</div><div class="num"><?= e($stats[ESTADO_APROBADO_RRHH] ?? 0) ?></div><div class="lbl">Aprobadas RRHH</div></a>
  <a href="<?= e(url_view('solicitudes') . '&estado=' . ESTADO_RECHAZADO_RRHH) ?>" class="stat-card stat-card-link"><div class="stat-icon">×</div><div class="num"><?= e($stats[ESTADO_RECHAZADO_RRHH] ?? 0) ?></div><div class="lbl">Rechazadas RRHH</div></a>
</div>
<div class="section-header"><h2>Solicitudes recientes</h2><a class="btn btn-outline" href="<?= e(url_view('solicitudes')) ?>">Ver todas</a></div>
<?php $solicitudes = $todas ?? []; $paginationParam = 'pag_admin'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>