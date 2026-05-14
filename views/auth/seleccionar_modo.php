<?php
$logoUrl = app_asset('assets/img/Logo ULGC.png');
$nombreUsuario = e($user['nombre'] ?? 'Usuario');
$esSuperAdmin = $esSuperAdmin ?? false;
$modoAdminValue = $esSuperAdmin ? 'super_usuario' : 'admin';
$modoAdminTitulo = $esSuperAdmin ? 'Modo Super Usuario' : 'Modo Administrador';
$modoAdminDesc = $esSuperAdmin
    ? 'Acceso total administrativo. Gestion de empleados, todas las solicitudes, aprobaciones RRHH y configuracion del sistema.'
    : 'Acceso administrativo. Gestion de empleados, todas las solicitudes y aprobaciones RRHH.';
$modoAdminBadge = $esSuperAdmin ? 'Super Administrador' : 'Administrador';
?>
<div class="login-container">
  <div class="login-bg-pattern"></div>
  <div class="login-card animate-fade-up">
    <div class="login-brand">
      <div class="brand-icon">
        <div class="logo-capibara-wrap">
          <img src="<?= e($logoUrl) ?>" alt="Universidad La Gran Colombia" class="login-logo">
        </div>
      </div>
      <h1 class="brand-title">Bienvenido, <?= $nombreUsuario ?></h1>
      <p class="brand-subtitle">Selecciona tu modo de acceso</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error animate-shake"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url_action('seleccionar_modo')) ?>" id="modoForm" class="login-form">
      <?= csrf_input() ?>
      
      <div class="modo-seleccion">
        <label class="modo-option">
          <input type="radio" name="modo" value="<?= e($modoAdminValue) ?>" required>
          <div class="modo-card">
            <div class="modo-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                <circle cx="12" cy="16" r="1"/>
                <path d="M16 8v-2a2 2 0 0 1 2-2h2"/>
                <circle cx="20" cy="4" r="1"/>
              </svg>
            </div>
            <div class="modo-info">
              <h3><?= e($modoAdminTitulo) ?></h3>
              <p><?= e($modoAdminDesc) ?></p>
              <span class="modo-badge admin"><?= e($modoAdminBadge) ?></span>
            </div>
          </div>
        </label>

        <label class="modo-option">
          <input type="radio" name="modo" value="empleado" required>
          <div class="modo-card">
            <div class="modo-icon empleado">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div class="modo-info">
              <h3>Modo Empleado / Jefe</h3>
              <p>Flujo operativo normal. Crear solicitudes, aprobaciones como jefe inmediato. <strong>Sin acceso a funciones administrativas.</strong></p>
              <span class="modo-badge jefe">Operativo Normal</span>
            </div>
          </div>
        </label>
      </div>

      <button type="submit" class="btn-login" id="btnModo">
        <span class="btn-text">Continuar al Sistema</span>
      </button>
    </form>

    <form method="post" action="<?= e(url_action('logout')) ?>" class="logout-form">
      <?= csrf_input() ?>
      <button type="submit" class="btn-link">Cambiar de usuario</button>
    </form>
  </div>
</div>

<style>
.modo-seleccion {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.modo-option {
  cursor: pointer;
}

.modo-option input {
  position: absolute;
  opacity: 0;
}

.modo-card {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}

.modo-option:hover .modo-card {
  border-color: #d1d5db;
  background: #f9fafb;
}

.modo-option input:checked + .modo-card {
  border-color: #3b82f6;
  background: #eff6ff;
}

.modo-option input:focus + .modo-card {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.modo-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: #dbeafe;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1d4ed8;
}

.modo-icon svg {
  width: 24px;
  height: 24px;
}

.modo-icon.empleado {
  background: #d1fae5;
  color: #059669;
}

.modo-info h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.modo-info p {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 0.75rem 0;
  line-height: 1.5;
}

.modo-info p strong {
  color: #dc2626;
}

.modo-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}

.modo-badge.admin {
  background: #dbeafe;
  color: #1d4ed8;
}

.modo-badge.jefe {
  background: #d1fae5;
  color: #059669;
}

.logout-form {
  text-align: center;
  margin-top: 1rem;
}

.btn-link {
  background: none;
  border: none;
  color: #6b7280;
  font-size: 0.875rem;
  cursor: pointer;
  text-decoration: underline;
  padding: 0.5rem;
}

.btn-link:hover {
  color: #374151;
}
</style>

<script>
document.getElementById('modoForm').addEventListener('submit', function() {
  const btn = document.getElementById('btnModo');
  btn.classList.add('loading');
  btn.querySelector('.btn-text').textContent = 'Ingresando...';
});
</script>
