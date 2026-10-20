<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    protected $table = 'organization';

    protected $fillable = [
        'name',
        'status',
    ];

    public function shareLinks()
    {
        return $this->hasMany(VideoShareLink::class, 'org_id');
    }
}
