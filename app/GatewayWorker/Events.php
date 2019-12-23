<?php


namespace App\GatewayWorker;

use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Log;

class Events
{

    // 当有客户端连接时，将client_id返回，让框架判断当前uid并执行绑定
    public static function onConnect($client_id)
    {
        Log::info('open connection' . $client_id);
        Gateway::sendToClient($client_id, json_encode(array(
            'type'      => 'init',
            'client_id' => $client_id
        )));
    }

    public static function onWebSocketConnect($client_id, $data)
    {

    }

    public static function onMessage($client_id, $message)
    {

    }

    public static function onClose($client_id)
    {
        Log::info('close connection' . $client_id);
    }

}
