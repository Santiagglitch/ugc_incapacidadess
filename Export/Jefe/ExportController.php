<?php

declare(strict_types=1);

namespace App\Export\Jefe;

use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
        'ID',
        'NIT EMPLEADO',
        'NIT JEFE',
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
        'FECHA GESTION JEFE',
    ];

    private array $campos = [
        'ID',
        'NIT_EMPLEADO',
        'NIT_JEFE',
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
        'FECHA_GESTION_JEFE',
    ];

    public function todasExcel(): void
    {
        require_once __DIR__ . '/../../config/helpers.php';
        require_once __DIR__ . '/../Libraries/SimpleXLSXGen.php';
        require_once __DIR__ . '/../../models/solicitudModel.php';

        \iniciar_sesion_segura();
        \requiere_rol([\ROL_JEFE, \ROL_ADMIN]);

        $user = \usuario_actual();
        $nitJefe = (string)($user['cedula'] ?? '');
        $model = new \solicitudModel();

        $gestionadas = $model->getGestionadasByJefe($nitJefe);

        $data = [
            'Pendientes Aprobacion' => $model->getPendientesJefe($nitJefe),
            'Aprobadas Por Ti' => $this->filtrarPorEstados($gestionadas, [
                \ESTADO_APROBADO_JEFE,
                \ESTADO_APROBADO_RRHH,
                \ESTADO_RECHAZADO_RRHH,
            ]),
            'Rechazadas Por Ti' => $this->filtrarPorEstados($gestionadas, [\ESTADO_RECHAZADO_JEFE]),
            'Mis Solicitudes' => $model->getByEmpleado($nitJefe),
            'Historial Gestionado' => $gestionadas,
        ];

        $this->generarExcelPorHojas($data, 'reporte_jefe');
    }

    private function filtrarPorEstados(array $rows, array $estados): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($estados): bool {
            return in_array((string)($row['ESTADO'] ?? ''), $estados, true);
        }));
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
