<?php

declare(strict_types=1);

/** Estados y acceso del panel, almacenados como datos privados; no modifica codigo PHP. */
final class controlIncapacidadesService
{
    public const CONTROLES = [
        'LOGIN_HABILITADO' => ['label' => 'Inicio de sesion', 'description' => 'Permite nuevos ingresos al portal. No cierra las sesiones existentes ni bloquea este panel.'],
        'VACACIONES_ACTIVAS' => ['label' => 'Aviso de vacaciones', 'description' => 'Muestra el aviso y bloquea el ingreso normal. Los usuarios de prueba habilitados mantienen su acceso especial.'],
        'USUARIO_PRUEBA_HABILITADO' => ['label' => 'Usuarios de prueba', 'description' => 'Permite el acceso especial con ?pruebas=1. Al apagarlo sus sesiones dejan de tener acceso.'],
        'SOLICITUDES_CREAR' => ['label' => 'Crear solicitudes', 'description' => 'Permite abrir el formulario y guardar nuevas solicitudes.'],
        'SELECCION_JEFES' => ['label' => 'Seleccionar jefes', 'description' => 'Permite elegir o cambiar el jefe. Al apagarlo se usa el asignado por la universidad y se conserva el actual al editar.'],
        'SOLICITUDES_EDITAR' => ['label' => 'Editar solicitudes', 'description' => 'Permite abrir la edicion y guardar los cambios.'],
        'SOLICITUDES_ELIMINAR' => ['label' => 'Eliminar solicitudes', 'description' => 'Permite eliminar solicitudes conforme a los permisos del usuario.'],
        'SOLICITUDES_CANCELAR' => ['label' => 'Cancelar agenda', 'description' => 'Permite cancelar solicitudes aprobadas conforme a los permisos del usuario.'],
        'GESTION_JEFES' => ['label' => 'Gestion de jefes', 'description' => 'Permite aprobar o rechazar como jefe inmediato.'],
        'GESTION_RRHH' => ['label' => 'Gestion de Talento Humano', 'description' => 'Permite aprobar o rechazar como Talento Humano.'],
        'NOTIFICACIONES' => ['label' => 'Notificaciones', 'description' => 'Permite generar, consultar y marcar notificaciones. Conserva las ya registradas.'],
        'EXPORTACIONES' => ['label' => 'Exportaciones', 'description' => 'Permite descargar los reportes Excel y CSV.'],
    ];

    public static function directory(): string
    {
        return dirname(__DIR__) . '/storage/private/control-panel';
    }

    public static function setupKeyPath(): string
    {
        return self::directory() . '/instalacion.key';
    }

    public static function enabled(string $key): bool
    {
        if (!isset(self::CONTROLES[$key])) {
            throw new InvalidArgumentException('Control desconocido.');
        }
        static $snapshot = null;
        $snapshot ??= self::state()['flags'];
        return $snapshot[$key];
    }

    public static function state(): array
    {
        if (!is_file(self::directory() . '/estado.json')) {
            return self::initialState();
        }
        return self::readFile();
    }

    public static function isConfigured(): bool
    {
        return self::state()['account'] !== null;
    }

    public static function setupAvailable(): bool
    {
        return !self::isConfigured() && is_file(self::setupKeyPath());
    }

