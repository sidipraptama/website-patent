<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    use HasFactory;

    protected $table = 'update_logs';

    protected $primaryKey = 'update_log_id';

    public function updateHistory()
    {
        return $this->belongsTo(UpdateHistory::class, 'update_history_id');
    }
}
