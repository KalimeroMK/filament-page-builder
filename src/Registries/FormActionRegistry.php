<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Registries;

use InvalidArgumentException;
use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;

class FormActionRegistry
{
    /**
     * @var array<string, FormActionContract>
     */
    protected array $actions = [];

    /**
     * @param  class-string<FormActionContract>|FormActionContract  $action
     */
    public function register(string | FormActionContract $action): self
    {
        $instance = is_string($action) ? app($action) : $action;

        if (! $instance instanceof FormActionContract) {
            throw new InvalidArgumentException('Form action must implement ' . FormActionContract::class);
        }

        $this->actions[$instance::getName()] = $instance;

        return $this;
    }

    public function get(string $name): ?FormActionContract
    {
        return $this->actions[$name] ?? null;
    }

    /**
     * @return array<string, FormActionContract>
     */
    public function all(): array
    {
        return $this->actions;
    }
}
