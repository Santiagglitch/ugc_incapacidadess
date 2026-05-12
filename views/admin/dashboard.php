<?php
$historialTipo = $historialTipo ?? 'total';
$historialTitulo = $historialTitulo ?? 'Historial completo';
$historialSolicitudes = $historialSolicitudes ?? ($todas ?? []);
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
$historialCardClass = static fn(string $tipo): string => 'stat-card stat-card-link' . ($historialTipo === $tipo ? ' is-active' : '');
?>
<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Panel de Administrador</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Vista general del sistema de solicitudes</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php
    // Super Admin en modo empleado puede crear solicitudes
    $esSuperAdminModoEmpleado = (($user['cedula'] ?? '') === SUPER_ADMIN_NIT) && (($_SESSION['super_admin_modo'] ?? 'super_usuario') === 'empleado');
    if ($esSuperAdminModoEmpleado):
    ?>
      <a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">Gestionar empleados</a>
 <a class="btn btn-green" href="Export/Admin/ExportController.php?download=1">
  + Exportar datos
</a>

  </div>
 </section>
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <a href="<?= e($historialUrl('total')) ?>" class="<?= e($historialCardClass('total')) ?>"><div class="stat-icon">&#9633;</div><div class="num"><?= e($stats['TOTAL'] ?? 0) ?></div><div class="lbl">Historial completo</div></a>
  <a href="<?= e($historialUrl('pendiente_jefe')) ?>" class="<?= e($historialCardClass('pendiente_jefe')) ?>"><div class="stat-icon">&#9201;</div><div class="num"><?= e($stats[ESTADO_PENDIENTE_JEFE] ?? 0) ?></div><div class="lbl">Pendientes jefe</div></a>
  <a href="<?= e($historialUrl('aprobado_jefe')) ?>" class="<?= e($historialCardClass('aprobado_jefe')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats[ESTADO_APROBADO_JEFE] ?? 0) ?></div><div class="lbl">Pendientes RRHH</div></a>
  <a href="<?= e($historialUrl('aprobado_rrhh')) ?>" class="<?= e($historialCardClass('aprobado_rrhh')) ?>"><div class="stat-icon">&#10003;</div><div class="num"><?= e($stats[ESTADO_APROBADO_RRHH] ?? 0) ?></div><div class="lbl">Aprobadas RRHH</div></a>
  <a href="<?= e($historialUrl('rechazado_rrhh')) ?>" class="<?= e($historialCardClass('rechazado_rrhh')) ?>"><div class="stat-icon">&times;</div><div class="num"><?= e($stats[ESTADO_RECHAZADO_RRHH] ?? 0) ?></div><div class="lbl">Rechazadas RRHH</div></a>
  <a href="<?= e($historialUrl('canceladas')) ?>" class="<?= e($historialCardClass('canceladas')) ?>"><div class="stat-icon">&#8856;</div><div class="num"><?= e($stats[ESTADO_CANCELADO_AGENDA] ?? 0) ?></div><div class="lbl">Cancelado por el usuario</div></a>
</div>
<div id="historial-admin" class="section-header"><h2><?= e($historialTitulo) ?></h2></div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationData = $historialPaginationData ?? null; $paginationParam = 'pag_admin'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
