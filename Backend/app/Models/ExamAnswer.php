<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    const UPDATED_AT = null;

    protected $table = 'exam_answer';

    protected $fillable = [
        'exam_record_id',
        'question_id',
        'stage',
        'user_answer',
        'is_correct',
        'score',
        'duration',
        'created_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function examRecord()
    {
        return $this->belongsTo(ExamRecord::class, 'exam_record_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
