<?php

use App\Http\Controllers\PiDeployerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/pi-deploy');
});

Route::prefix(config('pi-deployer.route_prefix', 'pi-deploy'))
    ->middleware(config('pi-deployer.middleware', ['web']))
    ->group(function () {
        Route::get('/', [PiDeployerController::class, 'index'])->name('pi-deployer.index');
        Route::get('/api/audit', [PiDeployerController::class, 'audit'])->name('pi-deployer.audit');
        Route::post('/api/fix-permissions', [PiDeployerController::class, 'fixPermissions'])->name('pi-deployer.fix-permissions');
        Route::post('/api/git-pull', [PiDeployerController::class, 'gitPull'])->name('pi-deployer.git-pull');
        Route::post('/api/test-db', [PiDeployerController::class, 'testDb'])->name('pi-deployer.test-db');
        Route::post('/api/create-db-user', [PiDeployerController::class, 'createDbUser'])->name('pi-deployer.create-db-user');
        Route::post('/api/save-env', [PiDeployerController::class, 'saveEnv'])->name('pi-deployer.save-env');
        Route::post('/api/composer-install', [PiDeployerController::class, 'composerInstall'])->name('pi-deployer.composer-install');
        Route::post('/api/run-migrations', [PiDeployerController::class, 'runMigrations'])->name('pi-deployer.run-migrations');
        Route::post('/api/run-seeders', [PiDeployerController::class, 'runSeeders'])->name('pi-deployer.run-seeders');
        Route::post('/api/optimize', [PiDeployerController::class, 'optimize'])->name('pi-deployer.optimize');
    });
