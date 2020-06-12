<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use GatewayClient\Gateway;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    public $code = 200;
    public $msg = 'success';
    public $error_msg = 'System is busy, please try again later!';
    public $data = [];
    protected $timeout;
    protected $debug;

    public function __construct()
    {
        $this->timeout = env('REDIS_TIMEOUT',864000);
        $this->debug = env('APP_DEBUG',false);
        Gateway::$registerAddress = '127.0.0.1:' . env('WS_PORT','1236');
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

    /**
     *  错误处理
     * @param $error_msg
     */
    public function errorHandle($error_msg)
    {
        $this->code = 500;
        $this->msg = $this->error_msg;
        if ($this->debug){
            $this->msg = $error_msg;
        }
    }

    /**
     *  日志处理
     * @param $channel
     * @param $info
     */
    public function logHandle($channel = 'websocket',$info = '')
    {
        if (!is_string($info)){
            $info = json_encode($info);
        }
        Log::channel($channel)->info($info);
    }

}
