<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'project_code',
        'project_name',
        'status',
    ];

    public function developers()
    {
        return $this->belongsToMany(Developer::class);
    }

    public function timelines()
    {
        return $this->hasMany(Timeline::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }
}
