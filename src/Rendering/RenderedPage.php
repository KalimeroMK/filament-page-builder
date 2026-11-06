<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Rendering;

use Illuminate\Contracts\Support\Htmlable;
use Stringable;

class RenderedPage implements Htmlable, Stringable
{
    public function __construct(
        public readonly string $html,
        public readonly string $css,
    ) {}

    public function toHtml(): string
    {
        $styleTag = $this->css !== ''
            ? "<style class=\"fpb-generated-styles\">\n{$this->css}\n</style>\n"
            : '';

        return $styleTag . '<div class="fpb-canvas-root">' . $this->html . '</div>';
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }
}
