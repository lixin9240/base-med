<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes;

    protected $table = 'video';

    protected $fillable = [
        'title',
        'category_id',
        'topic_id',
        'speaker',
        'speaker_title',
        'speaker_org',
        'brief_intro',
        'text_key_points',
        'source_type',
        'video_url',
        'oss_path',
        'oss_upload_id',
        'cover_url',
        'duration',
        'file_size',
        'transcode_status',
        'sort_order',
        'status',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function videoQuestions()
    {
        return $this->hasMany(VideoQuestion::class, 'video_id');
    }

    public function shareLinks()
    {
        return $this->hasMany(VideoShareLink::class, 'video_id');
    }

    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class, 'video_id');
    }
}
