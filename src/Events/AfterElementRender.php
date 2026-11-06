<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AfterElementRender
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public array $element,
        public array $settings,
        public string &$html,
        public array $context = [],
    ) {}
}
