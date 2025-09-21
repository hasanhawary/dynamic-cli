<?php

namespace HasanHawary\DynamicCli\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DCrudMakeCommand extends Command
{
    protected $signature = 'd:crud {name : The base name of the CRUD (e.g., DataEntry)}'
        . ' {--table= : Custom table name. Defaults to snake plural of name}'
        . ' {--route=api : Route file to register in (api or web)}'
        . ' {--force : Overwrite existing files}';

    protected $description = 'Generate a minimal CRUD (model, controller, request, resource, enum, seeder, migration) and append a resource route.';

    public function handle(Filesystem $files): int
    {
        $name = trim($this->argument('name'));
        if ($name === '') {
            $this->error('Name is required.');
            return self::INVALID;
        }

        $studly = Str::studly($name);
        $snake = Str::snake($studly);
        $table = $this->option('table') ?: Str::plural($snake);
        $force = (bool)$this->option('force');
        $routeTarget = strtolower((string)$this->option('route')) === 'web' ? 'web' : 'api';

        $this->line("Generating CRUD for <info>$studly</info> (table: <comment>$table</comment>)...");

        // Resolve stub resolver (prefer published in stubs/dynamic-cli)
        $stubResolver = function (string $key): ?string {
            $published = base_path('stubs/dynamic-cli/' . $key . '.stub');
            if (file_exists($published)) {
                return $published;
            }
            $fromConfig = config('dynamic-cli.stubs.' . $key);
            return is_string($fromConfig) && file_exists($fromConfig) ? $fromConfig : null;
        };

        // Prepare replacements shared across files
        $replacements = [
            '{{ model }}' => $studly,
            '{{ table }}' => $table,
        ];

        $created = [];

        // 1) Model
        $this->generateFromStub(
            $files,
            $stubResolver('model'),
            app_path('Models/' . $studly . '.php'),
            array_merge($replacements, [
                '{{ namespace }}' => 'App\\Models',
                '{{ class }}' => $studly,
            ]),
            $force,
            $created,
            'Model'
        );

        // 2) Controller
        $this->generateFromStub(
            $files,
            $stubResolver('controller'),
            app_path('Http/Controllers/' . $studly . 'Controller.php'),
            array_merge($replacements, [
                '{{ namespace }}' => 'App\\Http\\Controllers',
                '{{ class }}' => $studly . 'Controller',
            ]),
            $force,
            $created,
            'Controller'
        );

        // 3) Request
        $this->generateFromStub(
            $files,
            $stubResolver('request'),
            app_path('Http/Requests/' . $studly . 'Request.php'),
            array_merge($replacements, [
                '{{ namespace }}' => 'App\\Http\\Requests',
                '{{ class }}' => $studly . 'Request',
            ]),
            $force,
            $created,
            'Request'
        );

        // 4) Resource
        $this->generateFromStub(
            $files,
            $stubResolver('resource'),
            app_path('Http/Resources/' . $studly . 'Resource.php'),
            array_merge($replacements, [
                '{{ namespace }}' => 'App\\Http\\Resources',
                '{{ class }}' => $studly . 'Resource',
            ]),
            $force,
            $created,
            'Resource'
        );

        // 5) Enum (Status enum as default)
        $this->generateFromStub(
            $files,
            $stubResolver('enum'),
            app_path('Enums/' . $studly . 'Status.php'),
            array_merge($replacements, [
                '{{ namespace }}' => 'App\\Enums',
                '{{ class }}' => $studly . 'Status',
            ]),
            $force,
            $created,
            'Enum'
        );

        // 6) Seeder
        $this->generateFromStub(
            $files,
            $stubResolver('seeder'),
            base_path('database/seeders/' . $studly . 'Seeder.php'),
            array_merge($replacements, [
                '{{ namespace }}' => 'Database\\Seeders',
                '{{ class }}' => $studly . 'Seeder',
            ]),
            $force,
            $created,
            'Seeder'
        );

        // 7) Migration (timestamped)
        $migrationStub = $stubResolver('migration');
        $timestamp = date('Y_m_d_His');
        $migrationPath = base_path('database/migrations/' . $timestamp . '_create_' . $table . '_table.php');
        $this->generateFromStub(
            $files,
            $migrationStub,
            $migrationPath,
            $replacements,
            $force,
            $created,
            'Migration'
        );

        // 8) Route registration (resource route)
        $routeFile = base_path('routes/' . $routeTarget . '.php');
        $uri = Str::kebab(Str::pluralStudly($studly));
        $controllerFqn = 'App\\Http\\Controllers\\' . $studly . 'Controller';
        $routeLine = $routeTarget === 'api'
            ? "Route::apiResource('$uri', \\$controllerFqn::class);"
            : "Route::resource('$uri', \\$controllerFqn::class);";

        if (file_exists($routeFile)) {
            $content = file_get_contents($routeFile);
            if (strpos($content, $controllerFqn) === false) {
                $append = "\n// Generated by dynamic-cli on " . date('Y-m-d H:i:s') . "\n" . $routeLine . "\n";
                file_put_contents($routeFile, $content . $append);
                $created[] = 'Route: ' . $routeTarget;
                $this->info("Appended route to routes/$routeTarget.php -> $uri");
            } else {
                $this->line("- Skipped route (already contains $controllerFqn)");
            }
        } else {
            $this->warn("routes/$routeTarget.php not found. Skipped route registration.");
        }

        // Summary
        $this->newLine();
        $this->info('CRUD generation complete. Created:');
        foreach ($created as $item) {
            $this->line(' - ' . $item);
        }
        $this->newLine();
        $this->comment('Next steps:');
        $this->line(' - php artisan migrate');
        $this->line(" - php artisan db:seed --class={$studly}Seeder");

        return self::SUCCESS;
    }

    private function generateFromStub(
        Filesystem $files,
        ?string $stubPath,
        string $targetPath,
        array $replacements,
        bool $force,
        array &$created,
        string $label
    ): void {
        if (!$stubPath || !file_exists($stubPath)) {
            $this->warn("- Missing $label stub. Skipped ($targetPath)");
            return;
        }

        if ($files->exists($targetPath) && !$force) {
            $this->line("- Skipped $label (exists): $targetPath");
            return;
        }

        // Ensure directory exists
        $dir = dirname($targetPath);
        if (!$files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }

        $content = $files->get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        $files->put($targetPath, $content);
        $created[] = $label . ': ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath);
        $this->line("- Created $label -> " . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetPath));
    }
}
