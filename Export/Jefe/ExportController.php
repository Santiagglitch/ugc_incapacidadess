<?php

declare(strict_types=1);

namespace App\Export\Jefe;

use App\Export\Shared\EmpleadoInfoEnricher;
use App\Export\Shared\SafeXlsx;
use Shuchkin\SimpleXLSXGen;

final class ExportController
{
    private array $cabeceras = [
        'ID',
        'NIT EMPLEADO',
        'NOMBRE COMPLETO',
        'DEPENDENCIA',
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
        'NOMBRE_EMPLEADO',
        'NOMBRE_DEPENDENCIA',
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

        /*
            Unimos el historial con canceladas para que "Historial Gestionado"
            también coincida con la tarjeta del panel.
        */
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

        /*
            MISMO ORDEN Y MISMOS NOMBRES DE LAS TARJETAS DEL PANEL
        */
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
        /*
            Estado real que estás usando en el sistema:
            ESTADO_CANCELADO_AGENDA = CANCELADO_AGENDA
        */
        $estadosCancelados = [
            $this->constante('ESTADO_CANCELADO_AGENDA', 'CANCELADO_AGENDA'),
            'CANCELADO_AGENDA',
            'CANCELADO',
            'CANCELADA',
        ];

        /*
            1. Primero intenta sacarlas del historial gestionado.
            Si tu getGestionadasByJefe ya trae CANCELADO_AGENDA, aquí funciona.
        */
        $canceladas = $this->filtrarPorEstados($historialGestionado, $estadosCancelados);
        $canceladas = $this->filtrarSoloEquipoDelJefe($canceladas, $nitJefe);

        if (!empty($canceladas)) {
            return $canceladas;
        }

        /*
            2. Si no salieron en el historial, intenta con getByJefe.
            Esto sirve si tu modelo tiene ese método.
        */
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

        /*
            3. Último respaldo: usa getAll filtrando por estado.
            Luego vuelve a filtrar por NIT_JEFE para evitar traer datos que no sean del jefe.
        */
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
