<?php

declare(strict_types=1);

class adminRolesModel
{
    private string $path;
    private static ?array $adminsCache = null;

    public function __construct()
    {
        $this->path = __DIR__ . '/../data/admins_adicionales.json';
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
        if (!in_array($nit, $admins, true)) {
            $admins[] = $nit;
        }

        return $this->guardar($admins);
    }

    public function quitarAdmin(string $nit): bool
    {
        $admins = array_values(array_filter($this->getAdminsAdicionales(), function ($item) use ($nit) {
            return $item !== $nit;
        }));

        return $this->guardar($admins);
    }

    private function guardar(array $admins): bool
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = ['admins' => array_values(array_unique($admins)), 'version' => 1];
        $ok = file_put_contents($this->path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;

        if ($ok) {
            self::$adminsCache = $payload['admins'];
        }

        return $ok;
    }
}
