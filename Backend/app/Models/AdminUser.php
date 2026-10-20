<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * 管理员模型
 *
 * @method \Laravel\Sanctum\PersonalAccessToken|null currentAccessToken()
 */
class AdminUser extends Model
{
    use HasApiTokens;
    use SoftDeletes;

    protected $table = 'admin_user';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'name',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function videos()
    {
        return $this->hasMany(Video::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function shareLinks()
    {
        return $this->hasMany(VideoShareLink::class, 'created_by');
    }

    public function importTasks()
    {
        return $this->hasMany(ImportTask::class, 'created_by');
    }

    public function operationLogs()
    {
        return $this->hasMany(OperationLog::class, 'admin_id');
    }
}
