# Filament Page Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kalimeromk/filament-page-builder.svg?style=flat-square)](https://packagist.org/packages/kalimeromk/filament-page-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/kalimeromk/filament-page-builder.svg?style=flat-square)](https://packagist.org/packages/kalimeromk/filament-page-builder)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE.md)
[![Tests](https://github.com/KalimeroMK/filament-page-builder/actions/workflows/tests.yml/badge.svg)](https://github.com/KalimeroMK/filament-page-builder/actions/workflows/tests.yml)

> **Build pages in Laravel without writing code — an open-source (MIT) Elementor Pro-like visual page builder plugin for Filament v5.**

Give your clients, editors and marketers a **drag-and-drop visual builder** inside your existing Laravel + Filament admin panel. They design pages, headers, footers, popups and forms visually — you keep full control of the code, the data and the rendering.

Built and maintained by [KalimeroMK](https://github.com/KalimeroMK) • **All features are and remain free & open-source (MIT) — no paid version, no locked features.**

---

## Table of Contents

1. [What is this?](#what-is-this)
2. [Who is it for?](#who-is-it-for)
3. [How does it work?](#how-does-it-work)
4. [Feature overview](#feature-overview)
5. [Requirements](#requirements)
6. [Installation (Quick Start)](#installation-quick-start)
7. [Your first page in 2 minutes](#your-first-page-in-2-minutes)
8. [Usage guides](#usage-guides)
   - [Pages & Visual Editor](#pages--visual-editor)
   - [Menu Builder](#menu-builder)
   - [Posts & Categories](#posts--categories)
   - [Theme Builder (Header / Footer / Single / Archive / 404)](#theme-builder)
   - [Dynamic Tags](#dynamic-tags)
   - [Loop Builder](#loop-builder)
   - [Form Builder](#form-builder)
   - [Popup Builder](#popup-builder)
9. [Configuration reference](#configuration-reference)
10. [Add-on API (for developers)](#add-on-api-for-developers)
11. [Security notes](#security-notes)
12. [Testing](#testing)
13. [FAQ](#faq)
14. [Roadmap & monetization](#roadmap--monetization)
15. [Contributing & License](#contributing--license)

---

## What is this?

**Filament Page Builder** is a Composer package that adds a complete **visual page-building system** to any Laravel application that uses [Filament v5](https://filamentphp.com) as its admin panel.

Think of it as **Elementor Pro for Laravel**, but:

- **100% open-source (MIT)** — no license fees, no activation keys, no SaaS dependency.
- **Laravel-native** — pages are Eloquent models, layouts are JSON schemas rendered with Blade, forms submit to Laravel controllers, dynamic content reads your Eloquent models directly.
- **Extensible by design** — every widget, dynamic tag, template and form action is a small PHP class you (or a paid add-on) can register.

You get a **Livewire v4 visual editor** inside Filament where users drag widgets onto a canvas, edit them in a Content / Style / Advanced inspector, preview on Desktop / Tablet / Mobile, and **Save Draft → Preview → Publish** when ready.

## Who is it for?

| You are... | Why you'll like it |
| --- | --- |
| **Laravel agency / freelancer** | Deliver client-editable marketing sites in Filament without building a custom CMS from scratch. |
| **SaaS developer** | Let tenants build their own landing pages, popups and forms inside your existing Filament panel. |
| **Product owner** | Marketers edit pages, headers, footers and forms visually — developers keep Eloquent models and code review. |
| **Add-on author** | Sell your own widgets/integrations on top of a free core (Crocoblock-style add-on API). |

## How does it work?

```
┌────────────────────────────────────────────────────────────────────────┐
│                         Filament v5 Admin Panel                        │
│                                                                        │
│  Pages Resource  →  "Visual Builder"  →  Livewire v4 Editor            │
│                                              │                         │
│                    Widgets ── Inspector ── Canvas (drag & drop)        │
│                                              │                         │
│                         Save Draft ── Preview ── Publish               │
└──────────────────────────────┬─────────────────────────────────────────┘
                               │
                    JSON Schema (version 1, containers → widgets)
                               │
              ┌────────────────┴─────────────────┐
              │                                  │
     draft_content (working)            content (live, cached)
              │                                  │
     Signed Preview URL                 Blade + CSS renderer
     (/_builder/preview/{page})         (public /pages/{slug})
```

- Every page is stored as a **JSON schema tree** (`containers → widgets → settings + dynamic`).
- **Two columns** keep editing safe: `draft_content` (what you edit/preview) and `content` (what visitors see).
- On **Publish**, the draft is copied to live content, a **revision snapshot** is created and the render cache is invalidated.
- Rendering is pure **Blade + generated CSS** (CSS variables with `@container` and `@media` queries), so no iframe or JS build step is required.

## Feature overview

| Area | What you get |
| --- | --- |
| **Visual Editor** | Drag & drop canvas, nested Flexbox containers, Content/Style/Advanced inspector, labeled Navigator element tree, Editor Labels, Undo/Redo, Copy/Paste, Duplicate, Hide/Show, responsive viewport switcher |
| **Workflow** | Draft vs Published, signed Preview URLs, Publish with revisions, Discard Draft, Unpublish, Restore Revision |
| **Admin & Organization** | Collapsible Page Builder menu, theme groups, parent/child page hierarchy with nested lines, default Visual Builder, Advanced JSON forms and unified Site Settings with Coming Soon/Maintenance |
| **Posts & Categories** | Built-in Eloquent models and Filament resources, featured image uploads, publishing states, category relationships and optional starter content |
| **26+ Widgets** | Containers, content and media widgets, Gallery, Accordion, Tabs, Testimonial, Loop, Form, Global Widget, Slider, Pricing Table, Countdown, Progress Bar, Social Icons, Rating, Maps, Flip Box, CTA, Nav Menu and more |
| **Theme Builder** | Visual Builder for Header/Footer/Single/Archive/404 templates with include/exclude display conditions (path, route, page slug, model) |
| **Dynamic Content** | Dynamic Tags (`page.title`, `model.attribute:*`, `site.current_date`) + Loop Builder with grid/list/carousel, filters, AJAX Load More and numbered pagination |
| **Forms** | Drag & drop fields, pluggable actions (Email, Redirect, Webhook, Save to Database), conditional logic, multi-step, honeypot, Cloudflare Turnstile, rate limiting |
| **Popups** | Same schema/renderer as pages, on-load/scroll/exit-intent/click triggers, URL conditions, show-once-per-visitor, accessible dialogs |
| **Add-on API** | WidgetRegistry, DynamicTagRegistry, TemplateRegistry, FormActionRegistry + lifecycle events |

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | `^8.3 \| ^8.4 \| ^8.5` |
| Laravel | `^12.0 \| ^13.0 \| ^14.0` |
| Filament | `^5.0` |
| Livewire | `^4.0` |
| Tailwind CSS | `^4.0` |

## Installation (Quick Start)

### 1. Require the package

```bash
composer require kalimeromk/filament-page-builder
```

### 2. Install the package

```bash
php artisan fpb:install
```

This publishes the configuration and migrations, runs `migrate`, and seeds **3 starter categories, 9 published posts and a News page** with a working Loop Builder query. Existing pages and posts are never overwritten when the installer or starter seeder runs again.

To install only the package structure without starter content:

```bash
php artisan fpb:install --without-content
```

The tables are: `fpb_pages`, `fpb_page_revisions`, `fpb_templates`, `fpb_theme_templates`, `fpb_form_submissions`, `fpb_popups`, `fpb_settings`, `fpb_menus`, `fpb_categories`, `fpb_posts`.

### 3. Register the plugin in your panel

In `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Filament\Panel;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        // ... your existing configuration
        ->plugin(FilamentPageBuilderPlugin::make());
}
```

Optional configuration:

```php
->plugin(
    FilamentPageBuilderPlugin::make()
        ->navigationGroup('Page Builder')
        ->navigationSort(10)
        ->pageResource(true)
        ->themeTemplateResource(true)
)
```

### 4. (Optional) Publish the config

```bash
php artisan vendor:publish --tag="filament-page-builder-config"
```

## Your first page in 2 minutes

1. In the Filament sidebar, open **Page Builder → Pages → Create**.
2. Give it a **Title** (e.g. `Home`) and **Slug** (e.g. `home`), then **Create**. You are redirected to **Visual Builder** automatically.
3. From the **+ Blocks** panel, click **Flex Container**, then **Heading** inside it.
4. Click the heading on the canvas — the **Inspector** opens with Content / Style / Advanced tabs. Change the text to `Hello Laravel!`.
5. Click **Save Draft**, then **Preview** to see the draft in the real frontend layout (Desktop / Tablet / Mobile).
6. Click **Publish** — your page is now live at **`/pages/home`** 🎉

## Usage guides

### Pages & Visual Editor

**Editor tabs (left panel):**

- **+ Blocks** — widget palette (click or drag onto the canvas / into a container).
- **Edit (Inspector)** — Filament v5 `Schema` form with **Content / Style / Advanced** tabs for the selected element + **Dynamic Tags** bindings.
- **Tree (Navigator)** — hierarchical element tree with readable labels, vertical nesting guides, select, move up/down, hide/show and delete.
- **Library** — Save the full page or a selected block as a reusable template; insert/replace from database or registered templates; **Import / Export JSON**.
- **History** — published revisions with **Restore** and **Compare**.
- **SEO** — title, slug, layout (`default`, `canvas`, `full_width`), meta title/description, OG image, `noindex`.

**Top toolbar:** Undo / Redo, Paste Block, viewport switcher, **Discard Draft**, **Save Draft**, **Preview**, **Publish**. The **Advanced** action opens the classic form with the draft JSON editor.

**Pages admin and hierarchy:**

- Clicking a page, or creating a new one, opens **Visual Builder by default**. The classic form remains available through **Advanced**.
- **General → Theme & Hierarchy** stores a theme name and optional parent page in metadata. Pages are grouped by theme in a collapsible table and nested with `│` / `└─` lines.
- Example: `News → Category → Post Default → Post Fullwidth`. Hierarchy depth is cycle-protected.

**Editor labels and container backgrounds:**

- Every widget has **Advanced → Editor Label**. Labels such as `Featured stories` appear in the Navigator and canvas badge instead of only a technical element ID.
- Containers support **background image, size, position, repeat and attachment**. Choose **Fixed (Parallax)** for a CSS parallax effect, or **Scroll** for normal backgrounds. Image URLs are sanitized before CSS generation.

**Render a page in your own Blade view:**

```blade
@renderPageBuilder($page)
```

**Programmatic rendering:**

```php
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;

$published = PageBuilder::renderPage($page);                 // cached live content
$draft     = PageBuilder::renderPage($page, useDraft: true); // draft preview

echo $published->toHtml(); // includes <style> + canvas markup
```

**Page model workflow:**

```php
$page->saveDraft($schema);       // writes draft_content only
$page->getPreviewUrl();          // signed preview link (60 min TTL)
$page->publish();                // copies draft to live + creates revision
$page->discardDraft();           // reset draft to published content
$page->unpublish();              // back to draft, hides from public route
$page->restoreRevision($rev);    // restore a previous snapshot
$page->hasUnpublishedChanges();  // true when draft differs from live
```

### Menu Builder

**Page Builder → Menus** keeps the WordPress-inspired layout while using Filament theme variables for primary, gray and danger colors at `/admin/menus/{record}/builder`:

- Use the left **Pages** panel with **Most Recent / View All / Search**, page checkboxes, **Select All** and **Add to Menu**, or add a custom label and URL under **Custom Links**.
- Drag & drop root items and dropdown children, or use the accessible up/down buttons.
- Click any item to expand its details: navigation label, custom URL, link target and **Custom Link** or **Page** link type.
- Selecting a Page fills its public `/pages/{slug}` URL automatically and uses the page title when the label is empty.
- Add dropdown children, move a child to the root or move items between parents without creating unsupported nesting levels.
- Edit the menu name and slug, then **Save Menu**. Links are normalized and sanitized through the Menu model.
- The classic Repeater form remains available through **Advanced**.

### Posts & Categories

The package includes a small editorial CMS out of the box — no `App\Models\Post` is required in your application.

- **Page Builder → Posts** provides title/slug, category, author, status, publish date, featured image, excerpt and rich body fields.
- **Page Builder → Categories** provides named categories with slugs, descriptions and post counts. A post belongs to a category; deleting a category keeps its posts with a null category.
- Statuses are `draft`, `published`, `scheduled` and `archived`. The `published` query scope requires a non-null `published_at` at or before the current time.
- **Featured Image** is a real upload on the `public` disk in `fpb/posts`. JPEG, PNG, WebP and GIF are accepted up to **5 MB**; SVG is intentionally not accepted. `fpb:install` creates the public storage link when missing.
- Published posts have public **`/posts/{slug}`** pages with title, category, author, date, featured image and sanitized body. Draft and future posts return 404.

For uploaded or externally hosted images and public post links, use these attributes in Loop item schemas:

```text
model.attribute:image_url
model.attribute:public_url
model.attribute:category.name
```

`public_url` generates the public post route and is used by starter/demo loop titles and images.

`image_url` returns the public storage URL for uploads and preserves existing HTTP(S) or root-relative image URLs.

The idempotent starter seeder can also run independently. It creates starter categories/posts and a News page only when they are absent, without overwriting existing content:

```bash
php artisan db:seed --class="KalimeroMK\FilamentPageBuilder\Database\Seeders\StarterContentSeeder"
```

### Theme Builder

Create **Theme Builder → New Template** with a region:

- **Header** / **Footer** — rendered by the layout via `@filamentThemeRegion('header')` / `@filamentThemeRegion('footer')`.
- **Single** — per-model detail templates.
- **Archive** — listing templates.
- **404 Not Found** — custom 404 layout rendered by the fallback controller with a real 404 response.

Theme templates support the same **Visual Builder** used for pages: widgets, drag & drop, Navigator, Editor Labels, responsive canvas and **Save Draft → Publish**. Clicking a template or creating one opens `/admin/theme-templates/{record}/builder`.

Use **Advanced** for the classic JSON form, region, priority and display conditions. Page Preview and Page Revisions are intentionally hidden for theme templates.

**Display conditions** (include/exclude rules):

| Target | Example | Meaning |
| --- | --- | --- |
| `entire_site` | `*` | Everywhere |
| `path` | `blog/*` | Any URL starting with `/blog/` |
| `route` | `blog.*` | Named routes matching the pattern |
| `page_slug` | `checkout` | Only the page with that slug |
| `model` | `App\Models\Post` | When the current record is that model |

Example: **"Whole site except checkout"** = `include entire_site *` + `exclude page_slug checkout`.

**Priority** decides which template wins when several match (higher number wins).

### Dynamic Tags

Bind **any widget setting** to a dynamic source in the Inspector → **Dynamic Tags** section:

```
model.attribute:title          → current record's `title` attribute
model.attribute:author.name    → nested attribute / relation
model.attribute:image_url      → public uploaded image or external image URL
model.attribute:category.name  → related category name on the built-in Post model
page.title                     → current page title
site.current_date              → today's date
```

Add your own provider:

```php
use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;

class UserNameTag implements DynamicTagContract
{
    public static function getName(): string { return 'user.name'; }
    public static function getLabel(): string { return 'Current User Name'; }
    public static function getGroup(): string { return 'User'; }

    public function resolve(array $parameters = [], array $context = []): mixed
    {
        return auth()->user()?->name;
    }
}

// boot()
app(\KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry::class)
    ->register(UserNameTag::class);
```

### Loop Builder

Render **any Eloquent collection** as a grid, list or carousel:

1. Add the **Loop Builder** widget.
2. Set **Model Class** (the built-in `KalimeroMK\FilamentPageBuilder\Models\Post` or your own model), **Limit**, **Order By**, optional scope and filters.
3. Choose a **Sub-template** from the Template Library or paste an **Inline Item JSON Schema**.
4. Inside the item schema, use `model.attribute:*` — it resolves against the **current record** of each iteration.
5. Optionally enable **Load More (AJAX)**, **Page Numbers (AJAX)** and exposed frontend filter fields. Responses include `has_more`, `total` and `total_pages` metadata with deterministic ordering.

**Security:** only models listed in `loop.allowed_models` can be queried. An empty model list denies every model; the default config allows only the built-in Post model. Field restrictions apply only to models explicitly configured in `allowed_fields`.

```php
'loop' => [
    'allowed_models' => [\KalimeroMK\FilamentPageBuilder\Models\Post::class],
    'allowed_fields' => [
        \KalimeroMK\FilamentPageBuilder\Models\Post::class => [
            'id', 'category_id', 'title', 'slug', 'excerpt', 'body', 'image',
            'author', 'status', 'published_at', 'created_at', 'updated_at',
        ],
    ],
],
```

### Form Builder

1. Add the **Form Builder** widget to a page (or popup).
2. Define fields (`text`, `email`, `number`, `textarea`, `select`, `checkbox`) with labels, required flags, options, **step numbers** (multi-step) and **`visible_if` rules** (conditional display).
3. Add **actions after submit** (any order, any combination):
   - **Save to Database** — stores the submission in `fpb_form_submissions`.
   - **Email** — sends a plain-text notification to the configured recipient.
   - **Redirect** — relative URL by default (external URLs require `allow_external_redirects`).
   - **Webhook** — HTTPS POST to a public host (SSRF-safe).
4. Enable **Honeypot** (default on) and/or **Cloudflare Turnstile** (set both keys in config).

Submissions are **rate-limited**, validated server-side (including conditional visibility), and each action's result is logged. Errors use a per-form error bag, so multiple forms on one page don't clash.

### Popup Builder

1. Create a popup in the database (or via code) with a JSON schema — the **same renderer** as pages.
2. Configure **trigger**: `on_load` (delay seconds), `on_scroll` (percent), `exit_intent`, or `on_click`.
3. Add **conditions** (same rule engine as Theme Builder) and **show once per visitor**.

Popups render on all layouts (`default`, `canvas`, `full_width`) with accessible dialogs (`role="dialog"`, Escape to close, focus handling) and storage-safe "show once" logic.

## Configuration reference

`config/filament-page-builder.php`:

```php
return [
    'allow_raw_html' => false, // force sanitization of HTML embeds

    'routing' => [
        'enabled' => true,
        'prefix' => 'pages',               // public pages: /pages/{slug}
        'preview_prefix' => '_builder/preview',
        'middleware' => ['web'],
        'preview_middleware' => ['web'],
    ],

    'layouts' => [
        'default' => 'filament-page-builder::layouts.default',
        'canvas' => 'filament-page-builder::layouts.canvas',
        'full_width' => 'filament-page-builder::layouts.full-width',
    ],

    'breakpoints' => ['tablet' => 1024, 'mobile' => 767],

    'cache' => ['enabled' => true, 'ttl' => 86400, 'prefix' => 'fpb_page_render_'],

    'loop' => [
        'allowed_models' => [\KalimeroMK\FilamentPageBuilder\Models\Post::class],
        'allowed_fields' => [
            \KalimeroMK\FilamentPageBuilder\Models\Post::class => [
                'id', 'category_id', 'title', 'slug', 'excerpt', 'body', 'image',
                'author', 'status', 'published_at', 'created_at', 'updated_at',
            ],
        ],
    ],

    'forms' => [
        'rate_limit' => '10,1',
        'webhook_allowed_hosts' => [],
        'allow_external_redirects' => false,
        'turnstile' => [
            'site_key' => env('FPB_TURNSTILE_SITE_KEY', ''),
            'secret_key' => env('FPB_TURNSTILE_SECRET_KEY', ''),
        ],
    ],
];
```

## Add-on API (for developers)

### Register a widget

```php
use KalimeroMK\FilamentPageBuilder\Widgets\AbstractWidget;

class PricingTableWidget extends AbstractWidget
{
    public static function getType(): string { return 'pricing_table'; }
    public static function getLabel(): string { return 'Pricing Table'; }
    public static function getIcon(): string { return 'heroicon-o-currency-dollar'; }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'plan_name' => 'Pro',
            'price' => '€19',
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('plan_name')->required(),
            \Filament\Forms\Components\TextInput::make('price')->required(),
        ];
    }

    public function getStyleSchema(): array { return []; }

    public function getCssMap(): array
    {
        return $this->baseCssMap();
    }

    protected function getViewName(): string
    {
        return 'my-addon::widgets.pricing-table';
    }
}

// boot() in your add-on's service provider
\KalimeroMK\FilamentPageBuilder\Facades\PageBuilder::widgets()
    ->register(PricingTableWidget::class);
```

### Register a form action

```php
use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;

class MailchimpAction implements FormActionContract
{
    public static function getName(): string { return 'mailchimp'; }
    public static function getLabel(): string { return 'Subscribe to Mailchimp'; }

    public function handle(array $actionSettings, array $formData, $submission, $page, array $context = []): array
    {
        // ... call your integration
        return ['success' => true];
    }
}

app(\KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry::class)
    ->register(MailchimpAction::class);
```

### Register a template

```php
\KalimeroMK\FilamentPageBuilder\Facades\PageBuilder::templates()->register(
    'hero-gradient',
    'Hero Gradient',
    ['version' => 1, 'elements' => [/* ... */]],
    category: 'heroes',
);
```

### Lifecycle events

`PageSavingDraft`, `PageDraftSaved`, `PagePublishing`, `PagePublished`, `BeforeElementRender`, `AfterElementRender` — listen to them to alter schemas, settings or rendered HTML.

## Security notes

- **XSS**: DOM-based HTML sanitization (Symfony HTML Sanitizer) + URL scheme/control-character filtering on links and images. Background image URLs receive additional CSS-safe validation.
- **Editor**: `#[Locked]` Livewire properties (IDOR-safe), `RestrictsFileUploadsToSchemaComponents`, panel/policy authorization.
- **Uploads**: public-disk storage with only JPEG/PNG/WebP/GIF accepted and a 5 MB limit; no executable or SVG uploads.
- **Preview**: signed URLs or Filament panel/policy check — never "any logged-in user".
- **Dynamic content**: Eloquent `$hidden` attributes are never exposed; loop models are fail-closed allowlisted and fields can be restricted per model.
- **Forms**: CSRF, server-side validation, rate limiting, honeypot, optional Turnstile, per-form error bags, sanitized action logs.
- **Webhooks**: HTTPS only, public IPs only, no redirects, optional host allowlist.

## Testing

```bash
composer test
# or
./vendor/bin/pest
```

**72 feature tests (539+ assertions)** covering: widgets and responsive CSS, Page and Theme Template Visual Builders, WordPress-style Menu Builder with nested reordering and Page links, Draft/Preview/Publish, public post routes and loop links, revisions, Add-on API, theme conditions, labeled Navigator, page hierarchy and admin navigation, Posts/Categories and starter installation, public image uploads, Loop Builder & Dynamic Tags, forms and SSRF protection, Popups & Turnstile, unified Global and Coming Soon settings, Custom 404, Nav Menu, Schema Diff, Site Kit, Maintenance mode, and Finder.

CI runs automatically on every push/PR via GitHub Actions (`.github/workflows/tests.yml`) on PHP 8.3, 8.4 and 8.5.

## FAQ

**Does it require an iframe?**
No. Responsive preview works with CSS `@container` queries + `@media` fallbacks, so the canvas runs directly in the Filament page.

**Does it replace Filament Resources?**
No — it complements them. Your Eloquent models and Resources remain the source of truth for data; the builder handles presentation, dynamic content and forms.

**Can I use my own Blade layout?**
Yes. Configure `layouts` in the config or override the published views. The `canvas` layout renders without header/footer (Elementor Canvas-style).

**Will the core become paid?**
No. Everything is and stays MIT forever — there is no paid version and no locked features. Future add-ons (e-commerce widgets, integrations, template cloud) will also be published free and open-source.

## Roadmap (100% free & open-source)

- [x] v0.1 — JSON schema, renderer, Draft/Preview/Publish, 4 widgets
- [x] v0.2 — Livewire v4 visual editor, drag & drop, 4 more widgets
- [x] v0.3 — Undo/Redo, Copy/Paste, Template Library + JSON import/export, 6 more widgets
- [x] v0.4 — Theme Builder + display conditions, Navigator
- [x] v0.5 — Dynamic Tags + Loop Builder
- [x] v0.6 — Form Builder with pluggable actions
- [x] v0.7 — Popup Builder + conditional/multi-step forms + Turnstile
- [x] v1.0 — Documentation, test suite, stable release
- [x] v1.1 — Global Site Settings, Global Widget, Custom CSS/Attributes, Custom 404, Submissions admin, Autoresponder, 9 new widgets
- [x] v1.2 — Nav Menu + off-canvas, Motion/Sticky/Device visibility, Custom Fonts/Icons, Role Manager, Revision diff
- [x] v1.3 — Full Site Kit import/export, Loop Carousel + AJAX pagination, Maintenance mode, Finder (Ctrl+K)
- [x] v1.3.1 — Coming Soon templates, Loop AJAX filters, numbered pagination and maintenance hardening
- [x] Current development — Built-in Posts/Categories, starter installer, public image uploads, labeled Navigator, page hierarchy, collapsible admin menu and Visual Builder for theme templates

**Planned free add-ons (also MIT):** e-commerce widgets (Lunar/Bagisto/Cashier), CRM & newsletter integrations (Mailchimp, HubSpot), advanced motion effects, template cloud library, A/B testing — all published for free.

## Contributing & License

Issues and pull requests are welcome at [github.com/KalimeroMK/filament-page-builder](https://github.com/KalimeroMK/filament-page-builder).

**License:** MIT — see [LICENSE.md](LICENSE.md). © KalimeroMK <zbogoevski@gmail.com>
