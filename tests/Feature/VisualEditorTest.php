<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use Livewire\Livewire;

it('registers and renders all v0.2 widgets (image, spacer, icon, video)', function (): void {
    $registry = PageBuilder::widgets();

    expect($registry->has('image'))->toBeTrue()
        ->and($registry->has('spacer'))->toBeTrue()
        ->and($registry->has('icon'))->toBeTrue()
        ->and($registry->has('video'))->toBeTrue();

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'img_1',
                'type' => 'image',
                'settings' => [
                    'src' => 'https://example.com/banner.jpg',
                    'alt' => 'Promo Banner',
                    'caption' => 'Слика за промоција',
                    'link' => '/promo',
                    'border_radius' => 12,
                ],
            ],
            [
                'id' => 'spc_1',
                'type' => 'spacer',
                'settings' => [
                    'space' => 60,
                    'space_mobile' => 24,
                    'show_divider' => true,
                    'divider_style' => 'dashed',
                ],
            ],
            [
                'id' => 'icn_1',
                'type' => 'icon',
                'settings' => [
                    'icon' => 'heroicon-o-rocket-launch',
                    'icon_size' => 48,
                    'icon_color' => '#16a34a',
                ],
            ],
            [
                'id' => 'vid_1',
                'type' => 'video',
                'settings' => [
                    'source' => 'youtube',
                    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'autoplay' => true,
                    'mute' => true,
                ],
            ],
        ],
    ]);

    expect($rendered->html)
        ->toContain('src="https://example.com/banner.jpg"')
        ->toContain('alt="Promo Banner"')
        ->toContain('Слика за промоција')
        ->toContain('class="fpb-spacer-line"')
        ->toContain('<svg')
        ->toContain('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&amp;mute=1');

    expect($rendered->css)
        ->toContain('.fpb-el-spc_1 {')
        ->toContain('--fpb-space: 60px;')
        ->toContain('@container fpb-canvas (max-width: 767px) { .fpb-el-spc_1 { --fpb-space: 24px; } }');
});

it('builds pages interactively in VisualPageBuilderEditor with drag-and-drop reordering, inspector editing, Save Draft, Publish, and Discard Draft', function (): void {
    $page = Page::create([
        'title' => 'Визуелна Страница',
        'slug' => 'visual-page',
    ]);

    $component = Livewire::test(VisualPageBuilderEditor::class, ['page' => $page])
        ->assertOk()
        ->assertSee('Your Page Canvas is Empty');

    // 1. Add a root Flex Container
    $component->call('addWidget', 'container');
    $rootContainerId = $component->get('activeElementId');

    expect($rootContainerId)->toStartWith('container_')
        ->and($component->get('targetContainerId'))->toBe($rootContainerId)
        ->and($component->get('leftTab'))->toBe('inspector');

    // 2. Add a Heading and a Button inside the target Container
    $component->call('addWidget', 'heading', $rootContainerId);
    $headingId = $component->get('activeElementId');

    $component->call('updateActiveElementSettings', [
        'text' => 'Наслов од Визуелниот Едитор',
        'tag' => 'h1',
        'font_size' => 48,
    ]);

    $component->call('addWidget', 'button', $rootContainerId);
    $buttonId = $component->get('activeElementId');

    $component->call('updateActiveElementSettings', [
        'label' => 'Контакт',
        'link' => '/contact',
    ]);

    // 3. Reorder children inside the container (button first, heading second)
    $component->call('reorderElements', [$buttonId, $headingId], $rootContainerId);

    $schemaAfterReorder = $component->get('schema');
    $childrenIds = array_column($schemaAfterReorder['elements'][0]['children'], 'id');
    expect($childrenIds)->toBe([$buttonId, $headingId]);

    // 4. Switch responsive viewport in the editor
    $component->call('setViewport', 'mobile')
        ->assertSet('viewport', 'mobile')
        ->assertSee('max-width: 375px', false);

    // 5. Save Draft (writes only to draft_content, page remains Draft)
    $component->call('saveDraft')
        ->assertSet('hasUnsavedEditorChanges', false);

    $page->refresh();
    expect($page->isDraft())->toBeTrue()
        ->and($page->content)->toBeNull()
        ->and($page->hasUnpublishedChanges())->toBeTrue();

    // Preview route renders the new visual editor draft!
    $this->get($page->getPreviewUrl())
        ->assertOk()
        ->assertSee('Наслов од Визуелниот Едитор')
        ->assertSee('Контакт');

    // Public route is still 404 until published
    $this->get('/pages/visual-page')->assertNotFound();

    // 6. Publish directly from the Visual Editor
    $component->call('publishPage');

    $page->refresh();
    expect($page->isPublished())->toBeTrue()
        ->and($page->hasUnpublishedChanges())->toBeFalse()
        ->and($page->revisions()->count())->toBe(1);

    // Public route now serves the visually built page!
    $this->get('/pages/visual-page')
        ->assertOk()
        ->assertSee('Наслов од Визуелниот Едитор')
        ->assertSee('Контакт');

    // 7. Duplicate element, save draft, then discard draft from Visual Editor
    $component->call('duplicateElement', $headingId)
        ->call('saveDraft');

    $page->refresh();
    expect($page->hasUnpublishedChanges())->toBeTrue()
        ->and(count($page->getWorkingSchema()['elements'][0]['children']))->toBe(3);

    $component->call('discardDraft');
    $page->refresh();
    expect($page->hasUnpublishedChanges())->toBeFalse()
        ->and(count($page->getWorkingSchema()['elements'][0]['children']))->toBe(2);
});
