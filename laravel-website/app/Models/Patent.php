<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patent extends Model
{
    // Tentukan atribut yang dapat diisi
    protected $fillable = [
        'patent_id',
        'patent_type',
        'patent_date',
        'patent_title',
        'wipo_kind',
        'num_claims',
        'patent_abstract',
    ];

    protected $casts = [
        'patent_date' => 'date',
    ];
}
