<?php

namespace Illuminate\Console\View\Components\Mutators;

class EnsureDynamicContentIsHighlighted
{
    /**
     * Highlight dynamic-cli content within the given string.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        return preg_replace('/\[([^\]]+)\]/', '<options=bold>[$1]</>', (string) $string);
    }
}
