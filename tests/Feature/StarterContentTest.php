<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use KalimeroMK\FilamentPageBuilder\Database\Seeders\StarterContentSeeder;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource;
use KalimeroMK\FilamentPageBuilder\Models\Category;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\Post;
use KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder;

it('seeds starter categories, posts and a working News page exactly once', function (): void {
    $seeder = app(StarterContentSeeder::class);
    $seeder->run();

    expect(Category::query()->count())->toBe(3)
        ->and(Post::query()->count())->toBe(9)
        ->and(Post::query()->published()->count())->toBe(9)
        ->and(Category::query()->where('slug', 'news')->firstOrFail()->posts()->count())->toBe(5);

    $post = Post::query()->with('category')->where('slug', 'launch-v13')->firstOrFail();
    expect($post->category?->name)->toBe('News')
        ->and($post->isPublished())->toBeTrue();

    $records = app(LoopQueryBuilder::class)->query([
        'model_class' => Post::class,
        'limit' => 20,
        'page_size' => 20,
        'scope' => 'published',
        'order_by' => 'published_at',
    ]);
    expect($records)->toHaveCount(9)
        ->and($records->first()->category?->name)->not->toBeEmpty();

    $this->get('/pages/news')
        ->assertOk()
        ->assertSee('Latest news')
        ->assertSee('Launching Filament Page Builder v1.3');

    $seeder->run();
    expect(Category::query()->count())->toBe(3)
        ->and(Post::query()->count())->toBe(9)
        ->and(Page::query()->where('slug', 'news')->count())->toBe(1);
});

it('does not overwrite existing content when starter seeding runs again', function (): void {
    $page = Page::create([
        'title' => 'My Existing News',
        'slug' => 'news',
        'draft_content' => ['version' => 1, 'elements' => []],
    ]);
    $page->publish();

    $category = Category::create(['name' => 'Custom News', 'slug' => 'news']);
    $post = Post::create([
        'title' => 'My Existing Launch Post',
        'slug' => 'launch-v13',
        'category_id' => $category->id,
        'status' => 'draft',
    ]);

    app(StarterContentSeeder::class)->run();

    expect($page->fresh()->title)->toBe('My Existing News')
        ->and($post->fresh()->title)->toBe('My Existing Launch Post')
        ->and($post->fresh()->status)->toBe('draft')
        ->and(Post::query()->count())->toBe(9);
});

it('registers the installer and Post and Category admin resources', function (): void {
    expect(Artisan::all())->toHaveKey('fpb:install')
        ->and(PostResource::getModel())->toBe(Post::class)
        ->and(CategoryResource::getModel())->toBe(Category::class)
        ->and(PostResource::form(Schema::make())->getComponents())->not->toBeEmpty()
        ->and(CategoryResource::form(Schema::make())->getComponents())->not->toBeEmpty();
});

it('uploads featured images to public storage and resolves their dynamic image URL', function (): void {
    \Illuminate\Support\Facades\Storage::fake('public');
    $path = \Illuminate\Http\UploadedFile::fake()
        ->create('featured.jpg', 128, 'image/jpeg')
        ->store('fpb/posts', 'public');

    $post = Post::create([
        'title' => 'Uploaded Featured Image',
        'slug' => 'uploaded-featured-image',
        'image' => $path,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $expectedUrl = '/storage/'.$path;
    expect(\Illuminate\Support\Facades\Storage::disk('public')->exists($path))->toBeTrue()
        ->and($post->image_url)->toBe($expectedUrl)
        ->and($post->toArray()['image_url'])->toBe($expectedUrl);

    $dynamicTag = new \KalimeroMK\FilamentPageBuilder\DynamicTags\ModelAttributeDynamicTag();
    expect($dynamicTag->resolve(['attribute' => 'image_url'], ['record' => $post]))->toBe($expectedUrl);

    $externalPost = Post::create([
        'title' => 'External Featured Image',
        'slug' => 'external-featured-image',
        'image' => 'https://images.example.com/external.jpg',
    ]);
    expect($externalPost->image_url)->toBe('https://images.example.com/external.jpg');

    $upload = collect(PostResource::form(Schema::make())->getComponents())
        ->first(fn ($component): bool => $component instanceof \Filament\Forms\Components\FileUpload && $component->getName() === 'image');

    expect($upload)->not->toBeNull()
        ->and($upload->getDiskName())->toBe('public')
        ->and($upload->getDirectory())->toBe('fpb/posts');
});
