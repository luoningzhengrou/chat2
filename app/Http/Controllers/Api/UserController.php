<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserGroupCommentRequest;
use App\Models\Ban_types;
use App\Models\Complaints;
use App\Models\Message;
use App\Models\User;
use App\Models\UserAddFriend;
use App\Models\UserBuddy;
use App\Models\UserGroup;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected $phone_status = [0,1];

    /**
     * 查找好友 通过手机号
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function find(Request $request)
    {
        $phone = $request->get('phone');
        $key = env('REDIS_PREFIX') . 'find_' . $phone;
        $user = Redis::get($key);
        if (!$user || $this->debug){
            $user = User::where('phone',$phone)->where('is_cs',0)->first(['id','nickname as username','phone','avatar']);
            if ($user){
                Redis::setex($key,$this->timeout,json_encode($user));
                $this->data = $user;
            }else{
                $this->code = 404;
                $this->msg = '用户不存在';
            }
        }else{
            $this->data = json_decode($user,true);
        }
        return $this->response();
    }

    /**
     * 申请添加好友
     * @param Request $request
     * @param UserAddFriend $addFriend
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request, UserAddFriend $addFriend)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $info = $request->get('info');
        if (!$info){
            $info = '学习的道路上，愿与你同行！';
        }
        if ($user_id == $to_user_id){
            $this->code = 400;
            $this->msg = '不能添加自己';
            return $this->response();
        }
        if (!$addFriends = UserAddFriend::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'status'=>0])->first()){
            try {
                $addFriend->user_id = $user_id;
                $addFriend->info = $info;
                $addFriend->to_user_id = $to_user_id;
                $addFriend->save();
            }catch (\Exception $exception){
                $this->code = 500;
                $this->msg = 'Failed';
                $this->apiLog($exception->getMessage());
            }
        }else{
            $addFriends->info = $info;
            $addFriends->save();
        }
        if (Gateway::isUidOnline($to_user_id)){
            Gateway::sendToUid($to_user_id, json_encode(['code'=>600,'msg'=>'','data'=>[]]));
        }
        return $this->response();
    }

    /**
     * 获取添加好友信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAddInfo(Request $request)
    {
        $user_id = $request->get('user_id');
        $data = UserAddFriend::where(['to_user_id'=>$user_id])->orderBy('id','desc')->groupBy('user_id')->get(['user_id']);
        if ($data){
            $i = 0;
            foreach ($data as $k => $v){
                $info = '';
                $value = UserAddFriend::where(['user_id'=>$v['user_id'],'to_user_id'=>$user_id])->orderBy('created_at','desc')->first();
                $this->data[$i]['user_id'] = $value['user_id'];
                $this->data[$i]['username'] = User::where('id',$value['user_id'])->value('nickname');
                $this->data[$i]['avatar'] = User::where('id',$value['user_id'])->value('avatar');
                $info .= $value['info'] . ';';
                $this->data[$i]['info'] = $info;
                $this->data[$i]['send_time'] = date('Y-m-d H:i:s',strtotime($value['created_at']));
                $this->data[$i]['status'] = $value['status'];
                $i++;
            }
        }
        return $this->response();
    }

    /**
     * 添加好友 agree or reject
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $status = $request->get('status');
        $message = $request->get('message');
        if (!$message){
            $message = '';
        }
        $time = date('Y-m-d H:i:s');
        $where = ['user_id'=>$to_user_id,'to_user_id'=>$user_id,'is_handle'=>0];
        if ($status == 1){
            $info = UserAddFriend::where($where)->first();
            if ($info){
                DB::beginTransaction();
                try {
                    UserAddFriend::where($where)->update(['status'=>$status,'is_handle'=>1,'r_info'=>$message,'verified_at'=>$time]);
                    if (!UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->first()){
                        UserBuddy::create(['user_id'=>$user_id,'to_user_id'=>$to_user_id]);
                        if (!UserBuddy::where(['user_id'=>$to_user_id,'to_user_id'=>$user_id])->first()){
                            UserBuddy::create(['user_id'=>$to_user_id,'to_user_id'=>$user_id]);
                        }
                    }else{
                        if (!UserBuddy::where(['user_id'=>$to_user_id,'to_user_id'=>$user_id])->first()){
                            UserBuddy::create(['user_id'=>$to_user_id,'to_user_id'=>$user_id]);
                        }
                    }
                    if ($add = UserAddFriend::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'is_handle'=>0])->first()){
                        $add->is_handle = 1;
                        $add->status = 1;
                        $add->save();
                    }
                    $msg = 'agree';
                    $code = 601;
                    DB::commit();
                    $this->apiLog('添加成功');
                }catch (\Exception $exception){
                    $this->code = 500;
                    $this->msg = 'Failed';
                    $this->apiLog('添加失败');
                    $this->apiLog($exception->getMessage());
                    DB::rollBack();
                }
            }else{
                $this->apiLog('未找到好友请求信息');
                $this->code = 404;
                $this->msg = 'Not Found';
            }
        }else{
            UserAddFriend::where($where)->update(['status'=>$status,'is_handle'=>1,'r_info'=>$message,'verified_at'=>$time]);
            $msg = 'disagree';
            $code = 602;
            $this->apiLog('拒绝添加好友');
        }
        if (isset($msg) && isset($code)){
            if ($code == 601){
                $data = ['user_id'=>$user_id,'username'=>User::where('id',$user_id)->value('nickname'),'message'=>$message];
            }else{
                $data = ['message'=>$message];
            }
            if (Gateway::isUidOnline($to_user_id)){
                Gateway::sendToUid($to_user_id, json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data]));
                $this->apiLog('消息推送成功');
            }else{
                $this->apiLog('推送失败，对方不在线');
            }
        }
        $this->updateListCache($user_id);
        $this->updateListCache($to_user_id);
        return $this->response();
    }

    private function updateListCache($user_id)
    {
        $list_key = env('REDIS_PREFIX') . 'list_' . $user_id;
        $users = UserBuddy::where(['user_id'=>$user_id,'status'=>1])->get(['to_user_id as user_id','is_show_phone','is_top']);
        $i = 0;
        $list = [];
        foreach ($users as $k => $v){
            $list[$k]['user_id'] = $v['user_id'];
            $list[$k]['is_top'] = $v['is_top'];
            $user = User::where('id',$v['user_id'])->first(['id','nickname','avatar','area','sex']);
            $list[$k]['username'] = $user['nickname'];
            $list[$k]['avatar'] = $user['avatar'];
            $list[$k]['area'] = $user['area'];
            $list[$k]['sex'] = $user['sex'];
            $i++;
        }
        $customer_services = User::where(['status'=>0,'is_cs'=>1])->get(['id','avatar','nickname as username','area','sex']);
        foreach ($customer_services as $key => $val){
            $data[$key]['user_id'] = $val['id'];
            $data[$key]['is_cs'] = 1;
            $data[$key]['is_top'] = 1;
            $data[$key]['username'] = $val['username'];
            $data[$key]['avatar'] = $val['avatar'];
            $data[$key]['area'] = $val['area'];
            $data[$key]['sex'] = $val['sex'];
            array_splice($list,0,0,$data);
            $i++;
        }
        Redis::setex($list_key,$this->timeout,json_encode($list));
    }

    /**
     * 获取好友列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $user_id = $request->get('user_id');
        $list_key = env('REDIS_PREFIX') . 'list_' . $user_id;
        $list = Redis::get($list_key);
        if (!$list || $this->debug){
            $users = UserBuddy::where(['user_id'=>$user_id,'status'=>1])->get(['to_user_id as user_id','is_show_phone','is_top']);
            $i = 0;
            foreach ($users as $k => $v){
                $this->data[$k]['user_id'] = $v['user_id'];
                $this->data[$k]['is_top'] = $v['is_top'];
                $user = User::where('id',$v['user_id'])->first(['id','nickname','avatar','area','sex','phone']);
                if ($v['is_show_phone']){
                    $this->data[$k]['phone'] = $user['phone'];
                }
                $this->data[$k]['username'] = $user['nickname'];
                $this->data[$k]['avatar'] = $user['avatar'];
                $this->data[$k]['is_cs'] = 0;
                if (is_numeric($user['area'])){
                    $this->data[$k]['area'] = DB::table('city')->where('id',$user['area'])->value('cityname');
                }else{
                    $this->data[$k]['area'] = $user['area'];
                }
                $this->data[$k]['sex'] = $user['sex'];
                $i++;
            }
            // 客服
            $customer_services = User::where(['status'=>0,'is_cs'=>1])->get(['id','avatar','nickname as username','area','sex','phone']);
            foreach ($customer_services as $key => $val){
                $data[$key]['user_id'] = $val['id'];
                $data[$key]['is_cs'] = 1;
                $data[$key]['is_top'] = 1;
                $data[$key]['username'] = $val['username'];
                $data[$key]['avatar'] = $val['avatar'];
                $data[$key]['area'] = $val['area'];
                $data[$key]['sex'] = $val['sex'];
                array_splice($this->data,0,0,$data);
                $i++;
            }
            Redis::setex($list_key,$this->timeout,json_encode($this->data));
        }else{
            $this->data = json_decode($list,true);
        }
        return $this->response();
    }

    /**
     * 查看好友信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function friend(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        if (!self::checkUser($to_user_id)){
            $this->msg = '对方用户不存在';
            return $this->response();
        }
        if ($to_user = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->first()){
            if ($to_user['is_show_phone']){
                $data = User::where('id',$to_user_id)->first(['id','nickname as username','avatar','area','phone']);
            }else{
                $data = User::where('id',$to_user_id)->first(['id','nickname as username','avatar','area']);
            }
            if (is_numeric($data['area'])){
                $data['area'] = DB::table('city')->where('id',$data['area'])->value('cityname');
            }
        }else{
            $this->code = 403;
            $this->msg = '对方不是你的好友';
            $data = [];
        }
        $this->data = $data;
        return $this->response();
    }


    /**
     * 获取聊天记录
     * type 1 开始日期 多少条
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $limit = $request->get('limit');
        $current = $request->get('current');
        $sql = "SELECT id,user_id,to_user_id,type,content,created_at as send_time FROM hh_messages WHERE id > $current AND ((user_id = $user_id and to_user_id = $to_user_id and is_show = 1) OR (user_id = $to_user_id AND to_user_id = $user_id AND to_is_show = 1)) ORDER BY created_at ASC LIMIT $limit";
        $sql_total = "SELECT count(*) as total FROM hh_messages WHERE id > $current AND ((user_id = $user_id and to_user_id = $to_user_id and is_show = 1) OR (user_id = $to_user_id AND to_user_id = $user_id AND to_is_show = 1))";
        $data = DB::select($sql);
        $total = DB::select($sql_total);
        $this->data['total'] = $total[0]->total;
        $this->data['limit'] = $limit;
        $this->data['current_total'] = count($data);
        $this->data['data'] = $data;
        return $this->response();
    }

    /**
     * 获取所有未在线消息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllUnSendMessage(Request $request)
    {
        $user_id = $request->get('user_id');
        $list = Message::where(['to_user_id'=>$user_id,'is_arrived'=>0])->distinct()->get('user_id');
        foreach ($list as $k => &$v){
            $v['username'] = User::where('id',$v['user_id'])->value('nickname');
            $v['avatar'] = User::where('id',$v['user_id'])->value('avatar');
            $v['messages'] = Message::where(['user_id'=>$v['user_id'],'to_user_id'=>$user_id,'is_arrived'=>0])->get(['id','content','created_at as send_time','type']);
            Message::where(['to_user_id'=>$user_id,'user_id'=>$v['user_id'],'is_arrived'=>0])->update(['is_arrived'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
        }
        $new = UserAddFriend::where(['to_user_id'=>$user_id,'is_handle'=>0])->distinct()->get('id')->toArray();
        if (!empty($new)){
            UserAddFriend::where(['to_user_id'=>$user_id,'is_handle'=>0])->update(['is_send'=>1]);
            $this->msg = 1;
        }else{
            $this->msg = 0;
        }
        $this->data = $list;
        return $this->response();
    }

    /**
     *  删除单条消息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOne(Request $request)
    {
        $user_id = $request->get('user_id');
        $from_user_id = $request->get('from_user_id');
        $to_user_id = $request->get('to_user_id');
        $id = $request->get('id');
        $content = $request->get('content');
        try {
            $message = Message::where(['id'=>$id, 'user_id'=>$from_user_id,'to_user_id'=>$to_user_id,'content'=>$content])->first();
            if ($message['user_id'] == $user_id){
                if ($message->is_show != 0){
                    $message->is_show = 0;
                    $message->save();
                }
            }else{
                $message->to_is_show = 0;
                $message->save();
            }
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = 'Failed';
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 删除聊天记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteHistory(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        try {
            Message::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->update(['is_show'=>0]);
            Message::Where(['to_user_id'=>$user_id,'user_id'=>$to_user_id])->update(['to_is_show'=>0]);
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = 'Failed';
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 加入/解除黑名单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addBlack(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        try {
            $status = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->value('status');
            if ($status == 1){
                $status = 2;
            }else{
                $status = 1;
            }
            $this->data = ['status'=>$status];
            UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->update(['status'=>$status]);
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = 'Failed';
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 黑名单列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function blackList(Request $request)
    {
        $user_id = $request->get('user_id');
        $key_list = env('REDIS_PREFIX') . 'black_list_' . $user_id;
        $list = Redis::get($key_list);
        if (!$list || $this->debug){
            $list = UserBuddy::where(['user_id'=>$user_id,'status'=>2])->get();
            foreach ($list as $k => $v){
                $user = User::where('id',$v['to_user_id'])->first(['id','nickname','avatar','area','sex','phone']);
                $this->data[$k]['id'] = $user['id'];
                $this->data[$k]['username'] = $user['nickname'];
                $this->data[$k]['avatar'] = $user['avatar'];
                $this->data[$k]['area'] = $user['area'];
                $this->data[$k]['sex'] = $user['sex'];
                if (UserBuddy::where(['user_id'=>$v['to_user_id'],'to_user_id'=>$v['user_id']])->value('is_show_phone')){
                    $this->data[$k]['phone'] = $user['phone'];
                }
            }
            Redis::set($key_list,json_encode($this->data));
        }else{
            $this->data = json_decode($list,true);
        }
        return $this->response();
    }

    /**
     * 设置权限
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function shield(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        try {
            $status = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->value('is_show_phone');
            if ($status == 1){
                $status = 0;
            }else{
                $status = 1;
            }
            $this->data = ['status'=>$status];
            UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->update(['is_show_phone'=>$status]);
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = 'Failed';
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 解除好友关系 单向删除 标记聊天记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $user_id = $request->get('user_id');
        $del_user_id = $request->get('del_user_id');
        if ($user_id == $del_user_id){
            $this->code = 400;
            $this->msg = '不能删除自己';
            return $this->response();
        }
        DB::beginTransaction();
        try {
            $buddy = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->first();
            if ($buddy){
                UserBuddy::where(['user_id' => $user_id,'to_user_id'=>$del_user_id])->delete();
            }
            if (UserBuddy::where(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->first()){
                Message::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->update(['is_show'=>0]);
                Message::Where(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->update(['to_is_show'=>0]);
                UserAddFriend::where(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->delete();
            }else{
                Message::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->delete();
                Message::Where(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->delete();
                UserAddFriend::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->delete();
                UserAddFriend::where(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->delete();
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->code = 500;
            $this->msg = 'Failed';
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 置顶/取消
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function top(Request $request)
    {
        $user_id = $request->get('user_id');
        $top_user_id = $request->get('up_user_id');
        try {
            $user = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$top_user_id])->first();
            if ($user['is_top'] == 0){
                $user->is_top = 1;
            }else{
                $user->is_top = 0;
            }
            $this->data = ['is_top'=>$user->is_top];
            $user->save();
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = 'Failed';
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 获取好友权限
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function phoneStatus(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $status = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'status'=>1])->value('is_show_phone');
        if (in_array($status,$this->phone_status)){
            $this->data['status'] = $status;
        }else{
            $this->code = 404;
            $this->msg = 'Failed';
        }
        return $this->response();
    }

    /**
     * 获取用户ID BY Token
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserId(Request $request)
    {
        $token = $request->get('token');
        $key_token = env('REDIS_PREFIX') . '_token_' . $token;
        $id = Redis::get($key_token);
        if (!$id || $this->debug){
            if ($id = User::where(['token'=>$token])->orWhere(['user_token'=>$token])->value('id')){
                $this->data['user_id'] = $id;
                Redis::set($key_token,$id);
            }else{
                $this->code = 404;
                $this->msg = 'Failed';
            }
        }else{
            $this->data['user_id'] = $id;
        }
        return $this->response();
    }

    /**
     * 检查是否封禁
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkBan(Request $request)
    {
        $uid = $request->get('user_id');
        $key = env('REDIS_PREFIX') . 'check_ban_' . $uid;
        $data = Redis::get($key);
        if (!$data || $this->debug){
            $data = Complaints::where('user_id',$uid)
                ->where('status',1)
                ->where('t_time','>',date('Y-m-d H:i:s'))
                ->orderBy('t_time','desc')
                ->first();
            if (!empty($data)){
                $this->code = 403;
                $this->data['username'] = DB::table('user')->where(['id'=>$uid])->value('nickname');
                $this->data['start_date'] = $data['p_time'];
                $this->data['date'] = $data['t_time'];
                $this->data['info'] = Ban_types::where(['id'=>$data['c_ban_id']])->value('info');
                $expire_time = strtotime($data['t_time']) - time();
                if ($expire_time > 0){
                    Redis::setex($key,$expire_time,json_encode($this->data));
                }
            }else{
//                Complaints::where('user_id',$uid)
//                    ->where('status',1)
//                    ->where('t_time','<=',date('Y-m-d H:i:s'))
//                    ->update(['status'=>4]);
            }

        }else{
            $this->data = $data;
        }
        return $this->response();
    }

    /**
     * 投诉
     * @param Request $request
     * @param Complaints $complaints
     * @return \Illuminate\Http\JsonResponse
     */
    public function complaint(Request $request, Complaints $complaints)
    {
        $number = $request->get('number');
        if ($number > 9){
            $this->msg = 502;
            $this->msg = 'Picture is too many';
            return $this->response();
        }
        $disk = Storage::disk('oss');
        $date = date('Y-m-d');
        $uid = $request->get('user_id');
        $tid = $request->get('to_user_id');
        DB::beginTransaction();
        try {
            $url = '';
            for ($i = 1; $i <= $number; $i++){
                $picture = $request->file('picture' . $i);
                $file_name = 'complaint/'  . $date;
                $res = $disk->put($file_name, $picture);
                if ($number == $i){
                    $url .= $disk->getUrl($res);
                }else{
                    $url .= $disk->getUrl($res) . ',';
                }
            }
            $complaints->user_id = $tid;
            $complaints->c_user_id = $uid;
            $complaints->ban_id = $request->get('ban_id');
            $complaints->info = $request->get('info');
            $complaints->picture = $url;
            $complaints->status = 0;
            $complaints->save();
            DB::commit();
            $this->logHandle('websocket_message',$url);
        }catch (\Exception $exception){
            DB::rollBack();
            $this->errorHandle($exception->getMessage());
            $this->apiLog($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * Api 日志
     * @param $info
     */
    private function apiLog($info)
    {
        Log::channel('api_error')->info($info);
    }

    /**
     * 投诉类型
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBan(){
        $this->data = Ban_types::where(['status'=>1,'is_home'=>1])->get(['id','info']);
        return $this->response();
    }

    public function messageResponse(Request $request)
    {
        $message_id = $request->get('message_id');
        $now_status = DB::table('messages')->where('id',$message_id)->value('is_arrived');
        if ($now_status == 0){
            DB::beginTransaction();
            try {
                DB::table('messages')->where('id',$message_id)->update(['is_arrived'=>1]);
                DB::commit();
            }catch (\Exception $exception){
                DB::rollBack();
                $this->errorHandle($exception->getMessage());
                $this->apiLog($exception->getMessage());
            }
        }
        return $this->response();
    }

    public function updateGroup(UserGroupCommentRequest $request)
    {
        if (!$userGroup = UserGroup::where(['group_id'=>$request->group_id,'user_id'=>$request->user_id])->first()){
            $this->infoHandle('信息不存在');
            return $this->response();
        }

        $userGroup->name_group = $request->name_group;
        $userGroup->save();

        return $this->response();
    }

}
