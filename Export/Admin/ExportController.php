<?php

declare(strict_types=1);

namespace App\Export\Admin;

use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
        'ID',
        'NIT EMPLEADO',
        'NIT JEFE',
        'NIT RRHH',
        'TIPO SOLICITUD',
        'FECHA INICIO',
        'FECHA FIN',
        'HORAS',
        'DIAS',
        'ESTADO',
        'OBSERVACIONES',
        'OBSERVACION JEFE',
        'OBSERVACION RRHH',
        'FECHA CREACION',
    ];

    private array $campos = [
        'ID',
        'NIT_EMPLEADO',
        'NIT_JEFE',
        'NIT_RRHH',
        'TIPO_SOLICITUD',
        'FECHA_INICIO',
        'FECHA_FIN',
        'DURACION_HORAS',
        'DURACION_DIAS',
        'ESTADO',
        'OBSERVACIONES',
        'OBSERVACION_JEFE',
        'OBSERVACION_RRHH',
        'FECHA_CREACION',
    ];

    public function todasExcel(): void
    {
        require_once __DIR__ . '/../../config/helpers.php';
        require_once __DIR__ . '/../Libraries/SimpleXLSXGen.php';
        require_once __DIR__ . '/../../models/solicitudModel.php';

        \iniciar_sesion_segura();
        \requiere_rol([\ROL_ADMIN]);

        $model = new \solicitudModel();

        $data = [
            'Total Solicitudes' => $model->getAll(),
            'Pendiente Jefe' => $model->getAll(['estado' => \ESTADO_PENDIENTE_JEFE]),
            'Pendientes RRHH' => $model->getAll(['estado' => \ESTADO_APROBADO_JEFE]),
            'Aprobado RRHH' => $model->getAll(['estado' => \ESTADO_APROBADO_RRHH]),
            'Rechazado RRHH' => $model->getAll(['estado' => \ESTADO_RECHAZADO_RRHH]),
        ];

        $this->generarExcelPorHojas($data, 'reporte_admin');
    }

    private function generarExcelPorHojas(array $data, string $nombreArchivo): void
    {
        $xlsx = new SimpleXLSXGen();

        foreach ($data as $tituloHoja => $rows) {
            $xlsx->addSheet($this->prepararFilas($rows), $this->limpiarTituloHoja($tituloHoja));
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xlsx->downloadAs($nombreArchivo . '_' . date('Ymd_His') . '.xlsx');
        exit;
    }

    private function prepararFilas(array $rows): array
    {
        $filas = [$this->cabeceras];

        foreach ($rows as $row) {
            $fila = [];
            foreach ($this->campos as $campo) {
                $valor = $row[$campo] ?? '';
                if ($campo === 'TIPO_SOLICITUD' && defined('TIPOS_SOLICITUD') && isset(\TIPOS_SOLICITUD[$valor])) {
                    $valor = \TIPOS_SOLICITUD[$valor];
                }
                $fila[] = $this->normalizarValor($valor);
            }
            $filas[] = $fila;
        }

        return $filas;
    }

    private function normalizarValor($valor): string
    {
        if ($valor === null || is_array($valor) || is_object($valor)) {
            return '';
        }

        return (string)$valor;
    }

    private function limpiarTituloHoja(string $titulo): string
    {
        $titulo = trim(str_replace(['\\', '/', '*', '[', ']', ':', '?'], ' ', $titulo));
        return substr($titulo !== '' ? $titulo : 'Hoja', 0, 31);
    }
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    (new ExportController())->todasExcel();
}
