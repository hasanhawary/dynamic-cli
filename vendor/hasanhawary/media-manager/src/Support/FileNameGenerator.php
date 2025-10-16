<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileNameGenerator
{
    /**
     * @param UploadedFile|string $file
     * @param string $strategy
     * @param mixed|null $customName
     * @return string
     */
    public static function generate(UploadedFile|string $file, string $strategy = 'original', mixed $customName = null): string
    {
        // Detect extension
        $isUploadFile = $file instanceof UploadedFile;

        $extension = $isUploadFile
            ? $file->extension()
            : ltrim((string)$file, '.');

        return match ($strategy) {
            'hash' => $isUploadFile ? self::hash($file->getRealPath(), $extension) : self::uuid($extension),
            'timestamp' => self::timestamp($extension),
            'original' => $isUploadFile ? $file->getClientOriginalName() : self::uuid($extension),
            'custom' => is_callable($customName) ? call_user_func($customName, $file) : (string)$customName,
            default => self::uuid($extension),
        };
    }

    public static function uuid(string $extension): string
    {
        $ext = ltrim($extension, '.');
        return Str::uuid()->toString() . ($ext ? ".{$ext}" : '');
    }

    public static function hash(string $path, string $extension): string
    {
        $ext = ltrim($extension, '.');
        return md5_file($path) . ($ext ? ".{$ext}" : '');
    }

    public static function timestamp(string $extension): string
    {
        $ext = ltrim($extension, '.');
        return time() . '_' . Str::random(6) . ($ext ? ".{$ext}" : '');
    }

    public static function determineExtension(?string $mime, ?string $fallback): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
        ];
        $mime = $mime ? strtolower($mime) : null;
        return $map[$mime] ?? ($fallback ?: 'bin');
    }
}
