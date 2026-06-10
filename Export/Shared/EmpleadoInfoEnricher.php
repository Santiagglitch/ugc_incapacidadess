<?php

declare(strict_types=1);

namespace App\Export\Shared;

require_once __DIR__ . '/../../models/empleadoModel.php';

final class EmpleadoInfoEnricher
{
    public function enriquecerHojas(array $hojas): array
    {
        $nits = [];

        foreach ($hojas as $rows) {
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $nit = trim((string)($row['NIT_EMPLEADO'] ?? ''));
                if ($nit !== '') {
                    $nits[$nit] = true;
                }
            }
        }

        $infoEmpleados = (new \empleadoModel())->getInfoExportacionPorNits(array_keys($nits));

        foreach ($hojas as &$rows) {
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as &$row) {
                if (!is_array($row)) {
                    continue;
                }

                $nit = trim((string)($row['NIT_EMPLEADO'] ?? ''));
                $info = $infoEmpleados[$nit] ?? [];

                $row['NOMBRE_EMPLEADO'] = (string)($info['NOMBRE_EMPLEADO'] ?? '');
                $row['NOMBRE_DEPENDENCIA'] = (string)($info['NOMBRE_DEPENDENCIA'] ?? '');
            }
            unset($row);
        }
        unset($rows);

        return $hojas;
    }
}
