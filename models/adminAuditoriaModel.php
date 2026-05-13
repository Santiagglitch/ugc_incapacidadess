<?php

declare(strict_types=1);

class adminAuditoriaModel
{
    private string $path;
    private const MAX_ENTRADAS = 200;

    public function __construct()
    {
        $this->path = __DIR__ . '/../storage/private/auditoria_admin.json';
    }

    public function registrar(string $nitActor, string $accion, string $descripcion, array $meta = [], string $nombreActor = ''): bool
    {
        $entradas = $this->leer();

        array_unshift($entradas, [
            'fecha'         => date('Y-m-d H:i:s'),
            'nit_actor'     => $nitActor,
            'nombre_actor'  => $nombreActor !== '' ? $nombreActor : $this->resolverNombre($nitActor),
            'accion'        => $accion,
            'descripcion'   => $descripcion,
            'ip'            => $this->ipCliente(),
            'meta'          => $meta,
        ]);

        if (count($entradas) > self::MAX_ENTRADAS) {
            $entradas = array_slice($entradas, 0, self::MAX_ENTRADAS);
        }

        return $this->guardar($entradas);
    }

    public function getEntradas(int $limite = 50): array
    {
        return array_slice($this->leer(), 0, max(1, $limite));
    }

    public function filtrar(string $accion = '', int $pagina = 1, int $porPagina = 20): array
    {
        $todas = $this->leer();

        if ($accion !== '') {
            $todas = array_values(array_filter($todas, fn($e) => ($e['accion'] ?? '') === $accion));
        }

        $total      = count($todas);
        $totalPags  = max(1, (int) ceil($total / $porPagina));
        $pagina     = max(1, min($pagina, $totalPags));
        $filas      = array_slice($todas, ($pagina - 1) * $porPagina, $porPagina);

        return [
            'filas'       => $filas,
            'total'       => $total,
            'pagina'      => $pagina,
            'totalPaginas' => $totalPags,
            'porPagina'   => $porPagina,
        ];
    }

    public function getByNitObjetivo(string $nit, int $limite = 10): array
    {
        $todas = array_values(array_filter(
            $this->leer(),
            fn($e) => isset($e['meta']['nit_objetivo']) && (string)$e['meta']['nit_objetivo'] === $nit
        ));
        return array_slice($todas, 0, max(1, $limite));
    }

    public function getAccionesUnicas(): array
    {
        $acciones = array_unique(array_column($this->leer(), 'accion'));
        sort($acciones);
        return $acciones;
    }

    public function exportCsv(): string
    {
        $entradas = $this->leer();
        $cabeceras = ['Fecha', 'NIT Actor', 'Nombre Actor', 'Accion', 'Descripcion', 'IP'];
        $lineas = [implode(',', array_map([$this, 'csvEscape'], $cabeceras))];

        foreach ($entradas as $e) {
            $lineas[] = implode(',', array_map([$this, 'csvEscape'], [
                $e['fecha']        ?? '',
                $e['nit_actor']    ?? '',
                $e['nombre_actor'] ?? '',
                $e['accion']       ?? '',
                $e['descripcion']  ?? '',
                $e['ip']           ?? '',
            ]));
        }

        return implode("\r\n", $lineas);
    }

    private function resolverNombre(string $nit): string
    {
        if ($nit === '') {
            return '';
        }

        if (defined('APP_ENV') && APP_ENV === 'development' && defined('USUARIOS_PRUEBA')) {
            $nombre = trim((string)(USUARIOS_PRUEBA[$nit]['nombre'] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        if (defined('SUPER_ADMIN_NIT') && $nit === SUPER_ADMIN_NIT) {
            return 'Super Usuario';
        }

        try {
            require_once __DIR__ . '/empleadoModel.php';
            $emp = (new empleadoModel())->getByNit($nit);
            $nombre = trim((string)($emp['NOMBRE_COMPLETO'] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        } catch (Throwable $e) {
        }

        return $nit;
    }

    private function leer(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $raw = file_get_contents($this->path);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function guardar(array $entradas): bool
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json    = json_encode($entradas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tmpPath = $this->path . '.tmp.' . getmypid();

        if (file_put_contents($tmpPath, $json, LOCK_EX) === false) {
            @unlink($tmpPath);
            return false;
        }

        if (!rename($tmpPath, $this->path)) {
            @unlink($tmpPath);
            return false;
        }

        return true;
    }

    private function ipCliente(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        return 'desconocida';
    }

    private function csvEscape(string $valor): string
    {
        $valor = str_replace('"', '""', $valor);
        if (strpbrk($valor, ",\"\r\n") !== false) {
            $valor = '"' . $valor . '"';
        }
        return $valor;
    }
}
