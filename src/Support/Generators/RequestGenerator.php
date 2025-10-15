<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RequestGenerator extends AbstractStubGenerator
{
    protected array $uses = [
        'Illuminate\\Validation\\Rule',
        'Illuminate\\Foundation\\Http\\FormRequest',
        'App\\Rules\\TranslatableRequired',
        'App\\Rules\\TranslatableNullable',
    ];

    /**
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $path = config('dynamic-cli.path.request');
        $namespace = config('dynamic-cli.namespaces.request');
        $targetPath = "$path/{$params['studly']}Request.php";

        $validation = $this->resolveValidation($params);

        $this->writeFromBase(
            'request',
            $targetPath,
            [
                '{{class}}'      => "{$params['studly']}Request",
                '{{namespace}}'  => $namespace,
                '{{rules}}'      => $validation,
                '{{uses}}'       => $this->resolveUses(),
            ],
            $force,
            $created,
            $callbacks
        );
    }

    /**
     * Build validation array.
     */
    public function resolveValidation(array $params): string
    {
        $schema = $params['schema'] ?? [];
        $table = $params['table'] ?? '';

        $lines = collect($schema)->map(function ($meta, $column) use ($params, $table, $isUpdate) {
            $rules = [];

            // handle translatable fields first
            if (!empty($meta['is_translatable'])) {
                $rules[] = !empty($meta['is_nullable'])
                    ? "new TranslatableNullable('{$column}', ['string', 'max:191'], '{$column}')"
                    : "new TranslatableRequired('{$column}', ['string', 'max:191'], '{$column}')";

                // translatable is always array-based
                $rules = array_merge(
                    [!empty($meta['is_nullable']) ? 'nullable' : 'required', 'array'],
                    $rules
                );
                return "            '$column' => [" . implode(', ', $rules) . "],";
            }

            // standard field validation
            $rules[] = !empty($meta['is_nullable']) ? 'nullable' : 'required';

            // file fields
            if (!empty($meta['is_file'])) {
                $rules[] = 'file';
                if (!empty($meta['file_types'])) {
                    $rules[] = "'mimes:" . implode(',', (array) $meta['file_types']) . "'";
                }
            }
            // relations
            elseif (!empty($meta['is_relation'])) {
                $relatedTable = $meta['relation']['table'] ?? Str::plural(Str::snake($meta['relation']['model'] ?? ''));
                $rules[] = "'exists:$relatedTable,id'";
            }
            // enums
            elseif (!empty($meta['is_enum'])) {
                $values = implode(',', $meta['enum_values'] ?? []);
                $rules[] = "'in:$values'";
            }
            // normal types
            else {
                $rules[] = "'" . $this->resolveType($meta['data_type'] ?? 'string') . "'";
            }

            // handle unique rule (create vs update)
            if (!empty($meta['is_unique'])) {
                $rule = "Rule::unique('$table', '$column')->ignore(\$this->route('" . Str::singular($table) . "'))";
                $rules[] = $rule;
            }

            return "            '$column' => [" . implode(', ', $rules) . "],";
        })->implode("\n");

        return "[\n$lines\n        ];";
    }

    protected function resolveType(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'tinyint', 'smallint' => 'integer',
            'boolean', 'bool' => 'boolean',
            'float', 'double', 'decimal' => 'numeric',
            'date', 'datetime', 'timestamp' => 'date',
            default => 'string',
        };
    }

    public function resolveUses(): string
    {
        $uniqueUses = array_filter(array_unique($this->uses));
        return collect($uniqueUses)->map(fn($use) => "use {$use};")->implode("\n") . "\n";
    }
}
