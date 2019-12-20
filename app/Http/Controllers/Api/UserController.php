<?php

namespace App\Http\Controllers\Api;

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
        $user = User::where(['phone'=>$phone])->first();
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
        $where = ['user_id'=>$user_id,'to_user_id'=>$to_user_id,'status'=>0,'is_handle'=>0];
        $info = UserAddFriend::find(1)->where($where)->first();
        $i = 0;
        if ($info){
            DB::beginTransaction();
            try {
                UserAddFriend::where($where)->update(['status'=>$status,'is_handle'=>1]);
                // 被加人好友
                $buddy = UserBuddy::where(['user_id'=>$user_id])->first();

                if (!$buddy){
                    $userBuddy = new UserBuddy();
                    $userBuddy->user_id = $to_user_id;
                    $userBuddy->buddy = $user_id;
                    $userBuddy->save();
                }else{
                    $buddy .= ',' . $to_user_id;
                    UserBuddy::where(['user_id'=>$to_user_id])->update(['buddy'=>$buddy]);
                }
//                for ($i=0;$i<2;$i++){
//                    if ($i == 0){
//                        $this->addFriend($user_id,$to_user_id,$userBuddy);
//                    }else{
                        $buddy = UserBuddy::where(['user_id'=>$to_user_id])->first();
                        if (!$buddy){
                            $uBuddy = new UserBuddy();
                            $uBuddy->user_id = $user_id;
                            $uBuddy->buddy = $to_user_id;
                            $uBuddy->save();
                        }else{
                            $buddy .= ',' . $to_user_id;
                            UserBuddy::where(['user_id'=>$user_id])->update(['buddy'=>$buddy]);
                        }
//                        $this->addFriend($to_user_id,$user_id,$userBuddy);
//                    }
//                }

                //加人好友

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
        return $this->response();
    }

    private function addFriend($user_id,$to_user_id,UserBuddy $userBuddy)
    {
        $buddy = UserBuddy::where(['user_id'=>$to_user_id])->first();
        if (!$buddy){
            $userBuddy->user_id = $user_id;
            $userBuddy->buddy = $to_user_id;
            $userBuddy->save();
        }else{
            $buddy .= ',' . $to_user_id;
            UserBuddy::where(['user_id'=>$user_id])->update(['buddy'=>$buddy]);
        }
    }

    // 获取好友列表
    public function list(Request $request)
    {
        $user_id = $request->get('user_id');
        $users = UserBuddy::where(['user_id'=>$user_id])->value('buddy');
        if ($users){
            $this->data = explode(',',$users);
        }
        return $this->response();
    }

    // 获取聊天记录
    public function history(Request $request)
    {

    }

    // 解除好友关系
    public function delete(Request $request)
    {
        $user_id = $request->get('user_id');
        $del_user_id = $request->get('del_user_id');

    }

    //返回公用方法
    private function response()
    {
        return [
            'code'    => $this->code,
            'msg'           => $this->msg,
            'data' => $this->data
        ];
    }
}
