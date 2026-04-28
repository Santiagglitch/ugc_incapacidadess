<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/solicitudModel.php';
require_once __DIR__ . '/../models/empleadoModel.php';
require_once __DIR__ . '/../services/notificacionService.php';

class solicitudController
{
    private solicitudModel $model;

    public function __construct()
    {
        $this->model = new solicitudModel();
    }

    public function listar(): void
    {
        requiere_login();
        $user = usuario_actual();
        $rol = $user['rol'] ?? '';
        $tipo = limpiar_texto($_GET['tipo'] ?? '');
        $estado = limpiar_texto($_GET['estado'] ?? '');
        $titulo = 'Solicitudes';

        if ($rol === ROL_ADMIN) {
            $solicitudes = $this->model->getAll(['estado' => $estado]);
            $titulo = 'Todas las solicitudes';
        } elseif ($rol === ROL_RRHH) {
            if ($tipo === 'aprobadas') {
                $solicitudes = $this->model->getAll(['estado' => ESTADO_APROBADO_RRHH]);
                $titulo = 'Aprobadas por Talento Humano';
            } elseif ($tipo === 'rechazadas') {
                $solicitudes = $this->model->getAll(['estado' => ESTADO_RECHAZADO_RRHH]);
                $titulo = 'Rechazadas por Talento Humano';
            } elseif ($tipo === 'historico') {
                $solicitudes = $this->model->getHistoricoRRHH();
                $titulo = 'Historial gestionado por Talento Humano';
            } elseif ($tipo === 'revision_jefe') {
                $solicitudes = $this->model->getAll(['estado' => ESTADO_PENDIENTE_JEFE]);
                $titulo = 'En revision de jefe';
            } else {
                $solicitudes = $this->model->getPendientesRRHH();
                $titulo = 'Pendientes de Talento Humano';
            }
        } elseif ($rol === ROL_JEFE) {
            $gestionadas = $this->model->getGestionadasByJefe($user['cedula']);
            if ($tipo === 'aprobadas') {
                $solicitudes = array_values(array_filter($gestionadas, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true)));
                $titulo = 'Aprobadas por mi';
            } elseif ($tipo === 'rechazadas') {
                $solicitudes = array_values(array_filter($gestionadas, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_RECHAZADO_JEFE));
                $titulo = 'Rechazadas por mi';
            } elseif ($tipo === 'mis_solicitudes') {
                $solicitudes = $this->model->getByEmpleado($user['cedula']);
                $titulo = 'Mis solicitudes personales';
            } elseif ($tipo === 'gestionadas') {
                $solicitudes = $gestionadas;
                $titulo = 'Historial gestionado';
            } else {
                $solicitudes = $this->model->getPendientesJefe($user['cedula']);
                $titulo = 'Pendientes de aprobacion';
            }
        } else {
            $todas = $this->model->getByEmpleado($user['cedula']);
            if ($tipo === 'pendientes') {
                $solicitudes = array_values(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === ESTADO_PENDIENTE_JEFE));
                $titulo = 'Mis solicitudes pendientes';
            } elseif ($tipo === 'aprobadas') {
                $solicitudes = array_values(array_filter($todas, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_APROBADO_JEFE, ESTADO_APROBADO_RRHH], true)));
                $titulo = 'Mis solicitudes aprobadas';
            } elseif ($tipo === 'rechazadas') {
                $solicitudes = array_values(array_filter($todas, fn($s) => in_array(($s['ESTADO'] ?? ''), [ESTADO_RECHAZADO_JEFE, ESTADO_RECHAZADO_RRHH], true)));
                $titulo = 'Mis solicitudes rechazadas';
            } else {
                $solicitudes = $todas;
                $titulo = 'Mis solicitudes';
            }
        }

        render_view('shared/solicitudes', compact('user', 'solicitudes', 'titulo', 'rol', 'tipo', 'estado'));
    }

    public function crearForm(): void
    {
        requiere_login();
        $user = usuario_actual();
        $empleadoModel = new empleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz((string)($user['centro_costo'] ?? ''));
        $jefes = $esAprendiz ? $this->jefesDisponibles($empleadoModel) : [];
        render_view('empleado/form_crear', compact('user', 'esAprendiz', 'jefes'));
    }

    public function crearPost(): void
    {
        requiere_login();
        $this->validarPost();
        $user = usuario_actual();

        $tipoSolicitud = limpiar_texto($_POST['tipo_solicitud'] ?? '');
        $fechaInicio = limpiar_texto($_POST['fecha_inicio'] ?? '');
        $fechaFin = limpiar_texto($_POST['fecha_fin'] ?? '');
        $duracionHoras = $this->numeroOpcional($_POST['duracion_horas'] ?? '');
        $duracionDias = $this->numeroOpcional($_POST['duracion_dias'] ?? '');
        $observaciones = limpiar_texto($_POST['observaciones'] ?? '');
        $empleadoModel = new empleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz((string)($user['centro_costo'] ?? ''));
        $nitJefe = $esAprendiz ? limpiar_texto($_POST['nit_jefe_seleccionado'] ?? '') : limpiar_texto($_POST['nit_jefe'] ?? ($user['nit_jefe'] ?? ''));

        if ($nitJefe === '') {
            $jefe = (new empleadoModel())->getJefeInmediato((string)($user['cedula'] ?? ''));
            $nitJefe = (string)($jefe['NIT_JEFE'] ?? '');
        }

        $errores = $this->validarSolicitud($tipoSolicitud, $fechaInicio, $fechaFin, $duracionHoras, $duracionDias, $observaciones, $nitJefe);
        if (!empty($errores)) {
            flash_set('error', implode(' ', $errores));
            redirect_to(url_view('solicitud_crear'));
        }

        $rutaPdf = $this->procesarArchivoPDF((string)$user['cedula']);
        if ($rutaPdf === false) {
            redirect_to(url_view('solicitud_crear'));
        }

        $ok = $this->model->crear([
            'nit_empleado' => $user['cedula'],
            'nit_jefe' => $nitJefe,
            'tipo_solicitud' => $tipoSolicitud,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'duracion_horas' => $duracionHoras,
            'duracion_dias' => $duracionDias,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
            'ruta_comprobante' => $rutaPdf,
        ]);

        if (!$ok && $rutaPdf) {
            $abs = dirname(__DIR__) . '/' . $rutaPdf;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        if ($ok) {
            $idSolicitud = $this->model->getUltimoIdByEmpleado((string)$user['cedula']);
            (new notificacionService())->notificarNuevaSolicitud($idSolicitud, (string)$user['cedula'], $nitJefe, $tipoSolicitud);
        }

        flash_set($ok ? 'success' : 'error', $ok ? 'Solicitud creada correctamente.' : 'No se pudo guardar la solicitud.');
        redirect_to($ok ? url_view('dashboard') : url_view('solicitud_crear'));
    }

    public function ver(int $id): void
    {
        requiere_login();
        $user = usuario_actual();
        $solicitud = $this->model->getById($id);

        if (!$solicitud || !$this->puedeVer($solicitud, $user)) {
            http_response_code(403);
            exit('Sin permiso para ver esta solicitud.');
        }

        render_view('shared/detalle', compact('user', 'solicitud'));
    }

    public function gestionJefePost(int $id): void
    {
        requiere_rol([ROL_JEFE, ROL_ADMIN]);
        $this->validarPost();
        $user = usuario_actual();
        $accion = limpiar_texto($_POST['decision'] ?? '');
        $obs = limpiar_texto($_POST['observacion_jefe'] ?? '');
        $solicitud = $this->model->getById($id);
        $ok = $accion === 'aprobar' ? $this->model->aprobarJefe($id, $user['cedula'], $obs) : $this->model->rechazarJefe($id, $user['cedula'], $obs);
        if ($ok && $solicitud) {
            $notificaciones = new notificacionService();
            if ($accion === 'aprobar') {
                $notificaciones->notificarAprobacionJefe($id, (string)$solicitud['NIT_EMPLEADO'], (string)$user['cedula'], (string)$solicitud['TIPO_SOLICITUD']);
            } else {
                $notificaciones->notificarRechazoJefe($id, (string)$solicitud['NIT_EMPLEADO'], (string)$user['cedula'], (string)$solicitud['TIPO_SOLICITUD'], $obs);
            }
        }
        flash_set($ok ? 'success' : 'error', $ok ? 'Gestion de jefe guardada.' : 'No se pudo gestionar la solicitud.');
        redirect_to($this->returnTo(url_view('dashboard')));
    }

    public function gestionRrhhPost(int $id): void
    {
        requiere_rol([ROL_RRHH, ROL_ADMIN]);
        $this->validarPost();
        $user = usuario_actual();
        $accion = limpiar_texto($_POST['decision'] ?? '');
        $obs = limpiar_texto($_POST['observacion_rrhh'] ?? '');
        $solicitud = $this->model->getById($id);
        $ok = $accion === 'aprobar' ? $this->model->aprobarRRHH($id, $user['cedula'], $obs) : $this->model->rechazarRRHH($id, $user['cedula'], $obs);
        if ($ok && $solicitud) {
            $notificaciones = new notificacionService();
            if ($accion === 'aprobar') {
                $notificaciones->notificarAprobacionRRHH($id, (string)$solicitud['NIT_EMPLEADO'], (string)$solicitud['NIT_JEFE'], (string)$solicitud['TIPO_SOLICITUD']);
            } else {
                $notificaciones->notificarRechazoRRHH($id, (string)$solicitud['NIT_EMPLEADO'], (string)$solicitud['NIT_JEFE'], (string)$solicitud['TIPO_SOLICITUD'], $obs);
            }
        }
        flash_set($ok ? 'success' : 'error', $ok ? 'Gestion de RRHH guardada.' : 'No se pudo gestionar la solicitud.');
        redirect_to($this->returnTo(url_view('dashboard')));
    }

    public function eliminar(int $id): void
    {
        requiere_login();
        $this->validarPost();
        $user = usuario_actual();
        $ok = $this->model->eliminar($id, $user['cedula']);
        flash_set($ok ? 'success' : 'error', $ok ? 'Solicitud eliminada.' : 'No se pudo eliminar.');
        redirect_to($this->returnTo(url_view('dashboard')));
    }


    public function servirArchivo(int $id): void
    {
        requiere_login();
        $user = usuario_actual();
        $solicitud = $this->model->getById($id);

        if (!$solicitud || !$this->puedeVer($solicitud, $user) || empty($solicitud['RUTA_COMPROBANTE'])) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }

        $nombre = basename((string)$solicitud['RUTA_COMPROBANTE']);
        $ruta = dirname(__DIR__) . '/storage/solicitudes/' . $nombre;
        if (!is_file($ruta)) {
            http_response_code(404);
            exit('Archivo no encontrado fisicamente.');
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header_remove('Content-Security-Policy');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    }
    private function validarSolicitud(string $tipo, string $inicio, string $fin, ?float $horas, ?float $dias, string $obs, string $nitJefe): array
    {
        $errores = [];
        if (!array_key_exists($tipo, TIPOS_SOLICITUD)) {
            $errores[] = 'Selecciona un tipo de solicitud valido.';
        }
        if (!$this->fechaValida($inicio)) {
            $errores[] = 'La fecha de inicio no es valida.';
        }
        if (!$this->fechaValida($fin)) {
            $errores[] = 'La fecha fin no es valida.';
        }
        if ($this->fechaValida($inicio) && $this->fechaValida($fin) && strtotime($fin) < strtotime($inicio)) {
            $errores[] = 'La fecha fin debe ser igual o posterior a la fecha de inicio.';
        }
        if ($horas !== null && ($horas < 0 || $horas > 999)) {
            $errores[] = 'La duracion en horas no es valida.';
        }
        if ($dias !== null && ($dias < 0 || $dias > 365)) {
            $errores[] = 'La duracion en dias no es valida.';
        }
        if (mb_strlen($obs, 'UTF-8') > 2000) {
            $errores[] = 'Las observaciones no pueden superar 2000 caracteres.';
        }
        if ($nitJefe === '') {
            $errores[] = 'No se encontro jefe inmediato para esta solicitud.';
        }
        return $errores;
    }

    private function procesarArchivoPDF(string $nitEmpleado)
    {
        if (!isset($_FILES['documento_pdf']) || ($_FILES['documento_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash_set('error', 'El documento PDF es obligatorio.');
            return false;
        }

        $archivo = $_FILES['documento_pdf'];
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash_set('error', 'Error al subir el archivo PDF.');
            return false;
        }

        if (($archivo['size'] ?? 0) > 5 * 1024 * 1024) {
            flash_set('error', 'El PDF no puede superar 5MB.');
            return false;
        }

        $extension = strtolower(pathinfo((string)$archivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            flash_set('error', 'El archivo debe tener extension .pdf.');
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            flash_set('error', 'El archivo debe ser un PDF valido.');
            return false;
        }

        if (!is_uploaded_file($archivo['tmp_name'])) {
            flash_set('error', 'Archivo temporal no valido.');
            return false;
        }

        $uploadDir = dirname(__DIR__) . '/storage/solicitudes';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            flash_set('error', 'No se pudo crear el directorio de almacenamiento.');
            return false;
        }

        $baseName = preg_replace('/[^A-Za-z0-9_-]/', '', pathinfo((string)$archivo['name'], PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? substr($baseName, 0, 30) : 'documento';
        $nombreFinal = sprintf('%s_%s_%s_%s.pdf', preg_replace('/\D/', '', $nitEmpleado), date('Ymd'), str_replace('.', '', uniqid('', true)), $baseName);
        $destino = $uploadDir . '/' . $nombreFinal;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            flash_set('error', 'No se pudo guardar el archivo PDF.');
            return false;
        }

        return 'storage/solicitudes/' . $nombreFinal;
    }

    private function numeroOpcional($valor): ?float
    {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return null;
        }
        return is_numeric($valor) ? (float)$valor : -1;
    }

    private function fechaValida(string $fecha): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dt && $dt->format('Y-m-d') === $fecha;
    }

    private function returnTo(string $fallback): string
    {
        $returnTo = (string)($_POST['return_to'] ?? '');
        if ($returnTo !== '' && str_starts_with($returnTo, app_base_url(''))) {
            return $returnTo;
        }
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '' && str_contains($referer, BASE_URL)) {
            return $referer;
        }
        return $fallback;
    }

    private function puedeVer(array $solicitud, array $user): bool
    {
        return ($solicitud['NIT_EMPLEADO'] ?? '') === ($user['cedula'] ?? '')
            || ($solicitud['NIT_JEFE'] ?? '') === ($user['cedula'] ?? '')
            || in_array($user['rol'] ?? '', [ROL_ADMIN, ROL_RRHH], true);
    }

    private function jefesDisponibles(empleadoModel $empleadoModel): array
    {
        $jefes = $empleadoModel->getTodosLosJefes();

        if (APP_ENV === 'development') {
            foreach (USUARIOS_PRUEBA as $cedula => $datos) {
                if (($datos['rol'] ?? '') === ROL_JEFE || ($datos['rol'] ?? '') === ROL_ADMIN) {
                    $jefes[] = [
                        'NIT' => (string)$cedula,
                        'NOMBRE_COMPLETO' => (string)($datos['nombre'] ?? $cedula),
                        'CENTRO_COSTO' => (string)($datos['centro_costo'] ?? ''),
                        'NIVEL' => (int)($datos['nivel'] ?? 0),
                    ];
                }
            }
        }

        $unicos = [];
        foreach ($jefes as $jefe) {
            if (empty($jefe['NIT'])) {
                continue;
            }
            $unicos[(string)$jefe['NIT']] = $jefe;
        }

        uasort($unicos, static fn($a, $b) => strcmp((string)($a['NOMBRE_COMPLETO'] ?? ''), (string)($b['NOMBRE_COMPLETO'] ?? '')));
        return array_values($unicos);
    }

    private function validarPost(): void
    {
        if (!validar_csrf($_POST['_csrf_token'] ?? null)) {
            http_response_code(422);
            exit('Token de seguridad invalido.');
        }
    }
}
