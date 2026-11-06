# Filament Page Builder

**Open-source (MIT) visual page builder for Laravel & Filament v5** — an Elementor Pro-like drag-and-drop builder that lets your users build pages, theme layouts, popups and forms **without writing code**.

Built and maintained by [KalimeroMK](https://github.com/KalimeroMK).

---

## Features

### Visual Editor (Livewire v4)
- Drag & drop canvas with nested **Flexbox Containers**
- Left panel: **Widgets**, **Inspector** (Content / Style / Advanced via Filament v5 `Schema`), **Navigator** (element tree), **Template Library**, **Revisions**, **SEO**
- **Undo / Redo**, **Copy / Paste**, **Duplicate**, **Move Up/Down**, **Hide/Show** elements
- Responsive preview switcher: **Desktop / Tablet / Mobile** (via CSS `@container` + `@media` queries)

### Draft → Preview → Publish Workflow
- `draft_content` vs `content` — the live page is never touched while editing
- Signed **Preview URL** showing the draft in the real frontend layout
- **Publish** copies draft to live content, records a `PageRevision` snapshot and clears the render cache
- **Discard Draft**, **Unpublish**, **Restore Revision** at any time

### 17 Built-in Widgets
`Container`, `Heading`, `Rich Text`, `Button`, `Image`, `Spacer / Divider`, `Icon`, `Video`, `HTML Embed`, `Image Gallery`, `Alert`, `Accordion`, `Tabs`, `Testimonial`, `Loop Builder`, `Form Builder`

### Theme Builder (Pro)
- **Header / Footer / Single / Archive** templates with `draft_content` / `content` and publish workflow
- **Display conditions**: `include` / `exclude` rules on `entire_site`, `path` (wildcards), `route`, `page_slug`, `model`
- Blade directive: `@filamentThemeRegion('header')`

### Dynamic Content (JetEngine-style)
- **Dynamic Tags**: bind any widget setting to `page.title`, `model.attribute:title`, `site.current_date`, or your own providers
- **Loop Builder**: render any allowlisted Eloquent model as a grid/list with filters, ordering, limits and per-record sub-templates (with unique element IDs and cycle protection)

### Form Builder (Pro)
- Drag & drop fields (text, email, number, textarea, select, checkbox)
- **Pluggable actions**: Save to Database, Email, Redirect, Webhook (SSRF-safe, HTTPS + public hosts only)
- **Conditional logic** (`visible_if`), **multi-step forms**, **Honeypot**, **Cloudflare Turnstile**, **rate limiting**

### Popup Builder (Pro)
- Same JSON schema + renderer as pages
- Triggers: **on load**, **on scroll %**, **exit intent**, **on click**
- URL / slug conditions, show-once-per-visitor, accessible dialogs (`role="dialog"`, focus handling, `localStorage` safe-guards)

### Add-on API (Crocoblock strategy)
- `WidgetRegistry`, `DynamicTagRegistry`, `TemplateRegistry`, `FormActionRegistry`
- Events: `PageSavingDraft`, `PageDraftSaved`, `PagePublishing`, `PagePublished`, `BeforeElementRender`, `AfterElementRender`

---

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | `^8.3 \| ^8.4 \| ^8.5` |
| Laravel | `^12.0 \| ^13.0 \| ^14.0` |
| Filament | `^5.0` |
| Livewire | `^4.0` |
| Tailwind CSS | `^4.0` |

## Installation

```bash
composer require kalimeromk/filament-page-builder
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="filament-page-builder-migrations"
php artisan migrate
```

Register the plugin in your panel provider:

```php
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentPageBuilderPlugin::make());
}
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="filament-page-builder-config"
```

## Usage

### Building a page

1. Open **Pages** in your Filament panel and create a page.
2. Click **Visual Builder** (`/{record}/builder`).
3. Drag widgets onto the canvas, edit them in the **Inspector**, then click **Save Draft**, **Preview** or **Publish**.
4. The public URL is `/pages/{slug}` (configurable prefix).

### Rendering a page in your own Blade view

```blade
@renderPageBuilder($page)
```

### Rendering a theme region

```blade
@filamentThemeRegion('header', ['page' => $page])
@filamentThemeRegion('footer', ['page' => $page])
```

### Programmatic rendering

```php
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;

$rendered = PageBuilder::renderPage($page);            // published, cached
$rendered = PageBuilder::renderPage($page, useDraft: true); // draft preview

echo $rendered->toHtml();
```

### Loop Builder & Dynamic Tags

Add a **Loop Builder** widget, set your Eloquent model class and query options, then bind any widget setting with a dynamic tag:

```
model.attribute:title
page.title
site.current_date
```

Allow models explicitly in `config/filament-page-builder.php`:

```php
'loop' => [
    'allowed_models' => [\App\Models\Post::class],
    'allowed_fields' => [],
],
```

### Registering your own widget (Add-on API)

```php
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Widgets\AbstractWidget;

class PricingTableWidget extends AbstractWidget
{
    public static function getType(): string { return 'pricing_table'; }
    public static function getLabel(): string { return 'Pricing Table'; }
    public static function getIcon(): string { return 'heroicon-o-currency-dollar'; }

    public function getContentSchema(): array { return []; }
    public function getStyleSchema(): array { return []; }
    public function getCssMap(): array { return $this->baseCssMap(); }

    protected function getViewName(): string
    {
        return 'my-addon::widgets.pricing-table';
    }
}

// In your add-on's service provider boot():
PageBuilder::widgets()->register(PricingTableWidget::class);
```

### Registering a form action

```php
use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;

class CrmAction implements FormActionContract
{
    public static function getName(): string { return 'crm'; }
    public static function getLabel(): string { return 'Send to CRM'; }

    public function handle(array $actionSettings, array $formData, $submission, $page, array $context = []): array
    {
        // ...
        return ['success' => true];
    }
}

app(\KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry::class)->register(CrmAction::class);
```

## Configuration highlights

```php
'routing' => [
    'prefix' => 'pages',
    'preview_prefix' => '_builder/preview',
],

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
```

## Security

- DOM-based HTML sanitization (Symfony HTML Sanitizer) for rich text & HTML embeds
- URL scheme + control-character sanitization for links and images
- `#[Locked]` Livewire properties (IDOR-safe editor)
- `RestrictsFileUploadsToSchemaComponents`
- Signed preview URLs + panel/policy authorization
- SSRF-safe webhooks (HTTPS, public IPs only, no redirects)
- Form rate limiting, honeypot, optional Cloudflare Turnstile
- Eloquent `$hidden` attributes are never exposed via dynamic tags

## Testing

```bash
composer test
# or
./vendor/bin/pest
```

31 feature tests (262+ assertions) cover the full Draft/Preview/Publish lifecycle, renderer, widgets, theme conditions, loop builder, forms and popups.

## Roadmap

- [x] v0.1 — JSON schema, renderer, Draft/Preview/Publish, 4 widgets
- [x] v0.2 — Livewire v4 visual editor, drag & drop, 4 more widgets
- [x] v0.3 — Undo/Redo, Copy/Paste, Template Library + JSON import/export, 6 more widgets
- [x] v0.4 — Theme Builder + display conditions, Navigator
- [x] v0.5 — Dynamic Tags + Loop Builder
- [x] v0.6 — Form Builder with pluggable actions
- [x] v0.7 — Popup Builder + conditional/multi-step forms + Turnstile
- [x] v1.0 — Documentation, test suite, Packagist release

Planned paid add-ons (core stays MIT forever): e-commerce widgets, advanced motion effects, CRM/newsletter integrations, template cloud library.

## License

MIT — see [LICENSE.md](LICENSE.md). © KalimeroMK <zbogoevski@gmail.com>
