<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ModelGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $this->writeFromBase(
            'model',
            app_path('Models/' . $studly . '.php'),
            [
                '{{ model }}' => $studly,
                '{{ table }}' => $table,
                '{{ namespace }}' => 'App\\Models',
                '{{ class }}' => $studly,
            ],
            $force,
            'Model',
            $created,
            $callbacks
        );
    }
}
