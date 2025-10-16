<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use Illuminate\Support\Facades\Storage;

class LocalPathHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $sourcePath, protected bool $isCopy = true)
    {
    }

    public function store(string $path, array $options): ?string
    {
        if (!is_file($this->sourcePath)) {
            return null;
        }

        $ext = pathinfo($this->sourcePath, PATHINFO_EXTENSION) ?: ($options['fallbackExtension'] ?? 'bin');
        $filename = $this->filename($options['namingMode'], $options['customName'], $ext);

        $stream = fopen($this->sourcePath, 'rb');
        if ($stream === false) {
            return null;
        }

        $stored = Storage::disk($options['disk'])
            ->put(
                $this->path($path) . '/' . $filename,
                $stream,
                $this->options($options['visibility'])
            );

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($stored && !$this->isCopy) {
            @unlink($this->sourcePath);
        }

        return $stored ?: null;
    }

    private function filename($namingMode, $customName, string $extension): string
    {
        return FileNameGenerator::generate($extension, $namingMode, $customName);
    }
}
