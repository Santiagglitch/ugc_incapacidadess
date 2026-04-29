<?php

declare(strict_types=1);

namespace App\Export\Jefe;

use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
        'ID',
        'EMPLEADO',
        'JEFE',
        'TIPO SOLICITUD',
        'FECHA SOLICITUD',
        'FECHA INICIO',
        'FECHA FIN',
        'HORAS',
        'DÍAS',
        'ESTADO',
        'OBSERVACIONES',
        'FECHA CREACIÓN'
    ];

    private array $campos = [
        'ID',
        'NIT_EMPLEADO',
        'JEFE',
        'TIPO_SOLICITUD',
        'FECHA_SOLICITUD',
        'FECHA_INICIO',
        'FECHA_FIN',
        'DURACION_HORAS',
        'DURACION_DIAS',
        'ESTADO',
        'OBSERVACIONES',
        'FECHA_CREACION'
    ];

    public function todasExcel(): void
    {
        $rutaHelpers = __DIR__ . '/../../config/helpers.php';
        $rutaModelo = __DIR__ . '/../../models/SolicitudModel.php';
        $rutaLibreria = __DIR__ . '/../Libraries/SimpleXLSXGen.php';

        if (file_exists($rutaHelpers)) {
            require_once $rutaHelpers;
        }

        if (function_exists('iniciar_sesion_segura')) {
            iniciar_sesion_segura();
        } elseif (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!file_exists($rutaLibreria)) {
            http_response_code(500);
            echo 'No se encontró la librería SimpleXLSXGen en: ' . $rutaLibreria;
            exit;
        }

        if (!file_exists($rutaModelo)) {
            http_response_code(500);
            echo 'No se encontró el modelo SolicitudModel en: ' . $rutaModelo;
            exit;
        }

        require_once $rutaLibreria;
        require_once $rutaModelo;

        if (class_exists('\App\Models\SolicitudModel')) {
            $model = new \App\Models\SolicitudModel();
        } elseif (class_exists('\SolicitudModel')) {
            $model = new \SolicitudModel();
        } else {
            http_response_code(500);
            echo 'El archivo SolicitudModel.php existe, pero no se encontró la clase SolicitudModel.';
            exit;
        }

        if (!method_exists($model, 'getAll')) {
            http_response_code(500);
            echo 'El modelo SolicitudModel no tiene el método getAll().';
            exit;
        }

        $todas = $model->getAll();

        $nitJefe = $this->obtenerNitUsuarioActual();

        if ($nitJefe === '') {
            $this->mostrarDiagnostico($todas);
            exit;
        }

        /*
         * Datos del jefe logueado.
         */
        $solicitudesEquipo = $this->filtrarSolicitudesDelJefe($todas, $nitJefe);
        $misSolicitudes = $this->filtrarMisSolicitudes($todas, $nitJefe);

        /*
         * Mismas tarjetas del panel de jefe.
         */
        $pendientes = $this->filtrarPorEstados($solicitudesEquipo, [
            'PENDIENTE_JEFE'
        ]);

        $aprobadas = $this->filtrarPorEstados($solicitudesEquipo, [
            'APROBADO_JEFE',
            'APROBADO_RRHH'
        ]);

        $rechazadas = $this->filtrarPorEstados($solicitudesEquipo, [
            'RECHAZADO_JEFE'
        ]);

        $gestionadas = $this->filtrarPorEstados($solicitudesEquipo, [
            'APROBADO_JEFE',
            'RECHAZADO_JEFE',
            'APROBADO_RRHH',
            'RECHAZADO_RRHH'
        ]);

        $data = [
            'Pendientes Aprobacion' => $pendientes,
            'Aprobadas Por Ti' => $aprobadas,
            'Rechazadas Por Ti' => $rechazadas,
            'Mis Solicitudes' => $misSolicitudes,
            'Historial Gestionado' => $gestionadas,
        ];

        $this->generarExcelPorHojas($data, 'reporte_jefe');
    }

    private function obtenerNitUsuarioActual(): string
    {
        $posiblesRutas = [
            $_SESSION['usuario']['NIT'] ?? null,
            $_SESSION['usuario']['nit'] ?? null,
            $_SESSION['usuario']['NIT_EMPLEADO'] ?? null,
            $_SESSION['usuario']['nit_empleado'] ?? null,
            $_SESSION['usuario']['DOCUMENTO'] ?? null,
            $_SESSION['usuario']['documento'] ?? null,
            $_SESSION['usuario']['CEDULA'] ?? null,
            $_SESSION['usuario']['cedula'] ?? null,

            $_SESSION['user']['NIT'] ?? null,
            $_SESSION['user']['nit'] ?? null,
            $_SESSION['user']['NIT_EMPLEADO'] ?? null,
            $_SESSION['user']['nit_empleado'] ?? null,
            $_SESSION['user']['DOCUMENTO'] ?? null,
            $_SESSION['user']['documento'] ?? null,

            $_SESSION['empleado']['NIT'] ?? null,
            $_SESSION['empleado']['nit'] ?? null,
            $_SESSION['empleado']['NIT_EMPLEADO'] ?? null,
            $_SESSION['empleado']['nit_empleado'] ?? null,
            $_SESSION['empleado']['DOCUMENTO'] ?? null,
            $_SESSION['empleado']['documento'] ?? null,

            $_SESSION['NIT'] ?? null,
            $_SESSION['nit'] ?? null,
            $_SESSION['NIT_EMPLEADO'] ?? null,
            $_SESSION['nit_empleado'] ?? null,
            $_SESSION['DOCUMENTO'] ?? null,
            $_SESSION['documento'] ?? null,
            $_SESSION['CEDULA'] ?? null,
            $_SESSION['cedula'] ?? null,
        ];

        foreach ($posiblesRutas as $valor) {
            if ($valor !== null && trim((string) $valor) !== '') {
                return $this->normalizarNit((string) $valor);
            }
        }

        return '';
    }

    private function filtrarSolicitudesDelJefe(array $rows, string $nitJefe): array
    {
        $nitJefe = $this->normalizarNit($nitJefe);

        return array_values(array_filter($rows, function (array $row) use ($nitJefe): bool {
            $camposJefe = [
                'JEFE',
                'NIT_JEFE',
                'NIT_JEFE_INMEDIATO',
                'JEFE_INMEDIATO',
                'JEFE_NIT',
                'NIT_APROBADOR',
                'NIT_RESPONSABLE'
            ];

            foreach ($camposJefe as $campo) {
                if (
                    isset($row[$campo])
                    && $this->normalizarNit((string) $row[$campo]) === $nitJefe
                ) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function filtrarMisSolicitudes(array $rows, string $nitEmpleado): array
    {
        $nitEmpleado = $this->normalizarNit($nitEmpleado);

        return array_values(array_filter($rows, function (array $row) use ($nitEmpleado): bool {
            return isset($row['NIT_EMPLEADO'])
                && $this->normalizarNit((string) $row['NIT_EMPLEADO']) === $nitEmpleado;
        }));
    }

    private function filtrarPorEstados(array $rows, array $estados): array
    {
        $estadosNormalizados = array_map([$this, 'normalizarEstado'], $estados);

        return array_values(array_filter($rows, function (array $row) use ($estadosNormalizados): bool {
            if (!isset($row['ESTADO'])) {
                return false;
            }

            $estadoFila = $this->normalizarEstado((string) $row['ESTADO']);

            return in_array($estadoFila, $estadosNormalizados, true);
        }));
    }

    private function generarExcelPorHojas(array $data, string $nombreArchivo): void
    {
        $xlsx = new SimpleXLSXGen();

        foreach ($data as $tituloHoja => $rows) {
            $filas = $this->prepararFilas($rows);
            $xlsx->addSheet($filas, $this->limpiarTituloHoja($tituloHoja));
        }

        $archivo = $nombreArchivo . '_' . date('Ymd_His') . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xlsx->downloadAs($archivo);
        exit;
    }

    private function prepararFilas(array $rows): array
    {
        $filas = [];

        /*
         * Primera fila: cabeceras normales.
         */
        $filas[] = $this->cabeceras;

        /*
         * Filas de datos.
         */
        foreach ($rows as $row) {
            $fila = [];

            foreach ($this->campos as $campo) {
                $valor = $row[$campo] ?? '';

                if (
                    $campo === 'TIPO_SOLICITUD'
                    && defined('TIPOS_SOLICITUD')
                    && isset(TIPOS_SOLICITUD[$valor])
                ) {
                    $valor = TIPOS_SOLICITUD[$valor];
                }

                $fila[] = $this->normalizarValor($valor);
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    private function normalizarValor($valor): string
    {
        if ($valor === null) {
            return '';
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if (is_object($valor) && method_exists($valor, 'load')) {
            return (string) $valor->load();
        }

        if (is_array($valor) || is_object($valor)) {
            return '';
        }

        return (string) $valor;
    }

    private function normalizarNit(string $nit): string
    {
        $nit = trim($nit);

        return preg_replace('/[^0-9A-Za-z]/', '', $nit) ?? '';
    }

    private function normalizarEstado(string $estado): string
    {
        $estado = trim($estado);
        $estado = strtoupper($estado);
        $estado = str_replace([' ', '-'], '_', $estado);

        return $estado;
    }

    private function limpiarTituloHoja(string $titulo): string
    {
        $titulo = str_replace(['\\', '/', '*', '[', ']', ':', '?'], ' ', $titulo);
        $titulo = trim($titulo);

        if ($titulo === '') {
            $titulo = 'Hoja';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($titulo, 0, 31);
        }

        return substr($titulo, 0, 31);
    }

    private function mostrarDiagnostico(array $todas): void
    {
        http_response_code(200);

        echo '<!DOCTYPE html>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Diagnóstico exportador jefe</title>';
        echo '<style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f7f5;
                color: #102015;
                padding: 24px;
            }

            .box {
                background: #fff;
                border: 1px solid #cfd8cf;
                border-radius: 12px;
                padding: 18px;
                margin-bottom: 18px;
                box-shadow: 0 8px 20px rgba(0,0,0,.06);
            }

            h1 {
                color: #066021;
                margin-top: 0;
            }

            pre {
                background: #0f172a;
                color: #e5e7eb;
                padding: 16px;
                border-radius: 10px;
                overflow: auto;
                max-height: 420px;
            }

            .warning {
                background: #fff7ed;
                border-color: #fed7aa;
            }
        </style>';
        echo '</head>';
        echo '<body>';

        echo '<div class="box warning">';
        echo '<h1>No se pudo identificar el NIT del jefe</h1>';
        echo '<p>Por seguridad no se descargó el Excel, porque si no se identifica el NIT se podrían exportar datos que no son de este jefe.</p>';
        echo '</div>';

        echo '<div class="box">';
        echo '<h2>SESSION actual</h2>';
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
        echo '</div>';

        echo '<div class="box">';
        echo '<h2>Primera solicitud del modelo</h2>';
        echo '<pre>';
        print_r($todas[0] ?? []);
        echo '</pre>';
        echo '</div>';

        echo '</body>';
        echo '</html>';
    }
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    $controller = new ExportController();
    $controller->todasExcel();
}