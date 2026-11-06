<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

class SchemaDiffService
{
    /**
     * Compare two element trees: added/removed/changed/unchanged counts, per-type breakdown,
     * and reordered sibling groups (by ordered IDs).
     *
     * @param  array<int, array<string, mixed>>  $oldElements
     * @param  array<int, array<string, mixed>>  $newElements
     * @return array{added: int, removed: int, changed: int, unchanged: int, reordered: int, by_type: array<string, array{added: int, removed: int, changed: int}>, changed_ids: array<int, string>}
     */
    public function compare(array $oldElements, array $newElements): array
    {
        $oldMap = $this->flattenById($oldElements);
        $newMap = $this->flattenById($newElements);

        $added = array_diff_key($newMap, $oldMap);
        $removed = array_diff_key($oldMap, $newMap);
        $changed = [];
        $unchanged = 0;

        foreach (array_intersect_key($newMap, $oldMap) as $id => $newElement) {
            if ($oldMap[$id] !== $newElement) {
                $changed[] = $id;
            } else {
                $unchanged++;
            }
        }

        $reordered = $this->countReorderedSiblingGroups($oldElements, $newElements);

        $byType = [];
        foreach ([$added, $removed] as $direction => $elements) {
            foreach ($elements as $element) {
                $type = (string) ($element['type'] ?? 'unknown');
                $key = $direction === 0 ? 'added' : 'removed';
                $byType[$type] ??= ['added' => 0, 'removed' => 0, 'changed' => 0];
                $byType[$type][$key]++;
            }
        }

        foreach ($changed as $id) {
            $type = (string) ($newMap[$id]['type'] ?? 'unknown');
            $byType[$type] ??= ['added' => 0, 'removed' => 0, 'changed' => 0];
            $byType[$type]['changed']++;
        }

        return [
            'added' => count($added),
            'removed' => count($removed),
            'changed' => count($changed),
            'unchanged' => $unchanged,
            'reordered' => $reordered,
            'by_type' => $byType,
            'changed_ids' => array_values($changed),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<string, array<string, mixed>>
     */
    protected function flattenById(array $elements): array
    {
        $map = [];

        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }

            $id = (string) ($element['id'] ?? '');
            if ($id !== '') {
                $nodeData = $element;
                unset($nodeData['children']);
                $map[$id] = $nodeData;
            }

            if (! empty($element['children']) && is_array($element['children'])) {
                $map += $this->flattenById($element['children']);
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $oldElements
     * @param  array<int, array<string, mixed>>  $newElements
     */
    protected function countReorderedSiblingGroups(array $oldElements, array $newElements): int
    {
        $reordered = 0;

        $oldIds = $this->orderedIds($oldElements);
        $newIds = $this->orderedIds($newElements);

        if ($oldIds !== $newIds && count(array_intersect($oldIds, $newIds)) === count($oldIds) && count($oldIds) === count($newIds) && $oldIds !== [] && $newIds !== []) {
            $reordered++;
        }

        $oldById = [];
        foreach ($oldElements as $element) {
            if (is_array($element) && isset($element['id'])) {
                $oldById[(string) $element['id']] = $element;
            }
        }

        foreach ($newElements as $element) {
            if (! is_array($element)) {
                continue;
            }

            $id = (string) ($element['id'] ?? '');
            if ($id === '' || ! isset($oldById[$id])) {
                continue;
            }

            $oldChildren = is_array($oldById[$id]['children'] ?? null) ? $oldById[$id]['children'] : [];
            $newChildren = is_array($element['children'] ?? null) ? $element['children'] : [];

            $reordered += $this->countReorderedSiblingGroups($oldChildren, $newChildren);
        }

        return $reordered;
    }

    /**
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<int, string>
     */
    protected function orderedIds(array $elements): array
    {
        $ids = [];
        foreach ($elements as $element) {
            if (is_array($element) && filled($element['id'] ?? null)) {
                $ids[] = (string) $element['id'];
            }
        }

        return $ids;
    }
}
