<?php

namespace HasanHawary\DynamicCli\Support\Detectors;

use Illuminate\Support\Str;

class KeyDetector implements IDetector
{
    /**
     * @param $value
     * @param $meta
     * @return string|null
     */
    public static function resolve($value, &$meta): ?string
    {
        $detectedType = null;
        $key = $value;

        if (Str::endsWith($key, '_id')) {
            $detectedType = 'foreignId';
        } elseif (preg_match('/(_at|_on)$/', $key)) {
            $detectedType = 'datetime';
        } elseif (preg_match('/(price|amount|total|rate|score|percent)/', $key)) {
            $detectedType = 'float';
        } elseif (preg_match('/(count|qty|quantity|number|age|rank)/', $key)) {
            $detectedType = 'integer';
        } elseif (preg_match('/(is_|has_|can_|active|enabled|visible|approved)/', $key)) {
            $detectedType = 'boolean';
        } elseif (preg_match('/(image|photo|logo|avatar|file|document|attachment|media)/', $key)) {
            $detectedType = 'file';
        } elseif (preg_match('/(description|details|content|body|notes|comment|bio)/', $key)) {
            $detectedType = 'text';
        } elseif (preg_match('/(status|type|category|stage|level|role|email|_email|email_)/', $key)) {
            $detectedType = 'string';
        }

        return $detectedType;
    }
}
