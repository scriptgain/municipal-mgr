<?php

namespace App\Models;

use App\Models\AuditLog;
use App\Services\RecordsSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A booking record.
 *
 * The guardrails that matter are enforced HERE rather than only in the
 * controller, because the controller is not the only thing that will ever
 * write to this table: seeders, imports from a jail management system, and
 * future API callers all go through the model.
 *
 * Enforced on every save:
 *   - a juvenile subject is never published, whatever was asked for
 *   - a published record always carries a retention expiry
 * Enforced on every public read (scopePublic):
 *   - published, past its publish time, inside its retention window, adult
 */
class ArrestRecord extends Model
{
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'suffix', 'age',
        'booked_at', 'released_at', 'arresting_agency', 'case_number',
        'booking_number', 'bond_amount', 'bond_note', 'custody_status',
        'disposition', 'disposition_note', 'mugshot_path',
        'mugshot_takedown_requested', 'mugshot_takedown_note',
        'internal_notes', 'is_published', 'unpublish_reason',
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'released_at' => 'datetime',
            'published_at' => 'datetime',
            'retention_expires_at' => 'datetime',
            'disposition_updated_at' => 'datetime',
            'is_published' => 'bool',
            'mugshot_takedown_requested' => 'bool',
            'age' => 'int',
            'bond_amount' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_ref';
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ArrestCharge::class)->orderBy('sort_order')->orderBy('id');
    }

    /* ------------------------------------------------------------------
     * Guardrails
     * ---------------------------------------------------------------- */

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            $record->public_ref ??= self::newRef();
        });

        static::saving(function (self $record) {
            // Juvenile block. Not a warning, not a confirm dialog: the value
            // is forced back to unpublished before it reaches the database.
            if ($record->isJuvenile()) {
                $record->is_published = false;
                $record->published_at = null;
                $record->retention_expires_at = null;
            }

            if ($record->is_published) {
                $record->published_at ??= now();
                // Retention runs from the BOOKING, not from the day someone
                // got around to publishing it. Backdating a booking must not
                // buy it a fresh 60 days on the public blotter.
                $record->retention_expires_at = $record->booked_at
                    ? $record->booked_at->copy()->addDays(RecordsSettings::retentionDays())
                    : now()->addDays(RecordsSettings::retentionDays());
            }

            if ($record->isDirty('disposition')) {
                $record->disposition_updated_at = now();
            }
        });

        // An ordinary delete must not leave the mugshot sitting on the public
        // disk. (Expungement destroys it explicitly, before deleteQuietly.)
        static::deleting(function (self $record) {
            if ($record->mugshot_path) {
                rescue(fn () => Storage::disk('public')->delete($record->mugshot_path), null, false);
            }
        });

        // Audit trail. Case number and internal id only: writing the subject's
        // name into the audit log would leave it behind after an expungement.
        static::created(fn (self $r) => AuditLog::record('created', "Arrest record {$r->reference()} created", $r));
        static::updated(fn (self $r) => AuditLog::record('updated', "Arrest record {$r->reference()} updated", $r));
        static::deleted(fn (self $r) => AuditLog::record('deleted', "Arrest record {$r->reference()} deleted", $r));
    }

    public static function newRef(): string
    {
        do {
            $ref = Str::lower(Str::random(12));
        } while (self::where('public_ref', $ref)->exists());

        return $ref;
    }

    public function isJuvenile(): bool
    {
        return $this->age !== null && $this->age < (int) config('records.minimum_publish_age', 18);
    }

    public function retentionExpired(): bool
    {
        return $this->retention_expires_at !== null && $this->retention_expires_at->isPast();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published
            && ! $this->isJuvenile()
            && ! $this->retentionExpired()
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /** Mugshots are shown only if the policy allows it AND no takedown was asked for. */
    public function showsMugshot(): bool
    {
        return (bool) $this->mugshot_path
            && RecordsSettings::mugshotsEnabled()
            && ! $this->mugshot_takedown_requested;
    }

    /* ------------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    /** The ONLY query the public site is allowed to read through. */
    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(fn (Builder $s) => $s->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $s) => $s->whereNull('retention_expires_at')->orWhere('retention_expires_at', '>', now()))
            ->where(fn (Builder $s) => $s->whereNull('age')->orWhere('age', '>=', (int) config('records.minimum_publish_age', 18)));
    }

    /** Still in custody: the source of the inmate roster. */
    public function scopeInCustody(Builder $q): Builder
    {
        return $q->where('custody_status', 'in_custody');
    }

    public function scopeBookedBetween(Builder $q, $from = null, $to = null): Builder
    {
        if ($from) {
            $q->where('booked_at', '>=', $from);
        }
        if ($to) {
            $q->where('booked_at', '<=', $to);
        }

        return $q;
    }

    /** Published records whose retention window has run out. */
    public function scopeRetentionLapsed(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->whereNotNull('retention_expires_at')
            ->where('retention_expires_at', '<=', now());
    }

    /* ------------------------------------------------------------------
     * Presentation helpers (keep the logic out of the views)
     * ---------------------------------------------------------------- */

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name, $this->middle_name, $this->last_name, $this->suffix,
        ])));
    }

    public function listName(): string
    {
        return trim($this->last_name . ', ' . $this->first_name . ' ' . ($this->middle_name ?? ''));
    }

    /** Case number if there is one, otherwise the internal id. Never the name. */
    public function reference(): string
    {
        return (string) ($this->case_number ?: $this->booking_number ?: '#' . $this->getKey());
    }

    public function dispositionLabel(): string
    {
        return config("records.dispositions.{$this->disposition}.label", 'Pending');
    }

    public function dispositionColor(): string
    {
        return config("records.dispositions.{$this->disposition}.color", 'neutral');
    }

    public function dispositionExplanation(): string
    {
        return config("records.dispositions.{$this->disposition}.public", 'Case Pending. No Finding Has Been Made.');
    }

    public function custodyLabel(): string
    {
        return config("records.custody_statuses.{$this->custody_status}.label", 'Unknown');
    }

    public function custodyColor(): string
    {
        return config("records.custody_statuses.{$this->custody_status}.color", 'neutral');
    }

    public function isInCustody(): bool
    {
        return $this->custody_status === 'in_custody';
    }
}
