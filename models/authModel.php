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
        $password = trim((string) $password);

        if ($cedula === '' || $password === '') {
            return ['ok' => false, 'error' => 'Ingresa documento y contrasena.'];
        }

        if (es_usuario_prueba($cedula)) {
            if (!USUARIO_PRUEBA_HABILITADO || !password_verify($password, USUARIO_PRUEBA_PASSWORD_HASH)) {
                return ['ok' => false, 'error' => 'Credenciales incorrectas.'];
            }

            return ['ok' => true, 'user' => USUARIO_PRUEBA];
        }

        $ldap = ldap_autenticar_usuario($cedula, $password);
        if (!$ldap) {
            return ['ok' => false, 'error' => 'Credenciales incorrectas.'];
        }

        $empleadoModel = new empleadoModel();
        $empleado = $empleadoModel->getByNit($cedula);
        if (!$empleado) {
            return ['ok' => false, 'error' => 'Usuario no autorizado.'];
        }

        $rol = $empleadoModel->getRol($cedula, $empleado);

        return [
            'ok' => true,
            'user' => [
                'cedula' => $cedula,
                'nombre' => trim((string) ($empleado['NOMBRE_COMPLETO'] ?? '')) ?: ($ldap['nombre'] ?? $cedula),
                'email' => $ldap['email'] ?? '',
                'rol' => $rol,
                'nivel' => (int) ($empleado['NIVEL'] ?? 0),
                'centro_costo' => $empleado['CENTRO_COSTO'] ?? '',
                // El jefe se consulta al crear/editar solicitudes, no al autenticar.
                'nit_jefe' => null,
                'nombre_jefe' => null,
            ],
        ];
    }
}
