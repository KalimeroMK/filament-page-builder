<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use KalimeroMK\FilamentPageBuilder\Models\Menu;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Models\Popup;
use KalimeroMK\FilamentPageBuilder\Models\Setting;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use KalimeroMK\FilamentPageBuilder\Support\SchemaTreeManipulator;

class SiteKitService
{
    public const KIT_VERSION = 1;

    /**
     * Export the full site kit (pages, theme templates, templates, popups, menus, settings).
     *
     * @return array{kit: string, version: int, pages: array<int, array<string, mixed>>, theme_templates: array<int, array<string, mixed>>, templates: array<int, array<string, mixed>>, popups: array<int, array<string, mixed>>, menus: array<int, array<string, mixed>>, settings: array<string, mixed>}
     */
    public function exportKit(): array
    {
        return [
            'kit' => 'filament-page-builder-site-kit',
            'version' => self::KIT_VERSION,
            'pages' => $this->pageModel()::query()->get()->map(fn (Page $page): array => [
                'title' => $page->title,
                'slug' => $page->slug,
                'layout' => $page->layout,
                'meta' => $page->meta,
                'schema' => $page->getWorkingSchema(),
            ])->values()->all(),
            'theme_templates' => $this->themeTemplateModel()::query()->get()->map(fn (ThemeTemplate $tpl): array => [
                'title' => $tpl->title,
                'region' => $tpl->region->value,
                'priority' => $tpl->priority,
                'conditions' => $tpl->conditions,
                'schema' => $tpl->getWorkingSchema(),
            ])->values()->all(),
            'templates' => $this->templateModel()::query()->get()->map(fn (PageTemplate $tpl): array => $tpl->exportToArray() + ['source_id' => $tpl->id])->values()->all(),
            'popups' => $this->popupModel()::query()->get()->map(fn (Popup $popup): array => [
                'title' => $popup->title,
                'trigger_settings' => $popup->trigger_settings,
                'conditions' => $popup->conditions,
                'schema' => $popup->getWorkingSchema(),
            ])->values()->all(),
            'menus' => $this->menuModel()::query()->get()->map(fn (Menu $menu): array => [
                'title' => $menu->title,
                'slug' => $menu->slug,
                'items' => $menu->items,
            ])->values()->all(),
            'settings' => [
                'site_style' => Setting::getGroup(SiteStyleService::SETTING_KEY, SiteStyleService::defaults()),
                'custom_fonts' => Setting::getGroup('custom_fonts', []),
                'custom_icons' => Setting::getGroup('custom_icons', []),
            ],
        ];
    }

    /**
     * @return array{pages: int, theme_templates: int, templates: int, popups: int, menus: int, settings: bool}
     */
    public function importKit(array $kit, ?int $userId = null): array
    {
        if (! $this->isValidKit($kit)) {
            throw new \InvalidArgumentException('Invalid site kit payload: unsupported kit format, version, or pages array.');
        }

        return DB::transaction(function () use ($kit, $userId): array {
            $counts = [
                'pages' => 0,
                'theme_templates' => 0,
                'templates' => 0,
                'popups' => 0,
                'menus' => 0,
                'settings' => false,
            ];

            $templateIdMap = [];

            foreach ((array) ($kit['templates'] ?? []) as $tplData) {
                if (! is_array($tplData) || empty($tplData['name'])) {
                    continue;
                }

                $template = $this->templateModel()::create([
                    'name' => (string) $tplData['name'],
                    'category' => (string) ($tplData['category'] ?? 'section'),
                    'description' => $tplData['description'] ?? null,
                    'schema' => $this->freshSchema((array) ($tplData['schema'] ?? [])),
                    'created_by' => $userId,
                ]);

                if (isset($tplData['source_id']) && is_numeric($tplData['source_id'])) {
                    $templateIdMap[(int) $tplData['source_id']] = (int) $template->getKey();
                }

                $counts['templates']++;
            }

            foreach ((array) ($kit['pages'] ?? []) as $pageData) {
                if (! is_array($pageData) || empty($pageData['title']) || empty($pageData['slug'])) {
                    continue;
                }

                $schema = $this->remapTemplateReferences(
                    $this->freshSchema((array) ($pageData['schema'] ?? [])),
                    $templateIdMap,
                );

                $this->pageModel()::create([
                    'title' => (string) $pageData['title'],
                    'slug' => $this->uniqueSlug((string) $pageData['slug']),
                    'layout' => (string) ($pageData['layout'] ?? 'default'),
                    'meta' => is_array($pageData['meta'] ?? null) ? $pageData['meta'] : null,
                    'draft_content' => $schema,
                ]);
                $counts['pages']++;
            }

            foreach ((array) ($kit['theme_templates'] ?? []) as $tplData) {
                if (! is_array($tplData) || empty($tplData['title']) || empty($tplData['region'])) {
                    continue;
                }

                $this->themeTemplateModel()::create([
                    'title' => (string) $tplData['title'],
                    'region' => (string) $tplData['region'],
                    'priority' => (int) ($tplData['priority'] ?? 10),
                    'conditions' => is_array($tplData['conditions'] ?? null) ? $tplData['conditions'] : null,
                    'draft_content' => $this->remapTemplateReferences(
                        $this->freshSchema((array) ($tplData['schema'] ?? [])),
                        $templateIdMap,
                    ),
                ]);
                $counts['theme_templates']++;
            }

            foreach ((array) ($kit['popups'] ?? []) as $popupData) {
                if (! is_array($popupData) || empty($popupData['title'])) {
                    continue;
                }

                $this->popupModel()::create([
                    'title' => (string) $popupData['title'],
                    'trigger_settings' => is_array($popupData['trigger_settings'] ?? null) ? $popupData['trigger_settings'] : null,
                    'conditions' => is_array($popupData['conditions'] ?? null) ? $popupData['conditions'] : null,
                    'draft_content' => $this->remapTemplateReferences(
                        $this->freshSchema((array) ($popupData['schema'] ?? [])),
                        $templateIdMap,
                    ),
                ]);
                $counts['popups']++;
            }

            foreach ((array) ($kit['menus'] ?? []) as $menuData) {
                if (! is_array($menuData) || empty($menuData['title']) || empty($menuData['slug'])) {
                    continue;
                }

                $this->menuModel()::create([
                    'title' => (string) $menuData['title'],
                    'slug' => $this->uniqueMenuSlug((string) $menuData['slug']),
                    'items' => is_array($menuData['items'] ?? null) ? $menuData['items'] : [],
                ]);
                $counts['menus']++;
            }

            $settings = is_array($kit['settings'] ?? null) ? $kit['settings'] : [];
            foreach (['site_style', 'custom_fonts', 'custom_icons'] as $settingKey) {
                if (isset($settings[$settingKey]) && is_array($settings[$settingKey])) {
                    Setting::setGroup($settingKey, $settings[$settingKey]);
                    $counts['settings'] = true;
                }
            }

            return $counts;
        });
    }

