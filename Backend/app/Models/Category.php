<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'category';

    protected $fillable = [
        'name',
        'icon',
        'sort_order',
        'status',
    ];

    public function videos()
    {
        return $this->hasMany(Video::class, 'category_id');
    }
}
