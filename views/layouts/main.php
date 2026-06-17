<?php
/**
 * Layout principal del sistema
 * 
 * Variables recibidas del scope externo:
 * @var string $content Contenido HTML de la vista principal
 * @var array $user Datos del usuario autenticado
 * @var array|null $flash Mensaje flash de notificacion
 */

$cssUrl = app_asset('assets/css/ugc.css');
$logoUrl = app_asset('assets/img/Logo ULGC.png');
$faviconUrl = app_asset('assets/img/favicon.png');

$rolesLabel = [
  ROL_ADMIN => 'Administrador',
  ROL_RRHH => 'Talento Humano',
  ROL_JEFE => 'Jefe Inmediato',
  ROL_EMPLEADO => 'Empleado'
];

$rolLabel = $rolesLabel[$user['rol'] ?? ''] ?? 'Usuario';

// Variables para el modo Admin (Super Admin, Admin Adicional o TH fijo)
$esSuperAdmin        = es_super_admin_nit($user['cedula'] ?? '');
$esTalentoHumanoFijo = in_array($user['cedula'] ?? '', NITS_TALENTO_HUMANO, true);
$tieneSelectorModo   = usuario_tiene_selector_modo();
$modoAdminKey = $esSuperAdmin ? 'super_usuario' : 'admin';
$modoActual   = $_SESSION['admin_modo'] ?? $modoAdminKey;
if ($modoActual === 'super_usuario' || $modoActual === 'admin') {
    if ($esSuperAdmin) {
        $modoLabel = 'Super Usuario';
    } elseif ($esTalentoHumanoFijo) {
        $modoLabel = 'Talento Humano';
    } else {
        $modoLabel = 'Administrador';
    }
} else {
    // En modo empleado usar el rol real en sesión para el label
    $rolEnSesion = $_SESSION['usuario']['rol'] ?? '';
    if ($rolEnSesion === ROL_JEFE) {
        $modoLabel = 'Jefe Inmediato';
    } elseif ($rolEnSesion === ROL_EMPLEADO) {
        $modoLabel = 'Solicitante';
    } else {
        $modoLabel = 'Modo Empleado';
    }
}

$notifCountUrl = url_view('notif_count');
$notifListUrl = url_view('notif_list');
$notifReadUrl = url_action('notif_read');
$notifReadAllUrl = url_action('notif_read_all');
$solicitudDetalleUrl = url_view('solicitud_ver');
$headerQ = limpiar_texto($_GET['q'] ?? '');
$headerTipo = limpiar_texto($_GET['tipo_solicitud'] ?? '');
$headerHistorial = limpiar_texto($_GET['historial'] ?? '');

