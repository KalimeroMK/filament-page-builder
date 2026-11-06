<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $image
 * @property string|null $image_url
 * @property string|null $author
 * @property string $status
 * @property Carbon|null $published_at
 * @property Category|null $category
 */
class Post extends Model
{
    protected $guarded = ['id'];

    protected $with = ['category'];

    protected $appends = ['image_url'];

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.posts', 'fpb_posts');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        $image = $this->getRawOriginal('image');

        if (! is_string($image) || trim($image) === '') {
            return null;
        }

        $image = trim($image);

        if (str_starts_with($image, '/') || preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        /** @var class-string<Category> $categoryModel */
        $categoryModel = config('filament-page-builder.models.category', Category::class);

        return $this->belongsTo($categoryModel, 'category_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(Carbon::now());
    }
}
