<?php

namespace HasanHawary\MediaManager;

abstract class BaseHandler
{
    protected function options(?string $visibility): array
    {
        return $visibility ? ['visibility' => $visibility] : [];
    }

    protected function path(string $base): string
    {
        return trim($base, '/');
    }
}
