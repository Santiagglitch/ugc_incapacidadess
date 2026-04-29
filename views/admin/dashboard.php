<?php
$historialTipo = $historialTipo ?? 'total';
$historialTitulo = $historialTitulo ?? 'Solicitudes recientes';
$historialSolicitudes = $historialSolicitudes ?? ($todas ?? []);

$historialUrl = static fn(string $tipo): string => url_view('dashboard') . '&historial=' . urlencode($tipo) . '#historial-admin';
$historialCardClass = static fn(string $tipo): string => 'stat-card stat-card-link' . ($historialTipo === $tipo ? ' is-active' : '');
?>
<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Panel de Administrador</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Vista general del sistema de solicitudes</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a><a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">Gestionar empleados</a></div>
</section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e($historialUrl('total')) ?>" class="<?= e($historialCardClass('total')) ?>"><div class="stat-icon">&#9633;</div><div class="num"><?= e($stats['TOTAL'] ?? 0) ?></div><div class="lbl">Total solicitudes</div></a>
  <a href="<?= e($historialUrl('pendiente_jefe')) ?>" class="<?= e($historialCardClass('pendiente_jefe')) ?>"><div class="stat-icon">&#9201;</div><div class="num"><?= e($stats[ESTADO_PENDIENTE_JEFE] ?? 0) ?></div><div class="lbl">Pendientes jefe</div></a>
  <a href="<?= e($historialUrl('aprobado_jefe')) ?>" class="<?= e($historialCardClass('aprobado_jefe')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats[ESTADO_APROBADO_JEFE] ?? 0) ?></div><div class="lbl">Pendientes RRHH</div></a>
  <a href="<?= e($historialUrl('aprobado_rrhh')) ?>" class="<?= e($historialCardClass('aprobado_rrhh')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats[ESTADO_APROBADO_RRHH] ?? 0) ?></div><div class="lbl">Aprobadas RRHH</div></a>
  <a href="<?= e($historialUrl('rechazado_rrhh')) ?>" class="<?= e($historialCardClass('rechazado_rrhh')) ?>"><div class="stat-icon">&times;</div><div class="num"><?= e($stats[ESTADO_RECHAZADO_RRHH] ?? 0) ?></div><div class="lbl">Rechazadas RRHH</div></a>
</div>
<div id="historial-admin" class="section-header"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationParam = 'pag_admin'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
