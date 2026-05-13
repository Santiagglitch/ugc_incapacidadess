<?php

declare(strict_types=1);

class notaAdminService
{
    private string $path;

    public function __construct()
    {
        $this->path = __DIR__ . '/../storage/private/notas_admin.json';
    }

    public function guardar(int $idSolicitud, string $nitAdmin, string $nombreAdmin, string $nota): bool
    {
        $notas = $this->leer();
        $key   = (string)$idSolicitud;

        if (!isset($notas[$key])) {
            $notas[$key] = [];
        }

        array_unshift($notas[$key], [
            'fecha'  => date('Y-m-d H:i:s'),
            'nit'    => $nitAdmin,
            'nombre' => $nombreAdmin,
            'nota'   => $nota,
        ]);

        $notas[$key] = array_slice($notas[$key], 0, 20);

        return $this->escribir($notas);
    }

    public function getByIdSolicitud(int $idSolicitud): array
    {
        $notas = $this->leer();
        return $notas[(string)$idSolicitud] ?? [];
    }

    private function leer(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($this->path), true);
        return is_array($data) ? $data : [];
    }

    private function escribir(array $notas): bool
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $tmp = $this->path . '.tmp.' . getmypid();
        if (file_put_contents($tmp, json_encode($notas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            @unlink($tmp);
            return false;
        }
        if (!rename($tmp, $this->path)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}
