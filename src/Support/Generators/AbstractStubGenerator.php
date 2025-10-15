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
        array  &$created,
        array  $callbacks
    ): void
    {
        $line = $callbacks['line'];
        $warn = $callbacks['warn'];

        $stubPath = $this->resolveStub($stubKey);
//        if (!$stubPath || !file_exists($stubPath)) {
//            $warn("- Missing $stubKey stub. Skipped ($targetPath)");
//            return;
//        }
//
//        if (!$force && $this->files->exists($targetPath)) {
//            $line("- Skipped $stubKey (exists): $targetPath");
//            return;
//        }


        $dir = dirname($targetPath);
        if (!$this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        // Get Stub Content
        $content = $this->files->get($stubPath);

        // dd(array_keys($replacements),
        //     array_values($replacements));
        // Replace Dynamic Variables
        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );

        // Write New Fil With Generated Data
        $this->files->put($targetPath, $content);

        $created[] = $stubKey . ': ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath);
        $line('- Created ' . $stubKey . ' -> ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath));
    }
}
