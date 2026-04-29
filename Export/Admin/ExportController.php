<?php

declare(strict_types=1);

namespace App\Export\Admin;

use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
        'ID',
        'NIT EMPLEADO',
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

        $data = [
            'Total Solicitudes' => $todas,

            'Pendiente Jefe' => $this->filtrarPorEstados($todas, [
                'PENDIENTE_JEFE'
            ]),

            'Pendientes RRHH' => $this->filtrarPorEstados($todas, [
                'APROBADO_JEFE'
            ]),

            'Aprobado RRHH' => $this->filtrarPorEstados($todas, [
                'APROBADO_RRHH'
            ]),

            'Rechazado RRHH' => $this->filtrarPorEstados($todas, [
                'RECHAZADO_RRHH'
            ]),
        ];

        $this->generarExcelPorHojas($data, 'reporte_admin');
    }

    private function filtrarPorEstados(array $rows, array $estados): array
    {
        return array_values(array_filter($rows, function (array $row) use ($estados): bool {
            return isset($row['ESTADO']) && in_array($row['ESTADO'], $estados, true);
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

        $filas[] = $this->cabeceras;

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
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    $controller = new ExportController();
    $controller->todasExcel();
}