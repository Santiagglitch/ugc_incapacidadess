<?php
require_once __DIR__ . '/pagination.php';

$solicitudes = $solicitudes ?? [];
$user = $user ?? usuario_actual();
$paginationParam = $paginationParam ?? 'pagina';
$paginationLabel = $paginationLabel ?? 'solicitudes';
$paginationData = $paginationData ?? null;

if (is_array($paginationData)) {
  $pagination = $paginationData + [
    'current' => 1,
    'totalPages' => 1,
    'total' => count($solicitudes),
    'perPage' => APP_ROWS_PER_PAGE,
  ];
  $pagination['paramName'] = $paginationParam;
  $solicitudesPagina = $solicitudes;
} else {
  $pagination = ugcPaginateRows($solicitudes, $paginationParam, APP_ROWS_PER_PAGE);
  $solicitudesPagina = $pagination['rows'];
}

$paginationData = null;
$returnTo = app_base_url('index.php') . '?' . http_build_query($_GET);
?>

<style>
  .cancelacion-panel-row {
    display: none;
  }

  .cancelacion-panel-row.is-active {
    display: table-row;
  }

  .cancelacion-panel-cell {
    padding: 0 16px 18px !important;
    background: #ffffff;
  }

  .cancelacion-panel {
    margin-top: 12px;
    padding: 22px;
    border-radius: 16px;
    border: 1px solid #f3c7a0;
    border-left: 6px solid #CE5915;
    background: #fff8f1;
    box-shadow: 0 8px 22px rgba(15, 21, 53, 0.08);
  }

  .cancelacion-panel h3 {
    margin: 0 0 8px;
    color: #7a2e00;
    font-size: 18px;
    font-weight: 800;
  }

  .cancelacion-panel p {
    margin: 0 0 16px;
    color: #3d2c1b;
    font-size: 14px;
    line-height: 1.5;
  }

  .cancelacion-panel label {
    display: block;
    margin-bottom: 8px;
    color: #0F1535;
    font-size: 14px;
    font-weight: 800;
  }

  .cancelacion-panel textarea {
    width: 100%;
    min-height: 120px;
    padding: 14px;
    border: 1px solid #d8dce8;
    border-radius: 12px;
    resize: vertical;
    outline: none;
    font-family: inherit;
    font-size: 14px;
    box-sizing: border-box;
    background: #ffffff;
  }

  .cancelacion-panel textarea:focus {
    border-color: #CE5915;
    box-shadow: 0 0 0 3px rgba(206, 89, 21, 0.14);
  }

  .cancelacion-panel__ayuda {
    margin-top: 6px;
    color: #7a6a5f;
    font-size: 12px;
  }

  .cancelacion-panel__error {
    display: none;
    margin-top: 10px;
    color: #b42318;
    font-size: 13px;
    font-weight: 700;
  }

  .cancelacion-panel__error.is-active {
    display: block;
  }

  .cancelacion-panel__acciones {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
  }

  .btn-cerrar-panel-cancelacion {
    background: #ffffff;
  }

  .btn-abrir-panel-cancelacion {
    cursor: pointer;
  }
</style>

<?php if (empty($solicitudes)): ?>

  <div class="empty-state">
    <h2>No hay solicitudes para mostrar</h2>
    <p>Cuando existan registros aparecerán aquí.</p>
  </div>

<?php else: ?>

