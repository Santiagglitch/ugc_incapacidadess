<?php
$solicitud = $solicitud ?? [];
$user = $user ?? usuario_actual();
$idSolicitud = (string)($solicitud['ID'] ?? '');
$cedulaUsuario = normalizar_documento($user['cedula'] ?? '');
$nitEmpleadoSolicitud = normalizar_documento($solicitud['NIT_EMPLEADO'] ?? '');
$nitJefeSolicitud = normalizar_documento($solicitud['NIT_JEFE'] ?? '');
$nombreEmpleadoSolicitud = trim((string)($solicitud['NOMBRE_EMPLEADO'] ?? ''));
$nombreJefeSolicitud = trim((string)($solicitud['NOMBRE_JEFE'] ?? ''));
$empleadoDetalle = $nombreEmpleadoSolicitud !== '' ? $nombreEmpleadoSolicitud : (string)($solicitud['NIT_EMPLEADO'] ?? '');
$jefeDetalle = $nombreJefeSolicitud !== '' ? $nombreJefeSolicitud : (string)($solicitud['NIT_JEFE'] ?? '');
$fechaCreacionDetalle = (string)($solicitud['FECHA_CREACION'] ?? '');
$estadoSolicitud = (string)($solicitud['ESTADO'] ?? '');
$esDuenoSolicitud = $nitEmpleadoSolicitud === $cedulaUsuario;
$estaPendienteJefe = $estadoSolicitud === ESTADO_PENDIENTE_JEFE;
$puedeModificarSolicitud = $esDuenoSolicitud && $estaPendienteJefe;
$puedeCancelarSolicitud = $esDuenoSolicitud
  && in_array($estadoSolicitud, [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true);
$puedeGestionarJefe = in_array($user['rol'] ?? '', [ROL_JEFE, ROL_ADMIN], true)
  && $estaPendienteJefe
  && $nitJefeSolicitud === $cedulaUsuario
  && !$esDuenoSolicitud;
$puedeGestionarRrhh = in_array($user['rol'] ?? '', [ROL_RRHH, ROL_ADMIN], true)
  && $estadoSolicitud === ESTADO_APROBADO_JEFE
  && !$esDuenoSolicitud;
$esAdmin = ($user['rol'] ?? '') === ROL_ADMIN;
$estadosIntervenibles = [ESTADO_PENDIENTE_JEFE, ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH];
$puedeIntervenir = $esAdmin && !$esDuenoSolicitud && in_array($estadoSolicitud, $estadosIntervenibles, true);
$puedeForzarEstado = $esAdmin && !$esDuenoSolicitud;
$notasAdmin = $notasAdmin ?? [];
$returnToDetalle = url_retorno_segura($_GET['return_to'] ?? null, url_view('dashboard'));
?>
<section class="page-header">
  <div>
    <h1 class="page-title">Solicitud</h1>
    <p style="color:var(--muted)"><?= e(TIPOS_SOLICITUD[$solicitud['TIPO_SOLICITUD'] ?? ''] ?? ($solicitud['TIPO_SOLICITUD'] ?? '')) ?></p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php if ($puedeCancelarSolicitud): ?>
      <form
        id="form-cancelar-agenda"
        class="inline-form form-cancelar-agenda"
        method="post"
        action="<?= e(url_action('solicitud_cancelar_agenda')) ?>"
      >
        <?= csrf_input() ?>
        <input type="hidden" name="_method" value="POST">
        <input type="hidden" name="return_to" value="<?= e($returnToDetalle) ?>">
        <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
        <input type="hidden" name="observacion_cancelacion" id="motivo_cancelacion_val">
      </form>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e($returnToDetalle) ?>">Volver</a>
  </div>
</section>

<!-- Card de Informacion General -->
<div class="detail-card">
  <div class="detail-section-header">
    <h2 class="detail-section-title">Información General</h2>
  </div>

  <div class="detail-grid">
    <div class="detail-item">
      <span class="detail-label">Empleado</span>
      <span class="detail-value"><?= e($empleadoDetalle) ?></span>
    </div>
    <div class="detail-item">
      <span class="detail-label">Jefe Inmediato</span>
      <span class="detail-value"><?= e($jefeDetalle) ?></span>
    </div>
  </div>
</div>

<!-- Card de Fechas -->
<div class="detail-card" style="margin-top: 20px;">
  <div class="detail-section-header">
    <h2 class="detail-section-title">Período de la Solicitud</h2>
  </div>

  <div class="detail-grid">
    <div class="detail-item">
      <span class="detail-label">Fecha de Inicio</span>
      <span class="detail-value date-value"><?= e(fecha_co((string)($solicitud['FECHA_INICIO'] ?? ''))) ?></span>
    </div>
    <div class="detail-item">
      <span class="detail-label">Fecha de Fin</span>
      <span class="detail-value date-value"><?= e(fecha_co((string)($solicitud['FECHA_FIN'] ?? ''))) ?></span>
    </div>
    <?php if (!empty($solicitud['DURACION_HORAS'])): ?>
    <div class="detail-item">
      <span class="detail-label">Duración en Horas</span>
      <span class="detail-value duration-value"><?= e((string)$solicitud['DURACION_HORAS']) ?> horas</span>
    </div>
    <?php endif; ?>
    <?php if (!empty($solicitud['DURACION_DIAS'])): ?>
    <div class="detail-item">
      <span class="detail-label">Duración en Días</span>
      <span class="detail-value duration-value"><?= e((string)$solicitud['DURACION_DIAS']) ?> días</span>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Card de Estado -->
<div class="detail-card" style="margin-top: 20px;">
  <div class="detail-section-header">
    <h2 class="detail-section-title">Estado de la Solicitud</h2>
  </div>

  <div class="detail-status-box">
    <div class="detail-status-label">Estado actual:</div>
    <div class="detail-status-badge">
      <?php $estadoValue = $solicitud['ESTADO'] ?? ''; require __DIR__ . '/badge_estado.php'; ?>
    </div>
  </div>

  <?php if ($fechaCreacionDetalle !== ''): ?>
  <div class="detail-meta-row">
    <span class="detail-meta-label">Fecha de creación:</span>
    <span class="detail-meta-value"><?= e(fecha_hora_co($fechaCreacionDetalle)) ?></span>
  </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['FECHA_GESTION_JEFE'])): ?>
  <div class="detail-meta-row">
    <span class="detail-meta-label">Gestionado por Jefe:</span>
    <span class="detail-meta-value"><?= e(fecha_hora_co((string)($solicitud['FECHA_GESTION_JEFE'] ?? ''))) ?></span>
  </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['FECHA_GESTION_RRHH'])): ?>
  <div class="detail-meta-row">
    <span class="detail-meta-label">Gestionado por RRHH:</span>
    <span class="detail-meta-value"><?= e(fecha_hora_co((string)($solicitud['FECHA_GESTION_RRHH'] ?? ''))) ?></span>
  </div>
  <?php endif; ?>
