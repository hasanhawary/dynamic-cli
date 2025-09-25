<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class EnumGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $this->writeFromBase(
            'enum',
            app_path('Enums/' . $studly . 'Status.php'),
            [
                '{{ model }}' => $studly,
                '{{ table }}' => $table,
                '{{ namespace }}' => 'App\\Enums',
                '{{ class }}' => $studly . 'Status',
            ],
            $force,
            'Enum',
            $created,
            $callbacks
        );
    }
}
