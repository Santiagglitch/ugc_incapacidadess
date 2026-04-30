<?php
$cssUrl = app_asset('assets/css/ugc.css');
$logoUrl = app_asset('assets/img/Logo ULGC.png');

$rolesLabel = [
  ROL_ADMIN => 'Administrador',
  ROL_RRHH => 'Talento Humano',
  ROL_JEFE => 'Jefe Inmediato',
  ROL_EMPLEADO => 'Empleado'
];

$rolLabel = $rolesLabel[$user['rol'] ?? ''] ?? 'Usuario';

$notifCountUrl = url_view('notif_count');
$notifListUrl = url_view('notif_list');
$notifReadUrl = url_action('notif_read');
$notifReadAllUrl = url_action('notif_read_all');
$solicitudDetalleUrl = url_view('solicitud_ver');

// Sonido de notificación
$notifSoundUrl = app_asset('assets/sounds/notificacion.mp3');

$csrfToken = csrf_token();
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= e(APP_NAME) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e($cssUrl) ?>">
</head>

<body>

<header class="ugc-header">
  <a href="<?= e(url_view('dashboard')) ?>" class="logo-link" title="Inicio">
    <img src="<?= e($logoUrl) ?>" alt="Universidad La Gran Colombia" class="header-logo" height="50">
  </a>

  <nav>
    <a href="<?= e(url_view('dashboard')) ?>">Inicio</a>

    <?php if (($user['rol'] ?? '') === ROL_ADMIN): ?>
      <a href="<?= e(url_view('admin_empleados')) ?>">Empleados</a>
    <?php endif; ?>
  </nav>

  <div class="notificacion-wrap" data-notificaciones>
    <button class="notificacion-bell" type="button" data-notificacion-toggle aria-label="Notificaciones" title="Notificaciones">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M15 17H9m10-2c-1.2-1.1-2-2.2-2-5a5 5 0 0 0-10 0c0 2.8-.8 3.9-2 5h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10 19a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>

      <span class="notificacion-badge" data-notificacion-count data-count="0"></span>
    </button>

    <div class="notificacion-dropdown" data-notificacion-dropdown>
      <div class="notificacion-header">
        <h4>Notificaciones</h4>
        <button type="button" class="mark-all" data-notificacion-read-all>Marcar todas</button>
      </div>

      <div class="notificacion-list" data-notificacion-list>
        <div class="notificacion-empty">
          <p>Cargando...</p>
        </div>
      </div>
    </div>
  </div>

  <div class="user-chip">
    <?= e($user['nombre'] ?? '') ?>
    <span class="user-role"><?= e($rolLabel) ?></span>
  </div>

  <form action="<?= e(url_action('logout')) ?>" method="post">
    <?= csrf_input() ?>
    <button class="logout" type="submit">Salir</button>
  </form>
</header>

<main class="ugc-wrap">
  <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= $flash['type'] === 'success' ? 'ok' : 'err' ?> animate-fade-down">
      <?= e($flash['message']) ?>
    </div>
  <?php endif; ?>

  <?= $content ?>
</main>

<footer class="ugc-footer">
  <div class="footer-content">
    <div class="footer-brand">
      <span class="footer-logo">UGC</span>
      <span class="footer-name">Universidad La Gran Colombia</span>
    </div>

    <div class="footer-links">
      <a href="<?= e(url_view('dashboard')) ?>">Inicio</a>

      <form action="<?= e(url_action('logout')) ?>" method="post" class="footer-logout-form">
        <?= csrf_input() ?>
        <button type="submit" class="footer-logout-btn">Cerrar Sesion</button>
      </form>
    </div>

    <div class="footer-copy">
      &copy; <?= date('Y') ?> Sistema de Solicitudes. Todos los derechos reservados.
    </div>
  </div>
</footer>

