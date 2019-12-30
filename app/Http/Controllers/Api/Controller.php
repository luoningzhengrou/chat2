<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    public $code = 200;
    public $msg = 'success';
    public $data = [];

    // 返回公用方法
    public function response()
    {
        return response()->json([
            'code' => $this->code,
            'msg'  => $this->msg,
            'data' => $this->data
        ]);
    }

}