    public static function setup(string $key, string $username, string $password, string $ip): array
    {
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,80}$/D', $username) || strlen($password) < 12 || strlen($password) > 72) {
            throw new DomainException('Usa un usuario de 3 a 80 caracteres y una contrasena de 12 a 72 bytes.');
        }
        $result = self::change(function (array &$state) use ($key, $username, $password, $ip): array {
            if ($state['account'] !== null) {
                throw new DomainException('El acceso ya fue configurado. Ingresa con el usuario del panel.');
            }
            if (self::blocked($state, 'setup', $ip)) {
                return ['ok' => false, 'blocked' => true];
            }
            $expected = is_file(self::setupKeyPath()) ? trim((string)@file_get_contents(self::setupKeyPath())) : '';
            if (strlen($expected) < 32 || $key === '' || !hash_equals($expected, trim($key))) {
                self::failure($state, 'setup', $ip);
                return ['ok' => false, 'blocked' => false];
            }
            $state['account'] = ['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)];
            $state['attempts'] = [];
            self::audit($state, $username, 'SETUP_SUCCESS');
            return ['ok' => true, 'blocked' => false];
        });
        if ($result['ok']) {
            // Si el borrado falla, la cuenta ya creada impide reutilizar la llave.
            @unlink(self::setupKeyPath());
        }
        return $result;
    }

    public static function login(string $username, string $password, string $ip): array
    {
        return self::change(function (array &$state) use ($username, $password, $ip): array {
            if (self::blocked($state, $username, $ip)) {
                return ['ok' => false, 'blocked' => true];
            }
            $account = $state['account'];
            if ($account === null || !password_verify($password, $account['password_hash'])
                || !hash_equals($account['username'], $username)) {
                self::failure($state, $username, $ip);
                self::audit($state, 'anonymous', 'LOGIN_FAILURE');
                return ['ok' => false, 'blocked' => false];
            }
            self::clearFailures($state, $username, $ip);
            self::audit($state, $username, 'LOGIN_SUCCESS');
            return ['ok' => true, 'blocked' => false];
        });
    }

    public static function save(array $values, int $revision, string $username, string $password, string $ip): array
    {
        if (count($values) !== count(self::CONTROLES) || array_diff_key(self::CONTROLES, $values)) {
            throw new DomainException('El formulario esta incompleto. Recarga la pagina.');
        }
        foreach ($values as $value) {
            if (!is_bool($value)) {
                throw new DomainException('Un interruptor tiene un valor invalido.');
            }
        }
        return self::change(function (array &$state) use ($values, $revision, $username, $password, $ip): array {
            if ($state['revision'] !== $revision) {
                throw new DomainException('Los controles cambiaron en otra sesion. Revisa el estado actual.');
            }
            if ($state['flags'] === $values) {
                return ['ok' => true, 'changes' => 0];
            }
            if (self::blocked($state, $username, $ip)) {
                return ['ok' => false, 'blocked' => true];
            }
            $account = $state['account'];
            if ($account === null || !hash_equals($account['username'], $username)
                || !password_verify($password, $account['password_hash'])) {
                self::failure($state, $username, $ip);
                return ['ok' => false, 'blocked' => false];
            }
            self::clearFailures($state, $username, $ip);
            $changes = 0;
            foreach (self::CONTROLES as $key => $_definition) {
                if ($state['flags'][$key] !== $values[$key]) {
                    self::audit($state, $username, 'FEATURE_CHANGE', $key, $state['flags'][$key], $values[$key]);
                    $changes++;
                }
            }
            $state['flags'] = $values;
            $state['revision']++;
            return ['ok' => true, 'changes' => $changes];
        });
    }

    private static function initialState(): array
    {
        // Primera instalacion: conserva el comportamiento previo. Desde entonces manda el estado guardado por el panel.
        return ['schema' => 1, 'revision' => 0, 'flags' => array_fill_keys(array_keys(self::CONTROLES), true),
            'account' => null, 'attempts' => [], 'audit' => []];
    }

    private static function readFile(): array
    {
        $contents = @file_get_contents(self::directory() . '/estado.json');
        if ($contents === false) {
            throw new RuntimeException('No fue posible leer el estado del panel.');
        }
        $state = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($state) || ($state['schema'] ?? null) !== 1 || !is_int($state['revision'] ?? null)
            || !is_array($state['flags'] ?? null) || count($state['flags']) !== count(self::CONTROLES)
            || array_diff_key(self::CONTROLES, $state['flags']) || !array_key_exists('account', $state)
            || !is_array($state['attempts'] ?? null) || !is_array($state['audit'] ?? null)) {
            throw new RuntimeException('El estado del panel no es valido.');
        }
        foreach ($state['flags'] as $value) {
            if (!is_bool($value)) {
                throw new RuntimeException('El estado de una funcionalidad no es valido.');
            }
        }
        if ($state['account'] !== null && (!is_string($state['account']['username'] ?? null)
            || !is_string($state['account']['password_hash'] ?? null)
            || (password_get_info($state['account']['password_hash'])['algo'] ?? null) === null)) {
            throw new RuntimeException('El acceso del panel no es valido.');
        }
        return $state;
    }

    private static function change(callable $operation): array
    {
        $directory = self::directory();
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el almacenamiento privado.');
        }
        if (!is_file($directory . '/.htaccess') && @file_put_contents($directory . '/.htaccess', "Require all denied\n") === false) {
            throw new RuntimeException('No se pudo proteger el almacenamiento privado.');
        }
        $lock = @fopen($directory . '/estado.lock', 'c+b');
        if ($lock === false) {
            throw new RuntimeException('No se pudo abrir el bloqueo del panel.');
        }
        $temporary = false;
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear el estado del panel.');
            }
            $state = is_file($directory . '/estado.json') ? self::readFile() : self::initialState();
            $result = $operation($state);
            $json = json_encode($state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $temporary = @tempnam($directory, 'estado-');
            if ($temporary === false) {
                throw new RuntimeException('No se pudo preparar el guardado.');
            }
            @chmod($temporary, 0600);
            if (@file_put_contents($temporary, $json, LOCK_EX) !== strlen($json)
                || !@rename($temporary, $directory . '/estado.json')) {
                throw new RuntimeException('No se pudo guardar el estado del panel.');
            }
            return $result;
        } finally {
            if ($temporary !== false && is_file($temporary)) {
                @unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function blocked(array &$state, string $username, string $ip): bool
    {
        $state['attempts'] = array_values(array_filter($state['attempts'], static fn(array $attempt): bool => $attempt['time'] > time() - 900));
        $userHash = hash('sha256', strtolower($username));
        $ipHash = hash('sha256', $ip);
        $users = 0;
        $ips = 0;
        foreach ($state['attempts'] as $attempt) {
            $users += (int)($attempt['user'] === $userHash);
            $ips += (int)($attempt['ip'] === $ipHash);
        }
        return $users >= 5 || $ips >= 5;
    }

    private static function failure(array &$state, string $username, string $ip): void
    {
        $state['attempts'][] = ['user' => hash('sha256', strtolower($username)), 'ip' => hash('sha256', $ip), 'time' => time()];
        $state['attempts'] = array_slice($state['attempts'], -2000);
    }

    private static function clearFailures(array &$state, string $username, string $ip): void
    {
        $userHash = hash('sha256', strtolower($username));
        $ipHash = hash('sha256', $ip);
        $state['attempts'] = array_values(array_filter($state['attempts'],
            static fn(array $attempt): bool => $attempt['user'] !== $userHash && $attempt['ip'] !== $ipHash));
    }

    private static function audit(array &$state, string $username, string $action, ?string $key = null,
        ?bool $old = null, ?bool $new = null): void
    {
        $state['audit'][] = ['time' => gmdate('c'), 'user' => $username, 'action' => $action, 'key' => $key, 'old' => $old, 'new' => $new];
        $state['audit'] = array_slice($state['audit'], -500);
    }
}
