<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $page_id
 * @property string $element_id
 * @property array<string, mixed> $data
 * @property array<int, array<string, mixed>>|null $actions_log
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FormSubmission extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.form_submissions', 'fpb_form_submissions');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_id' => 'integer',
            'data' => 'array',
            'actions_log' => 'array',
        ];
    }
}
