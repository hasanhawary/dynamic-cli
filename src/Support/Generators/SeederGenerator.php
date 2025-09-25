<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SeederGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     */
    public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $this->writeFromBase(
            'seeder',
            base_path('database/seeders/' . $studly . 'Seeder.php'),
            [
                '{{ model }}' => $studly,
                '{{ table }}' => $table,
                '{{ namespace }}' => 'Database\\Seeders',
                '{{ class }}' => $studly . 'Seeder',
            ],
            $force,
            'Seeder',
            $created,
            $callbacks
        );
    }
}
