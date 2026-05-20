<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/mainModelUXXI.php';

class loginBloqueoService extends mainModelUXXI
{
    private const MAX_INTENTOS = 3;
    private const VENTANA      = 900;

    public function verificar(string $cedula): ?string
    {
        $clave = md5($cedula);
        $ahora = time();

        $row = $this->consultarUno(
            "SELECT INTENTOS, PRIMER_INT FROM LOGIN_BLOQUEOS WHERE CLAVE = :clave AND EXPIRA >= SYSDATE",
            [':clave' => $clave]
        );

        if (!$row) {
            return null;
        }

        $intentos  = (int)($row['INTENTOS']  ?? 0);
        $primerInt = (int)($row['PRIMER_INT'] ?? $ahora);

        if ($intentos >= self::MAX_INTENTOS && ($ahora - $primerInt) < self::VENTANA) {
            $restante = self::VENTANA - ($ahora - $primerInt);
            return 'Cuenta bloqueada por seguridad. Intenta de nuevo en ' . ceil($restante / 60) . ' minuto(s).';
        }

        return null;
    }

    public function registrarFallido(string $cedula): int
    {
        $clave = md5($cedula);
        $ahora = time();
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $this->limpiarExpirados();

        $row = $this->consultarUno(
            "SELECT INTENTOS FROM LOGIN_BLOQUEOS WHERE CLAVE = :clave",
            [':clave' => $clave]
        );

        $nuevosIntentos = (int)($row['INTENTOS'] ?? 0) + 1;
        $expira         = date('Y-m-d H:i:s', $ahora + self::VENTANA);

        if ($row) {
            $this->ejecutar(
                "UPDATE LOGIN_BLOQUEOS
                 SET INTENTOS=:intentos, ULTIMA_ACT=:ultima, IP_ULTIMA=:ip
                 WHERE CLAVE=:clave",
                [
                    ':intentos' => $nuevosIntentos,
                    ':ultima'   => $ahora,
                    ':ip'       => $ip,
                    ':clave'    => $clave,
                ]
            );
        } else {
            $this->ejecutar(
                "INSERT INTO LOGIN_BLOQUEOS (CLAVE, INTENTOS, PRIMER_INT, ULTIMA_ACT, IP_ULTIMA, EXPIRA)
                 VALUES (:clave, :intentos, :primer_int, :ultima, :ip,
                         TO_DATE(:expira,'YYYY-MM-DD HH24:MI:SS'))",
                [
                    ':clave'      => $clave,
                    ':intentos'   => $nuevosIntentos,
                    ':primer_int' => $ahora,
                    ':ultima'     => $ahora,
                    ':ip'         => $ip,
                    ':expira'     => $expira,
                ]
            );
        }

        return $nuevosIntentos;
    }

    public function limpiar(string $cedula): void
    {
        $this->ejecutar(
            "DELETE FROM LOGIN_BLOQUEOS WHERE CLAVE = :clave",
            [':clave' => md5($cedula)]
        );
    }

    private function limpiarExpirados(): void
    {
        $conn = $this->conectar();
        if ($conn) {
            $stmt = @oci_parse($conn, "DELETE FROM LOGIN_BLOQUEOS WHERE EXPIRA < SYSDATE");
            if ($stmt) {
                @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
                @oci_free_statement($stmt);
            }
        }
    }
}
