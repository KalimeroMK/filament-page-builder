<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use KalimeroMK\FilamentPageBuilder\Models\Category;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\Post;

class StarterContentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [];

        foreach ([
            'news' => ['News', 'Release notes and product updates.'],
            'tutorial' => ['Tutorials', 'Practical guides for building with Laravel and Filament.'],
            'ecommerce' => ['Ecommerce', 'Product pages and conversion-focused layouts.'],
        ] as $slug => [$name, $description]) {
            $categories[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description],
            );
        }

        $posts = [
            ['Launching Filament Page Builder v1.3', 'launch-v13', 'news', 'Full Site Kit, Loop Carousel, Maintenance mode and Finder (Ctrl+K) are now available in the core package.', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85'],
            ['Build a SaaS landing page in 5 minutes', 'saas-landing-5min', 'tutorial', 'A step-by-step guide to building a polished SaaS landing page with Flexbox containers, buttons, icons and pricing tables.', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=85'],
            ['Dynamic Tags vs Loop Builder', 'dynamic-vs-loop', 'tutorial', 'When to bind a single field with a dynamic tag and when to render a full Eloquent collection with Loop Builder.', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=85'],
            ['Designing an e-commerce product page', 'ecom-product-design', 'ecommerce', 'Product gallery, pricing card, star rating, accordion specs and related products — the complete conversion-focused layout.', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1200&q=85'],
            ['Form actions that convert visitors', 'form-actions-convert', 'ecommerce', 'Autoresponder emails, webhooks and Turnstile anti-spam: the exact Form Builder actions that make your contact forms work harder.', 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=85'],
            ['Why Draft → Preview → Publish matters', 'draft-preview-publish', 'news', 'How the two-column draft/live schema model keeps your production pages safe while editors experiment.', 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=1200&q=85'],
            ['Loop filters and page numbers are ready', 'loop-filters-page-numbers', 'news', 'Build searchable news archives and product catalogs with validated AJAX filters, stable ordering and numbered pagination.', 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=1200&q=85'],
            ['Coming Soon pages without custom code', 'coming-soon-pages', 'news', 'Choose a designed page template, configure the retry interval and keep admin and signed previews accessible during maintenance.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=85'],
            ['November showcase: news, shop and galleries', 'november-showcase', 'news', 'Explore dedicated demo layouts with parallax backgrounds, dynamic post loops, product galleries and responsive sections.', 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1200&q=85'],
        ];

        foreach ($posts as $index => [$title, $slug, $category, $excerpt, $image]) {
            Post::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'category_id' => $categories[$category]->id,
                'excerpt' => $excerpt,
                'body' => '<p>'.$excerpt.'</p>',
                'image' => $image,
                'author' => 'KalimeroMK',
                'status' => 'published',
                'published_at' => now()->subDays($index + 1),
            ]);
        }

        $this->seedNewsPage();
    }

    private function seedNewsPage(): void
    {
        $itemSchema = [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'starter_card',
                    'type' => 'container',
                    'settings' => [
                        'direction' => 'column',
                        'gap' => 10,
                        'padding' => 18,
                        'background_color' => '#f8fafc',
                        'border_radius' => 10,
                        'admin_label' => 'Article card',
                    ],
                    'children' => [
                        ['id' => 'starter_image', 'type' => 'image', 'settings' => ['src' => '', 'alt' => '', 'height' => 180, 'object_fit' => 'cover', 'border_radius' => 8, 'admin_label' => 'Article image'], 'dynamic' => ['src' => 'model.attribute:image_url', 'alt' => 'model.attribute:title']],
                        ['id' => 'starter_category', 'type' => 'text', 'settings' => ['content' => '', 'color' => '#dc2626', 'font_size' => 12, 'admin_label' => 'Article category'], 'dynamic' => ['content' => 'model.attribute:category.name']],
                        ['id' => 'starter_title', 'type' => 'heading', 'settings' => ['text' => '', 'tag' => 'h3', 'font_size' => 21, 'admin_label' => 'Article title'], 'dynamic' => ['text' => 'model.attribute:title']],
                        ['id' => 'starter_excerpt', 'type' => 'text', 'settings' => ['content' => '', 'font_size' => 14, 'color' => '#475569', 'admin_label' => 'Article excerpt'], 'dynamic' => ['content' => 'model.attribute:excerpt']],
                    ],
                ],
            ],
        ];

        $page = Page::firstOrCreate(['slug' => 'news'], [
            'title' => 'News',
            'layout' => 'default',
            'meta' => [
                'meta_title' => 'Latest News',
                'meta_description' => 'Latest articles from your Filament Page Builder website.',
            ],
            'draft_content' => [
                'version' => 1,
                'elements' => [
                    [
                        'id' => 'starter_hero',
                        'type' => 'container',
                        'settings' => ['direction' => 'column', 'gap' => 12, 'padding' => 40, 'background_color' => '#f8fafc', 'border_radius' => 10, 'margin_bottom' => 28, 'admin_label' => 'News introduction'],
                        'children' => [
                            ['id' => 'starter_heading', 'type' => 'heading', 'settings' => ['text' => 'Latest news', 'tag' => 'h1', 'font_size' => 40, 'admin_label' => 'Page title']],
                            ['id' => 'starter_text', 'type' => 'text', 'settings' => ['content' => '<p>Fresh articles from your new Posts library. Edit or delete this starter page in Visual Builder.</p>', 'font_size' => 17, 'admin_label' => 'Page introduction']],
                        ],
                    ],
                    [
                        'id' => 'starter_news_loop',
                        'type' => 'loop',
                        'settings' => [
                            'model_class' => Post::class,
                            'limit' => 9,
                            'page_size' => 6,
                            'order_by' => 'published_at',
                            'order_direction' => 'desc',
                            'scope' => 'published',
                            'layout' => 'grid',
                            'columns' => 3,
                            'pagination' => 'page_numbers',
                            'item_schema_json' => json_encode($itemSchema, JSON_UNESCAPED_UNICODE),
                            'admin_label' => 'Published posts',
                        ],
                    ],
                ],
            ],
        ]);

        if ($page->wasRecentlyCreated) {
            $page->publish();
        }
    }
}
