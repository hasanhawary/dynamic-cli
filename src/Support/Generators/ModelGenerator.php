<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ModelGenerator extends AbstractStubGenerator
{
    /**
     * @param array{line:callable, warn:callable} $callbacks
     * @throws FileNotFoundException
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $modelPath = config('dynamic-cli.path.model') . "/{$params['group']}";
        $namespace = config('dynamic-cli.namespaces.model') . "\\{$params['group']}";
        $targetPath = "$modelPath/{$params['studly']}Controller.php";

        // Resolve Important Variables
        $params['schema'] = "name[ar]:test ar
name[en]:test en
nationality[ar]:nationality ar
nationality[en]:nationality en
code:RTU
phone_code:+966
phone_length:9";

        $columns = $this->resolveColumns($params['schema']);
        $customAttributes = $this->resolveCustomAttribute($columns);
        $columnsTranslatable = $this->resolveTranslatble($columns);
        $relations = $this->resolveRelation($columns);


        $this->writeFromBase(
            'model',
            $targetPath,
            [
                '{{model}}' => $params['studly'],
                '{{columns}}' => array_keys($columns),
                '{{namespace}}' => $namespace,
                '{{customAttributes}}' => $customAttributes,
                '{{columnsTranslatable}}' => $columnsTranslatable,
                '{{relations}}' => $relations,
            ],
            $force,
            $created,
            $callbacks
        );
    }

    public function resolveColumns(array $schema): array
    {

        return [];
    }

    public function resolveCustomAttribute(array $columns): array
    {

        return [];
    }

    public function resolveTranslate(array $columns): array
    {

        return [];
    }

    public function resolveRelation(array $columns): array
    {

        return [];
    }
}
