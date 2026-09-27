<?php

namespace App\PiDeployer\Services;

use Illuminate\Support\Facades\File;

class NginxService
{
    /**
     * Generate generic Nginx configuration text and filename.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generateConfig(array $params): array
    {
        $rootPath = rtrim(trim($params['root_path'] ?? '/var/www/pi-deployer'), '/\\');
        if (! str_ends_with($rootPath, '/public')) {
            $rootPath .= '/public';
        }

        $rawAppName = ! empty($params['app_name']) ? trim($params['app_name']) : '';
        if (empty($rawAppName) || $rawAppName === 'pi-deployer') {
            $baseDir = preg_replace('/[\/\\\\]public$/i', '', $rootPath);
            $extractedSlug = basename($baseDir);
            if (! empty($extractedSlug) && ! in_array($extractedSlug, ['www', 'var', 'public', 'html'])) {
                $rawAppName = $extractedSlug;
            }
        }
        if (empty($rawAppName)) {
            $rawAppName = 'pi-deployer';
        }

        $appName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawAppName);

        $requestedPort = (int) ($params['port'] ?? 8445);
        if ($requestedPort <= 0 || $requestedPort > 65535) {
            $requestedPort = 8445;
        }

        $port = $requestedPort;
        if (! empty($params['auto_resolve_port']) && ! $this->isPortFree($requestedPort)) {
            $port = $this->findNextAvailablePort($requestedPort + 1);
        }

        $serverName = trim($params['server_name'] ?? 'rhz.internet-box.ch');
        if (empty($serverName)) {
            $serverName = '_';
        }

        $phpVersion = trim($params['php_version'] ?? '8.4');
        $sslEnabled = filter_var($params['ssl_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $certPath = trim($params['cert_path'] ?? "/etc/letsencrypt/live/{$serverName}/fullchain.pem");
        $keyPath = trim($params['key_path'] ?? "/etc/letsencrypt/live/{$serverName}/privkey.pem");

        $filename = "{$appName}_{$port}";
        $sitesAvailablePath = "/etc/nginx/sites-available/{$filename}";
        $sitesEnabledPath = "/etc/nginx/sites-enabled/{$filename}";

        $listenLines = $sslEnabled
            ? "    listen {$port} ssl;\n    listen [::]:{$port} ssl;"
            : "    listen {$port};\n    listen [::]:{$port};";

        $sslBlock = '';
        if ($sslEnabled) {
            $sslBlock = "\n\n    # SSL-Zertifikate (Let's Encrypt)\n    ssl_certificate {$certPath};\n    ssl_certificate_key {$keyPath};";
        }

        $configContent = <<<NGINX
# Generierte Nginx Konfiguration
# Dateiname: {$filename}
# Ziel-Pfad auf dem Pi: {$sitesAvailablePath}

server {
{$listenLines}
    server_name {$serverName};
    root {$rootPath};

    index index.php index.html index.htm;{$sslBlock}

    # 1. Standard Laravel Routing & Assets
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # 2. PHP-FPM Verarbeitung (PHP {$phpVersion})
    location ~ \\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php{$phpVersion}-fpm.sock;

        include fastcgi_params;
        fastcgi_param REQUEST_METHOD \$request_method;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    }

    # 3. Sicherheit (versteckte Dateien wie .env und .git blockieren)
    location ~ /\\.ht {
        deny all;
    }

    location ~ /\\.env {
        deny all;
    }

    location ~ /\\.git {
        deny all;
    }
}
NGINX;

        $setupCommands = implode("\n", [
            "sudo nano {$sitesAvailablePath}",
            "sudo ln -s {$sitesAvailablePath} {$sitesEnabledPath}",
            'sudo nginx -t',
            'sudo systemctl reload nginx',
        ]);

        return [
            'success' => true,
            'filename' => $filename,
            'port' => $port,
            'server_name' => $serverName,
            'root_path' => $rootPath,
            'php_version' => $phpVersion,
            'ssl_enabled' => $sslEnabled,
            'sites_available_path' => $sitesAvailablePath,
            'sites_enabled_path' => $sitesEnabledPath,
            'config' => $configContent,
            'setup_commands' => $setupCommands,
        ];
    }

    /**
     * Check if a specific network port is currently free (not in use).
     */
    public function isPortFree(int $port, string $host = '127.0.0.1'): bool
    {
        if ($port <= 0 || $port > 65535) {
            return false;
        }

        $connection = @fsockopen($host, $port, $errno, $errstr, 0.4);
        if (is_resource($connection)) {
            fclose($connection);

            return false;
        }

        $nginxUsedPorts = $this->getUsedPortsFromNginx();
        if (in_array($port, $nginxUsedPorts, true)) {
            return false;
        }

        return true;
    }

    /**
     * Find the next available free port starting from a given port.
     */
    public function findNextAvailablePort(int $startPort = 8445, int $maxPort = 8550): int
    {
        for ($p = $startPort; $p <= $maxPort; $p++) {
            if ($this->isPortFree($p)) {
                return $p;
            }
        }

        return $startPort;
    }

    /**
     * Parse Nginx config files to find all configured listen ports.
     *
     * @return array<int, int>
     */
    public function getUsedPortsFromNginx(): array
    {
        $usedPorts = [80, 443];
        $paths = ['/etc/nginx/sites-enabled', '/etc/nginx/sites-available'];

        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                $files = File::files($path);
                foreach ($files as $file) {
                    $content = File::get($file->getRealPath());
                    if (preg_match_all('/listen\s+\[?::\]?:?(\d+)/i', $content, $matches)) {
                        foreach ($matches[1] as $p) {
                            $usedPorts[] = (int) $p;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($usedPorts));
    }
}
