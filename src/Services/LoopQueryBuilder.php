<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LoopQueryBuilder
{
    /**
     * Safely build and execute an Eloquent loop query from loop widget settings.
     *
     * @param  array<string, mixed>  $settings
     * @return Collection<int, Model>
     */
    /**
     * @param  array<string, mixed>  $settings
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function buildQuery(array $settings): \Illuminate\Database\Eloquent\Builder
    {
        $modelClass = (string) ($settings['model_class'] ?? '');

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException('Invalid model class for loop query.');
        }

        if (! $this->isModelAllowed($modelClass)) {
            throw new \InvalidArgumentException('Model class is not allowed for loop queries.');
        }

        /** @var \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query */
        $query = $modelClass::query();

        $scope = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($settings['scope'] ?? ''));
        if ($scope !== '' && method_exists($modelClass, 'scope' . ucfirst($scope))) {
            $query->{$scope}();
        }

        $filters = is_array($settings['filters'] ?? null) ? $settings['filters'] : [];
        foreach ($filters as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $field = $this->sanitizeField((string) ($filter['field'] ?? ''));
            $operator = (string) ($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;

            if ($field === '' || ! is_scalar($value)) {
                continue;
            }

            if (! $this->isFieldAllowed($modelClass, $field)) {
                continue;
            }

            $allowedOperators = ['=', '!=', '<', '<=', '>', '>=', 'like'];
            if (! in_array($operator, $allowedOperators, true)) {
                $operator = '=';
            }

            if ($operator === 'like') {
                $value = '%' . $value . '%';
            }

            $query->where($field, $operator, $value);
        }

        $defaultKey = $query->getModel()->getKeyName();
        $orderBy = $this->sanitizeField((string) ($settings['order_by'] ?? ''));
        if ($orderBy === '' || ! $this->isFieldAllowed($modelClass, $orderBy)) {
            $orderBy = $defaultKey;
        }

        $orderDirection = strtolower((string) ($settings['order_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // Deterministic secondary ordering for stable pagination when order column is not unique
        if ($orderBy !== $defaultKey) {
            $query->orderBy($defaultKey, $orderDirection);
        }

        return $query;
    }

    public function query(array $settings): Collection
    {
        try {
            $query = $this->buildQuery($settings);
        } catch (\InvalidArgumentException) {
            return new Collection();
        }

        $pageSize = $this->effectivePageSize($settings);
        $page = max(1, (int) ($settings['page'] ?? 1));

        return $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
    }

    /**
     * Effective page size used consistently for fetching, offsets, hasMore and total_pages.
     *
     * @param  array<string, mixed>  $settings
     */
    public function effectivePageSize(array $settings): int
    {
        $pageSize = max(1, min(100, (int) ($settings['page_size'] ?? $settings['limit'] ?? 6)));
        $totalCap = max(1, min(500, (int) ($settings['limit'] ?? 100)));

        return min($pageSize, $totalCap);
    }

    /**
     * Total number of records matching the current loop settings (before pagination).
     *
     * @param  array<string, mixed>  $settings
     */
    public function total(array $settings): int
    {
        try {
            return $this->buildQuery($settings)->count();
        } catch (\InvalidArgumentException) {
            return 0;
        }
    }

    /**
     * Determine whether at least one record exists after the current page.
     *
     * @param  array<string, mixed>  $settings
     */
    public function hasMore(array $settings): bool
    {
        try {
            $query = $this->buildQuery($settings);
        } catch (\InvalidArgumentException) {
            return false;
        }

        $pageSize = $this->effectivePageSize($settings);
        $page = max(1, (int) ($settings['page'] ?? 1));

        return $query
            ->offset($page * $pageSize)
            ->limit(1)
            ->exists();
    }

    /**
     * Fail closed: only models explicitly whitelisted in config can be queried.
     */
    protected function isModelAllowed(string $modelClass): bool
    {
        $allowed = (array) config('filament-page-builder.loop.allowed_models', []);

        return $allowed !== [] && in_array($modelClass, $allowed, true);
    }

    protected function isFieldAllowed(string $modelClass, string $field): bool
    {
        $allowedFields = (array) config('filament-page-builder.loop.allowed_fields', []);

        if ($allowedFields === []) {
            return true;
        }

        $modelFields = $allowedFields[$modelClass] ?? $allowedFields['*'] ?? [];

        return $modelFields !== [] && in_array($field, $modelFields, true);
    }

    protected function sanitizeField(string $field): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $field) ?? '';
    }
}
