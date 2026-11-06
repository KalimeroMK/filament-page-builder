<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class PageDraftSaved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Page $page,
    ) {}
}
