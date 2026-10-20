<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'video_question';

    protected $fillable = [
        'video_id',
        'stage',
        'question_id',
        'sort_order',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
