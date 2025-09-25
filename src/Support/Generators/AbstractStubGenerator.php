<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

abstract class AbstractStubGenerator
{
    public function __construct(
        protected Filesystem $files
    )
    {
    }

    protected function resolveStub(string $key): ?string
    {
        $packageBasePath = dirname(__DIR__, 3);
        $published = $packageBasePath . '/stubs/' . $key . '.stub';

        if (file_exists($published)) {
            return $published;
        }

        return null;
    }

    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     */
    protected function writeFromBase(
        string $stubKey,
        string $targetPath,
        array  $replacements,
        bool   $force,
        string $label,
        array  &$created,
        array  $callbacks
    ): void
    {
        $line = $callbacks['line'];
        $warn = $callbacks['warn'];

        $stubPath = $this->resolveStub($stubKey);
        if (!$stubPath || !file_exists($stubPath)) {
            $warn("- Missing $label stub. Skipped ($targetPath)");
            return;
        }

        if (!$force && $this->files->exists($targetPath)) {
            $line("- Skipped $label (exists): $targetPath");
            return;
        }

        $dir = dirname($targetPath);
        if (!$this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        $content = $this->files->get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        $this->files->put($targetPath, $content);
        $created[] = $label . ': ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath);
        $line('- Created ' . $label . ' -> ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath));
    }
}
