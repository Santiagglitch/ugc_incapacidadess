<?php

declare(strict_types=1);

require_once __DIR__ . '/mainModel.php';
require_once __DIR__ . '/../config/conexion_uxxi.php';

class mainModelUXXI extends mainModel
{
    protected function conectar()
    {
        return ConexionUXXI::conectar();
    }
}
