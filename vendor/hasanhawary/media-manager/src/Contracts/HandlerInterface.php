<?php

namespace HasanHawary\MediaManager\Contracts;

interface HandlerInterface
{
    public function store(string $path, array $options): ?string;
}
