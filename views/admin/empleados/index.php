<?php
$filtroRol  = $filtroRol  ?? '';
$filtroCC   = $filtroCC   ?? '';
$busqueda   = $busqueda   ?? '';
$ordenCol   = $ordenCol   ?? 'nombre';
$ordenDir   = $ordenDir   ?? 'asc';
$porPagina  = $porPagina  ?? 6;
$centrosCosto = $centrosCosto ?? [];
$conteoSolicitudes = $conteoSolicitudes ?? [];

$rolLabel = ['admin' => 'Administrador', 'rrhh' => 'Talento Humano', 'jefe' => 'Jefe', 'empleado' => 'Empleado'];

$empleadosUrl = static function (string $rol = '') use ($busqueda, $filtroCC, $porPagina): string {
  $params = array_filter(['q' => $busqueda, 'rol' => $rol, 'cc' => $filtroCC, 'pp' => $porPagina !== 6 ? $porPagina : ''], fn($v) => $v !== '' && $v !== null);
  return url_view('admin_empleados') . (!empty($params) ? '&' . http_build_query($params) : '');
};

$exportUrl = static function () use ($busqueda, $filtroRol, $filtroCC): string {
  $params = array_filter(['q' => $busqueda, 'rol' => $filtroRol, 'cc' => $filtroCC], fn($v) => $v !== '');
  return url_view('admin_empleados_export') . (!empty($params) ? '&' . http_build_query($params) : '');
};

$ordenUrl = static function (string $col) use ($busqueda, $filtroRol, $filtroCC, $ordenCol, $ordenDir, $porPagina): string {
  $dir = ($ordenCol === $col && $ordenDir === 'asc') ? 'desc' : 'asc';
  $params = array_filter(['q' => $busqueda, 'rol' => $filtroRol, 'cc' => $filtroCC, 'ord' => $col, 'dir' => $dir, 'pp' => $porPagina !== 6 ? $porPagina : ''], fn($v) => $v !== '' && $v !== null);
  return url_view('admin_empleados') . '&' . http_build_query($params);
};

$ordenIcon = static function (string $col) use ($ordenCol, $ordenDir): string {
  if ($ordenCol !== $col) return '<span class="sort-icon">&#8597;</span>';
  return $ordenDir === 'asc' ? '<span class="sort-icon asc">&#8593;</span>' : '<span class="sort-icon desc">&#8595;</span>';
};

$statCardClass = static fn(string $rol = ''): string => 'stat-card stat-card-link' . ($filtroRol === $rol ? ' is-active' : '');

function _rolBadge(array $emp, array $adminsAdicionales): array {
  $nit = (string)($emp['NIT'] ?? '');
  $esAdmin = $nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales, true);
  if ($esAdmin) return ['Administrador', 'admin'];
  if (es_jefe_revisor_nit($nit)) return ['Jefe', 'jefe'];
  if (in_array(($emp['CENTRO_COSTO'] ?? ''), CC_RRHH, true)) return ['Talento Humano', 'rrhh'];
  return ['Empleado', 'empleado'];
}
?>

<style>
.emp-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.emp-toolbar-right{margin-left:auto;display:flex;gap:8px;align-items:center}
.sort-icon{font-size:11px;opacity:.5;margin-left:3px}
.sort-icon.asc,.sort-icon.desc{opacity:1;color:var(--green)}
th a{color:inherit;text-decoration:none;white-space:nowrap}
th a:hover{color:var(--green2)}
.badge-act{display:inline-flex;align-items:center;gap:4px;background:var(--green3);color:var(--green);border-radius:99px;padding:2px 8px;font-size:11px;font-weight:600;border:1px solid var(--green4)}
.badge-act.none{background:var(--gray);color:var(--muted);border-color:var(--border)}
.emp-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.emp-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px;display:flex;flex-direction:column;gap:10px;box-shadow:var(--shadow);transition:box-shadow .18s,transform .18s}
.emp-card:hover{box-shadow:var(--shadow2);transform:translateY(-2px)}
.emp-card-avatar{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green2));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:17px;flex-shrink:0}
.emp-card-head{display:flex;align-items:center;gap:12px}
.emp-card-name{font-weight:700;font-size:14px;line-height:1.3}
.emp-card-nit{font-size:11px;color:var(--muted);font-family:monospace}
.emp-card-meta{font-size:12px;color:var(--muted);display:flex;flex-direction:column;gap:3px}
.emp-card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
.view-toggle{display:flex;border:1px solid var(--border);border-radius:var(--r2);overflow:hidden}
.view-toggle button{padding:6px 11px;background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px;transition:background .15s}
.view-toggle button.active{background:var(--green3);color:var(--green)}
.pp-select{padding:6px 10px;border:1px solid var(--border);border-radius:var(--r2);font-size:12px;background:var(--surface);color:var(--text);cursor:pointer}
.emp-filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px 20px;margin-bottom:16px}
.emp-filter-row .form-group{flex:1;min-width:140px;margin:0}
.emp-filter-row .form-group label{display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:var(--muted)}
.emp-filter-row input,.emp-filter-row select{width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--r2);font-size:13px;background:var(--surface);color:var(--text)}
.hidden-by-search{display:none!important}
</style>

