<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__, 2) . '/config/helpers.php';

if (($argv[1] ?? '') === 'values') {
    echo json_encode(['flags' => controlIncapacidadesService::state()['flags'], 'vacaciones' => VACACIONES_ACTIVAS,
        'pruebas' => USUARIO_PRUEBA_HABILITADO, 'sqlite' => extension_loaded('pdo_sqlite')], JSON_THROW_ON_ERROR);
    exit;
}

if (($argv[1] ?? '') === 'form') {
    iniciar_sesion_segura();
    $user = ['cedula' => 'fixture', 'nombre' => 'Prueba aislada'];
    $solicitud = ['ID' => 1, 'NIT_JEFE' => '123', 'TIPO_SOLICITUD' => 'PERMISO'];
    $jefes = [['NIT' => '123', 'NOMBRE_COMPLETO' => 'Jefe asignado']];
    $jefeFijo = ['NIT_JEFE' => '123', 'NOMBRE_JEFE' => 'Jefe asignado'];
    if (($argv[3] ?? '') === 'no-jefe') {
        $jefeFijo = null;
    }
    $submitToken = 'fixture';
    require dirname(__DIR__, 2) . '/views/empleado/' . (($argv[2] ?? '') === 'edit' ? 'form_editar' : 'form_crear') . '.php';
    exit;
}

exit(1);
