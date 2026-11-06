<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Registries;

use InvalidArgumentException;
use KalimeroMK\FilamentPageBuilder\Contracts\WidgetContract;

class WidgetRegistry
{
    /**
     * @var array<string, WidgetContract>
     */
    protected array $widgets = [];

    /**
     * Register a widget class or instance into the central registry.
     *
     * @param  class-string<WidgetContract>|WidgetContract  $widget
     */
    public function register(string | WidgetContract $widget): self
    {
        $instance = is_string($widget) ? app($widget) : $widget;

        if (! $instance instanceof WidgetContract) {
            throw new InvalidArgumentException('Widget must implement ' . WidgetContract::class);
        }

        $this->widgets[$instance::getType()] = $instance;

        return $this;
    }

    public function has(string $type): bool
    {
        return isset($this->widgets[$type]);
    }

    public function get(string $type): ?WidgetContract
    {
        return $this->widgets[$type] ?? null;
    }

    /**
     * @return array<string, WidgetContract>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * @return array<string, array<string, WidgetContract>>
     */
    public function byCategory(): array
    {
        $grouped = [];

        foreach ($this->widgets as $type => $widget) {
            $grouped[$widget::getCategory()][$type] = $widget;
        }

        return $grouped;
    }

    /**
     * Export widget metadata for the Livewire visual editor panel.
     *
     * @return array<string, array{type: string, label: string, icon: string, category: string, is_container: bool, defaults: array<string, mixed>}>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->widgets as $type => $widget) {
            $result[$type] = [
                'type' => $type,
                'label' => $widget::getLabel(),
                'icon' => $widget::getIcon(),
                'category' => $widget::getCategory(),
                'is_container' => $widget::isContainer(),
                'defaults' => $widget::getDefaultSettings(),
            ];
        }

        return $result;
    }
}
