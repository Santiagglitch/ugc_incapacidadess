<?php
$nit        = (string)($empleado['NIT']            ?? '');
$nombre     = (string)($empleado['NOMBRE_COMPLETO'] ?? 'Empleado');
$cc         = (string)($empleado['CENTRO_COSTO']   ?? '');
$nivel      = (string)($empleado['NIVEL']          ?? '');
$fechaIng   = (string)($empleado['FECHA_INGRESO']  ?? '');
$antiguedad = $antiguedad ?? '';
$esAprendiz = $esAprendiz ?? false;
$rolLabel   = $rolLabel   ?? 'empleado';
$jefeInmediato = $jefeInmediato ?? null;
$limiteAdmins    = $limiteAdmins    ?? 10;
$usadosAdmins    = $usadosAdmins    ?? 0;
$auditLog        = $auditLog        ?? [];
$esSuperAdmin    = $esSuperAdmin    ?? false;
$esAdminAdicional = $esAdminAdicional ?? false;
$puedeGestionarAdmin = $puedeGestionarAdmin ?? false;
$stats           = $stats ?? ['total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0];

$rolTextos = ['admin' => 'Administrador', 'rrhh' => 'Talento Humano', 'jefe' => 'Jefe', 'empleado' => 'Empleado'];
$rolText   = $rolTextos[$rolLabel] ?? $rolLabel;

$iniciales = implode('', array_map(
    fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'),
    array_slice(array_filter(explode(' ', $nombre)), 0, 2)
));
?>

<style>
.det-hero{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:28px 32px;margin-bottom:20px;display:flex;align-items:flex-start;gap:24px;box-shadow:var(--shadow)}
.det-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green2));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:26px;flex-shrink:0}
.det-hero-body{flex:1;min-width:0}
.det-hero-name{font-size:22px;font-weight:700;margin-bottom:4px}
.det-hero-sub{font-size:13px;color:var(--muted);margin-bottom:12px;font-family:monospace}
.det-hero-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:13px}
.det-meta-item{display:flex;align-items:center;gap:6px;color:var(--muted)}
.det-meta-item strong{color:var(--text)}
.det-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.badge-aprendiz{background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:700}
.det-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.det-tab{padding:10px 22px;font-size:13px;font-weight:600;cursor:pointer;color:var(--muted);background:none;border:none;border-bottom:3px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s}
.det-tab.active{color:var(--green);border-bottom-color:var(--green)}
.det-tab:hover:not(.active){color:var(--text)}
.det-panel{display:none}
.det-panel.active{display:block}
.det-info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:20px}
.det-info-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r2);padding:16px 18px}
.det-info-card .lbl{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
.det-info-card .val{font-size:14px;font-weight:600}
.det-jefe-card{background:var(--green3);border:1px solid var(--green4);border-radius:var(--r2);padding:14px 18px;display:flex;align-items:center;gap:14px}
.det-jefe-avatar{width:38px;height:38px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.det-jefe-name{font-weight:600;font-size:13px}
.det-jefe-meta{font-size:11px;color:var(--muted)}
.tab-badge{background:var(--red);color:#fff;border-radius:99px;font-size:10px;padding:1px 6px;margin-left:4px}
.admin-quota{display:flex;align-items:center;gap:12px;margin-bottom:18px;padding:14px 18px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r2)}
.admin-quota-bar{flex:1;height:8px;background:var(--border);border-radius:99px;overflow:hidden}
.admin-quota-fill{height:100%;background:var(--green);border-radius:99px;transition:width .4s}
.admin-quota-fill.full{background:var(--red)}
.admin-quota-label{font-size:12px;font-weight:600;white-space:nowrap}
.audit-mini{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}
.audit-mini th{text-align:left;padding:6px 10px;font-size:11px;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border)}
.audit-mini td{padding:7px 10px;border-bottom:1px solid var(--border2,var(--border));vertical-align:top}
.audit-mini tr:last-child td{border-bottom:none}
.audit-accion{display:inline-block;background:var(--green3);color:var(--green);border:1px solid var(--green4);border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700}
.audit-accion.red{background:#fff0f0;color:var(--red);border-color:#ffd0d0}
</style>

<section class="page-header">
  <div>
    <h1 class="page-title"><?= e($nombre) ?></h1>
    <p style="color:var(--muted)">Perfil del empleado</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <?php if ($esSuperAdmin): ?>
      <a class="btn btn-outline btn-sm" href="<?= e(url_view('superadmin_panel')) ?>">Panel SA</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">&#8592; Volver</a>
  </div>
</section>

<div class="det-hero animate-fade-down">
  <div class="det-avatar"><?= e($iniciales ?: '?') ?></div>
  <div class="det-hero-body">
    <div class="det-hero-name"><?= e($nombre) ?></div>
    <div class="det-hero-sub">NIT <?= e($nit) ?></div>
    <div class="det-hero-meta">
      <span class="det-meta-item"><strong><?= e($cc) ?></strong> <span>Centro de Costo</span></span>
      <span class="det-meta-item"><strong>Nivel <?= e($nivel) ?></strong></span>
      <?php if ($antiguedad !== ''): ?>
        <span class="det-meta-item"><strong><?= e($antiguedad) ?></strong> <span>de antigüedad</span></span>
      <?php endif; ?>
      <?php if ($fechaIng !== ''): ?>
        <span class="det-meta-item"><span>Ingreso:</span> <strong><?= e(substr($fechaIng, 0, 10)) ?></strong></span>
      <?php endif; ?>
    </div>
    <div class="det-badges">
      <span class="badge badge-role-<?= e($rolLabel) ?>"><?= e($rolText) ?></span>
      <?php if ($esAprendiz): ?>
        <span class="badge-aprendiz">Aprendiz</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="stats-row" style="margin-bottom:20px">
  <div class="stat-card"><div class="num"><?= e($stats['total']     ?? 0) ?></div><div class="lbl">Solicitudes</div></div>
  <div class="stat-card"><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes</div></div>
  <div class="stat-card"><div class="num"><?= e($stats['aprobadas']  ?? 0) ?></div><div class="lbl">Aprobadas</div></div>
  <div class="stat-card"><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas</div></div>
</div>

<div class="det-tabs">
  <button class="det-tab active" onclick="detTab(this,'tab-perfil')">Perfil</button>
  <button class="det-tab" onclick="detTab(this,'tab-solicitudes')">
    Solicitudes
    <?php if (($stats['pendientes'] ?? 0) > 0): ?>
      <span class="tab-badge"><?= e($stats['pendientes']) ?></span>
    <?php endif; ?>
  </button>
  <?php if (!empty($puedeGestionarAdmin)): ?>
    <button class="det-tab" onclick="detTab(this,'tab-admin')">Admin</button>
  <?php endif; ?>
</div>

<div id="tab-perfil" class="det-panel active">
  <div class="det-info-grid">
    <div class="det-info-card">
      <div class="lbl">NIT / Documento</div>
      <div class="val" style="font-family:monospace"><?= e($nit) ?></div>
    </div>
    <div class="det-info-card">
      <div class="lbl">Centro de Costo</div>
      <div class="val"><?= e($cc ?: '—') ?></div>
    </div>
    <div class="det-info-card">
      <div class="lbl">Nivel</div>
      <div class="val"><?= e($nivel ?: '—') ?></div>
    </div>
    <div class="det-info-card">
      <div class="lbl">Rol en el sistema</div>
      <div class="val"><span class="badge badge-role-<?= e($rolLabel) ?>"><?= e($rolText) ?></span></div>
    </div>
    <?php if ($fechaIng !== ''): ?>
    <div class="det-info-card">
      <div class="lbl">Fecha de Ingreso</div>
      <div class="val"><?= e(substr($fechaIng, 0, 10)) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($antiguedad !== ''): ?>
    <div class="det-info-card">
      <div class="lbl">Antigüedad</div>
      <div class="val"><?= e($antiguedad) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($esAprendiz): ?>
    <div class="det-info-card">
      <div class="lbl">Tipo</div>
      <div class="val"><span class="badge-aprendiz">Aprendiz</span></div>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($jefeInmediato && !empty($jefeInmediato['NIT_JEFE'])): ?>
    <div style="margin-bottom:8px;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Jefe Inmediato</div>
    <?php
      $jNombre = (string)($jefeInmediato['NOMBRE_JEFE'] ?? '');
      $jIni = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w,0,1,'UTF-8'),'UTF-8'), array_slice(array_filter(explode(' ',$jNombre)),0,2)));
      $jFuente = (string)($jefeInmediato['FUENTE_JEFE'] ?? '');
      $jFuenteLabel = match($jFuente) {
        'MISMO_CENTRO_COSTO' => 'Mismo Centro de Costo',
        'CABECERA_SEGMENTO'  => 'Cabecera de Segmento',
        default              => $jFuente,
      };
    ?>
    <div class="det-jefe-card">
      <div class="det-jefe-avatar"><?= e($jIni ?: '?') ?></div>
      <div>
        <div class="det-jefe-name"><?= e($jNombre) ?></div>
        <div class="det-jefe-meta">
          NIT <?= e($jefeInmediato['NIT_JEFE'] ?? '') ?>
          &nbsp;·&nbsp; Nivel <?= e($jefeInmediato['NIVEL_JEFE'] ?? '') ?>
          &nbsp;·&nbsp; <?= e($jFuenteLabel) ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <p style="font-size:13px;color:var(--muted);padding:12px 0">No se encontró jefe inmediato registrado.</p>
  <?php endif; ?>