</div>

<?php if ($puedeGestionarJefe): ?>
<div class="gestion-card">
  <h3>Gestión de jefe inmediato</h3>

  <form id="formGestionJefe" method="post" action="<?= e(url_action('solicitud_jefe')) ?>">
    <?= csrf_input() ?>

    <input type="hidden" name="return_to" value="<?= e($returnToDetalle) ?>">
    <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">

    <div class="form-group">
      <label>Observación</label>

      <textarea 
        id="observacion_jefe"
        name="observacion_jefe" 
        rows="3" 
        maxlength="2000"
        placeholder="Escribe la razón de la decisión..."
      ></textarea>

      <small id="errorObservacion" style="display:none; color:#b00020; font-weight:600; margin-top:6px;">
        Para rechazar la solicitud es obligatorio escribir una observación indicando el motivo.
      </small>
    </div>

    <div class="form-actions">
      <button class="btn btn-green" type="submit" name="decision" value="aprobar">
        Aprobar
      </button>

      <button class="btn btn-red" type="submit" name="decision" value="rechazar">
        Rechazar
      </button>
    </div>
  </form>
</div>

<script nonce="<?= e(csp_nonce()) ?>">
document.getElementById('formGestionJefe').addEventListener('submit', function(e) {
  const botonPresionado = e.submitter;
  const observacion = document.getElementById('observacion_jefe');
  const error = document.getElementById('errorObservacion');

  if (botonPresionado && botonPresionado.value === 'rechazar') {
    const textoObservacion = observacion.value.trim();

    if (textoObservacion === '') {
      e.preventDefault();

      error.style.display = 'block';
      observacion.style.border = '2px solid #b00020';
      observacion.focus();

      alert('Para rechazar la solicitud debes escribir obligatoriamente el motivo en la observación.');
      return false;
    }

    const confirmar = confirm('¿Estás seguro de que quieres rechazar esta solicitud?');

    if (!confirmar) {
      e.preventDefault();
      return false;
    }
  }

  error.style.display = 'none';
  observacion.style.border = '';
});
</script>
<?php endif; ?>
<?php if ($puedeGestionarRrhh): ?>
<div class="gestion-card">
  <h3>Gestión de Talento Humano</h3>

  <form id="formGestionRrhh" method="post" action="<?= e(url_action('solicitud_rrhh')) ?>">
    <?= csrf_input() ?>

    <input type="hidden" name="return_to" value="<?= e($returnToDetalle) ?>">
    <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">

    <div class="form-group">
      <label>Observación</label>

      <textarea 
        id="observacion_rrhh"
        name="observacion_rrhh" 
        rows="3" 
        maxlength="2000"
        placeholder="Escribe la razón de la decisión..."
      ></textarea>

      <small id="errorObservacionRrhh" style="display:none; color:#b00020; font-weight:600; margin-top:6px;">
        Para rechazar la solicitud es obligatorio escribir una observación indicando el motivo.
      </small>
    </div>

    <div class="form-actions">
      <button class="btn btn-green" type="submit" name="decision" value="aprobar">
        Aprobar RRHH
      </button>

      <button class="btn btn-red" type="submit" name="decision" value="rechazar">
        Rechazar RRHH
      </button>
    </div>
  </form>
