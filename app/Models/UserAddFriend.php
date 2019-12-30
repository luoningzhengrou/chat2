<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddFriend extends Model
{
    protected $table = 'user_add_friends';

    public function selfInfo()
    {
        return $this->morphTo();
    }
}
