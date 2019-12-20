<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBuddy extends Model
{
    protected $table = 'user_buddies';
    protected $fillable = ['user_id','buddy'];

}
