<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/mainModelUXXI.php';

class notaAdminService extends mainModelUXXI
{
    public function guardar(int $idSolicitud, string $nitAdmin, string $nombreAdmin, string $nota): bool
    {
        $ahoraCo = self::SQL_NOW_CO;

        return $this->ejecutar(
            "INSERT INTO NOTAS_ADMIN (ID, ID_SOLICITUD, NIT_ADMIN, NOMBRE_ADMIN, NOTA, FECHA_CREACION)
             VALUES (SEQ_NOTAS_ADMIN.NEXTVAL, :id_solicitud, :nit_admin, :nombre_admin, :nota, $ahoraCo)",
            [
                ':id_solicitud' => $idSolicitud,
                ':nit_admin'    => $nitAdmin,
                ':nombre_admin' => $nombreAdmin,
                ':nota'         => $nota,
            ]
        );
    }

    public function getByIdSolicitud(int $idSolicitud): array
    {
        $rows = $this->consultarTodo(
            "SELECT NIT_ADMIN AS nit,
                    NOMBRE_ADMIN AS nombre,
                    NOTA AS nota,
                    TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS fecha
             FROM NOTAS_ADMIN
             WHERE ID_SOLICITUD = :id
             ORDER BY FECHA_CREACION DESC",
            [':id' => $idSolicitud]
        );

        return array_map(static fn($r) => [
            'fecha'  => (string)($r['FECHA']  ?? ''),
            'nit'    => (string)($r['NIT']    ?? ''),
            'nombre' => (string)($r['NOMBRE'] ?? ''),
            'nota'   => (string)($r['NOTA']   ?? ''),
        ], $rows);
    }
}
