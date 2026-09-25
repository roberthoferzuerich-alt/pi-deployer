<?php

namespace App\Console\Commands;

use App\PiDeployer\Services\SystemCheckerService;
use Illuminate\Console\Command;

class PiCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pi:check {--fix : Automatically fix permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Raspberry Pi system requirements, PHP extensions, and folder permissions';

    /**
     * Execute the console command.
     */
    public function handle(SystemCheckerService $systemChecker): int
    {
        $this->info('🔍 Auditing System Environment & Permissions...');
        $audit = $systemChecker->audit();

        $this->table(['Metric', 'Value'], [
            ['PHP Version', $audit['php_version']],
            ['OS', $audit['os']],
            ['Device', $audit['is_raspberry_pi'] ? 'Raspberry Pi' : 'PC / Server'],
            ['Disk Free Space', $audit['disk_free_space']],
        ]);

        $this->newLine();
        $this->info('PHP Extensions:');
        $extRows = [];
        foreach ($audit['extensions'] as $ext => $loaded) {
            $extRows[] = [$ext, $loaded ? '✓ Installed' : '❌ Missing'];
        }
        $this->table(['Extension', 'Status'], $extRows);

        $this->newLine();
        $this->info('Directory Permissions:');
        $permRows = [];
        foreach ($audit['permissions'] as $path => $status) {
            $permRows[] = [$path, $status['perms'], $status['is_writable'] ? '✓ Writable' : '❌ Not Writable'];
        }
        $this->table(['Directory', 'Mode', 'Writable'], $permRows);

        if ($this->option('fix') || (! $audit['all_permissions_ok'] && $this->confirm('Fix directory permissions now?'))) {
            $systemChecker->fixPermissions();
            $this->info('✓ Directory permissions set to 0775.');
        }

        return self::SUCCESS;
    }
}
