<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A contact staff logged by hand: the phone call, the counter visit, the letter
 * that went out. These never pass through the public site, so nothing else in
 * the app would otherwise know they happened.
 */
class ConstituentInteraction extends Model
{
    protected $fillable = [
        'constituent_id', 'user_id', 'department_id',
        'type', 'direction', 'subject', 'note', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    /** Interaction kinds, with the icon each gets on the timeline. */
    public static function types(): array
    {
        return [
            'phone_call' => ['label' => 'Phone Call', 'icon' => 'phone'],
            'counter_visit' => ['label' => 'Counter Visit', 'icon' => 'building'],
            'email' => ['label' => 'Email', 'icon' => 'envelope'],
            'letter' => ['label' => 'Letter Or Mail', 'icon' => 'file-text'],
            'meeting' => ['label' => 'Meeting', 'icon' => 'users'],
            'other' => ['label' => 'Other Contact', 'icon' => 'clipboard'],
        ];
    }

    public static function directions(): array
    {
        return ['inbound' => 'Resident Contacted Us', 'outbound' => 'We Contacted The Resident'];
    }

    public function typeLabel(): string
    {
        return static::types()[$this->type]['label'] ?? Str::headline((string) $this->type);
    }

    public function typeIcon(): string
    {
        return static::types()[$this->type]['icon'] ?? 'clipboard';
    }

    public function directionLabel(): string
    {
        return static::directions()[$this->direction] ?? 'Contact';
    }

    public function constituent(): BelongsTo
    {
        return $this->belongsTo(Constituent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
