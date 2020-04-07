<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleIpHistories extends Model
{
    protected $fillable = ['client_id','ip','sale_id'];
}
