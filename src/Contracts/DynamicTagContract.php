<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Contracts;

interface DynamicTagContract
{
    public static function getName(): string;

    public static function getLabel(): string;

    public static function getGroup(): string;

    /**
     * Resolve the dynamic tag value using optional parameters and render context.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $context
     */
    public function resolve(array $parameters = [], array $context = []): mixed;
}
