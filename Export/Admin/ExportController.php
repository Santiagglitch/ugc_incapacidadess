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

        $filtrosExport = [];
        $fechaDesde = trim((string)($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string)($_GET['fecha_hasta'] ?? ''));
        if ($fechaDesde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $filtrosExport['fecha_desde'] = $fechaDesde;
        }
        if ($fechaHasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $filtrosExport['fecha_hasta'] = $fechaHasta;
        }

        $todasBase = $model->getAll($filtrosExport);

        $canceladoPorUsuario = $this->obtenerCanceladasPorUsuario($model, $todasBase);

        /*
            Historial completo debe traer todo, incluyendo canceladas.
            Si getAll ya las trae, no se duplican.
        */
        $historialCompleto = $this->unirFilasUnicas(
            $todasBase,
            $canceladoPorUsuario
        );

        $pendientesJefe = $this->ordenarPorFechaCreacionAsc(
            $this->filtrarPorEstados($historialCompleto, [
                $this->constante('ESTADO_PENDIENTE_JEFE', 'PENDIENTE_JEFE'),
            ])
        );

        $pendientesRRHH = $this->ordenarPorFechaCreacionAsc(
            $this->filtrarPorEstados($historialCompleto, [
                $this->constante('ESTADO_APROBADO_JEFE', 'APROBADO_JEFE'),
            ])
        );

        $aprobadasRRHH = $this->filtrarPorEstados($historialCompleto, [
            $this->constante('ESTADO_APROBADO_RRHH', 'APROBADO_RRHH'),
        ]);

        $rechazadasRRHH = $this->filtrarPorEstados($historialCompleto, [
            $this->constante('ESTADO_RECHAZADO_RRHH', 'RECHAZADO_RRHH'),
        ]);

        /*
            MISMO ORDEN Y MISMOS NOMBRES DE LAS TARJETAS DEL PANEL ADMIN
        */
        $data = [
            'Historial completo' => $historialCompleto,
            'Pendientes jefe' => $pendientesJefe,
            'Pendientes RRHH' => $pendientesRRHH,
            'Aprobadas RRHH' => $aprobadasRRHH,
            'Rechazadas RRHH' => $rechazadasRRHH,
            'Cancelado por el usuario' => $canceladoPorUsuario,
        ];

        $this->generarExcelPorHojas($data, 'reporte_admin');
    }

    private function obtenerCanceladasPorUsuario(object $model, array $todas): array
    {
        /*
            Estados posibles de cancelación.
            El principal en tu sistema es CANCELADO_AGENDA.
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
            1. Primero busca dentro de getAll().
        */
        $canceladas = $this->filtrarPorEstados($todas, $estadosCancelados);

        if (!empty($canceladas)) {
            return $this->unirFilasUnicas($canceladas);
        }

        /*
            2. Si getAll() no las trae, intenta métodos posibles del modelo.
            No rompe el sistema si alguno no existe.
        */
        $metodosPosibles = [
            'getCanceladasPorUsuario',
            'getCanceladasUsuario',
            'getCanceladas',
            'getCanceladasAgenda',
            'getCanceladasAdmin',
            'getCanceladasPorAdmin',
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
                        Si el método existe pero necesita parámetros,
                        no detenemos la descarga del Excel.
                    */
                }
            }
        }

        if (!empty($resultado)) {
            return $resultado;
        }

        /*
            3. Último respaldo:
            intenta getAll filtrando por estado.
            Esto sirve si tu modelo acepta filtros.
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
                        Si getAll no acepta filtros, seguimos sin romper.
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

                if (
                    $campo === 'TIPO_SOLICITUD'
                    && defined('TIPOS_SOLICITUD')
                    && isset(\TIPOS_SOLICITUD[$valor])
                ) {
                    $valor = \TIPOS_SOLICITUD[$valor];
                }

                $fila[] = $this->normalizarValor($valor);
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

        if ($titulo === '') {
            $titulo = 'Hoja';
        }

        return mb_substr($titulo, 0, 31);
    }
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    require_once __DIR__ . '/../../config/helpers.php';
    \iniciar_sesion_segura();
    if (!\usuario_autenticado()) {
        http_response_code(401);
        exit('No autenticado.');
    }
    if (!\in_array(\rol_actual(), [\ROL_ADMIN], true)) {
        http_response_code(403);
        exit('Sin permiso.');
    }
    (new ExportController())->todasExcel();
}