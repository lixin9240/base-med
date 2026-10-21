<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportTask extends Model
{
    protected $table = 'import_task';

    protected $fillable = [
        'task_no',
        'file_name',
        'file_path',
        'video_id',
        'total_rows',
        'valid_rows',
        'error_rows',
        'status',
        'error_report',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'error_report' => 'array',
        'completed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}
