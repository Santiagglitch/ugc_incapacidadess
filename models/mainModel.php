<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';

class mainModel
{
    protected function conectar()
    {
        return Conexion::conectar();
    }

    protected function ejecutarConsulta(string $sql, array $params = [], int $modo = OCI_DEFAULT)
    {
        try {
            $conexion = $this->conectar();
            $stmt = oci_parse($conexion, $sql);

            if (!$stmt) {
                $error = oci_error($conexion);
                error_log('Error preparando consulta: ' . ($error['message'] ?? 'desconocido'));
                return false;
            }

            foreach ($params as $nombre => &$valor) {
                if ($nombre === '' || $nombre[0] !== ':') {
                    $nombre = ':' . $nombre;
                }
                oci_bind_by_name($stmt, $nombre, $valor);
            }
            unset($valor);

            if (!@oci_execute($stmt, $modo)) {
                $error = oci_error($stmt);
                error_log('Error ejecutando consulta: ' . ($error['message'] ?? 'desconocido'));
                oci_free_statement($stmt);
                return false;
            }

            return $stmt;
        } catch (Throwable $e) {
            error_log('Excepcion DB: ' . $e->getMessage());
            return false;
        }
    }

    protected function consultarTodo(string $sql, array $params = []): array
    {
        $stmt = $this->ejecutarConsulta($sql, $params);
        if (!$stmt) {
            return [];
        }

        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $row;
        }
        oci_free_statement($stmt);
        return $rows;
    }

    protected function consultarUno(string $sql, array $params = []): ?array
    {
        $rows = $this->consultarTodo($sql, $params);
        return $rows[0] ?? null;
    }

    protected function ejecutar(string $sql, array $params = []): bool
    {
        $stmt = $this->ejecutarConsulta($sql, $params, OCI_COMMIT_ON_SUCCESS);
        if (!$stmt) {
            return false;
        }
        oci_free_statement($stmt);
        return true;
    }

    protected function buildInClause(array $values, string $prefix): array
    {
        $placeholders = [];
        $binds = [];
        foreach (array_values($values) as $index => $value) {
            $key = ':' . $prefix . $index;
            $placeholders[] = $key;
            $binds[$key] = $value;
        }
        return [implode(', ', $placeholders), $binds];
    }

    public function limpiarCadena($valor): string
    {
        return htmlspecialchars(trim((string) $valor), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function toUtf8($valor)
    {
        if ($valor === null) {
            return null;
        }
        if (mb_detect_encoding((string) $valor, 'UTF-8', true)) {
            return $valor;
        }
        $out = @iconv('Windows-1252', 'UTF-8//IGNORE', (string) $valor);
        return $out === false ? $valor : $out;
    }

    protected function toWin1252($valor)
    {
        if ($valor === null) {
            return null;
        }
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', (string) $valor);
        if ($out === false) {
            $out = @iconv('UTF-8', 'Windows-1252//IGNORE', (string) $valor);
        }
        return $out === false ? $valor : $out;
    }
}
