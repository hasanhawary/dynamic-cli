<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use Illuminate\Support\Facades\Storage;

class ContentHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $content, protected string $extension = 'png')
    {
    }

    public function store(string $path, array $options): ?string
    {
        $ext = $options['fallbackExtension'] ?? 'bin';
        $filename = $this->filename($options['namingMode'], $options['customName'], $ext);

        $stored = Storage::disk($options['disk'])
            ->put(
                $this->path($path) . '/' . $filename,
                $this->content,
                $this->options($options['visibility'] ?? null)
            );

        return $stored ?: null;
    }

    private function filename($namingMode, $customName, $extension): string
    {
        return FileNameGenerator::generate($extension, $namingMode, $customName);
    }
}
