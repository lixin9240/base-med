<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_config';

    protected $fillable = [
        'config_key',
        'config_value',
        'remark',
        'updated_by',
    ];

    public function updater()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }
}
