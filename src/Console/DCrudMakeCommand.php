<?php

namespace HasanHawary\DynamicCli\Console;

use HasanHawary\DynamicCli\Support\CrudGenerator;
use HasanHawary\DynamicCli\Support\Detectors\KeyDetector;
use HasanHawary\DynamicCli\Support\Detectors\ValueDetector;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use JsonException;

class DCrudMakeCommand extends Command
{
    protected $signature = 'd:crud {name?} {--force}';
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
        $route = $this->choice('Which route file to register in?', ['api', 'web'], 0);

        // JSON schema or default
        $customSchema = $this->confirm('Do you have a custom JSON schema?', false);
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
            $frontPath= $this->askValidPath('Please specify the absolute path to your frontend project ? (e.g., C:\\projects\\frontend)');
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
     */
    protected function loadCustomSchema(): ?array
    {
        $this->info('Opening temporary file... Write your JSON schema and save/close.');

        $tmpFile = storage_path('tmp_schema.json');
        file_put_contents($tmpFile, json_encode($this->defaultSchema(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        // Open editor
        $this->openEditor($tmpFile);

        // Read file
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
            $this->line(json_encode($this->defaultSchema(), JSON_PRETTY_PRINT));
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
     * Return default schema and show it.
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
                'is_translatable' => false,
                'is_file' => false,
                'file_types' => null,
                'is_relation' => false,
                'relation' => null,
                'is_nullable' => false,
                'is_unique' => false,
                'has_default' => false,
                'default_value' => null,
                'is_enum' => false,
                'is_column' => true,
                'enum_values' => [],
                'comment' => '',
            ];

            $keyLower = strtolower($key);
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

            // File detection
            if (preg_match('/(image|photo|logo|avatar|file|document|attachment|media)/i', $keyLower)) {
                $meta['is_file'] = true;
                $meta['file_types'] = ['jpg,png,jpeg,pdf'];
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

            $normalized[$key] = $meta;
        }

        $this->newLine();
        $this->info('📋 Final Schema Mapping:');
        foreach ($normalized as $key => $meta) {
            $flags = [];
            if ($meta['is_translatable']) $flags[] = '🌍 translatable';
            if ($meta['is_relation']) $flags[] = '🔗 relation(' . $meta['relation']['model'] . ')';
            if ($meta['is_file']) $flags[] = '🖼️ file';

            $this->line(sprintf(
                " - %-20s → %-10s (%s)",
                $key,
                $meta['data_type'],
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
            'name' => [
                'ar' => 'اسم تجريبي',
                'en' => 'Sample Name',
            ],
            'description' => [
                'ar' => 'وصف تجريبي',
                'en' => 'Sample Description',
            ],
            'photo' => 'file',
            'country_id' => 1,
        ];
    }

    /**
     * Print success message.
     */
    protected function displayCompletion(array $params, array $created): void
    {
        $this->newLine();
        $this->comment('Next steps:');
        $this->line(' - php artisan migrate');
        $this->line(' - php artisan db:seed --class=' . $params['studly'] . 'Seeder');
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
}
