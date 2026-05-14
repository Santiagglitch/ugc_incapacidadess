<?php
$historialTipo  = $historialTipo  ?? 'total';
$historialTitulo = $historialTitulo ?? 'Historial completo';
$historialSolicitudes = $historialSolicitudes ?? ($todas ?? []);
$q          = $q ?? '';
$tipoFiltro = $tipoFiltro ?? '';
$fechaDesde = $fechaDesde ?? '';
$fechaHasta = $fechaHasta ?? '';
$kpiAdmin   = $kpiAdmin ?? [];

$historialUrl = static function (string $tipo) use ($q, $tipoFiltro, $fechaDesde, $fechaHasta): string {
  $params = array_filter([
    'historial'      => $tipo,
    'q'              => $q,
    'tipo_solicitud' => $tipoFiltro,
    'fecha_desde'    => $fechaDesde,
    'fecha_hasta'    => $fechaHasta,
  ], static fn($value): bool => $value !== '' && $value !== null);
  return url_view('dashboard') . ($params ? '&' . http_build_query($params) : '');
};
$tieneFiltroDates = ($fechaDesde !== '' || $fechaHasta !== '');
$exportUrl = 'Export/Admin/ExportController.php?download=1'
  . ($fechaDesde !== '' ? '&fecha_desde=' . urlencode($fechaDesde) : '')
  . ($fechaHasta !== '' ? '&fecha_hasta=' . urlencode($fechaHasta) : '');
?>
<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div><h1 class="page-title">Panel de Administrador</h1><p style="color:var(--muted);font-size:14px;margin-top:4px">Vista general del sistema de solicitudes</p></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php $esSuperAdminModoEmpleado = (($user['cedula'] ?? '') === SUPER_ADMIN_NIT) && (($_SESSION['super_admin_modo'] ?? 'super_usuario') === 'empleado'); ?>
    <?php if ($esSuperAdminModoEmpleado): ?>
      <a class="btn btn-green" href="<?= e(url_view('solicitud_crear')) ?>">+ Nueva solicitud</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">Gestionar empleados</a>
    <?php if ((($user['cedula'] ?? '') === SUPER_ADMIN_NIT) && (($_SESSION['super_admin_modo'] ?? 'super_usuario') === 'super_usuario')): ?>
      <a class="btn btn-green" href="<?= e(url_view('superadmin_panel')) ?>">&#9733; Panel Super Admin</a>
    <?php endif; ?>
    <a class="btn btn-green" href="<?= e($exportUrl) ?>">&#8659; Exportar datos</a>
  </div>
</section>

<?php
  $hoy     = date('Y-m-d');
  $iniMes  = date('Y-m-01');
  $iniAnio = date('Y-01-01');
  $ini30   = date('Y-m-d', strtotime('-30 days'));
  $ini7    = date('Y-m-d', strtotime('-7 days'));
  $baseUrl = url_view('dashboard')
    . ($q !== '' ? '&q='.urlencode($q) : '')
    . ($tipoFiltro !== '' ? '&tipo_solicitud='.urlencode($tipoFiltro) : '');
  $accesos = [
    'Hoy'             => [$hoy,    $hoy],
    '7 días'          => [$ini7,   $hoy],
    '30 días'         => [$ini30,  $hoy],
    'Este mes'        => [$iniMes, $hoy],
    'Este año'        => [$iniAnio,$hoy],
  ];
?>

