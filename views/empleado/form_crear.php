<?php
$jefes = $jefes ?? [];
$hoy   = $hoy ?? date('Y-m-d');
?>

<section class="page-header animate-fade-down">
  <div>
    <h1 class="page-title">Nueva solicitud</h1>
  </div>
</section>

<style>
  .duration-type-box {
    display: none;
    margin-top: 4px;
  }

  .duration-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(240px, 1fr));
    gap: 16px;
    margin-top: 12px;
  }

  .duration-option {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 18px;
    border: 1px solid #dce5ef;
    border-radius: 18px;
    background: #ffffff;
    cursor: pointer;
    transition: all .22s ease;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  }

  .duration-option:hover {
    transform: translateY(-1px);
    border-color: #bfd8c7;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  }

  .duration-option.is-selected {
    border-color: #1d7a44;
    background: linear-gradient(180deg, #ffffff 0%, #f3fbf6 100%);
    box-shadow: 0 0 0 3px rgba(29, 122, 68, 0.12);
  }

  .duration-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .duration-indicator {
    width: 22px;
    height: 22px;
    min-width: 22px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    margin-top: 2px;
    background: #fff;
    transition: all .2s ease;
    box-sizing: border-box;
  }

  .duration-option.is-selected .duration-indicator {
    border-color: #1d7a44;
    background: #1d7a44;
    box-shadow: inset 0 0 0 4px #fff;
  }

  .duration-option-content {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .duration-option-title {
    font-weight: 700;
    color: #17324d;
    font-size: 16px;
    line-height: 1.2;
  }

  .duration-option-text {
    color: #64748b;
    font-size: 13px;
    line-height: 1.45;
  }

  .duration-help {
    display: none;
    margin-top: 12px;
    padding: 13px 14px;
    border-radius: 14px;
    background: #f8fbfd;
    border: 1px solid #dbe5ee;
    border-left: 4px solid #1d7a44;
    color: #4b5f74;
    font-size: 13px;
    line-height: 1.5;
  }

  .field-hidden {
    display: none !important;
  }

  @media (max-width: 768px) {
    .duration-options {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="form-card">
  <form id="formSolicitud" method="post" action="<?= e(url_action('solicitud_create')) ?>" enctype="multipart/form-data" autocomplete="off">
    
    <?= csrf_input() ?>
    <input type="hidden" name="_submit_token" value="<?= e($submitToken ?? '') ?>">

    <div class="form-grid">

      <!-- TIPO -->
      <div class="form-group">
        <label>Tipo de solicitud</label>
        <select id="tipo_solicitud" name="tipo_solicitud" required>
          <option value="">Selecciona...</option>
          <?php foreach (TIPOS_SOLICITUD as $key => $label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- JEFE -->
      <div class="form-group">
        <label>Jefe que revisa</label>
        <select name="nit_jefe_seleccionado" required>
          <option value="">Selecciona...</option>
          <?php foreach (($jefes ?? []) as $jefe): ?>
            <option value="<?= e($jefe['NIT']) ?>">
              <?= e(trim((string)($jefe['NOMBRE_COMPLETO'] ?? '')) !== '' ? ($jefe['NOMBRE_COMPLETO'] . ' - ' . ($jefe['NIT'] ?? '')) : ($jefe['NIT'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- TIPO DE DURACIÓN -->
      <div class="form-group duration-type-box" id="control_tipo_duracion">
        <label>¿Cómo se registra esta solicitud?</label>

        <div class="duration-options">
          <label class="duration-option" id="card_tipo_horas">
            <input type="radio" id="tipo_duracion_horas" name="tipo_duracion" value="HORAS">
            <span class="duration-indicator"></span>
            <span class="duration-option-content">
              <span class="duration-option-title">Por horas</span>
              <span class="duration-option-text">Ideal para permisos cortos dentro del mismo día.</span>
            </span>
          </label>

          <label class="duration-option" id="card_tipo_dias">
            <input type="radio" id="tipo_duracion_dias" name="tipo_duracion" value="DIAS">
            <span class="duration-indicator"></span>
            <span class="duration-option-content">
              <span class="duration-option-title">Por días</span>
              <span class="duration-option-text">Ideal para ausencias de uno o varios días completos.</span>
            </span>
          </label>
        </div>

        <div class="duration-help" id="ayuda_duracion"></div>
      </div>

      <!-- FECHAS -->
      <div class="form-row">
        <div class="form-group" id="grupo_fecha_inicio">
          <label id="label_fecha_inicio">Fecha inicio *</label>
          <input type="date" id="fecha_inicio" name="fecha_inicio"
                 min="<?= htmlspecialchars($hoy) ?>" required />
        </div>

        <div class="form-group" id="grupo_fecha_fin">
          <label>Fecha fin *</label>
          <input type="date" id="fecha_fin" name="fecha_fin"
                 min="<?= htmlspecialchars($hoy) ?>" required />
        </div>
      </div>

      <!-- DURACIÓN -->
      <div class="form-row">
        <div class="form-group" id="grupo_duracion_horas">
          <label>Duración en horas *</label>
          <input type="number" id="duracion_horas" name="duracion_horas"
                 min="0.5" step="0.5" placeholder="Ej: 2.5">
        </div>

        <div class="form-group" id="grupo_duracion_dias">
          <label>Duración en días</label>
          <input type="number" id="duracion_dias" name="duracion_dias" readonly placeholder="Se calcula automáticamente">
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

        <div id="pdf-error" class="file-error" style="display: none;"></div>
      </div>

    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-green">Enviar solicitud</button>
      <a href="<?= e(url_view('dashboard')) ?>" class="btn btn-gray" data-click-once>Cancelar</a>
    </div>

  </form>
</div>

<script>
(function () {

  var hoy = <?= json_encode($hoy) ?>;

  var form = document.getElementById('formSolicitud');
  var tipoSolicitud = document.getElementById('tipo_solicitud');

  var controlTipoDuracion = document.getElementById('control_tipo_duracion');
  var radioHoras = document.getElementById('tipo_duracion_horas');
  var radioDias = document.getElementById('tipo_duracion_dias');
  var ayudaDuracion = document.getElementById('ayuda_duracion');

  var cardHoras = document.getElementById('card_tipo_horas');
  var cardDias = document.getElementById('card_tipo_dias');

  var grupoFechaFin = document.getElementById('grupo_fecha_fin');
  var labelFechaInicio = document.getElementById('label_fecha_inicio');

  var ini = document.getElementById('fecha_inicio');
  var fin = document.getElementById('fecha_fin');

  var grupoHoras = document.getElementById('grupo_duracion_horas');
  var grupoDias = document.getElementById('grupo_duracion_dias');

  var horas = document.getElementById('duracion_horas');
  var dias = document.getElementById('duracion_dias');

  function parseFechaLocal(valor) {
    var partes = valor.split('-');

    return new Date(
      parseInt(partes[0], 10),
      parseInt(partes[1], 10) - 1,
      parseInt(partes[2], 10)
    );
  }

  function formatoISO(fecha) {
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + '-' + m + '-' + d;
  }

  function sumarMeses(fecha, meses) {
    var f = parseFechaLocal(fecha);
    f.setMonth(f.getMonth() + meses);
    return formatoISO(f);
  }

  var maxFecha = sumarMeses(hoy, 5);

  ini.min = hoy;
  ini.max = maxFecha;
  fin.min = hoy;
  fin.max = maxFecha;

  function normalizar(texto) {
    return String(texto || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function obtenerTextoTipoSeleccionado() {
    if (!tipoSolicitud || tipoSolicitud.selectedIndex < 0) {
      return '';
    }

    var option = tipoSolicitud.options[tipoSolicitud.selectedIndex];
    return normalizar((option.text || '') + ' ' + (option.value || ''));
  }

  function obtenerModoSeleccionado() {
    if (radioHoras.checked) return 'HORAS';
    if (radioDias.checked) return 'DIAS';
    return '';
  }

  function limpiarDuracion() {
    horas.value = '';
    dias.value = '';
  }

  function actualizarEstiloOpciones() {
    cardHoras.classList.toggle('is-selected', radioHoras.checked);
    cardDias.classList.toggle('is-selected', radioDias.checked);
  }

  function sugerirModoPorTipo() {
    var texto = obtenerTextoTipoSeleccionado();

    if (!tipoSolicitud.value) {
      return '';
    }

    if (
      texto.includes('reuniones escolares') ||
      texto.includes('reunion escolar') ||
      texto.includes('cita medica') ||
      texto.includes('cita médica')
    ) {
      return 'HORAS';
    }

    if (
      texto.includes('licencia') ||
      texto.includes('calamidad domestica') ||
      texto.includes('calamidad doméstica') ||
      texto.includes('permiso funebre') ||
      texto.includes('permiso fúnebre') ||
      texto.includes('incapacidad')
    ) {
      return 'DIAS';
    }

    return '';
  }

  function actualizarVistaDuracion() {
    var modo = obtenerModoSeleccionado();
    actualizarEstiloOpciones();

    if (!tipoSolicitud.value) {
      controlTipoDuracion.style.display = 'none';
      ayudaDuracion.style.display = 'none';

      grupoFechaFin.classList.remove('field-hidden');
      labelFechaInicio.textContent = 'Fecha inicio *';

      grupoHoras.classList.add('field-hidden');
      grupoDias.classList.add('field-hidden');

      limpiarDuracion();
      return;
    }

    controlTipoDuracion.style.display = 'block';

    if (!modo) {
      ayudaDuracion.style.display = 'block';
      ayudaDuracion.textContent = 'Selecciona si esta solicitud corresponde a una ausencia parcial del día o a uno o varios días completos.';

      grupoFechaFin.classList.remove('field-hidden');
      labelFechaInicio.textContent = 'Fecha inicio *';

      grupoHoras.classList.add('field-hidden');
      grupoDias.classList.add('field-hidden');

      limpiarDuracion();
      return;
    }

    ayudaDuracion.style.display = 'block';

    if (modo === 'HORAS') {
      ayudaDuracion.textContent = 'Esta solicitud se registrará por horas. Usa esta opción cuando la ausencia solo ocupe una parte del día.';

      labelFechaInicio.textContent = 'Fecha del permiso *';
      grupoFechaFin.classList.add('field-hidden');
      grupoHoras.classList.remove('field-hidden');
      grupoDias.classList.add('field-hidden');

      dias.value = '';

      if (ini.value) {
        fin.value = ini.value;
      }
    }

    if (modo === 'DIAS') {
      ayudaDuracion.textContent = 'Esta solicitud se registrará por días. La duración se calcula automáticamente según la fecha de inicio y la fecha final.';

      labelFechaInicio.textContent = 'Fecha inicio *';
      grupoFechaFin.classList.remove('field-hidden');
      grupoHoras.classList.add('field-hidden');
      grupoDias.classList.remove('field-hidden');

      horas.value = '';
      calcularDuracionDias();
    }
  }

  function calcularDuracionDias() {
    var modo = obtenerModoSeleccionado();

    if (!ini.value) {
      dias.value = '';

      if (modo === 'HORAS') {
        fin.value = '';
      }
      return;
    }

    fin.min = ini.value;
    fin.max = maxFecha;

    if (modo === 'HORAS') {
      fin.value = ini.value;
      dias.value = '';
      return;
    }

    if (modo === 'DIAS') {
      if (!fin.value) {
        fin.value = ini.value;
      }

      if (fin.value < ini.value) {
        fin.value = ini.value;
      }

      var d1 = parseFechaLocal(ini.value);
      var d2 = parseFechaLocal(fin.value);

      var diffDays = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
      dias.value = diffDays > 0 ? diffDays : '';
      horas.value = '';
    }
  }

  tipoSolicitud.addEventListener('change', function () {
    limpiarDuracion();

    radioHoras.checked = false;
    radioDias.checked = false;

    var sugerencia = sugerirModoPorTipo();

    if (sugerencia === 'HORAS') {
      radioHoras.checked = true;
    }

    if (sugerencia === 'DIAS') {
      radioDias.checked = true;
    }

    actualizarVistaDuracion();
    calcularDuracionDias();
  });

  radioHoras.addEventListener('change', function () {
    limpiarDuracion();
    actualizarVistaDuracion();
    calcularDuracionDias();
  });

  radioDias.addEventListener('change', function () {
    limpiarDuracion();
    actualizarVistaDuracion();
    calcularDuracionDias();
  });

  ini.addEventListener('change', function () {
    calcularDuracionDias();
    actualizarVistaDuracion();
  });

  fin.addEventListener('change', function () {
    calcularDuracionDias();
  });

  form.addEventListener('submit', function (e) {
    var modo = obtenerModoSeleccionado();

    if (!tipoSolicitud.value) {
      e.preventDefault();
      alert('Selecciona el tipo de solicitud.');
      tipoSolicitud.focus();
      return;
    }

    if (!modo) {
      e.preventDefault();
      alert('Selecciona si la solicitud se registra por horas o por días.');
      return;
    }

    if (!ini.value) {
      e.preventDefault();
      alert('Selecciona la fecha de inicio.');
      ini.focus();
      return;
    }

    if (modo === 'HORAS') {
      fin.value = ini.value;
      dias.value = '';

      if (!horas.value || parseFloat(horas.value) <= 0) {
        e.preventDefault();
        alert('Ingresa la duración en horas.');
        horas.focus();
        return;
      }
    }

    if (modo === 'DIAS') {
      horas.value = '';

      if (!fin.value) {
        e.preventDefault();
        alert('Selecciona la fecha fin.');
        fin.focus();
        return;
      }

      calcularDuracionDias();

      if (!dias.value || parseInt(dias.value, 10) <= 0) {
        e.preventDefault();
        alert('No se pudo calcular la duración en días. Revisa las fechas.');
        fin.focus();
        return;
      }
    }
  });

  actualizarVistaDuracion();

  // ===== VISTA PREVIA DEL PDF =====
  var pdfInput = document.getElementById('documento_pdf');
  var pdfPreviewContainer = document.getElementById('pdf-preview-container');
  var pdfFilename = document.getElementById('pdf-filename');
  var pdfSize = document.getElementById('pdf-size');
  var pdfIframe = document.getElementById('pdf-iframe');
  var removePdfBtn = document.getElementById('remove-pdf');
  var pdfError = document.getElementById('pdf-error');
  var MAX_FILE_SIZE = 5 * 1024 * 1024;

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

    if (file.type !== 'application/pdf') {
      showPdfError('El archivo debe ser un PDF válido.');
      return;
    }

    if (file.size > MAX_FILE_SIZE) {
      showPdfError('El archivo no puede superar 5MB.');
      return;
    }

    hidePdfError();

    pdfFilename.textContent = file.name;
    pdfSize.textContent = formatFileSize(file.size);

    var fileUrl = URL.createObjectURL(file);
    pdfIframe.src = fileUrl;
    pdfPreviewContainer.style.display = 'block';
  });

  removePdfBtn.addEventListener('click', function() {
    resetPdfPreview();
  });

  window.addEventListener('beforeunload', function() {
    if (pdfIframe.src && pdfIframe.src.startsWith('blob:')) {
      URL.revokeObjectURL(pdfIframe.src);
    }
  });

})();
</script>