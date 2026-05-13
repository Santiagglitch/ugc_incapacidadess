<?php

declare(strict_types=1);

class adminRolesModel
{
    private string $path;
    private static ?array $adminsCache = null;
    private const MAX_ADMINS = 10;

    public function __construct()
    {
        $this->path = __DIR__ . '/../storage/private/admins_adicionales.json';
    }

    public function getAdminsAdicionales(): array
    {
        if (self::$adminsCache !== null) {
            return self::$adminsCache;
        }

        if (!is_file($this->path)) {
            return self::$adminsCache = [];
        }

        $data = json_decode((string) file_get_contents($this->path), true);
        if (!is_array($data)) {
            return self::$adminsCache = [];
        }

        if (isset($data['admins']) && is_array($data['admins'])) {
            return self::$adminsCache = array_values(array_map('strval', $data['admins']));
        }

        return self::$adminsCache = array_values(array_map('strval', $data));
    }

    public function esAdminAdicional(string $nit): bool
    {
        return in_array($nit, $this->getAdminsAdicionales(), true);
    }

    public function agregarAdmin(string $nit): bool
    {
        $nit = preg_replace('/\D/', '', $nit);
        if ($nit === '') {
            return false;
        }

        $admins = $this->getAdminsAdicionales();

        if (in_array($nit, $admins, true)) {
            return true;
        }

        if (count($admins) >= self::MAX_ADMINS) {
            return false;
        }

        $admins[] = $nit;

        return $this->guardar($admins);
    }

    public function quitarAdmin(string $nit): bool
    {
        $admins = array_values(array_filter($this->getAdminsAdicionales(), function ($item) use ($nit) {
            return $item !== $nit;
        }));

        return $this->guardar($admins);
    }

    public function getLimiteMaximo(): int
    {
        return self::MAX_ADMINS;
    }

    public function alcanzaLimite(): bool
    {
        return count($this->getAdminsAdicionales()) >= self::MAX_ADMINS;
    }

    private function guardar(array $admins): bool
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload  = ['admins' => array_values(array_unique($admins)), 'version' => 1];
        $json     = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tmpPath  = $this->path . '.tmp.' . getmypid();

        if (file_put_contents($tmpPath, $json, LOCK_EX) === false) {
            @unlink($tmpPath);
            return false;
        }

        if (!rename($tmpPath, $this->path)) {
            @unlink($tmpPath);
            return false;
        }

        self::$adminsCache = $payload['admins'];
        return true;
    }
}
