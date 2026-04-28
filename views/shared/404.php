<div class="login-container">
  <div class="login-card animate-fade-up">
    <h1 class="brand-title">404</h1>
    <p class="brand-subtitle">Pagina no encontrada</p>
    <p class="muted">La ruta solicitada no existe: <?= e($view ?? '') ?></p>
    <p style="margin-top:18px"><a class="btn btn-green" href="<?= e(url_view('login')) ?>">Volver al inicio</a></p>
  </div>
</div>