<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadedFileHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected UploadedFile $file)
    {
    }

    public function store(string $path, array $options): ?string
    {
        $stored = Storage::disk($options['disk'])
            ->putFileAs(
                $this->path($path),
                $this->file,
                $this->filename($options['namingMode'], $options['customName']),
                $this->options($options['visibility'])
            );

        return $stored ?: null;
    }

    public function filename($namingMode, $customName): string
    {
        return FileNameGenerator::generate($this->file, $namingMode, $customName);
    }
}
