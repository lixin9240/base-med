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

    /**
     * 归一化正确答案
     *
     * 判断题统一存「正确/错误」，与用户端 user_answer 编码保持一致；
     * 入参写 A/B 时自动转换（A=正确、B=错误），其余题型原样返回。
     */
    public static function normalizeCorrectAnswer(string $type, string $answer): string
    {
        $answer = trim($answer);

        if ($type !== 'judge') {
            return $answer;
        }

        return match (strtoupper($answer)) {
            'A' => '正确',
            'B' => '错误',
            default => $answer,
        };
    }

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
