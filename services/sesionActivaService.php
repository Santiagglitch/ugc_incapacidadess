<?php

declare(strict_types=1);

class sesionActivaService
{
    private string $path;
    private int $ttl;

    public function __construct(int $ttlSegundos = 1800)
    {
        $this->path = __DIR__ . '/../storage/private/sesiones_activas.json';
        $this->ttl  = $ttlSegundos;
    }

    public function registrar(array $usuario): void
    {
        $sid  = session_id();
        $nit  = (string)($usuario['cedula'] ?? '');
        if ($sid === '' || $nit === '') {
            return;
        }

        $sesiones = $this->leer();
        $sesiones[$sid] = [
            'nit'           => $nit,
            'nombre'        => (string)($usuario['nombre']        ?? ''),
            'rol'           => (string)($usuario['rol']           ?? ''),
            'centro_costo'  => (string)($usuario['centro_costo']  ?? ''),
            'ultima_act'    => time(),
            'ip'            => $this->ip(),
            'user_agent'    => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120),
        ];

        $this->purgar($sesiones);
        $this->guardar($sesiones);
    }

    public function getSesionesActivas(): array
    {
        $sesiones = $this->leer();
        $this->purgar($sesiones);
        $this->guardar($sesiones);

        return array_values(
            array_map(fn($s) => $s + ['hace' => $this->hace((int)($s['ultima_act'] ?? 0))], $sesiones)
        );
    }

    public function cerrar(string $sid = ''): void
    {
        if ($sid === '') {
            $sid = session_id();
        }
        $sesiones = $this->leer();
        unset($sesiones[$sid]);
        $this->guardar($sesiones);
    }

    private function purgar(array &$sesiones): void
    {
        $limite = time() - $this->ttl;
        foreach ($sesiones as $sid => $s) {
            if ((int)($s['ultima_act'] ?? 0) < $limite) {
                unset($sesiones[$sid]);
            }
        }
    }

    private function leer(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $raw  = file_get_contents($this->path);
        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    private function guardar(array $sesiones): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $tmp = $this->path . '.tmp.' . getmypid();
        @file_put_contents($tmp, json_encode($sesiones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        @rename($tmp, $this->path);
    }

    private function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
    }

    private function hace(int $ts): string
    {
        $diff = max(0, time() - $ts);
        if ($diff < 60)  return 'hace ' . $diff . 's';
        if ($diff < 3600) return 'hace ' . (int)($diff / 60) . ' min';
        return 'hace ' . (int)($diff / 3600) . 'h';
    }
}
