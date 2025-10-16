<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use Illuminate\Support\Facades\Storage;

class Base64Handler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $content)
    {
    }

    public function store(string $path, array $options): ?string
    {
        $data = $this->decode($this->content, $mime);
        if ($data === null) {
            return null;
        }

        $extension = $this->determineExtension($mime, $options['fallbackExtension'] ?? 'bin');
        $filename = $this->filename($options['namingMode'], $options['customName'], $extension);

        $stored = Storage::disk($options['disk'])
            ->put(
                $this->path($path) . '/' . $filename,
                $data,
                $this->options($options['visibility'])
            );

        return $stored ?: null;
    }

    private function filename($namingMode, $customName, $extension): string
    {
        return FileNameGenerator::generate($extension, $namingMode, $customName);
    }

    private function decode(string $input, ?string &$mime = null): ?string
    {
        if (preg_match('/^data:([a-z0-9+\-\.\/]+);base64,(.*)$/i', $input, $m)) {
            $mime = $m[1] ?? null;
            $data = base64_decode($m[2] ?? '', true);
            return ($data === false) ? null : $data;
        }

        $data = base64_decode($input, true);
        return ($data === false) ? null : $data;
    }

    protected function determineExtension(?string $mime, ?string $fallback): string
    {
        return FileNameGenerator::determineExtension($mime, $fallback);
    }
}
