<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleIp extends Model
{
    protected $fillable = ['client_id','ip','sale_id'];
}
