<?php
$cssUrl = app_asset('assets/css/ugc.css');
$loginBg = app_asset('assets/img/login-fondo.png');
$faviconUrl = app_asset('assets/img/favicon.png');
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= e(APP_NAME) ?> - Acceso</title>
  <link rel="icon" type="image/png" href="<?= e($faviconUrl) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= e($faviconUrl) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e($cssUrl) ?>">
  <style>:root{--ugc-login-bg-image:url("<?= e($loginBg) ?>");}</style>
</head>
<body>
<?= $content ?>
<script>
(function () {
  document.addEventListener('click', function (event) {
    var link = event.target.closest('a[data-click-once], a.stat-card-link, a.logo-link, a.btn');

    if (
      !link
      || link.target === '_blank'
      || link.hasAttribute('download')
      || (link.href || '').indexOf('download=1') !== -1
    ) {
      return;
    }

    if (link.dataset.clicked === 'true') {
      event.preventDefault();
      return;
    }

    link.dataset.clicked = 'true';
    link.classList.add('is-disabled');
    link.setAttribute('aria-disabled', 'true');
  });

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (event.defaultPrevented) {
        return;
      }

      if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
      }

      form.dataset.submitting = 'true';

      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
        button.disabled = true;
        button.classList.add('is-disabled');
      });
    });
  });
})();
</script>
</body>
</html>