<section class="page-header">
  <div>
    <h1 class="page-title">Gestion de Empleados</h1>
    <p style="color:var(--muted)">Directorio de empleados activos &mdash; <?= e($total ?? 0) ?> resultado<?= ($total ?? 0) !== 1 ? 's' : '' ?></p>
  </div>
  <a class="btn btn-outline" href="<?= e(url_view('dashboard')) ?>">Dashboard</a>
</section>

<div class="stats-row">
  <a class="<?= e($statCardClass('')) ?>"       href="<?= e($empleadosUrl()) ?>">      <div class="num"><?= e($stats['total']    ?? 0) ?></div><div class="lbl">Total</div></a>
  <a class="<?= e($statCardClass('admin')) ?>"  href="<?= e($empleadosUrl('admin')) ?>"><div class="num"><?= e($stats['admin']    ?? 0) ?></div><div class="lbl">Administradores</div></a>
  <a class="<?= e($statCardClass('rrhh')) ?>"   href="<?= e($empleadosUrl('rrhh')) ?>"> <div class="num"><?= e($stats['rrhh']     ?? 0) ?></div><div class="lbl">Talento Humano</div></a>
  <a class="<?= e($statCardClass('jefe')) ?>"   href="<?= e($empleadosUrl('jefe')) ?>"> <div class="num"><?= e($stats['jefe']     ?? 0) ?></div><div class="lbl">Jefes</div></a>
  <a class="<?= e($statCardClass('empleado')) ?>" href="<?= e($empleadosUrl('empleado')) ?>"><div class="num"><?= e($stats['empleado'] ?? 0) ?></div><div class="lbl">Empleados</div></a>
</div>

<form class="emp-filter-row" method="get" action="<?= e(app_base_url('index.php')) ?>" id="emp-filter-form">
  <input type="hidden" name="view" value="admin_empleados">
  <input type="hidden" name="ord"  value="<?= e($ordenCol) ?>">
  <input type="hidden" name="dir"  value="<?= e($ordenDir) ?>">

  <div class="form-group" style="flex:2;min-width:180px">
    <label>Buscar</label>
    <input type="text" id="emp-search-input" name="q" value="<?= e($busqueda) ?>" placeholder="Nombre o NIT&hellip;" autocomplete="off">
  </div>

  <div class="form-group">
    <label>Rol</label>
    <select name="rol">
      <option value="">Todos los roles</option>
      <option value="admin"    <?= $filtroRol === 'admin'    ? 'selected' : '' ?>>Administrador</option>
      <option value="rrhh"     <?= $filtroRol === 'rrhh'     ? 'selected' : '' ?>>Talento Humano</option>
      <option value="jefe"     <?= $filtroRol === 'jefe'     ? 'selected' : '' ?>>Jefe</option>
      <option value="empleado" <?= $filtroRol === 'empleado' ? 'selected' : '' ?>>Empleado</option>
    </select>
  </div>

  <?php if (!empty($centrosCosto)): ?>
  <div class="form-group">
    <label>Centro de Costo</label>
    <select name="cc">
      <option value="">Todos los CC</option>
      <?php foreach ($centrosCosto as $cc): ?>
        <option value="<?= e($cc) ?>" <?= $filtroCC === $cc ? 'selected' : '' ?>><?= e($cc) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <div class="form-group">
    <label>Por página</label>
    <select name="pp" class="pp-select" onchange="this.form.submit()">
      <?php foreach ([6, 12, 24] as $pp): ?>
        <option value="<?= $pp ?>" <?= (int)$porPagina === $pp ? 'selected' : '' ?>><?= $pp ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div style="display:flex;gap:8px;align-items:flex-end">
    <button class="btn btn-green" type="submit">Filtrar</button>
    <a class="btn btn-gray" href="<?= e(url_view('admin_empleados')) ?>">Limpiar</a>
  </div>
