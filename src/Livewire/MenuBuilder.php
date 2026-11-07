<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use KalimeroMK\FilamentPageBuilder\Models\Menu;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MenuBuilder extends Component
{
    #[Locked]
    public int | string $menuId;

    public array $items = [];

    public array $expandedKeys = [];

    public array $titleData = [];

    public array $selectedPages = [];

    public string $availablePagesSearch = '';

    public string $panelTab = 'recent';

    public bool $selectAllPages = false;

    public array $customLink = ['label' => '', 'url' => ''];

    public function mount(Menu | int | string $menu): void
    {
        abort_unless(PageBuilderAuthorizer::can('menus'), 403);

        $record = $menu instanceof Menu ? $menu : $this->resolveMenuModel()::query()->findOrFail($menu);
        $this->menuId = $record->getKey();
        $this->titleData = ['title' => $record->title, 'slug' => $record->slug];
        $this->items = $this->prepareItems($record->items ?? []);
        $this->expandedKeys = [];
    }

    public function getMenuProperty(): Menu
    {
        return $this->resolveMenuModel()::query()->findOrFail($this->menuId);
    }

    public function addItem(): void
    {
        $item = $this->newItem();
        $this->items[] = $item;
        $this->expandedKeys[] = $item['_key'];
    }

    public function setPanelTab(string $tab): void
    {
        if (! in_array($tab, ['recent', 'all', 'search'], true)) {
            return;
        }

        $this->panelTab = $tab;
        $this->selectAllPages = false;
        $this->selectedPages = [];
    }

    public function toggleSelectAll(bool $checked): void
    {
        $this->selectAllPages = $checked;
        $this->selectedPages = $checked ? array_keys($this->availablePages()->all()) : [];
    }

    public function addSelectedPages(): void
    {
        if ($this->selectedPages === []) {
            return;
        }

        $pages = Page::query()->whereIn('slug', $this->selectedPages)->get();

        foreach ($pages as $page) {
            $item = $this->newItem();
            $item['label'] = $page->title;
            $item['url'] = '/pages/'.$page->slug;
            $item['link_type'] = 'page';
            $item['page_slug'] = $page->slug;
            $this->items[] = $item;
            $this->expandedKeys[] = $item['_key'];
        }

        $this->selectedPages = [];
        $this->selectAllPages = false;
    }

    public function addCustomLink(): void
    {
        $label = trim((string) ($this->customLink['label'] ?? ''));
        $url = trim((string) ($this->customLink['url'] ?? ''));

        if ($label === '' || $url === '') {
            $this->addError('customLink', 'Enter both a label and URL for the custom link.');

            return;
        }

        $item = $this->newItem();
        $item['label'] = $label;
        $item['url'] = $url;
        $this->items[] = $item;
        $this->expandedKeys[] = $item['_key'];
        $this->customLink = ['label' => '', 'url' => ''];
    }

    public function deleteMenu()
    {
        abort_unless(PageBuilderAuthorizer::can('menus'), 403);

        $this->menu->delete();

        return redirect()->to(\KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource::getUrl('index'));
    }

    public function addChild(int $parentIndex): void
    {
        if (! isset($this->items[$parentIndex])) {
            return;
        }

        $item = $this->newItem();
        $this->items[$parentIndex]['children'][] = $item;
        $this->expandedKeys[] = $item['_key'];
        $this->expandedKeys[] = $this->items[$parentIndex]['_key'];
        $this->expandedKeys = array_values(array_unique($this->expandedKeys));
    }

    public function removeItem(string $key, ?int $parentIndex = null): void
    {
        $items = &$this->itemsAt($parentIndex);
        $index = $this->findIndex($items, $key);

        if ($index !== null) {
            array_splice($items, $index, 1);
        }

        $this->expandedKeys = array_values(array_diff($this->expandedKeys, [$key]));
    }

    public function toggleItem(string $key): void
    {
        if (in_array($key, $this->expandedKeys, true)) {
            $this->expandedKeys = array_values(array_diff($this->expandedKeys, [$key]));

            return;
        }

        $this->expandedKeys[] = $key;
    }

    public function setLinkType(string $key, string $type, ?int $parentIndex = null): void
    {
        $items = &$this->itemsAt($parentIndex);
        $index = $this->findIndex($items, $key);

        if ($index === null) {
            return;
        }

        $items[$index]['link_type'] = $type === 'page' ? 'page' : 'url';

        if ($items[$index]['link_type'] === 'url') {
            $items[$index]['page_slug'] = null;
        }
    }

    public function selectPage(string $key, string $slug, ?int $parentIndex = null): void
    {
        $items = &$this->itemsAt($parentIndex);
        $index = $this->findIndex($items, $key);
        $page = Page::query()->where('slug', $slug)->first();

        if ($index === null || $page === null) {
            return;
        }

        $items[$index]['link_type'] = 'page';
        $items[$index]['page_slug'] = $page->slug;
        $items[$index]['url'] = '/pages/'.$page->slug;

        if (trim((string) $items[$index]['label']) === '') {
            $items[$index]['label'] = $page->title;
        }
    }

    public function moveRelative(string $key, ?int $parentIndex, int $direction): void
    {
        $items = &$this->itemsAt($parentIndex);
        $index = $this->findIndex($items, $key);

        if ($index === null) {
            return;
        }

        $targetIndex = $index + $direction;

        if (! isset($items[$targetIndex])) {
            return;
        }

        [$items[$index], $items[$targetIndex]] = [$items[$targetIndex], $items[$index]];
    }

    public function moveItem(string $draggedKey, ?int $draggedParent, ?string $targetKey, ?int $targetParent): void
    {
        if ($draggedKey === $targetKey && $draggedParent === $targetParent) {
            return;
        }

        $sourceItems = &$this->itemsAt($draggedParent);
        $sourceIndex = $this->findIndex($sourceItems, $draggedKey);

        if ($sourceIndex === null) {
            return;
        }

        $draggedItem = $sourceItems[$sourceIndex];

        if ($targetParent !== null && ! isset($this->items[$targetParent])) {
            return;
        }

        if ($draggedParent === null && $targetParent === $sourceIndex) {
            return;
        }

        if ($draggedParent === null && $targetParent !== null && ! empty($draggedItem['children'])) {
            return;
        }

        array_splice($sourceItems, $sourceIndex, 1);

        if ($draggedParent === null && $targetParent !== null && $targetParent > $sourceIndex) {
            $targetParent--;
        }

        $targetItems = &$this->itemsAt($targetParent);

        if ($targetKey === null) {
            $targetItems[] = $draggedItem;

            return;
        }

        $targetIndex = $this->findIndex($targetItems, $targetKey);

        if ($targetIndex === null) {
            $targetItems[] = $draggedItem;

            return;
        }

        array_splice($targetItems, $targetIndex, 0, [$draggedItem]);
    }

    public function save(): void
    {
        abort_unless(PageBuilderAuthorizer::can('menus'), 403);

        $menu = $this->menu;
        $this->validate([
            'titleData.title' => ['required', 'string', 'max:255'],
            'titleData.slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique($menu->getTable(), 'slug')->ignore($menu->getKey()),
            ],
        ]);

        $menu->title = trim((string) $this->titleData['title']);
        $menu->slug = trim((string) $this->titleData['slug']);
        $menu->items = $this->cleanItems($this->items);
        $menu->save();

        $this->titleData = ['title' => $menu->title, 'slug' => $menu->slug];
        $this->items = $this->prepareItems($menu->fresh()->items ?? []);

        Notification::make()
            ->title('Menu saved')
            ->body('Menu items, hierarchy and links are updated.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('filament-page-builder::livewire.menu-builder', [
            'pages' => $this->availablePages(),
        ]);
    }

    private function availablePages(): \Illuminate\Support\Collection
    {
        $search = trim($this->availablePagesSearch);

        if ($this->panelTab === 'search') {
            if ($search === '') {
                return collect();
            }

            return Page::query()
                ->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                })
                ->orderBy('title')
                ->pluck('title', 'slug');
        }

        return Page::query()
            ->when($this->panelTab === 'recent', fn ($query) => $query->orderByDesc('updated_at')->limit(10))
            ->when($this->panelTab === 'all', fn ($query) => $query->orderBy('title'))
            ->pluck('title', 'slug');
    }

    private function prepareItems(array $items): array
    {
        $prepared = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = (string) ($item['url'] ?? '');
            $isPage = str_starts_with($url, '/pages/');
            $prepared[] = [
                '_key' => (string) Str::uuid(),
                'label' => (string) ($item['label'] ?? ''),
                'url' => $url,
                'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'link_type' => $isPage ? 'page' : 'url',
                'page_slug' => $isPage ? substr($url, 7) : null,
                'children' => $this->prepareItems((array) ($item['children'] ?? [])),
            ];
        }

        return $prepared;
    }

    private function newItem(): array
    {
        return [
            '_key' => (string) Str::uuid(),
            'label' => '',
            'url' => '',
            'target' => '_self',
            'link_type' => 'url',
            'page_slug' => null,
            'children' => [],
        ];
    }

    private function cleanItems(array $items): array
    {
        return array_values(array_map(function (array $item): array {
            return [
                'label' => trim((string) ($item['label'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
                'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'children' => $this->cleanItems((array) ($item['children'] ?? [])),
            ];
        }, $items));
    }

    private function &itemsAt(?int $parentIndex): array
    {
        if ($parentIndex === null) {
            return $this->items;
        }

        return $this->items[$parentIndex]['children'];
    }

    private function findIndex(array $items, string $key): ?int
    {
        foreach ($items as $index => $item) {
            if (($item['_key'] ?? null) === $key) {
                return $index;
            }
        }

        return null;
    }

    private function resolveMenuModel(): string
    {
        /** @var class-string<Menu> $model */
        $model = config('filament-page-builder.models.menu', Menu::class);

        return $model;
    }
}
