<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements LaratrustUser
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRolesAndPermissions, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'profile_photo_path',
    ];

    protected $attributes = [
        'status' => UserStatus::Active->value,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->status === UserStatus::Active && ! $user->activated_at) {
                $user->activated_at = now();
            }
        });
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'suspended_by')->withTrashed();
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deactivated_by')->withTrashed();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function getPhoto(): string
    {
        $url = asset('img/avatar.png');
        if (! is_null($this->profile_photo_path)) {
            $url = $this->profile_photo_path;
        }

        return $url;
    }

    public function scopeSearch($query, $search)
    {
        if (! $search) {
            return $query;
        }
        $search = '%'.$search.'%';

        return $query->where(function ($query) use ($search): void {
            $query->where('name', 'like', $search)
                ->orWhere('email', 'like', $search)
                ->orWhere('username', 'like', $search);
        });
    }

    public function scopeSorting($q, $sort, $field)
    {
        $sort = ($sort) ? 'ASC' : 'DESC';

        return $q->orderBy($field, $sort);
    }
}
