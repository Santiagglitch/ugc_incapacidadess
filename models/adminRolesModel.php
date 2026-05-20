<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModelUXXI.php';

class adminRolesModel extends mainModelUXXI
{
    private static ?array $adminsCache = null;
    private const MAX_ADMINS = 10;

    public function getAdminsAdicionales(): array
    {
        if (self::$adminsCache !== null) {
            return self::$adminsCache;
        }

        $rows = $this->consultarTodo(
            "SELECT NIT_ADMIN FROM ADMIN_ROLES WHERE ACTIVO = 1 ORDER BY FECHA_ASIGNADO ASC"
        );

        return self::$adminsCache = array_values(array_map(
            static fn($r) => (string)$r['NIT_ADMIN'],
            $rows
        ));
    }

    public function esAdminAdicional(string $nit): bool
    {
        return in_array($nit, $this->getAdminsAdicionales(), true);
    }

    public function agregarAdmin(string $nit, string $nitAsignadoPor = ''): bool
    {
        $nit = preg_replace('/\D/', '', $nit);
        if ($nit === '') {
            return false;
        }

        if ($this->esAdminAdicional($nit)) {
            return true;
        }

        if ($this->alcanzaLimite()) {
            return false;
        }

        $asignadoPor = $nitAsignadoPor !== '' ? $nitAsignadoPor : null;
        $ok = $this->ejecutar(
            "INSERT INTO ADMIN_ROLES (ID, NIT_ADMIN, ACTIVO, FECHA_ASIGNADO, NIT_ASIGNADO_POR)
             VALUES (SEQ_ADMIN_ROLES.NEXTVAL, :nit, 1, SYSDATE, :asignado_por)",
            [':nit' => $nit, ':asignado_por' => $asignadoPor]
        );

        if ($ok) {
            self::$adminsCache = null;
        }

        return $ok;
    }

    public function quitarAdmin(string $nit): bool
    {
        if (!$this->esAdminAdicional($nit)) {
            return true;
        }

        $ok = $this->ejecutar(
            "UPDATE ADMIN_ROLES SET ACTIVO = 0 WHERE NIT_ADMIN = :nit AND ACTIVO = 1",
            [':nit' => $nit]
        );

        if ($ok) {
            self::$adminsCache = null;
        }

        return $ok;
    }

    public function getLimiteMaximo(): int
    {
        return self::MAX_ADMINS;
    }

    public function alcanzaLimite(): bool
    {
        return count($this->getAdminsAdicionales()) >= self::MAX_ADMINS;
    }
}
