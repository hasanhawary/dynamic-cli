<?php

namespace HasanHawary\DynamicCli\Support\Detectors;

interface IDetector
{
    public static function resolve($column, &$meta): ?string;

}
