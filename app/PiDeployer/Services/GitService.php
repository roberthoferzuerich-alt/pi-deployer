<?php

namespace App\PiDeployer\Services;

use App\PiDeployer\Services\Concerns\ResolvesTargetPath;
use Symfony\Component\Process\Process;

class GitService
{
    use ResolvesTargetPath;

    protected function ensureSafeDirectory(string $path): void
    {
        if (in_array(PHP_OS_FAMILY, ['Linux', 'BSD', 'Darwin'])) {
            $proc = new Process(['git', 'config', '--global', '--add', 'safe.directory', $path]);
            $proc->run();
        }
    }

    /**
     * Get current Git status and commit information.
     *
     * @return array<string, mixed>
     */
    public function getStatus(?string $targetPath = null): array
    {
        $base = $this->resolvePath($targetPath);
        $this->ensureSafeDirectory($base);
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
        $validated = $this->resolveAndValidatePath($targetPath);
        if (! $validated['valid']) {
            return [
                'success' => false,
                'branch' => $branch ?? config('pi-deployer.git.default_branch', 'main'),
                'output' => $validated['error'],
            ];
        }

        $base = $validated['path'];
        $this->ensureSafeDirectory($base);
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