    public function isValidKit(array $kit): bool
    {
        if (($kit['kit'] ?? '') !== 'filament-page-builder-site-kit') {
            return false;
        }

        $version = (int) ($kit['version'] ?? 0);

        if ($version < 1 || $version > self::KIT_VERSION) {
            return false;
        }

        if (! is_array($kit['pages'] ?? null)) {
            return false;
        }

        // Guard against unbounded imports
        foreach (['pages', 'theme_templates', 'templates', 'popups', 'menus'] as $section) {
            if (isset($kit[$section]) && is_array($kit[$section]) && count($kit[$section]) > 2000) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    protected function freshSchema(array $schema): array
    {
        $normalized = Page::normalizeSchema($schema);

        return [
            'version' => 1,
            'elements' => array_values(array_map(
                fn (array $el): array => SchemaTreeManipulator::cloneWithFreshIds($el),
                $normalized['elements'],
            )),
        ];
    }

    /**
     * Rewrite template_id / global_id references in imported schemas using the source->new template ID map.
     *
     * @param  array{version: int, elements: array<int, array<string, mixed>>}  $schema
     * @param  array<int, int>  $templateIdMap
     * @return array{version: int, elements: array<int, array<string, mixed>>}
     */
    protected function remapTemplateReferences(array $schema, array $templateIdMap): array
    {
        if ($templateIdMap === []) {
            return $schema;
        }

        $walk = function (array $elements) use (&$walk, $templateIdMap): array {
            foreach ($elements as $index => $element) {
                if (! is_array($element)) {
                    continue;
                }

                foreach (['template_id', 'global_id'] as $refKey) {
                    if (isset($element['settings'][$refKey]) && is_numeric($element['settings'][$refKey])) {
                        $oldId = (int) $element['settings'][$refKey];
                        if (isset($templateIdMap[$oldId])) {
                            $elements[$index]['settings'][$refKey] = $templateIdMap[$oldId];
                        }
                    }
                }

                if (! empty($element['children']) && is_array($element['children'])) {
                    $elements[$index]['children'] = $walk($element['children']);
                }
            }

            return array_values($elements);
        };

        $schema['elements'] = $walk($schema['elements']);

        return $schema;
    }

    /**
     * @return class-string<Page>
     */
    protected function pageModel(): string
    {
        /** @var class-string<Page> $model */
        $model = config('filament-page-builder.models.page', Page::class);

        return $model;
    }

    /**
     * @return class-string<ThemeTemplate>
     */
    protected function themeTemplateModel(): string
    {
        /** @var class-string<ThemeTemplate> $model */
        $model = config('filament-page-builder.models.theme_template', ThemeTemplate::class);

        return $model;
    }

    /**
     * @return class-string<PageTemplate>
     */
    protected function templateModel(): string
    {
        /** @var class-string<PageTemplate> $model */
        $model = config('filament-page-builder.models.template', PageTemplate::class);

        return $model;
    }

    /**
     * @return class-string<Popup>
     */
    protected function popupModel(): string
    {
        /** @var class-string<Popup> $model */
        $model = config('filament-page-builder.models.popup', Popup::class);

        return $model;
    }

    /**
     * @return class-string<Menu>
     */
    protected function menuModel(): string
    {
        /** @var class-string<Menu> $model */
        $model = config('filament-page-builder.models.menu', Menu::class);

        return $model;
    }

    protected function uniqueSlug(string $slug): string
    {
        $slug = trim($slug) !== '' ? trim($slug) : 'page';
        $candidate = $slug;
        $attempt = 0;

        while ($this->pageModel()::query()->where('slug', $candidate)->exists() && $attempt < 10) {
            $candidate = $slug . '-imported-' . Str::lower(Str::random(4));
            $attempt++;
        }

        return $candidate;
    }

    protected function uniqueMenuSlug(string $slug): string
    {
        $slug = trim($slug) !== '' ? trim($slug) : 'menu';
        $candidate = $slug;
        $attempt = 0;

        while ($this->menuModel()::query()->where('slug', $candidate)->exists() && $attempt < 10) {
            $candidate = $slug . '-imported-' . Str::lower(Str::random(4));
            $attempt++;
        }

        return $candidate;
    }
}
