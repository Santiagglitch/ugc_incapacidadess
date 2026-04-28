<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModel.php';
require_once __DIR__ . '/adminRolesModel.php';
require_once __DIR__ . '/../config/app.php';

class empleadoModel extends mainModel
{
    public function getByNit(string $nit): ?array
    {
        return $this->consultarUno(
            "SELECT NIT,
                    TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL, ESTADO, FECHA_INGRESO
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND NIT=:nit
             ORDER BY FECHA_INGRESO DESC, EMPLEADO DESC",
            [':nit' => $nit]
        );
    }

    public function getTodos(): array
    {
        return $this->consultarTodo(
            "SELECT NIT,
                    TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL, ESTADO
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A'
             ORDER BY NOMBRE_COMPLETO"
        );
    }

    public function getRol(string $nit): string
    {
        if ($nit === SUPER_ADMIN_NIT) {
            return ROL_ADMIN;
        }

        if ((new adminRolesModel())->esAdminAdicional($nit)) {
            return ROL_ADMIN;
        }

        $empleado = $this->getByNit($nit);
        if (!$empleado) {
            return ROL_EMPLEADO;
        }

        $nivel = (int) ($empleado['NIVEL'] ?? 0);
        $cc = (string) ($empleado['CENTRO_COSTO'] ?? '');

        if (in_array($cc, CC_RRHH, true)) {
            return ROL_RRHH;
        }

        if ($nivel >= NIVEL_MIN_JEFE) {
            return ROL_JEFE;
        }

        return ROL_EMPLEADO;
    }

    public function getJefeInmediato(string $nit): ?array
    {
        $row = $this->consultarUno(
            "WITH emp_buscado AS (
                SELECT e.ROWID AS rid_emp, e.EMPRESA, e.EMPLEADO, e.NIT,
                       TRIM(e.NOMBRE || ' ' || e.PRIMER_APELLIDO || ' ' || NVL(e.SEGUNDO_APELLIDO, '')) AS NOMBRE_COMPLETO,
                       e.CENTRO_COSTO, e.NIVEL, e.ESTADO
                  FROM EMPLEADO e
                 WHERE e.EMPRESA = 'BA2' AND e.ESTADO = 'A' AND e.NIT = :nit
            ), base AS (
                SELECT e.ROWID AS rid_emp, e.EMPRESA, e.EMPLEADO, e.NIT,
                       TRIM(e.NOMBRE || ' ' || e.PRIMER_APELLIDO || ' ' || NVL(e.SEGUNDO_APELLIDO, '')) AS NOMBRE_COMPLETO,
                       e.CENTRO_COSTO, e.NIVEL, e.ESTADO
                  FROM EMPLEADO e
                 WHERE e.EMPRESA = 'BA2' AND e.ESTADO = 'A'
            ), jefe_mismo_cc AS (
                SELECT b1.rid_emp, b2.NIT, b2.NOMBRE_COMPLETO, b2.NIVEL,
                       ROW_NUMBER() OVER (PARTITION BY b1.rid_emp ORDER BY b2.NIVEL DESC) AS rn
                  FROM emp_buscado b1
                  JOIN base b2 ON b2.CENTRO_COSTO = b1.CENTRO_COSTO AND b2.NIVEL > b1.NIVEL
            )
            SELECT j1.NIT AS NIT_JEFE, j1.NOMBRE_COMPLETO AS NOMBRE_JEFE, j1.NIVEL AS NIVEL_JEFE
              FROM emp_buscado b
              LEFT JOIN jefe_mismo_cc j1 ON j1.rid_emp = b.rid_emp AND j1.rn = 1",
            [':nit' => $nit]
        );

        if (!$row || empty($row['NIT_JEFE'])) {
            return null;
        }

        return $row;
    }

    public function esAprendiz(string $centroCosto): bool
    {
        return in_array($centroCosto, CC_APRENDICES, true);
    }

    public function getTodosLosJefes(): array
    {
        return $this->consultarTodo(
            "SELECT DISTINCT NIT,
                    TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND NIVEL >= :nivel
             ORDER BY NOMBRE_COMPLETO",
            [':nivel' => NIVEL_MIN_JEFE]
        );
    }

    public function getPorCentrosCosto(array $centrosCosto): array
    {
        if (empty($centrosCosto)) {
            return [];
        }

        $binds = [];
        $placeholders = [];
        foreach (array_values($centrosCosto) as $i => $centroCosto) {
            $key = ':cc' . $i;
            $placeholders[] = $key;
            $binds[$key] = $centroCosto;
        }

        return $this->consultarTodo(
            "SELECT NIT,
                    TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL, ESTADO
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND CENTRO_COSTO IN (" . implode(',', $placeholders) . ")
             ORDER BY NOMBRE_COMPLETO",
            $binds
        );
    }
}
