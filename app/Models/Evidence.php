<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    protected $fillable = [
        'image_path',
    ];

    public function timeline()
    {
        return $this->belongsTo(Timeline::class);
    }
}
