<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBuddy extends Model
{
    protected $table = 'user_buddies';
    protected $fillable = ['user_id','to_user_id','status'];

    public function username()
    {
        return $this->hasOne('App\User');
    }

}
