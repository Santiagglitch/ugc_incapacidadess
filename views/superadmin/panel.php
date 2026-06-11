<?php
$adminsAdicionales  = $adminsAdicionales  ?? [];
$limiteAdmins       = $limiteAdmins       ?? 10;
$logResultado       = $logResultado       ?? ['filas' => [], 'total' => 0, 'pagina' => 1, 'totalPaginas' => 1, 'porPagina' => 20];
$logFiltroAccion    = $logFiltroAccion    ?? '';
$logAccionesUnicas  = $logAccionesUnicas  ?? [];
$countsSolicitudes  = $countsSolicitudes  ?? [];
$totalSolicitudes   = $totalSolicitudes   ?? 0;
$totalEmpleados     = $totalEmpleados     ?? 0;
$totalAdmins        = $totalAdmins        ?? 1;
$estadoSistema      = $estadoSistema      ?? [];
$sesionesActivas    = $sesionesActivas    ?? [];
$tendenciaMeses     = $tendenciaMeses     ?? [];

$oracleOk  = (bool)($estadoSistema['oracle']['ok'] ?? false);
$oracleMsg = (string)($estadoSistema['oracle']['mensaje'] ?? '—');

$alertasCriticas = [];
if (!$oracleOk) {
    $alertasCriticas[] = 'Oracle DB no disponible: ' . $oracleMsg;
}
if (!($estadoSistema['oci_habilitado'] ?? false)) {
    $alertasCriticas[] = 'Extension OCI8 no habilitada en PHP.';
}
if (!($estadoSistema['storage_uploads'] ?? false)) {
    $alertasCriticas[] = 'Directorio uploads sin permisos de escritura.';
}

$iconAccion = static function (string $accion): string {
    return match ($accion) {
        'ASIGNAR_ADMIN' => '&#10003;',
        'QUITAR_ADMIN'  => '&times;',
        'LOGIN_SA'      => '&#9654;',
        'CAMBIO_MODO'   => '&#8644;',
        default         => '&#9632;',
    };
};

$labelAccion = static function (string $accion): string {
    return match ($accion) {
        'ASIGNAR_ADMIN' => 'Asignó admin',
        'QUITAR_ADMIN'  => 'Removió admin',
        'LOGIN_SA'      => 'Inicio sesión SA',
        'CAMBIO_MODO'   => 'Cambio de modo',
        default         => $accion,
    };
};

$iconCls = static function (string $accion): string {
    return match ($accion) {
        'ASIGNAR_ADMIN' => 'asignar',
        'QUITAR_ADMIN'  => 'quitar',
        'LOGIN_SA'      => 'login',
        'CAMBIO_MODO'   => 'modo',
        default         => 'otro',
    };
};

$logPagina     = (int)($logResultado['pagina']       ?? 1);
$logTotalPags  = (int)($logResultado['totalPaginas'] ?? 1);
$logTotal      = (int)($logResultado['total']        ?? 0);
$logFilas      = $logResultado['filas']              ?? [];

$logUrlFn = static function (int $pag) use ($logFiltroAccion): string {
    $params = array_filter(['view' => 'superadmin_panel', 'log_accion' => $logFiltroAccion, 'log_pagina' => $pag > 1 ? $pag : null]);
    return app_base_url('index.php') . '?' . http_build_query($params);
};
?>