</div>

<script nonce="<?= e(csp_nonce()) ?>">
document.getElementById('formGestionRrhh').addEventListener('submit', function(e) {
  const botonPresionado = e.submitter;
  const observacion = document.getElementById('observacion_rrhh');
  const error = document.getElementById('errorObservacionRrhh');

  if (botonPresionado && botonPresionado.value === 'rechazar') {
    const textoObservacion = observacion.value.trim();

    if (textoObservacion === '') {
      e.preventDefault();

      error.style.display = 'block';
      observacion.style.border = '2px solid #b00020';
      observacion.focus();

      alert('Para rechazar la solicitud primero debes escribir el motivo en la observación.');
      return false;
    }

    const confirmar = confirm('¿Estás seguro de que quieres rechazar esta solicitud?');

    if (!confirmar) {
      e.preventDefault();
      return false;
    }
  }

  error.style.display = 'none';
  observacion.style.border = '';
});
</script>
<?php endif; ?>

<!-- Card de Documento Adjunto -->
<?php if (!empty($solicitud['RUTA_COMPROBANTE'])): ?>
<div class="detail-card" style="margin-top: 20px;">
  <div class="detail-section-header">
    <h2 class="detail-section-title">Documento Adjunto</h2>
  </div>

  <div class="archivo-adjunto-box">
    <div class="archivo-info-row">
      <svg class="archivo-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
      </svg>
      <div class="archivo-details">
        <span class="archivo-name">Comprobante adjunto</span>
        <span class="archivo-hint">Documento PDF de respaldo</span>
      </div>
    </div>
    <a class="btn btn-green btn-ver-archivo" target="_blank" href="<?= e(url_view('solicitud_archivo') . '&id=' . urlencode((string)($solicitud['ID'] ?? ''))) ?>">
      Ver documento
    </a>
  </div>
