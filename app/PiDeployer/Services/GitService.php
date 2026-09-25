<?php

namespace App\PiDeployer\Services;

use Symfony\Component\Process\Process;

class GitService
{
    /**
     * Get current Git status and commit information.
     *
     * @return array<string, mixed>
     */
    /**
     * Resolve target path.
     */
    protected function resolvePath(?string $targetPath = null): string
    {
        if (! empty($targetPath) && is_dir($targetPath)) {
            return rtrim($targetPath, '/\\');
        }

        return config('pi-deployer.target_path', base_path());
    }

    /**
     * Get current Git status and commit information.
     *
     * @return array<string, mixed>
     */
    public function getStatus(?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);
        $isGitRepo = is_dir($base.DIRECTORY_SEPARATOR.'.git');

        if (! $isGitRepo) {
            return [
                'is_git_repo' => false,
                'current_branch' => null,
                'last_commit' => null,
                'remote_url' => null,
            ];
        }

        $branchProcess = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $base);
        $branchProcess->run();
        $branch = trim($branchProcess->getOutput());

        $commitProcess = new Process(['git', 'log', '-1', '--pretty=format:%h - %s (%cr) <%an>'], $base);
        $commitProcess->run();
        $lastCommit = trim($commitProcess->getOutput());

        $remoteProcess = new Process(['git', 'config', '--get', 'remote.origin.url'], $base);
        $remoteProcess->run();
        $remoteUrl = trim($remoteProcess->getOutput());

        return [
            'is_git_repo' => true,
            'current_branch' => $branch,
            'last_commit' => $lastCommit,
            'remote_url' => $remoteUrl,
        ];
    }

    /**
     * Pull the latest changes from Git repository.
     *
     * @return array<string, mixed>
     */
    public function pull(?string $branch = null, bool $hardReset = false, ?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);
        $targetBranch = $branch ?? config('pi-deployer.git.default_branch', 'main');
        $output = [];

        if ($hardReset) {
            $resetProc = new Process(['git', 'reset', '--hard'], $base);
            $resetProc->run();
            $output[] = 'Git hard reset performed: '.$resetProc->getOutput();
        }

        $fetchProc = new Process(['git', 'fetch', 'origin'], $base);
        $fetchProc->run();
        $output[] = 'Git fetch origin executed.';

        $pullProc = new Process(['git', 'pull', 'origin', $targetBranch], $base);
        $pullProc->run();
        $output[] = $pullProc->getOutput() ?: $pullProc->getErrorOutput();

        return [
            'success' => $pullProc->isSuccessful(),
            'branch' => $targetBranch,
            'output' => implode("\n", array_filter($output)),
        ];
    }
}
