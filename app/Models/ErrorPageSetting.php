<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ErrorPageSetting extends Model
{
    use Auditable;

    protected $fillable = [
        'status_code',
        'is_active',
        'title',
        'message',
        'accent',
        'button_label',
        'button_url',
        'show_back_button',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'show_back_button' => 'boolean',
        ];
    }
}