<!-- ── Barra de filtro compacta ── -->
<div class="animate-fade-down" style="background:var(--surface);border:1px solid <?= $tieneFiltroDates ? 'var(--green)' : 'var(--border)' ?>;border-radius:var(--r2);padding:12px 16px;margin-bottom:20px;<?= $tieneFiltroDates ? 'box-shadow:0 0 0 3px var(--green3)' : '' ?>">

  <!-- Fila superior: inputs + botones -->
  <form method="get" action="<?= e(app_base_url('index.php')) ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="view" value="dashboard">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <?php if ($tipoFiltro !== ''): ?><input type="hidden" name="tipo_solicitud" value="<?= e($tipoFiltro) ?>"><?php endif; ?>

    <!-- Ícono calendario -->
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--green);flex-shrink:0;margin-right:2px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>

    <!-- Input Desde -->
    <div style="display:flex;align-items:center;gap:6px;background:var(--bg,#f8f9fa);border:1.5px solid <?= $fechaDesde !== '' ? 'var(--green)' : 'var(--border)' ?>;border-radius:var(--r2);padding:5px 10px">
      <span style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">Desde</span>
      <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>" style="border:none;background:transparent;font-size:13px;color:var(--text);outline:none;min-width:130px">
    </div>

    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--muted);flex-shrink:0"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>

    <!-- Input Hasta -->
    <div style="display:flex;align-items:center;gap:6px;background:var(--bg,#f8f9fa);border:1.5px solid <?= $fechaHasta !== '' ? 'var(--green)' : 'var(--border)' ?>;border-radius:var(--r2);padding:5px 10px">
      <span style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">Hasta</span>
      <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>" style="border:none;background:transparent;font-size:13px;color:var(--text);outline:none;min-width:130px">
    </div>

    <button class="btn btn-outline btn-sm" type="submit" style="font-weight:600;white-space:nowrap">Aplicar</button>

    <?php if ($tieneFiltroDates): ?>
      <a href="<?= e($baseUrl) ?>" style="display:flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:var(--muted);text-decoration:none;padding:5px 8px;border-radius:var(--r2);border:1px solid var(--border);background:var(--surface)" title="Quitar filtro de fechas">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Limpiar
      </a>
      <span style="font-size:11px;color:var(--green);font-weight:700;background:var(--green3);border:1px solid var(--green4,#b7e3c7);border-radius:99px;padding:2px 10px;white-space:nowrap">
        <?= e($fechaDesde ?: '…') ?> &rarr; <?= e($fechaHasta ?: '…') ?>
      </span>
    <?php endif; ?>
  </form>

  <!-- Fila inferior: accesos rápidos -->
  <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:9px;padding-top:9px;border-top:1px solid var(--border)">
    <span style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">Período:</span>
    <?php foreach ($accesos as $label => [$fd, $fh]):
      $activo = ($fechaDesde === $fd && $fechaHasta === $fh);
      $href   = $baseUrl . '&fecha_desde=' . $fd . '&fecha_hasta=' . $fh;
    ?>
      <a href="<?= e($href) ?>" style="font-size:11px;font-weight:600;padding:3px 11px;border-radius:99px;border:1.5px solid <?= $activo ? 'var(--green)' : 'var(--border)' ?>;background:<?= $activo ? 'var(--green3)' : 'transparent' ?>;color:<?= $activo ? 'var(--green)' : 'var(--muted)' ?>;text-decoration:none;white-space:nowrap">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── KPIs principales ── -->
