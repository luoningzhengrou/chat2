<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\User;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebsocketController extends Controller
{

    // 绑定
    public function bind(Request $request)
    {
        $uid = $request->get('user_id');
        $client_id = $request->get('client_id');
        if (!empty($client_id)){
            try {
                Gateway::$registerAddress = '127.0.0.1:1236';
                Gateway::bindUid($client_id,$uid);
                Log::channel('websocket')->info('user_id ' . $uid . ' bind client_id ' . $client_id . ';');
            }catch (\Exception $exception){
                $this->code = 500;
                $this->msg = $exception->getMessage();
                Log::channel('websocket_error')->info('user_id ' . $uid . ' bind client_id ' . $client_id . 'failed: ' . $this->msg . ';');
            }
        }else{
            $this->code = 403;
            $this->msg = 'client_id can\'t be null!';
        }
        return $this->response();
    }

    // 聊天
    public function chat(Request $request, Message $message)
    {
        $uid = $request->get('user_id');
        $to_uid = $request->get('to_user_id');
        $content = $request->get('content');
        Gateway::$registerAddress = '127.0.0.1:1236';
        if (Gateway::isUidOnline($uid) == 0){
            $this->code = 400;
            $this->msg = '你已离线';
            return $this->response();
        }
        $message->fill($request->all());
        $message->user_id = $uid;
        $message->to_user_id = $to_uid;
        $message->content = $content;
        $message->is_send = 1;
        $data['from_user_id'] = $uid;
        $data['from_username'] = User::where('id',$uid)->value('nickname');
        $data['content'] = $content;
        $data['send_time'] = date('Y-m-d H:i:s');
        if (Gateway::isUidOnline($to_uid) == 0){
            // 对方已离线,标记为未推送
            $message->is_send = 0;
            Log::channel('websocket_message')->info('user_id: ' . $uid . ' send to user_id: ' . $to_uid . ' content: ' . $content . ';');
        }else{
            try {
                Gateway::sendToUid($to_uid, json_encode($data));
                Log::channel('websocket_message')->info('user_id: ' . $uid . ' send to user_id: ' . $to_uid . ' content: ' . $content . ';');
            }catch (\Exception $exception){
                $message->is_send = 0;
                $this->msg = $exception->getMessage();
                Log::channel('websocket_error')->info('user_id: ' . $uid . ' send to user_id: ' . $to_uid . ' content: ' . $content . ' failed;');
            }
        }
        $message->save();
        return $this->response();
    }

}
