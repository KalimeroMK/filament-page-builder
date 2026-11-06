<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Services\PagePublishService;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property PageStatus $status
 * @property string $layout
 * @property array<string, mixed>|null $draft_content
 * @property array<string, mixed>|null $content
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Page extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            if ($page->status === null) {
                $page->status = PageStatus::Draft;
            }

            if ($page->layout === null) {
                $page->layout = 'default';
            }

            if ($page->draft_content === null) {
                $page->draft_content = $page->content ?? static::emptySchema();
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.pages', 'fpb_pages');
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
            'meta' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    public static function emptySchema(): array
    {
        return [
            'version' => 1,
            'elements' => [],
        ];
    }

    /**
     * @return HasMany<PageRevision, $this>
     */
    public function revisions(): HasMany
    {
        /** @var class-string<PageRevision> $revisionModel */
        $revisionModel = config('filament-page-builder.models.page_revision', PageRevision::class);

        return $this->hasMany($revisionModel, 'page_id')->orderByDesc('version_number');
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
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Draft->value);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published && $this->content !== null;
    }

    public function isDraft(): bool
    {
        return $this->status === PageStatus::Draft;
    }

    /**
     * Returns true when draft_content differs from the live published content.
     */
    public function hasUnpublishedChanges(): bool
    {
        $draft = $this->getWorkingSchema();

        if ($this->content === null) {
            return ! empty($draft['elements']);
        }

        return $draft !== $this->getPublishedSchema();
    }

    /**
     * Returns the working draft schema used in the editor and live preview.
     *
     * @return array<string, mixed>
     */
    public function getWorkingSchema(): array
    {
        $schema = $this->draft_content ?? $this->content ?? static::emptySchema();

        return static::normalizeSchema($schema);
    }

    /**
     * Returns the published schema shown to public website visitors.
     *
     * @return array<string, mixed>
     */
    public function getPublishedSchema(): array
    {
        $schema = $this->content ?? static::emptySchema();

        return static::normalizeSchema($schema);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    public static function normalizeSchema(array $schema): array
    {
        if (isset($schema['elements']) && is_array($schema['elements'])) {
            return [
                'version' => (int) ($schema['version'] ?? 1),
                'elements' => array_values($schema['elements']),
            ];
        }

        // Support passing a flat list of root elements directly
        if (array_is_list($schema)) {
            return [
                'version' => 1,
                'elements' => $schema,
            ];
        }

        return static::emptySchema();
    }

    /**
     * Save changes only into draft_content without affecting the live published page.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>|null  $meta
     */
    public function saveDraft(array $schema, ?array $meta = null): self
    {
        return app(PagePublishService::class)->saveDraft($this, $schema, $meta);
    }

    /**
     * Publish the current draft_content into live content and create a revision snapshot.
     */
    public function publish(?string $summary = null, ?int $userId = null): self
    {
        return app(PagePublishService::class)->publish($this, $summary, $userId);
    }

    /**
     * Discard unpublished changes in draft_content by resetting it to the published content.
     */
    public function discardDraft(): self
    {
        return app(PagePublishService::class)->discardDraft($this);
    }

    /**
     * Revert the page status back to Draft and clear its public cache.
     */
    public function unpublish(): self
    {
        return app(PagePublishService::class)->unpublish($this);
    }

    /**
     * Restore a previous revision into draft_content so it can be previewed and published.
     */
    public function restoreRevision(PageRevision $revision): self
    {
        return app(PagePublishService::class)->restoreRevision($this, $revision);
    }

    /**
     * Generate a temporary signed URL to preview draft_content before publishing.
     */
    public function getPreviewUrl(int $ttlMinutes = 60): string
    {
        return URL::temporarySignedRoute(
            'filament-page-builder.preview',
            Carbon::now()->addMinutes($ttlMinutes),
            ['page' => $this->getKey()],
        );
    }

    /**
     * Generate the public URL for the published page.
     */
    public function getPublicUrl(): string
    {
        return route('filament-page-builder.show', ['slug' => $this->slug]);
    }
}
