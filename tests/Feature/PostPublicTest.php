<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Database\Seeders\StarterContentSeeder;
use KalimeroMK\FilamentPageBuilder\Models\Post;

it('renders published posts and links them from the News loop', function (): void {
    app(StarterContentSeeder::class)->run();
    $post = Post::query()->where('slug', 'launch-v13')->firstOrFail();

    expect($post->public_url)->toBe(url('/posts/launch-v13'));

    $this->get($post->public_url)
        ->assertOk()
        ->assertSee($post->title)
        ->assertSee($post->category->name)
        ->assertSee($post->excerpt)
        ->assertSee('photo-1516321318423-f06f85e504b3');

    $this->get('/pages/news')
        ->assertOk()
        ->assertSee('href="'.$post->public_url.'"', false);
});

it('returns 404 for draft and future posts', function (): void {
    $draft = Post::create([
        'title' => 'Draft Post',
        'slug' => 'draft-post',
        'status' => 'draft',
        'published_at' => now(),
    ]);
    $future = Post::create([
        'title' => 'Future Post',
        'slug' => 'future-post',
        'status' => 'published',
        'published_at' => now()->addDay(),
    ]);

    $this->get($draft->public_url)->assertNotFound();
    $this->get($future->public_url)->assertNotFound();
});
