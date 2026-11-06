<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Support;

use Illuminate\Support\Str;

class SchemaTreeManipulator
{
    public static function generateElementId(string $prefix = 'el'): string
    {
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'el';

        return $safePrefix . '_' . Str::lower(Str::random(8));
    }

    /**
     * Find an element by ID anywhere in the element tree.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<string, mixed>|null
     */
    public static function findElement(array $elements, string $id): ?array
    {
        foreach ($elements as $element) {
            if (($element['id'] ?? null) === $id) {
                return $element;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $found = static::findElement($element['children'], $id);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Update settings for a specific element ID in the tree.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @param  array<string, mixed>  $newSettings
     * @return array<int, array<string, mixed>>
     */
    public static function updateElementSettings(array $elements, string $id, array $newSettings): array
    {
        foreach ($elements as $index => $element) {
            if (($element['id'] ?? null) === $id) {
                $existing = is_array($element['settings'] ?? null) ? $element['settings'] : [];
                $elements[$index]['settings'] = array_merge($existing, $newSettings);

                return $elements;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $elements[$index]['children'] = static::updateElementSettings($element['children'], $id, $newSettings);
            }
        }

        return $elements;
    }

    /**
     * Update the dynamic tag bindings map for a specific element ID in the tree.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @param  array<string, mixed>  $newDynamic
     * @return array<int, array<string, mixed>>
     */
    public static function updateElementDynamic(array $elements, string $id, array $newDynamic): array
    {
        foreach ($elements as $index => $element) {
            if (($element['id'] ?? null) === $id) {
                $elements[$index]['dynamic'] = $newDynamic;

                return $elements;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $elements[$index]['children'] = static::updateElementDynamic($element['children'], $id, $newDynamic);
            }
        }

        return $elements;
    }

    /**
     * Add a new element to the root list or inside a target container ID.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @param  array<string, mixed>  $newElement
     * @return array<int, array<string, mixed>>
     */
    public static function addElement(
        array $elements,
        array $newElement,
        ?string $parentContainerId = null,
        ?int $position = null,
    ): array {
        if ($parentContainerId === null || $parentContainerId === '') {
            if ($position !== null && $position >= 0 && $position <= count($elements)) {
                array_splice($elements, $position, 0, [$newElement]);

                return array_values($elements);
            }

            $elements[] = $newElement;

            return array_values($elements);
        }

        foreach ($elements as $index => $element) {
            if (($element['id'] ?? null) === $parentContainerId) {
                $children = is_array($element['children'] ?? null) ? array_values($element['children']) : [];

                if ($position !== null && $position >= 0 && $position <= count($children)) {
                    array_splice($children, $position, 0, [$newElement]);
                } else {
                    $children[] = $newElement;
                }

                $elements[$index]['children'] = array_values($children);

                return $elements;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $elements[$index]['children'] = static::addElement(
                    $element['children'],
                    $newElement,
                    $parentContainerId,
                    $position,
                );
            }
        }

        return $elements;
    }

    /**
     * Remove an element by ID from anywhere in the tree.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<int, array<string, mixed>>
     */
    public static function removeElement(array $elements, string $id): array
    {
        $filtered = [];

        foreach ($elements as $element) {
            if (($element['id'] ?? null) === $id) {
                continue;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $element['children'] = static::removeElement($element['children'], $id);
            }

            $filtered[] = $element;
        }

        return array_values($filtered);
    }

    /**
     * Duplicate an element right after its original position, assigning fresh IDs recursively.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @return array{elements: array<int, array<string, mixed>>, cloned_id: string|null}
     */
    public static function duplicateElement(array $elements, string $id): array
    {
        $clonedId = null;
        $result = [];

        foreach ($elements as $element) {
            $result[] = $element;

            if (($element['id'] ?? null) === $id) {
                $clone = static::cloneWithFreshIds($element);
                $clonedId = (string) $clone['id'];
                $result[] = $clone;

                continue;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $nested = static::duplicateElement($element['children'], $id);
                $result[count($result) - 1]['children'] = $nested['elements'];

                if ($nested['cloned_id'] !== null) {
                    $clonedId = $nested['cloned_id'];
                }
            }
        }

        return [
            'elements' => array_values($result),
            'cloned_id' => $clonedId,
        ];
    }

    /**
     * Move an element one position up (-1) or down (+1) among its siblings.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<int, array<string, mixed>>
     */
    public static function moveElement(array $elements, string $id, int $direction): array
    {
        $elements = array_values($elements);
        $count = count($elements);

        for ($i = 0; $i < $count; $i++) {
            if (($elements[$i]['id'] ?? null) === $id) {
                $targetIndex = $i + $direction;

                if ($targetIndex >= 0 && $targetIndex < $count) {
                    $temp = $elements[$targetIndex];
                    $elements[$targetIndex] = $elements[$i];
                    $elements[$i] = $temp;
                }

                return array_values($elements);
            }

            if (! empty($elements[$i]['children']) && is_array($elements[$i]['children'])) {
                $elements[$i]['children'] = static::moveElement($elements[$i]['children'], $id, $direction);
            }
        }

        return array_values($elements);
    }

    /**
     * Reorder sibling elements at the root level or inside a specific container ID.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @param  array<int, string>  $orderedIds
     * @return array<int, array<string, mixed>>
     */
    public static function reorderSiblings(array $elements, ?string $parentContainerId, array $orderedIds): array
    {
        if ($parentContainerId === null || $parentContainerId === '') {
            return static::applyIdOrder($elements, $orderedIds);
        }

        foreach ($elements as $index => $element) {
            if (($element['id'] ?? null) === $parentContainerId) {
                $children = is_array($element['children'] ?? null) ? $element['children'] : [];
                $elements[$index]['children'] = static::applyIdOrder($children, $orderedIds);

                return $elements;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $elements[$index]['children'] = static::reorderSiblings($element['children'], $parentContainerId, $orderedIds);
            }
        }

        return $elements;
    }

    /**
     * @param  array<int, array<string, mixed>>  $siblings
     * @param  array<int, string>  $orderedIds
     * @return array<int, array<string, mixed>>
     */
    protected static function applyIdOrder(array $siblings, array $orderedIds): array
    {
        $byId = [];
        $unkeyed = [];
        foreach ($siblings as $item) {
            $itemId = (string) ($item['id'] ?? '');
            if ($itemId !== '') {
                $byId[$itemId] = $item;
            } else {
                $unkeyed[] = $item;
            }
        }

        $reordered = [];
        foreach ($orderedIds as $id) {
            $idStr = (string) $id;
            if (isset($byId[$idStr])) {
                $reordered[] = $byId[$idStr];
                unset($byId[$idStr]);
            }
        }

        // Preserve any remaining items not included in $orderedIds, and unkeyed elements
        foreach ($byId as $remaining) {
            $reordered[] = $remaining;
        }

        foreach ($unkeyed as $item) {
            $reordered[] = $item;
        }

        return array_values($reordered);
    }

    /**
     * @param  array<string, mixed>  $element
     * @return array<string, mixed>
     */
    public static function cloneWithFreshIds(array $element): array
    {
        $element['id'] = static::generateElementId((string) ($element['type'] ?? 'el'));

        if (! empty($element['children']) && is_array($element['children'])) {
            $element['children'] = array_values(array_map(
                fn (array $child): array => static::cloneWithFreshIds($child),
                array_filter($element['children'], 'is_array'),
            ));
        }

        return $element;
    }
}
