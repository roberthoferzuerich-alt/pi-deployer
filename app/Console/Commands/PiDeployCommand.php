<?php

namespace App\Console\Commands;

use App\PiDeployer\Services\ComposerService;
use App\PiDeployer\Services\DatabaseMigrationService;
use App\PiDeployer\Services\EnvironmentService;
use App\PiDeployer\Services\GitService;
use App\PiDeployer\Services\OptimizationService;
use App\PiDeployer\Services\SystemCheckerService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class PiDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pi:deploy
                            {--branch= : Target Git branch}
                            {--fresh-migration : Execute migrate:fresh}
                            {--seed : Execute database seeders}
                            {--skip-composer : Skip composer install}
                            {--skip-git : Skip git pull}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated Raspberry Pi 5 Laravel Deployment & Migration Assistant';

    /**
     * Execute the console command.
     */
    public function handle(
        SystemCheckerService $systemChecker,
        GitService $gitService,
        EnvironmentService $envService,
        ComposerService $composerService,
        DatabaseMigrationService $dbService,
        OptimizationService $optService
    ): int {
        intro('🚀 Raspberry Pi 5 Laravel Deployment & Migration Wizard');

        $targetPath = config('pi-deployer.target_path');
        if (empty($targetPath) || ! file_exists($targetPath) || ! is_dir($targetPath)) {
            $this->error('❌ Ziel-Verzeichnis existiert nicht oder ist nicht konfiguriert: '.($targetPath ?: '[nicht gesetzt]'));
            $this->warn('Bitte erstelle den Ziel-Ordner neu oder setze PI_TARGET_PROJECT_PATH in .env auf ein gültiges Projekt (z.B. /var/www/mein-projekt).');

            return self::FAILURE;
        }

        $realTarget = realpath($targetPath);
        $realDeployer = realpath(base_path());
        if ($realTarget !== false && $realDeployer !== false && rtrim($realTarget, '/\\') === rtrim($realDeployer, '/\\')) {
            if (! config('pi-deployer.allow_self_deploy', false)) {
                $this->error("❌ Sicherheitssperre: Pi-Deployer darf nicht auf sich selbst ({$targetPath}) ausgeführt werden.");
                $this->warn('Bitte setze PI_TARGET_PROJECT_PATH in .env auf den Pfad deines Ziel-Projekts.');

                return self::FAILURE;
            }
        }

        // Step 1: System & Permissions Check
        note('Step 1: Checking System & Directory Permissions...');
        $audit = $systemChecker->audit();

        if (! $audit['all_permissions_ok']) {
            $this->warn('Some directories are not writable. Attempting auto-fix...');
            $systemChecker->fixPermissions();
            $this->info('Permissions updated to 0775.');
        } else {
            $this->info('✓ System permissions look great.');
        }

        // Step 2: Git Code Sync
        if (! $this->option('skip-git')) {
            $gitStatus = $gitService->getStatus();
            if ($gitStatus['is_git_repo']) {
                $branch = $this->option('branch') ?: ($gitStatus['current_branch'] ?: 'main');
                note("Step 2: Syncing code from GitHub (Branch: {$branch})...");

                $pullResult = spin(
                    fn (): array => $gitService->pull($branch),
                    'Fetching latest commits from GitHub...'
                );

                if ($pullResult['success']) {
                    $this->info('✓ Code successfully updated from GitHub.');
                } else {
                    $this->error('Failed to pull from GitHub: '.$pullResult['output']);
                }
            } else {
                $this->warn('Not a Git repository or no remote configured. Skipping Git pull.');
            }
        }

        // Step 3: Environment Check
        $envData = $envService->getEnvironmentData();
        note("Step 3: Checking .env file ({$envData['env_path']}) and Database Credentials...");
        $this->info('✓ .env file path: '.$envData['env_path']);
        $dbTest = $envService->testDatabaseConnection();

        if (! $dbTest['success']) {
            $this->warn('Database connection failed or database missing. Attempting auto-creation...');
            $createDbRes = $envService->createDatabase([
                'target_path' => $envData['target_path'],
                'db_connection' => $envData['db_connection'],
                'db_host' => $envData['db_host'],
                'db_port' => $envData['db_port'],
                'db_database' => $envData['db_database'],
                'db_username' => $envData['db_username'],
                'db_password' => $envData['db_password'],
            ]);

            if ($createDbRes['success']) {
                $this->info("✓ {$createDbRes['message']}");
            } else {
                $this->error('Database connection failed: '.$dbTest['message']);
                if ($this->laravel->runningInConsole() && confirm('Would you like to configure DB settings now?')) {
                    $host = text('DB Host', default: $envData['db_host']);
                    $db = text('DB Database', default: $envData['db_database']);
                    $user = text('DB Username', default: $envData['db_username']);
                    $pass = text('DB Password', default: '');

                    $envService->updateEnvironment([
                        'DB_HOST' => $host,
                        'DB_DATABASE' => $db,
                        'DB_USERNAME' => $user,
                        'DB_PASSWORD' => $pass,
                    ]);
                }
            }
        } else {
            $this->info('✓ Database connection verified.');
        }

        // Step 4: Composer dependencies
        if (! $this->option('skip-composer')) {
            note('Step 4: Regenerating Vendor Packages (composer install)...');
            $compResult = spin(
                fn (): array => $composerService->install(),
                'Installing Composer dependencies...'
            );

            if ($compResult['success']) {
                $this->info('✓ Composer vendor packages regenerated.');
            } else {
                $this->error('Composer installation failed: '.$compResult['output']);
            }
        }

        // Step 5: Database Migration & Seeding
        note('Step 5: Executing Database Migrations...');
        $fresh = $this->option('fresh-migration');
        $migResult = spin(
            fn (): array => $dbService->runMigrations($fresh),
            'Running php artisan migrate...'
        );

        if ($migResult['success']) {
            $this->info('✓ Database migrations applied successfully.');
        } else {
            $this->error('Migration error: '.($migResult['error'] ?? 'Unknown error'));
        }

        if ($this->option('seed') || confirm('Run Database Seeders now?', default: false)) {
            note('Running Database Seeders...');
            $seedResult = $dbService->runSeeders();
            if ($seedResult['success']) {
                $this->info('✓ Database seeders executed.');
            } else {
                $this->error('Seeder error: '.($seedResult['error'] ?? 'Unknown error'));
            }
        }

        // Step 6: Cache Optimization
        note('Step 6: Caching Configuration, Routes, and Views...');
        $optService->optimize();
        $this->info('✓ App caches created.');

        outro('🎉 Migration & Deployment on Raspberry Pi completed successfully!');

        return self::SUCCESS;
    }
}
