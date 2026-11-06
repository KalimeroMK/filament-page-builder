<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use KalimeroMK\FilamentPageBuilder\Models\Popup;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;

class PopupResolver
{
    public function __construct(
        protected ThemeConditionMatcher $matcher,
    ) {}

    /**
     * Return all published popups whose conditions match the current request and context.
     *
     * @param  array<string, mixed>  $context
     * @return Collection<int, Popup>
     */
    public function activePopups(array $context = [], ?Request $request = null): Collection
    {
        /** @var class-string<Popup> $model */
        $model = config('filament-page-builder.models.popup', Popup::class);

        $popups = $model::query()
            ->select(['id', 'title', 'status', 'content', 'trigger_settings', 'conditions', 'published_at', 'created_at', 'updated_at'])
            ->published()
            ->orderBy('id')
            ->get();

        return $popups->filter(function (Popup $popup) use ($context, $request): bool {
            $probe = new ThemeTemplate();
            $probe->setRawAttributes(['conditions' => $popup->conditions], true);
            $probe->conditions = $popup->conditions;

            return $this->matcher->matches($probe, $context, $request);
        })->values();
    }
}
