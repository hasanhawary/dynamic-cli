<?php

namespace HasanHawary\DynamicCli\Console;

use HasanHawary\DynamicCli\Support\CrudGenerator;
use HasanHawary\DynamicCli\Support\Detectors\KeyDetector;
use HasanHawary\DynamicCli\Support\Detectors\ValueDetector;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;

class CrudMakeCommand extends Command
{
    protected $signature = 'cli:crud {name?} {--force}';
    protected $description = 'Interactively generate a CRUD (model, controller, request, resource, migration, seeder, etc.) with smart schema detection.';

    /**
     * @throws FileNotFoundException
     * @throws JsonException
     */
    public function handle(): int
    {
        $this->displayBanner();

        // Ask for name
        $name = $this->argument('name') ?? $this->ask('What is the base name for your CRUD? (e.g., Product)');
        if (empty(trim($name))) {
            $this->error('Name is required.');
            return self::INVALID;
        }

        $group = Str::studly($this->ask('Enter group name (default: DataEntry)', 'DataEntry'));
        $table = $this->ask('Custom table name? (press Enter for default)', Str::plural(Str::snake($name)));
        //$route = $this->choice('Which route file to register in?', ['api', 'web'], 0);
        $route = 'api';

        // JSON schema or default
        $customSchema = $this->confirm('Do you have a custom JSON schema?', false);
        if ($customSchema) $this->loadCommentSchema();

        $schema = $customSchema ? $this->loadCustomSchema() : $this->useDefaultSchema();

        if (!$schema) {
            $this->error('Failed to load schema.');
            return self::INVALID;
        }

        // Analyze schema
        $normalizedSchema = $this->analyzeSchema($schema);

        // Ask whether to integrate with a frontend project
        $integrateFront = $this->confirm('Would you like to integrate this module with a frontend project?', false);

        $frontPath = null;
        if ($integrateFront) {
            $frontPath = $this->askValidPath('Please specify the absolute path to your frontend project ? (e.g., C:\\projects\\frontend)');
        }

        // Confirm generation
        if (!$this->confirm('Do you want to continue and generate CRUD files?', true)) {
            $this->warn('Generation aborted.');
            return self::INVALID;
        }

        // Generate files
        $this->newLine();
        $this->info('⚙️ Generating files...');

        $params = [
            'name' => $name,
            'studly' => Str::studly($name),
            'group' => $group,
            'table' => $table,
            'route' => $route,
            'frontPath' => $frontPath,
            'schema' => $normalizedSchema,
        ];

        $files = new Filesystem();
        $generator = new CrudGenerator($files);

        $created = $generator->generateAll(
            $params,
            $this->option('force'),
            line: fn(string $msg) => $this->line($msg),
            info: fn(string $msg) => $this->info($msg),
            warn: fn(string $msg) => $this->warn($msg)
        );

        $this->displayCompletion($params, $created);
        return self::SUCCESS;
    }

    /**
     * Display fancy header
     */
    protected function displayBanner(): void
    {
        $this->newLine(2);
        $this->line('==============================================');
        $this->line('');
        $this->line('   🧠  <fg=bright-green;options=bold>Dynamic CLI CRUD Generator</>');
        $this->line('        <fg=gray>Build smart CRUDs in seconds</>');
        $this->line('      <fg=bright-blue>⚡ Powered by <options=bold>Hassan Elhawary</></>');
        $this->line('');
        $this->line('==============================================');
        $this->newLine();
        $this->info('👋 Welcome to the Dynamic CRUD Generator!');
        $this->newLine();
    }

