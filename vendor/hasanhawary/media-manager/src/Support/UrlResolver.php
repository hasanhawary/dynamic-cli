<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UrlResolver
{
    protected array $paths;
    protected array $prefix = ['http://', 'https://'];

    public function __construct(string|array|null $paths, protected string $disk = 'public')
    {
        $this->paths = array_filter(Arr::wrap($paths), fn($i) => !empty($i));
    }

    public function isValid(?string $path = null): bool
    {
        return $path && Str::startsWith($path, $this->prefix);
    }

    public function url(): string|array|null
    {
        return $this->mapPaths(function ($path) {
            if ($this->isValid($path)) {
                return $path;
            }

            return Storage::disk($this->disk)->exists($path) ? Storage::disk($this->disk)->url($path) : null;
        });
    }

    public function temporaryUrl(int $minutes = 5): string|array|null
    {
        return $this->mapPaths(fn($path) => Storage::disk($this->disk)->exists($path)
            ? Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($minutes))
            : null
        );
    }

    public function signedUrl(\DateTimeInterface $expiresAt): string|array|null
    {
        return $this->mapPaths(fn($path) => Storage::disk($this->disk)->exists($path)
            ? Storage::disk($this->disk)->temporaryUrl($path, $expiresAt)
            : null
        );
    }

    protected function mapPaths(callable $callback): string|array|null
    {
        if (!$this->paths) {
            return null;
        }

        $urls = array_filter(array_map($callback, $this->paths));
        return count($urls) > 1 ? $urls : ($urls[0] ?? null);
    }
}
