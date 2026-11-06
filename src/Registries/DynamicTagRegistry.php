<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Registries;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;
use Stringable;

class DynamicTagRegistry
{
    /**
     * @var array<string, DynamicTagContract>
     */
    protected array $tags = [];

    /**
     * @param  class-string<DynamicTagContract>|DynamicTagContract  $tag
     */
    public function register(string | DynamicTagContract $tag): self
    {
        $instance = is_string($tag) ? app($tag) : $tag;

        if (! $instance instanceof DynamicTagContract) {
            throw new InvalidArgumentException('Dynamic tag must implement ' . DynamicTagContract::class);
        }

        $this->tags[$instance::getName()] = $instance;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->tags[$name]);
    }

    public function get(string $name): ?DynamicTagContract
    {
        return $this->tags[$name] ?? null;
    }

    /**
     * @return array<string, DynamicTagContract>
     */
    public function all(): array
    {
        return $this->tags;
    }

    /**
     * Resolve a dynamic tag expression.
     * Accepts:
     * - string: "page.title" or "model.attribute:title"
     * - array: ["tag" => "model.attribute", "params" => ["key" => "title"]]
     *
     * @param  string|array<string, mixed>  $descriptor
     * @param  array<string, mixed>  $context
     */
    public function resolve(string | array $descriptor, array $context = []): mixed
    {
        if (is_string($descriptor)) {
            if (str_contains($descriptor, ':')) {
                [$tagName, $paramValue] = explode(':', $descriptor, 2);
                $parameters = ['key' => $paramValue, 'value' => $paramValue];
            } else {
                $tagName = $descriptor;
                $parameters = [];
            }
        } else {
            $tagName = (string) ($descriptor['tag'] ?? '');
            $parameters = is_array($descriptor['params'] ?? null) ? $descriptor['params'] : [];
        }

        if ($tagName === '') {
            return null;
        }

        $tag = $this->get($tagName);

        if ($tag !== null) {
            return $tag->resolve($parameters, $context);
        }

        // Fallback dot-notation lookup on sanitized context (converting Models via toArray() to respect $hidden)
        $safeContext = [];
        foreach ($context as $ctxKey => $ctxVal) {
            $safeContext[$ctxKey] = $ctxVal instanceof Model ? $ctxVal->toArray() : $ctxVal;
        }

        return data_get($safeContext, $tagName);
    }

    /**
     * Merge resolved dynamic tag scalar values over static widget settings.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $dynamicMap
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function resolveElementSettings(array $settings, array $dynamicMap, array $context = []): array
    {
        foreach ($dynamicMap as $settingKey => $descriptor) {
            if ($descriptor === null || $descriptor === '') {
                continue;
            }

            if (is_string($descriptor) || is_array($descriptor)) {
                $resolved = $this->resolve($descriptor, $context);

                if (is_scalar($resolved) || $resolved instanceof Stringable) {
                    $settings[$settingKey] = (string) $resolved;
                }
            }
        }

        return $settings;
    }
}
