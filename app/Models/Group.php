<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'chat_groups';
    protected $fillable = ['name', 'announcement', 'is_only_manage_chat', 'is_only_manage_invite', 'prohibit_user_ids', 'code', 'start_time', 'end_time'];

    public function manager()
    {
        return $this->hasOne('App\User');
    }

    public function user()
    {
        return $this->hasMany(GroupUser::class);
    }

    public function file()
    {
        return $this->hasMany(GroupFile::class);
    }
}
