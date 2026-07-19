<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = [
        'title',
        'status'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
