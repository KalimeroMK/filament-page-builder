<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class PageSavingDraft
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $schema
     */
    public function __construct(
        public Page $page,
        public array &$schema,
    ) {}
}
