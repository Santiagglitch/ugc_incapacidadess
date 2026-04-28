<section class="page-header">
  <div>
    <h1 class="page-title"><?= e($empleado['NOMBRE_COMPLETO'] ?? 'Empleado') ?></h1>
    <p style="color:var(--muted)">NIT: <?= e($empleado['NIT'] ?? '') ?> | Centro costo: <?= e($empleado['CENTRO_COSTO'] ?? '') ?> | Nivel: <?= e($empleado['NIVEL'] ?? '') ?></p>
  </div>
  <a class="btn btn-outline" href="<?= e(url_view('admin_empleados')) ?>">Volver</a>
</section>

<div class="stats-row">
  <div class="stat-card"><div class="num"><?= e($stats['total'] ?? 0) ?></div><div class="lbl">Solicitudes</div></div>
  <div class="stat-card"><div class="num"><?= e($stats['pendientes'] ?? 0) ?></div><div class="lbl">Pendientes</div></div>
  <div class="stat-card"><div class="num"><?= e($stats['aprobadas'] ?? 0) ?></div><div class="lbl">Aprobadas</div></div>
  <div class="stat-card"><div class="num"><?= e($stats['rechazadas'] ?? 0) ?></div><div class="lbl">Rechazadas</div></div>
</div>

<?php if (!empty($puedeGestionarAdmin)): ?>
  <div class="form-card" style="margin-bottom:20px">
    <h2>Gestion de administrador</h2>
    <?php if (!empty($esAdminAdicional)): ?>
      <p>Este empleado tiene acceso de administrador adicional.</p>
      <form method="post" action="<?= e(url_action('admin_quitar_admin')) ?>">
        <?= csrf_input() ?><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="nit" value="<?= e($empleado['NIT'] ?? '') ?>">
        <button class="btn btn-red" type="submit">Quitar administrador</button>
      </form>
    <?php else: ?>
      <p>El super usuario puede asignar acceso de administrador a este empleado.</p>
      <form method="post" action="<?= e(url_action('admin_hacer_admin')) ?>">
        <?= csrf_input() ?><input type="hidden" name="_method" value="PUT"><input type="hidden" name="nit" value="<?= e($empleado['NIT'] ?? '') ?>">
        <button class="btn btn-green" type="submit">Hacer administrador</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="section-header"><h2>Historial de solicitudes</h2></div>
<?php require dirname(__DIR__, 2) . '/shared/tabla_solicitudes.php'; ?>