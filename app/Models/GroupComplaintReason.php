<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupComplaintReason extends Model
{
    protected $table = 'chat_group_complaint_reason';

    public function complaint()
    {
        return $this->belongsTo('App\Models\GroupComplaint');
    }

}
