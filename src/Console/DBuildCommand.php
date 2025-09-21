<?php

namespace HasanHawary\DynamicCli\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class DBuildCommand extends Command
{
    protected $signature = 'dbuild {--force : Overwrite existing files}';

    protected $description = 'Build dynamic CLI scaffolding: publish stubs and verify setup.';

    public function handle(Filesystem $files): int
    {
        $this->line('<info>Dynamic CLI</info> :: Starting build...');

        $packageStubs = config('dynamic-cli.stubs', []);
        if (empty($packageStubs)) {
            $this->warn('No stub paths configured. Please check config/dynamic-cli.php.');
        }

        $targetBase = base_path('stubs/dynamic-cli');
        if (!$files->isDirectory($targetBase)) {
            $files->makeDirectory($targetBase, 0755, true);
        }

        $copied = 0;
        foreach ($packageStubs as $name => $path) {
            $target = $targetBase . DIRECTORY_SEPARATOR . $name . '.stub';
            if ($files->exists($target) && !$this->option('force')) {
                $this->line("- Skipped $name (already exists). Use --force to overwrite.");
                continue;
            }

            if ($files->exists($path)) {
                $files->copy($path, $target);
                $this->line("- Published $name stub -> stubs/dynamic-cli/$name.stub");
                $copied++;
            } else {
                $this->warn("- Missing package stub for $name at: $path");
            }
        }

        $this->info("Build complete. {$copied} stub(s) published.");
        $this->comment('You can now customize stubs in stubs/dynamic-cli and start implementing generators.');
        return self::SUCCESS;
    }
}
