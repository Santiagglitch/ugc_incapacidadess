<?php
require_once __DIR__ . '/pagination.php';
$solicitudes = $solicitudes ?? [];
$user = $user ?? usuario_actual();
$paginationParam = $paginationParam ?? 'pagina';
$paginationLabel = $paginationLabel ?? 'solicitudes';
$pagination = ugcPaginateRows($solicitudes, $paginationParam, 8);
$solicitudesPagina = $pagination['rows'];
$returnTo = app_base_url('index.php') . '?' . http_build_query($_GET);
?>
<?php if (empty($solicitudes)): ?>
  <div class="empty-state"><h2>No hay solicitudes para mostrar</h2><p>Cuando existan registros apareceran aqui.</p></div>
<?php else: ?>
<div class="ugc-table-wrap animate-fade-up">
  <table class="ugc-table">
    <thead>
      <tr>
        <th>ID</th><th>Empleado</th><th>Jefe</th><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($solicitudesPagina as $s): ?>
        <tr>
          <td data-label="ID">#<?= e($s['ID'] ?? '') ?></td>
          <td data-label="Empleado"><?= e($s['NIT_EMPLEADO'] ?? '') ?></td>
          <td data-label="Jefe"><?= e($s['NIT_JEFE'] ?? '') ?></td>
          <td data-label="Tipo"><?= e(TIPOS_SOLICITUD[$s['TIPO_SOLICITUD'] ?? ''] ?? ($s['TIPO_SOLICITUD'] ?? '')) ?></td>
          <td data-label="Inicio"><?= e(substr((string)($s['FECHA_INICIO'] ?? ''), 0, 10)) ?></td>
          <td data-label="Fin"><?= e(substr((string)($s['FECHA_FIN'] ?? ''), 0, 10)) ?></td>
          <td data-label="Estado"><?php $estadoValue = $s['ESTADO'] ?? ''; require __DIR__ . '/badge_estado.php'; ?></td>
          <td data-label="Acciones" class="actions-cell">
            <a class="btn btn-outline btn-sm" href="<?= e(url_view('solicitud_ver') . '&id=' . urlencode((string) ($s['ID'] ?? ''))) ?>">Ver</a>
            <?php if (($user['rol'] ?? '') === ROL_JEFE && ($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE): ?>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_jefe')) ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="id" value="<?= e($s['ID'] ?? '') ?>">
                <input type="hidden" name="decision" value="aprobar">
                <button class="btn btn-green btn-sm" type="submit">Aprobar</button>
              </form>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_jefe')) ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="id" value="<?= e($s['ID'] ?? '') ?>">
                <input type="hidden" name="decision" value="rechazar">
                <button class="btn btn-red btn-sm" type="submit">Rechazar</button>
              </form>
            <?php endif; ?>
            <?php if (in_array(($user['rol'] ?? ''), [ROL_RRHH, ROL_ADMIN], true) && ($s['ESTADO'] ?? '') === ESTADO_APROBADO_JEFE): ?>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_rrhh')) ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="id" value="<?= e($s['ID'] ?? '') ?>">
                <input type="hidden" name="decision" value="aprobar">
                <button class="btn btn-green btn-sm" type="submit">Aprobar RRHH</button>
              </form>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_rrhh')) ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="id" value="<?= e($s['ID'] ?? '') ?>">
                <input type="hidden" name="decision" value="rechazar">
                <button class="btn btn-red btn-sm" type="submit">Rechazar RRHH</button>
              </form>
            <?php endif; ?>
            <?php if (($s['NIT_EMPLEADO'] ?? '') === ($user['cedula'] ?? '') && ($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE): ?>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_delete')) ?>" onsubmit="return confirm('Eliminar solicitud?')">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="id" value="<?= e($s['ID'] ?? '') ?>">
                <button class="btn btn-red btn-sm" type="submit">Eliminar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php ugcRenderPagination($pagination, $paginationLabel); ?>
<?php endif; ?>