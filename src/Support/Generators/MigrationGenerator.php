<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;

class MigrationGenerator extends AbstractStubGenerator
{
    protected array $uses = [];

    /**
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $table = $params['table'];
        $path = config('dynamic-dynamic.path.migration');
        $namespace = config('dynamic-dynamic.namespaces.migration');
        $timestamp = now()->format('Y_m_d_His');
        $filename = "{$timestamp}_create_{$params['table']}_table.php";
        $targetPath = "$path/$filename";

        $schema = $this->buildSchema($params);

        $this->writeFromBase(
            'migration',
            $targetPath,
            [
                '{{table}}'     => $table,
                '{{schema}}'    => $schema,
                '{{namespace}}' => $namespace,
                '{{uses}}'      => $this->resolveUses(),
            ],
            $force,
            $created,
            $callbacks
        );
    }

    /**
     * Build table schema from the provided metadata.
     */
    protected function buildSchema(array $params): string
    {
        $lines = [];
        foreach ($params['schema'] as $column => $meta) {
            // skip timestamps or handled columns
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $type = $this->resolveType($meta['data_type'] ?? 'string');
            $line = "\$table->{$type}('{$column}')";

            // handle nullable
            if (!empty($meta['is_nullable'])) {
                $line .= "->nullable()";
            }

            // handle unique
            if (!empty($meta['is_unique'])) {
                $line .= "->unique()";
            }

            // handle default
            if (!empty($meta['has_default']) && !empty($meta['default_value'])) {
                $default = var_export($meta['default_value'], true);
                $line .= "->default($default)";
            }

            // handle relation (foreign key)
            if (!empty($meta['is_relation']) && !empty($meta['relation']['table'])) {
                $relatedTable = $meta['relation']['table'];
                $line = "\$table->foreignId('{$column}')->constrained('{$relatedTable}')";
            }

            $line .= ';';
            $lines[] = '            ' . $line;
        }

        return implode("\n", $lines);
    }

    protected function resolveType(string $type): string
    {
        return match ($type) {
            'id', 'bigint', 'bigIncrements' => 'bigIncrements',
            'integer', 'int' => 'integer',
            'tinyint', 'smallint' => 'smallInteger',
            'boolean', 'bool' => 'boolean',
            'text', 'longtext', 'mediumtext' => 'text',
            'float', 'double', 'decimal' => 'decimal',
            'date' => 'date',
            'datetime', 'timestamp' => 'timestamp',
            default => 'string',
        };
    }

    public function resolveUses(): string
    {
        $uniqueUses = array_filter(array_unique($this->uses));
        return collect($uniqueUses)
                ->map(fn($use) => "use {$use};")
                ->implode("\n") . "\n";
    }
}
