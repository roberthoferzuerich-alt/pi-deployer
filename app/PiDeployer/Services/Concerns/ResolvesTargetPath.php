<?php

namespace App\PiDeployer\Services\Concerns;

use Illuminate\Support\Facades\File;

trait ResolvesTargetPath
{
    /**
     * Resolve target directory path.
     */
    protected function resolvePath(?string $targetPath = null): string
    {
        if (! empty($targetPath)) {
            return rtrim(trim($targetPath), '/\\');
        }

        $configPath = config('pi-deployer.target_path');
        if (! empty($configPath)) {
            return rtrim(trim($configPath), '/\\');
        }

        return base_path();
    }

    /**
     * Resolve target directory path and validate safety and existence.
     *
     * @return array{path: string, valid: bool, error: ?string}
     */
    protected function resolveAndValidatePath(?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);

        if (! File::exists($base) || ! File::isDirectory($base)) {
            return [
                'path' => $base,
                'valid' => false,
                'error' => "Ziel-Verzeichnis existiert nicht: {$base}. Bitte das Verzeichnis anlegen oder ein vorhandenes Ziel-Projekt angeben.",
            ];
        }

        $realBase = realpath($base);
        $realDeployer = realpath(base_path());

        if ($realBase !== false && $realDeployer !== false && rtrim($realBase, '/\\') === rtrim($realDeployer, '/\\')) {
            if (! config('pi-deployer.allow_self_deploy', false)) {
                return [
                    'path' => $base,
                    'valid' => false,
                    'error' => "Sicherheitssperre: Pi-Deployer darf keine Deployment-Aktionen auf sich selbst ({$base}) ausführen. Bitte erstelle den Ziel-Ordner neu oder passe PI_TARGET_PROJECT_PATH in .env an.",
                ];
            }
        }

        return [
            'path' => $base,
            'valid' => true,
            'error' => null,
        ];
    }
}
