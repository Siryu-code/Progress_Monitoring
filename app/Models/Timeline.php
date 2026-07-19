<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $fillable = [
        'title',
        'description',
        'developer_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }

    public function evidence()
    {
        return $this->hasMany(Evidence::class);
    }
}
