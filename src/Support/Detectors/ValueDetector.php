<?php

namespace HasanHawary\DynamicCli\Support\Detectors;

class ValueDetector implements IDetector
{
    /**
     * @param $value
     * @param $meta
     * @return string|null
     */
    public static function resolve($value, &$meta): ?string
    {
        $detectedType = null;

        if (is_string($value)) {

            $lower = strtolower($value);

            // if user provided data type directly
            if (in_array($lower, ['string', 'text', 'int', 'integer', 'float', 'double', 'boolean', 'date', 'datetime', 'timestamp', 'json', 'array', 'file', 'id'])) {
                $detectedType = $lower;
            } else if (is_numeric($value)) {
                $detectedType = (str_contains($value, '.')) ? 'float' : 'integer';
            } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $detectedType = 'string';
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                $detectedType = 'string';
            } elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
                $detectedType = 'date';
            } elseif (preg_match('/(true|false|yes|no)/i', $value)) {
                $detectedType = 'boolean';
            } elseif (preg_match('/[A-Za-z]/', $value)) {
                $detectedType = 'string';
            }

        } elseif (is_array($value)) {
            if (self::isTranslatableField($value)) {
                $detectedType = 'json';
                $meta['is_translatable'] = true;
            } elseif (self::isNumericArray($value)) {
                $detectedType = 'array';
            } elseif (self::isAssocArray($value)) {
                $detectedType = 'json';
            }
        }

        return $detectedType;
    }

    /**
     * Check if an array is numerically indexed (e.g., [1, 2, 3]).
     */
    protected static function isNumericArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Check if an array is associative (e.g., ['ar' => '', 'en' => '']).
     */
    protected static function isAssocArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Detect if the field is a translatable array (has keys like 'ar', 'en').
     *
     * Example:
     * [
     *   "ar" => "مرحبا",
     *   "en" => "Hello"
     * ]
     */
    protected static function isTranslatableField(array $value): bool
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
}
