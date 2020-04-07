<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatIpHistory extends Model
{
    protected $fillable = ['client_id','ip','user_id'];

}
