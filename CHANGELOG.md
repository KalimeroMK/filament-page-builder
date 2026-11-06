# Changelog

All notable changes to `kalimeromk/filament-page-builder` are documented here.

## v1.3.0 — 2026-11-06

- **Full Site Kit import/export** (`SiteKitService` + `SiteKitPage`): pages, theme templates, templates, popups, menus, and settings in one JSON payload, imported atomically as drafts with fresh element IDs, unique slugs, and source→new `template_id` / `global_id` remapping.
- **Loop Carousel** layout with scroll-snap and **AJAX Load More pagination** (`LoopController`, offset-based `hasMore` probing, response validation, and error states).
- **Maintenance mode** middleware with 503 page (optional `PageTemplate` render) and admin/preview/Livewire bypass.
- **Finder (Ctrl+K)** in the visual editor: search elements by ID, widget type, or text with breadcrumbs and quick selection.

## v1.2.0 — 2026-11-06

- **Nav Menu + Off-canvas** (`Menu`, `MenuResource`, `NavMenuWidget` with dropdowns, breakpoint off-canvas, ARIA, focus management).
- **Motion Effects & Sticky** (`animation`, `sticky` + offset) and **Device visibility** (`hide_on_desktop/tablet/mobile`).
- **Custom Fonts & Icons** (`CustomAssetsService` with HTTPS font imports, custom families, and a DOM-based SVG sanitizer).
- **Role Manager** (`fpb.access_builder` Gate + `PageBuilderAuthorizer`, `default_allow` config) on all resources.
- **Revision diff/compare** (`SchemaDiffService` with reorder detection) + Compare UI in the editor.
- All features remain free & open-source (MIT), no paid version.

## v1.1.0 — 2026-11-06

- **Global Site Settings** (colors & typography via CSS variables + Filament settings page).
- **Global Widget** with synced instances and nested CSS propagation.
- **Custom CSS + Custom HTML Attributes** per element (strict allowlists).
- **Custom 404** theme region + fallback controller.
- **Form Submissions admin** with details + CSV export (formula escaping).
- **Autoresponder** email action.
- 9 new widgets (global_widget, slider, pricing_table, countdown, progress_bar, social_icons, star_rating, google_maps, flip_box, cta).

## v1.0.0 — 2026-11-06

Official stable release.

- Full README documentation, add-on API guide and security notes
- 31 Pest feature tests (262 assertions) across all modules
- Packagist-ready package metadata

## v0.7.0 — 2026-11-06

- **Popup Builder**: `Popup` model, `PopupResolver`, Alpine.js triggers (on load, on scroll %, exit intent, on click), URL/slug conditions, accessible dialogs, safe `localStorage`
- **Conditional & multi-step forms** with `visible_if` rules and step navigation
- **Cloudflare Turnstile** anti-spam with fail-closed configuration
- Popup-embedded form submissions via dedicated route
- Per-form error bags and disabled/required binding for hidden fields

## v0.6.0 — 2026-11-06

- **Form Builder** widget with pluggable actions: Save to Database, Email, Redirect, Webhook
- SSRF-safe webhooks (HTTPS, public hosts, no redirects)
- Optional submission persistence, action failure aggregation, per-action exception isolation
- Select option allowlists, accepted-checkbox validation, rate limiting, `old()` repopulation

## v0.5.0 — 2026-11-06

- **Dynamic Tags** (`model.attribute`, `page.title`, `site.current_date`) + bindings UI in the editor
- **Loop Builder** with secure Eloquent querying (model allowlist, field allowlist, scalar filters)
- Unique per-record element IDs, item CSS collection, circular-template protection, `dynamic.model` relation resolution

## v0.4.0 — 2026-11-06

- **Theme Builder**: Header/Footer/Single/Archive templates with `include`/`exclude` display conditions (path, route, page slug, model)
- `@filamentThemeRegion` Blade directive
- **Navigator** element tree with visibility toggling, move and delete actions

## v0.3.0 — 2026-11-06

- **Undo / Redo** and **Copy / Paste** in the visual editor (with `#[Locked]` history state)
- **Template Library** (`PageTemplate` model) with Save as Template, Insert/Replace and validated JSON import/export
- **Revisions panel** with restore support
- 6 new widgets: HTML Embed, Image Gallery, Alert, Accordion, Tabs, Testimonial

## v0.2.0 + v0.2.1 — 2026-11-06

- **Livewire v4 visual editor** with drag & drop canvas, lazy Filament v5 `Schema` inspector (Content/Style/Advanced), responsive viewport switcher, Save Draft / Preview / Publish toolbar
- New widgets: Image, Spacer/Divider, Icon, Video (YouTube/Vimeo/self-hosted)
- `#[Locked]` `$pageId`, file-upload restriction, DOM XSS sanitization, slug validation, live inspector sync

## v0.1.0 + v0.1.1 — 2026-11-06

- Initial JSON schema renderer + CSS variables (`@container` + `@media` queries)
- **Draft / Preview / Publish** workflow with `draft_content` vs `content`, signed preview URLs, revisions and render cache
- Add-on API: `WidgetRegistry`, `DynamicTagRegistry`, `TemplateRegistry`, render/publish events
- Core widgets: Container, Heading, Rich Text, Button
- DOM-based HTML sanitizer, deterministic cache keys, preview authorization hardening
