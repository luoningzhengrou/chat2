<?php

namespace App\Http\Controllers\Api;

use App\Models\Ban_types;
use App\Models\ChatIp;
use App\Models\ChatIpHistory;
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
    protected $error_code = 400;

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
                if (Gateway::isOnline($client_id)){
                    if (Gateway::isUidOnline($uid)){
                        $data = [
                            'code' => 302,
                            'msg'  => '您已在新设备登陆!',
                            'data' => [],
                            'content' => '您已在新设备登陆!'
                        ];
                        $this->push($uid,$uid,$data);
                        $old_client_id = Gateway::getClientIdByUid($uid);
                        if ($old_client_id){
                            Gateway::unbindUid($old_client_id[0],$uid);
                            Gateway::closeClient($old_client_id[0]);
                        }
                        $this->logHandle('websocket',$data);
                        $this->logHandle('websocket', $uid . ': ' . $old_client_id[0] . ' is unBind!');
                        sleep(1);
                    }
                    Gateway::bindUid($client_id,$uid);
                    $client_id_session = Gateway::getSession($client_id);
                    if (isset($client_id_session['ip'])){
                        $ip = $client_id_session['ip'];
                        $ip = ip2long($ip);
                        ChatIpHistory::create(['client_id'=>$client_id,'user_id'=>$uid,'ip'=>$ip]);
                        $ip_user = ChatIp::where(['user_id'=>$uid])->first();
                        if ($ip_user){
                            $ip_user->ip = $ip;
                            $ip_user->client_id = $client_id;
                            $ip_user->save();
                        }else{
                            ChatIp::create(['client_id'=>$client_id,'user_id'=>$uid,'ip'=>$ip]);
                        }
                    }
                }else{
                    $this->code = 404;
                    $this->msg = 'client_id not found!';
                }
                $this->logHandle('websocket','user_id ' . $uid . ' bind client_id ' . $client_id . ';');
            }catch (\Exception $exception){
                $this->errorHandle($exception->getMessage());
                $this->logHandle('websocket_error','user_id ' . $uid . ' bind client_id ' . $client_id . 'failed: ' . $exception->getMessage() . ';');
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
    public function chat(Request $request)
    {
        $uid = $request->get('user_id');
        $tid = $request->get('to_user_id');
        if (!self::checkUser($uid)){
            $this->msg = '你的用户不存在';
            return $this->response();
        }
        if (!self::checkUser($tid)){
            $this->msg = '对方用户不存在';
            return $this->response();
        }
        if ($uid == $tid){
            $this->code = 400;
            $this->msg = '不能发送消息给自己';
            return $this->response();
        }
        $content = $request->get('content');
        if (mb_strlen($content) > 2048){
            $this->code = 502;
            $this->msg = 'Message is too long!';
            return $this->response();
        }
        if (!self::checkOnline($uid)){
            return $this->response();
        }
        if (!User::where('id',$tid)->value('is_cs') && !User::where('id',$uid)->value('is_cs')){
            if (!self::checkFriend($uid,$tid)){
                return $this->response();
            }
            if (!self::checkBan($uid)){
                return $this->response();
            }
            if (self::checkBlack($tid)){
                $this->msg = '对方已被封禁';
                return $this->response();
            }
            if (!self::checkBlackList($uid,$tid)){
                return $this->response();
            }
        }
        $date_time = date('Y-m-d H:i:s');
        $data['user_id'] = $uid;
        $data['to_user_id'] = $tid;
        $data['content'] = $content;
        $data['type'] = 1;
        $data['created_at'] = $date_time;
        $id = DB::table('messages')->insertGetId($data);
        $data_r['id'] = $id;
        $data_r['from_user_id'] = $uid;
        $data_r['from_username'] = User::where('id',$uid)->value('nickname');
        $data_r['content'] = $content;
        $data_r['type'] = $data['type'];
        $data_r['send_time'] = $date_time;
        if ($this->push($uid,$tid,$data_r)){
            DB::table('messages')->where(['id'=>$id])->update(['is_send'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
        }
        $this->data = ['id'=>$id,'send_time'=>$date_time];
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
            $this->msg = '网络连接失败';
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
        if ($data = $db->where(['user_id'=>$uid,'status'=>1])->where('t_time','>',date('Y-m-d H:i:s'))->orderBy('t_time','desc')->first()){
            $this->code = 403;
            $this->msg = '你已被封禁至' . $data['t_time'];
            $msg = false;
        }
        return $msg;
    }

    /**
     *  检查是否被封禁
     * @param $uid
     * @return bool
     */
    private function checkBlack($uid){
        $status = false;
        $db = new Complaints();
        if ($data = $db->where(['user_id'=>$uid,'status'=>1])->where('t_time','>',date('Y-m-d H:i:s'))->orderBy('t_time','desc')->first()){
            $this->code = 403;
            $status = $data['t_time'];
        }
        return $status;
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
                $this->msg = $this->error_msg;
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
        if ($uid == $tid){
            $this->code = 400;
            $this->msg = '不能发送给自己';
            return $this->response();
        }
        $number = $request->get('number');
        if ($number > 9){
            $this->code = 502;
            $this->msg = 'Picture is too many';
            return $this->response();
        }
        $disk = Storage::disk('oss');
        $date = date('Y-m-d');
        if (!self::checkOnline($uid)){
            return $this->response();
        }
        if (!User::where('id',$tid)->value('is_cs') && !User::where('id',$uid)->value('is_cs')){
            if (!self::checkUser($uid)){
                $this->code = 404;
                $this->msg = '你的用户不存在';
                return $this->response();
            }
            if (!self::checkUser($tid)){
                $this->code = 404;
                $this->msg = '对方用户不存在';
                return $this->response();
            }
            if (!self::checkFriend($uid,$tid)){
                return $this->response();
            }
            if (!self::checkBlackList($uid,$tid)){
                return $this->response();
            }
            if (self::checkBlack($tid)){
                $this->msg = '对方已被封禁';
                return $this->response();
            }
            if (!self::checkBan($uid)){
                return $this->response();
            }
        }
        $date_time = date('Y-m-d H:i:s');
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
                $data['send_time'] = $date_time;
                $data['type'] = 2;
                $id = DB::table('messages')->insertGetId(['user_id'=>$uid,'to_user_id'=>$tid,'content'=>$url,'type'=>2,'created_at'=>$date_time]);
                $data['id'] = $id;
                $is_send = $this->push($uid,$tid,$data);    //推送
                if ($is_send){
                    DB::table('messages')->where(['id'=>$id])->update(['is_send'=>$is_send, 'updated_at'=>date('Y-m-d H:i:s')]);
                }
                $this->data[$i]['id'] = $id;
                $this->data[$i]['url'] = $url;
                $this->data[$i]['send_time'] = $date_time;
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->code = 500;
            Log::channel('api_error')->info($exception->getMessage());
        }
        return $this->response();
    }

    public function getOnlineList()
    {
        try {
            if (Gateway::getAllUidCount()){
                $list = Gateway::getAllUidList();
                $this->data = $list;
            }
        }catch (\Exception $exception){
            $exception = $exception->getMessage();
            $this->code = $this->error_code;
            $this->msg = $exception;
        }
        return $this->response();
    }

}
