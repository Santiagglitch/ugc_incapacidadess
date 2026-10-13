<?php

declare(strict_types=1);

require_once __DIR__ . '/server.php';

function ldap_diagnostico_archivo(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'ldap_auth.log';
}

function ldap_diagnostico_sanitizar($valor, string $clave = '')
{
    if ($clave !== '' && preg_match('/password|contrasena|clave|secret|token/i', $clave)) {
        return '[REDACTADO]';
    }

    if (is_array($valor)) {
        $limpio = [];
        foreach ($valor as $indice => $elemento) {
            $limpio[$indice] = ldap_diagnostico_sanitizar($elemento, (string) $indice);
        }
        return $limpio;
    }

    if (is_object($valor)) {
        return '[OBJETO ' . get_class($valor) . ']';
    }

    if (is_resource($valor)) {
        return '[RECURSO ' . get_resource_type($valor) . ']';
    }

    return $valor;
}

function ldap_diagnostico_id(): string
{
    try {
        return bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        return str_replace('.', '', uniqid('ldap', true));
    }
}

function ldap_diagnostico_registrar(
    string $intentoId,
    string $cedula,
    string $etapa,
    string $resultado,
    array $detalle = []
): void {
    $registro = [
        'fecha' => date('Y-m-d\TH:i:sP'),
        'intento_id' => $intentoId,
        'usuario_login' => $cedula,
        'ip_cliente' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        'etapa' => $etapa,
        'resultado' => $resultado,
        'ldap' => [
            'host' => defined('LDAP_HOST') ? LDAP_HOST : null,
            'puerto' => defined('LDAP_PORT') ? LDAP_PORT : null,
            'usuario_servicio' => defined('LDAP_USER') ? LDAP_USER : null,
            'base_dn' => defined('LDAP_TREE') ? LDAP_TREE : null,
            'modo_login' => ldap_login_directo_cn_activo() ? 'bind_directo_cn' : 'buscar_uid_y_bind',
        ],
        'detalle' => ldap_diagnostico_sanitizar($detalle),
    ];

    $linea = json_encode(
        $registro,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($linea === false) {
        return;
    }

    $ruta = ldap_diagnostico_archivo();
    $directorio = dirname($ruta);

    try {
        if (!is_dir($directorio) && !@mkdir($directorio, 0770, true) && !is_dir($directorio)) {
            error_log('LDAP diagnostico: no se pudo crear el directorio de logs.');
            return;
        }

        // PHP/Apache crea la carpeta como su propio usuario. Estos permisos permiten
        // consultarla por FileZilla; storage/.htaccess sigue bloqueando el acceso web.
        @chmod($directorio, 0755);

        if (@file_put_contents($ruta, $linea . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('LDAP diagnostico: no se pudo escribir el archivo de log.');
        } else {
            @chmod($ruta, 0644);
        }
    } catch (Throwable $e) {
        error_log('LDAP diagnostico: fallo al escribir el archivo de log.');
    }
}

function ldap_diagnostico_error($conexion, string $etapa): array
{
    $codigo = null;
    $mensaje = 'Sin detalle entregado por la extension LDAP.';
    $diagnosticoServidor = '';

    if ($conexion && function_exists('ldap_errno')) {
        $codigo = @ldap_errno($conexion);
        $mensajeLdap = @ldap_error($conexion);
        if (is_string($mensajeLdap) && $mensajeLdap !== '') {
            $mensaje = $mensajeLdap;
        }

        if (defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
            @ldap_get_option($conexion, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diagnosticoServidor);
        }
    }

    $mensajeNormalizado = strtolower($mensaje . ' ' . $diagnosticoServidor);
    if ($codigo === 49) {
        $causa = $etapa === 'bind_usuario'
            ? 'credenciales_del_usuario_invalidas'
            : 'credenciales_de_la_cuenta_de_servicio_invalidas';
    } elseif ($codigo === 32) {
        $causa = 'usuario_o_base_dn_no_encontrado';
    } elseif ($codigo === 34) {
        $causa = 'dn_invalido';
    } elseif ($codigo === 50) {
        $causa = 'permisos_ldap_insuficientes';
    } elseif ($codigo === 3 || str_contains($mensajeNormalizado, 'timed out') || str_contains($mensajeNormalizado, 'timeout')) {
        $causa = 'tiempo_de_espera_agotado';
    } elseif (
        in_array($codigo, [-1, 51, 52, 81, 91], true)
        || str_contains($mensajeNormalizado, "can't contact")
        || str_contains($mensajeNormalizado, 'cannot contact')
        || str_contains($mensajeNormalizado, 'server down')
        || str_contains($mensajeNormalizado, 'unavailable')
    ) {
        $causa = 'host_o_puerto_ldap_no_disponible';
    } else {
        $causa = 'error_ldap_no_clasificado';
    }

    return [
        'codigo_ldap' => $codigo,
        'mensaje_ldap' => $mensaje,
        'diagnostico_servidor' => $diagnosticoServidor,
        'causa_probable' => $causa,
    ];
}

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

function ldap_conectar(?string $intentoId = null, string $cedula = '')
{
    $intentoId = $intentoId ?: ldap_diagnostico_id();
    $inicio = microtime(true);

    ldap_diagnostico_registrar($intentoId, $cedula, 'conexion', 'inicio', [
        'extension_ldap_cargada' => function_exists('ldap_connect'),
        'timeout_red_segundos' => defined('LDAP_NETWORK_TIMEOUT') ? (int) LDAP_NETWORK_TIMEOUT : null,
    ]);

    if (!function_exists('ldap_connect')) {
        ldap_diagnostico_registrar($intentoId, $cedula, 'conexion', 'error', [
            'causa_probable' => 'extension_ldap_de_php_no_instalada_o_no_habilitada',
        ]);
        return false;
    }

    try {
        error_clear_last();
        $conexion = @ldap_connect('ldap://' . LDAP_HOST . ':' . LDAP_PORT);
    } catch (Throwable $e) {
        ldap_diagnostico_registrar($intentoId, $cedula, 'conexion', 'excepcion', [
            'tipo_excepcion' => get_class($e),
            'mensaje_excepcion' => $e->getMessage(),
            'causa_probable' => 'host_puerto_o_uri_ldap_invalidos',
        ]);
        return false;
    }

    if (!$conexion) {
        $errorPhp = error_get_last();
        ldap_diagnostico_registrar($intentoId, $cedula, 'conexion', 'error', [
            'causa_probable' => 'no_se_pudo_crear_la_conexion_ldap',
            'mensaje_php' => is_array($errorPhp) ? ($errorPhp['message'] ?? null) : null,
        ]);
        return false;
    }

    try {
        $protocoloOk = @ldap_set_option($conexion, LDAP_OPT_PROTOCOL_VERSION, 3);
        $referenciasOk = @ldap_set_option($conexion, LDAP_OPT_REFERRALS, 0);
        $timeoutOk = null;
        if (defined('LDAP_NETWORK_TIMEOUT')) {
            $timeoutOk = @ldap_set_option($conexion, LDAP_OPT_NETWORK_TIMEOUT, (int) LDAP_NETWORK_TIMEOUT);
        }

        ldap_diagnostico_registrar($intentoId, $cedula, 'configuracion_conexion', 'aplicada', [
            'protocolo_ldap_v3' => $protocoloOk,
            'referencias_deshabilitadas' => $referenciasOk,
            'timeout_red_aplicado' => $timeoutOk,
        ]);

        $inicioBind = microtime(true);
        error_clear_last();
        $bindServicio = @ldap_bind($conexion, LDAP_USER, LDAP_PASSWORD);
        if (!$bindServicio) {
            $errorPhp = error_get_last();
            ldap_diagnostico_registrar(
                $intentoId,
                $cedula,
                'bind_servicio',
                'error',
                array_merge(ldap_diagnostico_error($conexion, 'bind_servicio'), [
                    'mensaje_php' => is_array($errorPhp) ? ($errorPhp['message'] ?? null) : null,
                    'duracion_ms' => round((microtime(true) - $inicioBind) * 1000, 2),
                ])
            );
            @ldap_unbind($conexion);
            return false;
        }

        ldap_diagnostico_registrar($intentoId, $cedula, 'bind_servicio', 'ok', [
            'duracion_ms' => round((microtime(true) - $inicioBind) * 1000, 2),
            'duracion_total_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);

        return $conexion;
    } catch (Throwable $e) {
        ldap_diagnostico_registrar($intentoId, $cedula, 'conexion', 'excepcion', [
            'tipo_excepcion' => get_class($e),
            'mensaje_excepcion' => $e->getMessage(),
            'duracion_total_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);
        @ldap_unbind($conexion);
        return false;
    }
}

function ldap_autenticar_usuario(string $cedula, string $password): ?array
{
    $cedula = trim($cedula);
    $password = (string) $password;
    $intentoId = ldap_diagnostico_id();
    $inicio = microtime(true);

    ldap_diagnostico_registrar($intentoId, $cedula, 'autenticacion', 'inicio', [
        'usuario_informado' => $cedula !== '',
        'se_recibio_credencial' => $password !== '',
    ]);

    if ($cedula === '' || $password === '') {
        ldap_diagnostico_registrar($intentoId, $cedula, 'validacion_entrada', 'error', [
            'causa_probable' => 'usuario_o_contrasena_vacios',
        ]);
        return null;
    }

    $conexion = ldap_conectar($intentoId, $cedula);
    if (!$conexion) {
        ldap_diagnostico_registrar($intentoId, $cedula, 'autenticacion', 'error', [
            'causa_probable' => 'fallo_la_conexion_o_el_bind_de_servicio',
            'duracion_total_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);
        return null;
    }

    try {
        if (ldap_login_directo_cn_activo()) {
            $dnDirecto = ldap_cn_directo_dn($cedula);
            $inicioBind = microtime(true);
            error_clear_last();
            $bindDirecto = @ldap_bind($conexion, $dnDirecto, $password);
            if (!$bindDirecto) {
                $errorPhp = error_get_last();
                ldap_diagnostico_registrar(
                    $intentoId,
                    $cedula,
                    'bind_usuario',
                    'error',
                    array_merge(ldap_diagnostico_error($conexion, 'bind_usuario'), [
                        'mensaje_php' => is_array($errorPhp) ? ($errorPhp['message'] ?? null) : null,
                        'dn_usuario' => $dnDirecto,
                        'duracion_ms' => round((microtime(true) - $inicioBind) * 1000, 2),
                    ])
                );
                return null;
            }

            ldap_diagnostico_registrar($intentoId, $cedula, 'autenticacion', 'ok', [
                'dn_usuario' => $dnDirecto,
                'duracion_total_ms' => round((microtime(true) - $inicio) * 1000, 2),
            ]);

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
        $inicioBusqueda = microtime(true);
        error_clear_last();
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
            $errorPhp = error_get_last();
            ldap_diagnostico_registrar(
                $intentoId,
                $cedula,
                'busqueda_usuario',
                'error',
                array_merge(ldap_diagnostico_error($conexion, 'busqueda_usuario'), [
                    'mensaje_php' => is_array($errorPhp) ? ($errorPhp['message'] ?? null) : null,
                    'filtro' => $filtro,
                    'duracion_ms' => round((microtime(true) - $inicioBusqueda) * 1000, 2),
                ])
            );
            return null;
        }

        $entradas = @ldap_get_entries($conexion, $busqueda);
        if (!$entradas || (int) $entradas['count'] < 1) {
            ldap_diagnostico_registrar($intentoId, $cedula, 'busqueda_usuario', 'sin_resultados', [
                'filtro' => $filtro,
                'cantidad' => is_array($entradas) ? (int) ($entradas['count'] ?? 0) : null,
                'causa_probable' => 'usuario_no_encontrado_en_la_base_dn',
                'duracion_ms' => round((microtime(true) - $inicioBusqueda) * 1000, 2),
            ]);
            return null;
        }

        $dn = $entradas[0]['dn'] ?? '';
        if ($dn === '') {
            ldap_diagnostico_registrar($intentoId, $cedula, 'busqueda_usuario', 'error', [
                'filtro' => $filtro,
                'cantidad' => (int) $entradas['count'],
                'causa_probable' => 'el_resultado_no_contiene_dn',
            ]);
            return null;
        }

        ldap_diagnostico_registrar($intentoId, $cedula, 'busqueda_usuario', 'ok', [
            'filtro' => $filtro,
            'cantidad' => (int) $entradas['count'],
            'dn_usuario' => $dn,
            'duracion_ms' => round((microtime(true) - $inicioBusqueda) * 1000, 2),
        ]);

        $inicioBind = microtime(true);
        error_clear_last();
        $bindUsuario = @ldap_bind($conexion, $dn, $password);
        if (!$bindUsuario) {
            $errorPhp = error_get_last();
            ldap_diagnostico_registrar(
                $intentoId,
                $cedula,
                'bind_usuario',
                'error',
                array_merge(ldap_diagnostico_error($conexion, 'bind_usuario'), [
                    'mensaje_php' => is_array($errorPhp) ? ($errorPhp['message'] ?? null) : null,
                    'dn_usuario' => $dn,
                    'duracion_ms' => round((microtime(true) - $inicioBind) * 1000, 2),
                ])
            );
            return null;
        }

        ldap_diagnostico_registrar($intentoId, $cedula, 'autenticacion', 'ok', [
            'dn_usuario' => $dn,
            'duracion_total_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);

        return [
            'nombre' => $entradas[0]['cn'][0] ?? $cedula,
            'email' => $entradas[0]['mail'][0] ?? '',
            'telefono' => $entradas[0]['telephonenumber'][0] ?? '',
            'departamento' => $entradas[0]['department'][0] ?? '',
            'titulo' => $entradas[0]['title'][0] ?? '',
        ];
    } catch (Throwable $e) {
        ldap_diagnostico_registrar($intentoId, $cedula, 'autenticacion', 'excepcion', [
            'tipo_excepcion' => get_class($e),
            'mensaje_excepcion' => $e->getMessage(),
            'duracion_total_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);
        return null;
    } finally {
        if ($conexion) {
            @ldap_unbind($conexion);
        }
    }
}
