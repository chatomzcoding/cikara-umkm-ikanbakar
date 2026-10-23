<?php

namespace Modules\Superadmin\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataPokok extends Model
{
    use Auditable, HasFactory;

    protected $table = 'data_pokok';

    public const SUB_MENU_DEFAULTS = ['hide', 'show'];

    protected $fillable = [
        'singleton_key',
        'uuid',
        'nama_aplikasi',
        'keterangan',
        'no_telp',
        'email',
        'logo',
        'favicon',
        'nama_owner',
        'alamat',
        'tanggal_dibuat',
        'url_website',
        'sub_menu_default',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_dibuat' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataPokok $dataPokok) {
            $dataPokok->singleton_key ??= 1;
            $dataPokok->uuid ??= (string) Str::uuid();
        });
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query->where('nama_aplikasi', 'like', "%{$search}%")
                ->orWhere('nama_owner', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('no_telp', 'like', "%{$search}%")
                ->orWhere('url_website', 'like', "%{$search}%");
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? Storage::disk('public')->url($this->favicon) : null;
    }
}