<script>
(function () {
  var root = document.querySelector('[data-notificaciones]');

  if (!root || !window.fetch) {
    return;
  }

  var urls = {
    count: '<?= e($notifCountUrl) ?>',
    list: '<?= e($notifListUrl) ?>',
    read: '<?= e($notifReadUrl) ?>',
    readAll: '<?= e($notifReadAllUrl) ?>',
    detail: '<?= e($solicitudDetalleUrl) ?>',
    sound: '<?= e($notifSoundUrl) ?>'
  };

  var csrf = '<?= e($csrfToken) ?>';

  var toggle = root.querySelector('[data-notificacion-toggle]');
  var dropdown = root.querySelector('[data-notificacion-dropdown]');
  var badge = root.querySelector('[data-notificacion-count]');
  var list = root.querySelector('[data-notificacion-list]');
  var readAll = root.querySelector('[data-notificacion-read-all]');

  var notificationAudio = null;
  var lastNotifCount = null;
  var firstCountLoad = true;
  var pendingSound = false;

  function getNotificationAudio() {
    if (!notificationAudio) {
      notificationAudio = new Audio(urls.sound);
      notificationAudio.preload = 'auto';
      notificationAudio.volume = 0.8;
    }

    return notificationAudio;
  }

  function playNotificationSound() {
    var audio = getNotificationAudio();

    try {
      audio.pause();
      audio.currentTime = 0;

      var playPromise = audio.play();

      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(function () {
          pendingSound = true;
        });
      }
    } catch (error) {
      pendingSound = true;
    }
  }

  function unlockNotificationSound() {
    var audio = getNotificationAudio();

    try {
      var wasMuted = audio.muted;
      audio.muted = true;

      var playPromise = audio.play();

      var finishUnlock = function () {
        audio.pause();
        audio.currentTime = 0;
        audio.muted = wasMuted;

        if (pendingSound) {
          pendingSound = false;
          playNotificationSound();
        }
      };

      if (playPromise && typeof playPromise.then === 'function') {
        playPromise.then(finishUnlock).catch(function () {
          audio.muted = wasMuted;
        });
      } else {
        finishUnlock();
      }
    } catch (error) {
      // Si el navegador bloquea el audio, no se rompe la campana.
    }
  }

  document.addEventListener('click', unlockNotificationSound, { once: true });
  document.addEventListener('keydown', unlockNotificationSound, { once: true });

  function post(url, data) {
    var body = new FormData();

    Object.keys(data || {}).forEach(function (key) {
      body.append(key, data[key]);
    });

    body.append('_csrf_token', csrf);

    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-Token': csrf
      },
      body: body
    }).then(function (res) {
      return res.json();
    });
  }

  function setCount(total) {
    total = parseInt(total || 0, 10);

    badge.dataset.count = String(total);
    badge.textContent = total > 99 ? '99+' : (total > 0 ? String(total) : '');

    // Se mantiene el movimiento existente de la campana.
    toggle.classList.toggle('has-new', total > 0);
  }

  function handleCount(total) {
    total = parseInt(total || 0, 10);

    var shouldPlaySound = false;

    // Suena si al entrar ya tiene notificaciones atrasadas.
    if (firstCountLoad && total > 0) {
      shouldPlaySound = true;
    }

    // Suena cuando llegan nuevas notificaciones.
    if (!firstCountLoad && lastNotifCount !== null && total > lastNotifCount) {
      shouldPlaySound = true;
    }

    setCount(total);

    if (shouldPlaySound) {
      playNotificationSound();
    }

    lastNotifCount = total;
    firstCountLoad = false;
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  function iconClass(tipo) {
    tipo = String(tipo || '').toLowerCase();

    if (tipo.indexOf('rechazada') !== -1 || tipo.indexOf('rechazo') !== -1) {
      return 'rechazada';
    }

    if (tipo.indexOf('aprobada') !== -1 || tipo.indexOf('aprobacion') !== -1) {
      return 'aprobada';
    }

    if (tipo.indexOf('revision') !== -1) {
      return 'revision';
    }

    return 'nueva';
  }

  function drawList(items) {
    if (!items || !items.length) {
      list.innerHTML = '<div class="notificacion-empty"><p>No tienes notificaciones pendientes.</p></div>';
      return;
    }

    list.innerHTML = items.map(function (item) {
      var id = escapeHtml(item.ID);
      var solicitud = escapeHtml(item.ID_SOLICITUD);
      var cls = iconClass(item.TIPO);

      return '<button type="button" class="notificacion-item unread" data-id="' + id + '" data-solicitud="' + solicitud + '">' +
        '<span class="notificacion-icon ' + cls + '"></span>' +
        '<span class="notificacion-content">' +
          '<p>' + escapeHtml(item.MENSAJE) + '</p>' +
          '<span class="notificacion-time">' + escapeHtml(item.FECHA_CREACION) + '</span>' +
        '</span>' +
      '</button>';
    }).join('');
  }

  function loadCount() {
    fetch(urls.count, {
      credentials: 'same-origin'
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        handleCount(data.contador || 0);
      })
      .catch(function () {
        setCount(0);
      });
  }

  function loadList() {
    fetch(urls.list, {
      credentials: 'same-origin'
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        drawList(data.notificaciones || []);
      })
      .catch(function () {
        list.innerHTML = '<div class="notificacion-empty"><p>No se pudieron cargar las notificaciones.</p></div>';
      });
  }

  toggle.addEventListener('click', function () {
    dropdown.classList.toggle('active');

    if (dropdown.classList.contains('active')) {
      loadList();
    }
  });

  document.addEventListener('click', function (event) {
    if (!root.contains(event.target)) {
      dropdown.classList.remove('active');
    }
  });

  list.addEventListener('click', function (event) {
    var item = event.target.closest('[data-id]');

    if (!item) {
      return;
    }

    post(urls.read, {
      id: item.dataset.id
    }).then(function () {
      window.location.href = urls.detail + '&id=' + encodeURIComponent(item.dataset.solicitud);
    });
  });

  readAll.addEventListener('click', function () {
    post(urls.readAll, {}).then(function () {
      lastNotifCount = 0;
      firstCountLoad = false;
      pendingSound = false;

      setCount(0);
      loadList();
    });
  });

  loadCount();

  window.setInterval(loadCount, 60000);
})();
</script>

</body>
</html>