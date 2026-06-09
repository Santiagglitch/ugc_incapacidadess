<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModelUXXI.php';
require_once __DIR__ . '/empleadoModel.php';

class adminAuditoriaModel extends mainModelUXXI
{
    public function registrar(string $nitActor, string $accion, string $descripcion, array $meta = [], string $nombreActor = ''): bool
    {
        $nitObjetivo = isset($meta['nit_objetivo']) ? (string)$meta['nit_objetivo'] : null;
        $idSolicitud = isset($meta['id_solicitud']) ? (int)$meta['id_solicitud'] : null;
        $metaJson    = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
        $nombre      = $nombreActor !== '' ? $nombreActor : $this->resolverNombre($nitActor);

        return $this->ejecutar(
            "INSERT INTO ADMIN_AUDITORIA
                (ID, NIT_ACTOR, NOMBRE_ACTOR, ACCION, DESCRIPCION, IP, NIT_OBJETIVO, ID_SOLICITUD, META_JSON, FECHA)
             VALUES
                (SEQ_ADMIN_AUDITORIA.NEXTVAL, :nit_actor, :nombre_actor, :accion, :descripcion, :ip, :nit_objetivo, :id_solicitud, :meta_json, SYSDATE)",
            [
                ':nit_actor'    => $nitActor,
                ':nombre_actor' => $nombre,
                ':accion'       => $accion,
                ':descripcion'  => $descripcion,
                ':ip'           => $this->ipCliente(),
                ':nit_objetivo' => $nitObjetivo,
                ':id_solicitud' => $idSolicitud,
                ':meta_json'    => $metaJson,
            ]
        );
    }

    public function filtrar(string $accion = '', int $pagina = 1, int $porPagina = 20): array
    {
        $pagina    = max(1, $pagina);
        $porPagina = max(1, $porPagina);
        $offset    = ($pagina - 1) * $porPagina;

        $whereAcicion = $accion !== '' ? "WHERE ACCION = :accion" : "";
        $params       = $accion !== '' ? [':accion' => $accion] : [];

        $totalRow = $this->consultarUno(
            "SELECT COUNT(*) AS TOTAL FROM ADMIN_AUDITORIA $whereAcicion",
            $params
        );
        $total = (int)($totalRow['TOTAL'] ?? 0);

        $rows = $this->consultarTodo(
            "SELECT ID, TO_CHAR(FECHA,'YYYY-MM-DD HH24:MI:SS') AS FECHA,
                    NIT_ACTOR, NOMBRE_ACTOR, ACCION, DESCRIPCION, IP,
                    NIT_OBJETIVO, ID_SOLICITUD, META_JSON
             FROM ADMIN_AUDITORIA
             $whereAcicion
             ORDER BY FECHA DESC
             OFFSET :offset ROWS FETCH NEXT :por_pagina ROWS ONLY",
            $params + [':offset' => $offset, ':por_pagina' => $porPagina]
        );

        $totalPags = max(1, (int)ceil($total / $porPagina));

        return [
            'filas'        => $this->normalizarFilas($rows),
            'total'        => $total,
            'pagina'       => min($pagina, $totalPags),
            'totalPaginas' => $totalPags,
            'porPagina'    => $porPagina,
        ];
    }

    public function getByNitObjetivo(string $nit, int $limite = 10): array
    {
        $limite = max(1, $limite);
        $rows   = $this->consultarTodo(
            "SELECT ID, TO_CHAR(FECHA,'YYYY-MM-DD HH24:MI:SS') AS FECHA,
                    NIT_ACTOR, NOMBRE_ACTOR, ACCION, DESCRIPCION, IP,
                    NIT_OBJETIVO, ID_SOLICITUD, META_JSON
             FROM ADMIN_AUDITORIA
             WHERE NIT_OBJETIVO = :nit
             ORDER BY FECHA DESC
             FETCH FIRST :limite ROWS ONLY",
            [':nit' => $nit, ':limite' => $limite]
        );

        return $this->normalizarFilas($rows);
    }

    public function getAccionesUnicas(): array
    {
        $rows     = $this->consultarTodo("SELECT DISTINCT ACCION FROM ADMIN_AUDITORIA ORDER BY ACCION ASC");
        $acciones = array_column($rows, 'ACCION');
        return array_values(array_filter(array_map('strval', $acciones)));
    }

    public function exportCsv(): string
    {
        $rows      = $this->consultarTodo(
            "SELECT TO_CHAR(FECHA,'YYYY-MM-DD HH24:MI:SS') AS FECHA,
                    NIT_ACTOR, NOMBRE_ACTOR, ACCION, DESCRIPCION, IP
             FROM ADMIN_AUDITORIA
             ORDER BY FECHA DESC"
        );
        $cabeceras = ['Fecha', 'NIT Actor', 'Nombre Actor', 'Accion', 'Descripcion', 'IP'];
        $lineas    = [implode(',', array_map([$this, 'csvEscape'], $cabeceras))];

        foreach ($rows as $e) {
            $lineas[] = implode(',', array_map([$this, 'csvEscape'], [
                (string)($e['FECHA']        ?? ''),
                (string)($e['NIT_ACTOR']    ?? ''),
                (string)($e['NOMBRE_ACTOR'] ?? ''),
                (string)($e['ACCION']       ?? ''),
                (string)($e['DESCRIPCION']  ?? ''),
                (string)($e['IP']           ?? ''),
            ]));
        }

        return implode("\r\n", $lineas);
    }

    private function normalizarFilas(array $rows): array
    {
        return array_map(static function (array $row): array {
            $meta = [];
            if (!empty($row['META_JSON'])) {
                $decoded = json_decode((string)$row['META_JSON'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            if (!empty($row['NIT_OBJETIVO'])) {
                $meta['nit_objetivo'] = $row['NIT_OBJETIVO'];
            }
            if (!empty($row['ID_SOLICITUD'])) {
                $meta['id_solicitud'] = $row['ID_SOLICITUD'];
            }

            return [
                'fecha'        => (string)($row['FECHA']        ?? ''),
                'nit_actor'    => (string)($row['NIT_ACTOR']    ?? ''),
                'nombre_actor' => (string)($row['NOMBRE_ACTOR'] ?? ''),
                'accion'       => (string)($row['ACCION']       ?? ''),
                'descripcion'  => (string)($row['DESCRIPCION']  ?? ''),
                'ip'           => (string)($row['IP']           ?? ''),
                'meta'         => $meta,
            ];
        }, $rows);
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
            $emp    = (new empleadoModel())->getByNit($nit);
            $nombre = trim((string)($emp['NOMBRE_COMPLETO'] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        } catch (Throwable $e) {
        }

        return $nit;
    }

    private function ipCliente(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
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
