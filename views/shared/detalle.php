<?php
$solicitud = $solicitud ?? [];
$user = $user ?? usuario_actual();
$idSolicitud = (string)($solicitud['ID'] ?? '');
$cedulaUsuario = normalizar_documento($user['cedula'] ?? '');
$nitEmpleadoSolicitud = normalizar_documento($solicitud['NIT_EMPLEADO'] ?? '');
$estadoSolicitud = (string)($solicitud['ESTADO'] ?? '');
$esDuenoSolicitud = $nitEmpleadoSolicitud === $cedulaUsuario;
$estaPendienteJefe = $estadoSolicitud === ESTADO_PENDIENTE_JEFE;
$esSolicitudPropiaDirectaRrhh = ($user['rol'] ?? '') === ROL_JEFE && $estadoSolicitud === ESTADO_APROBADO_JEFE;
$puedeModificarSolicitud = $esDuenoSolicitud && ($estaPendienteJefe || $esSolicitudPropiaDirectaRrhh);
$returnToDetalle = app_base_url('index.php') . '?' . http_build_query($_GET);
?>
<section class="page-header">
  <div>
    <h1 class="page-title">Solicitud #<?= e($solicitud['ID'] ?? '') ?></h1>
    <p style="color:var(--muted)"><?= e(TIPOS_SOLICITUD[$solicitud['TIPO_SOLICITUD'] ?? ''] ?? ($solicitud['TIPO_SOLICITUD'] ?? '')) ?></p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php if ($puedeModificarSolicitud): ?>
      <a class="btn btn-outline" href="<?= e(url_view('solicitud_editar') . '&id=' . urlencode($idSolicitud) . '&return_to=' . urlencode($returnToDetalle)) ?>">Editar</a>
      <form class="inline-form" method="post" action="<?= e(url_action('solicitud_delete')) ?>" onsubmit="return confirm('Seguro que deseas eliminar esta solicitud?')">
        <?= csrf_input() ?>
        <input type="hidden" name="return_to" value="<?= e(url_view('dashboard')) ?>">
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
        <button class="btn btn-red" type="submit">Eliminar</button>
      </form>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url_view('dashboard')) ?>">Volver a inicio</a>
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
      <span class="detail-value"><?= e($solicitud['NIT_EMPLEADO'] ?? '') ?></span>
    </div>
    <div class="detail-item">
      <span class="detail-label">Jefe Inmediato</span>
      <span class="detail-value"><?= e($solicitud['NIT_JEFE'] ?? '') ?></span>
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
      <span class="detail-value date-value"><?= e($solicitud['FECHA_INICIO'] ?? '') ?></span>
    </div>
    <div class="detail-item">
      <span class="detail-label">Fecha de Fin</span>
      <span class="detail-value date-value"><?= e($solicitud['FECHA_FIN'] ?? '') ?></span>
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

  <?php if (!empty($solicitud['FECHA_GESTION_JEFE'])): ?>
  <div class="detail-meta-row">
    <span class="detail-meta-label">Gestionado por Jefe:</span>
    <span class="detail-meta-value"><?= e($solicitud['FECHA_GESTION_JEFE'] ?? '') ?></span>
  </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['FECHA_GESTION_RRHH'])): ?>
  <div class="detail-meta-row">
    <span class="detail-meta-label">Gestionado por RRHH:</span>
    <span class="detail-meta-value"><?= e($solicitud['FECHA_GESTION_RRHH'] ?? '') ?></span>
  </div>
  <?php endif; ?>
</div>

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

  <?php if (empty($solicitud['OBSERVACIONES']) && empty($solicitud['OBSERVACION_JEFE']) && empty($solicitud['OBSERVACION_RRHH'])): ?>
  <div class="observacion-empty">
    <p>No hay observaciones registradas para esta solicitud.</p>
  </div>
  <?php endif; ?>
</div>