<?php
$dashboardKpiCards = [
  ['tipo'=>'total',          'color'=>'#6366f1', 'bg'=>'#eef2ff', 'lbl'=>'Total',              'val'=>$stats['TOTAL'] ?? 0,
   'svg'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>'],
  ['tipo'=>'pendiente_jefe', 'color'=>'#f59e0b', 'bg'=>'#fffbeb', 'lbl'=>'Pendientes jefe',    'val'=>$stats[ESTADO_PENDIENTE_JEFE] ?? 0,
   'svg'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
  ['tipo'=>'aprobado_jefe',  'color'=>'#3b82f6', 'bg'=>'#eff6ff', 'lbl'=>'Pendientes RRHH',   'val'=>$stats[ESTADO_APROBADO_JEFE] ?? 0,
   'svg'=>'<polyline points="20 6 9 17 4 12"/>'],
  ['tipo'=>'aprobado_rrhh',  'color'=>'#10b981', 'bg'=>'#ecfdf5', 'lbl'=>'Aprobadas RRHH',    'val'=>$stats[ESTADO_APROBADO_RRHH] ?? 0,
   'svg'=>'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'],
  ['tipo'=>'rechazado_rrhh', 'color'=>'#ef4444', 'bg'=>'#fef2f2', 'lbl'=>'Rechazadas RRHH',   'val'=>$stats[ESTADO_RECHAZADO_RRHH] ?? 0,
   'svg'=>'<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
  ['tipo'=>'canceladas',     'color'=>'#64748b', 'bg'=>'#f1f5f9', 'lbl'=>'Canceladas',         'val'=>$stats[ESTADO_CANCELADO_AGENDA] ?? 0,
   'svg'=>'<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'],
];
$dashboardKpiActive = $historialTipo;
$dashboardKpiUrl = $historialUrl;
$dashboardKpiRowClass = 'dashboard-kpi-row--compact';
require dirname(__DIR__) . '/shared/dashboard_kpi_cards.php';
?>

<!-- ── KPIs adicionales (insights) ── -->
<?php if (!empty($kpiAdmin) && ($kpiAdmin['tipo_top'] !== '' || $kpiAdmin['promedio_horas'] !== null || $kpiAdmin['dia_top'] !== '')): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:12px" class="animate-fade-up">
  <?php if ($kpiAdmin['tipo_top'] !== ''): ?>
  <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r2)">
    <div style="width:36px;height:36px;border-radius:8px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.2" stroke-linecap="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
    <div style="min-width:0">
      <div style="font-size:12px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e(TIPOS_SOLICITUD[$kpiAdmin['tipo_top']] ?? $kpiAdmin['tipo_top']) ?></div>
      <div style="font-size:11px;color:var(--muted)">Tipo m&aacute;s solicitado &middot; <?= e($kpiAdmin['tipo_top_cnt']) ?> veces</div>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($kpiAdmin['promedio_horas'] !== null): ?>
  <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r2)">
    <div style="width:36px;height:36px;border-radius:8px;background:#fff7ed;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div>
      <div style="font-size:18px;font-weight:800;color:var(--text);line-height:1"><?= e((string)$kpiAdmin['promedio_horas']) ?><span style="font-size:12px;font-weight:600;color:var(--muted)"> h</span></div>
      <div style="font-size:11px;color:var(--muted)">Promedio de duraci&oacute;n</div>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($kpiAdmin['dia_top'] !== ''): ?>
  <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r2)">
    <div style="width:36px;height:36px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </div>
    <div>
      <div style="font-size:15px;font-weight:800;color:var(--text);text-transform:capitalize;line-height:1"><?= e(ucfirst(mb_strtolower($kpiAdmin['dia_top']))) ?></div>
      <div style="font-size:11px;color:var(--muted)">D&iacute;a m&aacute;s activo &middot; <?= e($kpiAdmin['dia_top_cnt']) ?> solicitudes</div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Encabezado historial ── -->
<div id="historial-admin" style="display:flex;align-items:center;justify-content:space-between;margin:24px 0 12px">
  <h2 style="font-size:15px;font-weight:700;color:var(--text);margin:0"><?= e($historialTitulo) ?></h2>
  <?php if ($tieneFiltroDates): ?>
    <span style="font-size:11px;color:var(--muted)">Filtrado: <strong style="color:var(--green)"><?= e($fechaDesde) ?> &rarr; <?= e($fechaHasta) ?></strong></span>
  <?php endif; ?>
</div>
<?php $solicitudes = $historialSolicitudes ?? []; $paginationData = $historialPaginationData ?? null; $paginationParam = 'pag_admin'; $paginationLabel = 'solicitudes'; require dirname(__DIR__) . '/shared/tabla_solicitudes.php'; ?>
