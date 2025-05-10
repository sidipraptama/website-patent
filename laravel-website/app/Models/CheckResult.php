<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckResult extends Model
{
    use HasFactory;

    protected $primaryKey = 'result_id';

    protected $fillable = [
        'check_id',
        'patent_id',
        'similarity_score',
    ];

    public function similarityCheck()
    {
        return $this->belongsTo(SimilarityCheck::class, 'check_id');
    }
}
