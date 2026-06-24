<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

class pdfStorageService
{
    public function guardarArchivoSubido(array $archivo, string $nitEmpleado): ?string
    {
        $nombre = $this->generarNombre(
            (string)($archivo['name'] ?? 'documento.pdf'),
            $nitEmpleado
        );

        $guardado = $this->esServidorCentral()
            ? $this->guardarLocal((string)($archivo['tmp_name'] ?? ''), $nombre, true)
            : $this->subirRemoto($archivo, $nombre);

        return $guardado ? 'storage/solicitudes/' . $nombre : null;
    }

    public function servirRuta(string $ruta): bool
    {
        $nombre = $this->nombreSeguro($ruta);

        if ($nombre === null) {
            return false;
        }

        $rutaLocal = $this->rutaLocal($nombre);

        if (is_file($rutaLocal)) {
            $contenido = file_get_contents($rutaLocal);

            if (!is_string($contenido)) {
                return false;
            }

            $this->enviarPdf($contenido, $nombre);
            return true;
        }

        if ($this->esServidorCentral()) {
            return false;
        }

        if (!function_exists('curl_init')) {
            error_log('PDF storage: la extension cURL no esta disponible.');
            return false;
        }

        $ch = curl_init(PDF_STORAGE_CENTRAL_URL . '?view=pdf_storage_file&name=' . rawurlencode($nombre));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => PDF_STORAGE_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => PDF_STORAGE_TIMEOUT,
            CURLOPT_HTTPHEADER => ['X-PDF-Storage-Token: ' . PDF_STORAGE_TOKEN],
        ]);

        $contenido = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($contenido) || $status !== 200) {
            error_log('PDF storage: no se pudo leer ' . $nombre . '. HTTP ' . $status . ' ' . $error);
            return false;
        }

        $this->enviarPdf($contenido, $nombre);
        return true;
    }

    public function eliminarRuta(string $ruta): bool
    {
        $nombre = $this->nombreSeguro($ruta);

        if ($nombre === null) {
            return false;
        }

        if ($this->esServidorCentral()) {
            $rutaLocal = $this->rutaLocal($nombre);
            return !is_file($rutaLocal) || @unlink($rutaLocal);
        }

        return $this->solicitudRemota('pdf_storage_delete', ['name' => $nombre]);
    }

    public function guardarEnCentral(string $tmpName, string $nombre): bool
    {
        $nombreSeguro = $this->nombreSeguro($nombre);

        return $nombreSeguro !== null
            && $this->guardarLocal($tmpName, $nombreSeguro, true);
    }

    public function rutaCentral(string $nombre): ?string
    {
        $nombreSeguro = $this->nombreSeguro($nombre);

        return $nombreSeguro === null ? null : $this->rutaLocal($nombreSeguro);
    }

    public function eliminarEnCentral(string $nombre): bool
    {
        $nombreSeguro = $this->nombreSeguro($nombre);

        if ($nombreSeguro === null) {
            return false;
        }

        $ruta = $this->rutaLocal($nombreSeguro);
        return !is_file($ruta) || @unlink($ruta);
    }

    private function esServidorCentral(): bool
    {
        $serverAddr = trim((string)($_SERVER['SERVER_ADDR'] ?? ''));

        return $serverAddr === PDF_STORAGE_CENTRAL_IP;
    }

    private function subirRemoto(array $archivo, string $nombre): bool
    {
        if (!function_exists('curl_init') || !class_exists('CURLFile')) {
            error_log('PDF storage: la extension cURL no esta disponible.');
            return false;
        }

        $tmpName = (string)($archivo['tmp_name'] ?? '');

        if ($tmpName === '' || !is_file($tmpName)) {
            return false;
        }

        $ch = curl_init(PDF_STORAGE_CENTRAL_URL . '?action=pdf_storage_upload');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => PDF_STORAGE_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => PDF_STORAGE_TIMEOUT,
            CURLOPT_HTTPHEADER => ['X-PDF-Storage-Token: ' . PDF_STORAGE_TOKEN],
            CURLOPT_POSTFIELDS => [
                'name' => $nombre,
                'documento_pdf' => new CURLFile($tmpName, 'application/pdf', $nombre),
            ],
        ]);

        $respuesta = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $json = is_string($respuesta) ? json_decode($respuesta, true) : null;
        $ok = $status === 201 && is_array($json) && ($json['ok'] ?? false) === true;

        if (!$ok) {
            error_log('PDF storage: fallo al subir ' . $nombre . '. HTTP ' . $status . ' ' . $error);
        }

        return $ok;
    }

    private function solicitudRemota(string $action, array $campos): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init(PDF_STORAGE_CENTRAL_URL . '?action=' . rawurlencode($action));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => PDF_STORAGE_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => PDF_STORAGE_TIMEOUT,
            CURLOPT_HTTPHEADER => ['X-PDF-Storage-Token: ' . PDF_STORAGE_TOKEN],
            CURLOPT_POSTFIELDS => $campos,
        ]);

        $respuesta = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = is_string($respuesta) ? json_decode($respuesta, true) : null;
        return $status === 200 && is_array($json) && ($json['ok'] ?? false) === true;
    }

    private function guardarLocal(string $tmpName, string $nombre, bool $esUpload): bool
    {
        if ($tmpName === '' || !is_file($tmpName)) {
            return false;
        }

        if (!is_dir(SOLICITUDES_STORAGE_PATH)
            && !mkdir(SOLICITUDES_STORAGE_PATH, 0755, true)
            && !is_dir(SOLICITUDES_STORAGE_PATH)) {
            error_log('PDF storage: no se pudo crear ' . SOLICITUDES_STORAGE_PATH);
            return false;
        }

        $destino = $this->rutaLocal($nombre);

        if ($esUpload && is_uploaded_file($tmpName)) {
            return move_uploaded_file($tmpName, $destino);
        }

        return @rename($tmpName, $destino);
    }

    private function generarNombre(string $nombreOriginal, string $nitEmpleado): string
    {
        $baseName = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '',
            pathinfo($nombreOriginal, PATHINFO_FILENAME)
        );
        $baseName = $baseName !== '' ? substr($baseName, 0, 30) : 'documento';
        $nit = preg_replace('/\D/', '', $nitEmpleado) ?: 'sin_nit';

        return sprintf(
            '%s_%s_%s_%s.pdf',
            $nit,
            date('Ymd'),
            str_replace('.', '', uniqid('', true)),
            $baseName
        );
    }

    private function nombreSeguro(string $ruta): ?string
    {
        $nombre = basename(str_replace('\\', '/', trim($ruta)));

        if (!preg_match('/^[A-Za-z0-9_-]+\.pdf$/i', $nombre)) {
            return null;
        }

        return $nombre;
    }

    private function rutaLocal(string $nombre): string
    {
        return rtrim(SOLICITUDES_STORAGE_PATH, '/\\') . DIRECTORY_SEPARATOR . $nombre;
    }

    private function enviarPdf($contenido, string $nombre): void
    {
        if (!is_string($contenido)) {
            return;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header_remove('Content-Security-Policy');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($contenido));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');

        echo $contenido;
    }
}
