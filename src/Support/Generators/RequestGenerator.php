<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use App\Rules\TranslatableNullable;
use App\Rules\TranslatableRequired;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RequestGenerator extends AbstractStubGenerator
{
    protected array $uses = [
        Rule::class,
        FormRequest::class,
        TranslatableRequired::class,
        TranslatableNullable::class,
    ];

    /**
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $path = config('dynamic-dynamic.path.request') . "/{$params['group']}";
        $namespace = config('dynamic-dynamic.namespaces.request') . "\\{$params['group']}";
        $targetPath = "$path/{$params['studly']}Request.php";

        $validation = $this->resolveValidation($params);

        $this->writeFromBase(
            'request',
            $targetPath,
            [
                '{{class}}' => "{$params['studly']}Request",
                '{{namespace}}' => $namespace,
                '{{rules}}' => $validation,
                '{{uses}}' => $this->resolveUses(),
            ],
            $force,
            $created,
            $callbacks
        );
    }

    /**
     * Build a validation array.
     */
    public function resolveValidation(array $params): string
    {
        $schema = $params['schema'] ?? [];
        $table = $params['table'] ?? '';

        $lines = collect($schema)->map(function ($meta, $column) use ($table) {
            $rules = [];

            // standard field validation
            if (!empty($meta['is_nullable']) && empty($meta['is_translatable'])) {
                $rules[] = "'nullable'";
            }

            if (empty($meta['is_nullable']) && empty($meta['is_translatable'])) {
                $rules[] = "'required'";
            }

            // handle translatable fields
            if (!empty($meta['is_translatable'])) {
                $rules[] = !empty($meta['is_nullable'])
                    ? "new TranslatableNullable('{$column}', ['string'], '{$column}')"
                    : "new TranslatableRequired('{$column}', ['string'], '{$column}')";

                $rules = array_merge(
                    [!empty($meta['is_nullable']) ? "'nullable'" : "'required'", "'array'"],
                    $rules
                );

                return "            '$column' => [" . implode(', ', $rules) . "],";
            }

            // file fields
            if (!empty($meta['is_file'])) {
                $rules[] = "'file'";
                if (!empty($meta['file_types'])) {
                    $rules[] = "'mimes:" . implode(',', (array)$meta['file_types']) . "'";
                }
            } // relations
            elseif (!empty($meta['is_relation'])) {
                $relatedTable = $meta['relation']['table'] ?? Str::plural(Str::snake($meta['relation']['model'] ?? ''));
                $rules[] = "'exists:$relatedTable,id'";
            } // enums
            elseif (!empty($meta['is_enum'])) {
                $values = implode(',', $meta['enum_values'] ?? []);
                $rules[] = "'in:$values'";
            } // normal types
            else {
                $rules[] = "'" . $this->resolveType($meta['data_type'] ?? 'string') . "'";
            }

            // unique rule
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