    /**
     * Load a custom JSON schema file interactively.
     * @throws JsonException
     */
    protected function loadCustomSchema(): ?array
    {
        $this->info('Opening temporary file... Write your JSON schema and save/close.');

        $tmpFile = storage_path('tmp_schema.json');
        file_put_contents($tmpFile, json_encode($this->defaultSchema(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        // Open editor
        $this->openEditor($tmpFile);

        // Read a file
        $jsonContent = file_get_contents($tmpFile);
        if ($jsonContent === false) {
            $this->error('Could not read temporary schema file.');
            return null;
        }

        $jsonContent = preg_replace('/\\\\[ntr]/', '', trim($jsonContent));
        $jsonContent = trim($jsonContent, "\"' \t\n\r");

        try {
            $decoded = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error('❌ Invalid JSON format.');
            $this->warn('Here’s what we found:');
            $this->line(substr($jsonContent, 0, 300) . (strlen($jsonContent) > 300 ? '...' : ''));
            $this->comment('💡 Tip: Use valid JSON like:');
            $this->line(json_encode($this->defaultSchema(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            return null;
        }

        if (!is_array($decoded)) {
            $this->error('Schema must be a JSON object.');
            return null;
        }

        return $decoded;
    }

    /**
     * Open system editor cross-platform.
     */
    protected function openEditor(string $file): void
    {
        $os = PHP_OS_FAMILY;
        if ($os === 'Darwin') {
            exec("open -a TextEdit " . escapeshellarg($file));
        } elseif ($os === 'Windows') {
            exec("start notepad " . escapeshellarg($file));
        } else {
            system("nano " . escapeshellarg($file));
        }
    }

    /**
     * Return the default schema and show it.
     */
    protected function useDefaultSchema(): array
    {
        $this->warn('💡 Using default schema:');
        foreach ($this->defaultSchema() as $key => $type) {
            $this->line(" - {$key}: " . (is_array($type) ? 'array' : $type));
        }
        $this->newLine();
        return $this->defaultSchema();
    }

    /**
     * Analyze schema and enrich it with metadata.
     */
    protected function analyzeSchema(array $schema): array
    {
        $this->info('🧠 Analyzing schema...');

        $normalized = [];
        foreach ($schema as $key => $value) {
            $meta = [
                'data_type' => 'string',
                'is_column' => true,
                'is_translatable' => false,

                'is_file' => false,
                'file_category' => null,
                'file_types' => null,

                'is_relation' => false,
                'relation' => null,

                'is_nullable' => true,
                'is_unique' => false,

                'has_default' => false,
                'default_value' => null,

                'is_enum' => false,
                'enum_values' => [],
            ];

            $keyLower = strtolower($key);
            $keyLower = self::guessValidation($keyLower, $meta);
            $detected = ValueDetector::resolve($value, $meta) ?? KeyDetector::resolve($keyLower, $meta);

            if (!$detected) {
                $this->warn("⚠️ Couldn't detect type for '{$key}'.");
                $detected = $this->anticipate(
                    "Please specify data type for '{$key}'",
                    ['string', 'text', 'integer', 'float', 'boolean', 'date', 'datetime', 'foreignId', 'json', 'array', 'file'],
                    'string'
                );
            }

            $meta['data_type'] = $detected;

            // Relation
            if ($detected === 'foreignId' || Str::endsWith($keyLower, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($keyLower, '_id'));
                $meta['is_relation'] = true;
                $meta['relation'] = [
                    'model' => $relatedModel,
                    'table' => Str::plural(Str::snake($relatedModel)),
                    'type' => 'belongsTo',
                    'key' => $keyLower,
                ];
            }

            // Translatable
            if (
                !$meta['is_translatable']
                && preg_match('/(title|name|description|label|text)/i', $keyLower)
                && $this->confirm("Is '{$key}' translatable?", false)
            ) {
                $meta['is_translatable'] = true;
                $meta['data_type'] = 'json';
            }

            $normalized[$keyLower] = $meta;
        }

        $this->newLine();
        $this->info('📋 Final Schema Mapping:');
        foreach ($normalized as $key => $meta) {
            $flags = [];

            // ---------------- FLAGS & ATTRIBUTES ----------------
            if (!empty($meta['is_translatable'])) {
                $flags[] = '🌍 translatable';
            }

            if (!empty($meta['is_relation'])) {
                $relation = $meta['relation']['model'] ?? 'unknown';
                $flags[] = "🔗 relation($relation)";
            }

            if (!empty($meta['is_file'])) {
                $category = $meta['file_category'] ?? 'file';
                $types = $meta['file_types'] ? implode('|', $meta['file_types']) : '—';
                $flags[] = "🖼️ $category($types)";
            }

            if (!empty($meta['is_enum'])) {
                $values = implode('|', $meta['enum_values'] ?? []);
                $flags[] = "🎯 enum[$values]";
            }

            if (!$meta['is_nullable']) {
                $flags[] = '🚫 not null';
            }

            if (!empty($meta['is_unique'])) {
                $flags[] = '🔑 unique';
            }

            if (!empty($meta['has_default'])) {
                $default = var_export($meta['default_value'], true);
                $flags[] = "⚙️ default($default)";
            }

            // ---------------- OUTPUT LINE ----------------
            $this->line(sprintf(
                " - %-20s → %-10s (%s)",
                $key,
                $meta['data_type'] ?? 'unknown',
                $flags ? implode(', ', $flags) : '—'
            ));
        }

        $this->newLine();
        return $normalized;
    }

    /**
     * Default schema sample.
     */
    protected function defaultSchema(): array
    {
        return [
            '*name' => [
                'ar' => 'اسم تجريبي',
                'en' => 'Sample Name',
            ],
            'description' => [
                'ar' => 'وصف تجريبي',
                'en' => 'Sample Description',
            ],
            '^phone' => "phone",
            'photo' => 'file',
            'status' => "enum[pending,approved,rejected]",
            'country_id' => 1,
        ];
    }

    /**
     * Print success message.
     */
    protected function displayCompletion(): void
    {
        $this->newLine();
        $this->comment('Next steps:');
        $this->line(' - php artisan migrate');
        $this->line(' - Review and customize generated files as needed.');
        $this->line(' - Enjoy your new CRUD! 🚀');
        $this->newLine();
    }

    /**
     * Detect if a field is translatable (contains language keys).
     */
    protected function isTranslatableField(array $value): bool
    {
        $langKeys = ['ar', 'en', 'fr', 'de', 'es', 'it', 'tr', 'ru', 'zh', 'jp'];
        foreach (array_keys($value) as $k) {
            if (in_array(strtolower($k), $langKeys, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Recursively ask for a valid path until the user provides one or skips.
     */
    protected function askValidPath(string $question, string $default = null): ?string
    {
        $path = $this->ask($question, $default);

        if (is_dir($path)) {
            return $path;
        }

        $this->warn("⚠️  The provided path '{$path}' does not exist.");

        if ($this->confirm('Would you like to try again?', true)) {
            return $this->askValidPath($question, $default);
        }

        $this->warn('Frontend integration skipped.');
        return null;
    }

    private function loadCommentSchema(): void
    {
        $this->warn('💡 Schema Reference Guide');
        $this->newLine();

        // Display formatted guide for field modifiers
        $this->info("Symbol-based field modifiers used during meta parsing:");
        $this->line("-------------------------------------------------------------");
        $this->comment("  * => required field (is_nullable = false)");
        $this->comment("  ^ => unique field (is_unique = true)");
//        $this->comment("  ! => field with default value (has_default = true, default_value = value)");
        $this->comment("  enum[...] => enumeration field (is_enum = true, enum_values = [...])");
        $this->newLine();

        $this->info("Examples:");
        $this->line("  '*price'  => 'float'       // required float field");
        $this->line("  '^email'  => 'string'      // unique string field");
//        $this->line("  '!status' => 'active'      // default value = 'active'");
        $this->line("  'state'   => 'enum[draft,published,archived]'");
        $this->line("-------------------------------------------------------------");
        $this->newLine();

        $this->info("Additional Field Guidelines:");
        $this->line("-------------------------------------------------------------");
        $this->comment("  'name' => ['ar' => '...', 'en' => '...']  //Translatable fields => use array with language keys, e.g.:");
        $this->comment("  'photo' => 'file'   //File fields => use 'file' type for uploads, e.g.:");
        $this->comment("  'country_id' => 1  //Foreign key fields => use integer reference, e.g.:");
        $this->line("-------------------------------------------------------------");
        $this->newLine();
    }


    public static function guessValidation($key, &$meta): string
    {
        // Handle combinations like *!value or ^*value etc.
        if (is_string($key)) {
            $key = strtolower(trim($key));

            $symbols = ['*', '^', '!'];
            $cleanValue = $key;

            foreach ($symbols as $symbol) {
                if (Str::startsWith($cleanValue, $symbol)) {
                    $cleanValue = ltrim($cleanValue, $symbol);

                    match ($symbol) {
                        '*' => [
                            $meta['is_nullable'] = false,
                            self::guessValidation($cleanValue, $meta),
                        ],
                        '^' => [
                            $meta['is_unique'] = true,
                            self::guessValidation($cleanValue, $meta),
                        ],
                        '!' => [
                            $meta['has_default'] = true,
                            $meta['default_value'] = self::guessDefaultValue($cleanValue),
                            self::guessValidation($cleanValue, $meta),
                        ],
                        default => null,
                    };
                }
            }


            // replace $value after prefix cleanup
            return $cleanValue;
        }

        return $key;
    }

    private static function guessDefaultValue($value)
    {
        // Enum pattern
        if (self::isEnum($value)) {
            return Arr::first(self::extractEnumValues($value));
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    protected static function isEnum(string $string): bool
    {
        return Str::startsWith($string, 'enum[') && Str::endsWith($string, ']');
    }

    protected static function extractEnumValues(string $string): array
    {
        $inner = Str::between($string, 'enum[', ']');

        if (!$inner) {
            return [];
        }

        return array_map('trim', explode(',', $inner));
    }
}
