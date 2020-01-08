<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\User;
use App\Models\UserBuddy;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WebsocketController extends Controller
{

    /**
     * 绑定
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                $this->msg = 'Failed';
                Log::channel('websocket_error')->info('user_id ' . $uid . ' bind client_id ' . $client_id . 'failed: ' . $this->msg . ';');
            }
        }else{
            $this->code = 403;
            $this->msg = 'client_id can\'t be null!';
        }
        return $this->response();
    }

    /**
     * 聊天
     * @param Request $request
     * @param Message $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request, Message $message)
    {
        $uid = $request->get('user_id');
        $tid = $request->get('to_user_id');
        $content = $request->get('content');
        Gateway::$registerAddress = '127.0.0.1:1236';
        self::checkOnline($uid) && self::checkFriend($uid,$tid) && self::checkBlackList($uid,$tid);
        $message->fill($request->all());
        $message->user_id = $uid;
        $message->to_user_id = $tid;
        $message->content = $content;
        $data['from_user_id'] = $uid;
        $data['from_username'] = User::where('id',$uid)->value('nickname');
        $data['content'] = $content;
        $data['send_time'] = date('Y-m-d H:i:s');
        $message->is_send = $this->push($uid,$tid,$data);
        $message->save();
        return $this->response();
    }

    /**
     * 检查是否在线
     * @param $uid
     * @return \Illuminate\Http\JsonResponse
     */
    private function checkOnline($uid)
    {
        if (Gateway::isUidOnline($uid) == 0){
            $this->code = 400;
            $this->msg = '你已离线';
            return $this->response();
        }
    }

    /**
     * 检查是否是好友
     * @param $uid
     * @param $tid
     * @return \Illuminate\Http\JsonResponse
     */
    private function checkFriend($uid,$tid)
    {
        if (!UserBuddy::where(['user_id'=>$uid,'to_user_id'=>$tid])->first() || !UserBuddy::where(['user_id'=>$tid,'to_user_id'=>$uid])->first()){
            $this->code = 404;
            $this->msg = '请先添加好友';
            return $this->response();
        }
    }

    /**
     * 检查是否黑名单
     * @param $uid
     * @param $tid
     * @return \Illuminate\Http\JsonResponse
     */
    private function checkBlackList($uid,$tid)
    {
        if ($user = UserBuddy::where(['user_id'=>$tid,'to_user_id'=>$uid])->first()){
            if ($user['status'] == 0){
                $this->code = 403;
                $this->msg = '你不是对方的好友';
                return $this->response();
            }
            if ($user['status'] == 2){
                $this->code = 403;
                $this->msg = '消息已发出，但对方已拒绝接收';
                return $this->response();
            }
        }
    }

    /**
     * 推送
     * @param $uid
     * @param $tid
     * @param $data
     * @return int
     */
    private function push($uid,$tid,$data)
    {
        if (Gateway::isUidOnline($tid) == 0){
            // 对方已离线,标记为未推送
            $is_send = 0;
            Log::channel('websocket_message')->info('user_id: ' . $uid . ' send to user_id: ' . $tid . ' content: ' . $data['content'] . ';');
        }else{
            try {
                Gateway::sendToUid($tid, json_encode($data));
                $is_send = 1;
                Log::channel('websocket_message')->info('user_id: ' . $uid . ' send to user_id: ' . $tid . ' content: ' . $data['content'] . ';');
            }catch (\Exception $exception){
                $is_send = 0;
                $this->msg = 'Failed';
                Log::channel('websocket_error')->info('user_id: ' . $uid . ' send to user_id: ' . $tid . ' content: ' . $data['content'] . ' failed;');
            }
        }
        return $is_send;
    }

    /**
     * 发送图片接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPicture(Request $request)
    {
        $uid = $request->get('user_id');
        $tid = $request->get('to_user_id');
        $number = $request->get('number');
        $disk = Storage::disk('oss');
        $date = date('Y-m-d');
        Gateway::$registerAddress = '127.0.0.1:1236';
        self::checkOnline($uid) && self::checkFriend($uid,$tid) && self::checkBlackList($uid,$tid);
        DB::beginTransaction();
        try {
            for ($i = 1; $i <= $number; $i++){
                $picture = $request->file('picture' . $i);
                $file_name = 'chat/'  . $date;
                $res = $disk->put($file_name, $picture);
                $url = $disk->getUrl($res);
                $data['from_user_id'] = $uid;
                $data['from_username'] = User::where('id',$uid)->value('nickname');
                $data['content'] = $url;
                $data['send_time'] = date('Y-m-d H:i:s');
                $is_send = $this->push($uid,$tid,$data);
                Message::create(['user_id'=>$uid,'to_user_id'=>$tid,'content'=>$url,'is_send'=>$is_send]);
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->code = 500;
            Log::channel('api_error')->info($exception->getMessage());
        }
        return $this->response();
    }

}
