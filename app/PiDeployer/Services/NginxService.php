<?php

namespace App\PiDeployer\Services;

use Illuminate\Support\Str;

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
        $rawAppName = ! empty($params['app_name']) ? $params['app_name'] : 'pi-deployer';
        $appName = Str::slug($rawAppName, '_');
        if (empty($appName)) {
            $appName = 'pi-deployer';
        }

        $port = (int) ($params['port'] ?? 8445);
        if ($port <= 0 || $port > 65535) {
            $port = 8445;
        }

        $serverName = trim($params['server_name'] ?? 'rhz.internet-box.ch');
        if (empty($serverName)) {
            $serverName = '_';
        }

        $rootPath = rtrim(trim($params['root_path'] ?? '/var/www/pi-deployer'), '/\\');
        if (! str_ends_with($rootPath, '/public')) {
            $rootPath .= '/public';
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
}
