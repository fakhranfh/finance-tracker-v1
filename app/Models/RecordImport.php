<?php

namespace App\Models;

use App\Enums\RecordImportStep;
use App\Models\Concerns\HasUserTimezone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A spreadsheet upload on its way to becoming records: the stored file plus the progress made
 * (column mapping, the accounts and categories found, and the choices made for them).
 * Removed once imported or discarded.
 *
 * @property RecordImportStep $step
 * @property array<int, array{index: int, name: string}> $columns
 * @property array<int, array<int, mixed>> $sample
 * @property array<string, int|null>|null $mapping
 * @property array<string, mixed>|null $values
 * @property array<string, string>|null $choices
 */
#[Fillable(['user_id', 'filename', 'path', 'row_count', 'step', 'columns', 'sample', 'mapping', 'values', 'choices'])]
class RecordImport extends Model
{
    use HasUserTimezone;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step' => RecordImportStep::class,
            'row_count' => 'integer',
            'columns' => 'array',
            'sample' => 'array',
            'mapping' => 'array',
            'values' => 'array',
            'choices' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
