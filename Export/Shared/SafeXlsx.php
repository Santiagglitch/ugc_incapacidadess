<?php

declare(strict_types=1);

namespace App\Export\Shared;

use RuntimeException;
use Shuchkin\SimpleXLSXGen;
use ZipArchive;

final class SafeXlsx
{
    public static function valor($valor): string
    {
        if ($valor === null || is_array($valor) || is_object($valor)) {
            return '';
        }

        $texto = str_replace("\0", '', (string)$valor);

        if ($texto === '') {
            return '';
        }

        $textoTrim = trim($texto);
        $esInterpretado = preg_match('/^[=+\-@]/u', $textoTrim) === 1
            || preg_match('/^(?:https?:\/\/|mailto:|file:|\\\\)/i', $textoTrim) === 1
            || preg_match('/^[a-zA-Z0-9_.-]+@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $textoTrim) === 1
            || str_contains($texto, '<');

        return $esInterpretado ? "\0" . $texto : $texto;
    }

    public static function descargar(SimpleXLSXGen $xlsx, string $nombreArchivo): void
    {
        if (!class_exists(ZipArchive::class)) {
            $xlsx->downloadAs($nombreArchivo);
            return;
        }

        $origen = tempnam(sys_get_temp_dir(), 'ugc_xlsx_src_');
        $salida = tempnam(sys_get_temp_dir(), 'ugc_xlsx_out_');

        if ($origen === false || $salida === false) {
            throw new RuntimeException('No fue posible preparar el archivo Excel.');
        }

        try {
            if (!$xlsx->saveAs($origen) || !self::reempaquetar($origen, $salida)) {
                throw new RuntimeException('No fue posible generar el archivo Excel.');
            }

            $nombreArchivo = str_replace(["\0", "\r", "\n", "\t", '"'], '', $nombreArchivo);
            $tamano = filesize($salida);

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . (string)$tamano);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store, max-age=0');
            header('Pragma: no-cache');

            $stream = fopen($salida, 'rb');
            if ($stream === false) {
                throw new RuntimeException('No fue posible abrir el archivo Excel.');
            }

            fpassthru($stream);
            fclose($stream);
        } finally {
            if (is_file($origen)) {
                @unlink($origen);
            }
            if (is_file($salida)) {
                @unlink($salida);
            }
        }
    }

    public static function guardarParaPrueba(SimpleXLSXGen $xlsx, string $ruta): bool
    {
        $origen = tempnam(sys_get_temp_dir(), 'ugc_xlsx_test_');
        if ($origen === false) {
            return false;
        }

        try {
            return $xlsx->saveAs($origen) && self::reempaquetar($origen, $ruta);
        } finally {
            if (is_file($origen)) {
                @unlink($origen);
            }
        }
    }

    private static function reempaquetar(string $origen, string $salida): bool
    {
        $entradaZip = new ZipArchive();
        $salidaZip = new ZipArchive();

        if ($entradaZip->open($origen) !== true) {
            return false;
        }

        if ($salidaZip->open($salida, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $entradaZip->close();
            return false;
        }

        $ok = true;

        for ($i = 0; $i < $entradaZip->numFiles; $i++) {
            $nombre = $entradaZip->getNameIndex($i);
            if ($nombre === false || self::esEntradaActiva($nombre)) {
                $ok = false;
                break;
            }

            $contenido = $entradaZip->getFromIndex($i);
            if ($contenido === false || !$salidaZip->addFromString($nombre, $contenido)) {
                $ok = false;
                break;
            }
        }

        $entradaZip->close();
        $salidaZip->close();

        return $ok;
    }

    private static function esEntradaActiva(string $nombre): bool
    {
        return preg_match(
            '#(?:^|/)(?:vbaProject\.bin|externalLinks|embeddings|activeX|oleObjects)(?:/|$)#i',
            $nombre
        ) === 1;
    }
}
