<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormDefinition extends Model
{
    use Auditable, HasSlug;

    protected $fillable = [
        'department_id', 'name', 'slug', 'description', 'fields', 'notify_email',
        'success_message', 'store_submissions', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'store_submissions' => 'bool',
            'is_published' => 'bool',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Field definitions, normalised so the renderer never guesses. */
    public function fieldList(): array
    {
        return collect($this->fields ?? [])
            ->filter(fn ($f) => is_array($f) && ! empty($f['key']))
            ->map(fn ($f) => [
                'key' => $f['key'],
                'label' => $f['label'] ?? $f['key'],
                'type' => $f['type'] ?? 'text',
                'required' => (bool) ($f['required'] ?? false),
                'help' => $f['help'] ?? null,
                'options' => array_values(array_filter((array) ($f['options'] ?? []))),
            ])->values()->all();
    }

    /**
     * Field rows shaped for the admin form builder, where `options` is a
     * newline-joined string because that is what the textarea edits.
     */
    public function builderRows(): array
    {
        return collect($this->fieldList())->map(fn (array $f) => [
            'label' => $f['label'],
            'type' => $f['type'],
            'required' => $f['required'],
            'help' => $f['help'],
            'options' => implode(PHP_EOL, $f['options']),
        ])->values()->all();
    }

    /** Laravel validation rules derived from the field schema. */
    public function validationRules(): array
    {
        $rules = [];
        foreach ($this->fieldList() as $f) {
            $r = [$f['required'] ? 'required' : 'nullable'];
            $r[] = match ($f['type']) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                'checkbox' => 'boolean',
                'textarea' => 'string',
                default => 'string',
            };
            if (in_array($f['type'], ['select', 'radio'], true) && $f['options']) {
                $r[] = 'in:' . implode(',', $f['options']);
            }
            if (! in_array($f['type'], ['textarea', 'checkbox', 'number', 'date'], true)) {
                $r[] = 'max:255';
            }
            $rules['fields.' . $f['key']] = $r;
        }

        return $rules;
    }
}
