<?php

declare(strict_types=1);

namespace App\Export\RRHH;

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
        'FECHA GESTION RRHH',
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
        'FECHA_GESTION_RRHH',
    ];

    public function todasExcel(): void
    {
        require_once __DIR__ . '/../../config/helpers.php';
        require_once __DIR__ . '/../Libraries/SimpleXLSXGen.php';
        require_once __DIR__ . '/../../models/solicitudModel.php';

        \iniciar_sesion_segura();
        \requiere_rol([\ROL_RRHH, \ROL_ADMIN]);

        $model = new \solicitudModel();
        $todas = $model->getAll();

        $data = [
            'Pendientes RRHH' => $this->ordenarPorFechaCreacionAsc($this->filtrarPorEstado($todas, \ESTADO_APROBADO_JEFE)),
            'Aprobadas RRHH' => $this->filtrarPorEstado($todas, \ESTADO_APROBADO_RRHH),
            'Rechazadas RRHH' => $this->filtrarPorEstado($todas, \ESTADO_RECHAZADO_RRHH),
            'Total Historico' => $todas,
            'En Revision Jefe' => $this->filtrarPorEstado($todas, \ESTADO_PENDIENTE_JEFE),
        ];

        $this->generarExcelPorHojas($data, 'reporte_rrhh');
    }

    private function filtrarPorEstado(array $rows, string $estado): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($estado): bool {
            return (string)($row['ESTADO'] ?? '') === $estado;
        }));
    }

    private function ordenarPorFechaCreacionAsc(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $fecha = strcmp((string)($a['FECHA_CREACION'] ?? ''), (string)($b['FECHA_CREACION'] ?? ''));

            if ($fecha !== 0) {
                return $fecha;
            }

            return (int)($a['ID'] ?? 0) <=> (int)($b['ID'] ?? 0);
        });

        return $rows;
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
