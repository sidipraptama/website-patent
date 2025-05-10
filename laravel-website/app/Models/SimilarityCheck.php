<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimilarityCheck extends Model
{
    use HasFactory;

    protected $primaryKey = 'check_id';

    protected $fillable = [
        'user_id',
        'input_text',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function results()
    {
        return $this->hasMany(CheckResult::class, 'check_id');
    }
}