<div class="ugc-table-wrap animate-fade-up">
  <table class="ugc-table">
    <thead>
      <tr>
        <th>Empleado</th>
        <th>Jefe</th>
        <th>Tipo</th>
        <th>Inicio</th>
        <th>Fin</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($solicitudesPagina as $s): ?>
        <?php
          $nombreEmpleado = trim((string)($s['NOMBRE_EMPLEADO'] ?? ''));
          $nombreJefe = trim((string)($s['NOMBRE_JEFE'] ?? ''));

          $idSolicitud = (string)($s['ID'] ?? '');
          $rolUsuario = $user['rol'] ?? '';
          $cedulaUsuario = normalizar_documento($user['cedula'] ?? '');

          $nitEmpleadoSolicitud = normalizar_documento($s['NIT_EMPLEADO'] ?? '');
          $nitJefeSolicitud = normalizar_documento($s['NIT_JEFE'] ?? '');
          $estadoSolicitud = (string)($s['ESTADO'] ?? '');

          $esDuenoSolicitud = $nitEmpleadoSolicitud === $cedulaUsuario;

          $estaPendienteJefe = $estadoSolicitud === ESTADO_PENDIENTE_JEFE;
          $estaAprobadaJefe = $estadoSolicitud === ESTADO_APROBADO_JEFE;
          $estaAprobada = in_array($estadoSolicitud, [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true);

          $puedeModificarSolicitud = $esDuenoSolicitud && $estaPendienteJefe;

          $esJefeAsignado = in_array($rolUsuario, [ROL_JEFE, ROL_ADMIN], true) 
            && $nitJefeSolicitud === $cedulaUsuario;

          $esRrhhOAdmin = in_array($rolUsuario, [ROL_RRHH, ROL_ADMIN], true);
          $puedeGestionarRrhh = $esRrhhOAdmin && !$esDuenoSolicitud && $estaAprobadaJefe;

          /*
            El usuario solo puede cancelar si:
            1. Es el dueño de la solicitud.
            2. La solicitud ya está aprobada por jefe o por Talento Humano.
          */
          $puedeCancelarAgenda = $estaAprobada && $esDuenoSolicitud;

          $panelCancelarId = 'panel-cancelar-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $idSolicitud);
        ?>

        <tr>

          <td data-label="Empleado">
            <strong><?= e($nombreEmpleado) ?></strong>
          </td>

          <td data-label="Jefe">
            <strong><?= e($nombreJefe) ?></strong>
          </td>

          <td data-label="Tipo">
            <?= e(TIPOS_SOLICITUD[$s['TIPO_SOLICITUD'] ?? ''] ?? ($s['TIPO_SOLICITUD'] ?? '')) ?>
          </td>

          <td data-label="Inicio">
            <?= e(substr((string)($s['FECHA_INICIO'] ?? ''), 0, 10)) ?>
          </td>

          <td data-label="Fin">
            <?= e(substr((string)($s['FECHA_FIN'] ?? ''), 0, 10)) ?>
          </td>

          <td data-label="Estado">
            <?php 
              $estadoValue = $s['ESTADO'] ?? ''; 
              require __DIR__ . '/badge_estado.php'; 
            ?>
          </td>

          <td data-label="Acciones" class="actions-cell">

            <a 
              class="btn btn-outline btn-sm" 
              href="<?= e(url_view('solicitud_ver') . '&id=' . urlencode($idSolicitud) . '&return_to=' . urlencode($returnTo)) ?>"
              data-click-once
            >
              Ver
            </a>

            <?php if ($puedeCancelarAgenda): ?>
              <button 
                class="btn btn-red btn-sm btn-abrir-panel-cancelacion" 
                type="button"
                data-panel="<?= e($panelCancelarId) ?>"
                onclick="ugcAbrirPanelCancelacion('<?= e($panelCancelarId) ?>'); return false;"
                title="Cancelar solicitud"
              >
                Cancelar
              </button>
            <?php endif; ?>

            <?php if ($puedeModificarSolicitud): ?>
              <a 
                class="btn btn-outline btn-sm" 
                href="<?= e(url_view('solicitud_editar') . '&id=' . urlencode($idSolicitud) . '&return_to=' . urlencode($returnTo)) ?>"
                data-click-once
              >
                Editar
              </a>

              <form 
                class="inline-form" 
                method="post" 
                action="<?= e(url_action('solicitud_delete')) ?>" 
                onsubmit="return confirm('¿Seguro que deseas eliminar esta solicitud?')"
              >
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">

                <button class="btn btn-red btn-sm" type="submit">
                  Eliminar
                </button>
              </form>
            <?php endif; ?>

            <?php if ($esJefeAsignado && !$esDuenoSolicitud && $estaPendienteJefe): ?>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_jefe')) ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
                <input type="hidden" name="decision" value="aprobar">

                <button class="btn btn-green btn-sm" type="submit">
                  Aprobar
                </button>
              </form>

              <a 
                class="btn btn-red btn-sm"
                href="<?= e(url_view('solicitud_ver') . '&id=' . urlencode($idSolicitud) . '&accion=rechazar&return_to=' . urlencode($returnTo) . '#observacion_jefe') ?>"
                onclick="alert('Para rechazar la solicitud es obligatorio ingresar una observación indicando el motivo.');"
              >
                Rechazar
              </a>
            <?php endif; ?>

            <?php if ($puedeGestionarRrhh): ?>
              <form class="inline-form" method="post" action="<?= e(url_action('solicitud_rrhh')) ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">
                <input type="hidden" name="decision" value="aprobar">

                <button class="btn btn-green btn-sm" type="submit">
                  Aprobar
                </button>
              </form>

              <a 
                class="btn btn-red btn-sm"
                href="<?= e(url_view('solicitud_ver') . '&id=' . urlencode($idSolicitud) . '&accion=rechazar_rrhh&return_to=' . urlencode($returnTo) . '#observacion_rrhh') ?>"
                onclick="alert('Para rechazar la solicitud es obligatorio escribir una observación indicando el motivo.');"
              >
                Rechazar
              </a>
            <?php endif; ?>

          </td>
        </tr>

        <?php if ($puedeCancelarAgenda): ?>
          <tr class="cancelacion-panel-row" id="<?= e($panelCancelarId) ?>">
            <td colspan="8" class="cancelacion-panel-cell">
              <div class="cancelacion-panel">
                <h3>Motivo de cancelación</h3>

                <p>
                  Escribe por qué deseas cancelar esta cita. Este mensaje es obligatorio y quedará guardado como motivo de cancelación.
                </p>

                <form 
                  class="form-cancelar-agenda-visible" 
                  method="post" 
                  action="<?= e(app_base_url('index.php')) ?>"
                  onsubmit="return ugcValidarCancelacion(this);"
                >
                  <?= csrf_input() ?>

                  <input type="hidden" name="action" value="solicitud_cancelar_agenda">
                  <input type="hidden" name="_method" value="POST">
                  <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                  <input type="hidden" name="id" value="<?= e($idSolicitud) ?>">

                  <label for="observacion_cancelacion_<?= e($idSolicitud) ?>">
                    Observaciones
                  </label>

                  <textarea
                    id="observacion_cancelacion_<?= e($idSolicitud) ?>"
                    name="observacion_cancelacion"
                    rows="4"
                    maxlength="500"
                    required
                    placeholder="Escribe el motivo por el cual deseas cancelar la cita..."
                  ></textarea>

                  <div class="cancelacion-panel__ayuda">
                    Este campo es obligatorio. Máximo 500 caracteres.
                  </div>

                  <div class="cancelacion-panel__error">
                    Debes escribir el motivo de la cancelación.
                  </div>

                  <div class="cancelacion-panel__acciones">
                    <button 
                      type="button" 
                      class="btn btn-outline btn-sm btn-cerrar-panel-cancelacion"
                      onclick="ugcCerrarPanelCancelacion(this); return false;"
                    >
                      No cancelar
                    </button>

                    <button 
                      class="btn btn-cancelar-agenda btn-sm" 
                      type="submit"
                    >
                      Guardar y cancelar cita
                    </button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
        <?php endif; ?>

      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php ugcRenderPagination($pagination, $paginationLabel); ?>

<?php endif; ?>

<script nonce="<?= e(csp_nonce()) ?>">
function ugcAbrirPanelCancelacion(panelId) {
  const panel = document.getElementById(panelId);

  document.querySelectorAll('.cancelacion-panel-row.is-active').forEach(function (fila) {
    if (fila.id !== panelId) {
      fila.classList.remove('is-active');
    }
  });

  if (!panel) {
    return false;
  }

  panel.classList.add('is-active');

  const textarea = panel.querySelector('textarea[name="observacion_cancelacion"]');
  const error = panel.querySelector('.cancelacion-panel__error');

  if (error) {
    error.classList.remove('is-active');
  }

  if (textarea) {
    setTimeout(function () {
      textarea.focus();
      panel.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    }, 100);
  }

  return false;
}

function ugcCerrarPanelCancelacion(boton) {
  const fila = boton.closest('.cancelacion-panel-row');

  if (fila) {
    fila.classList.remove('is-active');
  }

  return false;
}

function ugcValidarCancelacion(form) {
  const textarea = form.querySelector('textarea[name="observacion_cancelacion"]');
  const error = form.querySelector('.cancelacion-panel__error');

  if (!textarea || textarea.value.trim() === '') {
    if (error) {
      error.classList.add('is-active');
    }

    if (textarea) {
      textarea.focus();
    }

    return false;
  }

  return confirm('¿Seguro que deseas cancelar esta cita? El motivo quedará guardado como motivo de cancelación.');
}

document.addEventListener('click', function (event) {
  const botonAbrir = event.target.closest('.btn-abrir-panel-cancelacion');
  const botonCerrar = event.target.closest('.btn-cerrar-panel-cancelacion');

  if (botonAbrir) {
    event.preventDefault();
    event.stopPropagation();

    const panelId = botonAbrir.getAttribute('data-panel');
    ugcAbrirPanelCancelacion(panelId);

    return false;
  }

  if (botonCerrar) {
    event.preventDefault();
    event.stopPropagation();

    ugcCerrarPanelCancelacion(botonCerrar);

    return false;
  }
}, true);
</script>
