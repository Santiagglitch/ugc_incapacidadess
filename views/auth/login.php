<?php
$logoUrl = app_asset('assets/img/Logo ULGC.png');
$capibaraUrl = app_asset('assets/img/capibara-login.png');
?>
<div class="login-container">
  <div class="login-bg-pattern"></div>
  <div class="login-card animate-fade-up">
    <div class="login-brand">
      <div class="brand-icon">
        <div class="logo-capibara-wrap">
          <img src="<?= e($logoUrl) ?>" alt="Universidad La Gran Colombia" class="login-logo">
          <img src="<?= e($capibaraUrl) ?>" alt="Mascota" class="login-capibara">
        </div>
      </div>
      <h1 class="brand-title">Portal de Solicitudes</h1>
      <p class="brand-subtitle">Permisos e Incapacidades</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error animate-shake"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url_action('login')) ?>" id="loginForm" class="login-form">
      <?= csrf_input() ?>
      <div class="input-group">
        <label class="input-label" for="cedula">Numero de Documento</label>
        <div class="input-wrap">
          <span class="input-icon">&#128100;</span>
          <input type="text" id="cedula" name="cedula" class="input-field" placeholder="Ej: 11111111" required autocomplete="username">
        </div>
      </div>

      <div class="input-group">
        <label class="input-label" for="password">Contrasena</label>
        <div class="input-wrap">
          <span class="input-icon">&#128274;</span>
          <input type="password" id="password" name="password" class="input-field" placeholder="Tu contrasena" required autocomplete="current-password">
          <button type="button" class="input-toggle" onclick="togglePassword()" aria-label="Mostrar contrasena">Ver</button>
        </div>
      </div>

      <button type="submit" class="btn-login" id="btnLogin">
        <span class="btn-text">Ingresar al Sistema</span>
      </button>
    </form>

    <?php if (!empty($devUsuarios)): ?>
      <div class="dev-section">
        <p class="dev-title">Usuarios de Prueba <span class="dev-hint">pass: prueba123</span></p>
        <div class="dev-chips">
          <?php foreach ($devUsuarios as $ced => $u): ?>
            <button type="button" class="dev-chip" onclick="fillLogin('<?= e($ced) ?>')">
              <span class="chip-role <?= e($u['rol']) ?>"><?= e(strtoupper(substr($u['rol'], 0, 3))) ?></span>
              <span class="chip-name"><?= e(explode(' ', $u['nombre'])[0]) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function togglePassword() {
  const pwd = document.getElementById('password');
  pwd.type = pwd.type === 'password' ? 'text' : 'password';
}
function fillLogin(cedula) {
  document.getElementById('cedula').value = cedula;
  document.getElementById('password').value = 'prueba123';
  document.getElementById('btnLogin').focus();
}
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('btnLogin');
  btn.classList.add('loading');
  btn.querySelector('.btn-text').textContent = 'Ingresando...';
});
</script>