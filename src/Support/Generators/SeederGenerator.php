<?php

namespace HasanHawary\DynamicCli\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class SeederGenerator extends AbstractStubGenerator
{
    protected array $uses = [
        Seeder::class,
        Faker::class,
    ];

    /**
     * @throws FileNotFoundException
     */
    public function generate(array $params, bool $force, array &$created, array $callbacks): void
    {
        $path = config('dynamic-dynamic.path.seeder');
        $namespace = config('dynamic-dynamic.namespaces.seeder');
        $targetPath = "{$path}/{$params['studly']}Seeder.php";

        $table = $params['table'] ?? Str::plural(Str::snake($params['studly']));
        $model = $params['model'] ?? Str::studly(Str::singular($table));
        $modelNamespace = "\App\\Models\\{$model}";

        $content = $this->buildSeederContent($table, $modelNamespace, $params['schema'] ?? []);

        $this->writeFromBase(
            'seeder',
            $targetPath,
            [
                '{{class}}' => "{$params['studly']}Seeder",
                '{{namespace}}' => $namespace,
                '{{uses}}' => $this->resolveUses(),
                '{{content}}' => $content,
            ],
            $force,
            $created,
            $callbacks
        );
    }

    protected function buildSeederContent(string $table, string $model, array $schema): string
    {
        $fakerAssignments = collect($schema)->map(function ($meta, $column) {
            $faker = '$faker';
            $type = $meta['data_type'] ?? 'string';

            // Map DB type → Faker generator
            return match ($type) {
                'integer', 'bigint' => "'{$column}' => {$faker}->numberBetween(1, 1000)",
                'boolean', 'tinyint' => "'{$column}' => {$faker}->boolean()",
                'float', 'decimal', 'double' => "'{$column}' => {$faker}->randomFloat(2, 10, 9999)",
                'date', 'datetime', 'timestamp' => "'{$column}' => {$faker}->dateTimeThisYear()",
                'text' => "'{$column}' => {$faker}->paragraph()",
                default => "'{$column}' => {$faker}->word()",
            };
        })->implode(",\n            ");

        return <<<PHP
                \$faker = Faker::create();

                for (\$i = 0; \$i < 20; \$i++) {
                    {$model}::create([
                        {$fakerAssignments}
                    ]);
                }
        PHP;
    }

    public function resolveUses(): string
    {
        $uniqueUses = array_filter(array_unique($this->uses));
        return collect($uniqueUses)->map(fn($use) => "use {$use};")->implode("\n") . "\n";
    }
}
