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
        $modelPath = config('dynamic-dynamic.path.resource') . "/{$params['group']}";
        $namespace = config('dynamic-dynamic.namespaces.resource') . "\\{$params['group']}";
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
        $lines = [];

        foreach ($schema as $column => $meta) {
            // Handle translatable fields
            if (!empty($meta['is_translatable'])) {
                $lines[] = "            '{$column}' => \$this->getTranslations('{$column}'),";
                $lines[] = "            'translation_{$column}' => \$this->{$column},";
                continue;
            }

            // Handle enum fields
            if (!empty($meta['is_enum'])) {
                $enumClass = $meta['enum_class'] ?? $this->guessEnumClass($column, $namespace);
                if ($enumClass) {
                    $this->uses[] = $enumClass;
                    $lines[] = "            'display_{$column}' => {$this->classBaseName($enumClass)}::resolve(\$this->{$column}),";
                } else {
                    $lines[] = "            '{$column}' => \$this->{$column},";
                }
                continue;
            }

            // 🔗 Handle relations
            if (!empty($meta['is_relation'])) {
                $relation = str_replace('_id', '', $column);

                $lines[] = "            '{$column}' => \$this->whenLoaded('{$relation}', fn() => new " . $this->classBaseName() . "(\$this->{$relation}), ['id' => \$this->{$column}_id]),";
                continue;
            }

            // 🌿 Default simple field
            $lines[] = "            '{$column}' => \$this->{$column},";
        }

        // Always include basic metadata
        $lines[] = "            'id' => \$this->id,";
        $lines[] = "            'created_at' => \$this->created_at,";
        $lines[] = "            'updated_at' => \$this->updated_at,";

        return "[\n" . implode("\n", $lines) . "\n        ];";
    }

    protected function guessEnumClass(string $column, string $namespace): ?string
    {
        $studly = Str::studly($column);
        $class = "$namespace\\{$studly}Enum";
        if (class_exists($class)) {
            return $class;
        }

        return null;
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
