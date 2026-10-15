<?php

declare(strict_types=1);

namespace App\Export\RRHH;

use App\Export\Shared\EmpleadoInfoEnricher;
use App\Export\Shared\SafeXlsx;
use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
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
        \app_require_feature('EXPORTACIONES');
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

        if (!is_array($todas)) {
            $todas = [];
        }

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

        if (!is_array($misSolicitudes)) {
            $misSolicitudes = [];
        }

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
        usort($rows, function (array $a, array $b): int {
            $fechaA = $this->parsearFecha($a['FECHA_CREACION'] ?? null);
            $fechaB = $this->parsearFecha($b['FECHA_CREACION'] ?? null);

            $timeA = $fechaA ? $fechaA->getTimestamp() : 0;
            $timeB = $fechaB ? $fechaB->getTimestamp() : 0;

            if ($timeA !== $timeB) {
                return $timeA <=> $timeB;
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

                /*
                    El ID ya no se exporta, pero se sigue usando internamente
                    para evitar solicitudes duplicadas entre hojas.
                */
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

        if (method_exists($xlsx, 'setDefaultFont')) {
            $xlsx->setDefaultFont('Calibri');
        }

        if (method_exists($xlsx, 'setDefaultFontSize')) {
            $xlsx->setDefaultFontSize(11);
        }

        foreach ($data as $tituloHoja => $rows) {
            if (!is_array($rows)) {
                $rows = [];
            }

            $xlsx->addSheet(
                $this->prepararFilas($rows),
                $this->limpiarTituloHoja($tituloHoja)
            );

            /*
                Importante:
                Los anchos se aplican después de crear la hoja.
                Si se aplican antes, SimpleXLSXGen puede lanzar error con rows.
            */
            $this->aplicarAnchosColumnas($xlsx);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        SafeXlsx::descargar($xlsx, $nombreArchivo . '_' . date('Ymd_His') . '.xlsx');
        exit;
    }

    private function prepararFilas(array $rows): array
    {
        $filas = [];

        /*
            Primera fila con diseño.
        */
        $filas[] = $this->prepararCabecerasConDiseno();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

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

                /*
                    FECHA INICIO y FECHA FIN:
                    solo fecha, sin hora 00:00.
                    Ejemplo: 18/06/2026
                */
                if (in_array($campo, ['FECHA_INICIO', 'FECHA_FIN'], true)) {
                    $valor = $this->formatearFechaSolo($valor);
                }

                /*
                    FECHA CREACION y FECHA GESTION RRHH:
                    fecha + hora en formato AM/PM.
                    Ejemplo: 17/06/2026 08:57 AM
                */
                if (in_array($campo, ['FECHA_CREACION', 'FECHA_GESTION_RRHH'], true)) {
                    $valor = $this->formatearFechaHoraAmPm($valor);
                }

                $fila[] = $this->celdaConDiseno($valor, $campo);
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    private function prepararCabecerasConDiseno(): array
    {
        $cabeceras = [];

        foreach ($this->cabeceras as $cabecera) {
            $valor = $this->limpiarValorParaExcel($cabecera);

            $cabeceras[] =
                '<style bgcolor="#063125" color="#FFFFFF" border="thin">' .
                '<b><center><middle><raw>' . $valor . '</raw></middle></center></b>' .
                '</style>';
        }

        return $cabeceras;
    }

    private function celdaConDiseno($valor, string $campo): string
    {
        $valor = $this->limpiarValorParaExcel($valor);

        /*
            Columnas de texto largo.
            Quedan con wraptext para que el texto no se riegue hacia otras columnas.
        */
        if (in_array($campo, ['OBSERVACIONES', 'OBSERVACION_JEFE', 'OBSERVACION_RRHH'], true)) {
            return
                '<style border="thin">' .
                '<wraptext><top><left><raw>' . $valor . '</raw></left></top></wraptext>' .
                '</style>';
        }

        /*
            Nombre y centro de costo pueden ser largos.
        */
        if (in_array($campo, ['NOMBRE_EMPLEADO', 'NOMBRE_CENTRO_COSTO'], true)) {
            return
                '<style border="thin">' .
                '<wraptext><middle><left><raw>' . $valor . '</raw></left></middle></wraptext>' .
                '</style>';
        }

        /*
            Resto de columnas centradas.
        */
        return
            '<style border="thin">' .
            '<center><middle><raw>' . $valor . '</raw></middle></center>' .
            '</style>';
    }

    private function aplicarAnchosColumnas(SimpleXLSXGen $xlsx): void
    {
        if (!method_exists($xlsx, 'setColWidth')) {
            return;
        }

        try {
            /*
                Columnas después de quitar ID:

                1  A NIT EMPLEADO
                2  B NOMBRE COMPLETO
                3  C FACULTAD / CENTRO DE COSTO
                4  D NIT JEFE
                5  E NIT RRHH
                6  F TIPO SOLICITUD
                7  G FECHA INICIO
                8  H FECHA FIN
                9  I HORAS
                10 J DIAS
                11 K ESTADO
                12 L OBSERVACIONES
                13 M OBSERVACION JEFE
                14 N OBSERVACION RRHH
                15 O FECHA CREACION
                16 P FECHA GESTION RRHH
            */
            $xlsx
                ->setColWidth(1, 18)
                ->setColWidth(2, 35)
                ->setColWidth(3, 38)
                ->setColWidth(4, 18)
                ->setColWidth(5, 18)
                ->setColWidth(6, 25)
                ->setColWidth(7, 16)
                ->setColWidth(8, 16)
                ->setColWidth(9, 10)
                ->setColWidth(10, 10)
                ->setColWidth(11, 24)
                ->setColWidth(12, 90)
                ->setColWidth(13, 45)
                ->setColWidth(14, 45)
                ->setColWidth(15, 24)
                ->setColWidth(16, 24);
        } catch (\Throwable $e) {
            /*
                Si tu versión de SimpleXLSXGen falla con setColWidth,
                no detenemos la exportación.
            */
        }
    }

    private function limpiarValorParaExcel($valor): string
    {
        if ($valor === null) {
            return '';
        }

        if ($valor instanceof \DateTimeInterface) {
            $valor = $valor->format('Y-m-d H:i:s');
        }

        $valor = trim((string)$valor);

        /*
            Normaliza saltos de línea.
        */
        $valor = str_replace(["\r\n", "\r"], "\n", $valor);

        /*
            Evita que textos que empiezan con =, +, - o @ se interpreten como fórmulas.
        */
        if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@'], true)) {
            $valor = "'" . $valor;
        }

        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatearFechaSolo($valor): string
    {
        $fecha = $this->parsearFecha($valor);

        if (!$fecha) {
            return is_scalar($valor) ? trim((string)$valor) : '';
        }

        return $fecha->format('d/m/Y');
    }

    private function formatearFechaHoraAmPm($valor): string
    {
        $fecha = $this->parsearFecha($valor);

        if (!$fecha) {
            return is_scalar($valor) ? trim((string)$valor) : '';
        }

        return $fecha->format('d/m/Y h:i A');
    }

    private function parsearFecha($valor): ?\DateTimeImmutable
    {
        if ($valor === null) {
            return null;
        }

        if ($valor instanceof \DateTimeImmutable) {
            return $valor;
        }

        if ($valor instanceof \DateTimeInterface) {
            return new \DateTimeImmutable($valor->format('Y-m-d H:i:s'));
        }

        $valor = trim((string)$valor);

        if ($valor === '') {
            return null;
        }

        $formatos = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y G:i',
            'd/m/Y h:i A',
            'd/m/Y h:i:s A',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:s',
            'Y-m-d',
            'd/m/Y',
        ];

        foreach ($formatos as $formato) {
            $fecha = \DateTimeImmutable::createFromFormat($formato, $valor);

            if ($fecha instanceof \DateTimeImmutable) {
                return $fecha;
            }
        }

        try {
            return new \DateTimeImmutable($valor);
        } catch (\Throwable $e) {
            return null;
        }
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