</form>

<div class="emp-toolbar">
  <span style="font-size:13px;color:var(--muted)">
    Mostrando <?= e(count($empleados ?? [])) ?> de <?= e($total ?? 0) ?> empleados
  </span>
  <div class="emp-toolbar-right">
    <a class="btn btn-outline btn-sm" href="<?= e($exportUrl()) ?>" title="Descargar CSV con filtros actuales">
      &#8595; Exportar CSV
    </a>
    <div class="view-toggle" title="Cambiar vista">
      <button type="button" id="btn-view-table" class="active" title="Vista tabla">&#9776;</button>
      <button type="button" id="btn-view-cards" title="Vista tarjetas">&#9638;</button>
    </div>
  </div>
</div>

<?php if (empty($empleados)): ?>
  <div class="empty-state"><h2>No hay empleados para mostrar</h2><p>Revisa la conexion Oracle o los filtros aplicados.</p></div>
<?php else: ?>

<div id="emp-view-table">
<div class="ugc-table-wrap animate-fade-up">
  <table class="ugc-table">
    <thead>
      <tr>
        <th><a href="<?= e($ordenUrl('nit')) ?>">NIT <?= $ordenIcon('nit') ?></a></th>
        <th><a href="<?= e($ordenUrl('nombre')) ?>">Nombre <?= $ordenIcon('nombre') ?></a></th>
        <th><a href="<?= e($ordenUrl('cc')) ?>">Centro Costo <?= $ordenIcon('cc') ?></a></th>
        <th><a href="<?= e($ordenUrl('nivel')) ?>">Nivel <?= $ordenIcon('nivel') ?></a></th>
        <th>Rol</th>
        <th>Solicitudes</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($empleados as $emp): ?>
        <?php
          $nit = (string)($emp['NIT'] ?? '');
          [$rolTexto, $rolSlug] = _rolBadge($emp, $adminsAdicionales ?? []);
          $cnt = $conteoSolicitudes[$nit] ?? ['total' => 0, 'activas' => 0];
        ?>
        <tr class="emp-row" data-nombre="<?= e(mb_strtolower($emp['NOMBRE_COMPLETO'] ?? '', 'UTF-8')) ?>" data-nit="<?= e($nit) ?>">
          <td data-label="NIT"><code style="font-size:12px"><?= e($nit) ?></code></td>
          <td data-label="Nombre"><strong><?= e($emp['NOMBRE_COMPLETO'] ?? '') ?></strong></td>
          <td data-label="Centro Costo"><?= e($emp['CENTRO_COSTO'] ?? '') ?></td>
          <td data-label="Nivel"><?= e($emp['NIVEL'] ?? '') ?></td>
          <td data-label="Rol"><span class="badge badge-role-<?= e($rolSlug) ?>"><?= e($rolTexto) ?></span></td>
          <td data-label="Solicitudes">
            <?php if ($cnt['activas'] > 0): ?>
              <span class="badge-act" title="<?= e($cnt['activas']) ?> activas / <?= e($cnt['total']) ?> total">
                &#9679; <?= e($cnt['activas']) ?> activa<?= $cnt['activas'] !== 1 ? 's' : '' ?>
              </span>
            <?php elseif ($cnt['total'] > 0): ?>
              <span class="badge-act none" title="<?= e($cnt['total']) ?> solicitudes total"><?= e($cnt['total']) ?></span>
            <?php else: ?>
              <span style="font-size:12px;color:var(--muted)">—</span>
            <?php endif; ?>
          </td>
          <td data-label="Acciones">
            <a class="btn btn-outline btn-sm" href="<?= e(url_view('admin_empleado') . '&nit=' . urlencode($nit)) ?>">Ver perfil</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>

