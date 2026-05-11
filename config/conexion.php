<?php

declare(strict_types=1);

require_once __DIR__ . '/server.php';

class Conexion
{
    private static $conexion = null;

    public static function conectar()
    {
        if (self::$conexion) {
            return self::$conexion;
        }

        if (!function_exists('oci_connect')) {
            throw new RuntimeException('La extension OCI8 no esta habilitada en PHP.');
        }

        if (defined('ORACLE_STATEMENT_CACHE_SIZE')) {
            @ini_set('oci8.statement_cache_size', (string) ORACLE_STATEMENT_CACHE_SIZE);
        }

        $cadenaConexion = ORACLE_HOST . ':' . ORACLE_PORT . '/' . ORACLE_SERVICE;
        $conectar = (defined('ORACLE_PERSISTENT') && ORACLE_PERSISTENT && function_exists('oci_pconnect'))
            ? 'oci_pconnect'
            : 'oci_connect';

        self::$conexion = @$conectar(ORACLE_USER, ORACLE_PASS, $cadenaConexion, ORACLE_CHARSET);

        if (!self::$conexion) {
            $error = oci_error();
            $mensaje = $error['message'] ?? 'Error desconocido al conectar con Oracle.';
            error_log('Oracle connection error: ' . $mensaje);
            throw new RuntimeException('No se pudo conectar con Oracle.');
        }

        return self::$conexion;
    }

    public static function disponible(): bool
    {
        try {
            self::conectar();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function cerrar(): void
    {
        if (self::$conexion) {
            if (!(defined('ORACLE_PERSISTENT') && ORACLE_PERSISTENT)) {
                @oci_close(self::$conexion);
            }
            self::$conexion = null;
        }
    }
}