// Sonido de notificación
$notifSoundUrl = app_asset('assets/sounds/notificacion.mp3');
$notifPlayOnLogin = !empty($_SESSION['notificacion_sonar_login']);
unset($_SESSION['notificacion_sonar_login']);
$notifStateKey = 'ugc:notificaciones:count:' . (string)($user['cedula'] ?? '');
$notifStateKeyJson = json_encode($notifStateKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

$csrfToken = csrf_token();
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e($faviconUrl) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= e($faviconUrl) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e($cssUrl) ?>">
  <style>
    /* Estilos para el botón Cambiar Modo - Super Admin */
    .user-modo-badge {
      display: inline-block;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      margin-left: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      border: 1px solid rgba(255,255,255,0.3);
    }
    .user-modo-badge.modo-admin {
      background: #ffd700;
      color: #0a5a1f;
      box-shadow: 0 2px 8px rgba(255,215,0,0.4);
    }
    .user-modo-badge.modo-empleado {
      background: #e8f5ec;
      color: #0a5a1f;
      box-shadow: 0 2px 8px rgba(232,245,236,0.4);
    }
    .header-cambiar-modo-form {
      display: inline-flex;
    }
    .btn-cambiar-modo {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 0.875rem;
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 0.5rem;
      font-size: 0.8125rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }
    .btn-cambiar-modo svg {
      width: 16px;
      height: 16px;
    }
    /* Modo Super Usuario - Amarillo dorado que resalta sobre verde */
    .btn-cambiar-modo.btn-modo-admin {
      background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
      color: #0a5a1f;
      box-shadow: 0 4px 12px rgba(255,215,0,0.3);
    }
    .btn-cambiar-modo.btn-modo-admin:hover {
      background: linear-gradient(135deg, #ffed4a 0%, #ffd700 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(255,215,0,0.5);
    }
    /* Modo Empleado - Blanco/verde claro */
    .btn-cambiar-modo.btn-modo-empleado {
      background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, #e8f5ec 100%);
      color: #0a5a1f;
      box-shadow: 0 4px 12px rgba(255,255,255,0.2);
    }
    .btn-cambiar-modo.btn-modo-empleado:hover {
      background: linear-gradient(135deg, #ffffff 0%, rgba(255,255,255,0.95) 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(255,255,255,0.35);
    }
    .btn-cambiar-modo:active {
      transform: translateY(0);
    }
    .btn-cambiar-modo:disabled,
    .btn-cambiar-modo.is-disabled {
      opacity: 0.62;
      cursor: not-allowed;
      pointer-events: none;
      transform: none;
      box-shadow: none;
    }
    @media (max-width: 768px) {
      .btn-cambiar-modo span {
        display: none;
      }
      .btn-cambiar-modo {
        padding: 0.5rem;
      }
    }
  </style>
</head>

<body>

<header class="ugc-header">
  <div class="header-left">
    <a href="<?= e(url_view('dashboard')) ?>" class="logo-link" title="Inicio" data-click-once>
      <img src="<?= e($logoUrl) ?>" alt="Universidad La Gran Colombia" class="header-logo" height="50">
    </a>
  </div>

  <form class="header-search" method="get" action="<?= e(app_base_url('index.php')) ?>">
    <input type="hidden" name="view" value="dashboard">
    <?php if ($headerHistorial !== ''): ?>
      <input type="hidden" name="historial" value="<?= e($headerHistorial) ?>">
    <?php endif; ?>

    <span class="header-search-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="m20 20-3.5-3.5"></path>
      </svg>
    </span>

    <input
      type="search"
      name="q"
      value="<?= e($headerQ) ?>"
      placeholder="Buscar empleado, NIT o tipo..."
      autocomplete="off"
    >

    <select name="tipo_solicitud" aria-label="Tipo de solicitud">
      <option value="">Todos</option>
      <?php foreach (TIPOS_SOLICITUD as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $headerTipo === (string)$key ? 'selected' : '' ?>>
          <?= e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit">Buscar</button>
  </form>

  <div class="header-actions">
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
      <?php if ($tieneSelectorModo): ?>
        <span class="user-modo-badge <?= ($modoActual === 'super_usuario' || $modoActual === 'admin') ? 'modo-admin' : 'modo-empleado' ?>"><?= e($modoLabel) ?></span>
      <?php endif; ?>
    </div>

    

    <?php if ($tieneSelectorModo): ?>
    <form action="<?= e(url_action('cambiar_modo')) ?>" method="post" class="header-cambiar-modo-form">
      <?= csrf_input() ?>
      <?php
        $esModoAdmin = ($modoActual === 'super_usuario' || $modoActual === 'admin');
        $modoDestino = $esModoAdmin ? 'empleado' : $modoAdminKey;
        $tituloDestino = $esModoAdmin
            ? ($esTalentoHumanoFijo ? 'Jefe / Solicitante' : 'Modo Empleado')
            : ($esSuperAdmin ? 'Super Usuario' : ($esTalentoHumanoFijo ? 'Talento Humano' : 'Administrador'));
        $btnClase = $esModoAdmin ? 'btn-modo-admin' : 'btn-modo-empleado';
      ?>
      <input type="hidden" name="modo_destino" value="<?= e($modoDestino) ?>">
      <button class="btn-cambiar-modo <?= $btnClase ?>" type="submit" title="Cambiar a <?= e($tituloDestino) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M16 3h3v3h-3zM8 3h3v3H8zM5 8h14v12H5z"/>
          <path d="M12 12v6M8 15h8"/>
        </svg>
        <span>Cambiar Modo</span>
      </button>
    </form>
    <?php endif; ?>

    <form action="<?= e(url_action('logout')) ?>" method="post" class="header-logout-form">
      <?= csrf_input() ?>
      <button class="logout" type="submit">Salir</button>
    </form>
  </div>
</header>

<main class="ugc-wrap">
  <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= $flash['type'] === 'success' ? 'ok' : 'err' ?> animate-fade-down" data-flash-message>
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
      <a href="<?= e(url_view('dashboard')) ?>" data-click-once>Inicio</a>

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

<script nonce="<?= csp_nonce() ?>">
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
  var flashes = document.querySelectorAll('[data-flash-message]');

  if (!flashes.length) {
    return;
  }

  window.setTimeout(function () {
    flashes.forEach(function (flash) {
      flash.classList.add('flash-exit');

      window.setTimeout(function () {
        if (flash.parentNode) {
          flash.parentNode.removeChild(flash);
        }
      }, 400);
    });
  }, 3000);
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
    '.btn-cambiar-modo',
    '.logout',
    '.stat-card',
    '.stat-card-link',
    '.notificacion-item',
    '.mark-all',
    '.ugc-pagination__page',
    '.ugc-pagination__step'
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
    var submitter = event.target.closest('button[type="submit"], input[type="submit"]');

    if (submitter && submitter.form) {
      submitter.form._ugcSubmitter = submitter;
    }

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

      var submitter = event.submitter || form._ugcSubmitter;

      if (submitter && submitter.name) {
        var hiddenSubmitter = document.createElement('input');
        hiddenSubmitter.type = 'hidden';
        hiddenSubmitter.name = submitter.name;
        hiddenSubmitter.value = submitter.value;
        form.appendChild(hiddenSubmitter);
      }

      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
        button.disabled = true;
        button.classList.add('is-disabled');
      });
    });
  });
})();

