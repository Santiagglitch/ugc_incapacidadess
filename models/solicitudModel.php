<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModelUXXI.php';

if (!defined('ESTADO_CANCELADO_AGENDA')) {
    define('ESTADO_CANCELADO_AGENDA', 'CANCELADO_AGENDA');
}

class solicitudModel extends mainModelUXXI
{
    private const LIST_SELECT = "ID, NIT_EMPLEADO, NIT_JEFE, NIT_RRHH, TIPO_SOLICITUD, DURACION_HORAS, DURACION_DIAS,
        OBSERVACIONES, OBSERVACION_JEFE, OBSERVACION_RRHH, RUTA_COMPROBANTE, ESTADO,
        TO_CHAR(FECHA_INICIO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_INICIO,
        TO_CHAR(FECHA_FIN, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_FIN,
        TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION,
        TO_CHAR(FECHA_GESTION_JEFE, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_JEFE,
        TO_CHAR(FECHA_GESTION_RRHH, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_RRHH";

    private const DETAIL_SELECT = "ID, NIT_EMPLEADO, NIT_JEFE, NIT_RRHH, TIPO_SOLICITUD, DURACION_HORAS, DURACION_DIAS,
        TIPO_DURACION, NIT_CANCELADOR, MOTIVO_CANCELACION, OBSERVACIONES, OBSERVACION_JEFE, OBSERVACION_RRHH, RUTA_COMPROBANTE, ESTADO, ACTIVO,
        TO_CHAR(FECHA_INICIO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_INICIO,
        TO_CHAR(FECHA_FIN, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_FIN,
        TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION,
        TO_CHAR(FECHA_MODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_MODIFICACION,
        TO_CHAR(FECHA_GESTION_JEFE, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_JEFE,
        TO_CHAR(FECHA_GESTION_RRHH, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_RRHH,
        TO_CHAR(FECHA_CANCELACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CANCELACION";

    public function crear(array $data): bool
    {
        $estado = (string)($data['estado'] ?? 'PENDIENTE_JEFE');

        $tipoDuracion = isset($data['tipo_duracion']) && $data['tipo_duracion'] !== '' ? $data['tipo_duracion'] : null;

        return $this->ejecutar(
            "INSERT INTO SOLICITUDES_PERMISOS
                (NIT_EMPLEADO, NIT_JEFE, TIPO_SOLICITUD, FECHA_INICIO, FECHA_FIN,
                 DURACION_HORAS, DURACION_DIAS, TIPO_DURACION, OBSERVACIONES, RUTA_COMPROBANTE,
                 ESTADO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION)
             VALUES
                (:nit_empleado, :nit_jefe, :tipo_solicitud,
                 TO_DATE(:fecha_inicio, 'YYYY-MM-DD'), TO_DATE(:fecha_fin, 'YYYY-MM-DD'),
                 :duracion_horas, :duracion_dias, :tipo_duracion, :observaciones, :ruta_comprobante,
                 :estado, 1, SYSDATE, SYSDATE)",
            [
                ':nit_empleado'     => $data['nit_empleado'],
                ':nit_jefe'         => $data['nit_jefe'],
                ':tipo_solicitud'   => $data['tipo_solicitud'],
                ':fecha_inicio'     => $data['fecha_inicio'],
                ':fecha_fin'        => $data['fecha_fin'],
                ':duracion_horas'   => $data['duracion_horas'],
                ':duracion_dias'    => $data['duracion_dias'],
                ':tipo_duracion'    => $tipoDuracion,
                ':observaciones'    => $data['observaciones'],
                ':ruta_comprobante' => $data['ruta_comprobante'],
                ':estado'           => $estado,
            ]
        );
    }

    public function getUltimoIdByEmpleado(string $nit): int
    {
        $row = $this->consultarUno(
            "SELECT ID
             FROM SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC
             FETCH FIRST 1 ROWS ONLY",
            [':nit' => $nit]
        );

        return (int)($row['ID'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        return $this->consultarUno(
            "SELECT " . self::DETAIL_SELECT . "
             FROM SOLICITUDES_PERMISOS
             WHERE ID=:id AND ACTIVO=1",
            [':id' => $id]
        );
    }

    public function getByEmpleado(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC",
            [':nit' => $nit]
        );
    }

    public function getPendientesJefe(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit
               AND NIT_EMPLEADO<>:nit
               AND ESTADO='PENDIENTE_JEFE'
               AND ACTIVO=1
             ORDER BY ID ASC",
            [':nit' => $nit]
        );
    }

    public function getGestionadasByJefe(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit
               AND NIT_EMPLEADO<>:nit
               AND ACTIVO=1
               AND ESTADO IN ('APROBADO_JEFE','RECHAZADO_JEFE','APROBADO_RRHH','RECHAZADO_RRHH','CANCELADO_AGENDA')
             ORDER BY FECHA_GESTION_JEFE DESC",
            [':nit' => $nit]
        );
    }

    public function getPendientesRRHH(): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS
             WHERE ACTIVO=1
               AND ESTADO='APROBADO_JEFE'
             ORDER BY ID ASC"
        );
    }

    public function getHistoricoRRHH(): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS
             WHERE ACTIVO=1
             ORDER BY FECHA_CREACION DESC"
        );
    }

    public function getAll(array $filtros = []): array
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);

        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql
             ORDER BY FECHA_CREACION DESC",
            $binds
        );
    }

    public function getAllPaginado(array $filtros = [], int $pagina = 1, int $porPagina = 6, string $orden = 'FECHA_CREACION DESC'): array
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);

        return $this->paginar(
            "SELECT " . self::LIST_SELECT . "
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql",
            $binds,
            $orden,
            $pagina,
            $porPagina
        );
    }

