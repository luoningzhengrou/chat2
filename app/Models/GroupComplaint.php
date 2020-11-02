<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupComplaint extends Model
{
    protected $table = 'chat_group_complaint';

    public function punish()
    {
        return $this->hasOne('App\Models\GroupComplaintPunish');
    }

    public function reason()
    {
        return $this->hasOne('App\Models\GroupComplaintReason');
    }

}
