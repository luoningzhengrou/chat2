<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'user';

    public function addInfo()
    {
        return $this->morphMany(UserAddFriend::class,'selfInfo');
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(GroupUser::class);
    }

    public function groupUser()
    {
        return $this->hasOne(GroupUser::class);
    }
}
