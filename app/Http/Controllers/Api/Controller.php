<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    public $code = 200;
    public $msg = 'success';
    public $data = [];
    protected $timeout;
    protected $debug;

    public function __construct()
    {
        $this->timeout = env('REDIS_TIMEOUT',864000);
        $this->debug = env('APP_DEBUG',false);
    }

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
