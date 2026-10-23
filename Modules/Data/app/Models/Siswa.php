<?php

namespace Modules\Data\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use Auditable;
    use HasPublicUlid;
    use SoftDeletes;

    protected $fillable = [
        'nama',
        'nis',
        'alamat',
    ];

    protected function casts(): array
    {
        return [

        ];
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function ($query, $search): void {
            $term = '%'.$search.'%';
            $query->where(function ($query) use ($term): void {
                $query->where('nama', 'like', $term)
                    ->orWhere('nis', 'like', $term)
                    ->orWhere('alamat', 'like', $term);
            });
        });
    }
}
