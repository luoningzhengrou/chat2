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
//        $token = $request->get('token');
//        if (!User::find(1)->where('token',$token)->first()){
//            $this->error_code = 1;
//            $this->msg = '未登录';
//            return response()->json(['error_code'=>$this->error_code,'msg'=>$this->msg,'response_info'=>$this->response_info]);
//        }
    }
}
