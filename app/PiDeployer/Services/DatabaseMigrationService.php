<?php

namespace App\PiDeployer\Services;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseMigrationService
{
    protected function resolvePath(?string $targetPath = null): string
    {
        if (! empty($targetPath) && is_dir($targetPath)) {
            return rtrim($targetPath, '/\\');
        }

        return config('pi-deployer.target_path', base_path());
    }

    protected function executeArtisan(string $command, array $params = [], ?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);

        if (rtrim($base, '/\\') === rtrim(base_path(), '/\\')) {
            $exitCode = Artisan::call($command, $params);

            return [
                'success' => $exitCode === 0,
                'output' => Artisan::output(),
            ];
        }

        $cmdArray = ['php', 'artisan', $command];
        foreach ($params as $k => $v) {
            if (is_bool($v) && $v) {
                $cmdArray[] = $k;
            } elseif (is_string($k) && ! is_numeric($k)) {
                $cmdArray[] = "{$k}={$v}";
            } else {
                $cmdArray[] = (string) $v;
            }
        }

        $process = new Process($cmdArray, $base, null, null, 180);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput() ?: $process->getErrorOutput(),
        ];
    }

    /**
     * Get migration status overview.
     *
     * @return array<string, mixed>
     */
    public function getStatus(?string $targetPath = null): array
    {
        try {
            $res = $this->executeArtisan('migrate:status', [], $targetPath);

            return [
                'db_connected' => $res['success'],
                'output' => $res['output'],
            ];
        } catch (Throwable $e) {
            return [
                'db_connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute migrations.
     *
     * @return array<string, mixed>
     */
    public function runMigrations(bool $fresh = false, ?string $targetPath = null): array
    {
        try {
            $command = $fresh ? 'migrate:fresh' : 'migrate';
            $res = $this->executeArtisan($command, ['--force' => true], $targetPath);

            return [
                'success' => $res['success'],
                'fresh' => $fresh,
                'target_path' => $this->resolvePath($targetPath),
                'output' => $res['output'],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'fresh' => $fresh,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run Database Seeders.
     *
     * @return array<string, mixed>
     */
    public function runSeeders(?string $seederClass = null, ?string $targetPath = null): array
    {
        try {
            $params = ['--force' => true];
            if ($seederClass) {
                $params['--class'] = $seederClass;
            }

            $res = $this->executeArtisan('db:seed', $params, $targetPath);

            return [
                'success' => $res['success'],
                'class' => $seederClass ?? 'DatabaseSeeder',
                'target_path' => $this->resolvePath($targetPath),
                'output' => $res['output'],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'class' => $seederClass ?? 'DatabaseSeeder',
                'error' => $e->getMessage(),
            ];
        }
    }
}