<style>
.sa-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-bottom:28px}
.sa-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:22px 24px;box-shadow:var(--shadow)}
.sa-section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:14px;display:flex;align-items:center;gap:8px}
.sa-section-title::before{content:'';display:inline-block;width:3px;height:14px;background:var(--green);border-radius:2px}
.sa-health-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.sa-health-row:last-child{border-bottom:none}
.sa-health-label{color:var(--muted);font-weight:500}
.sa-health-val{font-weight:600}
.badge-ok{background:#d4edda;color:#155724;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-err{background:#f8d7da;color:#721c24;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-warn{background:#fff3cd;color:#856404;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-sa{background:linear-gradient(135deg,#ffd700,#ffed4a);color:#0a5a1f;padding:2px 12px;border-radius:20px;font-size:11px;font-weight:700;box-shadow:0 2px 6px rgba(255,215,0,.35)}
.sa-admins-list{list-style:none;padding:0;margin:0}
.sa-admins-list li{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.sa-admins-list li:last-child{border-bottom:none}
.sa-admins-nit{font-family:monospace;font-size:12px;color:var(--muted)}
.sa-log-list{list-style:none;padding:0;margin:0}
.sa-log-item{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:start;padding:9px 0;border-bottom:1px solid var(--border);font-size:12px}
.sa-log-item:last-child{border-bottom:none}
.sa-log-icon{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.sa-log-icon.asignar{background:#d4edda;color:#155724}
.sa-log-icon.quitar{background:#f8d7da;color:#721c24}
.sa-log-icon.login{background:#cce5ff;color:#004085}
.sa-log-icon.modo{background:#e2d9f3;color:#4a1d8a}
.sa-log-icon.otro{background:var(--gray);color:var(--muted)}
.sa-log-desc{line-height:1.4}
.sa-log-actor{color:var(--muted);font-size:11px}
.sa-log-fecha{color:var(--muted);font-size:11px;white-space:nowrap;text-align:right}
.sa-empty{color:var(--muted);font-size:13px;font-style:italic;padding:12px 0}
.sa-superadmin-nit{font-family:monospace;font-size:12px;color:var(--muted);background:var(--green3);padding:1px 8px;border-radius:4px}
.sa-limit-bar{height:6px;background:var(--border);border-radius:3px;margin-top:8px;overflow:hidden}
.sa-limit-fill{height:100%;border-radius:3px;background:var(--green2);transition:width .4s}
.sa-limit-fill.warn{background:#ffc107}
.sa-limit-fill.danger{background:#c0392b}
.sa-alert-banner{background:#f8d7da;border:1px solid #f5c2c7;border-radius:var(--r2);padding:14px 18px;margin-bottom:20px;display:flex;flex-direction:column;gap:6px}
.ses-table{width:100%;border-collapse:collapse;font-size:12px}
.ses-table th{text-align:left;padding:6px 10px;font-size:11px;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border)}
.ses-table td{padding:7px 10px;border-bottom:1px solid var(--border);vertical-align:middle}
.ses-table tr:last-child td{border-bottom:none}
.ses-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--green);margin-right:6px;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.ses-rol{display:inline-block;font-size:10px;font-weight:700;padding:1px 7px;border-radius:99px;background:var(--green3);color:var(--green);border:1px solid var(--green4)}
.ses-you{font-size:10px;color:var(--green);font-weight:700;margin-left:4px}
.sa-alert-banner-title{font-size:13px;font-weight:700;color:#721c24;display:flex;align-items:center;gap:8px}
.sa-alert-banner-item{font-size:12px;color:#721c24;display:flex;align-items:center;gap:6px}
.sa-alert-banner-item::before{content:'▲';font-size:10px}
.sa-mini-form{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.sa-mini-form input{flex:1;min-width:120px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--r2);font-size:13px;background:var(--surface);color:var(--text)}
.sa-log-controls{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.sa-log-controls select{padding:6px 10px;border:1px solid var(--border);border-radius:var(--r2);font-size:12px;background:var(--surface);color:var(--text)}
.sa-log-pagination{display:flex;align-items:center;gap:6px;margin-top:12px;flex-wrap:wrap}
.sa-log-meta{font-size:11px;color:var(--muted);margin-right:auto}
.sa-ac-input{flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:var(--r2);font-size:13px;background:var(--surface);color:var(--text);transition:border-color .15s}
.sa-ac-input:focus{outline:none;border-color:var(--green)}
.sa-ac-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:var(--r2);box-shadow:0 6px 24px rgba(0,0,0,.12);z-index:200;overflow:hidden}
.sa-ac-item{display:flex;flex-direction:column;padding:9px 12px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s}
.sa-ac-item:last-child{border-bottom:none}
.sa-ac-item:hover,.sa-ac-item.active{background:var(--green3)}
.sa-ac-item-name{font-size:13px;font-weight:600}
.sa-ac-item-meta{font-size:11px;color:var(--muted);font-family:monospace}
.sa-ac-item.ya-admin .sa-ac-item-name::after{content:' (ya admin)';font-size:11px;font-weight:400;color:var(--green);margin-left:4px}
.sa-ac-item.ya-admin{opacity:.6;cursor:not-allowed}
.sa-ac-loading{padding:10px 14px;font-size:12px;color:var(--muted)}
@media (prefers-color-scheme: dark){
  .badge-ok{background:#1a3322;color:#5cb85c}
  .badge-err{background:#3b1c1e;color:#f1aeb5}
  .badge-warn{background:#3d3418;color:#ffc107}
  .badge-sa{color:#0a5a1f}
  .sa-log-icon.asignar{background:#1a3322;color:#5cb85c}
  .sa-log-icon.quitar{background:#3b1c1e;color:#f1aeb5}
  .sa-log-icon.login{background:#0d2a4a;color:#7ec8ff}
  .sa-log-icon.modo{background:#2a1a4a;color:#c7a7f7}
  .sa-superadmin-nit{background:var(--green4)}
  .sa-alert-banner{background:#3b1c1e;border-color:#721c24}
  .sa-alert-banner-title,.sa-alert-banner-item{color:#f1aeb5}
}
</style>

<section class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
  <div>
    <h1 class="page-title">Panel Super Usuario</h1>
    <p style="color:var(--muted);font-size:14px;margin-top:4px">Administracion global del sistema &mdash; acceso exclusivo</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn btn-outline" href="<?= e(url_view('dashboard')) ?>">Dashboard</a>
    <a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">Empleados</a>
  </div>
</section>

<?php if (!empty($alertasCriticas)): ?>
<div class="sa-alert-banner animate-fade-down">
  <div class="sa-alert-banner-title">&#9888; Se detectaron problemas criticos en el sistema</div>
  <?php foreach ($alertasCriticas as $alerta): ?>
    <div class="sa-alert-banner-item"><?= e($alerta) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- KPIs globales -->
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:28px">
  <div class="stat-card">
    <div class="stat-icon">&#9671;</div>
    <div class="num"><?= e($totalSolicitudes) ?></div>
    <div class="lbl">Solicitudes totales</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#9650;</div>
    <div class="num"><?= e((int)($countsSolicitudes[ESTADO_PENDIENTE_JEFE] ?? 0)) ?></div>
    <div class="lbl">Pendientes jefe</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#9679;</div>
    <div class="num"><?= e((int)($countsSolicitudes[ESTADO_APROBADO_JEFE] ?? 0)) ?></div>
    <div class="lbl">Pendientes RRHH</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#9632;</div>
    <div class="num"><?= e($totalEmpleados) ?></div>
    <div class="lbl">Empleados activos</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#9733;</div>
    <div class="num"><?= e($totalAdmins) ?></div>
    <div class="lbl">Administradores</div>
  </div>
</div>

<?php
$rolSesLabel = ['administrador'=>'Admin','talento_humano'=>'RRHH','jefe_inmediato'=>'Jefe','solicitante'=>'Empleado'];
$nitActual = (string)($user['cedula'] ?? '');
?>

<div class="animate-fade-up" style="margin-bottom:28px">

  <!-- Sesiones activas -->
  <div class="sa-section">
    <div class="sa-section-title">
      Sesiones activas
      <span style="margin-left:auto;font-size:11px;font-weight:400;color:var(--muted)"><?= count($sesionesActivas) ?> usuario<?= count($sesionesActivas) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($sesionesActivas)): ?>
      <p class="sa-empty">No hay sesiones activas registradas.</p>
    <?php else: ?>
      <table class="ses-table">
        <thead><tr><th>Usuario</th><th>Rol</th><th>IP</th><th>Actividad</th></tr></thead>
        <tbody>
          <?php foreach ($sesionesActivas as $ses): ?>
            <?php $esYo = ($ses['nit'] ?? '') === $nitActual; ?>
            <tr>
              <td>
                <span class="ses-dot"></span>
                <strong><?= e($ses['nombre'] ?? $ses['nit'] ?? '—') ?></strong>
                <?php if ($esYo): ?><span class="ses-you">(tú)</span><?php endif; ?>
                <div style="font-size:10px;font-family:monospace;color:var(--muted)"><?= e($ses['nit'] ?? '') ?></div>
              </td>
              <td><span class="ses-rol"><?= e($rolSesLabel[$ses['rol'] ?? ''] ?? ($ses['rol'] ?? '—')) ?></span></td>
              <td style="font-family:monospace;font-size:11px;color:var(--muted)"><?= e($ses['ip'] ?? '') ?></td>
              <td style="white-space:nowrap;color:var(--muted);font-size:11px"><?= e($ses['hace'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>


</div>

<div class="sa-grid animate-fade-up">

  <!-- Salud del sistema -->
  <div class="sa-section">
    <div class="sa-section-title">Salud del sistema</div>
    <div class="sa-health-row">
      <span class="sa-health-label">Oracle DB</span>
      <span class="sa-health-val">
        <?php if ($oracleOk): ?><span class="badge-ok">&#10003; <?= e($oracleMsg) ?></span>
        <?php else: ?><span class="badge-err">&times; <?= e($oracleMsg) ?></span><?php endif; ?>
      </span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Extension OCI8</span>
      <span class="sa-health-val">
        <?php if ($estadoSistema['oci_habilitado'] ?? false): ?><span class="badge-ok">Habilitada</span>
        <?php else: ?><span class="badge-err">No disponible</span><?php endif; ?>
      </span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Extension LDAP</span>
      <span class="sa-health-val">
        <?php if ($estadoSistema['ldap_habilitado'] ?? false): ?><span class="badge-ok">Habilitada</span>
        <?php else: ?><span class="badge-err">No disponible</span><?php endif; ?>
      </span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Storage /uploads</span>
      <span class="sa-health-val">
        <?php if ($estadoSistema['storage_uploads'] ?? false): ?><span class="badge-ok">Escribible</span>
        <?php else: ?><span class="badge-err">Sin permisos</span><?php endif; ?>
      </span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Entorno</span>
      <span class="sa-health-val">
        <?php if (($estadoSistema['app_env'] ?? '') === 'production'): ?><span class="badge-ok">Produccion</span>
        <?php else: ?><span class="badge-warn">Desarrollo</span><?php endif; ?>
      </span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">PHP</span>
      <span class="sa-health-val" style="font-family:monospace;font-size:12px"><?= e($estadoSistema['php_version'] ?? '—') ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Version app</span>
      <span class="sa-health-val" style="font-family:monospace;font-size:12px"><?= e($estadoSistema['app_version'] ?? '—') ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Zona horaria</span>
      <span class="sa-health-val" style="font-size:12px"><?= e($estadoSistema['timezone'] ?? '—') ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Hora servidor</span>
      <span class="sa-health-val" style="font-size:12px"><?= e($estadoSistema['timestamp'] ?? '—') ?></span>
    </div>
  </div>

  <!-- Administradores del sistema -->
  <div class="sa-section">
    <div class="sa-section-title">
      Administradores del sistema
      <span style="margin-left:auto;font-size:11px;font-weight:600;color:var(--muted)"><?= e(count($adminsAdicionales)) ?>/<?= e($limiteAdmins) ?></span>
    </div>

    <?php
      $pctLimite  = $limiteAdmins > 0 ? round((count($adminsAdicionales) / $limiteAdmins) * 100) : 0;
      $barCls     = $pctLimite >= 90 ? 'danger' : ($pctLimite >= 70 ? 'warn' : '');
    ?>
    <div class="sa-limit-bar" title="<?= e($pctLimite) ?>% del limite usado">
      <div class="sa-limit-fill <?= e($barCls) ?>" style="width:<?= e($pctLimite) ?>%"></div>
    </div>

    <ul class="sa-admins-list" style="margin-top:12px">
      <li>
        <div>
          <div style="font-weight:600;font-size:13px">Super Usuario <span class="badge-sa">SA</span></div>
          <div class="sa-admins-nit"><?= e(SUPER_ADMIN_NIT) ?></div>
        </div>
        <span class="badge-ok">Permanente</span>
      </li>
      <?php if (empty($adminsAdicionales)): ?>
        <li><span class="sa-empty">Sin administradores adicionales asignados.</span></li>
      <?php else: ?>
        <?php foreach ($adminsAdicionales as $nitAdmin): ?>
          <li>
            <div>
              <div style="font-weight:600;font-size:13px">Administrador adicional</div>
              <div class="sa-admins-nit"><?= e($nitAdmin) ?></div>
            </div>
            <form method="post" action="<?= e(url_action('admin_quitar_admin')) ?>" style="display:inline">
              <?= csrf_input() ?>
              <input type="hidden" name="_method" value="DELETE">
              <input type="hidden" name="nit" value="<?= e($nitAdmin) ?>">
              <button class="btn btn-red btn-sm" type="submit"
                onclick="return confirm('¿Quitar acceso de administrador al NIT <?= e(addslashes($nitAdmin)) ?>?')">
                Quitar
              </button>
            </form>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>

    <?php if (!($pctLimite >= 100)): ?>
    <form method="post" action="<?= e(url_action('superadmin_asignar_admin')) ?>" class="sa-mini-form" id="form-asignar-admin" autocomplete="off">
      <?= csrf_input() ?>
      <div class="sa-ac-wrap" style="flex:1;position:relative;min-width:160px">
        <input
          type="text"
          id="sa-ac-input"
          class="sa-ac-input"
          placeholder="NIT o nombre del empleado"
          maxlength="80"
          autocomplete="off"
          spellcheck="false"
          style="width:100%"
        >
        <input type="hidden" name="nit" id="sa-ac-nit">
        <div id="sa-ac-dropdown" class="sa-ac-dropdown" hidden></div>
      </div>
      <button class="btn btn-green btn-sm" type="submit" id="sa-ac-submit" disabled>Asignar</button>
    </form>
    <?php else: ?>
    <p style="margin-top:12px;font-size:12px;color:var(--red)">Limite de administradores alcanzado (<?= e($limiteAdmins) ?>).</p>
    <?php endif; ?>

    <div style="margin-top:12px">
      <a class="btn btn-outline" href="<?= e(url_view('admin_empleados') . '&rol=empleado') ?>" style="font-size:12px;width:100%;text-align:center">
        Ver directorio de empleados &rarr;
      </a>
    </div>
  </div>

  <!-- Configuracion visible -->
  <div class="sa-section">
    <div class="sa-section-title">Configuracion del sistema</div>
    <div class="sa-health-row">
      <span class="sa-health-label">Nivel minimo jefe</span>
      <span class="sa-health-val" style="font-family:monospace"><?= e(NIVEL_MIN_JEFE) ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Nivel minimo admin</span>
      <span class="sa-health-val" style="font-family:monospace"><?= e(NIVEL_MIN_ADMIN) ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Talento Humano fijo</span>
      <span class="sa-health-val" style="font-family:monospace;font-size:11px"><?= e(implode(', ', NITS_TALENTO_HUMANO)) ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Centros costo aprendices</span>
      <span class="sa-health-val" style="font-family:monospace;font-size:11px"><?= e(implode(', ', CC_APRENDICES)) ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Timeout sesion</span>
      <span class="sa-health-val"><?= e(defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT . ' seg (' . round(SESSION_IDLE_TIMEOUT / 60) . ' min)' : '—') ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Filas por pagina</span>
      <span class="sa-health-val"><?= e(defined('APP_ROWS_PER_PAGE') ? APP_ROWS_PER_PAGE : '—') ?></span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Tipos de solicitud</span>
      <span class="sa-health-val"><?= e(count(TIPOS_SOLICITUD)) ?> tipos</span>
    </div>
    <div class="sa-health-row">
      <span class="sa-health-label">Limite admins adicionales</span>
      <span class="sa-health-val" style="font-family:monospace"><?= e($limiteAdmins) ?></span>
    </div>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
      <p style="font-size:11px;color:var(--muted);line-height:1.6">
        Para modificar estos valores edita <code>config/server.php</code> y <code>config/app.php</code>.
      </p>
    </div>
  </div>

</div>

<!-- Acciones rapidas -->
<div class="sa-grid animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:28px">
  <a class="stat-card stat-card-link" href="<?= e(url_view('dashboard')) ?>">
    <div class="stat-icon">&#9632;</div>
    <div class="num" style="font-size:18px">Dashboard</div>
    <div class="lbl">Panel de solicitudes</div>
  </a>
  <a class="stat-card stat-card-link" href="<?= e(url_view('admin_empleados')) ?>">
    <div class="stat-icon">&#9650;</div>
    <div class="num" style="font-size:18px">Empleados</div>
    <div class="lbl">Directorio y roles</div>
  </a>
  <a class="stat-card stat-card-link" href="Export/Admin/ExportController.php?download=1">
    <div class="stat-icon">&#8659;</div>
    <div class="num" style="font-size:18px">Exportar datos</div>
    <div class="lbl">Descargar Excel completo</div>
  </a>
  <a class="stat-card stat-card-link" href="<?= e(url_view('superadmin_auditoria_export')) ?>">
    <div class="stat-icon">&#9632;</div>
    <div class="num" style="font-size:18px">Auditoria CSV</div>
    <div class="lbl">Descargar log de acciones</div>
  </a>
</div>

<!-- Log de auditoria -->
<div class="sa-section animate-fade-up" style="margin-bottom:28px">
  <div class="sa-section-title" style="justify-content:space-between;flex-wrap:wrap;gap:8px">
    <span>Auditoria de acciones administrativas</span>
    <a class="btn btn-outline btn-sm" href="<?= e(url_view('superadmin_auditoria_export')) ?>">&#8659; CSV</a>
  </div>

  <div class="sa-log-controls">
    <form method="get" action="<?= e(app_base_url('index.php')) ?>" style="display:contents">
      <input type="hidden" name="view" value="superadmin_panel">
      <select name="log_accion" onchange="this.form.submit()">
        <option value="">Todas las acciones</option>
        <?php foreach ($logAccionesUnicas as $ac): ?>
          <option value="<?= e($ac) ?>" <?= $logFiltroAccion === $ac ? 'selected' : '' ?>><?= e($ac) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($logFiltroAccion !== ''): ?>
        <a class="btn btn-gray btn-sm" href="<?= e(url_view('superadmin_panel')) ?>">Limpiar</a>
      <?php endif; ?>
    </form>
    <span style="margin-left:auto;font-size:12px;color:var(--muted)"><?= e($logTotal) ?> registros</span>
  </div>

  <?php if (empty($logFilas)): ?>
    <p class="sa-empty">No hay acciones registradas<?= $logFiltroAccion !== '' ? ' para este filtro' : '' ?>.</p>
  <?php else: ?>
    <div style="max-height:420px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--r2)">
    <ul class="sa-log-list" style="margin:0">
      <?php foreach ($logFilas as $entrada): ?>
        <?php $accion = (string)($entrada['accion'] ?? ''); ?>
        <li class="sa-log-item" style="padding-left:12px;padding-right:12px">
          <span class="sa-log-icon <?= e($iconCls($accion)) ?>"><?= $iconAccion($accion) ?></span>
          <div class="sa-log-desc">
            <div style="font-weight:600"><?= e($labelAccion($accion)) ?></div>
            <div><?= e($entrada['descripcion'] ?? '') ?></div>
            <div class="sa-log-actor">
              <?= e($entrada['nombre_actor'] ?? $entrada['nit_actor'] ?? '—') ?>
              (<?= e($entrada['nit_actor'] ?? '—') ?>)
              &mdash; IP: <?= e($entrada['ip'] ?? '—') ?>
            </div>
          </div>
          <div class="sa-log-fecha"><?= e(fecha_hora_co((string)($entrada['fecha'] ?? ''))) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
    </div>

    <?php if ($logTotalPags > 1): ?>
    <div class="sa-log-pagination">
      <span class="sa-log-meta">Pag. <?= e($logPagina) ?> de <?= e($logTotalPags) ?></span>
      <?php if ($logPagina > 1): ?>
        <a class="btn btn-outline btn-sm" href="<?= e($logUrlFn($logPagina - 1)) ?>">Anterior</a>
      <?php endif; ?>
      <?php for ($p = max(1, $logPagina - 2); $p <= min($logTotalPags, $logPagina + 2); $p++): ?>
        <?php if ($p === $logPagina): ?>
          <span class="btn btn-green btn-sm"><?= e($p) ?></span>
        <?php else: ?>
          <a class="btn btn-outline btn-sm" href="<?= e($logUrlFn($p)) ?>"><?= e($p) ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($logPagina < $logTotalPags): ?>
        <a class="btn btn-outline btn-sm" href="<?= e($logUrlFn($logPagina + 1)) ?>">Siguiente</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var input    = document.getElementById('sa-ac-input');
  var hidden   = document.getElementById('sa-ac-nit');
  var dropdown = document.getElementById('sa-ac-dropdown');
  var submit   = document.getElementById('sa-ac-submit');

  if (!input) return;

  var endpoint = <?= json_encode(url_view('superadmin_buscar_empleado')) ?>;
  var timer    = null;
  var activeIdx = -1;
  var items     = [];

  function getItems() {
    return dropdown.querySelectorAll('.sa-ac-item:not(.ya-admin)');
  }

  function clearActive() {
    dropdown.querySelectorAll('.sa-ac-item').forEach(function(el) {
      el.classList.remove('active');
    });
  }

  function selectItem(nit, nombre) {
    input.value  = nombre + ' — ' + nit;
    hidden.value = nit;
    submit.disabled = false;
    dropdown.hidden = true;
    activeIdx = -1;
  }

  function renderDropdown(data) {
    dropdown.innerHTML = '';
    activeIdx = -1;
    items = [];

    if (!data.length) {
      dropdown.innerHTML = '<div class="sa-ac-loading">Sin resultados</div>';
      dropdown.hidden = false;
      return;
    }

    data.forEach(function(emp) {
      var li = document.createElement('div');
      li.className = 'sa-ac-item' + (emp.ya_admin ? ' ya-admin' : '');
      li.innerHTML =
        '<span class="sa-ac-item-name">' + esc(emp.nombre) + '</span>' +
        '<span class="sa-ac-item-meta">NIT: ' + esc(emp.nit) + (emp.cc ? ' &nbsp;·&nbsp; CC: ' + esc(emp.cc) : '') + '</span>';

      if (!emp.ya_admin) {
        li.addEventListener('mousedown', function(e) {
          e.preventDefault();
          selectItem(emp.nit, emp.nombre);
        });
        items.push(li);
      }
      dropdown.appendChild(li);
    });

    dropdown.hidden = false;
  }

  function esc(str) {
    return String(str)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;');
  }

  function buscar(q) {
    if (q.length < 2) {
      dropdown.hidden = true;
      return;
    }
    dropdown.hidden = false;
    dropdown.innerHTML = '<div class="sa-ac-loading">Buscando...</div>';

    var url = endpoint + '&q=' + encodeURIComponent(q);
    fetch(url, { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(data) { renderDropdown(data); })
      .catch(function() { dropdown.hidden = true; });
  }

  input.addEventListener('input', function() {
    hidden.value = '';
    submit.disabled = true;
    clearTimeout(timer);
    timer = setTimeout(function() { buscar(input.value.trim()); }, 250);
  });

  input.addEventListener('keydown', function(e) {
    var selectables = getItems();
    if (!selectables.length) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIdx = Math.min(activeIdx + 1, selectables.length - 1);
      clearActive();
      selectables[activeIdx].classList.add('active');
      selectables[activeIdx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIdx = Math.max(activeIdx - 1, 0);
      clearActive();
      selectables[activeIdx].classList.add('active');
      selectables[activeIdx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      if (activeIdx >= 0 && selectables[activeIdx]) {
        e.preventDefault();
        selectables[activeIdx].dispatchEvent(new MouseEvent('mousedown'));
      }
    } else if (e.key === 'Escape') {
      dropdown.hidden = true;
    }
  });

  document.addEventListener('click', function(e) {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.hidden = true;
    }
  });
})();
</script>
