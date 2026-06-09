<?php
declare(strict_types=1);

/*
 * Configuracion central del sistema.
 * Version objetivo: PHP 8.0
 *
 * Este archivo reemplaza el uso de .env para que el proyecto sea mas facil
 * de subir por FTP/FileZilla en servidores sin Composer ni dotenv.
 */

define('APP_NAME', 'Sistema de Solicitudes - UGC');
define('APP_VERSION', '2.0.0-simple');
define('APP_ENV', 'production'); // development | production
define('BASE_URL', '/ugc_incapacidadess');
define('APP_TIMEZONE', 'America/Bogota');
define('SESSION_SAVE_PATH', ''); // Vacio usa el directorio temporal configurado por PHP
define('SESSION_IDLE_TIMEOUT', 1800); // 30 minutos de inactividad
define('APP_ROWS_PER_PAGE', 6);

/*
 * Oracle ICEBERG / RH.
 * Ajusta estos datos antes de subir a produccion.
 */
define('ORACLE_HOST', '172.28.5.101');
define('ORACLE_PORT', '1521');
define('ORACLE_USER', 'iceberg');
define('ORACLE_PASS', 'iceberg0');
define('ORACLE_SERVICE', 'UGC');
define('ORACLE_CHARSET', 'AL32UTF8');
define('ORACLE_PERSISTENT', true);
define('ORACLE_STATEMENT_CACHE_SIZE', 40);


/*
define('LDAP_HOST', '172.20.100.209');
define('LDAP_PORT', 389);
define('LDAP_USER', 'cn=Manager,dc=ugc.edu,dc=co');
define('LDAP_PASSWORD', 'Sl4pdU6Cn3W');
define('LDAP_TREE', 'ou=users,dc=ugc.edu,dc=co');
define('LDAP_DIRECT_CN_LOGIN', true); // Usa cn=cedula para evitar la busqueda LDAP lenta
 */


/*
 * Oracle UXXI / Permisos.
 * Conexion a FREEPDB1 para gestion de permisos e incapacidades.
 */
define('UXXI_HOST', '172.20.100.92');
define('UXXI_PORT', '1521');
define('UXXI_USER', 'PERMISOS');
define('UXXI_PASS', 'Permisos123');
define('UXXI_SERVICE', 'FREEPDB1');
define('UXXI_CHARSET', 'AL32UTF8');





define('LDAP_HOST', '10.238.30.115');
define('LDAP_PORT', 389);
define('LDAP_USER', 'cn=ConsultaSi,ou=users,dc=ugc.edu,dc=co');
define('LDAP_PASSWORD', '1s0lwt4Secy1723');
define('LDAP_TREE', 'ou=users,dc=ugc.edu,dc=co');
