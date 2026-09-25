<?php

namespace App\PiDeployer\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class SystemCheckerService
{
    protected function resolvePath(?string $targetPath = null): string
    {
        if (! empty($targetPath) && File::isDirectory($targetPath)) {
            return rtrim($targetPath, '/\\');
        }

        return config('pi-deployer.target_path', base_path());
    }

    /**
     * Audit system requirements, permissions, and environment info.
     *
     * @return array<string, mixed>
     */
    public function audit(?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'curl'];
        $extensionStatus = [];

        foreach ($requiredExtensions as $ext) {
            $extensionStatus[$ext] = extension_loaded($ext);
        }

        $writablePaths = config('pi-deployer.permissions.paths', [
            'storage',
            'bootstrap/cache',
        ]);

        $permissionStatus = [];
        foreach ($writablePaths as $relativePath) {
            $fullPath = $base.DIRECTORY_SEPARATOR.$relativePath;
            if (! File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0775, true, true);
            }
            $permissionStatus[$relativePath] = [
                'exists' => File::exists($fullPath),
                'is_writable' => is_writable($fullPath),
                'perms' => File::exists($fullPath) ? substr(sprintf('%o', fileperms($fullPath)), -4) : '0000',
            ];
        }

        return [
            'target_path' => $base,
            'php_version' => PHP_VERSION,
            'php_version_ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'os' => PHP_OS_FAMILY,
            'is_raspberry_pi' => $this->isRaspberryPi(),
            'extensions' => $extensionStatus,
            'all_extensions_ok' => ! in_array(false, $extensionStatus, true),
            'permissions' => $permissionStatus,
            'all_permissions_ok' => array_reduce($permissionStatus, fn (bool $carry, array $item): bool => $carry && $item['is_writable'], true),
            'disk_free_space' => $this->getFormattedDiskSpace($base),
        ];
    }

    /**
     * Fix permissions for storage and bootstrap/cache directories.
     *
     * @return array<string, mixed>
     */
    public function fixPermissions(?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);
        $paths = config('pi-deployer.permissions.paths', ['storage', 'bootstrap/cache']);
        $results = [];

        foreach ($paths as $relativePath) {
            $fullPath = $base.DIRECTORY_SEPARATOR.$relativePath;
            if (! File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0775, true, true);
            }

            $success = @chmod($fullPath, 0775);
            $results[$relativePath] = [
                'fixed' => $success,
                'is_writable' => is_writable($fullPath),
            ];
        }

        if (PHP_OS_FAMILY === 'Linux') {
            $webUser = config('pi-deployer.raspberry_pi.web_user', 'www-data');
            $webGroup = config('pi-deployer.raspberry_pi.web_group', 'www-data');

            $process = new Process(['chown', '-R', "{$webUser}:{$webGroup}", $base.DIRECTORY_SEPARATOR.'storage', $base.DIRECTORY_SEPARATOR.'bootstrap/cache']);
            $process->run();
        }

        return [
            'success' => true,
            'target_path' => $base,
            'details' => $results,
        ];
    }

    /**
     * Detect if running on Raspberry Pi.
     */
    protected function isRaspberryPi(): bool
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return false;
        }

        if (File::exists('/proc/device-tree/model')) {
            $model = File::get('/proc/device-tree/model');

            return str_contains(strtolower($model), 'raspberry pi');
        }

        return false;
    }

    /**
     * Format available disk space.
     */
    protected function getFormattedDiskSpace(?string $path = null): string
    {
        $base = $path ?: base_path();
        $bytes = @disk_free_space($base);
        if ($bytes === false) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
