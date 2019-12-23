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


    public function __construct(Request $request)
    {
        $token = $request->get('token');
        if (!User::where('token',$token)->first()){
            $this->code = 403;
            $this->msg = '未登录';
            return $this->response();
        }
    }

    // 返回公用方法
    public function response()
    {
        return [
            'code' => $this->code,
            'msg'  => $this->msg,
            'data' => $this->data
        ];
    }

}
