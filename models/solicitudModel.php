<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModel.php';
require_once __DIR__ . '/../config/app.php';

class solicitudModel extends mainModel
{
    private const LIST_SELECT = "ID, NIT_EMPLEADO, NIT_JEFE, NIT_RRHH, TIPO_SOLICITUD, DURACION_HORAS, DURACION_DIAS,
        OBSERVACIONES, OBSERVACION_JEFE, OBSERVACION_RRHH, RUTA_COMPROBANTE, ESTADO,
        TO_CHAR(FECHA_INICIO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_INICIO,
        TO_CHAR(FECHA_FIN, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_FIN,
        TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION,
        TO_CHAR(FECHA_GESTION_JEFE, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_JEFE,
        TO_CHAR(FECHA_GESTION_RRHH, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_RRHH";

    private const DETAIL_SELECT = "ID, NIT_EMPLEADO, NIT_JEFE, NIT_RRHH, TIPO_SOLICITUD, DURACION_HORAS, DURACION_DIAS,
        OBSERVACIONES, OBSERVACION_JEFE, OBSERVACION_RRHH, RUTA_COMPROBANTE, ESTADO, ACTIVO,
        TO_CHAR(FECHA_INICIO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_INICIO,
        TO_CHAR(FECHA_FIN, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_FIN,
        TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION,
        TO_CHAR(FECHA_MODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_MODIFICACION,
        TO_CHAR(FECHA_GESTION_JEFE, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_JEFE,
        TO_CHAR(FECHA_GESTION_RRHH, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_RRHH";

    public function crear(array $data): bool
    {
        $estado = (string)($data['estado'] ?? 'PENDIENTE_JEFE');

        return $this->ejecutar(
            "INSERT INTO ICEBERG.SOLICITUDES_PERMISOS
                (NIT_EMPLEADO, NIT_JEFE, TIPO_SOLICITUD, FECHA_INICIO, FECHA_FIN,
                 DURACION_HORAS, DURACION_DIAS, OBSERVACIONES, RUTA_COMPROBANTE,
                 ESTADO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION)
             VALUES
                (:nit_empleado, :nit_jefe, :tipo_solicitud,
                 TO_DATE(:fecha_inicio, 'YYYY-MM-DD'), TO_DATE(:fecha_fin, 'YYYY-MM-DD'),
                 :duracion_horas, :duracion_dias, :observaciones, :ruta_comprobante,
                 :estado, 1, SYSDATE, SYSDATE)",
            [
                ':nit_empleado' => $data['nit_empleado'],
                ':nit_jefe' => $data['nit_jefe'],
                ':tipo_solicitud' => $data['tipo_solicitud'],
                ':fecha_inicio' => $data['fecha_inicio'],
                ':fecha_fin' => $data['fecha_fin'],
                ':duracion_horas' => $data['duracion_horas'],
                ':duracion_dias' => $data['duracion_dias'],
                ':observaciones' => $data['observaciones'],
                ':ruta_comprobante' => $data['ruta_comprobante'],
                ':estado' => $estado,
            ]
        );
    }

    public function getUltimoIdByEmpleado(string $nit): int
    {
        $row = $this->consultarUno(
            "SELECT ID
             FROM ICEBERG.SOLICITUDES_PERMISOS
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
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ID=:id AND ACTIVO=1",
            [':id' => $id]
        );
    }

    public function getByEmpleado(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC",
            [':nit' => $nit]
        );
    }

    public function getPendientesJefe(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit
               AND NIT_EMPLEADO<>:nit
               AND ESTADO='PENDIENTE_JEFE'
               AND ACTIVO=1
             ORDER BY FECHA_CREACION ASC",
            [':nit' => $nit]
        );
    }

    public function getGestionadasByJefe(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit
               AND NIT_EMPLEADO<>:nit
               AND ACTIVO=1
               AND ESTADO IN ('APROBADO_JEFE','RECHAZADO_JEFE','APROBADO_RRHH','RECHAZADO_RRHH')
             ORDER BY FECHA_GESTION_JEFE DESC",
            [':nit' => $nit]
        );
    }

    public function getPendientesRRHH(): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ACTIVO=1
               AND ESTADO='APROBADO_JEFE'
             ORDER BY FECHA_CREACION ASC"
        );
    }

    public function getHistoricoRRHH(): array
    {
        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ACTIVO=1
             ORDER BY FECHA_CREACION DESC"
        );
    }

    public function getAll(array $filtros = []): array
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);

        return $this->consultarTodo(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
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
             FROM ICEBERG.SOLICITUDES_PERMISOS
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

        return $this->getAllPaginado($filtros, $pagina, $porPagina, 'FECHA_CREACION ASC');
    }

    public function getGestionadasByJefePaginado(string $nit, int $pagina = 1, int $porPagina = 6, array $filtros = []): array
    {
        $filtros['nit_jefe'] = $nit;
        $filtros['excluir_empleado'] = $nit;
        $filtros['estados'] = [ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH];

        return $this->getAllPaginado($filtros, $pagina, $porPagina, 'FECHA_GESTION_JEFE DESC');
    }

    public function contarTotal(array $filtros = []): int
    {
        [$whereSql, $binds] = $this->buildSolicitudWhere($filtros);
        $row = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL
             FROM ICEBERG.SOLICITUDES_PERMISOS
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
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE $whereSql
             GROUP BY ESTADO",
            $binds
        );

        $result = [];

        foreach ($rows as $row) {
            $result[$row['ESTADO']] = (int) $row['TOTAL'];
        }

        return $result;
    }

    public function getResumenEmpleado(string $nit): array
    {
        $row = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL,
                    SUM(CASE WHEN ESTADO='PENDIENTE_JEFE' THEN 1 ELSE 0 END) AS PENDIENTES,
                    SUM(CASE WHEN ESTADO IN ('APROBADO_JEFE','APROBADO_RRHH') THEN 1 ELSE 0 END) AS APROBADAS,
                    SUM(CASE WHEN ESTADO IN ('RECHAZADO_JEFE','RECHAZADO_RRHH') THEN 1 ELSE 0 END) AS RECHAZADAS
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1",
            [':nit' => $nit]
        ) ?? [];

        return [
            'total' => (int) ($row['TOTAL'] ?? 0),
            'pendientes' => (int) ($row['PENDIENTES'] ?? 0),
            'aprobadas' => (int) ($row['APROBADAS'] ?? 0),
            'rechazadas' => (int) ($row['RECHAZADAS'] ?? 0),
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
                'estados' => [ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH],
            ]),
            'misSolicitudes' => $this->contarTotal(['nit_empleado' => $nit]),
        ];
    }

    public function aprobarJefe(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarJefe($id, $nit, $obs, 'APROBADO_JEFE');
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

    public function eliminar(int $id, string $nit, bool $permitirAprobadoJefe = false): bool
    {
        $permitirAprobadoJefe = $permitirAprobadoJefe ? 1 : 0;

        return $this->ejecutar(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
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
        $where = ['ACTIVO=1'];
        $binds = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'ESTADO=:estado';
            $binds[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['estados']) && is_array($filtros['estados'])) {
            $estados = array_values(array_unique(array_filter(
                array_map('strval', $filtros['estados']),
                static fn(string $estado): bool => $estado !== ''
            )));

            if (!empty($estados)) {
                [$inClause, $estadoBinds] = $this->buildInClause($estados, 'estado_');
                $where[] = "ESTADO IN ($inClause)";
                $binds += $estadoBinds;
            }
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'TIPO_SOLICITUD=:tipo';
            $binds[':tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['nit'])) {
            $where[] = '(NIT_EMPLEADO=:nit_emp OR NIT_JEFE=:nit_jefe)';
            $binds[':nit_emp'] = $filtros['nit'];
            $binds[':nit_jefe'] = $filtros['nit'];
        }

        if (!empty($filtros['nit_empleado'])) {
            $where[] = 'NIT_EMPLEADO=:nit_empleado';
            $binds[':nit_empleado'] = $filtros['nit_empleado'];
        }

        if (!empty($filtros['nit_jefe'])) {
            $where[] = 'NIT_JEFE=:nit_jefe_directo';
            $binds[':nit_jefe_directo'] = $filtros['nit_jefe'];
        }

        if (!empty($filtros['excluir_empleado'])) {
            $where[] = 'NIT_EMPLEADO<>:excluir_empleado';
            $binds[':excluir_empleado'] = $filtros['excluir_empleado'];
        }

        return [implode(' AND ', $where), $binds];
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
        ];

        return in_array($orden, $permitidos, true) ? $orden : 'FECHA_CREACION DESC';
    }

    public function actualizarSolicitudEmpleado(int $id, string $nitEmpleado, array $data, bool $permitirAprobadoJefe = false): bool
    {
        $permitirAprobadoJefe = $permitirAprobadoJefe ? 1 : 0;

        return $this->ejecutar(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
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
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
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
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
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
