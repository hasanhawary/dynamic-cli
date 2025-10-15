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
        $this->newLine();

        // Fancy ASCII banner
        $this->line('');
        $this->line('==============================================');
        $this->line('');
        $this->line('   🧠  <fg=bright-green;options=bold>Dynamic CLI CRUD Generator</>');
        $this->line('        <fg=gray>Build smart CRUDs in seconds</>');
        $this->line('      <fg=bright-blue>⚡ Powered by <options=bold>Hassan Elhawary</></>');
        $this->line('');
        $this->line('==============================================');
        $this->line('');

        // Welcome message
        $this->info('👋 Welcome to the Dynamic CRUD Generator!');
        $this->newLine();

        // Ask for name
        $name = $this->argument('name') ?? $this->ask('What is the base name for your CRUD? (e.g., Product)');
        if (empty(trim($name))) {
            $this->error('Name is required.');
            return self::INVALID;
        }

        // Ask for group name
        $group = $this->ask('Enter group name (default: DataEntry)', 'DataEntry');
        $group = Str::studly($group);

        // Ask for table name
        $table = $this->ask('Custom table name? (press Enter for default)', Str::plural(Str::snake($name)));

        // Route type
        $route = $this->choice('Which route file to register in?', ['api', 'web'], 0);

        // Schema mode
        $customSchema = $this->confirm('Do you have a custom JSON schema?', false);

        if ($customSchema) {
            $this->info('Opening temporary file... Write your JSON schema and save/close.');
            $tmpFile = storage_path('tmp_schema.json');
            file_put_contents(
                $tmpFile,
                json_encode($this->defaultSchema(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
            );

            // Open editor
            if (PHP_OS_FAMILY === 'Darwin') { // macOS
                exec("open -a TextEdit {$tmpFile}");
            } elseif (PHP_OS_FAMILY === 'Windows') {
                exec("notepad {$tmpFile}");
            } else {
                exec("nano {$tmpFile}");
            }

            // Read and clean content
            $jsonContent = file_get_contents($tmpFile);
            $jsonContent = preg_replace('/\\\\[ntr]/', '', trim($jsonContent));
            $jsonContent = trim($jsonContent, "\"' \t\n\r");

            // Try decoding safely
            try {
                $decoded = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->newLine();
                $this->error('❌ Invalid JSON format in your schema file.');
                $this->warn('Here’s what we found:');
                $this->line(substr($jsonContent, 0, 300) . (strlen($jsonContent) > 300 ? '...' : ''));
                $this->newLine();
                $this->comment('💡 Tip: Make sure the file contains valid JSON like:');
                $this->line(json_encode($this->defaultSchema(), JSON_PRETTY_PRINT));
                $this->newLine();
                return self::INVALID;
            }

            if (!is_array($decoded)) {
                $this->error('❌ Schema must be a JSON object, not text or list.');
                return self::INVALID;
            }

            $schema = $decoded;
        } else {
            $this->newLine();
            $this->warn('💡 Using default schema:');
            foreach ($this->defaultSchema() as $key => $type) {
                $this->line(" - {$key}: " . (is_array($type) ? 'array' : $type));
            }
            $this->newLine();
            $schema = $this->defaultSchema();
        }

        $this->newLine();
        $this->info('🧩 Detected Schema Fields:');
        foreach ($schema as $key => $type) {
            if (is_array($type)) {
                if ($this->isTranslatableField($type)) {
                    $this->line(" - {$key} => translatable field 🌍");
                } else {
                    $this->line(" - {$key} => array field 🧱");
                }
            } else {
                $this->line(" - {$key} : {$type}");
            }

            if (Str::endsWith($key, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($key, '_id'));
                $this->warn("   ↪ Foreign key detected: {$relatedModel} model");
            }
        }

        // Normalize and enrich schema
        $this->newLine();
        $this->info('🧠 Analyzing and enriching schema details...');

        $normalizedSchema = [];
        foreach ($schema as $key => $value) {
            $meta = [
                'data_type' => 'string',
                'is_translatable' => false,
                'is_file' => false,
                'file_types' => ['jpg,png,jpeg,png'],
                'is_relation' => false,
                'relation' => null,
                'is_nullable' => true,
                'is_unique' => false,
                'has_default' => false,
                'default_value' => null,
                'is_enum' => false,
                'is_column' => true,
                'enum_values' => [],
                'comment' => '',
            ];

            $keyLower = strtolower($key);
            $detectedType = ValueDetector::resolve($value, $meta) ?? KeyDetector::resolve($keyLower, $meta);

            if (!$detectedType) {
                $this->newLine();
                $this->warn("⚠️  Couldn’t detect type for '{$key}' (value: " . json_encode($value, JSON_THROW_ON_ERROR) . ")");
                $detectedType = $this->anticipate(
                    "Please specify data type for '{$key}'",
                    ['string', 'text', 'integer', 'float', 'boolean', 'date', 'datetime', 'foreignId', 'json', 'array', 'file'],
                    'string'
                );
            }

            $meta['data_type'] = $detectedType;

            if ($detectedType === 'foreignId' || Str::endsWith($keyLower, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($keyLower, '_id'));
                $meta['is_relation'] = true;
                $meta['relation'] = [
                    'model' => $relatedModel,
                    'table' => Str::plural(Str::snake($relatedModel)),
                    'type' => 'belongsTo',
                    'key' => $keyLower,
                ];
            }

            if (preg_match('/(image|photo|logo|avatar|file|document|attachment|media)/i', $keyLower)) {
                $meta['is_file'] = true;
            }

            if (
                !$meta['is_translatable']
                && preg_match('/(title|name|description|label|text)/', $keyLower)
                && $this->confirm("Is '{$key}' translatable?", false)
            ) {
                $meta['is_translatable'] = true;
                $meta['data_type'] = 'json';
            }

            $normalizedSchema[$key] = $meta;
        }

        $this->newLine();
        $this->info('📋 Final Schema Mapping:');
        foreach ($normalizedSchema as $key => $meta) {
            $flags = [];
            if ($meta['is_translatable']) $flags[] = '🌍 translatable';
            if ($meta['is_relation']) $flags[] = '🔗 relation(' . $meta['relation']['model'] . ')';
            if ($meta['is_file']) $flags[] = '🖼️ file';

            $this->line(sprintf(
                " - %-20s → %-10s (%s)",
                $key,
                $meta['data_type'],
                $flags ? ' [' . implode(', ', $flags) . ']' : ''
            ));
        }

        $schema = $normalizedSchema;

        if (!$this->confirm('Do you want to continue and generate CRUD files?', true)) {
            $this->warn('Generation aborted.');
            return self::INVALID;
        }

        $this->newLine();
        $this->info('⚙️ Generating files...');

        $params = [
            'name' => $name,
            'studly' => Str::studly($name),
            'group' => $group,
            'table' => $table,
            'route' => $route,
            'schema' => $schema,
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

        $this->newLine();
        $this->info('✅ CRUD generation complete. Files created:');
        foreach ($created as $item) {
            $this->line(' - ' . $item);
        }

        $this->newLine();
        $this->comment('Next steps:');
        $this->line(' - php artisan migrate');
        $this->line(" - php artisan db:seed --class=" . Str::studly($name) . "Seeder");

        return self::SUCCESS;
    }

    /**
     * Default schema data (editable in one place).
     */
    protected function defaultSchema(): array
    {
        return [
            "name" => [
                "ar" => "اسم تجريبي",
                "en" => "Sample Name"
            ],
            "description" => [
                "ar" => "وصف تجريبي",
                "en" => "Sample Description"
            ],
            "photo" => "file",
            "country_id" => 1
        ];
    }

    /**
     * Detect if a field is translatable (contains language keys like ar/en).
     */
    protected function isTranslatableField(array $value): bool
    {
        $langKeys = ['ar', 'en', 'fr', 'de', 'es', 'it', 'tr', 'ru', 'zh', 'jp'];
        $keys = array_keys($value);
        foreach ($keys as $k) {
            if (in_array(strtolower($k), $langKeys, true)) {
                return true;
            }
        }
        return false;
    }
}
