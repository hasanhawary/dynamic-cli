<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ControllerGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $controllerPath = config('dynamic-cli.namespaces.controller');
        $this->writeFromBase(
            'controller',
            "$controllerPath/$group/{$studly}Controller.php",
            [
                '{{ model }}' => $studly,
                '{{ modelSnake }}' => $studly,
                '{{ table }}' => $table,
                '{{ group }}' => $group,
                '{{ namespace }}' => 'App\\Http\\Controllers',
                '{{ class }}' => $studly . 'Controller',
            ],
            $force,
            'Controller',
            $created,
            $callbacks
        );
    }
}
