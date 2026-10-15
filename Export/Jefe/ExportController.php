<?php

declare(strict_types=1);

namespace App\Export\Jefe;

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
        'NIT_EMPLEADO',
        'NOMBRE_EMPLEADO',
        'NOMBRE_CENTRO_COSTO',
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
        \app_require_feature('EXPORTACIONES');
        require_once __DIR__ . '/../Libraries/SimpleXLSXGen.php';
        require_once __DIR__ . '/../Shared/EmpleadoInfoEnricher.php';
        require_once __DIR__ . '/../Shared/SafeXlsx.php';
        require_once __DIR__ . '/../../models/solicitudModel.php';

        \iniciar_sesion_segura();
        \requiere_rol([\ROL_JEFE, \ROL_ADMIN]);

        $user = \usuario_actual();
        $nitJefe = (string)($user['cedula'] ?? '');

        $model = new \solicitudModel();

        $pendientesAprobacion = $model->getPendientesJefe($nitJefe);
        $misSolicitudes = $model->getByEmpleado($nitJefe);
        $historialGestionadoBase = $model->getGestionadasByJefe($nitJefe);

        $canceladasPorUsuario = $this->obtenerCanceladasPorUsuario(
            $model,
            $nitJefe,
            $historialGestionadoBase
        );

        $historialGestionado = $this->unirFilasUnicas(
            $historialGestionadoBase,
            $canceladasPorUsuario
        );

        $aprobadasPorTi = $this->filtrarPorEstados($historialGestionado, [
            $this->constante('ESTADO_APROBADO_JEFE', 'APROBADO_JEFE'),
            $this->constante('ESTADO_APROBADO_RRHH', 'APROBADO_RRHH'),
            $this->constante('ESTADO_RECHAZADO_RRHH', 'RECHAZADO_RRHH'),
        ]);

        $rechazadasPorTi = $this->filtrarPorEstados($historialGestionado, [
            $this->constante('ESTADO_RECHAZADO_JEFE', 'RECHAZADO_JEFE'),
        ]);

        $data = [
            'Historial Gestionado' => $historialGestionado,
            'Pendientes de Aprobacion' => $pendientesAprobacion,
            'Aprobadas por ti' => $aprobadasPorTi,
            'Rechazadas por ti' => $rechazadasPorTi,
            'Cancelado por el usuario' => $canceladasPorUsuario,
            'Mis Solicitudes' => $misSolicitudes,
        ];

        $data = (new EmpleadoInfoEnricher())->enriquecerHojas($data);

        $this->generarExcelPorHojas($data, 'reporte_jefe');
    }

    private function obtenerCanceladasPorUsuario(object $model, string $nitJefe, array $historialGestionado): array
    {
        $estadosCancelados = [
            $this->constante('ESTADO_CANCELADO_AGENDA', 'CANCELADO_AGENDA'),
            'CANCELADO_AGENDA',
            'CANCELADO',
            'CANCELADA',
        ];

        $canceladas = $this->filtrarPorEstados($historialGestionado, $estadosCancelados);
        $canceladas = $this->filtrarSoloEquipoDelJefe($canceladas, $nitJefe);

        if (!empty($canceladas)) {
            return $canceladas;
        }

        if (method_exists($model, 'getByJefe')) {
            $rows = $model->getByJefe($nitJefe);

            if (is_array($rows)) {
                $canceladas = $this->filtrarPorEstados($rows, $estadosCancelados);
                $canceladas = $this->filtrarSoloEquipoDelJefe($canceladas, $nitJefe);

                if (!empty($canceladas)) {
                    return $canceladas;
                }
            }
        }

        $resultado = [];

        if (method_exists($model, 'getAll')) {
            foreach ($estadosCancelados as $estado) {
                $rows = $model->getAll([
                    'estado' => $estado,
                    'nit' => $nitJefe,
                    'nit_jefe' => $nitJefe,
                    'excluir_empleado' => $nitJefe,
                ]);

                if (is_array($rows)) {
                    $rows = $this->filtrarPorEstados($rows, [$estado]);
                    $rows = $this->filtrarSoloEquipoDelJefe($rows, $nitJefe);
                    $resultado = $this->unirFilasUnicas($resultado, $rows);
                }
            }
        }

        return $resultado;
    }

    private function filtrarSoloEquipoDelJefe(array $rows, string $nitJefe): array
    {
        $nitJefeNormalizado = $this->normalizarNit($nitJefe);

        return array_values(array_filter($rows, function (array $row) use ($nitJefeNormalizado): bool {
            $nitJefeFila = $this->normalizarNit((string)($row['NIT_JEFE'] ?? ''));
            $nitEmpleadoFila = $this->normalizarNit((string)($row['NIT_EMPLEADO'] ?? ''));

            return $nitJefeFila === $nitJefeNormalizado
                && $nitEmpleadoFila !== $nitJefeNormalizado;
        }));
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

                if (in_array($campo, ['FECHA_INICIO', 'FECHA_FIN'], true)) {
                    $valor = $this->formatearFechaSolo($valor);
                }

                if (in_array($campo, ['FECHA_CREACION', 'FECHA_GESTION_JEFE'], true)) {
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

        if (in_array($campo, ['OBSERVACIONES', 'OBSERVACION_JEFE', 'OBSERVACION_RRHH'], true)) {
            return
                '<style border="thin">' .
                '<wraptext><top><left><raw>' . $valor . '</raw></left></top></wraptext>' .
                '</style>';
        }

        if (in_array($campo, ['NOMBRE_EMPLEADO', 'NOMBRE_CENTRO_COSTO'], true)) {
            return
                '<style border="thin">' .
                '<wraptext><middle><left><raw>' . $valor . '</raw></left></middle></wraptext>' .
                '</style>';
        }

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

        $xlsx
            ->setColWidth(1, 18)
            ->setColWidth(2, 35)
            ->setColWidth(3, 35)
            ->setColWidth(4, 18)
            ->setColWidth(5, 25)
            ->setColWidth(6, 16)
            ->setColWidth(7, 16)
            ->setColWidth(8, 10)
            ->setColWidth(9, 10)
            ->setColWidth(10, 24)
            ->setColWidth(11, 90)
            ->setColWidth(12, 45)
            ->setColWidth(13, 45)
            ->setColWidth(14, 24)
            ->setColWidth(15, 24);
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

    private function normalizarNit(string $nit): string
    {
        $nit = trim($nit);
        $soloNumeros = preg_replace('/\D+/', '', $nit);

        return $soloNumeros !== '' ? $soloNumeros : $nit;
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
