<?php

namespace HasanHawary\DynamicCli\Console;

use HasanHawary\DynamicCli\Support\CrudGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DCrudMakeCommand extends Command
{
    protected $signature = 'd:crud {name : The base name of the CRUD (e.g., Product)}'
    . ' {--group=DataEntry : Related group name. Defaults to data-entry'
    . ' {--table=test : Custom table name. Defaults to snake plural of name}'
    . ' {--route=api : Route file to register in (api or web)}'
    . ' {--force : Overwrite existing files}';

    protected $description = 'Generate a minimal CRUD (model, controller, request, resource, enum, seeder, migration) and append a resource route.';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        // Get the required "name" argument and trim spaces
        $name = trim($this->argument('name'));
        if ($name === '') {
            $this->error('Name is required.');
            return self::INVALID;
        }

        // If a group provided, convert to StudlyCase; otherwise default to "DataEntry"
        $group = trim($this->option('group'));
        $group = !empty($group) ? Str::studly($group) : "DataEntry";

        // Resolve table name: use --table option if given, otherwise default to plural snake_case of the name
        $table =Str::plural(Str::snake($name));

        $force = (bool)$this->option('force');
        $routeTarget = strtolower((string)$this->option('route')) === 'web' ? 'web' : 'api';

        $files = new Filesystem();
        $generator = new CrudGenerator($files);

        $created = $generator->generateAll(
            $name,
            $group,
            $table,
            $force,
            $routeTarget,
            line: fn(string $msg) => $this->line($msg),
            info: fn(string $msg) => $this->info($msg),
            warn: fn(string $msg) => $this->warn($msg)
        );

        // Summary
        $this->newLine();
        $studly = Str::studly($name);
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
}
