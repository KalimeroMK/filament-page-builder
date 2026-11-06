<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string|null $description
 * @property array<string, mixed> $schema
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PageTemplate extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (PageTemplate $template): void {
            if (empty($template->slug)) {
                $baseSlug = Str::limit(Str::slug($template->name) ?: 'template', 200, '');
                $template->slug = $baseSlug . '-' . Str::lower(Str::random(6));
            }

            if (empty($template->category)) {
                $template->category = 'section';
            }
        });

        static::saving(function (PageTemplate $template): void {
            $template->schema = Page::normalizeSchema(is_array($template->schema) ? $template->schema : []);
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.templates', 'fpb_templates');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'created_by' => 'integer',
        ];
    }

    /**
     * Export the template to a portable array structure for sharing/version control.
     *
     * @return array{name: string, category: string, description: string|null, version: int, schema: array<string, mixed>}
     */
    public function exportToArray(): array
    {
        $normalized = Page::normalizeSchema(is_array($this->schema) ? $this->schema : []);

        return [
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'version' => 1,
            'schema' => $normalized,
        ];
    }

    public function exportToJson(): string
    {
        return (string) json_encode($this->exportToArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Validate whether a decoded JSON array contains a valid page builder schema structure.
     *
     * @param  array<mixed>  $decoded
     */
    public static function isValidSchemaPayload(array $decoded): bool
    {
        if (isset($decoded['schema']) && is_array($decoded['schema']) && isset($decoded['schema']['elements']) && is_array($decoded['schema']['elements'])) {
            return true;
        }

        if (isset($decoded['elements']) && is_array($decoded['elements'])) {
            return true;
        }

        return $decoded !== [] && array_is_list($decoded) && count(array_filter($decoded, 'is_array')) === count($decoded);
    }

    /**
     * Import and persist a PageTemplate from a JSON string.
     */
    public static function importFromJson(string $json, ?int $userId = null): self
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || ! static::isValidSchemaPayload($decoded)) {
            throw new InvalidArgumentException('Invalid template JSON payload: missing schema elements.');
        }

        $name = Str::limit(trim((string) ($decoded['name'] ?? 'Imported Template')) ?: 'Imported Template', 240, '');
        $category = Str::limit(trim((string) ($decoded['category'] ?? 'section')) ?: 'section', 64, '');
        $description = isset($decoded['description']) && is_scalar($decoded['description'])
            ? (string) $decoded['description']
            : null;

        $rawSchema = is_array($decoded['schema'] ?? null) ? $decoded['schema'] : $decoded;
        $schema = Page::normalizeSchema($rawSchema);

        $baseSlug = Str::limit(Str::slug($name) ?: 'template', 200, '');

        return static::create([
            'name' => $name,
            'slug' => $baseSlug . '-' . Str::lower(Str::random(6)),
            'category' => $category,
            'description' => $description,
            'schema' => $schema,
            'created_by' => $userId,
        ]);
    }
}
