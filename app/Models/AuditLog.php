<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Audit log tidak dapat dihapus melalui model.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $term = "%{$search}%";
            $query->where(function (Builder $query) use ($term): void {
                $query->where('description', 'like', $term)
                    ->orWhere('event', 'like', $term)
                    ->orWhere('user_name', 'like', $term)
                    ->orWhere('user_email', 'like', $term)
                    ->orWhere('auditable_type', 'like', $term)
                    ->orWhere('auditable_id', 'like', $term)
                    ->orWhere('ip_address', 'like', $term);
            });
        });
    }
}
