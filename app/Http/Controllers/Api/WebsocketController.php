<?php

namespace App\Http\Controllers\Api;

use App\Models\Ban_types;
use App\Models\Complaints;
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
//        if (!self::checkFriend($uid,$tid)){
//            return $this->response();
//        }
        $content = $request->get('content');
        if (mb_strlen($content) > 2048){
            $this->code = 502;
            $this->msg = 'Message is too long!';
            return $this->response();
        }
        Gateway::$registerAddress = '127.0.0.1:1236';
        if (!self::checkOnline($uid)){
            return $this->response();
        }
        if (!self::checkFriend($uid,$tid)){
            return $this->response();
        }
        if (!self::checkBlackList($uid,$tid)){
            return $this->response();
        }
        if (!self::checkBan($uid)){
            return $this->response();
        }
        $message->fill($request->all());
        $message->user_id = $uid;
        $message->to_user_id = $tid;
        $message->content = $content;
        $data['from_user_id'] = $uid;
        $data['from_username'] = User::where('id',$uid)->value('nickname');
        $data['content'] = $content;
        $data['type'] = 1;
        $data['send_time'] = date('Y-m-d H:i:s');
        $message->is_send = $this->push($uid,$tid,$data);
        $message->save();
        return $this->response();
    }

    /**
     * 检查是否在线
     * @param $uid
     * @return boolean
     */
    private function checkOnline($uid)
    {
        $msg = true;
        if (Gateway::isUidOnline($uid) == 0){
            $this->code = 400;
            $this->msg = '你已离线';
            $msg = false;
        }
        return $msg;
    }

    /**
     * 检查是否是好友
     * @param $uid
     * @param $tid
     * @return boolean
     */
    private function checkFriend($uid,$tid)
    {
        $msg = true;
        if (!UserBuddy::where(['user_id'=>$uid,'to_user_id'=>$tid])->first() || !UserBuddy::where(['user_id'=>$tid,'to_user_id'=>$uid])->first()){
            $this->code = 404;
            $this->msg = '请先添加好友';
            $msg = false;
        }
        return $msg;
    }

    /**
     * 检查是否黑名单
     * @param $uid
     * @param $tid
     * @return boolean
     */
    private function checkBlackList($uid,$tid)
    {
        $msg = true;
        if ($user = UserBuddy::where(['user_id'=>$tid,'to_user_id'=>$uid])->first()){
            if ($user['status'] == 0){
                $this->code = 403;
                $this->msg = '你不是对方的好友';
                $msg = false;
            }
            if ($user['status'] == 2){
                $this->code = 403;
                $this->msg = '消息已发出，但对方已拒绝接收';
                $msg = false;
            }
        }
        return  $msg;
    }

    /**
     * @param $uid
     * @return bool
     */
    private function checkBan($uid){
        $msg = true;
        $db = new Complaints();
        if ($data = $db->where(['user_id'=>$uid,'status'=>1])->orderBy('t_time','desc')->first()){
            $this->code = 403;
            $this->msg = '你已被封禁至' . $data['t_time'];
            $msg = false;
        }
        return $msg;
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
        if ($number > 9){
            $this->code = 502;
            $this->msg = 'Picture is too many';
            return $this->response();
        }
        $disk = Storage::disk('oss');
        $date = date('Y-m-d');
        Gateway::$registerAddress = '127.0.0.1:1236';
        if (!self::checkOnline($uid)){
            return $this->response();
        }
        if (!self::checkFriend($uid,$tid)){
            return $this->response();
        }
        if (!self::checkBlackList($uid,$tid)){
            return $this->response();
        }
        if (!self::checkBan($uid)){
            return $this->response();
        }
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
                $data['type'] = 2;
                $is_send = $this->push($uid,$tid,$data);    //推送
                Message::create(['user_id'=>$uid,'to_user_id'=>$tid,'content'=>$url,'is_send'=>$is_send,'type'=>2]);
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
