<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResourceGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $this->writeFromBase(
            'resource',
            app_path('Http/Resources/' . $studly . 'Resource.php'),
            [
                '{{ model }}' => $studly,
                '{{ table }}' => $table,
                '{{ namespace }}' => 'App\\Http\\Resources',
                '{{ class }}' => $studly . 'Resource',
            ],
            $force,
            'Resource',
            $created,
            $callbacks
        );
    }
}
