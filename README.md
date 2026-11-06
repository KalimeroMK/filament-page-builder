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
   - [Theme Builder (Header / Footer / Single / Archive)](#theme-builder)
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
| **Posts & Categories** | Built-in Eloquent models and Filament resources, featured image uploads, publishing states, category relationships and optional starter content |
| **17 Widgets** | Container, Heading, Rich Text, Button, Image, Spacer/Divider, Icon, Video, HTML Embed, Image Gallery, Alert, Accordion, Tabs, Testimonial, Loop Builder, Form Builder |
| **Theme Builder** | Header/Footer/Single/Archive templates with include/exclude display conditions (path, route, page slug, model) |
| **Dynamic Content** | Dynamic Tags (`page.title`, `model.attribute:*`, `site.current_date`) + Loop Builder for Eloquent collections with filters/ordering/sub-templates |
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
        ->navigationGroup('Content')
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

1. In the Filament sidebar, open **Pages → Create**.
2. Give it a **Title** (e.g. `Home`) and **Slug** (e.g. `home`), then **Create**.
3. In the table, click **Visual Builder** (or open `Pages → Edit → Open Visual Builder`).
4. From the **+ Blocks** panel, click **Flex Container**, then **Heading** inside it.
5. Click the heading on the canvas — the **Inspector** opens with Content / Style / Advanced tabs. Change the text to `Hello Laravel!`.
6. Click **Save Draft**, then **Preview** to see the draft in the real frontend layout (Desktop / Tablet / Mobile).
7. Click **Publish** — your page is now live at **`/pages/home`** 🎉

## Usage guides

### Pages & Visual Editor

**Editor tabs (left panel):**

- **+ Blocks** — widget palette (click or drag onto the canvas / into a container).
- **Edit (Inspector)** — Filament v5 `Schema` form with **Content / Style / Advanced** tabs for the selected element + **Dynamic Tags** bindings.
- **Tree (Navigator)** — hierarchical element tree with select, move up/down, hide/show and delete.
- **Library** — Save the full page or a selected block as a reusable template; insert/replace from database or registered templates; **Import / Export JSON**.
- **History** — published revisions with **Restore**.
- **SEO** — title, slug, layout (`default`, `canvas`, `full_width`), meta title/description, OG image, `noindex`.

**Top toolbar:** Undo / Redo, Paste Block, viewport switcher, **Discard Draft**, **Save Draft**, **Preview**, **Publish**.

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

### Theme Builder

Create **Theme Builder → New Template** with a region:

- **Header** / **Footer** — rendered by the layout via `@filamentThemeRegion('header')` / `@filamentThemeRegion('footer')`.
- **Single** — per-model detail templates.
- **Archive** — listing templates.

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

Render **any Eloquent collection** as a grid or list:

1. Add the **Loop Builder** widget.
2. Set **Model Class** (e.g. `App\Models\Post`), **Limit**, **Order By**, optional scope and filters.
3. Choose a **Sub-template** from the Template Library or paste an **Inline Item JSON Schema**.
4. Inside the item schema, use `model.attribute:*` — it resolves against the **current record** of each iteration.

**Security:** only models listed in `loop.allowed_models` can be queried (fail-closed by default).

```php
'loop' => [
    'allowed_models' => [\App\Models\Post::class],
    'allowed_fields' => [
        \App\Models\Post::class => ['id', 'title', 'status', 'created_at'],
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
        'allowed_models' => [],
        'allowed_fields' => [],
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

- **XSS**: DOM-based HTML sanitization (Symfony HTML Sanitizer) + URL scheme/control-character filtering on links and images.
- **Editor**: `#[Locked]` Livewire properties (IDOR-safe), `RestrictsFileUploadsToSchemaComponents`, panel/policy authorization.
- **Preview**: signed URLs or Filament panel/policy check — never "any logged-in user".
- **Dynamic content**: Eloquent `$hidden` attributes are never exposed; loop models/fields are allowlisted.
- **Forms**: CSRF, server-side validation, rate limiting, honeypot, optional Turnstile, per-form error bags, sanitized action logs.
- **Webhooks**: HTTPS only, public IPs only, no redirects, optional host allowlist.

## Testing

```bash
composer test
# or
./vendor/bin/pest
```

**52 feature tests (401+ assertions)** covering: widget registry & schemas, rendering & responsive CSS, Draft/Preview/Publish lifecycle, revisions, Add-on API, Theme Builder conditions, Navigator, Loop Builder & Dynamic Tags, Form Builder (validation, actions, SSRF, honeypot), Popups & Turnstile, Global Settings, Global Widget, Custom 404, Autoresponder, Nav Menu, Custom Assets, Schema Diff, Site Kit, Maintenance mode, and Finder.

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

**Planned free add-ons (also MIT):** e-commerce widgets (Lunar/Bagisto/Cashier), CRM & newsletter integrations (Mailchimp, HubSpot), advanced motion effects, template cloud library, A/B testing — all published for free.

## Contributing & License

Issues and pull requests are welcome at [github.com/KalimeroMK/filament-page-builder](https://github.com/KalimeroMK/filament-page-builder).

**License:** MIT — see [LICENSE.md](LICENSE.md). © KalimeroMK <zbogoevski@gmail.com>
