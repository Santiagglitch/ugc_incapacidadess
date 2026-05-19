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
          <img src="<?= e($logoUrl) ?>" alt="Universidad La Gran Colombia" class="login-logo" draggable="false">
          <img src="<?= e($capibaraUrl) ?>" alt="Mascota" class="login-capibara" draggable="false">
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
          <svg class="input-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>

          <input 
            type="text" 
            id="cedula" 
            name="cedula" 
            class="input-field" 
            placeholder="Documento" 
            required 
            autocomplete="username"
          >
        </div>
      </div>

      <div class="input-group">
        <label class="input-label" for="password">Contrasena</label>

        <div class="input-wrap">
          <svg class="input-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <circle cx="12" cy="16" r="1"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>

          <input 
            type="password" 
            id="password" 
            name="password" 
            class="input-field" 
            placeholder="Tu contrasena" 
            required 
            autocomplete="current-password"
          >

          <button type="button" class="input-toggle" data-password-toggle aria-label="Mostrar contrasena">
            <!-- OJO CERRADO -->
            <svg class="toggle-icon eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a20.29 20.29 0 0 1 5.06-5.94"/>
              <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a20.26 20.26 0 0 1-3.17 4.41"/>
              <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>

            <!-- OJO ABIERTO -->
            <svg class="toggle-icon eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
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
            <button type="button" class="dev-chip" data-login-cedula="<?= e($ced) ?>">
              <span class="chip-role <?= e($u['rol']) ?>"><?= e(strtoupper(substr($u['rol'], 0, 3))) ?></span>
              <span class="chip-name"><?= e(explode(' ', $u['nombre'])[0]) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
  var passwordInput = document.getElementById('password');
  var toggleButton = document.querySelector('[data-password-toggle]');
  var loginButton = document.getElementById('btnLogin');
  var loginForm = document.getElementById('loginForm');

  if (toggleButton && passwordInput) {
    toggleButton.addEventListener('click', function () {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.classList.add('is-visible');
        toggleButton.setAttribute('aria-label', 'Ocultar contrasena');
      } else {
        passwordInput.type = 'password';
        toggleButton.classList.remove('is-visible');
        toggleButton.setAttribute('aria-label', 'Mostrar contrasena');
      }
    });
  }

  document.querySelectorAll('[data-login-cedula]').forEach(function (button) {
    button.addEventListener('click', function () {
      var cedulaInput = document.getElementById('cedula');

      if (cedulaInput) {
        cedulaInput.value = button.dataset.loginCedula || '';
      }
      if (passwordInput) {
        passwordInput.value = 'prueba123';
      }
      if (loginButton) {
        loginButton.focus();
      }
    });
  });

  document.querySelectorAll('.login-brand img').forEach(function (img) {
    img.addEventListener('dragstart', function (event) {
      event.preventDefault();
    });
  });

  document.querySelectorAll('#cedula, #password').forEach(function (input) {
    input.addEventListener('drop', function (event) {
      event.preventDefault();
    });
  });

  if (loginForm && loginButton) {
    loginForm.addEventListener('submit', function () {
      loginButton.classList.add('loading');
      loginButton.querySelector('.btn-text').textContent = 'Ingresando...';
    });
  }
})();
</script>
