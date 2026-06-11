<?php

declare(strict_types=1);

namespace App\Export\RRHH;

use App\Export\Shared\EmpleadoInfoEnricher;
use App\Export\Shared\SafeXlsx;
use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
        'ID',
        'NIT EMPLEADO',
        'NOMBRE COMPLETO',
        'FACULTAD / CENTRO DE COSTO',
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
        'NOMBRE_EMPLEADO',
        'NOMBRE_CENTRO_COSTO',
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
        require_once __DIR__ . '/../Shared/EmpleadoInfoEnricher.php';
        require_once __DIR__ . '/../Shared/SafeXlsx.php';
        require_once __DIR__ . '/../../models/solicitudModel.php';

        \iniciar_sesion_segura();
        \requiere_rol([\ROL_RRHH, \ROL_ADMIN]);

        $user = \usuario_actual();
        $nitRrhh = (string)($user['cedula'] ?? '');
        $model = new \solicitudModel();

        $todas = $model->getAll();

        $pendientesRRHH = $this->ordenarPorFechaCreacionAsc(
            $this->filtrarPorEstados($todas, [
                $this->constante('ESTADO_APROBADO_JEFE', 'APROBADO_JEFE'),
            ])
        );

        $enRevisionJefe = $this->ordenarPorFechaCreacionAsc(
            $this->filtrarPorEstados($todas, [
                $this->constante('ESTADO_PENDIENTE_JEFE', 'PENDIENTE_JEFE'),
            ])
        );

        $aprobadasRRHH = $this->filtrarPorEstados($todas, [
            $this->constante('ESTADO_APROBADO_RRHH', 'APROBADO_RRHH'),
        ]);

        $rechazadasRRHH = $this->filtrarPorEstados($todas, [
            $this->constante('ESTADO_RECHAZADO_RRHH', 'RECHAZADO_RRHH'),
        ]);

        $canceladoPorUsuario = $this->obtenerCanceladasPorUsuario($model, $todas);
        $misSolicitudes = $model->getByEmpleado($nitRrhh);

        /*
            MISMO ORDEN Y MISMOS NOMBRES DE LAS TARJETAS DEL PANEL RRHH
        */
        $data = [
            'Historial completo' => $todas,
            'Pendientes RRHH' => $pendientesRRHH,
            'En Revision Jefe' => $enRevisionJefe,
            'Aprobadas RRHH' => $aprobadasRRHH,
            'Rechazadas RRHH' => $rechazadasRRHH,
            'Cancelado por el usuario' => $canceladoPorUsuario,
            'Mis Solicitudes' => $misSolicitudes,
        ];

        $data = (new EmpleadoInfoEnricher())->enriquecerHojas($data);

        $this->generarExcelPorHojas($data, 'reporte_rrhh');
    }

    private function obtenerCanceladasPorUsuario(object $model, array $todas): array
    {
        /*
            Estados posibles para cancelaciones.
            El más importante para tu sistema es CANCELADO_AGENDA.
        */
        $estadosCancelados = [
            $this->constante('ESTADO_CANCELADO_AGENDA', 'CANCELADO_AGENDA'),
            $this->constante('ESTADO_CANCELADO_USUARIO', 'CANCELADO_USUARIO'),
            $this->constante('ESTADO_CANCELADA_USUARIO', 'CANCELADA_USUARIO'),
            $this->constante('ESTADO_CANCELADO_POR_USUARIO', 'CANCELADO_POR_USUARIO'),
            $this->constante('ESTADO_CANCELADA_POR_USUARIO', 'CANCELADA_POR_USUARIO'),
            'CANCELADO_AGENDA',
            'CANCELADO_USUARIO',
            'CANCELADA_USUARIO',
            'CANCELADO_POR_USUARIO',
            'CANCELADA_POR_USUARIO',
            'CANCELADO',
            'CANCELADA',
        ];

        /*
            1. Primero busca canceladas dentro de getAll().
            Si getAll ya trae todas las solicitudes, con esto debe funcionar.
        */
        $canceladas = $this->filtrarPorEstados($todas, $estadosCancelados);

        if (!empty($canceladas)) {
            return $this->unirFilasUnicas($canceladas);
        }

        /*
            2. Si por alguna razón getAll() no trae canceladas,
            intenta llamar métodos posibles del modelo.
        */
        $metodosPosibles = [
            'getCanceladasPorUsuario',
            'getCanceladasUsuario',
            'getCanceladas',
            'getCanceladasAgenda',
            'getCanceladasRRHH',
            'getCanceladasPorRRHH',
        ];

        $resultado = [];

        foreach ($metodosPosibles as $metodo) {
            if (method_exists($model, $metodo)) {
                try {
                    $rows = $model->{$metodo}();

                    if (is_array($rows)) {
                        $rows = $this->filtrarPorEstados($rows, $estadosCancelados);
                        $resultado = $this->unirFilasUnicas($resultado, $rows);
                    }
                } catch (\Throwable $e) {
                    /*
                        No detenemos la descarga si el método existe,
                        pero no corresponde a esta consulta.
                    */
                }
            }
        }

        if (!empty($resultado)) {
            return $resultado;
        }

        /*
            3. Último respaldo:
            intenta consultar getAll filtrando por estado.
            Esto ayuda cuando el modelo permite filtros por estado.
        */
        if (method_exists($model, 'getAll')) {
            foreach ($estadosCancelados as $estado) {
                try {
                    $rows = $model->getAll([
                        'estado' => $estado,
                    ]);

                    if (is_array($rows)) {
                        $rows = $this->filtrarPorEstados($rows, [$estado]);
                        $resultado = $this->unirFilasUnicas($resultado, $rows);
                    }
                } catch (\Throwable $e) {
                    /*
                        Si getAll no recibe filtros, simplemente seguimos.
                    */
                }
            }
        }

        return $resultado;
    }

    private function filtrarPorEstados(array $rows, array $estados): array
    {
        $estadosNormalizados = array_values(array_unique(array_map(
            fn($estado) => $this->normalizarEstado((string)$estado),
            $estados
        )));

        return array_values(array_filter($rows, function (array $row) use ($estadosNormalizados): bool {
            $estadoFila = $this->normalizarEstado((string)($row['ESTADO'] ?? ''));

            return in_array($estadoFila, $estadosNormalizados, true);
        }));
    }

    private function ordenarPorFechaCreacionAsc(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $fecha = strcmp(
                (string)($a['FECHA_CREACION'] ?? ''),
                (string)($b['FECHA_CREACION'] ?? '')
            );

            if ($fecha !== 0) {
                return $fecha;
            }

            return (int)($a['ID'] ?? 0) <=> (int)($b['ID'] ?? 0);
        });

        return $rows;
    }

    private function unirFilasUnicas(array ...$grupos): array
    {
        $resultado = [];
        $idsUsados = [];

        foreach ($grupos as $rows) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $id = (string)($row['ID'] ?? '');

                if ($id === '') {
                    $id = md5(json_encode($row));
                }

                if (isset($idsUsados[$id])) {
                    continue;
                }

                $idsUsados[$id] = true;
                $resultado[] = $row;
            }
        }

        return $resultado;
    }

    private function generarExcelPorHojas(array $data, string $nombreArchivo): void
    {
        $xlsx = new SimpleXLSXGen();

        foreach ($data as $tituloHoja => $rows) {
            $xlsx->addSheet(
                $this->prepararFilas($rows),
                $this->limpiarTituloHoja($tituloHoja)
            );
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        SafeXlsx::descargar($xlsx, $nombreArchivo . '_' . date('Ymd_His') . '.xlsx');
        exit;
    }

    private function prepararFilas(array $rows): array
    {
        $filas = [$this->cabeceras];

        foreach ($rows as $row) {
            $fila = [];

            foreach ($this->campos as $campo) {
                $valor = $row[$campo] ?? '';

                if (
                    $campo === 'TIPO_SOLICITUD'
                    && defined('TIPOS_SOLICITUD')
                    && isset(\TIPOS_SOLICITUD[$valor])
                ) {
                    $valor = \TIPOS_SOLICITUD[$valor];
                }

                $fila[] = SafeXlsx::valor($valor);
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    private function constante(string $nombre, string $valorDefecto): string
    {
        return defined($nombre) ? (string)constant($nombre) : $valorDefecto;
    }

    private function normalizarEstado(string $estado): string
    {
        return strtoupper(trim($estado));
    }

    private function limpiarTituloHoja(string $titulo): string
    {
        $titulo = trim(str_replace(['\\', '/', '*', '[', ']', ':', '?'], ' ', $titulo));

        if ($titulo === '') {
            $titulo = 'Hoja';
        }

        return mb_substr($titulo, 0, 31);
    }
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    (new ExportController())->todasExcel();
}
