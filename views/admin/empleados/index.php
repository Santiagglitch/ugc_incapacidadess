<?php
$filtroRol = $filtroRol ?? '';
$busqueda = $busqueda ?? '';
$empleadosUrl = static function (string $rol = '') use ($busqueda): string {
  $params = array_filter([
    'q' => $busqueda,
    'rol' => $rol,
  ], static fn($value): bool => $value !== '' && $value !== null);

  return url_view('admin_empleados') . (!empty($params) ? '&' . http_build_query($params) : '');
};
$statCardClass = static fn(string $rol = ''): string => 'stat-card stat-card-link' . ($filtroRol === $rol ? ' is-active' : '');
?>
<section class="page-header">
  <div>
    <h1 class="page-title">Gestion de Empleados</h1>
    <p style="color:var(--muted)">Directorio de empleados activos desde Oracle.</p>
  </div>
  <a class="btn btn-outline" href="<?= e(url_view('dashboard')) ?>">Dashboard</a>
</section>

<div class="stats-row">
  <a class="<?= e($statCardClass('')) ?>" href="<?= e($empleadosUrl()) ?>"><div class="num"><?= e($stats['total'] ?? 0) ?></div><div class="lbl">Total</div></a>
  <a class="<?= e($statCardClass('admin')) ?>" href="<?= e($empleadosUrl('admin')) ?>"><div class="num"><?= e($stats['admin'] ?? 0) ?></div><div class="lbl">Administradores</div></a>
  <a class="<?= e($statCardClass('rrhh')) ?>" href="<?= e($empleadosUrl('rrhh')) ?>"><div class="num"><?= e($stats['rrhh'] ?? 0) ?></div><div class="lbl">Talento Humano</div></a>
  <a class="<?= e($statCardClass('jefe')) ?>" href="<?= e($empleadosUrl('jefe')) ?>"><div class="num"><?= e($stats['jefe'] ?? 0) ?></div><div class="lbl">Jefes</div></a>
  <a class="<?= e($statCardClass('empleado')) ?>" href="<?= e($empleadosUrl('empleado')) ?>"><div class="num"><?= e($stats['empleado'] ?? 0) ?></div><div class="lbl">Empleados</div></a>
</div>

<div class="form-card" style="margin-bottom:18px">
  <form method="get" action="<?= e(app_base_url('index.php')) ?>" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
    <input type="hidden" name="view" value="admin_empleados">
    <div class="form-group" style="flex:1;min-width:240px">
      <label>Buscar</label>
      <input type="text" name="q" value="<?= e($busqueda ?? '') ?>" placeholder="Nombre o documento">
    </div>
    <div class="form-group" style="min-width:180px">
      <label>Rol</label>
      <select name="rol">
        <option value="">Todos</option>
        <option value="admin" <?= ($filtroRol ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
        <option value="rrhh" <?= ($filtroRol ?? '') === 'rrhh' ? 'selected' : '' ?>>Talento Humano</option>
        <option value="jefe" <?= ($filtroRol ?? '') === 'jefe' ? 'selected' : '' ?>>Jefe</option>
        <option value="empleado" <?= ($filtroRol ?? '') === 'empleado' ? 'selected' : '' ?>>Empleado</option>
      </select>
    </div>
    <button class="btn btn-green" type="submit">Filtrar</button>
    <a class="btn btn-gray" href="<?= e(url_view('admin_empleados')) ?>">Limpiar</a>
  </form>
</div>

<?php if (empty($empleados)): ?>
  <div class="empty-state"><h2>No hay empleados para mostrar</h2><p>Revisa conexion Oracle o filtros aplicados.</p></div>
<?php else: ?>
<div class="ugc-table-wrap">
  <table class="ugc-table">
    <thead><tr><th>NIT</th><th>Nombre</th><th>Centro costo</th><th>Nivel</th><th>Rol</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach ($empleados as $emp): ?>
        <?php
          $nit = (string) ($emp['NIT'] ?? '');
          $esAdmin = $nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales ?? [], true);
          $rol = $esAdmin ? 'Administrador' : (in_array(($emp['CENTRO_COSTO'] ?? ''), CC_RRHH, true) ? 'Talento Humano' : (((int)($emp['NIVEL'] ?? 0) >= NIVEL_MIN_JEFE) ? 'Jefe' : 'Empleado'));
          $rolBadge = $esAdmin ? 'admin' : (in_array(($emp['CENTRO_COSTO'] ?? ''), CC_RRHH, true) ? 'rrhh' : (((int)($emp['NIVEL'] ?? 0) >= NIVEL_MIN_JEFE) ? 'jefe' : 'empleado'));
        ?>
        <tr>
          <td data-label="NIT"><?= e($nit) ?></td>
          <td data-label="Nombre"><?= e($emp['NOMBRE_COMPLETO'] ?? '') ?></td>
          <td data-label="Centro costo"><?= e($emp['CENTRO_COSTO'] ?? '') ?></td>
          <td data-label="Nivel"><?= e($emp['NIVEL'] ?? '') ?></td>
          <td data-label="Rol"><span class="badge badge-role-<?= e($rolBadge) ?>"><?= e($rol) ?></span></td>
          <td data-label="Acciones"><a class="btn btn-outline" href="<?= e(url_view('admin_empleado') . '&nit=' . urlencode($nit)) ?>">Ver perfil</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php
require_once dirname(__DIR__, 2) . '/shared/pagination.php';
ugcRenderPagination([
  'current' => $pagina ?? 1,
  'totalPages' => $totalPaginas ?? 1,
  'total' => $total ?? 0,
  'perPage' => 12,
  'paramName' => 'pagina',
], 'empleados');
?>
