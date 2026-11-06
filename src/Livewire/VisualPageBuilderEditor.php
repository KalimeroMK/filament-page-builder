<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Livewire;

use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Rendering\CssGenerator;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;
use KalimeroMK\FilamentPageBuilder\Support\SchemaTreeManipulator;
use Livewire\Attributes\Locked;
use Livewire\Component;

class VisualPageBuilderEditor extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    #[Locked]
    public int | string $pageId;

    /**
     * Working JSON schema in the editor (`version` + `elements`).
     *
     * @var array{version: int, elements: array<int, array<string, mixed>>}
     */
    public array $schema = [
        'version' => 1,
        'elements' => [],
    ];

    public ?string $activeElementId = null;

    public ?string $targetContainerId = null;

    /**
     * Active left sidebar tab: 'widgets' | 'inspector' | 'page_settings'.
     */
    public string $leftTab = 'widgets';

    /**
     * Active responsive canvas viewport: 'desktop' | 'tablet' | 'mobile'.
     */
    public string $viewport = 'desktop';

    /**
     * Live state for the currently selected element's Filament v5 Schema inspector.
     *
     * @var array<string, mixed>
     */
    public array $inspectorData = [];

    /**
     * Page-level settings (Title, Slug, Layout, SEO Meta).
     *
     * @var array<string, mixed>
     */
    public array $pageSettingsData = [];

    public bool $hasUnsavedEditorChanges = false;

    public function mount(Page | int | string $page): void
    {
        $record = $page instanceof Page ? $page : $this->resolvePageModel()::query()->findOrFail($page);

        $this->authorizePageAccess($record);

        $this->pageId = $record->getKey();
        $this->schema = $record->getWorkingSchema();
        $this->fillPageSettingsFromRecord($record);
    }

    public function getPageProperty(): Page
    {
        /** @var Page $record */
        $record = $this->resolvePageModel()::query()->findOrFail($this->pageId);

        return $record;
    }

    protected function authorizePageAccess(Page $page): void
    {
        if (Gate::getPolicyFor($page) !== null) {
            Gate::authorize('update', $page);
        }
    }

    protected function fillPageSettingsFromRecord(Page $record): void
    {
        $this->pageSettingsData = [
            'title' => $record->title,
            'slug' => $record->slug,
            'layout' => $record->layout ?? 'default',
            'meta_title' => $record->meta['meta_title'] ?? '',
            'meta_description' => $record->meta['meta_description'] ?? '',
            'og_image' => $record->meta['og_image'] ?? '',
            'noindex' => (bool) ($record->meta['noindex'] ?? false),
        ];
    }

    /**
     * @return class-string<Page>
     */
    protected function resolvePageModel(): string
    {
        /** @var class-string<Page> $model */
        $model = config('filament-page-builder.models.page', Page::class);

        return $model;
    }

    /**
     * Builds the lazy Filament v5 Schema inspector (Content / Style / Advanced tabs)
     * for the currently selected element (`$activeElementId`).
     */
    public function inspectorSchema(Schema $schema): Schema
    {
        $schema
            ->statePath('inspectorData')
            ->live(debounce: 250);

        if ($this->activeElementId === null) {
            return $schema->components([]);
        }

        $element = SchemaTreeManipulator::findElement($this->schema['elements'], $this->activeElementId);
        if ($element === null) {
            return $schema->components([]);
        }

        $widget = app(WidgetRegistry::class)->get((string) ($element['type'] ?? ''));
        if ($widget === null) {
            return $schema->components([]);
        }

        return $widget->getSchema($schema);
    }

    /**
     * Select an element on the canvas and load its settings into the Filament v5 Inspector Schema.
     */
    public function selectElement(string $elementId): void
    {
        $element = SchemaTreeManipulator::findElement($this->schema['elements'], $elementId);

        if ($element === null) {
            return;
        }

        $widget = app(WidgetRegistry::class)->get((string) ($element['type'] ?? ''));
        $defaults = $widget !== null ? $widget::getDefaultSettings() : [];
        $currentSettings = is_array($element['settings'] ?? null) ? $element['settings'] : [];

        $this->activeElementId = $elementId;
        if ($widget !== null && $widget::isContainer()) {
            $this->targetContainerId = $elementId;
        }

        $this->inspectorData = array_merge($defaults, $currentSettings);
        $this->leftTab = 'inspector';
        $this->clearCachedInspectorSchema();
    }

    protected function clearCachedInspectorSchema(): void
    {
        if (property_exists($this, 'cachedSchemas') && is_array($this->cachedSchemas)) {
            unset($this->cachedSchemas['inspectorSchema']);
        }
    }

    /**
     * Automatically sync any change in `$inspectorData` back into the active element in `$this->schema`.
     */
    public function updatedInspectorData(mixed $value = null, ?string $key = null): void
    {
        if ($this->activeElementId === null) {
            return;
        }

        $this->schema['elements'] = SchemaTreeManipulator::updateElementSettings(
            $this->schema['elements'],
            $this->activeElementId,
            $this->inspectorData,
        );

        $this->hasUnsavedEditorChanges = true;
    }

    public function updatedPageSettingsData(): void
    {
        $this->hasUnsavedEditorChanges = true;
    }

    /**
     * Programmatically update one or more settings on the currently selected element.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateActiveElementSettings(array $settings): void
    {
        if ($this->activeElementId === null) {
            return;
        }

        $this->inspectorData = array_merge($this->inspectorData, $settings);
        $this->updatedInspectorData();
    }

    /**
     * Add a widget of `$type` to the root canvas or inside `$parentContainerId`.
     */
    public function addWidget(string $type, ?string $parentContainerId = null, ?int $position = null): ?string
    {
        $registry = app(WidgetRegistry::class);
        $widget = $registry->get($type);

        if ($widget === null) {
            return null;
        }

        $newId = SchemaTreeManipulator::generateElementId($type);
        $newElement = [
            'id' => $newId,
            'type' => $type,
            'settings' => $widget::getDefaultSettings(),
            'dynamic' => [],
            'children' => [],
        ];

        $containerId = $parentContainerId ?? $this->targetContainerId;

        if ($containerId !== null) {
            $parentEl = SchemaTreeManipulator::findElement($this->schema['elements'], $containerId);
            $parentWidget = $parentEl !== null ? $registry->get((string) ($parentEl['type'] ?? '')) : null;

            if ($parentWidget === null || ! $parentWidget::isContainer()) {
                $containerId = null;
            }
        }

        $this->schema['elements'] = SchemaTreeManipulator::addElement(
            $this->schema['elements'],
            $newElement,
            $containerId,
            $position,
        );

        $this->hasUnsavedEditorChanges = true;
        $this->selectElement($newId);

        return $newId;
    }

    public function setTargetContainer(?string $containerId): void
    {
        if ($containerId === null || $containerId === '') {
            $this->targetContainerId = null;

            return;
        }

        $parentEl = SchemaTreeManipulator::findElement($this->schema['elements'], $containerId);
        $parentWidget = $parentEl !== null ? app(WidgetRegistry::class)->get((string) ($parentEl['type'] ?? '')) : null;

        if ($parentWidget !== null && $parentWidget::isContainer()) {
            $this->targetContainerId = $containerId;
        }
    }

    public function moveElementUp(string $elementId): void
    {
        $this->schema['elements'] = SchemaTreeManipulator::moveElement($this->schema['elements'], $elementId, -1);
        $this->hasUnsavedEditorChanges = true;
    }

    public function moveElementDown(string $elementId): void
    {
        $this->schema['elements'] = SchemaTreeManipulator::moveElement($this->schema['elements'], $elementId, 1);
        $this->hasUnsavedEditorChanges = true;
    }

    public function duplicateElement(string $elementId): void
    {
        $result = SchemaTreeManipulator::duplicateElement($this->schema['elements'], $elementId);
        $this->schema['elements'] = $result['elements'];
        $this->hasUnsavedEditorChanges = true;

        if ($result['cloned_id'] !== null) {
            $this->selectElement($result['cloned_id']);
        }
    }

    public function removeElement(string $elementId): void
    {
        $this->schema['elements'] = SchemaTreeManipulator::removeElement($this->schema['elements'], $elementId);
        $this->hasUnsavedEditorChanges = true;

        if (
            $this->activeElementId !== null &&
            SchemaTreeManipulator::findElement($this->schema['elements'], $this->activeElementId) === null
        ) {
            $this->activeElementId = null;
            $this->inspectorData = [];
            $this->leftTab = 'widgets';
            $this->clearCachedInspectorSchema();
        }

        if (
            $this->targetContainerId !== null &&
            SchemaTreeManipulator::findElement($this->schema['elements'], $this->targetContainerId) === null
        ) {
            $this->targetContainerId = null;
        }
    }

    /**
     * Called by Drag & Drop to reorder siblings.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderElements(array $orderedIds, ?string $parentContainerId = null): void
    {
        $this->schema['elements'] = SchemaTreeManipulator::reorderSiblings(
            $this->schema['elements'],
            $parentContainerId,
            $orderedIds,
        );

        $this->hasUnsavedEditorChanges = true;
    }

    public function setViewport(string $viewport): void
    {
        if (in_array($viewport, ['desktop', 'tablet', 'mobile'], true)) {
            $this->viewport = $viewport;
        }
    }

    public function setLeftTab(string $tab): void
    {
        if (in_array($tab, ['widgets', 'inspector', 'page_settings'], true)) {
            $this->leftTab = $tab;
        }
    }

    /**
     * Save current editor state into `draft_content` without modifying the live published page.
     */
    public function saveDraft(): void
    {
        $page = $this->page;
        $this->authorizePageAccess($page);

        $allowedLayouts = array_keys((array) config('filament-page-builder.layouts', ['default' => 'default']));

        $this->validate([
            'pageSettingsData.title' => ['required', 'string', 'max:255'],
            'pageSettingsData.slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9\-_]+(?:\/[A-Za-z0-9\-_]+)*$/',
                Rule::unique($page->getTable(), 'slug')->ignore($page->getKey()),
            ],
            'pageSettingsData.layout' => ['required', 'string', Rule::in($allowedLayouts)],
            'pageSettingsData.meta_title' => ['nullable', 'string', 'max:255'],
            'pageSettingsData.meta_description' => ['nullable', 'string', 'max:1000'],
            'pageSettingsData.og_image' => ['nullable', 'string', 'max:2048'],
            'pageSettingsData.noindex' => ['boolean'],
        ]);

        $page->title = trim((string) $this->pageSettingsData['title']);
        $page->slug = trim((string) $this->pageSettingsData['slug']);
        $page->layout = (string) $this->pageSettingsData['layout'];

        $rawOgImage = trim((string) ($this->pageSettingsData['og_image'] ?? ''));
        $safeOgImage = $rawOgImage !== '' ? HtmlSanitizer::sanitizeUrl($rawOgImage, '') : '';

        $meta = [
            'meta_title' => (string) ($this->pageSettingsData['meta_title'] ?? ''),
            'meta_description' => (string) ($this->pageSettingsData['meta_description'] ?? ''),
            'og_image' => $safeOgImage,
            'noindex' => (bool) ($this->pageSettingsData['noindex'] ?? false),
        ];

        $page->saveDraft($this->schema, $meta);
        $this->hasUnsavedEditorChanges = false;

        Notification::make()
            ->title('Draft saved')
            ->body('Working changes saved to draft. Use Preview to inspect or Publish to push live.')
            ->success()
            ->send();
    }

    /**
     * Save current working state and publish it to live `content` + create a revision snapshot.
     */
    public function publishPage(): void
    {
        $this->saveDraft();
        $this->page->publish();
        $this->hasUnsavedEditorChanges = false;

        Notification::make()
            ->title('Page published!')
            ->body('Your draft is now live and a revision snapshot has been recorded.')
            ->success()
            ->send();
    }

    /**
     * Discard unpublished changes in `draft_content` and reload from live `content`.
     */
    public function discardDraft(): void
    {
        $page = $this->page;
        $this->authorizePageAccess($page);

        $page = $page->discardDraft();
        $this->schema = $page->getWorkingSchema();
        $this->fillPageSettingsFromRecord($page);
        $this->activeElementId = null;
        $this->targetContainerId = null;
        $this->inspectorData = [];
        $this->clearCachedInspectorSchema();
        $this->hasUnsavedEditorChanges = false;

        Notification::make()
            ->title('Draft discarded')
            ->body('Editor reset to the currently published version.')
            ->info()
            ->send();
    }

    public function render(): View
    {
        $registry = app(WidgetRegistry::class);
        $renderer = app(PageRenderer::class);
        $cssGenerator = app(CssGenerator::class);

        $page = $this->page;
        $renderedPreview = $renderer->renderSchema($this->schema, [
            'page' => $page,
            'is_preview' => true,
            'is_editor' => true,
        ]);

        return view('filament-page-builder::livewire.visual-page-builder-editor', [
            'page' => $page,
            'widgetsByCategory' => $registry->byCategory(),
            'widgetDefinitions' => $registry->toArray(),
            'activeElement' => $this->activeElementId !== null
                ? SchemaTreeManipulator::findElement($this->schema['elements'], $this->activeElementId)
                : null,
            'generatedCss' => $renderedPreview->css ?: $cssGenerator->baseStyles(),
            'previewUrl' => $page->getPreviewUrl(),
        ]);
    }
}
