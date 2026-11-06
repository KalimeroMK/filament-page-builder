<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $page_id
 * @property int $version_number
 * @property string $type
 * @property string|null $summary
 * @property array<string, mixed> $schema_snapshot
 * @property array<string, mixed>|null $meta_snapshot
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
class PageRevision extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.page_revisions', 'fpb_page_revisions');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'schema_snapshot' => 'array',
            'meta_snapshot' => 'array',
            'created_by' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = config('filament-page-builder.models.page', Page::class);

        return $this->belongsTo($pageModel, 'page_id');
    }
}