</div>
<?php endif; ?>

<!-- Card de Observaciones -->
<div class="detail-card" style="margin-top: 20px;">
  <div class="detail-section-header">
    <h2 class="detail-section-title">Observaciones</h2>
  </div>

  <?php if (!empty($solicitud['OBSERVACIONES'])): ?>
  <div class="observacion-block">
    <div class="observacion-header">
      <span class="observacion-badge obs-solicitante">Solicitante</span>
    </div>
    <div class="observacion-content"><?= nl2br(e($solicitud['OBSERVACIONES'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['OBSERVACION_JEFE'])): ?>
  <div class="observacion-block">
    <div class="observacion-header">
      <span class="observacion-badge obs-jefe">Jefe Inmediato</span>
    </div>
    <div class="observacion-content"><?= nl2br(e($solicitud['OBSERVACION_JEFE'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['OBSERVACION_RRHH'])): ?>
  <div class="observacion-block">
    <div class="observacion-header">
      <span class="observacion-badge obs-rrhh">Talento Humano</span>
    </div>
    <div class="observacion-content"><?= nl2br(e($solicitud['OBSERVACION_RRHH'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['MOTIVO_CANCELACION'])): ?>
  <div class="observacion-block">
    <div class="observacion-header">
      <span class="observacion-badge" style="background:#fff3cd;color:#856404;border:1px solid #ffe08a">Motivo de cancelación</span>
    </div>
    <div class="observacion-content"><?= nl2br(e($solicitud['MOTIVO_CANCELACION'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if (empty($solicitud['OBSERVACIONES']) && empty($solicitud['OBSERVACION_JEFE']) && empty($solicitud['OBSERVACION_RRHH']) && empty($solicitud['MOTIVO_CANCELACION'])): ?>
  <div class="observacion-empty">
    <p>No hay observaciones registradas para esta solicitud.</p>
  </div>
  <?php endif; ?>
</div>

<?php if ($puedeIntervenir): ?>
<div class="gestion-card" style="border-left:4px solid var(--green);margin-top:20px">
  <h3 style="display:flex;align-items:center;gap:8px">
    <span style="background:var(--green3);color:var(--green);font-size:11px;font-weight:700;padding:2px 10px;border-radius:99px;border:1px solid var(--green4)">ADMIN</span>
    Intervención administrativa
  </h3>
  <p style="font-size:12px;color:var(--muted);margin-bottom:14px">Como administrador puedes aprobar o rechazar esta solicitud independientemente del flujo normal.</p>
  <form id="formAdminIntervenir" method="post" action="<?= e(url_action('admin_intervenir_solicitud')) ?>">
    <?= csrf_input() ?>
    <input type="hidden" name="return_to" value="<?= e($returnToDetalle) ?>">
    <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
    <div class="form-group">
      <label>Observación administrativa <span style="color:var(--red)">*</span></label>
      <textarea id="obs_admin_intervenir" name="observacion_admin" rows="3" maxlength="2000" placeholder="Motivo de la intervención..."></textarea>
      <small id="err_obs_admin" style="display:none;color:#b00020;font-weight:600;margin-top:4px">La observación es obligatoria.</small>
    </div>
    <div class="form-actions">
      <button class="btn btn-green" type="submit" name="decision" value="aprobar">Aprobar (admin)</button>
      <button class="btn btn-red" type="submit" name="decision" value="rechazar">Rechazar (admin)</button>
    </div>
  </form>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
document.getElementById('formAdminIntervenir').addEventListener('submit', function(e) {
  var obs = document.getElementById('obs_admin_intervenir');
  var err = document.getElementById('err_obs_admin');
  if (obs.value.trim() === '') {
    e.preventDefault();
    err.style.display = 'block';
    obs.style.border = '2px solid #b00020';
    obs.focus();
    return false;
  }
  err.style.display = 'none';
  obs.style.border = '';
});
</script>
<?php endif; ?>

<?php if ($esAdmin): ?>
<div class="detail-card" style="margin-top:20px;border-left:4px solid var(--blue,#3b82f6)">
  <div class="detail-section-header" style="display:flex;justify-content:space-between;align-items:center">
    <h2 class="detail-section-title" style="display:flex;align-items:center;gap:8px">
      <span style="background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:700;padding:2px 10px;border-radius:99px;border:1px solid #bfdbfe">ADMIN</span>
      Notas internas
    </h2>
  </div>
  <form method="post" action="<?= e(url_action('admin_guardar_nota')) ?>" style="margin-bottom:16px">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
    <input type="hidden" name="return_to" value="<?= e($returnToDetalle) ?>">
    <div style="display:flex;gap:8px;align-items:flex-start">
      <textarea name="nota_admin" rows="2" maxlength="1000" placeholder="Escribe una nota interna (solo visible para administradores)..." style="flex:1;padding:8px 10px;border:1px solid var(--border);border-radius:var(--r2);font-size:13px;background:var(--surface);color:var(--text);resize:vertical"></textarea>
      <button class="btn btn-outline btn-sm" type="submit" style="white-space:nowrap">Guardar nota</button>
    </div>
  </form>
  <?php if (!empty($notasAdmin)): ?>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px">
      <?php foreach ($notasAdmin as $n): ?>
      <li style="background:var(--surface2,#f8fafc);border:1px solid var(--border);border-radius:var(--r2);padding:10px 12px">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px;display:flex;justify-content:space-between">
          <span style="font-weight:600"><?= e($n['nombre'] ?? $n['nit'] ?? '') ?></span>
          <span><?= e(fecha_hora_co((string)($n['fecha'] ?? ''))) ?></span>
        </div>
        <div style="font-size:13px;white-space:pre-wrap"><?= nl2br(e((string)($n['nota'] ?? ''))) ?></div>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p style="font-size:12px;color:var(--muted)">No hay notas internas para esta solicitud.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($puedeForzarEstado): ?>
<div class="gestion-card" style="border-left:4px solid #f39c12;margin-top:20px">
  <h3 style="display:flex;align-items:center;gap:8px">
    <span style="background:#fff3cd;color:#856404;font-size:11px;font-weight:700;padding:2px 10px;border-radius:99px;border:1px solid #ffe08a">ADMIN</span>
    Forzar estado
  </h3>
  <p style="font-size:12px;color:var(--muted);margin-bottom:14px">Cambia el estado de la solicitud directamente. Úsalo solo en casos excepcionales.</p>
  <form id="formForzarEstado" method="post" action="<?= e(url_action('admin_forzar_estado')) ?>">
    <?= csrf_input() ?>
    <input type="hidden" name="return_to" value="<?= e($returnToDetalle) ?>">
    <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
    <div class="form-group" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:180px">
        <label>Nuevo estado</label>
        <select name="nuevo_estado" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--r2);font-size:13px;background:var(--surface);color:var(--text)">
          <option value="<?= e(ESTADO_PENDIENTE_JEFE) ?>" <?= $estadoSolicitud === ESTADO_PENDIENTE_JEFE ? 'selected' : '' ?>>Pendiente jefe</option>
          <option value="<?= e(ESTADO_APROBADO_JEFE) ?>" <?= $estadoSolicitud === ESTADO_APROBADO_JEFE ? 'selected' : '' ?>>Aprobado jefe / Pendiente RRHH</option>
          <option value="<?= e(ESTADO_RECHAZADO_JEFE) ?>" <?= $estadoSolicitud === ESTADO_RECHAZADO_JEFE ? 'selected' : '' ?>>Rechazado jefe</option>
          <option value="<?= e(ESTADO_APROBADO_RRHH) ?>" <?= $estadoSolicitud === ESTADO_APROBADO_RRHH ? 'selected' : '' ?>>Aprobado RRHH</option>
          <option value="<?= e(ESTADO_RECHAZADO_RRHH) ?>" <?= $estadoSolicitud === ESTADO_RECHAZADO_RRHH ? 'selected' : '' ?>>Rechazado RRHH</option>
        </select>
      </div>
      <div style="flex:2;min-width:200px">
        <label>Motivo <span style="color:var(--red)">*</span></label>
        <input id="motivo_forzar" type="text" name="motivo" maxlength="500" placeholder="Motivo del cambio..." style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--r2);font-size:13px;background:var(--surface);color:var(--text)">
      </div>
      <button class="btn btn-outline" type="submit" onclick="return confirm('¿Confirmar cambio de estado?')">Aplicar</button>
    </div>
    <small id="err_motivo_forzar" style="display:none;color:#b00020;font-weight:600;margin-top:4px">El motivo es obligatorio.</small>
  </form>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
document.getElementById('formForzarEstado').addEventListener('submit', function(e) {
  var m = document.getElementById('motivo_forzar');
  var err = document.getElementById('err_motivo_forzar');
  if (m.value.trim() === '') {
    e.preventDefault();
    err.style.display = 'block';
    m.style.border = '2px solid #b00020';
    m.focus();
    return false;
  }
  err.style.display = 'none';
  m.style.border = '';
});
</script>
<?php endif; ?>

<?php if ($puedeCancelarSolicitud): ?>
<div id="modal-cancelar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--surface,#fff);border-radius:var(--r3,10px);padding:28px 24px;max-width:440px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18)">
    <h3 style="margin:0 0 6px;font-size:16px">Cancelar solicitud</h3>
    <p style="font-size:13px;color:var(--muted);margin:0 0 16px">Escribe el motivo de cancelación. Quedará guardado como motivo de cancelación.</p>
    <textarea id="motivo-cancelar-input" rows="3" maxlength="2000" placeholder="Motivo de cancelación..." style="width:100%;padding:8px 10px;border:1px solid var(--border,#e2e8f0);border-radius:var(--r2,6px);font-size:13px;background:var(--surface,#fff);color:var(--text,#1a1a1a);resize:vertical;box-sizing:border-box"></textarea>
    <small id="motivo-cancelar-error" style="display:none;color:#b00020;font-weight:600;margin-top:4px;display:block">El motivo de cancelación es obligatorio.</small>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
      <button type="button" id="btn-cancelar-cerrar" class="btn btn-outline">Cerrar</button>
      <button type="button" id="btn-cancelar-confirmar" class="btn btn-red">Confirmar cancelación</button>
    </div>
  </div>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
(function() {
  var modal   = document.getElementById('modal-cancelar');
  var input   = document.getElementById('motivo-cancelar-input');
  var error   = document.getElementById('motivo-cancelar-error');
  var btnConf = document.getElementById('btn-cancelar-confirmar');
  var btnCerr = document.getElementById('btn-cancelar-cerrar');
  var form    = document.getElementById('form-cancelar-agenda');
  var hidden  = document.getElementById('motivo_cancelacion_val');

  document.querySelectorAll('.form-cancelar-agenda button[type="submit"], .btn-cancelar-agenda').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      input.value = '';
      error.style.display = 'none';
      input.style.border = '';
      modal.style.display = 'flex';
      setTimeout(function(){ input.focus(); }, 80);
    });
  });

  btnCerr.addEventListener('click', function() {
    modal.style.display = 'none';
  });

  modal.addEventListener('click', function(e) {
    if (e.target === modal) modal.style.display = 'none';
  });

  btnConf.addEventListener('click', function() {
    var motivo = input.value.trim();
    if (motivo === '') {
      error.style.display = 'block';
      input.style.border = '2px solid #b00020';
      input.focus();
      return;
    }
    hidden.value = motivo;
    modal.style.display = 'none';
    form.submit();
  });
})();
</script>
<?php endif; ?>
