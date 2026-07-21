<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A resident the town has a record of. Deliberately NOT a User: the vast
 * majority of people who report a pothole or file a form will never hold an
 * account, and requiring one would simply mean the pothole goes unreported.
 * `user_id` links the rare resident who does register.
 *
 * Resident PII. Every route touching this model lives behind the staff auth
 * gate; nothing here is ever rendered on a public page.
 */
class Constituent extends Model
{
    use Auditable;

    protected $fillable = [
        'name', 'email', 'email_key', 'phone', 'phone_key',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code',
        'notes', 'tags', 'do_not_contact', 'user_id', 'source', 'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'do_not_contact' => 'bool',
            'last_interaction_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dedupe keys
    |--------------------------------------------------------------------------
    | Kept as plain statics (not a service) so the backfill migration and the
    | live intake path can never drift apart on what counts as "the same
    | person". Both call these.
    */

    /** Lowercased, trimmed email. Null when there is nothing to key on. */
    public static function emailKey(?string $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    /**
     * Digits-only phone, reduced to the last 10.
     * "(928) 555-0142", "928-555-0142" and "+1 928 555 0142" are one person.
     */
    public static function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '' || strlen($digits) < 7) {
            return null;
        }

        return substr($digits, -10);
    }

    /**
     * Find an existing constituent for these details, or create one.
     *
     * Email is the strong key (it is unique in the schema). Phone is a fallback
     * for counter/phone intake where no email was captured. A bare name is
     * never enough to match on: two different Marias would silently merge.
     */
    public static function resolve(array $attributes, string $source = 'manual'): ?self
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $emailKey = static::emailKey($attributes['email'] ?? null);
        $phoneKey = static::phoneKey($attributes['phone'] ?? null);

        if (! $emailKey && ! $phoneKey) {
            return null; // Nothing durable to identify them by.
        }

        $existing = null;
        if ($emailKey) {
            $existing = static::where('email_key', $emailKey)->first();
        }
        if (! $existing && $phoneKey) {
            $existing = static::where('phone_key', $phoneKey)->first();
        }

        if ($existing) {
            $existing->fillMissing($attributes);
            $existing->touchInteraction();

            return $existing;
        }

        return static::create([
            'name' => $name !== '' ? $name : ($attributes['email'] ?? $attributes['phone'] ?? 'Unnamed Resident'),
            'email' => $emailKey ? trim((string) $attributes['email']) : null,
            'email_key' => $emailKey,
            'phone' => $phoneKey ? trim((string) $attributes['phone']) : null,
            'phone_key' => $phoneKey,
            'address_line1' => $attributes['address_line1'] ?? null,
            'city' => $attributes['city'] ?? null,
            'state' => $attributes['state'] ?? null,
            'postal_code' => $attributes['postal_code'] ?? null,
            'source' => $source,
            'last_interaction_at' => now(),
        ]);
    }

    /**
     * Fill in blanks from a newer sighting without ever overwriting what staff
     * already curated. A resident supplying a phone on their second report
     * should enrich the record, not clobber a corrected spelling of their name.
     */
    public function fillMissing(array $attributes): void
    {
        $dirty = [];

        if (! $this->phone && $key = static::phoneKey($attributes['phone'] ?? null)) {
            $dirty['phone'] = trim((string) $attributes['phone']);
            $dirty['phone_key'] = $key;
        }
        if (! $this->email && $key = static::emailKey($attributes['email'] ?? null)) {
            $dirty['email'] = trim((string) $attributes['email']);
            $dirty['email_key'] = $key;
        }
        foreach (['address_line1', 'city', 'state', 'postal_code'] as $field) {
            $value = trim((string) ($attributes[$field] ?? ''));
            if (! $this->{$field} && $value !== '') {
                $dirty[$field] = $value;
            }
        }

        if ($dirty) {
            $this->forceFill($dirty)->save();
        }
    }

    public function touchInteraction(?\DateTimeInterface $at = null): void
    {
        $at ??= now();
        if (! $this->last_interaction_at || $this->last_interaction_at->lt($at)) {
            $this->forceFill(['last_interaction_at' => $at])->save();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(ConstituentInteraction::class)->latest('occurred_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        $digits = preg_replace('/\D+/', '', $term);

        return $q->where(function (Builder $s) use ($like, $digits) {
            $s->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('address_line1', 'like', $like)
                ->orWhere('city', 'like', $like);
            if ($digits !== '') {
                $s->orWhere('phone_key', 'like', '%' . $digits . '%');
            }
        });
    }

    /**
     * Constrain to what a given staff member may see.
     * Admins and site editors see the whole roll. A department editor sees only
     * residents who have actually dealt with their department, which is the
     * whole point of the role on a system holding resident PII.
     */
    public function scopeVisibleTo(Builder $q, ?User $user): Builder
    {
        if (! $user || $user->isEditor()) {
            return $q;
        }

        if ($user->isDepartmentEditor() && $user->department_id) {
            $department = $user->department_id;

            return $q->where(function (Builder $s) use ($department) {
                $s->whereHas('serviceRequests', fn (Builder $r) => $r->where('department_id', $department))
                    ->orWhereHas('interactions', fn (Builder $r) => $r->where('department_id', $department));
            });
        }

        // Anyone else (notably the read-only "viewer" role): no access to
        // resident PII. Returns an empty set as defense in depth, on top of the
        // controller's canAccessConstituents() gate.
        return $q->whereRaw('1 = 0');
    }

    /** Candidate duplicates: same phone, or a very similar name. */
    public function duplicateCandidates(int $limit = 10)
    {
        return static::where('id', '!=', $this->id)
            ->where(function (Builder $q) {
                $q->when($this->phone_key, fn (Builder $s) => $s->orWhere('phone_key', $this->phone_key));
                $q->orWhere('name', 'like', '%' . trim(Str::before($this->name, ' ')) . '%');
                if ($this->email_key) {
                    $q->orWhere('email_key', 'like', '%' . Str::before($this->email_key, '@') . '%');
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('') ?: '?';
    }

    public function addressLines(): array
    {
        $cityLine = trim(implode(' ', array_filter([
            $this->city ? $this->city . ',' : null,
            $this->state,
            $this->postal_code,
        ])));

        return array_values(array_filter([$this->address_line1, $this->address_line2, $cityLine]));
    }

    public function hasAddress(): bool
    {
        return count($this->addressLines()) > 0;
    }

    public function tagList(): array
    {
        return array_values(array_filter((array) ($this->tags ?? [])));
    }
}
