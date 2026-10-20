<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'operation_log';

    protected $fillable = [
        'admin_id',
        'module',
        'action',
        'target_id',
        'detail',
        'ip',
        'created_at',
    ];

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}
