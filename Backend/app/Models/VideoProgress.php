<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoProgress extends Model
{
    protected $table = 'video_progress';

    protected $fillable = [
        'user_id',
        'dr_id',
        'video_id',
        'watched_seconds',
        'duration',
        'progress_percent',
        'is_unlocked',
        'last_watched_at',
    ];

    protected $casts = [
        'last_watched_at' => 'datetime',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class, 'user_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
