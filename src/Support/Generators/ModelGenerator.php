<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ModelGenerator extends AbstractStubGenerator
{
    /**
     * @var array
     */
    protected array $uses = [];
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $modelPath = config('dynamic-cli.path.model');
        $namespace = config('dynamic-cli.namespaces.model');
        $targetPath = "$modelPath/{$params['studly']}.php";

        $columns = $this->resolveColumns($params['schema']);
        $customAttributes = $this->resolveCustomAttribute($params['schema']);
        $columnsTranslatable = $this->resolveTranslate($params['schema']);
        $relations = $this->resolveRelation($params['schema']);

        $this->writeFromBase(
            'model',
            $targetPath,
            [
                '{{model}}' => $params['studly'],
                '{{columns}}' => $columns,
                '{{namespace}}' => $namespace,
                '{{customAttributes}}' => $customAttributes ?? "",
                '{{columnsTranslatable}}' => $columnsTranslatable,
                '{{relations}}' => $relations,
                '{{uses}}' => $this->resolveUses(),
            ],
            $force,
            $created,
            $callbacks
        );

    }

    public function resolveColumns(array $schema): string
    {
        return collect($schema)->filter(function ($field) {
            return $field['is_column'] ?? false;
           })->keys()->map(function ($key) {
            return "'$key'";
        })->implode(', ');
    }

    public function resolveCustomAttribute(array $schema): string
    {
        return collect($schema)->filter(function ($field) {
            return $field['is_file'] ?? false;
        })->map(function ($column,$key) {
            return $this->generateCustomFileMethod($key);
        })->values()->implode("\n");
    }

    public function resolveTranslate(array $schema): string
    {
        return collect($schema)->filter(function ($field) {
            return $field['is_translatable'] ?? false;
        })->keys()->map(function ($key) {
            return "'$key'";
        })->implode(', ');
    }

    public function resolveRelation(array $schema): string
    {
        return collect($schema)->filter(function ($field) {
            return $field['is_relation'] ?? false;
        })->map(function ($relation) {
            return $this->generateRelationMethod($relation['relation']);
        })->values()->implode('\n');
    }

    private function generateRelationMethod(array $relation): string
    {
        $methodName = lcfirst($relation['model']);
        $relationType = $relation['type'];
        $relatedModel = $relation['model'];
        $relationClass = ucfirst($relationType);
        $this->uses[] = "Illuminate\Database\Eloquent\Relations\\$relationClass";

        return <<<EOT
        public function $methodName(): $relationClass
            {
                return \$this->$relationType($relatedModel::class);
            }
        EOT;
    }

    private function generateCustomFileMethod(string $column): string
    {
        $this->uses[] = 'Illuminate\Database\Eloquent\Casts\Attribute';
        $this->uses[] = 'HasanHawary\MediaManager\Facades\Media';

        return <<<EOT
        public function {$column}(): Attribute
            {
                return new Attribute(
                    set: fn(\$value) => Media::from(\$value)->store(),
                    get: fn(\$value) => Media::url(\$value)
                );
            }
        EOT;
    }

    public function resolveUses(): string
    {
        $uniqueUses = array_filter(array_unique($this->uses));
        $formattedUses = array_map(fn($use) => "use {$use};", $uniqueUses);

        return implode("\n", $formattedUses) . "\n";
    }

}