    public function getByEmpleadoPaginado(string $nit, int $pagina = 1, int $porPagina = 6, array $filtros = []): array
    {
        $filtros['nit_empleado'] = $nit;

        return $this->getAllPaginado($filtros, $pagina, $porPagina, 'FECHA_CREACION DESC');
    }

    public function getPendientesJefePaginado(string $nit, int $pagina = 1, int $porPagina = 6, array $filtros = []): array
    {
        $filtros['nit_jefe'] = $nit;
        $filtros['excluir_empleado'] = $nit;
        $filtros['estado'] = ESTADO_PENDIENTE_JEFE;

        return $this->getAllPaginado($filtros, $pagina, $porPagina, 'ID ASC');
    }

    public function getGestionadasByJefePaginado(string $nit, int $pagina = 1, int $porPagina = 6, array $filtros = []): array
    {
        $filtros['nit_jefe'] = $nit;
        $filtros['excluir_empleado'] = $nit;
        $filtros['estados'] = [
            ESTADO_APROBADO_JEFE,
            ESTADO_RECHAZADO_JEFE,
            ESTADO_APROBADO_RRHH,
            ESTADO_RECHAZADO_RRHH,
            ESTADO_CANCELADO_AGENDA,
        ];

        return $this->getAllPaginado($filtros, $pagina, $porPagina, 'FECHA_GESTION_JEFE DESC');
    }

