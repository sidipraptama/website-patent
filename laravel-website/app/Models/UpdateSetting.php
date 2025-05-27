<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateSetting extends Model
{
    protected $table = 'update_settings';

    protected $fillable = [
        'interval',
        'last_updated_at',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
    ];
}
