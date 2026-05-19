<?php

declare(strict_types=1);

require_once __DIR__ . '/server.php';

class ConexionUXXI
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

        $cadenaConexion = UXXI_HOST . ':' . UXXI_PORT . '/' . UXXI_SERVICE;

        self::$conexion = @oci_connect(UXXI_USER, UXXI_PASS, $cadenaConexion, UXXI_CHARSET);

        if (!self::$conexion) {
            $error = oci_error();
            $mensaje = $error['message'] ?? 'Error desconocido al conectar con Oracle UXXI.';
            error_log('Oracle UXXI connection error: ' . $mensaje);
            throw new RuntimeException('No se pudo conectar con Oracle UXXI.');
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
            @oci_close(self::$conexion);
            self::$conexion = null;
        }
    }
}
