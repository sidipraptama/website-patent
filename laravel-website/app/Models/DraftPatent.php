<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftPatent extends Model
{
    use HasFactory;

    protected $primaryKey = 'draft_id';

    protected $fillable = [
        'user_id',
        'check_id',
        'title',
        'technical_field',
        'background',
        'summary',
        'description',
        'claims',
        'abstract',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function similarityCheck()
    {
        return $this->belongsTo(SimilarityCheck::class, 'check_id');
    }

    public function images()
    {
        return $this->hasMany(DraftPatentImage::class, 'draft_id');
    }
}
