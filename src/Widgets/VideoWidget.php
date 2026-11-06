<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;

class VideoWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'video';
    }

    public static function getLabel(): string
    {
        return 'Video';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-video-camera';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'source' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'autoplay' => false,
            'mute' => false,
            'loop' => false,
            'controls' => true,
            'aspect_ratio' => '16 / 9',
            'border_radius' => 8,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'aspect_ratio' => ['var' => '--fpb-aspect-ratio'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('source')
                    ->label('Video Source')
                    ->options([
                        'youtube' => 'YouTube',
                        'vimeo' => 'Vimeo',
                        'self_hosted' => 'Self-Hosted (MP4/WebM)',
                    ])
                    ->default('youtube')
                    ->required(),
                TextInput::make('url')
                    ->label('Video URL')
                    ->required()
                    ->default('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
            ]),
            Grid::make(4)->schema([
                Toggle::make('autoplay')
                    ->label('Autoplay')
                    ->default(false),
                Toggle::make('mute')
                    ->label('Mute')
                    ->default(false),
                Toggle::make('loop')
                    ->label('Loop')
                    ->default(false),
                Toggle::make('controls')
                    ->label('Player Controls')
                    ->default(true),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('aspect_ratio')
                    ->label('Aspect Ratio')
                    ->options([
                        '16 / 9' => '16:9 (Widescreen)',
                        '4 / 3' => '4:3 (Standard)',
                        '21 / 9' => '21:9 (Ultrawide)',
                        '1 / 1' => '1:1 (Square)',
                    ])
                    ->default('16 / 9'),
                TextInput::make('border_radius')
                    ->label('Border Radius (px)')
                    ->numeric()
                    ->default(8),
            ]),
        ];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $settings['resolved_embed_url'] = $this->resolveEmbedUrl($settings);

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function resolveEmbedUrl(array $settings): string
    {
        $source = (string) ($settings['source'] ?? 'youtube');
        $rawUrl = trim((string) ($settings['url'] ?? ''));

        if ($source === 'youtube') {
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $rawUrl, $matches)) {
                $videoId = $matches[1];
                $params = [
                    'autoplay' => ! empty($settings['autoplay']) ? '1' : '0',
                    'mute' => ! empty($settings['mute']) ? '1' : '0',
                    'loop' => ! empty($settings['loop']) ? '1' : '0',
                    'controls' => ! isset($settings['controls']) || ! empty($settings['controls']) ? '1' : '0',
                ];

                return "https://www.youtube-nocookie.com/embed/{$videoId}?" . http_build_query($params);
            }

            return '';
        }

        if ($source === 'vimeo') {
            if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $rawUrl, $matches)) {
                $videoId = $matches[1];
                $params = [
                    'autoplay' => ! empty($settings['autoplay']) ? '1' : '0',
                    'muted' => ! empty($settings['mute']) ? '1' : '0',
                    'loop' => ! empty($settings['loop']) ? '1' : '0',
                ];

                return "https://player.vimeo.com/video/{$videoId}?" . http_build_query($params);
            }

            return '';
        }

        return HtmlSanitizer::sanitizeUrl($rawUrl, '');
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.video';
    }
}
