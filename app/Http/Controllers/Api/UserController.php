<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\User;
use App\Models\UserAddFriend;
use App\Models\UserBuddy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    // 查找好友 通过手机号
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

    // 申请添加好友
    public function send(Request $request, UserAddFriend $addFriend)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $info = $request->get('info');
        try {
            $addFriend->user_id = $user_id;
            $addFriend->info = $info;
            $addFriend->to_user_id = $to_user_id;
            $addFriend->save();
        }catch (\Exception $exception){
            $this->code = 500;
            $this->msg = $exception->getMessage();
        }
        return $this->response();
    }

    // 添加好友 agree or reject
    public function add(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $status = $request->get('status');
        $message = $request->get('message');
        $id = $request->get('id');
        $time = date('Y-m-d H:i:s');
        $where = ['id'=>$id,'user_id'=>$user_id,'to_user_id'=>$to_user_id,'status'=>0,'is_handle'=>0];
        if ($status == 1){
            $info = UserAddFriend::find(1)->where($where)->first();
            if ($info){
                DB::beginTransaction();
                try {
                    UserAddFriend::where($where)->update(['status'=>$status,'is_handle'=>1,'r_info'=>'','verified_at'=>$time]);
                    $buddy = UserBuddy::where(['user_id'=>$to_user_id])->first();
                    if (!$buddy){
                        $userBuddy = UserBuddy::create(['user_id'=>$to_user_id,'to_user_id'=>$user_id,'buddy'=>$user_id]);
                        $buddy = UserBuddy::where(['user_id'=>$user_id])->first();
                        if (!$buddy){
                            $userBuddy->fill(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'buddy'=>$to_user_id]);
                        }else{
                            $this->msg = '已是好友，无需重复添加';
                        }
                    }else{
                        $buddy_arr = explode(',',$buddy['buddy']);
                        $this->data = $buddy_arr;
                        if (!in_array($user_id,$buddy_arr)){
                            if ($buddy['buddy'] != ''){
                                $buddy['buddy'] .= ',' . $user_id;
                            }else{
                                $buddy['buddy'] = $user_id;
                            }
                            UserBuddy::where(['user_id'=>$to_user_id])->update(['buddy'=>$buddy['buddy']]);
                        }
                    }
                    $buddy = UserBuddy::where(['user_id'=>$user_id])->first();
                    if (!$buddy){
                        UserBuddy::create(['user_id'=>$user_id,'to_user_id'=>$to_user_id,'buddy'=>$to_user_id]);
                    }else{
                        $buddy_arr = explode(',',$buddy['buddy']);
                        if (!in_array($to_user_id,$buddy_arr)) {
                            if ($buddy['buddy'] != ''){
                                $buddy['buddy'] .= ',' . $to_user_id;
                            }else{
                                $buddy['buddy'] = $to_user_id;
                            }
                            UserBuddy::where(['user_id' => $user_id])->update(['buddy' => $buddy['buddy']]);
                        }else{
                            $this->msg = '已是好友，无需重复添加！';
                        }
                    }
                    DB::commit();
                }catch (\Exception $exception){
                    $this->code = 500;
                    $this->msg = $exception->getMessage();
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

    // 获取好友列表
    public function list(Request $request)
    {
        $user_id = $request->get('user_id');
        $users = UserBuddy::where(['user_id'=>$user_id])->value('buddy');
        if ($users){
            $my_buddy = explode(',',$users);
            $this->data = User::whereIn('id',$my_buddy)->get(['id','nickname as username','avatar']);
        }
        return $this->response();
    }

    // 获取聊天记录
    public function history(Request $request)
    {
        $user_id = $request->get('user_id');
        $to_user_id = $request->get('to_user_id');
        $s_time = $request->get('start_time');
        $e_time = $request->get('end_time');
        $this->data = Message::where(['user_id'=>$user_id,'to_user_id'=>$to_user_id])
            ->orWhere(['user_id'=>$to_user_id,'to_user_id'=>$user_id])
            ->whereBetween('created_at',[$s_time,$e_time])
            ->orderBy('created_at','asc')
            ->get(['user_id','to_user_id','content','created_at']);
        return $this->response();
    }

    // 解除好友关系 双向解除 清除聊天记录
    public function delete(Request $request)
    {
        $user_id = $request->get('user_id');
        $del_user_id = $request->get('del_user_id');
        DB::beginTransaction();
        try {
            $buddy = UserBuddy::where(['user_id'=>$user_id])->value('buddy');
            if ($buddy){
                $buddy_arr = explode(',',$buddy);
                $key = array_search($del_user_id,$buddy_arr);
                unset($buddy_arr[$key]);
                $buddy = implode(',',$buddy_arr);
                UserBuddy::where(['user_id' => $user_id])->update(['buddy' => $buddy]);
            }
            $buddy_d = UserBuddy::where(['user_id'=>$del_user_id])->value('buddy');
            $buddy_arr_d = explode(',',$buddy_d);
            $key_d = array_search($user_id,$buddy_arr_d);
            unset($buddy_arr_d[$key_d]);
            $buddy_d = implode(',',$buddy_arr_d);
            UserBuddy::where(['user_id' => $del_user_id])->update(['buddy' => $buddy_d]);
            Message::where(['user_id'=>$user_id,'to_user_id'=>$del_user_id])->orWhere(['user_id'=>$del_user_id,'to_user_id'=>$user_id])->delete();
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->code = 500;
            $this->msg = $exception->getMessage();
        }
        return $this->response();
    }

    // 获取所有未读消息
    public function getAllMessage(Request $request)
    {
        $user_id = $request->get('user_id');
        $list = Message::where(['to_user_id'=>$user_id,'is_send'=>0])->distinct('user_id')->get('user_id');
        foreach ($list as $k => &$v){
            $v['username'] = User::where('id',$v['user_id'])->value('nickname');
            $v['messages'] = Message::where(['user_id'=>$v['user_id'],'is_send'=>0])->get(['user_id','to_user_id','content','created_at']);
        }
        $this->data = $list;
        return $this->response();
    }

}
