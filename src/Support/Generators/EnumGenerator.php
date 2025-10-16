<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

class EnumGenerator extends AbstractStubGenerator
{
    protected array $uses = [
        'HasanHawary\LookupManager\Trait\EnumMethods',
    ];

    /**
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $path = config('dynamic-cli.path.enum') . "/{$params['group']}";
        $namespace = config('dynamic-cli.namespaces.enum') . "\\{$params['group']}";
        $targetPath = "$path/{$params['studly']}Enum.php";

        $cases = $this->buildCases($params['enum_values'] ?? []);

        $this->writeFromBase(
            'enum',
            $targetPath,
            [
                '{{namespace}}' => $namespace,
                '{{class}}'     => $params['studly'],
                '{{cases}}'       => $cases,
            ],
            $force,
            $created,
            $callbacks
        );
    }

    /**
     * Build enum cases (StudlyCase name = snake_case value)
     */
    protected function buildCases(array $values): string
    {
        return collect($values)
            ->filter()
            ->map(function ($value) {
                $caseName = Str::studly(Str::snake($value));
                $caseValue = Str::snake($value);
                return "    case {$caseName} = '{$caseValue}';";
            })
            ->implode("\n");
    }
}
