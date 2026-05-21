<?php

declare(strict_types=1);

require_once __DIR__ . '/server.php';

function ldap_uid_para_dn(string $cedula): string
{
    if (function_exists('ldap_escape')) {
        return ldap_escape($cedula, '', LDAP_ESCAPE_DN);
    }

    return str_replace(['\\', ',', '+', '"', '<', '>', ';', "\x00"], ['\\5c', '\\2c', '\\2b', '\\22', '\\3c', '\\3e', '\\3b', '\\00'], $cedula);
}

function ldap_cn_directo_dn(string $cedula): string
{
    return 'cn=' . ldap_uid_para_dn($cedula) . ',' . LDAP_TREE;
}

function ldap_login_directo_cn_activo(): bool
{
    return defined('LDAP_DIRECT_CN_LOGIN') && LDAP_DIRECT_CN_LOGIN;
}

function ldap_conectar()
{
    if (!function_exists('ldap_connect')) {
        return false;
    }

    $conexion = @ldap_connect('ldap://' . LDAP_HOST . ':' . LDAP_PORT);
    if (!$conexion) {
        return false;
    }

    ldap_set_option($conexion, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conexion, LDAP_OPT_REFERRALS, 0);
    if (defined('LDAP_NETWORK_TIMEOUT')) {
        @ldap_set_option($conexion, LDAP_OPT_NETWORK_TIMEOUT, (int) LDAP_NETWORK_TIMEOUT);
    }

    $bindServicio = @ldap_bind($conexion, LDAP_USER, LDAP_PASSWORD);
    if (!$bindServicio) {
        @ldap_unbind($conexion);
        return false;
    }

    return $conexion;
}

function ldap_autenticar_usuario(string $cedula, string $password): ?array
{
    $cedula = trim($cedula);
    $password = (string) $password;

    if ($cedula === '' || $password === '') {
        return null;
    }

    $conexion = ldap_conectar();
    if (!$conexion) {
        return null;
    }

    try {
        if (ldap_login_directo_cn_activo()) {
            $dnDirecto = ldap_cn_directo_dn($cedula);
            $bindDirecto = @ldap_bind($conexion, $dnDirecto, $password);
            if (!$bindDirecto) {
                return null;
            }

            return [
                'nombre' => $cedula,
                'email' => '',
                'telefono' => '',
                'departamento' => '',
                'titulo' => '',
            ];
        }

        $cedulaEscapada = function_exists('ldap_escape')
            ? ldap_escape($cedula, '', LDAP_ESCAPE_FILTER)
            : str_replace(['\\', '*', '(', ')', "\x00"], ['\\5c', '\\2a', '\\28', '\\29', '\\00'], $cedula);

        $filtro = '(uid=' . $cedulaEscapada . ')';
        $attrs = ['cn', 'mail', 'telephonenumber', 'department', 'title'];
        $busqueda = @ldap_search(
            $conexion,
            LDAP_TREE,
            $filtro,
            $attrs,
            0,
            1,
            defined('LDAP_SEARCH_TIMEOUT') ? (int) LDAP_SEARCH_TIMEOUT : 0
        );

        if (!$busqueda) {
            return null;
        }

        $entradas = @ldap_get_entries($conexion, $busqueda);
        if (!$entradas || (int) $entradas['count'] < 1) {
            return null;
        }

        $dn = $entradas[0]['dn'] ?? '';
        if ($dn === '') {
            return null;
        }

        $bindUsuario = @ldap_bind($conexion, $dn, $password);
        if (!$bindUsuario) {
            return null;
        }

        return [
            'nombre' => $entradas[0]['cn'][0] ?? $cedula,
            'email' => $entradas[0]['mail'][0] ?? '',
            'telefono' => $entradas[0]['telephonenumber'][0] ?? '',
            'departamento' => $entradas[0]['department'][0] ?? '',
            'titulo' => $entradas[0]['title'][0] ?? '',
        ];
    } finally {
        if ($conexion) {
            @ldap_unbind($conexion);
        }
    }
}
