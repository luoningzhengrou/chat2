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
}
