<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use SoftDeletes;

    protected $table = 'topic';

    protected $fillable = [
        'name',
        'cover_url',
        'intro',
        'sort_order',
        'status',
    ];

    public function videos()
    {
        return $this->hasMany(Video::class, 'topic_id');
    }
}
