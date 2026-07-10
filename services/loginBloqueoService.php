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
        $ahoraCo = self::SQL_NOW_CO;

        $row = $this->consultarUno(
            "SELECT INTENTOS,
                    ROUND((EXPIRA - $ahoraCo) * 86400) AS SEGUNDOS_RESTANTES
             FROM LOGIN_BLOQUEOS
             WHERE CLAVE = :clave AND EXPIRA >= $ahoraCo",
            [':clave' => $clave]
        );

        if (!$row) {
            return null;
        }

        $intentos  = (int)($row['INTENTOS'] ?? 0);
        $restantes = max(0, (int)($row['SEGUNDOS_RESTANTES'] ?? 0));

        if ($intentos >= self::MAX_INTENTOS) {
            return 'Cuenta bloqueada por seguridad. Intenta de nuevo en ' . (int)ceil($restantes / 60) . ' minuto(s).';
        }

        return null;
    }

    public function registrarFallido(string $cedula): int
    {
        $clave = md5($cedula);
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ahoraCo = self::SQL_NOW_CO;

        $this->limpiarExpirados();

        $row = $this->consultarUno(
            "SELECT INTENTOS FROM LOGIN_BLOQUEOS WHERE CLAVE = :clave AND EXPIRA >= $ahoraCo",
            [':clave' => $clave]
        );

        $nuevosIntentos = (int)($row['INTENTOS'] ?? 0) + 1;

        if ($row) {
            $this->ejecutar(
                "UPDATE LOGIN_BLOQUEOS
                 SET INTENTOS=:intentos, ULTIMA_ACT=:ultima_act, IP_ULTIMA=:ip
                 WHERE CLAVE=:clave",
                [
                    ':intentos'  => $nuevosIntentos,
                    ':ultima_act' => time(),
                    ':ip'        => $ip,
                    ':clave'     => $clave,
                ]
            );
        } else {
            $ahora = time();
            $this->ejecutar(
                "INSERT INTO LOGIN_BLOQUEOS (CLAVE, INTENTOS, PRIMER_INT, ULTIMA_ACT, IP_ULTIMA, EXPIRA)
                 VALUES (:clave, :intentos, :primer_int, :ultima_act, :ip,
                         $ahoraCo + (:ventana / 86400))",
                [
                    ':clave'      => $clave,
                    ':intentos'   => $nuevosIntentos,
                    ':primer_int' => $ahora,
                    ':ultima_act' => $ahora,
                    ':ip'         => $ip,
                    ':ventana'    => self::VENTANA,
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
        $ahoraCo = self::SQL_NOW_CO;

        $this->ejecutar("DELETE FROM LOGIN_BLOQUEOS WHERE EXPIRA < $ahoraCo");
    }
}
