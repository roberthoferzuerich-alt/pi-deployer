<?php

namespace App\PiDeployer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EnvironmentService
{
    /**
     * Resolve target directory path.
     */
    public function resolveTargetPath(?string $targetPath = null): string
    {
        if (! empty($targetPath) && File::isDirectory($targetPath)) {
            return rtrim($targetPath, '/\\');
        }

        return config('pi-deployer.target_path', base_path());
    }

    /**
     * Get environment details and variable values.
     *
     * @return array<string, mixed>
     */
    public function getEnvironmentData(?string $targetPath = null): array
    {
        $base = $this->resolveTargetPath($targetPath);
        $envPath = $base.DIRECTORY_SEPARATOR.'.env';
        $examplePath = $base.DIRECTORY_SEPARATOR.'.env.example';

        $exists = File::exists($envPath);

        if (! $exists && File::exists($examplePath)) {
            File::copy($examplePath, $envPath);
            $exists = true;
        }

        $envContent = $exists ? File::get($envPath) : '';
        $parsed = $this->parseEnv($envContent);

        return [
            'target_path' => $base,
            'env_exists' => $exists,
            'env_path' => $envPath,
            'app_name' => $parsed['APP_NAME'] ?? config('app.name'),
            'app_env' => $parsed['APP_ENV'] ?? config('app.env'),
            'app_debug' => $parsed['APP_DEBUG'] ?? 'false',
            'app_url' => $parsed['APP_URL'] ?? config('app.url'),
            'db_connection' => $parsed['DB_CONNECTION'] ?? config('database.default'),
            'db_host' => $parsed['DB_HOST'] ?? '127.0.0.1',
            'db_port' => $parsed['DB_PORT'] ?? '3306',
            'db_database' => $parsed['DB_DATABASE'] ?? 'laravel',
            'db_username' => $parsed['DB_USERNAME'] ?? 'root',
            'db_password' => $parsed['DB_PASSWORD'] ?? '',
            'has_app_key' => ! empty($parsed['APP_KEY']),
        ];
    }

    /**
     * Update environment variables in .env file.
     *
     * @param  array<string, string>  $values
     */
    public function updateEnvironment(array $values, ?string $targetPath = null): bool
    {
        $base = $this->resolveTargetPath($targetPath);
        $envPath = $base.DIRECTORY_SEPARATOR.'.env';

        if (! File::exists($envPath)) {
            $examplePath = $base.DIRECTORY_SEPARATOR.'.env.example';
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $content = File::get($envPath);

        foreach ($values as $key => $value) {
            $key = strtoupper(trim($key));
            $formattedValue = $this->formatEnvValue($value);

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$formattedValue}", $content);
            } else {
                $content .= "\n{$key}={$formattedValue}";
            }
        }

        File::put($envPath, trim($content)."\n");

        Artisan::call('config:clear');

