<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoShareLink extends Model
{
    use SoftDeletes;

    protected $table = 'video_share_link';

    protected $fillable = [
        'video_id',
        'org_id',
        'token',
        'expire_type',
        'expire_at',
        'visit_count',
        'status',
        'created_by',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function visitRecords()
    {
        return $this->hasMany(VisitRecord::class, 'share_link_id');
    }
}
