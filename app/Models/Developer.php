<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    protected $fillable = [
        'username',
        'password'
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function timelines()
    {
        return $this->hasMany(Timeline::class);
    }
}
