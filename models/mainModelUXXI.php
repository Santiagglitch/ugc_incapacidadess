<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModel.php';
require_once __DIR__ . '/../config/conexion_uxxi.php';

class mainModelUXXI extends mainModel
{
    // Colombia does not observe DST; store Oracle DATE timestamps in UTC-05.
    protected const SQL_NOW_CO = "CAST(SYSTIMESTAMP AT TIME ZONE '-05:00' AS DATE)";

    protected function conectar()
    {
        return ConexionUXXI::conectar();
    }
}
