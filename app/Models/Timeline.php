<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $fillable = [
        'title',
        'description'
    ];

    public function projects()
    {
        return $this->belongsTo(Project::class);
    }

    public function developers()
    {
        return $this->belongsTo(Developer::class);
    }

    public function evidence()
    {
        return $this->hasMany(Evidence::class);
    }
}