<div id="emp-view-cards" style="display:none">
  <div class="emp-cards">
    <?php foreach ($empleados as $emp): ?>
      <?php
        $nit = (string)($emp['NIT'] ?? '');
        [$rolTexto, $rolSlug] = _rolBadge($emp, $adminsAdicionales ?? []);
        $cnt = $conteoSolicitudes[$nit] ?? ['total' => 0, 'activas' => 0];
        $nombre = trim((string)($emp['NOMBRE_COMPLETO'] ?? ''));
        $iniciales = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'), array_slice(explode(' ', $nombre), 0, 2)));
      ?>
      <div class="emp-card emp-row" data-nombre="<?= e(mb_strtolower($nombre, 'UTF-8')) ?>" data-nit="<?= e($nit) ?>">
        <div class="emp-card-head">
          <div class="emp-card-avatar"><?= e($iniciales ?: '?') ?></div>
          <div>
            <div class="emp-card-name"><?= e($nombre) ?></div>
            <div class="emp-card-nit"><?= e($nit) ?></div>
          </div>
        </div>
        <div class="emp-card-meta">
          <span>CC: <?= e($emp['CENTRO_COSTO'] ?? '—') ?></span>
          <span>Nivel: <?= e($emp['NIVEL'] ?? '—') ?></span>
        </div>
        <div class="emp-card-footer">
          <span class="badge badge-role-<?= e($rolSlug) ?>"><?= e($rolTexto) ?></span>
          <?php if ($cnt['activas'] > 0): ?>
            <span class="badge-act" title="Solicitudes activas">&#9679; <?= e($cnt['activas']) ?></span>
          <?php elseif ($cnt['total'] > 0): ?>
            <span class="badge-act none"><?= e($cnt['total']) ?></span>
          <?php endif; ?>
        </div>
        <a class="btn btn-outline btn-sm" href="<?= e(url_view('admin_empleado') . '&nit=' . urlencode($nit)) ?>" style="text-align:center">Ver perfil</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php endif; ?>

<?php
require_once dirname(__DIR__, 2) . '/shared/pagination.php';
ugcRenderPagination([
  'current'    => $pagina     ?? 1,
  'totalPages' => $totalPaginas ?? 1,
  'total'      => $total      ?? 0,
  'perPage'    => $porPagina  ?? 6,
  'paramName'  => 'pagina',
  'extra'      => array_filter(['q' => $busqueda, 'rol' => $filtroRol, 'cc' => $filtroCC, 'ord' => $ordenCol, 'dir' => $ordenDir, 'pp' => $porPagina !== 6 ? $porPagina : ''], fn($v) => $v !== '' && $v !== null),
], 'empleados');
?>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var STORAGE_KEY = 'ugc_emp_view';

  function setView(v) {
    localStorage.setItem(STORAGE_KEY, v);
    document.getElementById('emp-view-table').style.display = v === 'table' ? '' : 'none';
    document.getElementById('emp-view-cards').style.display = v === 'cards' ? '' : 'none';
    document.getElementById('btn-view-table').classList.toggle('active', v === 'table');
    document.getElementById('btn-view-cards').classList.toggle('active', v === 'cards');
  }

  window.setView = setView;

  var btnTable = document.getElementById('btn-view-table');
  var btnCards = document.getElementById('btn-view-cards');
  if (btnTable) btnTable.addEventListener('click', function () { setView('table'); });
  if (btnCards) btnCards.addEventListener('click', function () { setView('cards'); });

  setView(localStorage.getItem(STORAGE_KEY) || 'table');

  var searchInput = document.getElementById('emp-search-input');
  if (!searchInput) return;

  var timer = null;
  searchInput.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      var q = searchInput.value.trim().toLowerCase();
      document.querySelectorAll('.emp-row').forEach(function (row) {
        if (q === '') {
          row.classList.remove('hidden-by-search');
          return;
        }
        var nombre = (row.dataset.nombre || '');
        var nit    = (row.dataset.nit    || '');
        row.classList.toggle('hidden-by-search', !nombre.includes(q) && !nit.includes(q));
      });
    }, 200);
  });
})();
</script>
