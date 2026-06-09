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

            if (function_exists('oci_set_prefetch')) {
                @oci_set_prefetch($stmt, 100);
            }

            foreach ($params as $nombre => &$valor) {
                if ($nombre === '' || $nombre[0] !== ':') {
                    $nombre = ':' . $nombre;
                }
                if ($valor === null) {
                    oci_bind_by_name($stmt, $nombre, $valor, -1, SQLT_CHR);
                } else {
                    oci_bind_by_name($stmt, $nombre, $valor);
                }
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
        $stmt = $this->ejecutarConsulta($sql, $params);
        if (!$stmt) {
            return null;
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return is_array($row) ? $row : null;
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
}
