<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use HasanHawary\MediaManager\Support\UrlResolver;
use Illuminate\Support\Facades\Storage;

class UrlHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $url, protected bool $storedLocal = false)
    {
    }

    public function store(string $path, array $options): ?string
    {
        $resolver = new UrlResolver($this->url, $options['disk']);

        if (!$this->storedLocal) {
            return $resolver->isValid($this->url) ? $this->url : null;
        }

        if (!$resolver->isValid($this->url)) {
            return null;
        }

        $content = @file_get_contents($this->url);
        if ($content === false) {
            return null;
        }

        $ext = pathinfo(parse_url($this->url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: ($options['fallbackExtension'] ?? 'bin');
        $filename = $this->filename($options['namingMode'], $options['customName'], $ext);

        $stored = Storage::disk($options['disk'])
            ->put(
                $this->path($path) . '/' . $filename,
                $content,
                $this->options($options['visibility'])
            );

        return $stored ?: null;
    }

    public function filename($namingMode, $customName, string $extension): string
    {
        return FileNameGenerator::generate($extension, $namingMode, $customName);
    }
}
