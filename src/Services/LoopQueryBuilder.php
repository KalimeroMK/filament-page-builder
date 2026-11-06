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
    public function query(array $settings): Collection
    {
        $modelClass = (string) ($settings['model_class'] ?? '');

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return new Collection();
        }

        if (! $this->isModelAllowed($modelClass)) {
            return new Collection();
        }

        /** @var Builder<Model> $query */
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

        $limit = max(1, min(100, (int) ($settings['limit'] ?? 6)));
        $query->limit($limit);

        return $query->get();
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
