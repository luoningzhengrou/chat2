<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\User;
use App\Models\UserAddFriend;
use App\Models\UserBuddy;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $user = User::where(['phone'=>$phone])->first(['id','nickname as username','phone','avatar']);
        if ($user){
            $this->data = $user;
        }else{
            $this->code = 404;
            $this->msg = '用户不存在';
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
        if (!$addFriends = UserAddFriend::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'status'=>0])->first()){
            try {
                $addFriend->user_id = $user_id;
                $addFriend->info = $info;
                $addFriend->to_user_id = $to_user_id;
                $addFriend->save();
            }catch (\Exception $exception){
                $this->code = 500;
                $this->msg = 'Failed';
                Log::channel('api_error')->info($exception->getMessage());
            }
        }else{
            $addFriends->info = $info;
            $addFriends->save();
        }
        Gateway::$registerAddress = '127.0.0.1:1236';
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
        $data = UserAddFriend::where(['to_user_id'=>$user_id])->orderBy('created_at','desc')->get(['user_id','info','created_at','status']);
        if ($data){
            foreach ($data as $k => $v){
                $this->data[$k]['user_id'] = $v['user_id'];
                $this->data[$k]['username'] = User::where('id',$v['user_id'])->value('nickname');
                $this->data[$k]['avatar'] = User::where('id',$v['user_id'])->value('avatar');
                $this->data[$k]['info'] = $v['info'];
                $this->data[$k]['send_time'] = date('Y-m-d H:i:s',strtotime($v['created_at']));
                $this->data[$k]['status'] = $v['status'];
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
        $time = date('Y-m-d H:i:s');
        $where = ['user_id'=>$to_user_id,'to_user_id'=>$user_id,'is_handle'=>0];
        if ($status == 1){
            $info = UserAddFriend::find(1)->where($where)->first();
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
                    DB::commit();
                }catch (\Exception $exception){
                    $this->code = 500;
                    $this->msg = 'Failed';
                    Log::channel('api_error')->info($exception->getMessage());
                    DB::rollBack();
                }
            }else{
                $this->code = 404;
                $this->msg = 'Not Found';
            }
        }else{
            UserAddFriend::where($where)->update(['status'=>$status,'is_handle'=>1,'r_info'=>$message,'verified_at'=>$time]);
        }
        return $this->response();
    }

    /**
     * 获取好友列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $user_id = $request->get('user_id');
        $users = UserBuddy::where(['user_id'=>$user_id,'status'=>1])->get(['to_user_id as user_id','is_show_phone','is_top']);
        foreach ($users as $k => $v){
            $this->data[$k]['user_id'] = $v['user_id'];
            $this->data[$k]['is_top'] = $v['is_top'];
            $user = User::where('id',$v['user_id'])->first(['id','nickname','avatar','area','sex']);
            $this->data[$k]['username'] = $user['nickname'];
            $this->data[$k]['avatar'] = $user['avatar'];
            $this->data[$k]['area'] = $user['area'];
            $this->data[$k]['sex'] = $user['sex'];
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
        $data = [];
        if (UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])->first() && $to_user = UserBuddy::where(['user_id'=>$to_user_id,'to_user_id'=>$user_id])->first()){
            if ($to_user['is_show_phone']){
                $data = User::where('id',$to_user_id)->first(['id','nickname as username','avatar','area','phone']);
            }else{
                $data = User::where('id',$to_user_id)->first(['id','nickname as username','avatar','area']);
            }
        }
        $this->data = $data;
        return $this->response();
    }


    /**
     * 获取聊天记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $s_time = date('Y-m-d H:i:s',strtotime($request->get('start_time')));
        $e_time = date('Y-m-d H:i:s',strtotime($request->get('end_time')));
        $sql = "SELECT user_id,to_user_id,content,created_at as send_time FROM hh_messages WHERE ((user_id = $user_id and to_user_id = $to_user_id and is_show = 1) OR (user_id = $to_user_id AND to_user_id = $user_id AND to_is_show = 1)) AND (created_at BETWEEN '$s_time' AND '$e_time')";
        $this->data = DB::select($sql);
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
        $list = Message::where(['to_user_id'=>$user_id,'is_send'=>0])->distinct('user_id')->get('user_id');
        foreach ($list as $k => &$v){
            $v['username'] = User::where('id',$v['user_id'])->value('nickname');
            $v['avatar'] = User::where('id',$v['user_id'])->value('avatar');
            $v['messages'] = Message::where(['user_id'=>$v['user_id'],'is_send'=>0])->get(['content','created_at as send_time']);
        }
        $this->data = $list;
        return $this->response();
    }

    /**
     *  删除单条消息  model 1 删除自己发出的   model 2 删除他人发出的
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOne(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $time = $request->get('send_time');
        $time = date('Y-m-d H:i:s',strtotime($time));
        $content = $request->get('content');
        $model = $request->get('model');
        try {
            $message = Message::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'created_at'=>$time,'content'=>$content])->first();
            if ($model == 1){
                $message->is_show = 0;
            }
            if ($model == 2){
                $message->to_is_show = 0;
            }
            $message->save();
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = 'Failed';
            Log::channel('api_error')->info($exception->getMessage());
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
            Log::channel('api_error')->info($exception->getMessage());
        }
        return $this->response();
    }

    /**
     * 获取聊天记录
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
            Log::channel('api_error')->info($exception->getMessage());
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
        $list = UserBuddy::where(['user_id'=>$user_id,'status'=>2])->get();
        foreach ($list as $k => $v){
            $user = User::where('id',$v['to_user_id'])->first(['id','nickname','avatar','area','sex','phone']);
            $this->data[$k]['id'] = $user['id'];
            $this->data[$k]['username'] = $user['nickname'];
            $this->data[$k]['avatar'] = $user['avatar'];
            $this->data[$k]['area'] = $user['area'];
            $this->data[$k]['sex'] = $user['sex'];
            if ($is_show_phone = UserBuddy::where(['user_id'=>$v['to_user_id'],'to_user_id'=>$v['user_id']])->value('is_show_phone')){
                if ($is_show_phone){
                    $this->data[$k]['phone'] = $user['phone'];
                }
            }
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
            Log::channel('api_error')->info($exception->getMessage());
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
        DB::beginTransaction();
        try {
            $buddy = UserBuddy::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->first();
            if ($buddy){
                UserBuddy::where(['user_id' => $user_id,'to_user_id'=>$del_user_id])->delete();
            }
            Message::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->update(['is_show'=>0]);
            Message::Where(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->update(['to_is_show'=>0]);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->code = 500;
            $this->msg = 'Failed';
            Log::channel('api_error')->info($exception->getMessage());
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
            Log::channel('api_error')->info($exception->getMessage());
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
     */
    public function getUserId(Request $request)
    {
        $token = $request->get('token');
        if ($id = User::where(['token'=>$token])->value('id')){
            $this->data['user_id'] = $id;
        }else{
            $this->code = 404;
            $this->msg = 'Failed';
        }
        return $this->response();
    }

}