</div>

<div id="tab-solicitudes" class="det-panel">
  <div class="section-header"><h2>Historial de solicitudes</h2></div>
  <?php require dirname(__DIR__, 2) . '/shared/tabla_solicitudes.php'; ?>
</div>

<?php if (!empty($puedeGestionarAdmin)): ?>
<div id="tab-admin" class="det-panel">
  <div class="form-card">
    <h2>Gestión de rol Administrador</h2>

    <?php
      $cupoLleno = $usadosAdmins >= $limiteAdmins;
      $pct = min(100, (int)round($usadosAdmins / max(1, $limiteAdmins) * 100));
    ?>
    <div class="admin-quota">
      <div class="admin-quota-bar">
        <div class="admin-quota-fill <?= $cupoLleno ? 'full' : '' ?>" style="width:<?= $pct ?>%"></div>
      </div>
      <span class="admin-quota-label <?= $cupoLleno ? 'color:var(--red)' : '' ?>">
        <?= e($usadosAdmins) ?> / <?= e($limiteAdmins) ?> admins adicionales
        <?php if ($cupoLleno): ?> &mdash; <span style="color:var(--red)">cupo lleno</span><?php endif; ?>
      </span>
    </div>

    <?php if (!empty($esAdminAdicional)): ?>
      <p style="margin-bottom:16px">Este empleado tiene acceso de <strong>administrador adicional</strong> activo.</p>
      <form method="post" action="<?= e(url_action('admin_quitar_admin')) ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="nit" value="<?= e($nit) ?>">
        <button class="btn btn-red" type="submit" onclick="return confirm('¿Quitar acceso de administrador a <?= e(addslashes($nombre)) ?>?')">
          Quitar administrador
        </button>
      </form>
    <?php else: ?>
      <p style="margin-bottom:16px">El Super Usuario puede asignar acceso de administrador a este empleado.</p>
      <form method="post" action="<?= e(url_action('admin_hacer_admin')) ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" name="nit" value="<?= e($nit) ?>">
        <button class="btn btn-green" type="submit" <?= $cupoLleno && !$esAdminAdicional ? 'disabled title="Cupo de administradores lleno"' : '' ?>>
          Hacer administrador
        </button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($auditLog)): ?>
  <div class="form-card" style="margin-top:16px">
    <h2 style="font-size:15px;margin-bottom:12px">Historial de cambios de rol</h2>
    <table class="audit-mini">
      <thead><tr><th>Fecha</th><th>Acción</th><th>Realizado por</th><th>Detalle</th></tr></thead>
      <tbody>
        <?php foreach ($auditLog as $entry): ?>
          <?php $esAsignar = ($entry['accion'] ?? '') === 'ASIGNAR_ADMIN'; ?>
          <tr>
            <td style="white-space:nowrap;color:var(--muted)"><?= e(fecha_hora_co((string)($entry['fecha'] ?? ''))) ?></td>
            <td><span class="audit-accion <?= $esAsignar ? '' : 'red' ?>"><?= $esAsignar ? 'Asignado' : 'Removido' ?></span></td>
            <td><?= e((string)($entry['nombre_actor'] ?? $entry['nit_actor'] ?? '')) ?></td>
            <td style="color:var(--muted)"><?= e((string)($entry['descripcion'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<script nonce="<?= e(csp_nonce()) ?>">
function detTab(btn, panelId) {
  document.querySelectorAll('.det-tab').forEach(function(t) { t.classList.remove('active'); });
  document.querySelectorAll('.det-panel').forEach(function(p) { p.classList.remove('active'); });
  btn.classList.add('active');
  var panel = document.getElementById(panelId);
  if (panel) panel.classList.add('active');
}

(function () {
  var hash = location.hash;
  if (hash === '#solicitudes') {
    var btn = document.querySelector('.det-tab[onclick*="tab-solicitudes"]');
    if (btn) detTab(btn, 'tab-solicitudes');
  } else if (hash === '#admin') {
    var btn2 = document.querySelector('.det-tab[onclick*="tab-admin"]');
    if (btn2) detTab(btn2, 'tab-admin');
  }
})();
</script>
