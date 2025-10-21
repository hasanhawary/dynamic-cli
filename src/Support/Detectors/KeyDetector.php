<?php

namespace HasanHawary\DynamicCli\Support\Detectors;

class KeyDetector implements IDetector
{
    /**
     * Detect type based on the key name.
     *
     * @param $column
     * @param array $meta
     * @return string|null
     */
    public static function resolve($column, &$meta): ?string
    {
        // Define grouped regex patterns
        $patterns = [
            'foreignId' => '/_id$/',
            'datetime' => '/(_at|_on)$/',
            'float' => '/(price|amount|total|rate|score|percent|salary|cost)/',
            'integer' => '/(count|qty|quantity|number|age|rank|year|size|limit|level)/',
            'boolean' => '/^(is_|has_|can_|should_|was_|were_)/',
            'text' => '/(description|details|content|body|notes|comment|bio|message|text)/',
            'string' => '/(status|type|category|stage|role|title|name|slug|email|phone|username|tag)/',
        ];

        // File-related keys and common file extensions
        $fileKeywords = '/(image|photo|logo|avatar|file|document|attachment|media|video|audio|sound)/';
        $fileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf', 'mp3', 'mp4', 'wav', 'webm', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

        // 1️File detection based on key naming
        if (preg_match($fileKeywords, $column)) {
            $meta['is_file'] = true;
            $meta['file_types'] = $fileExtensions;
            return 'file';
        }

        // If key looks like "something_mp4" or "avatar_pdf"
        if (preg_match('/_([a-z0-9]+)$/', $column, $matches)) {
            $ext = $matches[1];
            if (in_array($ext, $fileExtensions, true)) {
                $meta['is_file'] = true;
                $meta['file_types'] = [$ext];
                return 'file';
            }
        }

        // Loop through other patterns
        foreach ($patterns as $type => $regex) {
            if (preg_match($regex, $column)) {
                return $type;
            }
        }

        return null;
    }
}
