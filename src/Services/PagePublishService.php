<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Events\PageDraftSaved;
use KalimeroMK\FilamentPageBuilder\Events\PagePublished;
use KalimeroMK\FilamentPageBuilder\Events\PagePublishing;
use KalimeroMK\FilamentPageBuilder\Events\PageSavingDraft;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageRevision;

class PagePublishService
{
    public function __construct(
        protected PageCacheService $cache,
    ) {}

    /**
     * Save editor changes only to draft_content without modifying the live published content.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>|null  $meta
     */
    public function saveDraft(Page $page, array $schema, ?array $meta = null): Page
    {
        $normalized = Page::normalizeSchema($schema);

        event(new PageSavingDraft($page, $normalized));

        $page->draft_content = $normalized;

        if ($meta !== null) {
            $page->meta = array_merge($page->meta ?? [], $meta);
        }

        if ($page->status === null) {
            $page->status = PageStatus::Draft;
        }

        $page->save();

        event(new PageDraftSaved($page));

        return $page;
    }

    /**
     * Publish the current draft_content into live content, record a revision, and invalidate cache.
     */
    public function publish(Page $page, ?string $summary = null, ?int $userId = null): Page
    {
        $schemaToPublish = $page->getWorkingSchema();

        event(new PagePublishing($page, $schemaToPublish));

        DB::transaction(function () use ($page, $schemaToPublish, $summary, $userId): void {
            // Lock the page row to prevent concurrent revision number collisions
            $page->newQuery()->whereKey($page->getKey())->lockForUpdate()->first();

            $page->draft_content = $schemaToPublish;
            $page->content = $schemaToPublish;
            $page->status = PageStatus::Published;
            $page->published_at = Carbon::now();
            $page->save();

            $nextVersion = ((int) $page->revisions()->max('version_number')) + 1;

            $page->revisions()->create([
                'version_number' => $nextVersion,
                'type' => 'publish',
                'summary' => $summary ?? "Published version #{$nextVersion}",
                'schema_snapshot' => $schemaToPublish,
                'meta_snapshot' => $page->meta,
                'created_by' => $userId ?? (Auth::check() ? (int) Auth::id() : null),
                'created_at' => Carbon::now(),
            ]);
        });

        $this->cache->forget($page);

        event(new PagePublished($page));

        return $page->refresh();
    }

    /**
     * Reset draft_content back to the currently published content.
     */
    public function discardDraft(Page $page): Page
    {
        $page->draft_content = $page->content ?? Page::emptySchema();
        $page->save();

        return $page;
    }

    /**
     * Unpublish a page back to Draft status and clear its public cache.
     */
    public function unpublish(Page $page): Page
    {
        $page->status = PageStatus::Draft;
        $page->save();

        $this->cache->forget($page);

        return $page;
    }

    /**
     * Restore a historical revision into draft_content so the user can preview and publish it.
     */
    public function restoreRevision(Page $page, PageRevision $revision): Page
    {
        if ((int) $revision->page_id !== (int) $page->getKey()) {
            throw new InvalidArgumentException('The given revision does not belong to this page.');
        }

        $page->draft_content = Page::normalizeSchema($revision->schema_snapshot);

        if ($revision->meta_snapshot !== null) {
            $page->meta = $revision->meta_snapshot;
        }

        $page->save();

        return $page;
    }
}