    public function contarTotal(array $filtros = []): int
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);

        $row = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql",
            $binds
        );

        return (int)($row['TOTAL'] ?? 0);
    }

    public function contarPorEstado(array $filtros = []): array
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);

        $rows = $this->consultarTodo(
            "SELECT ESTADO, COUNT(*) AS TOTAL
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql
             GROUP BY ESTADO",
            $binds
        );

        $result = [];

        foreach ($rows as $row) {
            $result[$row['ESTADO']] = (int)$row['TOTAL'];
        }

        return $result;
    }

    public function contarPorNits(array $nits): array
    {
        $nits = array_values(array_unique(array_filter(
            array_map('strval', $nits),
            static fn(string $nit): bool => trim($nit) !== ''
        )));

        $result = [];
        foreach ($nits as $nit) {
            $result[$nit] = ['total' => 0, 'activas' => 0];
        }

        if (empty($nits)) {
            return $result;
        }

        [$inClause, $binds] = $this->buildInClause($nits, 'nit_conteo_');

        $rows = $this->consultarTodo(
            "SELECT s.NIT_EMPLEADO,
                    COUNT(*) AS TOTAL,
                    SUM(CASE
                        WHEN s.ESTADO IN ('PENDIENTE_JEFE', 'APROBADO_JEFE', 'APROBADO_RRHH') THEN 1
                        ELSE 0
                    END) AS ACTIVAS
             FROM SOLICITUDES_PERMISOS s
             WHERE s.ACTIVO=1
               AND s.NIT_EMPLEADO IN ($inClause)
             GROUP BY s.NIT_EMPLEADO",
            $binds
        );

        foreach ($rows as $row) {
            $nit = (string)($row['NIT_EMPLEADO'] ?? '');
            if ($nit === '') {
                continue;
            }

            $result[$nit] = [
                'total' => (int)($row['TOTAL'] ?? 0),
                'activas' => (int)($row['ACTIVAS'] ?? 0),
            ];
        }

        return $result;
    }

    public function kpiAdmin(array $filtros = []): array
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);

        $tipoTop = $this->consultarUno(
            "SELECT s.TIPO_SOLICITUD, COUNT(*) AS TOTAL
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql
             GROUP BY s.TIPO_SOLICITUD
             ORDER BY TOTAL DESC, s.TIPO_SOLICITUD ASC
             FETCH FIRST 1 ROWS ONLY",
            $binds
        ) ?? [];

        $promedio = $this->consultarUno(
            "SELECT ROUND(AVG(NVL(s.DURACION_HORAS, 0) + (NVL(s.DURACION_DIAS, 0) * 8)), 2) AS PROMEDIO_HORAS
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql",
            $binds
        ) ?? [];

        $diaTop = $this->consultarUno(
            "SELECT TRIM(TO_CHAR(s.FECHA_CREACION, 'DAY', 'NLS_DATE_LANGUAGE=SPANISH')) AS DIA, COUNT(*) AS TOTAL
             FROM SOLICITUDES_PERMISOS s
             WHERE $whereSql
             GROUP BY TRIM(TO_CHAR(s.FECHA_CREACION, 'DAY', 'NLS_DATE_LANGUAGE=SPANISH'))
             ORDER BY TOTAL DESC, DIA ASC
             FETCH FIRST 1 ROWS ONLY",
            $binds
        ) ?? [];

        $promedioHoras = $promedio['PROMEDIO_HORAS'] ?? null;
        if ($promedioHoras !== null) {
            $promedioHoras = round((float)str_replace(',', '.', (string)$promedioHoras), 2);
        }

        return [
            'tipo_top' => (string)($tipoTop['TIPO_SOLICITUD'] ?? ''),
            'tipo_top_cnt' => (int)($tipoTop['TOTAL'] ?? 0),
            'promedio_horas' => $promedioHoras,
            'dia_top' => (string)($diaTop['DIA'] ?? ''),
            'dia_top_cnt' => (int)($diaTop['TOTAL'] ?? 0),
        ];
    }

    public function contarPorMes(int $meses = 6): array
    {
        $meses = max(1, min(24, $meses));
        $inicio = (new DateTimeImmutable('first day of this month'))->modify('-' . ($meses - 1) . ' months');

        $serie = [];
        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->modify('+' . $i . ' months');
            $clave = $mes->format('Y-m');
            $serie[$clave] = [
                'mes' => $clave,
                'label' => $clave,
                'total' => 0,
            ];
        }

        $rows = $this->consultarTodo(
            "SELECT TO_CHAR(TRUNC(s.FECHA_CREACION, 'MM'), 'YYYY-MM') AS MES,
                    COUNT(*) AS TOTAL
             FROM SOLICITUDES_PERMISOS s
             WHERE s.ACTIVO=1
               AND s.FECHA_CREACION >= TO_DATE(:fecha_desde, 'YYYY-MM-DD')
             GROUP BY TRUNC(s.FECHA_CREACION, 'MM')
             ORDER BY MES ASC",
            [':fecha_desde' => $inicio->format('Y-m-d')]
        );

        foreach ($rows as $row) {
            $mes = (string)($row['MES'] ?? '');
            if (!isset($serie[$mes])) {
                continue;
            }

            $serie[$mes]['total'] = (int)($row['TOTAL'] ?? 0);
        }

        return array_values($serie);
    }

    public function getResumenEmpleado(string $nit): array
    {
        $row = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL,
                    SUM(CASE WHEN ESTADO='PENDIENTE_JEFE' THEN 1 ELSE 0 END) AS PENDIENTES,
                    SUM(CASE WHEN ESTADO IN ('APROBADO_JEFE','APROBADO_RRHH') THEN 1 ELSE 0 END) AS APROBADAS,
                    SUM(CASE WHEN ESTADO IN ('RECHAZADO_JEFE','RECHAZADO_RRHH') THEN 1 ELSE 0 END) AS RECHAZADAS,
                    SUM(CASE WHEN ESTADO='CANCELADO_AGENDA' THEN 1 ELSE 0 END) AS CANCELADAS
             FROM SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1",
            [':nit' => $nit]
        ) ?? [];

        return [
            'total' => (int)($row['TOTAL'] ?? 0),
            'pendientes' => (int)($row['PENDIENTES'] ?? 0),
            'aprobadas' => (int)($row['APROBADAS'] ?? 0),
            'rechazadas' => (int)($row['RECHAZADAS'] ?? 0),
            'canceladas' => (int)($row['CANCELADAS'] ?? 0),
        ];
    }

    public function getResumenJefe(string $nit): array
    {
        return [
            'pendientes' => $this->contarTotal([
                'nit_jefe' => $nit,
                'excluir_empleado' => $nit,
                'estado' => ESTADO_PENDIENTE_JEFE,
            ]),
            'gestionadas' => $this->contarTotal([
                'nit_jefe' => $nit,
                'excluir_empleado' => $nit,
                'estados' => [
                    ESTADO_APROBADO_JEFE,
                    ESTADO_RECHAZADO_JEFE,
                    ESTADO_APROBADO_RRHH,
                    ESTADO_RECHAZADO_RRHH,
                    ESTADO_CANCELADO_AGENDA,
                ],
            ]),
            'misSolicitudes' => $this->contarTotal(['nit_empleado' => $nit]),
        ];
    }

    public function aprobarJefe(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarJefe($id, $nit, $obs, 'APROBADO_JEFE');
    }

    public function aprobarJefeTalentoHumano(int $id, string $nitJefe, string $obs): bool
    {
        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET ESTADO=:estado,
                 FECHA_GESTION_JEFE=SYSDATE,
                 OBSERVACION_JEFE=:obs_jefe,
                 NIT_RRHH=NULL,
                 FECHA_GESTION_RRHH=SYSDATE,
                 OBSERVACION_RRHH=:obs_rrhh
             WHERE ID=:id
               AND NIT_JEFE=:nit_jefe
               AND NIT_EMPLEADO<>:nit_jefe
               AND ESTADO='PENDIENTE_JEFE'
               AND ACTIVO=1",
            [
                ':estado' => ESTADO_APROBADO_RRHH,
                ':obs_jefe' => $obs,
                ':obs_rrhh' => 'Aprobacion final automatica: solicitud propia de Talento Humano aprobada por jefe.',
                ':id' => $id,
                ':nit_jefe' => $nitJefe,
            ]
        );
    }

    public function rechazarJefe(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarJefe($id, $nit, $obs, 'RECHAZADO_JEFE');
    }

    public function aprobarRRHH(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarRRHH($id, $nit, $obs, 'APROBADO_RRHH');
    }

    public function rechazarRRHH(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarRRHH($id, $nit, $obs, 'RECHAZADO_RRHH');
    }

    public function adminIntervenir(int $id, string $nitAdmin, string $obs, string $decision): bool
    {
        $decision = strtolower(trim($decision));
        $obs = trim(preg_replace('/\s+/', ' ', $obs) ?? '');

        if (!in_array($decision, ['aprobar', 'rechazar'], true) || $obs === '') {
            return false;
        }

        $estado = $decision === 'aprobar' ? ESTADO_APROBADO_RRHH : ESTADO_RECHAZADO_RRHH;
        $obsAdmin = 'Intervencion administrativa (' . $decision . '): ' . $obs;
        $estadosIntervenibles = [
            ESTADO_PENDIENTE_JEFE,
            ESTADO_APROBADO_JEFE,
            ESTADO_RECHAZADO_JEFE,
            ESTADO_RECHAZADO_RRHH,
        ];
        [$inClause, $estadoBinds] = $this->buildInClause($estadosIntervenibles, 'admin_intervenir_estado_');

        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET ESTADO=:estado,
                 NIT_RRHH=:nit_admin,
                 FECHA_GESTION_RRHH=SYSDATE,
                 FECHA_MODIFICACION=SYSDATE,
                 OBSERVACION_RRHH=CASE
                    WHEN OBSERVACION_RRHH IS NULL THEN :obs
                    ELSE SUBSTR(OBSERVACION_RRHH || CHR(10) || :obs2, 1, 2000)
                 END
             WHERE ID=:id
               AND ACTIVO=1
               AND NIT_EMPLEADO<>:nit_admin2
               AND ESTADO IN ($inClause)",
            [
                ':estado'    => $estado,
                ':nit_admin' => $nitAdmin,
                ':nit_admin2'=> $nitAdmin,
                ':obs'       => $obsAdmin,
                ':obs2'      => $obsAdmin,
                ':id'        => $id,
            ] + $estadoBinds
        );
    }

    public function adminForzarEstado(int $id, string $nitAdmin, string $nuevoEstado, string $motivo): bool
    {
        $nuevoEstado = trim($nuevoEstado);
        $motivo = trim(preg_replace('/\s+/', ' ', $motivo) ?? '');
        $estadosPermitidos = [
            ESTADO_PENDIENTE_JEFE,
            ESTADO_APROBADO_JEFE,
            ESTADO_RECHAZADO_JEFE,
            ESTADO_APROBADO_RRHH,
            ESTADO_RECHAZADO_RRHH,
        ];

        if (!in_array($nuevoEstado, $estadosPermitidos, true) || $motivo === '') {
            return false;
        }

        $esEstadoRrhh = in_array($nuevoEstado, [ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH], true) ? 1 : 0;
        $esEstadoJefe = in_array($nuevoEstado, [ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE], true) ? 1 : 0;
        $obsAdmin = 'Estado forzado por admin a ' . $nuevoEstado . ': ' . $motivo;

        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET ESTADO=:estado,
                 FECHA_MODIFICACION=SYSDATE,
                 NIT_RRHH=CASE WHEN :es_rrhh=1 THEN :nit_admin ELSE NIT_RRHH END,
                 FECHA_GESTION_RRHH=CASE WHEN :es_rrhh2=1 THEN SYSDATE ELSE FECHA_GESTION_RRHH END,
                 OBSERVACION_RRHH=CASE
                    WHEN :es_rrhh3=1 THEN
                        CASE
                            WHEN OBSERVACION_RRHH IS NULL THEN :obs_rrhh
                            ELSE SUBSTR(OBSERVACION_RRHH || CHR(10) || :obs_rrhh2, 1, 2000)
                        END
                    ELSE OBSERVACION_RRHH
                 END,
                 FECHA_GESTION_JEFE=CASE WHEN :es_jefe=1 THEN SYSDATE ELSE FECHA_GESTION_JEFE END,
                 OBSERVACION_JEFE=CASE
                    WHEN :es_jefe2=1 THEN
                        CASE
                            WHEN OBSERVACION_JEFE IS NULL THEN :obs_jefe
                            ELSE SUBSTR(OBSERVACION_JEFE || CHR(10) || :obs_jefe2, 1, 2000)
                        END
                    ELSE OBSERVACION_JEFE
                 END
             WHERE ID=:id
               AND ACTIVO=1
               AND NIT_EMPLEADO<>:nit_admin2",
            [
                ':estado'    => $nuevoEstado,
                ':nit_admin' => $nitAdmin,
                ':nit_admin2'=> $nitAdmin,
                ':es_rrhh'   => $esEstadoRrhh,
                ':es_rrhh2'  => $esEstadoRrhh,
                ':es_rrhh3'  => $esEstadoRrhh,
                ':es_jefe'   => $esEstadoJefe,
                ':es_jefe2'  => $esEstadoJefe,
                ':obs_rrhh'  => $obsAdmin,
                ':obs_rrhh2' => $obsAdmin,
                ':obs_jefe'  => $obsAdmin,
                ':obs_jefe2' => $obsAdmin,
                ':id'        => $id,
            ]
        );
    }

