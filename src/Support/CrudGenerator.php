<?php

namespace HasanHawary\DynamicCli\Support;

use HasanHawary\DynamicCli\Support\Generators\ControllerGenerator;
use HasanHawary\DynamicCli\Support\Generators\ValueDetector;
use HasanHawary\DynamicCli\Support\Generators\EnumGenerator;
use HasanHawary\DynamicCli\Support\Generators\MigrationGenerator;
use HasanHawary\DynamicCli\Support\Generators\ModelGenerator;
use HasanHawary\DynamicCli\Support\Generators\RequestGenerator;
use HasanHawary\DynamicCli\Support\Generators\ResourceGenerator;
use HasanHawary\DynamicCli\Support\Generators\RouteRegistrar;
use HasanHawary\DynamicCli\Support\Generators\SeederGenerator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class CrudGenerator
{
    public function __construct(
        protected Filesystem $files
    )
    {
    }

    /**
     * @throws FileNotFoundException
     */
    public function generateAll(
        array    $params,
        bool     $force,
        callable $line,
        callable $info,
        callable $warn
    ): array
    {
        $model = Str::studly($params['name']);
        $created = [];

        $line("Generating CRUD for $model,(table: {$params['table']})...");

        $callbacks = [
            'line' => $line,
            'info' => $info,
            'warn' => $warn,
        ];

        // Instantiate generators
        $modelGen = new ModelGenerator($this->files);
        $controllerGen = new ControllerGenerator($this->files);
//        $requestGen = new RequestGenerator($this->files);
//        $resourceGen = new ResourceGenerator($this->files);
//        $enumGen = new EnumGenerator($this->files);
//        $seederGen = new SeederGenerator($this->files);
//        $migrationGen = new MigrationGenerator($this->files);
//        $routeRegistrar = new RouteRegistrar($this->files);

        // Execute generators
        $modelGen->generate($params, $force, $created, $callbacks);
        $controllerGen->generate($params, $force, $created, $callbacks);
//        $requestGen->generate($model, $group, $table, $force, $created, $callbacks);
//        $resourceGen->generate($model, $group, $table, $force, $created, $callbacks);
//        $enumGen->generate($model, $group, $table, $force, $created, $callbacks);
//        $seederGen->generate($model, $group, $table, $force, $created, $callbacks);
//        $migrationGen->generate($model, $group, $table, $force, $created, $callbacks);

        // Route registration
//        $routeRegistrar->register($model, $group, $routeTarget, $created, $callbacks);

        return $created;
    }

    private function resolveStub(string $key): ?string
    {
        $published = base_path('stubs/dynamic-cli/' . $key . '.stub');
        if (file_exists($published)) {
            return $published;
        }
        $fromConfig = config('dynamic-cli.stubs.' . $key);
        return is_string($fromConfig) && file_exists($fromConfig) ? $fromConfig : null;
    }

    private function generateFromStub(
        ?string  $stubPath,
        string   $targetPath,
        array    $replacements,
        bool     $force,
        array    &$created,
        string   $label,
        callable $line,
        callable $warn
    ): void
    {
        if (!$stubPath || !file_exists($stubPath)) {
            $warn("- Missing $label stub. Skipped ($targetPath)");
            return;
        }

        if ($this->files->exists($targetPath) && !$force) {
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
        $line("- Created $label -> " . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath));
    }
}
