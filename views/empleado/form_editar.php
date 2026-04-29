<?php
$solicitud = $solicitud ?? [];
$user = $user ?? usuario_actual();

$baseUrl = $baseUrl ?? '';
$esAprendiz = $esAprendiz ?? false;
$jefes = $jefes ?? [];
$hoy = $hoy ?? date('Y-m-d');

$id = (string)($solicitud['ID'] ?? '');
$tipoActual = (string)($solicitud['TIPO_SOLICITUD'] ?? '');
$fechaInicio = substr((string)($solicitud['FECHA_INICIO'] ?? ''), 0, 10);
$fechaFin = substr((string)($solicitud['FECHA_FIN'] ?? ''), 0, 10);
$duracionHoras = (string)($solicitud['DURACION_HORAS'] ?? '');
$duracionDias = (string)($solicitud['DURACION_DIAS'] ?? '');
$observaciones = (string)($solicitud['OBSERVACIONES'] ?? '');
$rutaComprobante = (string)($solicitud['RUTA_COMPROBANTE'] ?? '');

$returnTo = $_GET['return_to'] ?? (url_view('solicitudes') . '&tipo=pendientes');

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<section class="page-header animate-fade-down">
  <div>
    <h1 class="page-title">Editar solicitud</h1>
  </div>
</section>

<div class="form-card">
  <form id="formSolicitud" method="post" action="<?= e(url_action('solicitud_update')) ?>" enctype="multipart/form-data" autocomplete="off">

    <?= csrf_input() ?>

    <input type="hidden" name="id" value="<?= e($id) ?>">
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">

    <?php if (empty($esAprendiz)): ?>
      <input type="hidden" name="nit_jefe" value="<?= e($solicitud['NIT_JEFE'] ?? ($user['nit_jefe'] ?? '')) ?>">
    <?php endif; ?>

    <div class="form-grid">

      <!-- TIPO -->
      <div class="form-group">
        <label>Tipo de solicitud</label>
        <select name="tipo_solicitud" required>
          <option value="">Selecciona...</option>

          <?php foreach (TIPOS_SOLICITUD as $key => $label): ?>
            <option value="<?= e((string)$key) ?>" <?= $tipoActual === (string)$key ? 'selected' : '' ?>>
              <?= e((string)$label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- JEFE -->
      <?php if (!empty($esAprendiz)): ?>
        <div class="form-group">
          <label>Jefe que revisa</label>
          <select name="nit_jefe_seleccionado" required>
            <option value="">Selecciona...</option>

            <?php foreach (($jefes ?? []) as $jefe): ?>
              <option
                value="<?= e($jefe['NIT']) ?>"
                <?= (string)($solicitud['NIT_JEFE'] ?? '') === (string)($jefe['NIT'] ?? '') ? 'selected' : '' ?>
              >
                <?= e($jefe['NOMBRE_COMPLETO']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <!-- FECHAS -->
      <div class="form-row">
        <div class="form-group">
          <label>Fecha inicio *</label>
          <input
            type="date"
            id="fecha_inicio"
            name="fecha_inicio"
            value="<?= e($fechaInicio) ?>"
            min="<?= e($hoy) ?>"
            required
          />
        </div>

        <div class="form-group">
          <label>Fecha fin *</label>
          <input
            type="date"
            id="fecha_fin"
            name="fecha_fin"
            value="<?= e($fechaFin) ?>"
            min="<?= e($hoy) ?>"
            required
          />
        </div>
      </div>

      <!-- DURACIÓN -->
      <div class="form-row">
        <div class="form-group">
          <label>Duración en horas</label>
          <input
            type="number"
            id="duracion_horas"
            name="duracion_horas"
            value="<?= e($duracionHoras) ?>"
            min="0"
            step="0.5"
          >
        </div>

        <div class="form-group">
          <label>Duración en días</label>
          <input
            type="number"
            id="duracion_dias"
            name="duracion_dias"
            value="<?= e($duracionDias) ?>"
            readonly
          >
        </div>
      </div>

      <!-- OBSERVACIONES -->
      <div class="form-group">
        <label>Observaciones</label>
        <textarea name="observaciones" rows="4"><?= e($observaciones) ?></textarea>
      </div>

      <!-- DOCUMENTO -->
      <div class="form-group">
        <label>Documento PDF</label>

        <?php if ($rutaComprobante !== ''): ?>
          <p style="margin:0 0 10px;color:var(--muted);font-size:14px">
            Documento actual:
            <a href="<?= e(url_view('solicitud_archivo') . '&id=' . urlencode($id)) ?>" target="_blank">
              Ver PDF cargado
            </a>
          </p>
        <?php endif; ?>

        <input type="file" name="documento_pdf" accept=".pdf">
        <small style="display:block;margin-top:8px;color:var(--muted)">
          Si no seleccionas un nuevo PDF, se conserva el documento actual.
        </small>
      </div>

    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-green">Guardar cambios</button>
      <a href="<?= e($returnTo) ?>" class="btn btn-gray">Cancelar</a>
    </div>

  </form>
</div>

<script>
(function () {

  var hoy = <?= json_encode($hoy) ?>;
  var HORAS_POR_DIA = 8;

  var ini = document.getElementById('fecha_inicio');
  var fin = document.getElementById('fecha_fin');
  var horas = document.getElementById('duracion_horas');
  var dias = document.getElementById('duracion_dias');

  function sumarMeses(fecha, meses) {
    var f = new Date(fecha + 'T00:00:00');
    f.setMonth(f.getMonth() + meses);
    return f.toISOString().split('T')[0];
  }

  var maxFecha = sumarMeses(hoy, 5);

  if (ini) {
    ini.max = maxFecha;
  }

  if (fin) {
    fin.max = maxFecha;
  }

  function calcularDuracion() {
    var fechaInicio = ini.value;
    var fechaFin = fin.value;

    if (!fechaInicio) {
      dias.value = '';
      horas.value = '';
      return;
    }

    fin.min = fechaInicio;
    fin.max = maxFecha;

    if (!fechaFin) {
      fin.value = fechaInicio;
      fechaFin = fechaInicio;
    }

    if (fechaFin < fechaInicio) {
      fin.value = fechaInicio;
      fechaFin = fechaInicio;
    }

    var d1 = new Date(fechaInicio + 'T00:00:00');
    var d2 = new Date(fechaFin + 'T00:00:00');

    var diffDays = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;

    dias.value = diffDays;

    if (!horas.dataset.editado) {
      horas.value = diffDays * HORAS_POR_DIA;
    }
  }

  if (horas) {
    horas.addEventListener('input', function () {
      horas.dataset.editado = "true";
    });
  }

  if (ini) {
    ini.addEventListener('change', calcularDuracion);
  }

  if (fin) {
    fin.addEventListener('change', calcularDuracion);
  }

})();
</script>