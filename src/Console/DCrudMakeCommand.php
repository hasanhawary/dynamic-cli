<?php

namespace HasanHawary\DynamicCli\Console;

use HasanHawary\DynamicCli\Support\CrudGenerator;
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

        // 🏗️ Fancy ASCII banner
        $this->line('');
        $this->line('==============================================');
        $this->line('');
        $this->line('   🧠  <fg=bright-green;options=bold>Dynamic CLI CRUD Generator</>');
        $this->line('        <fg=gray>Build smart CRUDs in seconds</>');
        $this->line('      <fg=bright-blue>⚡ Powered by <options=bold>Hassan Elhawary</></>');
        $this->line('');
        $this->line('==============================================');
        $this->line('');

        // 💬 Welcome message
        $this->info('👋 Welcome to the Dynamic CRUD Generator!');
        $this->newLine();

        // 1️⃣ Ask for name
        $name = $this->argument('name') ?? $this->ask('What is the base name for your CRUD? (e.g., Product)');
        if (empty(trim($name))) {
            $this->error('Name is required.');
            return self::INVALID;
        }

        // 2️⃣ Ask for group name
        $group = $this->ask('Enter group name (default: DataEntry)', 'DataEntry');
        $group = Str::studly($group);

        // 3️⃣ Ask for table name
        $table = $this->ask('Custom table name? (press Enter for default)', Str::plural(Str::snake($name)));

        // 4️⃣ Route type
        $route = $this->choice('Which route file to register in?', ['api', 'web'], 0);

        // 5️⃣ Schema mode
        $customSchema = $this->confirm('Do you have a custom JSON schema?', false);

        if ($customSchema) {
            $this->info('Opening temporary file... Write your JSON schema and save/close.');
            $tmpFile = storage_path('tmp_schema.json');
            file_put_contents($tmpFile, json_encode([
                "name" => "string",
                "description" => "string"
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            // Try to open with default editor (Windows: notepad, Linux/Mac: vi/nano)
            if (PHP_OS_FAMILY === 'Windows') {
                exec("notepad {$tmpFile}");
            } else {
                exec("vi {$tmpFile}");
            }

            // 🔍 Read and clean content
            $jsonContent = file_get_contents($tmpFile);

            // Remove BOM and escape sequences like \n or \t that might appear literally
            $jsonContent = preg_replace('/\\\\[ntr]/', '', trim($jsonContent));
            $jsonContent = trim($jsonContent, "\"' \t\n\r");

            // 🧠 Try decoding safely
            try {
                $decoded = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->newLine();
                $this->error('❌ Invalid JSON format in your schema file.');
                $this->warn('Here’s what we found:');
                $this->line(substr($jsonContent, 0, 300) . (strlen($jsonContent) > 300 ? '...' : ''));
                $this->newLine();
                $this->comment('💡 Tip: Make sure the file contains valid JSON like:');
                $this->line('{
                        "name": "string",
                        "description": "string"
                    }');
                $this->newLine();
                return self::INVALID;
            }

            // ✅ Validate structure
            if (!is_array($decoded)) {
                $this->error('❌ Schema must be a JSON object, not text or list.');
                return self::INVALID;
            }

            $schema = $decoded;

        } else {
            $this->newLine();
            $this->warn('💡 Using default schema:');
            $this->line(' - name: string');
            $this->line(' - description: string');
            $this->newLine();
            $schema = [
                "name" => "string",
                "description" => "string",
            ];
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

            // Detect foreign key
            if (Str::endsWith($key, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($key, '_id'));
                $this->warn("   ↪ Foreign key detected: {$relatedModel} model");
            }
        }

        // 5️⃣  Normalize and enrich schema metadata (AI-like)
        $this->newLine();
        $this->info('🧠 Analyzing and enriching schema details...');

        $normalizedSchema = [];
        foreach ($schema as $key => $value) {
            $meta = [
                'data_type' => 'string',
                'input_type' => 'text',
                'is_translatable' => false,
                'is_file' => false,
                'is_relation' => false,
                'relation' => null,
                'is_nullable' => true,
                'is_unique' => false,
                'has_default' => false,
                'default_value' => null,
                'is_enum' => false,
                'enum_values' => [],
                'comment' => '',
            ];

            $keyLower = strtolower($key);

            // 🧩 1️⃣ Detect data type from explicit value or sample text
            $detectedType = null;

            if (is_string($value)) {
                $lower = strtolower($value);
                // if user provided data type directly
                if (in_array($lower, ['string', 'text', 'int', 'integer', 'float', 'double', 'boolean', 'date', 'datetime', 'timestamp', 'json', 'array', 'file', 'id'])) {
                    $detectedType = $lower;
                } else {
                    // try to infer from a sample text
                    if (is_numeric($value)) {
                        $detectedType = (str_contains($value, '.')) ? 'float' : 'integer';
                    } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $detectedType = 'string';
                        $meta['input_type'] = 'email';
                    } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                        $detectedType = 'string';
                        $meta['input_type'] = 'url';
                    } elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
                        $detectedType = 'date';
                    } elseif (preg_match('/(true|false|yes|no)/i', $value)) {
                        $detectedType = 'boolean';
                    } elseif (preg_match('/[A-Za-z]/', $value)) {
                        $detectedType = 'string';
                    }
                }
            } elseif (is_array($value)) {
                if ($this->isTranslatableField($value)) {
                    $detectedType = 'json';
                    $meta['is_translatable'] = true;
                } elseif ($this->isNumericArray($value)) {
                    $detectedType = 'array';
                } elseif ($this->isAssocArray($value)) {
                    $detectedType = 'json';
                }
            }

            // 🧠 If still unknown, try to infer from key name
            if (!$detectedType) {
                if (Str::endsWith($keyLower, '_id')) {
                    $detectedType = 'foreignId';
                } elseif (preg_match('/(_at|_on)$/', $keyLower)) {
                    $detectedType = 'datetime';
                } elseif (preg_match('/(price|amount|total|rate|score|percent)/', $keyLower)) {
                    $detectedType = 'float';
                } elseif (preg_match('/(count|qty|quantity|number|age|rank)/', $keyLower)) {
                    $detectedType = 'integer';
                } elseif (preg_match('/(is_|has_|can_|active|enabled|visible|approved)/', $keyLower)) {
                    $detectedType = 'boolean';
                } elseif (preg_match('/(image|photo|logo|avatar|file|document|attachment|media)/', $keyLower)) {
                    $detectedType = 'file';
                } elseif (preg_match('/(description|details|content|body|notes|comment|bio)/', $keyLower)) {
                    $detectedType = 'text';
                } elseif (preg_match('/(status|type|category|stage|level|role)/', $keyLower)) {
                    $detectedType = 'string';
                }
            }

            // ❓ 3️⃣ If still can’t detect — ask user
            if (!$detectedType) {
                $this->newLine();
                $this->warn("⚠️  Couldn’t detect type for '{$key}' (value: " . json_encode($value) . ")");
                $detectedType = $this->anticipate(
                    "Please specify data type for '{$key}'",
                    ['string', 'text', 'integer', 'float', 'boolean', 'date', 'datetime', 'foreignId', 'json', 'array', 'file'],
                    'string'
                );
            }

            // Assign detected type
            $meta['data_type'] = $detectedType;

            // 🔗 Relations
            if ($detectedType === 'foreignId' || Str::endsWith($keyLower, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($keyLower, '_id'));
                $meta['is_relation'] = true;
                $meta['relation'] = [
                    'model' => $relatedModel,
                    'table' => Str::plural(Str::snake($relatedModel)),
                    'type' => 'belongsTo',
                    'key' => $keyLower,
                ];
                $meta['input_type'] = 'select';
            }

            // 🖼️ File fields
            if (preg_match('/(image|photo|logo|avatar|file|document|attachment|media)/i', $keyLower)) {
                $meta['is_file'] = true;
                $meta['input_type'] = 'file';
            }

            // 🏷️ Boolean
            if ($detectedType == 'boolean') {
                $meta['input_type'] = 'switch';
            }

            // 📅 Dates
            if (in_array($detectedType, ['date', 'datetime', 'timestamp'])) {
                $meta['input_type'] = 'date';
            }

            // 🧾 Textarea
            if ($detectedType == 'text') {
                $meta['input_type'] = 'textarea';
            }

            // 🌍 Translatable field (from pattern)
            if (preg_match('/(title|name|description|label|text)/', $keyLower) && $meta['is_translatable'] === false) {
                if ($this->confirm("Is '{$key}' translatable? (e.g. has 'ar'/'en' values)", false)) {
                    $meta['is_translatable'] = true;
                    $meta['data_type'] = 'json';
                }
            }

            $normalizedSchema[$key] = $meta;
        }

        // 🧾 Show final schema summary
        $this->newLine();
        $this->info('📋 Final Schema Mapping:');
        foreach ($normalizedSchema as $key => $meta) {
            $flags = [];
            if ($meta['is_translatable']) $flags[] = '🌍 translatable';
            if ($meta['is_relation']) $flags[] = '🔗 relation(' . $meta['relation']['model'] . ')';
            if ($meta['is_file']) $flags[] = '🖼️ file';

            $this->line(sprintf(
                " - %-20s → %-10s (%s)%s",
                $key,
                $meta['data_type'],
                $meta['input_type'],
                $flags ? ' [' . implode(', ', $flags) . ']' : ''
            ));
        }

        $this->newLine();
        if (!$this->confirm('✅ Confirm this schema mapping?', true)) {
            $this->warn('Generation aborted.');
            return self::INVALID;
        }

        $schema = $normalizedSchema;


        // Confirm continuing
        $continue = $this->confirm('Do you want to continue and generate CRUD files?', true);
        if (!$continue) {
            $this->warn('Generation aborted.');
            return self::INVALID;
        }

        // 6️⃣ Generate
        $this->newLine();
        $this->info('⚙️ Generating files...');

        $params = [
            'name' => $name,
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
     * Detect if the field is a translatable array (has keys like 'ar', 'en').
     */
    /**
     * Detect if the given field value represents a translatable field.
     *
     * Example:
     * [
     *   "ar" => "مرحبا",
     *   "en" => "Hello"
     * ]
     */
    protected function isTranslatableField(array $value): bool
    {
        // Most common language keys
        $langKeys = ['ar', 'en', 'fr', 'de', 'es', 'it', 'tr', 'ru', 'zh', 'jp'];

        // More general check (in case other language codes are used)
        $keys = array_keys($value);
        foreach ($keys as $k) {
            if (in_array(strtolower($k), $langKeys, true)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Check if array is numerically indexed (e.g., [1, 2, 3]).
     */
    protected function isNumericArray(array $array): bool
    {
        if (empty($array)) return true;
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Check if array is associative (e.g., ['ar' => '', 'en' => '']).
     */
    protected function isAssocArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

}