(function () {
  var form = document.querySelector('.header-search');

  if (!form) {
    return;
  }

  var input = form.querySelector('input[name="q"]');
  var tipo = form.querySelector('select[name="tipo_solicitud"]');

  if (!input || !tipo) {
    return;
  }

  input.addEventListener('input', function () {
    if (input.value.trim() !== '') {
      tipo.value = '';
    }
  });

  tipo.addEventListener('change', function () {
    if (tipo.value !== '') {
      input.value = '';
    }
  });

  form.addEventListener('submit', function () {
    if (input.value.trim() !== '') {
      tipo.value = '';
    }
  });
})();

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

  var notificationStateKey = <?= $notifStateKeyJson ?: "''" ?>;
  var shouldPlayLoginSound = <?= $notifPlayOnLogin ? 'true' : 'false' ?>;
  var notificationAudio = null;
  var lastNotifCount = readStoredNotifCount();
  var firstCountLoad = true;

  function readStoredNotifCount() {
    try {
      if (!window.sessionStorage) {
        return null;
      }

      var value = window.sessionStorage.getItem(notificationStateKey);
      if (value === null || value === '') {
        return null;
      }

      value = parseInt(value, 10);
      return isNaN(value) ? null : value;
    } catch (error) {
      return null;
    }
  }

  function writeStoredNotifCount(total) {
    try {
      if (window.sessionStorage) {
        window.sessionStorage.setItem(notificationStateKey, String(total));
      }
    } catch (error) {
      // Sin storage disponible, el contador en memoria sigue funcionando.
    }
  }

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
        playPromise.catch(function () {});
      }
    } catch (error) {
      // El navegador puede bloquear audio automatico; no se reintenta en el siguiente clic.
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

    if (firstCountLoad) {
      shouldPlaySound = (shouldPlayLoginSound && total > 0)
        || (lastNotifCount !== null && total > lastNotifCount);
      shouldPlayLoginSound = false;
    } else if (lastNotifCount !== null && total > lastNotifCount) {
      // Suena cuando llegan nuevas notificaciones.
      shouldPlaySound = true;
    }

    setCount(total);

    if (shouldPlaySound) {
      playNotificationSound();
    }

    lastNotifCount = total;
    firstCountLoad = false;
    writeStoredNotifCount(total);
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

  function formatFechaCo(valor) {
    if (!valor) return '';
    var partes = String(valor).split(' ');
    if (partes.length < 2) return valor;
    var fechaParts = partes[0].split('-');
    if (fechaParts.length !== 3) return valor;
    var tiempo = partes[1].split(':');

    var yy = parseInt(fechaParts[0], 10);
    var mm = parseInt(fechaParts[1], 10);
    var dd = parseInt(fechaParts[2], 10);
    var h = parseInt(tiempo[0] || '0', 10);
    var min = parseInt(tiempo[1] || '0', 10);

    if (isNaN(yy) || isNaN(mm) || isNaN(dd) || isNaN(h) || isNaN(min)) {
      return valor;
    }

    dd = dd < 10 ? '0' + dd : String(dd);
    mm = mm < 10 ? '0' + mm : String(mm);
    yy = String(yy);
    min = min < 10 ? '0' + min : String(min);
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    if (h === 0) h = 12;
    var hStr = h < 10 ? '0' + h : String(h);

    return dd + '/' + mm + '/' + yy + ' ' + hStr + ':' + min + ' ' + ampm;
  }

  function iconClass(tipo) {
    tipo = String(tipo || '').toLowerCase();

    if (tipo.indexOf('rechazada') !== -1 || tipo.indexOf('rechazo') !== -1) {
      return 'rechazada';
    }

    if (tipo.indexOf('cancelada') !== -1 || tipo.indexOf('cancelacion') !== -1) {
      return 'cancelada';
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
          '<span class="notificacion-time">' + escapeHtml(formatFechaCo(item.FECHA_CREACION)) + '</span>' +
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

    if (item.dataset.loading === 'true') {
      return;
    }

    item.dataset.loading = 'true';
    item.disabled = true;

    post(urls.read, {
      id: item.dataset.id
    }).then(function () {
      window.location.href = urls.detail + '&id=' + encodeURIComponent(item.dataset.solicitud);
    }).catch(function () {
      item.dataset.loading = 'false';
      item.disabled = false;
    });
  });

  readAll.addEventListener('click', function () {
    if (readAll.dataset.loading === 'true') {
      return;
    }

    readAll.dataset.loading = 'true';
    readAll.disabled = true;

    post(urls.readAll, {}).then(function () {
      lastNotifCount = 0;
      firstCountLoad = false;
      shouldPlayLoginSound = false;

      setCount(0);
      writeStoredNotifCount(0);
      loadList();
    }).catch(function () {
      // Mantiene la campana usable si falla la peticion.
    }).then(function () {
      readAll.dataset.loading = 'false';
      readAll.disabled = false;
    });
  });

  loadCount();

  window.setInterval(loadCount, 60000);
})();
</script>

</body>
</html>
