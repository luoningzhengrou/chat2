<?php

namespace App\Http\Controllers\Websocket;

use App\Http\Controllers\Controller;
use GatewayClient\Gateway;
use Illuminate\Http\Request;

class WebsocketController extends Controller
{
    public function chat(Request $request)
    {
        $
        $client_id = $request->get('client_id');
        $uid = $request->get('user_id');
        $message = $request->get('content');
//        $client_id = '7f00000108fc00000001';
//        $uid = 1;
//        $gid = $request->get('group_id');
//        var_dump($request->toArray());exit;

        Gateway::$registerAddress = '127.0.0.1:1236';
        Gateway::bindUid($client_id,$uid);
        Gateway::sendToUid($uid, $message);

        // 加入某个群组（可调用多次加入多个群组）
//        Gateway::joinGroup($client_id, $group_id);

    }

    public function onMessage()
    {
        Gateway::$registerAddress = '127.0.0.1:1236';

    }
}
