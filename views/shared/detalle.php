<section class="page-header">
  <div>
    <h1 class="page-title">Solicitud #<?= e($solicitud['ID'] ?? '') ?></h1>
    <p style="color:var(--muted)"><?= e(TIPOS_SOLICITUD[$solicitud['TIPO_SOLICITUD'] ?? ''] ?? ($solicitud['TIPO_SOLICITUD'] ?? '')) ?></p>
  </div>
  <a class="btn btn-outline" href="<?= e(url_view('solicitudes')) ?>">Volver</a>
</section>
<div class="form-card">
  <p><strong>Empleado:</strong> <?= e($solicitud['NIT_EMPLEADO'] ?? '') ?></p>
  <p><strong>Jefe:</strong> <?= e($solicitud['NIT_JEFE'] ?? '') ?></p>
  <p><strong>Fecha inicio:</strong> <?= e($solicitud['FECHA_INICIO'] ?? '') ?></p>
  <p><strong>Fecha fin:</strong> <?= e($solicitud['FECHA_FIN'] ?? '') ?></p>
  <p><strong>Estado:</strong> <?php $estadoValue = $solicitud['ESTADO'] ?? ''; require __DIR__ . '/badge_estado.php'; ?></p>

  <?php if (!empty($solicitud['RUTA_COMPROBANTE'])): ?>
    <p><a class="btn btn-green" target="_blank" href="<?= e(url_view('solicitud_archivo') . '&id=' . urlencode((string)($solicitud['ID'] ?? ''))) ?>">Ver PDF adjunto</a></p>
  <?php endif; ?>

  <p><strong>Observaciones:</strong><br><?= nl2br(e($solicitud['OBSERVACIONES'] ?? '')) ?></p>
  <?php if (!empty($solicitud['OBSERVACION_JEFE'])): ?><p><strong>Observacion jefe:</strong><br><?= nl2br(e($solicitud['OBSERVACION_JEFE'])) ?></p><?php endif; ?>
  <?php if (!empty($solicitud['OBSERVACION_RRHH'])): ?><p><strong>Observacion RRHH:</strong><br><?= nl2br(e($solicitud['OBSERVACION_RRHH'])) ?></p><?php endif; ?>
</div>