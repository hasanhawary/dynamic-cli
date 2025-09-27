<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

class ControllerGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $controllerPath = config('dynamic-cli.path.controller')."/$group";
        $namespace = config('dynamic-cli.namespaces.controller')."\\$group";
        $targetPath = "$controllerPath/{$studly}Controller.php";

        $this->writeFromBase(
            'controller',
            $targetPath,
            [
                '{{model}}' => $studly,
                '{{modelSnake}}' => Str::snake($studly),
                '{{table}}' => $table,
                '{{group}}' => $group,
                '{{namespace}}' => $namespace,
                '{{class}}' => "{$studly}Controller",
            ],
            $force,
            'Controller',
            $created,
            $callbacks
        );
    }
}
