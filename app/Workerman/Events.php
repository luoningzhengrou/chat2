<?php


namespace App\Workerman;


use GatewayClient\Gateway;
use Illuminate\Support\Facades\Log;

class Events
{
    public static function onWorkerStart($businessWorker)
    {
    }

    public static function onConnect($client_id)
    {
        $session = ['ip'=>$_SERVER['REMOTE_ADDR']];
        Gateway::setSession($client_id,$session);
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
        Log::channel('websocket_error')->info($client_id . '断开连接！');
    }
}
