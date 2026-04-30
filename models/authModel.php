<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/conexion_ldap.php';
require_once __DIR__ . '/empleadoModel.php';
require_once __DIR__ . '/adminRolesModel.php';

class authModel
{
    public function login(string $cedula, string $password): array
    {
        $cedula = trim($cedula);
        $password = (string) $password;

        if ($cedula === '' || $password === '') {
            return ['ok' => false, 'error' => 'Ingresa documento y contrasena.'];
        }

        if (APP_ENV === 'development' && isset(USUARIOS_PRUEBA[$cedula]) && $password === 'prueba123') {
            $user = USUARIOS_PRUEBA[$cedula];
            if ($cedula === SUPER_ADMIN_NIT) {
                $user['rol'] = ROL_ADMIN;
            }
            return ['ok' => true, 'user' => $user];
        }

        $ldap = ldap_autenticar_usuario($cedula, $password);
        if (!$ldap) {
            return ['ok' => false, 'error' => 'Credenciales incorrectas.'];
        }

        $empleadoModel = new empleadoModel();
        $empleado = $empleadoModel->getByNit($cedula);
        $rol = $empleadoModel->getRol($cedula, $empleado);
        $jefe = !in_array($rol, [ROL_ADMIN, ROL_RRHH], true) ? $empleadoModel->getJefeInmediato($cedula) : null;

        return [
            'ok' => true,
            'user' => [
                'cedula' => $cedula,
                'nombre' => trim((string) ($empleado['NOMBRE_COMPLETO'] ?? '')) ?: ($ldap['nombre'] ?? $cedula),
                'email' => $ldap['email'] ?? '',
                'rol' => $rol,
                'nivel' => (int) ($empleado['NIVEL'] ?? 0),
                'centro_costo' => $empleado['CENTRO_COSTO'] ?? '',
                'nit_jefe' => $jefe['NIT_JEFE'] ?? null,
                'nombre_jefe' => $jefe['NOMBRE_JEFE'] ?? null,
            ],
        ];
    }
}
