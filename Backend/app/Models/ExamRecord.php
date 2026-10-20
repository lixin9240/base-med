<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamRecord extends Model
{
    const UPDATED_AT = null;

    protected $table = 'exam_record';

    protected $fillable = [
        'user_id',
        'dr_id',
        'video_id',
        'attempt_no',
        'pretest_correct',
        'posttest_correct',
        'pretest_score',
        'posttest_score',
        'accuracy_rate',
        'result_status',
        'total_duration',
        'submit_at',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'submit_at' => 'datetime',
        'pretest_score' => 'decimal:2',
        'posttest_score' => 'decimal:2',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class, 'user_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function examAnswers()
    {
        return $this->hasMany(ExamAnswer::class, 'exam_record_id');
    }
}