public function cancelarAgenda(int $id, string $nitUsuario, bool $esGestor = false, string $observacionCancelacion = ''): bool
{
    $observacionCancelacion = trim($observacionCancelacion);

    if ($observacionCancelacion === '') {
        return false;
    }

    $esGestorInt = $esGestor ? 1 : 0;

    $solicitudActual = $this->consultarUno(
        "SELECT OBSERVACIONES
         FROM SOLICITUDES_PERMISOS
         WHERE ID = :id
           AND ACTIVO = 1
           AND ESTADO IN ('APROBADO_JEFE', 'APROBADO_RRHH', 'PENDIENTE_APROBACION_RRHH')
           AND (
                NIT_EMPLEADO = :nit_usuario_chk
                OR :es_gestor_chk = 1
           )",
        [
            ':id'             => $id,
            ':nit_usuario_chk' => $nitUsuario,
            ':es_gestor_chk'  => $esGestorInt,
        ]
    );

    if (!$solicitudActual) {
        return false;
    }

    $motivoCancelacion = trim(preg_replace('/\s+/', ' ', $observacionCancelacion) ?? '');

    return $this->ejecutar(
        "UPDATE SOLICITUDES_PERMISOS
         SET ESTADO = :estado,
             FECHA_MODIFICACION = SYSDATE,
             FECHA_GESTION_JEFE = SYSDATE,
             MOTIVO_CANCELACION = :motivo_cancelacion,
             NIT_CANCELADOR = :nit_cancelador,
             FECHA_CANCELACION = SYSDATE
         WHERE ID = :id
           AND ACTIVO = 1
           AND ESTADO IN ('APROBADO_JEFE', 'APROBADO_RRHH', 'PENDIENTE_APROBACION_RRHH')
           AND (
                NIT_EMPLEADO = :nit_usuario_upd
                OR :es_gestor_upd = 1
           )",
        [
            ':estado'             => ESTADO_CANCELADO_AGENDA,
            ':motivo_cancelacion' => $motivoCancelacion,
            ':nit_cancelador'     => $nitUsuario,
            ':id'                 => $id,
            ':nit_usuario_upd'    => $nitUsuario,
            ':es_gestor_upd'      => $esGestorInt,
        ]
    );
}

    public function eliminar(int $id, string $nit, bool $permitirAprobadoJefe = false): bool
    {
        $permitirAprobadoJefe = $permitirAprobadoJefe ? 1 : 0;

        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET ACTIVO=0,
                 FECHA_MODIFICACION=SYSDATE
             WHERE ID=:id
               AND NIT_EMPLEADO=:nit
               AND (
                    ESTADO='PENDIENTE_JEFE'
                    OR (:permitir_aprobado_jefe=1 AND ESTADO='APROBADO_JEFE')
               )
               AND ACTIVO=1",
            [
                ':id' => $id,
                ':nit' => $nit,
                ':permitir_aprobado_jefe' => $permitirAprobadoJefe,
            ]
        );
    }

    private function buildSolicitudWhere(array $filtros): array
    {
        $where = ['s.ACTIVO=1'];
        $binds = [];

        if (!empty($filtros['estado'])) {
            $where[] = 's.ESTADO=:estado';
            $binds[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['estados']) && is_array($filtros['estados'])) {
            $estados = array_values(array_unique(array_filter(
                array_map('strval', $filtros['estados']),
                static fn(string $estado): bool => $estado !== ''
            )));

            if (!empty($estados)) {
                [$inClause, $estadoBinds] = $this->buildInClause($estados, 'estado_');
                $where[] = "s.ESTADO IN ($inClause)";
                $binds += $estadoBinds;
            }
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 's.TIPO_SOLICITUD=:tipo';
            $binds[':tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['nit'])) {
            $where[] = '(s.NIT_EMPLEADO=:nit_emp OR s.NIT_JEFE=:nit_jefe)';
            $binds[':nit_emp'] = $filtros['nit'];
            $binds[':nit_jefe'] = $filtros['nit'];
        }

        if (!empty($filtros['nit_empleado'])) {
            $where[] = 's.NIT_EMPLEADO=:nit_empleado';
            $binds[':nit_empleado'] = $filtros['nit_empleado'];
        }

        if (!empty($filtros['nit_jefe'])) {
            $where[] = 's.NIT_JEFE=:nit_jefe_directo';
            $binds[':nit_jefe_directo'] = $filtros['nit_jefe'];
        }

        if (!empty($filtros['excluir_empleado'])) {
            $where[] = 's.NIT_EMPLEADO<>:excluir_empleado';
            $binds[':excluir_empleado'] = $filtros['excluir_empleado'];
        }

        if (!empty($filtros['fecha_desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filtros['fecha_desde'])) {
            $where[] = "s.FECHA_CREACION >= TO_DATE(:fecha_desde, 'YYYY-MM-DD')";
            $binds[':fecha_desde'] = (string)$filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filtros['fecha_hasta'])) {
            $where[] = "s.FECHA_CREACION < TO_DATE(:fecha_hasta, 'YYYY-MM-DD') + 1";
            $binds[':fecha_hasta'] = (string)$filtros['fecha_hasta'];
        }

        $busqueda = $this->normalizarFiltroBusqueda((string)($filtros['q'] ?? ''));

        if ($busqueda !== '') {
            $like = '%' . $this->escapeLike(mb_strtoupper($busqueda, 'UTF-8')) . '%';

            $condicionesBusqueda = [
                "TO_CHAR(s.ID) LIKE :q_like ESCAPE '\\'",
                "UPPER(s.NIT_EMPLEADO) LIKE :q_like ESCAPE '\\'",
                "UPPER(s.NIT_JEFE) LIKE :q_like ESCAPE '\\'",
                "UPPER(NVL(s.NIT_RRHH, '')) LIKE :q_like ESCAPE '\\'",
                "UPPER(s.TIPO_SOLICITUD) LIKE :q_like ESCAPE '\\'",
                "UPPER(s.ESTADO) LIKE :q_like ESCAPE '\\'",
                "UPPER(NVL(s.OBSERVACIONES, '')) LIKE :q_like ESCAPE '\\'",
                "EXISTS (
                    SELECT 1
                    FROM ICEBERG.EMPLEADO emp
                    WHERE emp.EMPRESA='BA2'
                      AND emp.ESTADO='A'
                      AND emp.NIT IN (s.NIT_EMPLEADO, s.NIT_JEFE)
                      AND UPPER(TRIM(emp.NOMBRE || ' ' || emp.PRIMER_APELLIDO || ' ' || NVL(emp.SEGUNDO_APELLIDO, ''))) LIKE :q_like ESCAPE '\\'
                )",
            ];

            $binds[':q_like'] = $like;

            $tipos = $this->tiposSolicitudCoincidentes($busqueda);

            if (!empty($tipos)) {
                [$inClause, $tipoBinds] = $this->buildInClause($tipos, 'tipo_busqueda_');
                $condicionesBusqueda[] = "s.TIPO_SOLICITUD IN ($inClause)";
                $binds += $tipoBinds;
            }

            $where[] = '(' . implode(' OR ', $condicionesBusqueda) . ')';
        }

        return [implode(' AND ', $where), $binds];
    }

    private function normalizarFiltroBusqueda(string $busqueda): string
    {
        $busqueda = trim(preg_replace('/\s+/', ' ', $busqueda) ?? '');

        if ($busqueda === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($busqueda, 0, 100, 'UTF-8')
            : substr($busqueda, 0, 100);
    }

    private function escapeLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }

    private function tiposSolicitudCoincidentes(string $busqueda): array
    {
        $needle = $this->normalizarTextoBusqueda($busqueda);

        if ($needle === '') {
            return [];
        }

        $tipos = [];

        foreach (TIPOS_SOLICITUD as $key => $label) {
            $texto = $this->normalizarTextoBusqueda((string)$key . ' ' . (string)$label);

            if ($texto !== '' && str_contains($texto, $needle)) {
                $tipos[] = (string)$key;
            }
        }

        return $tipos;
    }

    private function normalizarTextoBusqueda(string $texto): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        $texto = function_exists('mb_strtolower')
            ? mb_strtolower($texto, 'UTF-8')
            : strtolower($texto);

        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

            if ($ascii !== false) {
                $texto = $ascii;
            }
        }

        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/i', ' ', $texto) ?? '';

        return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    }

    private function paginar(string $sqlBase, array $binds, string $orden, int $pagina, int $porPagina): array
    {
        $porPagina = max(1, $porPagina);
        $total = $this->contarSqlBase($sqlBase, $binds);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $pagina = max(1, min($totalPaginas, $pagina));
        $offset = ($pagina - 1) * $porPagina;

        $orden = $this->ordenSeguro($orden);

        $rows = $this->consultarTodo(
            "$sqlBase
             ORDER BY $orden
             OFFSET $offset ROWS FETCH NEXT $porPagina ROWS ONLY",
            $binds
        );

        return [
            'rows' => $rows,
            'pagination' => [
                'current' => $pagina,
                'totalPages' => $totalPaginas,
                'total' => $total,
                'perPage' => $porPagina,
            ],
        ];
    }

    private function contarSqlBase(string $sqlBase, array $binds): int
    {
        $row = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL FROM ($sqlBase)",
            $binds
        );

        return (int)($row['TOTAL'] ?? 0);
    }

    private function ordenSeguro(string $orden): string
    {
        $permitidos = [
            'FECHA_CREACION DESC',
            'FECHA_CREACION ASC',
            'FECHA_GESTION_JEFE DESC',
            'FECHA_MODIFICACION DESC',
            'ID ASC',
        ];

        return in_array($orden, $permitidos, true) ? $orden : 'FECHA_CREACION DESC';
    }

    public function actualizarSolicitudEmpleado(int $id, string $nitEmpleado, array $data, bool $permitirAprobadoJefe = false): bool
    {
        $permitirAprobadoJefe = $permitirAprobadoJefe ? 1 : 0;

        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET TIPO_SOLICITUD=:tipo_solicitud,
                 NIT_JEFE=:nit_jefe,
                 FECHA_INICIO=TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                 FECHA_FIN=TO_DATE(:fecha_fin, 'YYYY-MM-DD'),
                 DURACION_HORAS=:duracion_horas,
                 DURACION_DIAS=:duracion_dias,
                 OBSERVACIONES=:observaciones,
                 RUTA_COMPROBANTE=:ruta_comprobante,
                 FECHA_MODIFICACION=SYSDATE
             WHERE ID=:id
               AND NIT_EMPLEADO=:nit_empleado
               AND (
                    ESTADO='PENDIENTE_JEFE'
                    OR (:permitir_aprobado_jefe=1 AND ESTADO='APROBADO_JEFE')
               )
               AND ACTIVO=1",
            [
                ':tipo_solicitud' => $data['tipo_solicitud'],
                ':nit_jefe' => $data['nit_jefe'],
                ':fecha_inicio' => $data['fecha_inicio'],
                ':fecha_fin' => $data['fecha_fin'],
                ':duracion_horas' => $data['duracion_horas'],
                ':duracion_dias' => $data['duracion_dias'],
                ':observaciones' => $data['observaciones'],
                ':ruta_comprobante' => $data['ruta_comprobante'],
                ':id' => $id,
                ':nit_empleado' => $nitEmpleado,
                ':permitir_aprobado_jefe' => $permitirAprobadoJefe,
            ]
        );
    }

    private function gestionarJefe(int $id, string $nit, string $obs, string $estado): bool
    {
        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET ESTADO=:estado,
                 FECHA_GESTION_JEFE=SYSDATE,
                 OBSERVACION_JEFE=:obs
             WHERE ID=:id
               AND NIT_JEFE=:nit
               AND NIT_EMPLEADO<>:nit
               AND ESTADO='PENDIENTE_JEFE'
               AND ACTIVO=1",
            [
                ':estado' => $estado,
                ':obs' => $obs,
                ':id' => $id,
                ':nit' => $nit,
            ]
        );
    }

    private function gestionarRRHH(int $id, string $nit, string $obs, string $estado): bool
    {
        return $this->ejecutar(
            "UPDATE SOLICITUDES_PERMISOS
             SET ESTADO=:estado,
                 NIT_RRHH=:nit,
                 FECHA_GESTION_RRHH=SYSDATE,
                 OBSERVACION_RRHH=:obs
             WHERE ID=:id
               AND ESTADO='APROBADO_JEFE'
               AND ACTIVO=1",
            [
                ':estado' => $estado,
                ':obs' => $obs,
                ':id' => $id,
                ':nit' => $nit,
            ]
        );
    }
}
