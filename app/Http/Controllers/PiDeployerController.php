<?php

namespace App\Http\Controllers;

use App\PiDeployer\Services\ComposerService;
use App\PiDeployer\Services\DatabaseMigrationService;
use App\PiDeployer\Services\EnvironmentService;
use App\PiDeployer\Services\GitService;
use App\PiDeployer\Services\OptimizationService;
use App\PiDeployer\Services\SystemCheckerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PiDeployerController extends Controller
{
    public function index(
        Request $request,
        SystemCheckerService $systemChecker,
        GitService $gitService,
        EnvironmentService $envService
    ): View {
        $targetPath = $request->input('target_path') ?: config('pi-deployer.target_path', base_path());
        $audit = $systemChecker->audit($targetPath);
        $gitStatus = $gitService->getStatus($targetPath);
        $envData = $envService->getEnvironmentData($targetPath);

        return view('pi-deployer.index', compact('audit', 'gitStatus', 'envData', 'targetPath'));
    }

    public function audit(
        Request $request,
        SystemCheckerService $systemChecker,
        GitService $gitService,
        EnvironmentService $envService
    ): JsonResponse {
        $targetPath = $request->input('target_path');
        $audit = $systemChecker->audit($targetPath);
        $gitStatus = $gitService->getStatus($targetPath);
        $envData = $envService->getEnvironmentData($targetPath);

        return response()->json(array_merge($audit, [
            'success' => true,
            'gitStatus' => $gitStatus,
            'envData' => $envData,
        ]));
    }

    public function fixPermissions(Request $request, SystemCheckerService $systemChecker): JsonResponse
    {
        $targetPath = $request->input('target_path');

        return response()->json($systemChecker->fixPermissions($targetPath));
    }

    public function gitPull(Request $request, GitService $gitService): JsonResponse
    {
        $branch = $request->input('branch', config('pi-deployer.git.default_branch', 'main'));
        $hardReset = (bool) $request->input('hard_reset', false);
        $targetPath = $request->input('target_path');

        return response()->json($gitService->pull($branch, $hardReset, $targetPath));
    }

    public function testDb(Request $request, EnvironmentService $envService): JsonResponse
    {
        $config = $request->only(['db_connection', 'db_host', 'db_port', 'db_database', 'db_username', 'db_password']);

        return response()->json($envService->testDatabaseConnection(array_filter($config, fn ($v) => $v !== null && $v !== '') ? $config : null));
    }

    public function createDbUser(Request $request, EnvironmentService $envService): JsonResponse
    {
        $config = $request->only(['db_connection', 'db_host', 'db_port', 'db_database', 'db_username', 'db_password']);
        $adminUser = $request->input('admin_username');
        $adminPassword = $request->input('admin_password');

        return response()->json($envService->createDatabaseUser(
            array_filter($config, fn ($v) => $v !== null && $v !== '') ? $config : [],
            $adminUser,
            $adminPassword
        ));
    }

    public function saveEnv(Request $request, EnvironmentService $envService): JsonResponse
    {
        $validated = $request->validate([
            'APP_NAME' => 'nullable|string',
            'APP_ENV' => 'nullable|string',
            'APP_URL' => 'nullable|string',
            'DB_CONNECTION' => 'nullable|string',
            'DB_HOST' => 'required|string',
            'DB_PORT' => 'required|string',
            'DB_DATABASE' => 'required|string',
            'DB_USERNAME' => 'required|string',
            'DB_PASSWORD' => 'nullable|string',
        ]);

        $targetPath = $request->input('target_path');

        $dataToSave = [
            'DB_CONNECTION' => $validated['DB_CONNECTION'] ?? 'mysql',
            'DB_HOST' => $validated['DB_HOST'],
            'DB_PORT' => $validated['DB_PORT'] ?? '3306',
            'DB_DATABASE' => $validated['DB_DATABASE'],
            'DB_USERNAME' => $validated['DB_USERNAME'],
            'DB_PASSWORD' => $validated['DB_PASSWORD'] ?? '',
        ];

        if (! empty($validated['APP_NAME'])) {
            $dataToSave['APP_NAME'] = $validated['APP_NAME'];
        }
        if (! empty($validated['APP_ENV'])) {
            $dataToSave['APP_ENV'] = $validated['APP_ENV'];
        }
        if (! empty($validated['APP_URL'])) {
            $dataToSave['APP_URL'] = $validated['APP_URL'];
        }

        $success = $envService->updateEnvironment($dataToSave, $targetPath);
        $envData = $envService->getEnvironmentData($targetPath);

        return response()->json([
            'success' => $success,
            'env_path' => $envData['env_path'],
            'target_path' => $envData['target_path'],
            'env_data' => $envData,
        ]);
    }

    public function composerInstall(Request $request, ComposerService $composerService): JsonResponse
    {
        $devMode = (bool) $request->input('dev_mode', false);
        $targetPath = $request->input('target_path');

        return response()->json($composerService->install($devMode, $targetPath));
    }

    public function runMigrations(Request $request, DatabaseMigrationService $dbService): JsonResponse
    {
        $fresh = (bool) $request->input('fresh', false);
        $targetPath = $request->input('target_path');

        return response()->json($dbService->runMigrations($fresh, $targetPath));
    }

    public function runSeeders(Request $request, DatabaseMigrationService $dbService): JsonResponse
    {
        $seederClass = $request->input('seeder_class');
        $targetPath = $request->input('target_path');

        return response()->json($dbService->runSeeders($seederClass, $targetPath));
    }

    public function optimize(Request $request, OptimizationService $optService): JsonResponse
    {
        $targetPath = $request->input('target_path');

        return response()->json($optService->optimize($targetPath));
    }
}
