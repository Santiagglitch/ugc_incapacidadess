<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/mainModelUXXI.php';

class sesionActivaService extends mainModelUXXI
{
    private int $ttl;

    public function __construct(int $ttlSegundos = 1800)
    {
        $this->ttl = $ttlSegundos;
    }

    public function registrar(array $usuario): void
    {
        $sid = session_id();
        $nit = (string)($usuario['cedula'] ?? '');
        if ($sid === '' || $nit === '') {
            return;
        }

        $this->purgar();

        $nombre      = (string)($usuario['nombre']       ?? '');
        $rol         = (string)($usuario['rol']          ?? '');
        $centroCosto = (string)($usuario['centro_costo'] ?? '');
        $ip          = $this->ip();
        $userAgent   = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $existe = $this->consultarUno(
            "SELECT SESSION_ID FROM SESIONES_ACTIVAS WHERE SESSION_ID = :sid",
            [':sid' => $sid]
        );

        if ($existe) {
            $this->ejecutar(
                "UPDATE SESIONES_ACTIVAS
                 SET NIT=:nit, NOMBRE=:nombre, ROL=:rol, CENTRO_COSTO=:cc,
                     IP=:ip, USER_AGENT=:ua, ULTIMA_ACT=SYSDATE
                 WHERE SESSION_ID=:sid",
                [':nit' => $nit, ':nombre' => $nombre, ':rol' => $rol,
                 ':cc' => $centroCosto, ':ip' => $ip, ':ua' => $userAgent, ':sid' => $sid]
            );
        } else {
            $this->ejecutar(
                "INSERT INTO SESIONES_ACTIVAS
                    (SESSION_ID, NIT, NOMBRE, ROL, CENTRO_COSTO, IP, USER_AGENT, ULTIMA_ACT, FECHA_INICIO)
                 VALUES
                    (:sid, :nit, :nombre, :rol, :cc, :ip, :ua, SYSDATE, SYSDATE)",
                [':sid' => $sid, ':nit' => $nit, ':nombre' => $nombre, ':rol' => $rol,
                 ':cc' => $centroCosto, ':ip' => $ip, ':ua' => $userAgent]
            );
        }
    }

    public function getSesionesActivas(): array
    {
        $this->purgar();

        $rows = $this->consultarTodo(
            "SELECT SESSION_ID, NIT, NOMBRE, ROL, CENTRO_COSTO, IP, USER_AGENT,
                    TO_CHAR(ULTIMA_ACT,'YYYY-MM-DD HH24:MI:SS') AS ULTIMA_ACT_STR,
                    (SYSDATE - ULTIMA_ACT) * 86400 AS SEGUNDOS_INACTIVO
             FROM SESIONES_ACTIVAS
             ORDER BY ULTIMA_ACT DESC"
        );

        return array_map(function (array $r): array {
            $seg = (int)($r['SEGUNDOS_INACTIVO'] ?? 0);
            return [
                'nit'          => (string)($r['NIT']          ?? ''),
                'nombre'       => (string)($r['NOMBRE']       ?? ''),
                'rol'          => (string)($r['ROL']          ?? ''),
                'centro_costo' => (string)($r['CENTRO_COSTO'] ?? ''),
                'ip'           => (string)($r['IP']           ?? ''),
                'user_agent'   => (string)($r['USER_AGENT']   ?? ''),
                'ultima_act'   => strtotime((string)($r['ULTIMA_ACT_STR'] ?? '')) ?: time(),
                'hace'         => $this->hace($seg),
            ];
        }, $rows);
    }

    public function cerrar(string $sid = ''): void
    {
        if ($sid === '') {
            $sid = session_id();
        }
        if ($sid === '') {
            return;
        }
        $this->ejecutar(
            "DELETE FROM SESIONES_ACTIVAS WHERE SESSION_ID = :sid",
            [':sid' => $sid]
        );
    }

    private function purgar(): void
    {
        $this->ejecutar(
            "DELETE FROM SESIONES_ACTIVAS
             WHERE ULTIMA_ACT < SYSDATE - (:ttl / 86400)",
            [':ttl' => $this->ttl]
        );
    }

    private function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
    }

    private function hace(int $segundos): string
    {
        $segundos = max(0, $segundos);
        if ($segundos < 60)   return 'hace ' . $segundos . 's';
        if ($segundos < 3600) return 'hace ' . (int)($segundos / 60) . ' min';
        return 'hace ' . (int)($segundos / 3600) . 'h';
    }
}
