<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * A staff account. Distinct from StaffMember (the public directory entry):
 * plenty of listed staff never log in, and some admins are not listed publicly.
 */
class User extends Authenticatable
{
    use \App\Models\Concerns\Auditable;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'department_id',
        'job_title', 'phone', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** May create/edit content anywhere on the site. */
    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }

    /** May create/edit content, but only within their own department. */
    public function isDepartmentEditor(): bool
    {
        return $this->role === 'department_editor';
    }

    public function canEditContent(): bool
    {
        return $this->isEditor() || $this->isDepartmentEditor();
    }

    /**
     * Whether this user may edit a record belonging to a given department.
     * Admins and site editors: always. Department editors: only their own.
     */
    public function canEditDepartment(?int $departmentId): bool
    {
        if ($this->isEditor()) {
            return true;
        }
        if (! $this->isDepartmentEditor()) {
            return false;
        }

        return $departmentId !== null && $departmentId === $this->department_id;
    }

    /**
     * Constrain a content query to what this user may see in the admin.
     * Department editors see only their department's rows (plus unassigned
     * rows they created), which is the whole point of the role.
     */
    public function scopeForContent(Builder $query, ?self $user = null): Builder
    {
        return $query;
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && ! empty($this->two_factor_secret);
    }

    public function roleLabel(): string
    {
        return config('municipal.roles.' . $this->role, Str::headline((string) $this->role));
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'is_active' => 'bool',
        ];
    }
}
