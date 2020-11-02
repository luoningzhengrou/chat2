<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupComplaintPunish extends Model
{
    protected $table = 'chat_group_complaint_punish';

    public function complaint()
    {
        return $this->belongsTo('App\Models\GroupComplaint');
    }
}
