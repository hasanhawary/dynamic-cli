<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaMeta
{
    protected string $path;
    protected string $disk;

    public function __construct(string $path, string $disk)
    {
        $this->path = $path;
        $this->disk = $disk;
    }

    protected function storage()
    {
        return Storage::disk($this->disk);
    }

    protected function exists(): bool
    {
        return $this->storage()->exists($this->path);
    }

    public function path(): ?string
    {
        return $this->exists() ? $this->path : null;
    }

    public function url(): ?string
    {
        return $this->exists() ? $this->storage()->url($this->path) : null;
    }

    public function size(): ?int
    {
        return $this->exists() ? $this->storage()->size($this->path) : null;
    }

    public function mime(): ?string
    {
        return $this->exists() ? $this->storage()->mimeType($this->path) : null;
    }

    public function extension(): ?string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION) ?: null;
    }

    public function basename(): ?string
    {
        return basename($this->path);
    }

    public function filename(): ?string
    {
        $ext = $this->extension();
        $base = $this->basename();

        return $ext ? substr($base, 0, -(strlen($ext) + 1)) : $base;
    }

    public function dirname(): ?string
    {
        $dir = dirname($this->path);
        return ($dir && $dir !== '.') ? $dir : null;
    }

    public function lastModified(): ?int
    {
        return $this->exists() ? $this->storage()->lastModified($this->path) : null;
    }

    public function hash(string $algo = 'md5'): ?string
    {
        if (!$this->exists()) return null;

        try {
            $contents = $this->storage()->get($this->path);
            return hash($algo, $contents);

        } catch (\Throwable $e) {
            Log::error("MediaMeta::hash failed", ['file' => $this->path, 'exception' => $e]);
            return null;
        }
    }

    public function dimensions(): ?array
    {
        if (!$this->exists()) return null;

        try {
            $tmp = $this->storage()->path($this->path);
            [$width, $height] = @getimagesize($tmp) ?: [null, null];
            return ($width && $height) ? ['width' => $width, 'height' => $height] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Return all metadata
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path(),
            'url' => $this->url(),
            'exists' => $this->exists(),
            'size' => $this->size(),
            'mimeType' => $this->mime(),
            'extension' => $this->extension(),
            'basename' => $this->basename(),
            'filename' => $this->filename(),
            'dirname' => $this->dirname(),
            'lastModified' => $this->lastModified(),
            'hash' => $this->hash(),
            'dimensions' => $this->dimensions(),
        ];
    }

    public function __toString(): string
    {
        return $this->url() ?? $this->path ?? '';
    }

    public function __toArray(): array
    {
        return $this->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
