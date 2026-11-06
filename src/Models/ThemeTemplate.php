<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;

/**
 * @property int $id
 * @property string $title
 * @property ThemeRegionType $region
 * @property PageStatus $status
 * @property int $priority
 * @property array<string, mixed>|null $draft_content
 * @property array<string, mixed>|null $content
 * @property array<int, array<string, mixed>>|null $conditions
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ThemeTemplate extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (ThemeTemplate $template): void {
            if ($template->region === null) {
                $template->region = ThemeRegionType::Header;
            }

            if ($template->status === null) {
                $template->status = PageStatus::Draft;
            }

            if ($template->priority === null) {
                $template->priority = 10;
            }

            if ($template->draft_content === null) {
                $template->draft_content = $template->content ?? Page::emptySchema();
            }

            if ($template->conditions === null) {
                $template->conditions = [
                    ['type' => 'include', 'target' => 'entire_site', 'value' => '*'],
                ];
            }
        });

        static::saving(function (ThemeTemplate $template): void {
            if (is_array($template->draft_content)) {
                $template->draft_content = Page::normalizeSchema($template->draft_content);
            }

            if (is_array($template->content)) {
                $template->content = Page::normalizeSchema($template->content);
            }

            if (is_array($template->conditions)) {
                $template->conditions = static::normalizeConditions($template->conditions);
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.theme_templates', 'fpb_theme_templates');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'region' => ThemeRegionType::class,
            'status' => PageStatus::class,
            'priority' => 'integer',
            'draft_content' => 'array',
            'content' => 'array',
            'conditions' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  array<mixed>  $conditions
     * @return array<int, array{type: string, target: string, value: string}>
     */
    public static function normalizeConditions(array $conditions): array
    {
        $normalized = [];
        $allowedTypes = ['include', 'exclude'];
        $allowedTargets = ['entire_site', 'path', 'route', 'page_slug', 'model'];

        foreach ($conditions as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $type = in_array($rule['type'] ?? 'include', $allowedTypes, true) ? (string) $rule['type'] : 'include';
            $target = in_array($rule['target'] ?? 'entire_site', $allowedTargets, true) ? (string) $rule['target'] : 'entire_site';
            $value = is_scalar($rule['value'] ?? null) ? trim((string) $rule['value']) : '';

            if ($target === 'entire_site' && $value === '') {
                $value = '*';
            }

            if ($value === '') {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'target' => $target,
                'value' => $value,
            ];
        }

        return $normalized;
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

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForRegion(Builder $query, string | ThemeRegionType $region): Builder
    {
        $value = $region instanceof ThemeRegionType ? $region->value : $region;

        return $query->where('region', $value);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published
            && $this->content !== null
            && ($this->published_at === null || $this->published_at->lte(Carbon::now()));
    }

    public function hasUnpublishedChanges(): bool
    {
        $draft = $this->getWorkingSchema();

        if ($this->content === null) {
            return ! empty($draft['elements']);
        }

        return $draft !== $this->getPublishedSchema();
    }

    /**
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    public function getWorkingSchema(): array
    {
        return Page::normalizeSchema($this->draft_content ?? $this->content ?? Page::emptySchema());
    }

    /**
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    public function getPublishedSchema(): array
    {
        return Page::normalizeSchema($this->content ?? Page::emptySchema());
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function saveDraft(array $schema): self
    {
        $this->draft_content = Page::normalizeSchema($schema);
        $this->save();

        return $this;
    }

    public function publish(): self
    {
        $schema = $this->getWorkingSchema();
        $this->draft_content = $schema;
        $this->content = $schema;
        $this->status = PageStatus::Published;
        $this->published_at = Carbon::now();
        $this->save();

        return $this;
    }

    public function discardDraft(): self
    {
        $this->draft_content = $this->content ?? Page::emptySchema();
        $this->save();

        return $this;
    }

    public function unpublish(): self
    {
        $this->status = PageStatus::Draft;
        $this->save();

        return $this;
    }
}
