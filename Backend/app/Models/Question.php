<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $table = 'question';

    protected $fillable = [
        'type',
        'stem',
        'image_url',
        'options',
        'option_count',
        'correct_answer',
        'analysis',
        'difficulty',
        'knowledge_point',
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function videoQuestions()
    {
        return $this->hasMany(VideoQuestion::class, 'question_id');
    }

    public function examAnswers()
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }
}
