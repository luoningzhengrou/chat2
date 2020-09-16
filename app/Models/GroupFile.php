<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupFile extends Model
{
    protected $table = 'chat_group_files';
    protected $fillable = ['name', 'file_url', 'user_id', 'group_id'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
