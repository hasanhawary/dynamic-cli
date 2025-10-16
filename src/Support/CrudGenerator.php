<?php

namespace HasanHawary\DynamicCli\Support;

use HasanHawary\DynamicCli\Support\Generators\ControllerGenerator;
use HasanHawary\DynamicCli\Support\Generators\EnumGenerator;
use HasanHawary\DynamicCli\Support\Generators\MigrationGenerator;
use HasanHawary\DynamicCli\Support\Generators\ModelGenerator;
use HasanHawary\DynamicCli\Support\Generators\RequestGenerator;
use HasanHawary\DynamicCli\Support\Generators\ResourceGenerator;
use HasanHawary\DynamicCli\Support\Generators\RouteRegistrar;
use HasanHawary\DynamicCli\Support\Generators\SeederGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CrudGenerator
{
    public function __construct(
        protected Filesystem $files
    )
    {
    }

    /**
     * @param array $params
     * @param bool $force
     * @param callable $line
     * @param callable $info
     * @param callable $warn
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
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
        (new ModelGenerator($this->files))->generate($params, $force, $created, $callbacks);
        (new ControllerGenerator($this->files))->generate($params, $force, $created, $callbacks);
        (new RequestGenerator($this->files))->generate($params, $force, $created, $callbacks);
        (new ResourceGenerator($this->files))->generate($params, $force, $created, $callbacks);
//        (new SeederGenerator($this->files))->generate($params, $force, $created, $callbacks);
        (new MigrationGenerator($this->files))->generate($params, $force, $created, $callbacks);
        (new RouteRegistrar($this->files))->generate($params, $force, $created, $callbacks);

        return $created;
    }
}
