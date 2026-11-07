<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use KalimeroMK\FilamentPageBuilder\Models\Post;

class PostController extends Controller
{
    public function show(string $slug): View
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();

        $pageModel = config('filament-page-builder.models.page', \KalimeroMK\FilamentPageBuilder\Models\Page::class);
        $page = new $pageModel();
        $page->title = $post->title;
        $page->slug = $post->slug;
        $page->layout = 'default';
        $page->meta = [
            'meta_title' => $post->title,
            'meta_description' => $post->excerpt,
            'og_image' => $post->image_url,
        ];
        $page->exists = true;
        $page->setAttribute($page->getKeyName(), $post->getKey());

        return view('filament-page-builder::post', [
            'post' => $post,
            'page' => $page,
            'isPreview' => false,
        ]);
    }
}
