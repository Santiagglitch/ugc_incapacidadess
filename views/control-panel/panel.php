<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Control de Incapacidades</title>
  <link rel="stylesheet" href="<?= control_e(control_asset('style.css')) ?>">
  <script src="<?= control_e(control_asset('control.js')) ?>" defer></script>
</head>
<body>
  <div class="cp-grid" aria-hidden="true"></div>
  <header class="cp-header">
    <div><p class="cp-kicker">UNIVERSIDAD LA GRAN COLOMBIA</p><h1>Control de Incapacidades</h1></div>
    <form method="post" action="index.php">
      <input type="hidden" name="control_action" value="logout">
      <input type="hidden" name="csrf_token" value="<?= control_e($csrfToken) ?>">
      <button class="cp-button cp-button-ghost" type="submit">Cerrar sesion</button>
    </form>
  </header>
  <main class="cp-main">
    <?php if ($success !== null): ?><div class="cp-alert cp-alert-success" role="status"><?= control_e($success) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="cp-alert cp-alert-error" role="alert"><?= control_e($error) ?></div><?php endif; ?>
    <section class="cp-summary" aria-label="Estado del panel">
      <article><span>Controles disponibles</span><strong><?= count(controlIncapacidadesService::CONTROLES) ?></strong></article>
      <article><span>Administracion</span><strong class="cp-ok">Desde este panel</strong></article>
      <article><span>Usuario</span><strong><?= control_e($_SESSION['control_user']) ?></strong></article>
    </section>
    <section class="cp-card">
      <div class="cp-section-head"><h2>Funcionalidades del portal</h2><span class="cp-live">Los cambios se aplican en la siguiente peticion</span></div>
      <form method="post" action="index.php" class="cp-control-form" data-control-form>
        <input type="hidden" name="control_action" value="update">
        <input type="hidden" name="csrf_token" value="<?= control_e($csrfToken) ?>">
        <input type="hidden" name="revision" value="<?= (int)$state['revision'] ?>">
        <div class="cp-switch-grid">
          <?php foreach (controlIncapacidadesService::CONTROLES as $key => $control): ?>
            <label class="cp-switch-card" for="flag-<?= control_e($key) ?>">
              <span class="cp-switch-copy"><strong><?= control_e($control['label']) ?></strong><small><?= control_e($control['description']) ?></small></span>
              <span class="cp-switch">
                <input type="hidden" name="flags[<?= control_e($key) ?>]" value="0">
                <input id="flag-<?= control_e($key) ?>" type="checkbox" name="flags[<?= control_e($key) ?>]" value="1" data-original="<?= $state['flags'][$key] ? '1' : '0' ?>" <?= $state['flags'][$key] ? 'checked' : '' ?>>
                <span aria-hidden="true"></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="cp-reauth is-visible" data-reauth>
          <strong>Confirmar cambios</strong>
          <small>Revisa los interruptores e ingresa tu contrasena para guardar.</small>
          <label class="cp-confirm"><input type="checkbox" name="confirm_critical" value="1" data-confirm> Confirmo los cambios de funcionalidades</label>
          <div class="cp-reauth-fields"><label for="reauth_password">Contrasena del panel<input id="reauth_password" type="password" name="reauth_password" autocomplete="current-password" data-reauth-password></label></div>
        </div>
        <div class="cp-actions"><span data-change-status role="status" aria-live="polite">Revisa los controles antes de guardar</span><button class="cp-button" type="submit">Guardar cambios</button></div>
        <noscript><p class="cp-muted">Confirma los cambios e ingresa tu contrasena para guardar.</p></noscript>
      </form>
    </section>
    <?php if ($recentChanges): ?>
      <section class="cp-card cp-history">
        <h2>Ultimos cambios</h2>
        <div class="cp-table-wrap"><table><thead><tr><th>Fecha (Bogota)</th><th>Usuario</th><th>Control</th><th>Estado</th></tr></thead><tbody>
          <?php foreach ($recentChanges as $change): ?>
            <tr><td><?= control_e((new DateTimeImmutable($change['time']))->setTimezone(new DateTimeZone('America/Bogota'))->format('d/m/Y H:i')) ?></td><td><?= control_e($change['user']) ?></td><td><?= control_e(controlIncapacidadesService::CONTROLES[$change['key']]['label'] ?? $change['key']) ?></td><td><?= $change['new'] ? 'Encendido' : 'Apagado' ?></td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
