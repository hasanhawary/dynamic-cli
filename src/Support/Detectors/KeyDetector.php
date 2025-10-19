<?php

namespace HasanHawary\DynamicCli\Support\Detectors;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class KeyDetector implements IDetector
{
    /**
     * Detect type based on the key name.
     *
     * @param $key
     * @param array $meta
     * @return string|null
     */
    public static function resolve($key, &$meta): ?string
    {
        $keyLower = self::guessValidation($key, $meta);

        // Define grouped regex patterns
        $patterns = [
            'foreignId' => '/_id$/',
            'datetime' => '/(_at|_on)$/',
            'float' => '/(price|amount|total|rate|score|percent|salary|cost)/',
            'integer' => '/(count|qty|quantity|number|age|rank|year|size|limit|level)/',
            'boolean' => '/^(is_|has_|can_|should_|was_|were_|enable|active|visible|approved|published)/',
            'text' => '/(description|details|content|body|notes|comment|bio|message|text)/',
            'string' => '/(status|type|category|stage|role|title|name|slug|email|phone|username|tag)/',
        ];

        // File-related keys and common file extensions
        $fileKeywords = '/(image|photo|logo|avatar|file|document|attachment|media|video|audio|sound)/';
        $fileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf', 'mp3', 'mp4', 'wav', 'webm', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

        // 1️File detection based on key naming
        if (preg_match($fileKeywords, $keyLower)) {
            $meta['is_file'] = true;
            $meta['file_types'] = $fileExtensions;
            return 'file';
        }

        // If key looks like "something_mp4" or "avatar_pdf"
        if (preg_match('/_([a-z0-9]+)$/', $keyLower, $matches)) {
            $ext = $matches[1];
            if (in_array($ext, $fileExtensions, true)) {
                $meta['is_file'] = true;
                $meta['file_types'] = [$ext];
                return 'file';
            }
        }

        // Loop through other patterns
        foreach ($patterns as $type => $regex) {
            if (preg_match($regex, $keyLower)) {
                return $type;
            }
        }

        return null;
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
