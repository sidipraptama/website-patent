<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateHistory extends Model
{
    use HasFactory;

    protected $table = 'update_history';

    protected $primaryKey = 'update_history_id';

    // Menambahkan relasi dengan model Log
    public function updateLogs()
    {
        return $this->hasMany(UpdateLog::class, 'update_history_id');
    }

}
