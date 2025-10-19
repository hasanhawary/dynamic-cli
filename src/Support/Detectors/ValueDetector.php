<?php

namespace HasanHawary\DynamicCli\Support\Detectors;

use Illuminate\Support\Str;

class ValueDetector implements IDetector
{
    /**
     * Detect the appropriate data type for a given value and enrich the $meta-array.
     *
     * @param mixed $value
     * @param array $meta
     * @return string|null
     */
    public static function resolve($value, &$meta): ?string
    {
        // ---------- STRING DETECTION ----------
        if (is_string($value)) {
            $lower = strtolower($value);

            // Direct type declaration
            if (in_array($lower, [
                'string', 'text', 'int', 'integer', 'float', 'double',
                'boolean', 'date', 'datetime', 'timestamp',
                'id'
            ], true)) {
                return $lower;
            }

            // Enum pattern
            if (self::isEnum($value)) {
                $meta['is_enum'] = true;
                $meta['enum_values'] = self::extractEnumValues($value);
                return 'string';
            }

            // ---------- FILE DETECTION (by key or value) ----------
            if (
                preg_match('/(image|photo|logo|avatar|file|document|attachment|media|pdf|video|audio)/i', $lower) ||
                preg_match('/\.(jpg|jpeg|png|webp|gif|pdf|docx?|txt|mp3|wav|ogg|m4a|mp4|mov|avi|mkv|webm)$/i', $lower)
            ) {
                $meta['is_file'] = true;

                if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $lower) || preg_match('/(image|photo|logo|avatar)/i', $lower)) {
                    $meta['file_category'] = 'image';
                    $meta['file_types'] = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                } elseif (preg_match('/\.(pdf|docx?|txt)$/i', $lower) || preg_match('/(document|pdf|attachment)/i', $lower)) {
                    $meta['file_category'] = 'document';
                    $meta['file_types'] = ['pdf', 'doc', 'docx', 'txt'];
                } elseif (preg_match('/\.(mp3|wav|ogg|m4a)$/i', $lower) || preg_match('/(audio|sound|voice)/i', $lower)) {
                    $meta['file_category'] = 'audio';
                    $meta['file_types'] = ['mp3', 'wav', 'ogg', 'm4a'];
                } elseif (preg_match('/\.(mp4|mov|avi|mkv|webm)$/i', $lower) || preg_match('/(video|clip|media)/i', $lower)) {
                    $meta['file_category'] = 'video';
                    $meta['file_types'] = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
                } else {
                    $meta['file_category'] = 'file';
                    $meta['file_types'] = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                }

                return 'file';
            }

            // ---------- COMMON PATTERNS ----------
            if (is_numeric($value)) {
                return Str::contains($value, '.') ? 'float' : 'integer';
            }

            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'string';
            }
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return 'string';
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return 'date';
            }
            if (preg_match('/(true|false|yes|no)/i', $value)) {
                return 'boolean';
            }

            // Default fallback: string if contains letters or symbols
            if (preg_match('/[A-Za-z]/', $value)) {
                return 'string';
            }
        }

        // ---------- ARRAY DETECTION ----------
        if (is_array($value)) {
            if (self::isTranslatableField($value)) {
                $meta['is_translatable'] = true;
                return 'json';
            }

            if (self::isNumericArray($value)) {
                return 'array';
            }

            if (self::isAssocArray($value)) {
                return 'json';
            }
        }

        return null;
    }

    protected static function isNumericArray(array $array): bool
    {
        return $array === [] || array_keys($array) === range(0, count($array) - 1);
    }

    protected static function isAssocArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
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

    /**
     * Detect if array looks like translatable data (has language keys like ar, en, etc.)
     */
    protected static function isTranslatableField(array $value): bool
    {
        $langKeys = ['ar', 'en', 'fr', 'de', 'es', 'it', 'tr', 'ru', 'zh', 'jp'];
        foreach (array_keys($value) as $key) {
            if (in_array(strtolower($key), $langKeys, true)) {
                return true;
            }
        }
        return false;
    }
}
