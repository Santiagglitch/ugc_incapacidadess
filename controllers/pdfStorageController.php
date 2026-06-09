<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/pdfStorageService.php';

class pdfStorageController
{
    private const MAX_FILE_SIZE = 5242880;

    private pdfStorageService $storage;

    public function __construct()
    {
        $this->storage = new pdfStorageService();
    }

    public function upload(): void
    {
        $this->autorizar();

        $archivo = $_FILES['documento_pdf'] ?? null;
        $nombre = trim((string)($_POST['name'] ?? ''));

        if (!is_array($archivo)
            || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !$this->pdfValido($archivo)
            || !$this->storage->guardarEnCentral((string)$archivo['tmp_name'], $nombre)) {
            $this->json(['ok' => false, 'error' => 'No se pudo guardar el PDF.'], 422);
        }

        $this->json(['ok' => true], 201);
    }

    public function file(): void
    {
        $this->autorizar();

        $nombre = trim((string)($_GET['name'] ?? ''));
        $ruta = $this->storage->rutaCentral($nombre);

        if ($ruta === null || !is_file($ruta)) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header_remove('Content-Security-Policy');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');

        readfile($ruta);
        exit;
    }

    public function delete(): void
    {
        $this->autorizar();

        $ok = $this->storage->eliminarEnCentral((string)($_POST['name'] ?? ''));
        $this->json(['ok' => $ok], $ok ? 200 : 422);
    }

    private function autorizar(): void
    {
        $token = (string)($_SERVER['HTTP_X_PDF_STORAGE_TOKEN'] ?? '');

        if ($token === '' || !hash_equals(PDF_STORAGE_TOKEN, $token)) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
    }

    private function pdfValido(array $archivo): bool
    {
        $tmpName = (string)($archivo['tmp_name'] ?? '');
        $size = (int)($archivo['size'] ?? 0);

        if ($tmpName === ''
            || !is_uploaded_file($tmpName)
            || $size < 1
            || $size > self::MAX_FILE_SIZE) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmpName) : '';

        if ($finfo) {
            finfo_close($finfo);
        }

        return in_array($mime, ['application/pdf', 'application/x-pdf'], true);
    }

    private function json(array $data, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }
}
