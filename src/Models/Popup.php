<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;

/**
 * @property int $id
 * @property string $title
 * @property PageStatus $status
 * @property array<string, mixed>|null $draft_content
 * @property array<string, mixed>|null $content
 * @property array<string, mixed>|null $trigger_settings
 * @property array<int, array<string, mixed>>|null $conditions
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Popup extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Popup $popup): void {
            if ($popup->status === null) {
                $popup->status = PageStatus::Draft;
            }

            if ($popup->draft_content === null) {
                $popup->draft_content = $popup->content ?? Page::emptySchema();
            }

            if ($popup->trigger_settings === null) {
                $popup->trigger_settings = [
                    'type' => 'on_load',
                    'delay_seconds' => 2,
                    'scroll_percent' => 50,
                    'max_width' => 560,
                    'show_once_per_visitor' => true,
                ];
            }
        });

        static::saving(function (Popup $popup): void {
            if (is_array($popup->draft_content)) {
                $popup->draft_content = Page::normalizeSchema($popup->draft_content);
            }

            if (is_array($popup->content)) {
                $popup->content = Page::normalizeSchema($popup->content);
            }

            if (is_array($popup->conditions)) {
                $popup->conditions = ThemeTemplate::normalizeConditions($popup->conditions);
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.popups', 'fpb_popups');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'draft_content' => 'array',
            'content' => 'array',
            'trigger_settings' => 'array',
            'conditions' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PageStatus::Published->value)
            ->whereNotNull('content')
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', Carbon::now());
            });
    }

    public function publish(): self
    {
        $schema = Page::normalizeSchema($this->draft_content ?? $this->content ?? Page::emptySchema());
        $this->draft_content = $schema;
        $this->content = $schema;
        $this->status = PageStatus::Published;
        $this->published_at = Carbon::now();
        $this->save();

        return $this;
    }

    /**
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    public function getPublishedSchema(): array
    {
        return Page::normalizeSchema($this->content ?? Page::emptySchema());
    }

    /**
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    public function getWorkingSchema(): array
    {
        return Page::normalizeSchema($this->draft_content ?? $this->content ?? Page::emptySchema());
    }
}
