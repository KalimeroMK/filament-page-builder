<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
class Category extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.categories', 'fpb_categories');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        /** @var class-string<Post> $postModel */
        $postModel = config('filament-page-builder.models.post', Post::class);

        return $this->hasMany($postModel, 'category_id');
    }
}
