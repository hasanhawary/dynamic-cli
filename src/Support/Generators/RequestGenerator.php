<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class RequestGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $this->writeFromBase(
            'request',
            app_path('Http/Requests/' . $studly . 'Request.php'),
            [
                '{{ model }}' => $studly,
                '{{ table }}' => $table,
                '{{ namespace }}' => 'App\\Http\\Requests',
                '{{ class }}' => $studly . 'Request',
            ],
            $force,
            'Request',
            $created,
            $callbacks
        );
    }
}
