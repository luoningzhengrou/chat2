<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    public $timestamps = true;
    protected $fillable = ['user_id','is_send'];
    public $incrementing = true;

    public function messages()
    {
        return $this->hasMany('App\User');
    }
}
