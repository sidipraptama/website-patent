<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftPatentImage extends Model
{
    use HasFactory;

    protected $primaryKey = 'image_id';

    protected $fillable = [
        'draft_id',
        'idx',
        'file',
    ];

    public function draftPatent()
    {
        return $this->belongsTo(DraftPatent::class, 'draft_id');
    }
}
