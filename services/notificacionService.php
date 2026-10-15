<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/notificacionModel.php';
require_once __DIR__ . '/../models/empleadoModel.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/app.php';

class notificacionService
{
    private notificacionModel $model;
    private ?empleadoModel $empleadoModel = null;
    private array $nombreEmpleadoCache = [];
    private ?array $usuariosRrhhCache = null;

    public function __construct()
    {
        $this->model = new notificacionModel();
    }

    public function notificarNuevaSolicitud(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $nombre = $this->nombreEmpleado($nitEmpleado, $nitEmpleado);
        $mensaje = $nombre . ' solicito ' . $this->tipoLabel($tipoSolicitud) . ' y requiere tu aprobacion';
        $this->safeCrear($nitJefe, 'NUEVA_SOLICITUD', $mensaje, $idSolicitud);
    }

    public function notificarAprobacionJefe(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $nombre = $this->nombreEmpleado($nitJefe, 'Tu jefe');
        $mensaje = $nombre . ' aprobo tu solicitud de ' . $this->tipoLabel($tipoSolicitud);
        $this->safeCrear($nitEmpleado, 'SOLICITUD_APROBADA_JEFE', $mensaje, $idSolicitud);
        $this->notificarRevisionRRHH($idSolicitud, $nitEmpleado, $tipoSolicitud);
        $this->marcarLeidasPorSolicitud($idSolicitud, $nitJefe);
    }

    public function notificarAprobacionFinalJefeTalentoHumano(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $mensaje = 'Talento Humano aprobo tu solicitud de ' . $this->tipoLabel($tipoSolicitud) . '. La solicitud quedo aprobada.';
        $this->safeCrear($nitEmpleado, 'SOLICITUD_APROBADA_FINAL', $mensaje, $idSolicitud);
        $this->marcarLeidasPorSolicitud($idSolicitud, $nitJefe);
    }

    public function notificarRechazoJefe(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud, string $obs = ''): void
    {
        $nombre = $this->nombreEmpleado($nitJefe, 'Tu jefe');
        $mensaje = $nombre . ' rechazo tu solicitud de ' . $this->tipoLabel($tipoSolicitud);
        if ($obs !== '') {
            $mensaje .= '. Observacion: ' . substr($obs, 0, 100);
        }
        $this->safeCrear($nitEmpleado, 'SOLICITUD_RECHAZADA_JEFE', $mensaje, $idSolicitud);
        $this->marcarLeidasPorSolicitud($idSolicitud, $nitJefe);
    }

    public function notificarRevisionRRHH(int $idSolicitud, string $nitEmpleado, string $tipoSolicitud): void
    {
        $nombre = $this->nombreEmpleado($nitEmpleado, $nitEmpleado);
        $mensaje = 'Nueva solicitud de ' . $this->tipoLabel($tipoSolicitud) . ' de ' . $nombre . ' pendiente de revision';
        foreach ($this->usuariosRRHH() as $nit) {
            $this->safeCrear($nit, 'REVISION_RRHH', $mensaje, $idSolicitud);
        }
    }

    public function notificarAprobacionRRHH(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud, string $nitGestor = ''): void
    {
        $tipo = $this->tipoLabel($tipoSolicitud);
        $this->safeCrear($nitEmpleado, 'SOLICITUD_APROBADA_RRHH', 'Talento Humano aprobo tu solicitud de ' . $tipo, $idSolicitud);
        if (normalizar_documento($nitJefe) !== normalizar_documento($nitEmpleado)) {
            $this->safeCrear($nitJefe, 'SOLICITUD_APROBADA_RRHH', 'La solicitud de ' . $tipo . ' de tu colaborador fue aprobada por Talento Humano', $idSolicitud);
        }
        foreach ($this->usuariosRRHH() as $nit) {
            $this->marcarLeidasPorSolicitud($idSolicitud, $nit);
        }
        if ($nitGestor !== '' && !in_array(normalizar_documento($nitGestor), array_map('normalizar_documento', $this->usuariosRRHH()), true)) {
            $this->marcarLeidasPorSolicitud($idSolicitud, $nitGestor);
        }
    }

    public function notificarRechazoRRHH(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud, string $obs = '', string $nitGestor = ''): void
    {
        $tipo = $this->tipoLabel($tipoSolicitud);
        $msg = 'Talento Humano rechazo tu solicitud de ' . $tipo;
        if ($obs !== '') {
            $msg .= '. Observacion: ' . substr($obs, 0, 100);
        }
        $this->safeCrear($nitEmpleado, 'SOLICITUD_RECHAZADA_RRHH', $msg, $idSolicitud);
        if (normalizar_documento($nitJefe) !== normalizar_documento($nitEmpleado)) {
            $this->safeCrear($nitJefe, 'SOLICITUD_RECHAZADA_RRHH', 'La solicitud de ' . $tipo . ' de tu colaborador fue rechazada por Talento Humano', $idSolicitud);
        }
        foreach ($this->usuariosRRHH() as $nit) {
            $this->marcarLeidasPorSolicitud($idSolicitud, $nit);
        }
        if ($nitGestor !== '' && !in_array(normalizar_documento($nitGestor), array_map('normalizar_documento', $this->usuariosRRHH()), true)) {
            $this->marcarLeidasPorSolicitud($idSolicitud, $nitGestor);
        }
    }

