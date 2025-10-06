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
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $modelPath = config('dynamic-cli.path.model') . "/{$params['group']}";
        $namespace = config('dynamic-cli.namespaces.model') . "\\{$params['group']}";
        $targetPath = "$modelPath/{$params['studly']}Controller.php";

        $this->writeFromBase(
            'controller',
            $targetPath,
            [
                '{{model}}' => $params['studly'],
                '{{modelSnake}}' => Str::snake($params['studly']),
                '{{table}}' => $params['table'],
                '{{group}}' => $params['table'],
                '{{namespace}}' => $namespace,
                '{{class}}' => "{$params['studly']}Controller",
            ],
            $force,
            $created,
            $callbacks
        );
    }
}
