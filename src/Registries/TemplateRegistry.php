<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Registries;

class TemplateRegistry
{
    /**
     * @var array<string, array{key: string, name: string, category: string, schema: array<string, mixed>, thumbnail?: string|null}>
     */
    protected array $templates = [];

    /**
     * Register a starter page or section template (used by core and add-ons).
     *
     * @param  array<string, mixed>  $schema
     */
    public function register(
        string $key,
        string $name,
        array $schema,
        string $category = 'general',
        ?string $thumbnail = null,
    ): self {
        $this->templates[$key] = [
            'key' => $key,
            'name' => $name,
            'category' => $category,
            'schema' => $schema,
            'thumbnail' => $thumbnail,
        ];

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->templates[$key]);
    }

    /**
     * @return array{key: string, name: string, category: string, schema: array<string, mixed>, thumbnail?: string|null}|null
     */
    public function get(string $key): ?array
    {
        return $this->templates[$key] ?? null;
    }

    /**
     * @return array<string, array{key: string, name: string, category: string, schema: array<string, mixed>, thumbnail?: string|null}>
     */
    public function all(): array
    {
        return $this->templates;
    }
}
