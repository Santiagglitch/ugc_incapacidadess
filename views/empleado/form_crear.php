<?php
use Core\Config;
use Core\Security;

// 🔥 NO DEPENDER DE CONFIG
$baseUrl    = $baseUrl ?? '';
$esAprendiz = $esAprendiz ?? false;
$jefes      = $jefes ?? [];
$tipos      = $tipos ?? [];
$hoy        = $hoy ?? date('Y-m-d');

// 🔥 CSRF SIN USAR CLASE SECURITY
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<section class="page-header animate-fade-down">
  <div>
    <h1 class="page-title">Nueva solicitud</h1>
  </div>
</section>

<div class="form-card">
  <form id="formSolicitud" method="post" action="<?= e(url_action('solicitud_create')) ?>" enctype="multipart/form-data" autocomplete="off">
    
    <?= csrf_input() ?>

    <?php if (empty($esAprendiz)): ?>
      <input type="hidden" name="nit_jefe" value="<?= e($user['nit_jefe'] ?? '') ?>">
    <?php endif; ?>

    <div class="form-grid">

      <!-- TIPO -->
      <div class="form-group">
        <label>Tipo de solicitud</label>
        <select name="tipo_solicitud" required>
          <option value="">Selecciona...</option>
          <?php foreach (TIPOS_SOLICITUD as $key => $label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
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
            <option value="<?= e($jefe['NIT']) ?>">
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
          <input type="date" id="fecha_inicio" name="fecha_inicio"
                 min="<?= htmlspecialchars($hoy) ?>" required />
        </div>

        <div class="form-group">
          <label>Fecha fin *</label>
          <input type="date" id="fecha_fin" name="fecha_fin"
                 min="<?= htmlspecialchars($hoy) ?>" required />
        </div>
      </div>

      <!-- DURACIÓN -->
      <div class="form-row">
        <div class="form-group">
          <label>Duración en horas</label>
          <input type="number" id="duracion_horas" name="duracion_horas"
                 min="0" step="0.5">
        </div>

        <div class="form-group">
          <label>Duración en días</label>
          <input type="number" id="duracion_dias" name="duracion_dias" readonly>
        </div>
      </div>

      <!-- OBSERVACIONES -->
      <div class="form-group">
        <label>Observaciones</label>
        <textarea name="observaciones" rows="4"></textarea>
      </div>

      <!-- DOCUMENTO -->
      <div class="form-group">
        <label>Documento PDF</label>
        <input type="file" name="documento_pdf" accept=".pdf" required>
      </div>

    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-green">Enviar solicitud</button>
      <a href="<?= $baseUrl ?>/dashboard" class="btn btn-gray">Cancelar</a>
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

  // ===== LIMITE 5 MESES =====
  function sumarMeses(fecha, meses) {
    var f = new Date(fecha);
    f.setMonth(f.getMonth() + meses);
    return f.toISOString().split('T')[0];
  }

  var maxFecha = sumarMeses(hoy, 5);

  ini.max = maxFecha;
  fin.max = maxFecha;

  function calcularDuracion() {

    var fechaInicio = ini.value;
    var fechaFin = fin.value;

    if (!fechaInicio) {
      dias.value = '';
      horas.value = '';
      return;
    }

    // reglas fechas
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

    // días automáticos
    dias.value = diffDays;

    // horas automáticas (solo si no editó)
    if (!horas.dataset.editado) {
      horas.value = diffDays * HORAS_POR_DIA;
    }
  }

  // detectar edición manual de horas
  horas.addEventListener('input', function () {
    horas.dataset.editado = "true";
  });

  ini.addEventListener('change', calcularDuracion);
  fin.addEventListener('change', calcularDuracion);

})();
</script>