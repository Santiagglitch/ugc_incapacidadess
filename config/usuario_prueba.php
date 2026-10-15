<?php

declare(strict_types=1);

// Cambiar a false al terminar las pruebas para deshabilitar el acceso.
define('USUARIO_PRUEBA_HABILITADO', true);

// La contrasena se verifica con password_verify; no se guarda en texto plano.
define('USUARIO_PRUEBA_PASSWORD_HASH', '$2y$10$EMCICIItUEtujqpMjt/oeuPn01CWJ9.7msNu5ITcwOMtSkxiDZAOK');

define('USUARIO_PRUEBA', [
    'cedula'       => '9000000001',
    'nombre'       => 'Super Admin de Pruebas',
    'email'        => '',
    'rol'          => ROL_ADMIN,
    'nivel'        => 7,
    'centro_costo' => '',
    'nit_jefe'     => null,
    'nombre_jefe'  => null,
]);

function es_usuario_prueba(string $cedula): bool
{
    return $cedula === USUARIO_PRUEBA['cedula'];
}
