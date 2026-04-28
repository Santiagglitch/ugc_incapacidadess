<section class="page-header animate-fade-down">
  <div>
    <h1 class="page-title">Nueva solicitud</h1>
    <p style="color:var(--muted)">Registra una solicitud con soporte PDF para revision de tu jefe inmediato.</p>
  </div>
  <a class="btn btn-outline" href="<?= e(url_view('dashboard')) ?>">Volver</a>
</section>

<div class="form-card">
  <form method="post" action="<?= e(url_action('solicitud_create')) ?>" enctype="multipart/form-data" autocomplete="off">
    <?= csrf_input() ?>
    <?php if (empty($esAprendiz)): ?>
      <input type="hidden" name="nit_jefe" value="<?= e($user['nit_jefe'] ?? '') ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="form-group">
        <label for="tipo_solicitud">Tipo de solicitud</label>
        <select id="tipo_solicitud" name="tipo_solicitud" required>
          <option value="">Selecciona...</option>
          <?php foreach (TIPOS_SOLICITUD as $key => $label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (!empty($esAprendiz)): ?>
        <div class="form-group">
          <label for="nit_jefe_seleccionado">Jefe que revisa la solicitud</label>
          <select id="nit_jefe_seleccionado" name="nit_jefe_seleccionado" required>
            <option value="">Selecciona...</option>
            <?php foreach (($jefes ?? []) as $jefe): ?>
              <option value="<?= e($jefe['NIT'] ?? '') ?>">
                <?= e($jefe['NOMBRE_COMPLETO'] ?? $jefe['NIT'] ?? '') ?>
                <?php if (!empty($jefe['CENTRO_COSTO'])): ?>
                  - CC <?= e($jefe['CENTRO_COSTO']) ?>
                <?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="field-hint">Para aprendices o pasantes se permite escoger el jefe responsable.</small>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="fecha_inicio">Fecha inicio</label>
        <input id="fecha_inicio" name="fecha_inicio" type="date" required value="<?= e(date('Y-m-d')) ?>">
      </div>

      <div class="form-group">
        <label for="fecha_fin">Fecha fin</label>
        <input id="fecha_fin" name="fecha_fin" type="date" required value="<?= e(date('Y-m-d')) ?>">
      </div>

      <div class="form-group">
        <label for="duracion_horas">Duracion horas</label>
        <input id="duracion_horas" name="duracion_horas" type="number" min="0" max="999" step="0.5" placeholder="Opcional">
      </div>

      <div class="form-group">
        <label for="duracion_dias">Duracion dias</label>
        <input id="duracion_dias" name="duracion_dias" type="number" min="0" max="365" step="0.5" placeholder="Opcional">
      </div>

      <div class="form-group">
        <label for="documento_pdf">Documento PDF</label>
        <div class="file-upload-container">
          <input id="documento_pdf" name="documento_pdf" type="file" accept="application/pdf,.pdf" required>
        </div>
        <small class="field-hint">Maximo 5MB. Solo PDF.</small>
        <div id="pdf-error" class="file-error" style="display:none"></div>
      </div>
    </div>

    <div class="form-group">
      <label for="observaciones">Observaciones</label>
      <textarea id="observaciones" name="observaciones" rows="5" maxlength="2000" placeholder="Describe brevemente la solicitud"></textarea>
    </div>

    <?php if (empty($esAprendiz) && !empty($user['nombre_jefe'])): ?>
      <div class="alert" style="margin:14px 0;background:var(--green3);border:1px solid var(--green4);color:var(--green)">
        Jefe asignado: <strong><?= e($user['nombre_jefe']) ?></strong> (<?= e($user['nit_jefe'] ?? '') ?>)
      </div>
    <?php elseif (!empty($esAprendiz) && empty($jefes)): ?>
      <div class="alert alert-error" style="margin:14px 0">
        No se encontraron jefes disponibles para asignar la solicitud.
      </div>
    <?php endif; ?>

    <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:18px">
      <a class="btn btn-gray" href="<?= e(url_view('dashboard')) ?>">Cancelar</a>
      <button class="btn btn-green" type="submit">Guardar solicitud</button>
    </div>
  </form>
</div>

<script>
(function () {
  var input = document.getElementById('documento_pdf');
  var error = document.getElementById('pdf-error');
  if (!input || !error) {
    return;
  }

  input.addEventListener('change', function () {
    error.style.display = 'none';
    error.textContent = '';
    var file = input.files && input.files[0] ? input.files[0] : null;
    if (!file) {
      return;
    }

    var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
    if (!isPdf) {
      input.value = '';
      error.textContent = 'Selecciona un archivo PDF valido.';
      error.style.display = 'block';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      input.value = '';
      error.textContent = 'El PDF no puede superar 5MB.';
      error.style.display = 'block';
    }
  });
})();
</script>