        return true;
    }

    /**
     * Test database connection with given parameters or current config.
     *
     * @param  array<string, string>|null  $configOverride
     * @return array<string, mixed>
     */
    public function testDatabaseConnection(?array $configOverride = null): array
    {
        $driver = $configOverride['db_connection'] ?? config('database.default', 'mysql');

        if ($driver === 'sqlite') {
            try {
                $database = $configOverride['db_database'] ?? config('database.connections.sqlite.database', database_path('database.sqlite'));
                if ($database !== ':memory:' && ! File::exists($database)) {
                    File::put($database, '');
                }
                DB::purge('pi_test');
                config(['database.connections.pi_test' => [
                    'driver' => 'sqlite',
                    'database' => $database,
                ]]);
                DB::connection('pi_test')->getPdo();

                return ['success' => true, 'message' => 'SQLite-Verbindung erfolgreich!'];
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'SQLite-Fehler: '.$e->getMessage()];
            }
        }

        $host = $configOverride['db_host'] ?? config("database.connections.{$driver}.host", '127.0.0.1');
        $port = $configOverride['db_port'] ?? config("database.connections.{$driver}.port", '3306');
        $database = $configOverride['db_database'] ?? config("database.connections.{$driver}.database", '');
        $username = $configOverride['db_username'] ?? config("database.connections.{$driver}.username", '');
        $password = $configOverride['db_password'] ?? config("database.connections.{$driver}.password", '');

        try {
            config([
                'database.connections.pi_test' => [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ]);

            DB::purge('pi_test');
            DB::connection('pi_test')->getPdo();

            return [
                'success' => true,
                'message' => "Datenbank-Verbindung ({$driver}://{$username}@{$host}/{$database}) erfolgreich hergestellt!",
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            // Auto-create DB if error 1049 (Unknown database)
            if (str_contains($msg, '1049') && ! empty($database)) {
                try {
                    $pdo = new \PDO("mysql:host={$host};port={$port}", $username, $password);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                    DB::purge('pi_test');
                    DB::connection('pi_test')->getPdo();

                    return [
                        'success' => true,
                        'message' => "Datenbank `{$database}` wurde automatisch erstellt und Verbindung erfolgreich hergestellt!",
                    ];
                } catch (\Throwable $createErr) {
                    $msg = $createErr->getMessage();
                }
            }

            if (str_contains($msg, '1045')) {
                $msg .= " [Hinweis Laragon/MySQL]: Der Benutzer '{$username}' existiert noch nicht in MySQL oder das Passwort stimmt nicht. Für Laragon standardmäßig 'root' ohne Passwort nutzen oder den User '{$username}' in HeidiSQL/phpMyAdmin anlegen.]";
            }

            return [
                'success' => false,
                'message' => 'DB-Verbindung fehlgeschlagen: '.$msg,
            ];
        }
    }

    /**
     * Attempt to create database user and grant privileges.
     *
     * @param  array<string, string>  $config
     * @return array<string, mixed>
     */
    public function createDatabaseUser(array $config, ?string $adminUser = null, ?string $adminPassword = null): array
    {
        $driver = $config['db_connection'] ?? 'mysql';
        if (! in_array($driver, ['mysql', 'mariadb'])) {
            return [
                'success' => false,
                'message' => "Benutzererstellung wird für den Treibertyp '{$driver}' nicht unterstützt.",
            ];
        }

        $host = $config['db_host'] ?? '127.0.0.1';
        $port = $config['db_port'] ?? '3306';
        $database = $config['db_database'] ?? '';
        $username = $config['db_username'] ?? '';
        $password = $config['db_password'] ?? '';

        if (empty($username)) {
            return [
                'success' => false,
                'message' => 'Es wurde kein Datenbank-Benutzername angegeben.',
            ];
        }

        $adminCredentials = [];
        if (! empty($adminUser)) {
            $adminCredentials[] = ['user' => $adminUser, 'pass' => $adminPassword ?? ''];
        }
        $adminCredentials[] = ['user' => 'root', 'pass' => ''];
        $adminCredentials[] = ['user' => 'root', 'pass' => 'root'];

        $adminPdo = null;
        $lastError = '';

        foreach ($adminCredentials as $cred) {
            try {
                $dsn = "mysql:host={$host};port={$port}";
                $adminPdo = new \PDO($dsn, $cred['user'], $cred['pass'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
                break;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        if (! $adminPdo) {
            return [
                'success' => false,
                'message' => 'Konnte keine Admin-Verbindung zu MySQL herstellen (z.B. als root). Fehler: '.$lastError,
            ];
        }

        try {
            $userEscaped = addslashes($username);
            $passEscaped = addslashes($password);

            foreach (['%', 'localhost'] as $hostScope) {
                try {
                    $adminPdo->exec("CREATE USER IF NOT EXISTS '{$userEscaped}'@'{$hostScope}' IDENTIFIED BY '{$passEscaped}';");
                } catch (\Throwable $e) {
                    try {
                        $adminPdo->exec("CREATE USER '{$userEscaped}'@'{$hostScope}' IDENTIFIED BY '{$passEscaped}';");
                    } catch (\Throwable $e2) {
                        // ignore if already exists
                    }
                }

                try {
                    $adminPdo->exec("ALTER USER '{$userEscaped}'@'{$hostScope}' IDENTIFIED BY '{$passEscaped}';");
                } catch (\Throwable $e) {
                    // ignore if alter is unsupported
                }

                try {
                    $adminPdo->exec("GRANT ALL PRIVILEGES ON *.* TO '{$userEscaped}'@'{$hostScope}' WITH GRANT OPTION;");
                } catch (\Throwable $e) {
                    try {
                        $adminPdo->exec("GRANT ALL PRIVILEGES ON *.* TO '{$userEscaped}'@'{$hostScope}';");
                    } catch (\Throwable $e2) {
                        if (! empty($database)) {
                            $dbEscaped = str_replace('`', '``', $database);
                            $adminPdo->exec("GRANT ALL PRIVILEGES ON `{$dbEscaped}`.* TO '{$userEscaped}'@'{$hostScope}';");
                        }
                    }
                }
            }

            if (! empty($database)) {
                $dbEscaped = str_replace('`', '``', $database);
                $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbEscaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            }

            $adminPdo->exec('FLUSH PRIVILEGES;');

            $testResult = $this->testDatabaseConnection($config);

            if ($testResult['success']) {
                return [
                    'success' => true,
                    'message' => "Benutzer '{$username}' wurde erfolgreich in MySQL angelegt und Rechte wurden vergeben!",
                ];
            }

            return [
                'success' => true,
                'message' => "Benutzer '{$username}' wurde angelegt. DB-Test: ".$testResult['message'],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Erstellen des Benutzers: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Parse raw .env file string into array.
     *
     * @return array<string, string>
     */
    protected function parseEnv(string $content): array
    {
        $lines = explode("\n", $content);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Format value for .env output.
     */
    protected function formatEnvValue(string $value): string
    {
        if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '$')) {
            return '"'.addcslashes($value, '"').'"';
        }

        return $value;
    }
}
