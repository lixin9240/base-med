<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitRecord extends Model
{
    const UPDATED_AT = null;

    protected $table = 'visit_record';

    protected $fillable = [
        'dr_id',
        'share_link_id',
        'video_id',
        'entered_at',
        'created_at',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
    ];

    public function shareLink()
    {
        return $this->belongsTo(VideoShareLink::class, 'share_link_id');
    }

    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class, 'user_id');
    }
}