    public function notificarCancelacionUsuario(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud, string $obs = ''): void
    {
        $nombre = $this->nombreEmpleado($nitEmpleado, $nitEmpleado);
        $mensaje = $nombre . ' cancelo su solicitud de ' . $this->tipoLabel($tipoSolicitud);

        if ($obs !== '') {
            $mensaje .= '. Motivo: ' . substr($obs, 0, 100);
        }

        $notificados = [];
        $nitEmpleadoNormalizado = normalizar_documento($nitEmpleado);
        $notificar = function (string $nit) use (&$notificados, $nitEmpleadoNormalizado, $mensaje, $idSolicitud): void {
            $nitNormalizado = normalizar_documento($nit);
            if ($nitNormalizado === '' || $nitNormalizado === $nitEmpleadoNormalizado || isset($notificados[$nitNormalizado])) {
                return;
            }

            $notificados[$nitNormalizado] = true;
            $this->safeCrear($nit, 'SOLICITUD_CANCELADA_USUARIO', $mensaje, $idSolicitud);
        };

        $notificar($nitJefe);

        foreach ($this->usuariosRRHH() as $nit) {
            $notificar((string)$nit);
        }

        $this->marcarLeidasPorSolicitud($idSolicitud, $nitEmpleado);
    }

    public function contarNoLeidas(string $nit): int
    {
        if (!app_feature_enabled('NOTIFICACIONES')) {
            return 0;
        }
        try {
            return $this->model->contarNoLeidas($nit);
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getNoLeidas(string $nit): array
    {
        if (!app_feature_enabled('NOTIFICACIONES')) {
            return [];
        }
        try {
            return $this->model->getNoLeidas($nit);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function marcarLeida(int $id, string $nit): bool
    {
        if (!app_feature_enabled('NOTIFICACIONES')) {
            return false;
        }
        try {
            return $this->model->marcarLeida($id, $nit);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function marcarTodasLeidas(string $nit): bool
    {
        if (!app_feature_enabled('NOTIFICACIONES')) {
            return false;
        }
        try {
            return $this->model->marcarTodasLeidas($nit);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function marcarLeidasPorSolicitud(int $idSolicitud, string $nit): bool
    {
        if (!app_feature_enabled('NOTIFICACIONES')) {
            return false;
        }
        try {
            return $this->model->marcarLeidasPorSolicitud($idSolicitud, $nit);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function usuariosRRHH(): array
    {
        if ($this->usuariosRrhhCache !== null) {
            return $this->usuariosRrhhCache;
        }

        $rrhh = [];
        foreach ($this->empleadoModel()->getPorCentrosCosto(CC_RRHH) as $emp) {
            if (!empty($emp['NIT'])) { $rrhh[] = (string)$emp['NIT']; }
        }
        // Los usuarios TH fijos siempre reciben notificaciones de revision RRHH
        foreach (NITS_TALENTO_HUMANO as $nitTh) {
            $rrhh[] = $nitTh;
        }
        if (APP_ENV === 'development') {
            foreach (USUARIOS_PRUEBA as $cedula => $datos) {
                if (in_array($datos['rol'] ?? '', [ROL_RRHH, ROL_ADMIN], true)) {
                    $rrhh[] = (string)$cedula;
                }
            }
        }
        return $this->usuariosRrhhCache = array_values(array_unique($rrhh));
    }

    private function nombreEmpleado(string $nit, string $fallback): string
    {
        if ($nit === '') {
            return $fallback;
        }

        if (array_key_exists($nit, $this->nombreEmpleadoCache)) {
            return $this->nombreEmpleadoCache[$nit] !== '' ? $this->nombreEmpleadoCache[$nit] : $fallback;
        }

        if (APP_ENV === 'development' && isset(USUARIOS_PRUEBA[$nit])) {
            $nombre = trim((string)(USUARIOS_PRUEBA[$nit]['nombre'] ?? ''));
            $this->nombreEmpleadoCache[$nit] = $nombre;
            return $nombre !== '' ? $nombre : $fallback;
        }

        $empleado = $this->empleadoModel()->getByNit($nit);
        $nombre = trim((string)($empleado['NOMBRE_COMPLETO'] ?? ''));
        $this->nombreEmpleadoCache[$nit] = $nombre;

        return $nombre !== '' ? $nombre : $fallback;
    }

    private function empleadoModel(): empleadoModel
    {
        if ($this->empleadoModel === null) {
            $this->empleadoModel = new empleadoModel();
        }

        return $this->empleadoModel;
    }

    private function tipoLabel(string $tipo): string
    {
        return TIPOS_SOLICITUD[$tipo] ?? $tipo;
    }

    private function safeCrear(string $nit, string $tipo, string $mensaje, int $idSolicitud): void
    {
        if (!app_feature_enabled('NOTIFICACIONES')) {
            return;
        }
        if ($nit === '' || $idSolicitud <= 0) {
            return;
        }
        try {
            $this->model->crear($nit, $tipo, $mensaje, $idSolicitud);
        } catch (Throwable $e) {
            error_log('Notificacion error: ' . $e->getMessage());
        }
    }
}
