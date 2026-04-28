<?php

declare(strict_types=1);

require_once __DIR__ . '/server.php';

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

    if (!@ldap_bind($conexion, LDAP_USER, LDAP_PASSWORD)) {
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
        $cedulaEscapada = function_exists('ldap_escape')
            ? ldap_escape($cedula, '', LDAP_ESCAPE_FILTER)
            : str_replace(['\\', '*', '(', ')', "\x00"], ['\\5c', '\\2a', '\\28', '\\29', '\\00'], $cedula);

        $filtro = '(uid=' . $cedulaEscapada . ')';
        $attrs = ['cn', 'mail', 'telephonenumber', 'department', 'title'];
        $busqueda = @ldap_search($conexion, LDAP_TREE, $filtro, $attrs);

        if (!$busqueda) {
            return null;
        }

        $entradas = @ldap_get_entries($conexion, $busqueda);
        if (!$entradas || (int) $entradas['count'] < 1) {
            return null;
        }

        $dn = $entradas[0]['dn'] ?? '';
        if ($dn === '' || !@ldap_bind($conexion, $dn, $password)) {
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