<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;

class ThemeConditionMatcher
{
    /**
     * Evaluate whether a ThemeTemplate's include/exclude condition rules match the current request and context.
     * - If any `exclude` rule matches, immediately returns false ("all pages except X").
     * - At least one `include` rule must match.
     *
     * @param  array<string, mixed>  $context
     */
    public function matches(ThemeTemplate $template, array $context = [], ?Request $request = null): bool
    {
        $rules = ThemeTemplate::normalizeConditions(is_array($template->conditions) ? $template->conditions : []);

        if ($rules === []) {
            return false;
        }

        $req = $request ?? request();
        $hasIncludeRule = false;
        $matchedInclude = false;

        foreach ($rules as $rule) {
            $type = $rule['type'];
            $isMatch = $this->evaluateSingleRule($rule['target'], $rule['value'], $context, $req);

            if ($type === 'exclude' && $isMatch) {
                return false;
            }

            if ($type === 'include') {
                $hasIncludeRule = true;
                if ($isMatch) {
                    $matchedInclude = true;
                }
            }
        }

        return $hasIncludeRule && $matchedInclude;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function evaluateSingleRule(string $target, string $pattern, array $context, Request $request): bool
    {
        if ($target === 'entire_site') {
            return true;
        }

        if ($target === 'path') {
            $currentPath = isset($context['path']) && is_string($context['path'])
                ? trim($context['path'], '/')
                : trim($request->path(), '/');

            $normalizedPattern = trim($pattern, '/');

            if ($normalizedPattern === '' && $currentPath === '') {
                return true;
            }

            return Str::is($normalizedPattern, $currentPath);
        }

        if ($target === 'route') {
            $routeName = isset($context['route_name']) && is_string($context['route_name'])
                ? $context['route_name']
                : (string) ($request->route()?->getName() ?? '');

            return $routeName !== '' && Str::is($pattern, $routeName);
        }

        if ($target === 'page_slug') {
            $page = $context['page'] ?? null;
            $slug = '';

            if ($page instanceof Page) {
                $slug = $page->slug;
            } elseif (is_array($page) && isset($page['slug']) && is_string($page['slug'])) {
                $slug = $page['slug'];
            } elseif (isset($context['page_slug']) && is_string($context['page_slug'])) {
                $slug = $context['page_slug'];
            }

            return $slug !== '' && Str::is(trim($pattern, '/'), trim($slug, '/'));
        }

        if ($target === 'model') {
            $record = $context['record'] ?? $context['model'] ?? $context['page'] ?? null;

            if (is_object($record)) {
                $fqcn = $record::class;
            } elseif (is_string($record) && $record !== '') {
                $fqcn = ltrim($record, '\\');
            } else {
                return false;
            }

            $basename = class_basename($fqcn);
            $cleanPattern = ltrim($pattern, '\\');
            $patternBasename = class_basename($cleanPattern);

            return Str::is($cleanPattern, $fqcn)
                || Str::is($cleanPattern, $basename)
                || Str::is($patternBasename, $basename);
        }

        return false;
    }
}
