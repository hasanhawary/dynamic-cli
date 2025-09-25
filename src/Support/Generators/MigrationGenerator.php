<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class MigrationGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     */
     public function generate(string $studly, string $group, string $table, bool $force, array &$created, array $callbacks): void
    {
        $timestamp = date('Y_m_d_His');
        $migrationPath = base_path('database/migrations/' . $timestamp . '_create_' . $table . '_table.php');

        $this->writeFromBase(
            'migration',
            $migrationPath,
            [
                '{{ model }}' => $studly,
                '{{ table }}' => $table,
            ],
            $force,
            'Migration',
            $created,
            $callbacks
        );
    }
}
