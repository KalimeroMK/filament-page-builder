<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Livewire;

use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageRevision;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Registries\TemplateRegistry;
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

    protected const MAX_HISTORY_STEPS = 30;

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

    /**
     * Undo history stack of `elements` snapshots.
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    #[Locked]
    public array $historyStack = [];

    /**
     * Redo stack of `elements` snapshots.
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    #[Locked]
    public array $redoStack = [];

    /**
     * Copied element in clipboard for Copy/Paste.
     *
     * @var array<string, mixed>|null
     */
    #[Locked]
    public ?array $clipboardElement = null;

    #[Locked]
    public ?string $activeElementId = null;

    #[Locked]
    public ?string $targetContainerId = null;

    /**
     * Active left sidebar tab: 'widgets' | 'inspector' | 'templates' | 'revisions' | 'page_settings'.
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
     * Dynamic tag bindings for the currently selected element.
     *
     * @var array<string, mixed>
     */
    public array $dynamicData = [];

    /**
     * Page-level settings (Title, Slug, Layout, SEO Meta).
     *
     * @var array<string, mixed>
     */
    public array $pageSettingsData = [];

    public string $newTemplateName = '';

    public string $newTemplateCategory = 'section';

    public string $importJsonPayload = '';

    /**
     * Currently compared revision ID and its diff result against the current editor schema.
     */
    public ?int $compareRevisionId = null;

    /**
     * @var array{added: int, removed: int, changed: int, unchanged: int, by_type: array<string, array{added: int, removed: int, changed: int}>, changed_ids: array<int, string>}|null
     */
    public ?array $revisionDiff = null;

    #[Locked]
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
     * @return class-string<PageTemplate>
     */
    protected function resolveTemplateModel(): string
    {
        /** @var class-string<PageTemplate> $model */
        $model = config('filament-page-builder.models.template', PageTemplate::class);

        return $model;
    }

    /**
     * Commit a new elements array into `$this->schema['elements']` only if it actually changed,
     * pushing the previous state onto `$this->historyStack`.
     *
     * @param  array<int, array<string, mixed>>  $newElements
     */
    protected function applyElementsMutation(array $newElements): bool
    {
        $normalizedNew = Page::normalizeSchema(['version' => 1, 'elements' => $newElements])['elements'];

        if ($normalizedNew === $this->schema['elements']) {
            return false;
        }

        $this->historyStack[] = $this->schema['elements'];

        if (count($this->historyStack) > self::MAX_HISTORY_STEPS) {
            array_shift($this->historyStack);
        }

        $this->redoStack = [];
        $this->schema['elements'] = $normalizedNew;
        $this->hasUnsavedEditorChanges = true;

        return true;
    }

    public function canUndo(): bool
    {
        return $this->historyStack !== [];
    }

    public function canRedo(): bool
    {
        return $this->redoStack !== [];
    }

    public function undo(): void
    {
        if (! $this->canUndo()) {
            return;
        }

        $previousElements = array_pop($this->historyStack);
        $this->redoStack[] = $this->schema['elements'];
        $this->schema['elements'] = Page::normalizeSchema([
            'version' => 1,
            'elements' => is_array($previousElements) ? $previousElements : [],
        ])['elements'];
        $this->hasUnsavedEditorChanges = true;
        $this->syncSelectionAfterTreeChange();
    }

    public function redo(): void
    {
        if (! $this->canRedo()) {
            return;
        }

        $nextElements = array_pop($this->redoStack);
        $this->historyStack[] = $this->schema['elements'];
        $this->schema['elements'] = Page::normalizeSchema([
            'version' => 1,
            'elements' => is_array($nextElements) ? $nextElements : [],
        ])['elements'];
        $this->hasUnsavedEditorChanges = true;
        $this->syncSelectionAfterTreeChange();
    }

    protected function syncSelectionAfterTreeChange(): void
    {
        if ($this->activeElementId !== null) {
            $el = SchemaTreeManipulator::findElement($this->schema['elements'], $this->activeElementId);
            if ($el === null) {
                $this->activeElementId = null;
                $this->inspectorData = [];
                $this->clearCachedInspectorSchema();
            } else {
                $widget = app(WidgetRegistry::class)->get((string) ($el['type'] ?? ''));
                $defaults = $widget !== null ? $widget::getDefaultSettings() : [];
                $this->inspectorData = array_merge($defaults, is_array($el['settings'] ?? null) ? $el['settings'] : []);
                $this->clearCachedInspectorSchema();
            }
        }

        if (
            $this->targetContainerId !== null &&
            SchemaTreeManipulator::findElement($this->schema['elements'], $this->targetContainerId) === null
        ) {
            $this->targetContainerId = null;
        }
    }

    /**
     * Copy an element into the editor clipboard.
     */
    public function copyElement(string $elementId): void
    {
        $element = SchemaTreeManipulator::findElement($this->schema['elements'], $elementId);

        if ($element === null) {
            return;
        }

        $this->clipboardElement = $element;

        Notification::make()
            ->title('Element copied to clipboard')
            ->info()
            ->send();
    }

    /**
     * Paste the copied element into `$parentContainerId`, `$targetContainerId`, or root.
     */
    public function pasteElement(?string $parentContainerId = null): ?string
    {
        if ($this->clipboardElement === null) {
            return null;
        }

        $cloned = SchemaTreeManipulator::cloneWithFreshIds($this->clipboardElement);
        $newId = (string) $cloned['id'];

        $containerId = $parentContainerId ?? $this->targetContainerId;
        if ($containerId !== null) {
            $parentEl = SchemaTreeManipulator::findElement($this->schema['elements'], $containerId);
            $parentWidget = $parentEl !== null ? app(WidgetRegistry::class)->get((string) ($parentEl['type'] ?? '')) : null;

            if ($parentWidget === null || ! $parentWidget::isContainer()) {
                $containerId = null;
            }
        }

        $updated = SchemaTreeManipulator::addElement(
            $this->schema['elements'],
            $cloned,
            $containerId,
        );

        $this->applyElementsMutation($updated);
        $this->selectElement($newId);

        return $newId;
    }

    /**
     * Save either the whole page schema or only the currently selected element as a reusable PageTemplate.
     */
    public function saveAsTemplate(bool $onlyActiveElement = false): ?int
    {
        $this->authorizePageAccess($this->page);

        $this->validate([
            'newTemplateName' => ['required', 'string', 'max:255'],
            'newTemplateCategory' => ['required', 'string', 'max:64'],
        ]);

        if ($onlyActiveElement) {
            if ($this->activeElementId === null) {
                return null;
            }

            $activeEl = SchemaTreeManipulator::findElement($this->schema['elements'], $this->activeElementId);
            if ($activeEl === null) {
                return null;
            }

            $elementsToSave = [$activeEl];
        } else {
            $elementsToSave = $this->schema['elements'];
        }

        $templateModel = $this->resolveTemplateModel();

        /** @var PageTemplate $template */
        $template = $templateModel::create([
            'name' => trim($this->newTemplateName),
            'category' => trim($this->newTemplateCategory) ?: 'section',
            'schema' => [
                'version' => 1,
                'elements' => $elementsToSave,
            ],
            'created_by' => Auth::check() ? (int) Auth::id() : null,
        ]);

        $this->newTemplateName = '';

        Notification::make()
            ->title('Template saved to library!')
            ->success()
            ->send();

        return (int) $template->getKey();
    }

    /**
     * Insert or replace the canvas with a saved database PageTemplate.
     */
    public function applyDatabaseTemplate(int $templateId, bool $append = true): void
    {
        $this->authorizePageAccess($this->page);

        $templateModel = $this->resolveTemplateModel();

        /** @var PageTemplate $template */
        $template = $templateModel::query()->findOrFail($templateId);

        $this->applyNormalizedTemplateSchema(
            Page::normalizeSchema(is_array($template->schema) ? $template->schema : []),
            $append,
        );
    }

    /**
     * Insert or replace the canvas with a code-registered template from TemplateRegistry.
     */
    public function applyRegisteredTemplate(string $templateKey, bool $append = true): void
    {
        $this->authorizePageAccess($this->page);

        $template = app(TemplateRegistry::class)->get($templateKey);
        if ($template === null) {
            return;
        }

        $this->applyNormalizedTemplateSchema(
            Page::normalizeSchema($template['schema']),
            $append,
        );
    }

    /**
     * @param  array{version: int, elements: array<int, array<string, mixed>>}  $templateSchema
     */
    protected function applyNormalizedTemplateSchema(array $templateSchema, bool $append): void
    {
        $freshElements = array_values(array_map(
            fn (array $el): array => SchemaTreeManipulator::cloneWithFreshIds($el),
            $templateSchema['elements'],
        ));

        $nextElements = $append
            ? array_values(array_merge($this->schema['elements'], $freshElements))
            : $freshElements;

        $this->applyElementsMutation($nextElements);
        $this->syncSelectionAfterTreeChange();
    }

    /**
     * Export the current working schema as a JSON string and populate the JSON box in the UI.
     */
    public function exportCurrentSchemaJson(): string
    {
        $this->authorizePageAccess($this->page);

        $json = (string) json_encode($this->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->importJsonPayload = $json;

        return $json;
    }

    /**
     * Import a JSON schema payload into the visual editor (either replacing or appending).
     */
    public function importSchemaFromJson(?string $json = null, bool $append = false): void
    {
        $this->authorizePageAccess($this->page);

        $payload = $json ?? $this->importJsonPayload;
        $decoded = json_decode(trim($payload), true);

        if (! is_array($decoded) || ! PageTemplate::isValidSchemaPayload($decoded)) {
            $this->addError('importJsonPayload', 'Invalid JSON schema payload: must contain an elements array.');

            return;
        }

        $rawSchema = is_array($decoded['schema'] ?? null) ? $decoded['schema'] : $decoded;
        $normalized = Page::normalizeSchema($rawSchema);

        $this->applyNormalizedTemplateSchema($normalized, $append);
        $this->importJsonPayload = '';

        Notification::make()
            ->title('JSON schema imported into editor')
            ->success()
            ->send();
    }

    /**
     * Restore a historical PageRevision into the visual editor and draft_content with Undo support.
     */
    public function restoreRevisionInEditor(int $revisionId): void
    {
        $page = $this->page;
        $this->authorizePageAccess($page);

        /** @var PageRevision $revision */
        $revision = $page->revisions()->findOrFail($revisionId);

        $restoredSchema = Page::normalizeSchema($revision->schema_snapshot);
        $this->applyElementsMutation($restoredSchema['elements']);

        $page->restoreRevision($revision);
        $this->fillPageSettingsFromRecord($page);
        $this->hasUnsavedEditorChanges = false;
        $this->syncSelectionAfterTreeChange();

        Notification::make()
            ->title("Restored Revision #{$revision->version_number} into Draft")
            ->info()
            ->send();
    }

    /**
     * Compare a historical revision against the current editor schema (added/removed/changed elements).
     */
    public function compareRevision(int $revisionId): void
    {
        $page = $this->page;
        $this->authorizePageAccess($page);

        /** @var PageRevision $revision */
        $revision = $page->revisions()->findOrFail($revisionId);

        $oldElements = \KalimeroMK\FilamentPageBuilder\Models\Page::normalizeSchema($revision->schema_snapshot)['elements'];

        $this->compareRevisionId = $revisionId;
        $this->revisionDiff = app(\KalimeroMK\FilamentPageBuilder\Services\SchemaDiffService::class)
            ->compare($oldElements, $this->schema['elements']);
    }

    public function clearRevisionComparison(): void
    {
        $this->compareRevisionId = null;
        $this->revisionDiff = null;
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
        $this->dynamicData = is_array($element['dynamic'] ?? null) ? $element['dynamic'] : [];
        $this->leftTab = 'inspector';
        $this->clearCachedInspectorSchema();
    }

    /**
     * Bind a widget setting key to a dynamic tag descriptor (e.g. "model.attribute:title").
     */
    public function updateDynamicBinding(string $settingKey, string $descriptor): void
    {
        if ($this->activeElementId === null) {
            return;
        }

        $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $settingKey) ?? '';
        if ($safeKey === '') {
            return;
        }

        $trimmed = trim($descriptor);
        if ($trimmed === '') {
            unset($this->dynamicData[$safeKey]);
        } else {
            $this->dynamicData[$safeKey] = $trimmed;
        }

        $updated = SchemaTreeManipulator::updateElementDynamic(
            $this->schema['elements'],
            $this->activeElementId,
            $this->dynamicData,
        );

        $this->applyElementsMutation($updated);
    }

    public function clearDynamicBinding(string $settingKey): void
    {
        if ($this->activeElementId === null) {
            return;
        }

        $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $settingKey) ?? '';
        unset($this->dynamicData[$safeKey]);

        $updated = SchemaTreeManipulator::updateElementDynamic(
            $this->schema['elements'],
            $this->activeElementId,
            $this->dynamicData,
        );

        $this->applyElementsMutation($updated);
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

        $updatedElements = SchemaTreeManipulator::updateElementSettings(
            $this->schema['elements'],
            $this->activeElementId,
            $this->inspectorData,
        );

        $this->applyElementsMutation($updatedElements);
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

        $updatedElements = SchemaTreeManipulator::addElement(
            $this->schema['elements'],
            $newElement,
            $containerId,
            $position,
        );

        $this->applyElementsMutation($updatedElements);
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
        $updated = SchemaTreeManipulator::moveElement($this->schema['elements'], $elementId, -1);
        $this->applyElementsMutation($updated);
    }

    public function moveElementDown(string $elementId): void
    {
        $updated = SchemaTreeManipulator::moveElement($this->schema['elements'], $elementId, 1);
        $this->applyElementsMutation($updated);
    }

    public function duplicateElement(string $elementId): void
    {
        $result = SchemaTreeManipulator::duplicateElement($this->schema['elements'], $elementId);

        if ($this->applyElementsMutation($result['elements']) && $result['cloned_id'] !== null) {
            $this->selectElement($result['cloned_id']);
        }
    }

    public function removeElement(string $elementId): void
    {
        $updated = SchemaTreeManipulator::removeElement($this->schema['elements'], $elementId);

        if ($this->applyElementsMutation($updated)) {
            $this->syncSelectionAfterTreeChange();

            if ($this->activeElementId === null) {
                $this->leftTab = 'widgets';
            }
        }
    }

    /**
     * Called by Drag & Drop to reorder siblings.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderElements(array $orderedIds, ?string $parentContainerId = null): void
    {
        $updated = SchemaTreeManipulator::reorderSiblings(
            $this->schema['elements'],
            $parentContainerId,
            $orderedIds,
        );

        $this->applyElementsMutation($updated);
    }

    public function toggleElementVisibility(string $elementId): void
    {
        $element = SchemaTreeManipulator::findElement($this->schema['elements'], $elementId);
        if ($element === null) {
            return;
        }

        $isCurrentlyHidden = ! empty($element['settings']['hide_element']);
        $updated = SchemaTreeManipulator::updateElementSettings(
            $this->schema['elements'],
            $elementId,
            ['hide_element' => ! $isCurrentlyHidden],
        );

        if ($this->applyElementsMutation($updated) && $this->activeElementId === $elementId) {
            $this->inspectorData['hide_element'] = ! $isCurrentlyHidden;
        }
    }

    public function setViewport(string $viewport): void
    {
        if (in_array($viewport, ['desktop', 'tablet', 'mobile'], true)) {
            $this->viewport = $viewport;
        }
    }

    public function setLeftTab(string $tab): void
    {
        if (in_array($tab, ['widgets', 'inspector', 'navigator', 'templates', 'revisions', 'page_settings'], true)) {
            $this->leftTab = $tab;
        }
    }

    /**
     * Validate and persist the working editor state into `draft_content`.
     */
    protected function persistDraft(): Page
    {
        $page = $this->page;
        $this->authorizePageAccess($page);

        $allowedLayouts = array_keys((array) config('filament-page-builder.layouts', ['default' => 'default']));

        try {
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
        } catch (ValidationException $e) {
            $this->leftTab = 'page_settings';

            throw $e;
        }

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

        return $page;
    }

    /**
     * Save current editor state into `draft_content` without modifying the live published page.
     */
    public function saveDraft(): void
    {
        $this->persistDraft();

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
        $page = $this->persistDraft();
        $page->publish();
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
        $restored = $page->getWorkingSchema();
        $this->applyElementsMutation($restored['elements']);
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
        $templateRegistry = app(TemplateRegistry::class);
        $templateModel = $this->resolveTemplateModel();

        $page = $this->page;
        $generatedCss = $renderer->generateCssForSchema($this->schema, [
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
            'generatedCss' => $generatedCss ?: $cssGenerator->baseStyles(),
            'previewUrl' => $page->getPreviewUrl(),
            'savedTemplates' => $templateModel::query()->latest()->get(),
            'registeredTemplates' => $templateRegistry->all(),
            'revisions' => $page->revisions()->limit(20)->get(),
            'availableLayouts' => array_keys((array) config('filament-page-builder.layouts', ['default' => 'default'])),
            'availableDynamicTags' => $this->getAvailableDynamicTags(),
        ]);
    }

    /**
     * @return array<string, array{name: string, label: string, group: string}>
     */
    protected function getAvailableDynamicTags(): array
    {
        $tags = app(\KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry::class)->all();
        $result = [];

        foreach ($tags as $name => $tag) {
            $result[$name] = [
                'name' => $name,
                'label' => $tag::getLabel(),
                'group' => $tag::getGroup(),
            ];
        }

        return $result;
    }
}
