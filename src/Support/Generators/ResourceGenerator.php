<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

class ResourceGenerator extends AbstractStubGenerator
{
    protected array $uses = [
        BasicUserResource::class,
    ];

    /**
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $modelPath = config('dynamic-cli-dynamic-cli.path.resource') . "/{$params['group']}";
        $namespace = config('dynamic-cli-dynamic-cli.namespaces.resource') . "\\{$params['group']}";
        $targetPath = "$modelPath/{$params['studly']}Resource.php";

        $resourceBody = $this->resolveResource($params, $namespace);

        $this->writeFromBase(
            'resource',
            $targetPath,
            [
                '{{class}}' => "{$params['studly']}Resource",
                '{{namespace}}' => $namespace,
                '{{body}}' => $resourceBody,
                '{{uses}}' => $this->resolveUses(),
            ],
            $force,
            $created,
            $callbacks
        );
    }

    /**
     * Build the resource body (the `toArray` return array).
     */
    public function resolveResource(array $params, string $namespace): string
    {
        $schema = $params['schema'] ?? [];
        $lines[] = "            'id' => \$this->id,";

        foreach ($schema as $column => $meta) {
            // Handle translatable fields
            if (!empty($meta['is_translatable'])) {
                $lines[] = "            '{$column}' => \$this->getTranslations('{$column}'),";
                $lines[] = "            'translation_{$column}' => \$this->{$column},";
                continue;
            }

            // Handle enum fields
            if (!empty($meta['is_enum'])) {
                [$enumClass, $path] = $this->guessEnumClass($column, $params);
                if ($enumClass) {
                    $this->uses[] = $path;
                    $lines[] = "            'display_{$column}' => {$enumClass}::resolve(\$this->{$column}),";
                } else {
                    $lines[] = "            '{$column}' => \$this->{$column},";
                }
                continue;
            }

            // Handle relations
            if (!empty($meta['is_relation'])) {
                $relation = str_replace('_id', '', $column);

                $lines[] = "            '{$column}' => \$this->whenLoaded('{$relation}', fn() => new " . $this->classBaseName() . "(\$this->{$relation}), ['id' => \$this->{$column}]),";
                continue;
            }

            // Default simple field
            $lines[] = "            '{$column}' => \$this->{$column},";
        }

        // Always include basic metadata

        $lines[] = "            'creator' => \$this->whenLoaded('creator', fn() => new BasicUserResource(\$this->creator), ['id' => \$this->created_by]),";
        $lines[] = "            'created_at' => \$this->created_at,";
        $lines[] = "            'updated_at' => \$this->updated_at,";

        return "[\n" . implode("\n", $lines) . "\n        ];";
    }

    protected function guessEnumClass(string $column, array $params): ?array
    {
        $studly = Str::studly($column);
        $namespace = config('dynamic-cli-dynamic-cli.namespaces.enum') . "\\{$params['group']}";
        $name = "{$studly}Enum";
        $class = "$namespace\\{$studly}Enum";

        if (class_exists($class)) {
            return [$name, $class];
        }

        return [null, null];
    }

    protected function classBaseName(): string
    {
        return 'BasicUserResource';
    }

    public function resolveUses(): string
    {
        $uniqueUses = array_filter(array_unique($this->uses));
        return collect($uniqueUses)
                ->map(fn($use) => "use {$use};")
                ->implode("\n") . "\n";
    }
}
