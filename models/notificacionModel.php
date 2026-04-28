<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModel.php';

class notificacionModel extends mainModel
{
    public function crear(string $nitDestinatario, string $tipo, string $mensaje, int $idSolicitud): bool
    {
        return $this->ejecutar(
            "INSERT INTO ICEBERG.NOTIFICACIONES
                (NIT_DESTINATARIO, TIPO, MENSAJE, ID_SOLICITUD, LEIDA, FECHA_CREACION)
             VALUES
                (:nit, :tipo, :mensaje, :solicitud, 0, SYSDATE)",
            [':nit' => $nitDestinatario, ':tipo' => $tipo, ':mensaje' => $mensaje, ':solicitud' => $idSolicitud]
        );
    }

    public function getNoLeidas(string $nit): array
    {
        return $this->consultarTodo(
            "SELECT n.ID, n.NIT_DESTINATARIO, n.TIPO, n.MENSAJE, n.ID_SOLICITUD, n.LEIDA,
                    TO_CHAR(n.FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION,
                    s.TIPO_SOLICITUD, s.NIT_EMPLEADO
             FROM ICEBERG.NOTIFICACIONES n
             JOIN ICEBERG.SOLICITUDES_PERMISOS s ON s.ID = n.ID_SOLICITUD
             WHERE n.NIT_DESTINATARIO=:nit AND n.LEIDA=0 AND s.ACTIVO=1
             ORDER BY n.FECHA_CREACION DESC
             FETCH FIRST 20 ROWS ONLY",
            [':nit' => $nit]
        );
    }

    public function contarNoLeidas(string $nit): int
    {
        $row = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL
             FROM ICEBERG.NOTIFICACIONES n
             JOIN ICEBERG.SOLICITUDES_PERMISOS s ON s.ID = n.ID_SOLICITUD
             WHERE n.NIT_DESTINATARIO=:nit AND n.LEIDA=0 AND s.ACTIVO=1",
            [':nit' => $nit]
        );
        return (int)($row['TOTAL'] ?? 0);
    }

    public function marcarLeida(int $id, string $nit): bool
    {
        return $this->ejecutar(
            "UPDATE ICEBERG.NOTIFICACIONES SET LEIDA=1, FECHA_LECTURA=SYSDATE WHERE ID=:id AND NIT_DESTINATARIO=:nit",
            [':id' => $id, ':nit' => $nit]
        );
    }

    public function marcarTodasLeidas(string $nit): bool
    {
        return $this->ejecutar(
            "UPDATE ICEBERG.NOTIFICACIONES SET LEIDA=1, FECHA_LECTURA=SYSDATE WHERE NIT_DESTINATARIO=:nit AND LEIDA=0",
            [':nit' => $nit]
        );
    }
}