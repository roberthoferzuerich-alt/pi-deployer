<?php

namespace App\PiDeployer\Services;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Throwable;

class OptimizationService
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
     * Run Laravel caches & optimizations.
     *
     * @return array<string, mixed>
     */
    public function optimize(?string $targetPath = null): array
    {
        $logs = [];

        try {
            $res1 = $this->executeArtisan('config:cache', [], $targetPath);
            $logs[] = $res1['output'];

            $res2 = $this->executeArtisan('route:cache', [], $targetPath);
            $logs[] = $res2['output'];

            $res3 = $this->executeArtisan('view:cache', [], $targetPath);
            $logs[] = $res3['output'];

            return [
                'success' => true,
                'target_path' => $this->resolvePath($targetPath),
                'output' => implode("\n", array_filter($logs)),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear all Laravel caches.
     *
     * @return array<string, mixed>
     */
    public function clearCaches(?string $targetPath = null): array
    {
        try {
            $res = $this->executeArtisan('optimize:clear', [], $targetPath);

            return [
                'success' => $res['success'],
                'target_path' => $this->resolvePath($targetPath),
                'output' => $res['output'],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
