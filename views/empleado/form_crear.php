<?php
use Core\Config;
use Core\Security;

// 🔥 NO DEPENDER DE CONFIG
$baseUrl    = $baseUrl ?? '';
$esAprendiz = $esAprendiz ?? false;
$puedeSeleccionarJefe = $puedeSeleccionarJefe ?? $esAprendiz;
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

    <?php if (empty($puedeSeleccionarJefe)): ?>
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
      <?php if (!empty($puedeSeleccionarJefe)): ?>
      <div class="form-group">
        <label>Jefe que revisa</label>
        <select name="nit_jefe_seleccionado" required>
          <option value="">Selecciona...</option>
          <?php foreach (($jefes ?? []) as $jefe): ?>
            <option
              value="<?= e($jefe['NIT']) ?>"
              <?= (string)($user['nit_jefe'] ?? '') === (string)($jefe['NIT'] ?? '') ? 'selected' : '' ?>
            >
              <?= e($jefe['NOMBRE_COMPLETO']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <div class="form-group">
        <label>Jefe que revisa</label>
        <input
          type="text"
          value="<?= e(trim((string)($user['nombre_jefe'] ?? '')) !== '' ? ($user['nombre_jefe'] . ' - ' . ($user['nit_jefe'] ?? '')) : ($user['nit_jefe'] ?? '')) ?>"
          readonly
        >
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
        <input type="file" id="documento_pdf" name="documento_pdf" accept=".pdf" required>
        <span class="field-hint">Máximo 5MB. Formatos permitidos: PDF</span>

        <!-- Vista previa del PDF -->
        <div id="pdf-preview-container" class="pdf-preview-container" style="display: none;">
          <div class="pdf-preview-header">
            <svg class="pdf-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="pdf-filename" id="pdf-filename">documento.pdf</span>
            <span class="pdf-size" id="pdf-size">0 KB</span>
            <button type="button" class="remove-pdf-btn" id="remove-pdf" title="Eliminar archivo">×</button>
          </div>
          <div class="pdf-preview-content">
            <iframe id="pdf-iframe" src="" title="Vista previa del PDF"></iframe>
          </div>
        </div>

        <!-- Error de validación -->
        <div id="pdf-error" class="file-error" style="display: none;"></div>
      </div>

    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-green">Enviar solicitud</button>
      <a href="<?= e(url_view('dashboard')) ?>" class="btn btn-gray">Cancelar</a>
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

  // ===== VISTA PREVIA DEL PDF =====
  var pdfInput = document.getElementById('documento_pdf');
  var pdfPreviewContainer = document.getElementById('pdf-preview-container');
  var pdfFilename = document.getElementById('pdf-filename');
  var pdfSize = document.getElementById('pdf-size');
  var pdfIframe = document.getElementById('pdf-iframe');
  var removePdfBtn = document.getElementById('remove-pdf');
  var pdfError = document.getElementById('pdf-error');
  var MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function showPdfError(message) {
    pdfError.textContent = message;
    pdfError.style.display = 'block';
    pdfPreviewContainer.style.display = 'none';
    pdfInput.value = '';
  }

  function hidePdfError() {
    pdfError.style.display = 'none';
  }

  function resetPdfPreview() {
    pdfPreviewContainer.style.display = 'none';
    pdfIframe.src = '';
    pdfInput.value = '';
    hidePdfError();
  }

  pdfInput.addEventListener('change', function(e) {
    var file = e.target.files[0];

    if (!file) {
      resetPdfPreview();
      return;
    }

    // Validar tipo de archivo
    if (file.type !== 'application/pdf') {
      showPdfError('El archivo debe ser un PDF válido.');
      return;
    }

    // Validar tamaño
    if (file.size > MAX_FILE_SIZE) {
      showPdfError('El archivo no puede superar 5MB.');
      return;
    }

    hidePdfError();

    // Mostrar información del archivo
    pdfFilename.textContent = file.name;
    pdfSize.textContent = formatFileSize(file.size);

    // Crear URL para vista previa
    var fileUrl = URL.createObjectURL(file);
    pdfIframe.src = fileUrl;
    pdfPreviewContainer.style.display = 'block';
  });

  // Eliminar archivo seleccionado
  removePdfBtn.addEventListener('click', function() {
    resetPdfPreview();
  });

  // Limpiar URL cuando se envíe el formulario o se abandone la página
  window.addEventListener('beforeunload', function() {
    if (pdfIframe.src && pdfIframe.src.startsWith('blob:')) {
      URL.revokeObjectURL(pdfIframe.src);
    }
  });

})();
</script>
