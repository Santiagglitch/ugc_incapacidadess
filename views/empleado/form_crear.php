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
  .page-header {
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
  }

  .page-header > div {
    width: 100%;
  }

  .page-title {
    text-align: center;
  }

  :root {
    --ugreen: #005c2b;
    --ugreen-dark: #00451f;
    --ugreen-soft: #f2faf5;
    --utext: #111827;
    --utext-soft: #4b5563;
    --uborder: #dbe5ee;
    --ubg-soft: #f8fbfa;
  }

  .form-section-box {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px;
    background: #ffffff;
    margin-top: 4px;
  }

  .form-section-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--utext) !important;
    margin-bottom: 5px;
  }

  .form-section-subtitle {
    font-size: 13px;
    color: var(--utext-soft) !important;
    margin-bottom: 16px;
    line-height: 1.45;
  }

  .duration-type-box {
    display: none;
  }

  .duration-header-line {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  .duration-header-text {
    flex: 1;
  }

  .hd-toggle-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 4px;
    white-space: nowrap;
  }

  .hd-toggle-label {
    font-size: 13px;
    font-weight: 800;
    color: var(--utext) !important;
  }

  .hd-switch {
    position: relative;
    display: inline-block;
    width: 58px;
    height: 32px;
  }

  .hd-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .hd-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #ffffff;
    border: 2px solid #c9d9e2;
    border-radius: 999px;
    transition: all .22s ease;
  }

  .hd-slider::before {
    content: "";
    position: absolute;
    width: 22px;
    height: 22px;
    left: 3px;
    top: 3px;
    background: #9bd3df;
    border-radius: 50%;
    transition: all .22s ease;
  }

  .hd-switch input:checked + .hd-slider {
    border-color: var(--ugreen);
    background: var(--ugreen-soft);
  }

  .hd-switch input:checked + .hd-slider::before {
    transform: translateX(26px);
    background: var(--ugreen);
  }

  .duration-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(220px, 1fr));
    gap: 14px;
    margin-top: 12px;
  }

  #control_tipo_duracion .duration-option,
  #control_tipo_duracion .duration-option * {
    color: var(--utext) !important;
  }

  #control_tipo_duracion .duration-option-text {
    color: var(--utext-soft) !important;
  }

  .duration-option {
    position: relative;
    display: block;
    padding: 18px;
    border: 1px solid var(--uborder);
    border-radius: 18px;
    background: #ffffff;
    cursor: pointer;
    transition: all .22s ease;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    min-height: 120px;
    overflow: hidden;
  }

  .duration-option::before,
  .duration-option::after {
    display: none !important;
    content: none !important;
  }

  .duration-option:hover {
    transform: translateY(-1px);
    border-color: var(--ugreen);
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
  }

  .duration-option.is-selected {
    border-color: var(--ugreen);
    background: var(--ugreen-soft);
    box-shadow: 0 0 0 3px rgba(0, 92, 43, 0.12);
  }

  .duration-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .duration-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #eef8f2;
    color: var(--ugreen) !important;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 14px;
    margin-bottom: 12px;
  }

  .duration-option.is-selected .duration-icon {
    background: var(--ugreen);
    color: #ffffff !important;
  }

  .duration-option-title {
    display: block;
    font-weight: 800;
    font-size: 15px;
    line-height: 1.2;
    margin-bottom: 6px;
    color: var(--utext) !important;
  }

  .duration-option-text {
    display: block;
    font-size: 12.8px;
    line-height: 1.45;
    color: var(--utext-soft) !important;
  }

  .duration-help {
    display: none;
    margin-top: 14px;
    padding: 13px 14px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #dbe5ee;
    color: var(--utext-soft) !important;
    font-size: 13px;
    line-height: 1.5;
  }

  .smart-form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .duration-summary {
    display: none;
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    color: var(--utext-soft) !important;
    font-size: 13px;
    line-height: 1.45;
  }

  .duration-summary strong {
    color: var(--utext) !important;
  }

  .field-hidden {
    display: none !important;
  }

  @media (max-width: 768px) {
    .duration-header-line {
      flex-direction: column;
    }

    .hd-toggle-wrap {
      width: 100%;
      justify-content: space-between;
      padding-top: 0;
    }

    .duration-options,
    .smart-form-row {
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
        <?php if (app_feature_enabled('SELECCION_JEFES')): ?>
        <select name="nit_jefe_seleccionado" required>
          <option value="">Selecciona...</option>
          <?php foreach (($jefes ?? []) as $jefe): ?>
            <option value="<?= e($jefe['NIT']) ?>">
              <?= e(trim((string)($jefe['NOMBRE_COMPLETO'] ?? ''))) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php else: ?>
          <input type="text" aria-label="Jefe asignado" value="<?= e((string)($jefeFijo['NOMBRE_JEFE'] ?? '')) ?>" readonly>
        <?php endif; ?>
      </div>

      <!-- SELECCIÓN DE DURACIÓN -->
      <div class="form-group duration-type-box" id="control_tipo_duracion">
        <div class="form-section-box">

          <div class="duration-header-line">
            <div class="duration-header-text">
              <div class="form-section-title">Tipo de duración</div>
              <div class="form-section-subtitle">
                Selecciona cómo se va a registrar la solicitud. Para registrar días completos más horas adicionales, activa la opción <strong>Días + horas adicionales</strong>.
              </div>
            </div>

            <div class="hd-toggle-wrap">
              <span class="hd-toggle-label">Días + horas adicionales</span>
              <label class="hd-switch" title="Registrar días completos más horas adicionales">
                <input type="checkbox" id="toggle_tipo_ambas">
                <span class="hd-slider"></span>
              </label>
            </div>
          </div>

          <div class="duration-options">
            <label class="duration-option" id="card_tipo_horas">
              <input type="radio" id="tipo_duracion_horas" name="tipo_duracion" value="HORAS">
              <span class="duration-icon">H</span>
              <span class="duration-option-title">Por horas</span>
              <span class="duration-option-text">Para permisos cortos dentro del mismo día.</span>
            </label>

            <label class="duration-option" id="card_tipo_dias">
              <input type="radio" id="tipo_duracion_dias" name="tipo_duracion" value="DIAS">
              <span class="duration-icon">D</span>
              <span class="duration-option-title">Por días</span>
              <span class="duration-option-text">Para ausencias de uno o varios días completos.</span>
            </label>

            <input type="radio" id="tipo_duracion_ambas" name="tipo_duracion" value="AMBAS" hidden>
          </div>

          <div class="duration-help" id="ayuda_duracion"></div>
        </div>
      </div>

      <!-- FECHAS Y DURACIÓN -->
      <div class="form-group field-hidden" id="bloque_fechas_duracion">
        <div class="form-section-box">
          <div class="form-section-title">Fechas y duración</div>
          <div class="form-section-subtitle" id="subtitulo_fechas">
            Selecciona el tipo de duración para habilitar los campos correspondientes.
          </div>

          <div class="smart-form-row">
            <div class="form-group" id="grupo_fecha_inicio">
              <label id="label_fecha_inicio">Fecha inicio *</label>
              <input type="date" id="fecha_inicio" name="fecha_inicio"
                     min="<?= htmlspecialchars($hoy) ?>">
            </div>

            <div class="form-group" id="grupo_fecha_fin">
              <label>Fecha fin *</label>
              <input type="date" id="fecha_fin" name="fecha_fin"
                     min="<?= htmlspecialchars($hoy) ?>">
            </div>
          </div>

          <div class="smart-form-row" style="margin-top: 14px;">
            <div class="form-group" id="grupo_duracion_horas">
              <label id="label_duracion_horas">Duración en horas *</label>
              <input type="number" id="duracion_horas" name="duracion_horas"
                     min="0.5" max="5" step="0.5" placeholder="Ej: 2.5">
            </div>

            <div class="form-group" id="grupo_duracion_dias">
              <label>Duración en días</label>
              <input type="number" id="duracion_dias" name="duracion_dias"
                     readonly placeholder="Se calcula automáticamente">
            </div>
          </div>

          <div class="duration-summary" id="resumen_duracion"></div>
        </div>
      </div>

      <!-- OBSERVACIONES -->
      <div class="form-group">
        <label>Observaciones</label>
        <textarea name="observaciones" rows="4" placeholder="Escribe una breve descripción o aclaración de la solicitud..."></textarea>
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

<script src="<?= e(app_asset('assets/js/jefe-picker.js')) ?>"></script>
<script src="<?= e(app_asset('assets/vendor/pdfjs/pdf.min.js')) ?>"></script>
<script src="<?= e(app_asset('assets/js/pdf-mobile-preview.js')) ?>"></script>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {

  var hoy = <?= json_encode($hoy) ?>;

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

  function esDomingo(fecha) {
    return fecha.getDay() === 0;
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
      if (!esDomingo(actual)) {
        total++;
      }

      actual.setDate(actual.getDate() + 1);
    }

    return total;
  }

  var maxFecha = sumarMeses(hoy, 5);

  ini.min = hoy;
  ini.max = maxFecha;

  fin.min = hoy;
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

  function validarMensajeHorasNativo() {
    var valorHoras = parseFloat(horas.value);

    horas.setCustomValidity('');

    if (!horas.value) {
      return;
    }

    if (isNaN(valorHoras)) {
      horas.setCustomValidity('Ingresa un valor válido en horas.');
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
      resumenDuracion.innerHTML = '<strong>Resumen:</strong> el empleado pidió <strong>' + horas.value + ' hora(s)</strong>.';
    }

    if (modo === 'DIAS' && dias.value) {
      resumenDuracion.style.display = 'block';
      resumenDuracion.innerHTML = '<strong>Resumen:</strong> el empleado pidió <strong>' + dias.value + ' día(s) hábil(es)</strong>. No se cuentan los domingos.';
    }

    if (modo === 'AMBAS' && dias.value && horas.value) {
      resumenDuracion.style.display = 'block';
      resumenDuracion.innerHTML = '<strong>Resumen:</strong> el empleado pidió <strong>' + dias.value + ' día(s) hábil(es)</strong> más <strong>' + horas.value + ' hora(s) adicional(es)</strong>. No se cuentan los domingos.';
    }
  }

  function actualizarVistaDuracion() {
    var modo = obtenerModoSeleccionado();

    actualizarEstiloOpciones();

    ini.required = false;
    fin.required = false;
    horas.required = false;

    horas.placeholder = 'Ej: 4';
    labelDuracionHoras.textContent = 'Duración en horas *';

    if (!tipoSolicitud.value) {
      controlTipoDuracion.style.display = 'none';
      bloqueFechasDuracion.classList.add('field-hidden');

      grupoFechaFin.classList.remove('field-hidden');
      grupoHoras.classList.add('field-hidden');
      grupoDias.classList.add('field-hidden');

      limpiarDuracion();
      return;
    }

    controlTipoDuracion.style.display = 'block';

    if (!modo) {
      bloqueFechasDuracion.classList.add('field-hidden');
      ayudaDuracion.style.display = 'block';
      ayudaDuracion.textContent = 'Selecciona una opción o activa “Días + horas adicionales” para registrar días completos más horas adicionales.';
      limpiarDuracion();
      return;
    }

    bloqueFechasDuracion.classList.remove('field-hidden');
    ayudaDuracion.style.display = 'none';

    ini.required = true;

    if (modo === 'HORAS') {
      subtituloFechas.textContent = 'Selecciona la fecha del permiso e ingresa la cantidad de horas solicitadas. Debe ser mayor a 0 y máximo 5 horas.';

      labelFechaInicio.textContent = 'Fecha del permiso *';
      labelDuracionHoras.textContent = 'Duración en horas *';
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
      subtituloFechas.textContent = 'Selecciona el rango de fechas. El sistema calculará automáticamente los días hábiles, sin contar domingos.';

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
      subtituloFechas.textContent = 'Selecciona los días hábiles de ausencia y agrega las horas adicionales. No se cuentan los domingos. Ejemplo: 1 día hábil completo + 4 horas adicionales.';

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

  function validarHorasPermitidas(mensajeVacio) {
    var valorHoras = parseFloat(horas.value);

    validarMensajeHorasNativo();

    if (!horas.value || isNaN(valorHoras)) {
      alert(mensajeVacio);
      horas.focus();
      return false;
    }

    if (valorHoras <= 0 || valorHoras > 5) {
      alert('No se puede registrar la solicitud. La duración debe ser mayor a 0 y máximo 5 horas.');
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
      alert('No se puede registrar la solicitud porque el rango seleccionado no tiene días hábiles. Los domingos no cuentan.');
      fin.focus();
      return false;
    }

    return true;
  }

  tipoSolicitud.addEventListener('change', function () {
    radioHoras.checked = false;
    radioDias.checked = false;
    radioAmbas.checked = false;
    toggleAmbas.checked = false;

    ini.value = '';
    fin.value = '';
    limpiarDuracion();

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
      alert('Selecciona si la solicitud se registra por horas, por días o activa días + horas adicionales.');
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

      if (!validarHorasPermitidas('Ingresa la duración en horas.')) {
        e.preventDefault();
        return;
      }
    }

    if (modo === 'DIAS') {
      horas.value = '';
      horas.setCustomValidity('');

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
      alert('Corrige el archivo PDF antes de enviar la solicitud.');
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
