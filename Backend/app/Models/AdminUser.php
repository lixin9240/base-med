<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * 管理员模型
 */
class AdminUser extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;
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

    /**
     * JWT 载荷中的唯一标识
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * JWT 自定义载荷
     */
    public function getJWTCustomClaims()
    {
        return [
            'username' => $this->username,
            'role' => $this->role,
        ];
    }

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
