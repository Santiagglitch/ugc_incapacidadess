<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este instalador solo se puede ejecutar desde consola.');
}

require_once __DIR__ . '/../config/conexion.php';

function ora_execute($conn, string $sql): void
{
    $stmt = oci_parse($conn, $sql);
    if (!$stmt) {
        $error = oci_error($conn);
        throw new RuntimeException($error['message'] ?? 'No se pudo preparar SQL.');
    }

    if (!@oci_execute($stmt)) {
        $error = oci_error($stmt);
        oci_free_statement($stmt);
        throw new RuntimeException($error['message'] ?? 'No se pudo ejecutar SQL.');
    }

    oci_free_statement($stmt);
}

function ora_exists($conn, string $sql): bool
{
    $stmt = oci_parse($conn, $sql);
    if (!$stmt || !@oci_execute($stmt)) {
        if ($stmt) {
            oci_free_statement($stmt);
        }
        return false;
    }

    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    return (int)($row['TOTAL'] ?? 0) > 0;
}

$conn = Conexion::conectar();

$tableExists = ora_exists(
    $conn,
    "SELECT COUNT(*) AS TOTAL FROM ALL_TABLES WHERE OWNER='ICEBERG' AND TABLE_NAME='NOTIFICACIONES'"
);

if (!$tableExists) {
    ora_execute(
        $conn,
        "CREATE TABLE ICEBERG.NOTIFICACIONES (
            ID NUMBER(12) NOT NULL,
            NIT_DESTINATARIO VARCHAR2(30) NOT NULL,
            TIPO VARCHAR2(60) NOT NULL,
            MENSAJE VARCHAR2(1000) NOT NULL,
            ID_SOLICITUD NUMBER(12) NOT NULL,
            LEIDA NUMBER(1) DEFAULT 0 NOT NULL,
            FECHA_CREACION DATE DEFAULT SYSDATE NOT NULL,
            FECHA_LECTURA DATE,
            CONSTRAINT PK_NOTIFICACIONES PRIMARY KEY (ID)
        )"
    );
    echo "Tabla ICEBERG.NOTIFICACIONES creada.\n";
} else {
    echo "Tabla ICEBERG.NOTIFICACIONES ya existe.\n";
}

$sequenceExists = ora_exists(
    $conn,
    "SELECT COUNT(*) AS TOTAL FROM ALL_SEQUENCES WHERE SEQUENCE_OWNER='ICEBERG' AND SEQUENCE_NAME='SEQ_NOTIFICACIONES'"
);

if (!$sequenceExists) {
    ora_execute(
        $conn,
        "CREATE SEQUENCE ICEBERG.SEQ_NOTIFICACIONES
            START WITH 1
            INCREMENT BY 1
            NOCACHE
            NOCYCLE"
    );
    echo "Secuencia ICEBERG.SEQ_NOTIFICACIONES creada.\n";
} else {
    echo "Secuencia ICEBERG.SEQ_NOTIFICACIONES ya existe.\n";
}

ora_execute(
    $conn,
    "CREATE OR REPLACE TRIGGER ICEBERG.TRG_NOTIFICACIONES_BI
     BEFORE INSERT ON ICEBERG.NOTIFICACIONES
     FOR EACH ROW
     BEGIN
         IF :NEW.ID IS NULL THEN
             SELECT ICEBERG.SEQ_NOTIFICACIONES.NEXTVAL
             INTO :NEW.ID
             FROM DUAL;
         END IF;
     END;"
);
echo "Trigger ICEBERG.TRG_NOTIFICACIONES_BI creado/actualizado.\n";

$indexDestExists = ora_exists(
    $conn,
    "SELECT COUNT(*) AS TOTAL FROM ALL_INDEXES WHERE OWNER='ICEBERG' AND INDEX_NAME='IDX_NOTIF_DEST_LEIDA'"
);

if (!$indexDestExists) {
    ora_execute(
        $conn,
        "CREATE INDEX ICEBERG.IDX_NOTIF_DEST_LEIDA
         ON ICEBERG.NOTIFICACIONES (NIT_DESTINATARIO, LEIDA, FECHA_CREACION)"
    );
    echo "Indice ICEBERG.IDX_NOTIF_DEST_LEIDA creado.\n";
} else {
    echo "Indice ICEBERG.IDX_NOTIF_DEST_LEIDA ya existe.\n";
}

$indexSolicitudExists = ora_exists(
    $conn,
    "SELECT COUNT(*) AS TOTAL FROM ALL_INDEXES WHERE OWNER='ICEBERG' AND INDEX_NAME='IDX_NOTIF_SOLICITUD'"
);

if (!$indexSolicitudExists) {
    ora_execute(
        $conn,
        "CREATE INDEX ICEBERG.IDX_NOTIF_SOLICITUD
         ON ICEBERG.NOTIFICACIONES (ID_SOLICITUD)"
    );
    echo "Indice ICEBERG.IDX_NOTIF_SOLICITUD creado.\n";
} else {
    echo "Indice ICEBERG.IDX_NOTIF_SOLICITUD ya existe.\n";
}

echo "Instalacion de notificaciones finalizada.\n";

$stmt = oci_parse(
    $conn,
    "SELECT OBJECT_TYPE, OBJECT_NAME, STATUS
     FROM ALL_OBJECTS
     WHERE OWNER='ICEBERG'
       AND OBJECT_NAME IN ('NOTIFICACIONES', 'SEQ_NOTIFICACIONES', 'TRG_NOTIFICACIONES_BI', 'IDX_NOTIF_DEST_LEIDA', 'IDX_NOTIF_SOLICITUD')
     ORDER BY OBJECT_TYPE, OBJECT_NAME"
);

if ($stmt && @oci_execute($stmt)) {
    echo "Objetos encontrados:\n";
    while ($row = oci_fetch_assoc($stmt)) {
        echo "- " . $row['OBJECT_TYPE'] . " " . $row['OBJECT_NAME'] . " " . $row['STATUS'] . "\n";
    }
    oci_free_statement($stmt);
}
