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
  window.addEventListener('pageshow', function (event) {
    if (!event.persisted) {
      return;
    }

    document.querySelectorAll('[data-clicked="true"]').forEach(function (link) {
      link.dataset.clicked = 'false';
      link.classList.remove('is-disabled');
      link.removeAttribute('aria-disabled');
    });

    document.querySelectorAll('form[data-submitting="true"]').forEach(function (form) {
      form.dataset.submitting = 'false';
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
        button.disabled = false;
        button.classList.remove('is-disabled');
      });
    });
  });
})();

(function () {
  var nonDraggableSelector = [
    'a',
    'button',
    'img',
    'input[type="button"]',
    'input[type="submit"]',
    'input[type="reset"]',
    '[role="button"]',
    '.btn',
    '.btn-login',
    '.btn-link',
    '.stat-card',
    '.stat-card-link',
    '.dev-chip'
  ].join(',');

  function closest(target, selector) {
    return target && target.closest ? target.closest(selector) : null;
  }

  function isProtectedDropTarget(target) {
    var field = closest(target, 'input, textarea, [contenteditable="true"]');

    return !!field && !field.matches('input[type="file"]');
  }

  document.querySelectorAll(nonDraggableSelector).forEach(function (element) {
    element.setAttribute('draggable', 'false');
  });

  document.addEventListener('dragstart', function (event) {
    if (closest(event.target, nonDraggableSelector)) {
      event.preventDefault();
    }
  });

  document.addEventListener('drop', function (event) {
    if (isProtectedDropTarget(event.target)) {
      event.preventDefault();
    }
  });
})();

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

    try {
      var linkUrl = new URL(link.href, window.location.href);
      if (linkUrl.pathname === window.location.pathname && linkUrl.search === window.location.search && linkUrl.hash) {
        return;
      }
    } catch (error) {
      // Continua con la proteccion normal si el navegador no puede leer la URL.
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
