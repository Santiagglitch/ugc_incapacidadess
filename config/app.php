<?php

declare(strict_types=1);

require_once __DIR__ . '/server.php';

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/debug.log');
}

define('ROL_ADMIN', 'administrador');
define('ROL_RRHH', 'talento_humano');
define('ROL_JEFE', 'jefe_inmediato');
define('ROL_EMPLEADO', 'solicitante');

define('CC_RRHH', ['2413001', '2413002', '2413003', '2413004']);
define('CC_APRENDICES', ['2411001', '2411002', '2411004']);

define('NIVEL_MIN_JEFE', 4);
define('NIVEL_MIN_ADMIN', 7);
define('SUPER_ADMIN_NIT', '1085042421');

define('NITS_JEFES_REVISORES', [
    '19229766',
    '38217178',
    '1020727118',
    '12647451',
    '81715536',
    '52952249',
    '1015462252',
    '51937767',
    '52699570',
    '12745542',
    '43733246',
    '80759522',
    '1014208217',
    '52273463',
    '79544111',
    '1019066807',
    '19407592',
    '39544854',
    '53165229',
    '41651999',
    '19069937',
    '79792432',
    '52451211',
    '51688407',
    '11039376',
    '79389155',
    '79703877',
    '1070916958',
    '52109300',
    '1031134966',
    '11386108',
    '4404301',
    '1085042421',
    '1014179639',
    '19260753',
    '79356948',
    '52026458',
    '19318358',
    '79694291',
    '1136880553',
]);

function es_jefe_revisor_nit($nit): bool
{
    $nitNormalizado = preg_replace('/\D+/', '', (string)$nit) ?? '';
    return $nitNormalizado !== '' && in_array($nitNormalizado, NITS_JEFES_REVISORES, true);
}

define('ESTADO_PENDIENTE_JEFE', 'PENDIENTE_JEFE');
define('ESTADO_APROBADO_JEFE', 'APROBADO_JEFE');
define('ESTADO_RECHAZADO_JEFE', 'RECHAZADO_JEFE');
define('ESTADO_APROBADO_RRHH', 'APROBADO_RRHH');
define('ESTADO_RECHAZADO_RRHH', 'RECHAZADO_RRHH');
define('ESTADO_CANCELADO_AGENDA', 'CANCELADO_AGENDA');

define('TIPOS_SOLICITUD', [
    'LICENCIA_NO_REMUNERADA' => 'Licencia No Remunerada',
    'LICENCIA_REMUNERADA' => 'Licencia Remunerada',
    'REUNIONES_ESCOLARES' => 'Reuniones Escolares',
    'CITA_MEDICA' => 'Cita Medica',
    'PERMISO_SINDICAL' => 'Permiso Sindical',
    'CALAMIDAD_DOMESTICA' => 'Calamidad Domestica',
    'PERMISO_FUNEBRE' => 'Permiso Funebre',
    'CITACIONES_JUDICIALES_ADMIN_LEGALES' => 'Citaciones Judiciales / Adm. / Legales',
    'COMPENSATORIO' => 'Compensatorio',
    'OTROS' => 'Otros',
]);

define('USUARIOS_PRUEBA', [
    SUPER_ADMIN_NIT => [
        'cedula' => SUPER_ADMIN_NIT,
        'nombre' => 'Ingeniero Jefe / Super Admin',
        'email' => 'superadmin@ugc.edu.co',
        'rol' => ROL_JEFE,
        'nivel' => 5,
        'centro_costo' => '2312101',
        'nit_jefe' => null,
        'nombre_jefe' => null,
    ],
]);

function app_base_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function app_asset(string $path): string
{
    static $versionCache = [];

    $relative = ltrim($path, '/');
    $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $url = app_base_url($relative);

    if (is_file($absolute)) {
        $versionCache[$absolute] ??= filemtime($absolute);
        return $url . '?v=' . $versionCache[$absolute];
    }

    return $url;
}
