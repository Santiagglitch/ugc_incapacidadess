<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $configured ? 'Ingresar' : 'Configurar acceso' ?> · Control de Incapacidades</title>
  <link rel="stylesheet" href="<?= control_e(control_asset('style.css')) ?>">
</head>
<body class="cp-login-body">
  <div class="cp-grid" aria-hidden="true"></div>
  <main class="cp-login-shell">
    <section class="cp-card cp-login-card">
      <div class="cp-orbit" aria-hidden="true"><span></span></div>
      <p class="cp-kicker">UNIVERSIDAD LA GRAN COLOMBIA</p>
      <h1>Control de Incapacidades</h1>
      <p class="cp-muted"><?= $configured ? 'Ingresa para administrar las funcionalidades del portal.' : 'Crea el acceso al panel desde esta pagina.' ?></p>
      <?php if ($error !== null): ?><div class="cp-alert cp-alert-error" role="alert"><?= control_e($error) ?></div><?php endif; ?>
      <?php if (!$configured && !$setupAvailable): ?>
        <p class="cp-muted">Sube la llave de instalacion proporcionada con esta version a:</p>
        <code class="cp-command">storage/private/control-panel/instalacion.key</code>
        <p class="cp-muted">Despues recarga esta pagina para crear el usuario y la contrasena del panel.</p>
      <?php else: ?>
        <form method="post" action="index.php" class="cp-form">
          <input type="hidden" name="control_action" value="<?= $configured ? 'login' : 'setup' ?>">
          <input type="hidden" name="csrf_token" value="<?= control_e($csrfToken) ?>">
          <?php if (!$configured): ?>
            <label for="setup_key">Llave de instalacion</label>
            <input id="setup_key" type="password" name="setup_key" autocomplete="off" required>
          <?php endif; ?>
          <label for="username">Usuario del panel</label>
          <input id="username" name="username" value="<?= control_e($username) ?>" autocomplete="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9_.@-]+" required>
          <label for="password">Contrasena<?= $configured ? '' : ' (minimo 12 caracteres)' ?></label>
          <input id="password" type="password" name="password" autocomplete="<?= $configured ? 'current-password' : 'new-password' ?>" <?= $configured ? '' : 'minlength="12" maxlength="72"' ?> required>
          <?php if (!$configured): ?>
            <label for="password_confirmation">Repite la contrasena</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required>
          <?php endif; ?>
          <button class="cp-button" type="submit"><?= $configured ? 'Ingresar al control' : 'Crear acceso al panel' ?></button>
        </form>
      <?php endif; ?>
      <p class="cp-secure">Los interruptores se administran desde este panel.</p>
    </section>
  </main>
</body>
</html>
