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

define('CC_RRHH', []);
define('CC_APRENDICES', ['2411001', '2411002', '2411004']);

define('NIVEL_MIN_JEFE', 4);
define('NIVEL_MIN_ADMIN', 7);
define('SUPER_ADMIN_NIT', '1085042421');
define('TALENTO_HUMANO_NIT', '11039376');
define('TALENTO_HUMANO_NIT_2', '1022941616');
define('NITS_TALENTO_HUMANO', [TALENTO_HUMANO_NIT, TALENTO_HUMANO_NIT_2]);

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
    '11039376',
]);

function es_jefe_revisor_nit($nit): bool
{
    $nitNormalizado = preg_replace('/\D+/', '', (string)$nit) ?? '';

    if ($nitNormalizado === '') {
        return false;
    }

    // Los usuarios de Talento Humano fijos nunca son tratados como jefe revisor
    if (in_array($nitNormalizado, NITS_TALENTO_HUMANO, true)) {
        return false;
    }

    // Verificar que no sea un usuario de RRHH por centro de costo
    if (!empty(CC_RRHH) && in_array($nitNormalizado, CC_RRHH, true)) {
        return false;
    }

    return in_array($nitNormalizado, NITS_JEFES_REVISORES, true);
}

/**
 * Obtiene la lista de jefes revisores filtrando los de RRHH
 * @return array NITS de jefes que no son de RRHH
 */
function get_jefes_revisores_filtrados(): array
{
    return array_values(array_diff(NITS_JEFES_REVISORES, CC_RRHH));
}

function es_talento_humano_nit($nit): bool
{
    $nitNormalizado = preg_replace('/\D+/', '', (string)$nit) ?? '';
    return $nitNormalizado !== '' && in_array($nitNormalizado, NITS_TALENTO_HUMANO, true);
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
        'cedula'       => SUPER_ADMIN_NIT,
        'nombre'       => 'Ingeniero Jefe / Super Admin',
        'email'        => 'superadmin@ugc.edu.co',
        'rol'          => ROL_JEFE,
        'nivel'        => 5,
        'centro_costo' => '2312101',
        'nit_jefe'     => null,
        'nombre_jefe'  => null,
    ],
    // --- USUARIOS DE PRUEBA LOCAL: eliminar antes de subir a produccion ---
    TALENTO_HUMANO_NIT => [
        'cedula'       => TALENTO_HUMANO_NIT,
        'nombre'       => 'ARLEM ANTONIO GARCIA PEINADO',
        'email'        => 'arlem.garcia@ugc.edu.co',
        'rol'          => ROL_JEFE,
        'nivel'        => 4,
        'centro_costo' => '2411001',
        'nit_jefe'     => null,
        'nombre_jefe'  => null,
    ],
    TALENTO_HUMANO_NIT_2 => [
        'cedula'       => TALENTO_HUMANO_NIT_2,
        'nombre'       => 'JULIE ANABEL MARTINEZ COTE',
        'email'        => 'julie.martinez@ugc.edu.co',
        'rol'          => ROL_JEFE,
        'nivel'        => 4,
        'centro_costo' => '2411001',
        'nit_jefe'     => null,
        'nombre_jefe'  => null,
    ],
    // --- FIN USUARIOS DE PRUEBA LOCAL ---
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
