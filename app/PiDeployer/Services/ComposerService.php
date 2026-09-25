<?php

namespace App\PiDeployer\Services;

use Symfony\Component\Process\Process;

class ComposerService
{
    protected function resolvePath(?string $targetPath = null): string
    {
        if (! empty($targetPath) && is_dir($targetPath)) {
            return rtrim($targetPath, '/\\');
        }

        return config('pi-deployer.target_path', base_path());
    }

    /**
     * Run composer install / package generation.
     *
     * @return array<string, mixed>
     */
    public function install(bool $devMode = false, ?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);
        $binary = config('pi-deployer.composer.binary', 'composer');
        $args = [$binary, 'install', '--no-interaction'];

        if (! $devMode) {
            $args[] = '--no-dev';
            $args[] = '--optimize-autoloader';
        }

        $process = new Process($args, $base, null, null, 300);
        $process->run();

        $success = $process->isSuccessful();
        $output = $process->getOutput() ?: $process->getErrorOutput();

        return [
            'success' => $success,
            'target_path' => $base,
            'output' => $output,
        ];
    }

    /**
     * Run npm build assets if package.json exists.
     *
     * @return array<string, mixed>
     */
    public function buildNpmAssets(?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);

        if (! file_exists($base.DIRECTORY_SEPARATOR.'package.json')) {
            return [
                'skipped' => true,
                'target_path' => $base,
                'message' => 'No package.json found. NPM build skipped.',
            ];
        }

        $npmBinary = config('pi-deployer.npm.binary', 'npm');

        $installProc = new Process([$npmBinary, 'install', '--no-audit'], $base, null, null, 300);
        $installProc->run();

        $buildProc = new Process([$npmBinary, 'run', 'build'], $base, null, null, 300);
        $buildProc->run();

        $success = $buildProc->isSuccessful();

        return [
            'success' => $success,
            'target_path' => $base,
            'install_output' => $installProc->getOutput(),
            'build_output' => $buildProc->getOutput() ?: $buildProc->getErrorOutput(),
        ];
    }
}
