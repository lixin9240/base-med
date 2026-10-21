<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamPretest extends Model
{
    protected $table = 'exam_pretest';

    protected $fillable = [
        'user_id',
        'dr_id',
        'video_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
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
