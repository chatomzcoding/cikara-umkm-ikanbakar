<?php

namespace Modules\Superadmin\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

// use Modules\Superadmin\Database\Factories\SistemkategoriFactory;

class Sistemkategori extends Model
{
    use Auditable, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'sistem_kategori';

    protected $fillable = [
        'uuid',
        'nama',
        'keterangan',
        'is_status',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    // function getGambar() : string {
    //     $url = asset('img/danger.svg');
    //     if (!is_null($this->gambar)) {
    //         $url = Storage::disk('s3')->temporaryUrl(
    //             $this->gambar,
    //             now()->addMinutes(1)
    //         );
    //     }
    //     return $url;
    // }

    public function getStatus(): string
    {
        return ($this->is_status) ? 'Aktif' : 'Tidak Aktif';
    }

    public function getStatusView(): string
    {
        if ($this->is_status) {
            $html = "  <div class='badge rounded-full bg-success text-white'>Aktif</div>";
        } else {
            $html = "  <div class='badge rounded-full bg-warning text-white'>Tidak Aktif</div>";
        }

        return $html;
    }

    public function scopeSearch(Builder $query, ?string $search)
    {
        if (! $search) {
            return $query;
        }
        $search = '%'.$search.'%';

        return $query->where('keterangan', 'like', $search)
            ->OrWhere('nama', 'like', $search);
    }

    public function scopeSorting(Builder $query, ?string $sort, ?string $field)
    {
        $sort = ($sort) ? 'ASC' : 'DESC';

        return $query->orderBy($field, $sort);
    }

    public function sistemkategorisub(): HasMany
    {
        return $this->hasMany(Sistemkategorisub::class);
    }
}
