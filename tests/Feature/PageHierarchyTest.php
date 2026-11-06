<?php

declare(strict_types=1);

use Filament\Forms\Components\CodeEditor;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;

it('resolves theme and parent hierarchy with cycle protection', function (): void {
    $news = Page::create(['title' => 'News', 'slug' => 'news', 'meta' => ['theme' => 'Tailnews']]);
    $category = Page::create(['title' => 'Category', 'slug' => 'category', 'meta' => ['theme' => 'Tailnews', 'parent_slug' => 'news']]);
    $post = Page::create(['title' => 'Post', 'slug' => 'post', 'meta' => ['theme' => 'Tailnews', 'parent_slug' => 'category']]);
    $fullwidth = Page::create(['title' => 'Fullwidth', 'slug' => 'post-fullwidth', 'meta' => ['theme' => 'Tailnews', 'parent_slug' => 'post']]);

    expect($news->hierarchyDepth())->toBe(0)
        ->and($category->hierarchyDepth())->toBe(1)
        ->and($post->hierarchyDepth())->toBe(2)
        ->and($fullwidth->hierarchyDepth())->toBe(3)
        ->and($fullwidth->themeName())->toBe('Tailnews')
        ->and($fullwidth->parentSlug())->toBe('post');

    $news->update(['meta' => ['theme' => 'Tailnews', 'parent_slug' => 'post-fullwidth']]);
    expect($news->fresh()->hierarchyDepth())->toBeLessThanOrEqual(10);
});

it('places the draft JSON editor in the Advanced tab', function (): void {
    $tabs = PageResource::form(Schema::make())->getComponents()[0];
    expect($tabs)->toBeInstanceOf(Tabs::class);

    $tabLabels = collect($tabs->getDefaultChildComponents())->map(fn ($tab): string => $tab->getLabel());
    expect($tabLabels)->toContain('General', 'SEO & Meta', 'Advanced');

    $advancedTab = collect($tabs->getDefaultChildComponents())
        ->first(fn ($tab): bool => $tab->getLabel() === 'Advanced');
    $advancedComponents = $advancedTab->getDefaultChildComponents();
    $jsonEditor = collect($advancedComponents[0]->getDefaultChildComponents())
        ->first(fn ($component): bool => $component instanceof CodeEditor && $component->getName() === 'draft_content_json');

    expect($jsonEditor)->not->toBeNull();
});
