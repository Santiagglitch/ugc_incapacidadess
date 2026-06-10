<?php
$solicitud = $solicitud ?? [];
$user = $user ?? usuario_actual();

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

$duracionHorasNumero = is_numeric($duracionHoras) ? (float)$duracionHoras : 0.0;
$duracionDiasNumero = is_numeric($duracionDias) ? (float)$duracionDias : 0.0;
$modoDuracion = '';

if ($duracionHorasNumero > 0 && $duracionDiasNumero > 0) {
    $modoDuracion = 'AMBAS';
} elseif ($duracionDiasNumero > 0) {
    $modoDuracion = 'DIAS';
} elseif ($duracionHorasNumero > 0) {
    $modoDuracion = 'HORAS';
}

$minFechaInicio = $fechaInicio !== '' && $fechaInicio < $hoy ? $fechaInicio : $hoy;
$minFechaFin = $fechaFin !== '' && $fechaFin < $hoy ? $fechaFin : $hoy;

$returnTo = url_retorno_segura($_GET['return_to'] ?? null, url_view('dashboard'));
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

    <div class="form-grid">

      <!-- TIPO -->
      <div class="form-group">
        <label>Tipo de solicitud</label>
        <select id="tipo_solicitud" name="tipo_solicitud" required>
          <option value="">Selecciona...</option>

          <?php foreach (TIPOS_SOLICITUD as $key => $label): ?>
            <option value="<?= e((string)$key) ?>" <?= $tipoActual === (string)$key ? 'selected' : '' ?>>
              <?= e((string)$label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- JEFE -->
      <div class="form-group">
        <label>Jefe que revisa</label>
        <select name="nit_jefe_seleccionado" required>
          <option value="">Selecciona...</option>

          <?php foreach (($jefes ?? []) as $jefe): ?>
            <option
              value="<?= e($jefe['NIT']) ?>"
              <?= (string)($solicitud['NIT_JEFE'] ?? '') === (string)($jefe['NIT'] ?? '') ? 'selected' : '' ?>
            >
              <?= e(trim((string)($jefe['NOMBRE_COMPLETO'] ?? ''))) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- SELECCION DE DURACION -->
      <div class="form-group duration-type-box" id="control_tipo_duracion">
        <div class="form-section-box">
          <div class="duration-header-line">
            <div class="duration-header-text">
              <div class="form-section-title">Tipo de duracion</div>
              <div class="form-section-subtitle">
                Ajusta si la solicitud se registra por horas, por dias completos o por dias mas horas adicionales.
              </div>
            </div>

            <div class="hd-toggle-wrap">
              <span class="hd-toggle-label">Dias + horas adicionales</span>
              <label class="hd-switch" title="Registrar dias completos mas horas adicionales">
                <input type="checkbox" id="toggle_tipo_ambas" <?= $modoDuracion === 'AMBAS' ? 'checked' : '' ?>>
                <span class="hd-slider"></span>
              </label>
            </div>
          </div>

          <div class="duration-options">
            <label class="duration-option" id="card_tipo_horas">
              <input type="radio" id="tipo_duracion_horas" name="tipo_duracion" value="HORAS" <?= $modoDuracion === 'HORAS' ? 'checked' : '' ?>>
              <span class="duration-icon">H</span>
              <span class="duration-option-title">Por horas</span>
              <span class="duration-option-text">Para permisos cortos dentro del mismo dia.</span>
            </label>

            <label class="duration-option" id="card_tipo_dias">
              <input type="radio" id="tipo_duracion_dias" name="tipo_duracion" value="DIAS" <?= $modoDuracion === 'DIAS' ? 'checked' : '' ?>>
              <span class="duration-icon">D</span>
              <span class="duration-option-title">Por dias</span>
              <span class="duration-option-text">Para ausencias de uno o varios dias completos.</span>
            </label>

            <input type="radio" id="tipo_duracion_ambas" name="tipo_duracion" value="AMBAS" <?= $modoDuracion === 'AMBAS' ? 'checked' : '' ?> hidden>
          </div>

          <div class="duration-help" id="ayuda_duracion"></div>
        </div>
      </div>

      <!-- FECHAS Y DURACION -->
      <div class="form-group field-hidden" id="bloque_fechas_duracion">
        <div class="form-section-box">
          <div class="form-section-title">Fechas y duracion</div>
          <div class="form-section-subtitle" id="subtitulo_fechas">
            Selecciona el tipo de duracion para habilitar los campos correspondientes.
          </div>

          <div class="smart-form-row">
            <div class="form-group" id="grupo_fecha_inicio">
              <label id="label_fecha_inicio">Fecha inicio *</label>
              <input
                type="date"
                id="fecha_inicio"
                name="fecha_inicio"
                value="<?= e($fechaInicio) ?>"
                min="<?= e($minFechaInicio) ?>"
              >
            </div>

            <div class="form-group" id="grupo_fecha_fin">
              <label>Fecha fin *</label>
              <input
                type="date"
                id="fecha_fin"
                name="fecha_fin"
                value="<?= e($fechaFin) ?>"
                min="<?= e($minFechaFin) ?>"
              >
            </div>
          </div>

          <div class="smart-form-row" style="margin-top: 14px;">
            <div class="form-group" id="grupo_duracion_horas">
              <label id="label_duracion_horas">Duracion en horas *</label>
              <input
                type="number"
                id="duracion_horas"
                name="duracion_horas"
                value="<?= e($duracionHoras) ?>"
                min="0.5"
                max="8"
                step="0.5"
                placeholder="Ej: 2.5"
              >
            </div>

            <div class="form-group" id="grupo_duracion_dias">
              <label>Duracion en dias</label>
              <input
                type="number"
                id="duracion_dias"
                name="duracion_dias"
                value="<?= e($duracionDias) ?>"
                readonly
                placeholder="Se calcula automaticamente"
              >
            </div>
          </div>

          <div class="duration-summary" id="resumen_duracion"></div>
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
          <div class="pdf-current-file">
            <div class="pdf-current-header">
              <svg class="pdf-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
              </svg>
              <span class="pdf-current-label">Documento actual cargado</span>
            </div>
            <a href="<?= e(url_view('solicitud_archivo') . '&id=' . urlencode($id)) ?>" target="_blank" class="pdf-view-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              Ver PDF cargado
            </a>
          </div>
        <?php endif; ?>

        <input type="file" id="documento_pdf" name="documento_pdf" accept=".pdf">
        <small style="display:block;margin-top:8px;color:var(--muted)">
          Si no seleccionas un nuevo PDF, se conserva el documento actual.
        </small>

        <!-- Vista previa del nuevo PDF -->
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
      <button type="submit" class="btn btn-green">Guardar cambios</button>
      <a href="<?= e($returnTo) ?>" class="btn btn-gray" data-click-once>Cancelar</a>
    </div>

  </form>
</div>

<script src="<?= e(app_asset('assets/js/jefe-picker.js')) ?>"></script>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {

  var hoy = <?= json_encode($hoy) ?>;
  var minFechaPermitida = <?= json_encode($minFechaInicio) ?>;

  var form = document.getElementById('formSolicitud');
  var tipoSolicitud = document.getElementById('tipo_solicitud');
  var jefePicker = window.initJefePicker
    ? window.initJefePicker(form.querySelector('select[name="nit_jefe_seleccionado"]'))
    : null;

  var controlTipoDuracion = document.getElementById('control_tipo_duracion');
  var bloqueFechasDuracion = document.getElementById('bloque_fechas_duracion');

  var radioHoras = document.getElementById('tipo_duracion_horas');
  var radioDias = document.getElementById('tipo_duracion_dias');
  var radioAmbas = document.getElementById('tipo_duracion_ambas');
  var toggleAmbas = document.getElementById('toggle_tipo_ambas');

  var cardHoras = document.getElementById('card_tipo_horas');
  var cardDias = document.getElementById('card_tipo_dias');

  var ayudaDuracion = document.getElementById('ayuda_duracion');
  var subtituloFechas = document.getElementById('subtitulo_fechas');
  var resumenDuracion = document.getElementById('resumen_duracion');

  var grupoFechaFin = document.getElementById('grupo_fecha_fin');
  var labelFechaInicio = document.getElementById('label_fecha_inicio');

  var ini = document.getElementById('fecha_inicio');
  var fin = document.getElementById('fecha_fin');
  var grupoHoras = document.getElementById('grupo_duracion_horas');
  var grupoDias = document.getElementById('grupo_duracion_dias');
  var labelDuracionHoras = document.getElementById('label_duracion_horas');
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

  function esSabadoODomingo(fecha) {
    var diaSemana = fecha.getDay();

    return diaSemana === 0 || diaSemana === 6;
  }

  function calcularDiasHabiles(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) {
      return 0;
    }

    var inicio = parseFechaLocal(fechaInicio);
    var final = parseFechaLocal(fechaFin);

    if (final < inicio) {
      return 0;
    }

    var total = 0;
    var actual = new Date(inicio);

    while (actual <= final) {
      if (!esSabadoODomingo(actual)) {
        total++;
      }

      actual.setDate(actual.getDate() + 1);
    }

    return total;
  }

  var maxFecha = sumarMeses(hoy, 5);

  ini.min = minFechaPermitida;
  ini.max = maxFecha;
  fin.min = minFechaPermitida;
  fin.max = maxFecha;

  function obtenerModoSeleccionado() {
    if (radioAmbas.checked) return 'AMBAS';
    if (radioHoras.checked) return 'HORAS';
    if (radioDias.checked) return 'DIAS';
    return '';
  }

  function limpiarDuracion() {
    horas.value = '';
    horas.setCustomValidity('');

    dias.value = '';
    resumenDuracion.style.display = 'none';
    resumenDuracion.innerHTML = '';
  }

  function actualizarEstiloOpciones() {
    var modo = obtenerModoSeleccionado();

    cardHoras.classList.toggle('is-selected', modo === 'HORAS' || modo === 'AMBAS');
    cardDias.classList.toggle('is-selected', modo === 'DIAS' || modo === 'AMBAS');
    toggleAmbas.checked = modo === 'AMBAS';
  }

  function actualizarResumen() {
    var modo = obtenerModoSeleccionado();

    resumenDuracion.style.display = 'none';
    resumenDuracion.innerHTML = '';

    if (!modo || !ini.value) {
      return;
    }

    if (modo === 'HORAS' && horas.value) {
      resumenDuracion.style.display = 'block';
      resumenDuracion.innerHTML = '<strong>Resumen:</strong> la solicitud queda por <strong>' + horas.value + ' hora(s)</strong>.';
    }

    if (modo === 'DIAS' && dias.value) {
      resumenDuracion.style.display = 'block';
      resumenDuracion.innerHTML = '<strong>Resumen:</strong> la solicitud queda por <strong>' + dias.value + ' dia(s) habil(es)</strong>. No se cuentan sabados ni domingos.';
    }

    if (modo === 'AMBAS' && dias.value && horas.value) {
      resumenDuracion.style.display = 'block';
      resumenDuracion.innerHTML = '<strong>Resumen:</strong> la solicitud queda por <strong>' + dias.value + ' dia(s) habil(es)</strong> mas <strong>' + horas.value + ' hora(s) adicional(es)</strong>. No se cuentan sabados ni domingos.';
    }
  }

  function validarMensajeHorasNativo() {
    var valorHoras = parseFloat(horas.value);

    horas.setCustomValidity('');

    if (!horas.value) {
      return;
    }

    if (isNaN(valorHoras)) {
      horas.setCustomValidity('Ingresa un valor valido en horas.');
      return;
    }

    if (valorHoras <= 0) {
      horas.setCustomValidity('El valor debe ser mayor a 0 horas.');
      return;
    }
if (valorHoras > 5) {
  horas.setCustomValidity('El valor debe ser máximo 5 horas.');
  return;
}

    if (Math.abs((valorHoras * 2) - Math.round(valorHoras * 2)) > 0.00001) {
      horas.setCustomValidity('Usa incrementos de media hora.');
    }
  }

  function validarHorasPermitidas(mensajeVacio) {
    var valorHoras = parseFloat(horas.value);

    validarMensajeHorasNativo();

    if (!horas.value || isNaN(valorHoras)) {
      alert(mensajeVacio);
      horas.focus();
      return false;
    }

    if (valorHoras <= 0 || valorHoras > 8) {
      alert('No se puede guardar la solicitud. La duracion debe ser mayor a 0 y maximo 8 horas.');
      horas.value = '';
      horas.setCustomValidity('');
      actualizarResumen();
      horas.focus();
      return false;
    }

    if (Math.abs((valorHoras * 2) - Math.round(valorHoras * 2)) > 0.00001) {
      alert('La duracion en horas debe registrarse en incrementos de media hora.');
      horas.focus();
      return false;
    }

    return true;
  }

  function validarDiasPermitidos() {
    var valorDias = parseInt(dias.value, 10);

    if (!dias.value || isNaN(valorDias) || valorDias <= 0) {
      alert('No se puede guardar la solicitud porque el rango seleccionado no tiene dias habiles. Los sabados y domingos no cuentan.');
      fin.focus();
      return false;
    }

    return true;
  }

  function calcularDuracionDias() {
    var modo = obtenerModoSeleccionado();

    if (!ini.value) {
      dias.value = '';

      if (modo === 'HORAS') {
        fin.value = '';
      }

      actualizarResumen();
      return;
    }

    fin.min = ini.value;
    fin.max = maxFecha;

    if (modo === 'HORAS') {
      fin.value = ini.value;
      dias.value = '';
      actualizarResumen();
      return;
    }

    if (modo === 'DIAS' || modo === 'AMBAS') {
      if (!fin.value) {
        fin.value = ini.value;
      }

      if (fin.value < ini.value) {
        fin.value = ini.value;
      }

      var diasHabiles = calcularDiasHabiles(ini.value, fin.value);

      dias.value = diasHabiles > 0 ? diasHabiles : '';
    }

    if (modo === 'DIAS') {
      horas.value = '';
      horas.setCustomValidity('');
    }

    actualizarResumen();
  }

  function actualizarVistaDuracion() {
    var modo = obtenerModoSeleccionado();

    actualizarEstiloOpciones();

    ini.required = false;
    fin.required = false;
    horas.required = false;
    horas.setCustomValidity('');

    horas.placeholder = 'Ej: 2.5';
    labelDuracionHoras.textContent = 'Duracion en horas *';

    if (!tipoSolicitud.value) {
      controlTipoDuracion.style.display = 'none';
      bloqueFechasDuracion.classList.add('field-hidden');
      limpiarDuracion();
      return;
    }

    controlTipoDuracion.style.display = 'block';

    if (!modo) {
      bloqueFechasDuracion.classList.add('field-hidden');
      ayudaDuracion.style.display = 'block';
      ayudaDuracion.textContent = 'Selecciona una opcion o activa dias + horas adicionales para completar la duracion.';
      return;
    }

    bloqueFechasDuracion.classList.remove('field-hidden');
    ayudaDuracion.style.display = 'none';
    ini.required = true;

    if (modo === 'HORAS') {
      subtituloFechas.textContent = 'Selecciona la fecha del permiso e ingresa la cantidad de horas solicitadas.';
      labelFechaInicio.textContent = 'Fecha del permiso *';
      labelDuracionHoras.textContent = 'Duracion en horas *';
      horas.placeholder = 'Ej: 4';

      grupoFechaFin.classList.add('field-hidden');
      grupoHoras.classList.remove('field-hidden');
      grupoDias.classList.add('field-hidden');

      fin.required = false;
      horas.required = true;
      dias.value = '';

      if (ini.value) {
        fin.value = ini.value;
      }
    }

    if (modo === 'DIAS') {
      subtituloFechas.textContent = 'Selecciona el rango de fechas. El sistema calcula automaticamente solo los dias habiles, sin contar sabados ni domingos.';
      labelFechaInicio.textContent = 'Fecha inicio *';

      grupoFechaFin.classList.remove('field-hidden');
      grupoHoras.classList.add('field-hidden');
      grupoDias.classList.remove('field-hidden');

      fin.required = true;
      horas.required = false;
      horas.value = '';
      horas.setCustomValidity('');
      calcularDuracionDias();
    }

    if (modo === 'AMBAS') {
      subtituloFechas.textContent = 'Selecciona los dias habiles de ausencia y agrega las horas adicionales. No se cuentan sabados ni domingos.';
      labelFechaInicio.textContent = 'Fecha inicio *';
      labelDuracionHoras.textContent = 'Horas adicionales *';
      horas.placeholder = 'Ej: 4';

      grupoFechaFin.classList.remove('field-hidden');
      grupoHoras.classList.remove('field-hidden');
      grupoDias.classList.remove('field-hidden');

      fin.required = true;
      horas.required = true;
      calcularDuracionDias();
    }

    validarMensajeHorasNativo();
    actualizarResumen();
  }

  tipoSolicitud.addEventListener('change', function () {
    actualizarVistaDuracion();
  });

  radioHoras.addEventListener('change', function () {
    radioAmbas.checked = false;
    toggleAmbas.checked = false;
    limpiarDuracion();
    actualizarVistaDuracion();
  });

  radioDias.addEventListener('change', function () {
    radioAmbas.checked = false;
    toggleAmbas.checked = false;
    limpiarDuracion();
    actualizarVistaDuracion();
  });

  toggleAmbas.addEventListener('change', function () {
    if (toggleAmbas.checked) {
      radioHoras.checked = false;
      radioDias.checked = false;
      radioAmbas.checked = true;
    } else {
      radioAmbas.checked = false;
    }

    limpiarDuracion();
    actualizarVistaDuracion();
  });

  ini.addEventListener('change', function () {
    calcularDuracionDias();
    actualizarVistaDuracion();
  });

  fin.addEventListener('change', function () {
    calcularDuracionDias();
  });

  horas.addEventListener('input', function () {
    validarMensajeHorasNativo();
    actualizarResumen();
  });

  horas.addEventListener('change', function () {
    validarMensajeHorasNativo();
    actualizarResumen();
  });

  horas.addEventListener('invalid', function () {
    validarMensajeHorasNativo();
  });

  form.addEventListener('submit', function (e) {
    var modo = obtenerModoSeleccionado();

    if (!tipoSolicitud.value) {
      e.preventDefault();
      alert('Selecciona el tipo de solicitud.');
      tipoSolicitud.focus();
      return;
    }

    if (jefePicker && !jefePicker.isValid()) {
      e.preventDefault();
      alert('Selecciona el jefe que revisara esta solicitud.');
      jefePicker.focus();
      return;
    }

    if (!modo) {
      e.preventDefault();
      alert('Selecciona si la solicitud se registra por horas, por dias o por dias mas horas adicionales.');
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

      if (!validarHorasPermitidas('Ingresa la duracion en horas.')) {
        e.preventDefault();
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

      if (!validarDiasPermitidos()) {
        e.preventDefault();
        return;
      }
    }

    if (modo === 'AMBAS') {
      if (!fin.value) {
        e.preventDefault();
        alert('Selecciona la fecha fin.');
        fin.focus();
        return;
      }

      calcularDuracionDias();

      if (!validarDiasPermitidos()) {
        e.preventDefault();
        return;
      }

      if (!validarHorasPermitidas('Ingresa las horas adicionales.')) {
        e.preventDefault();
        return;
      }
    }

    var errorPdfActual = document.getElementById('pdf-error');

    if (errorPdfActual && errorPdfActual.style.display !== 'none' && errorPdfActual.textContent.trim() !== '') {
      e.preventDefault();
      alert('Corrige el archivo PDF antes de guardar la solicitud.');
      document.getElementById('documento_pdf').focus();
      return;
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
