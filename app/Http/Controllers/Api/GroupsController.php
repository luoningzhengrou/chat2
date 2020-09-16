<?php

namespace App\Http\Controllers\Api;

use App\Http\Queries\GroupQuery;
use App\Http\Requests\Api\ChangeGroupOwnerRequest;
use App\Http\Requests\Api\DestroyGroupRequest;
use App\Http\Requests\Api\GroupAnnouncementRequest;
use App\Http\Requests\Api\GroupJoinUserRequest;
use App\Http\Requests\Api\GroupLectureRequest;
use App\Http\Requests\Api\GroupRequest;
use App\Http\Requests\Api\UserJoinGroupRequest;
use App\Http\Requests\Api\UserLeaveGroupRequest;
use App\Http\Requests\Api\UserOutGroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\GroupUser;
use App\Models\User;
use App\Models\UserGroup;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupsController extends Controller
{
    protected $param;

    // 建群
    public function store(GroupRequest $request, Group $group, GroupUser $groupUser)
    {
        $user_id = $request->user_id;
        $users_id = $request->users_id;
        $id_array = explode(',',$users_id);
        $nu = env('CHAT_GROUP_MIN',7);
        if (count($id_array) < $nu){
            $this->infoHandle("新建群人数不能少于 $nu 人");
            return $this->response();
        }
        // 群号
        ID:
        $group_union_id = group_union();
        if (Group::where('union_id',$group_union_id)->first()){
            goto ID;
        }
        DB::beginTransaction();
        try {
            // 群
            $group->union_id = $group_union_id;
            $group->name = $request->name;
            $group->save();

            // 群主
            $groupUser->group_id = $group->id;
            $groupUser->user_id = $user_id;
            $groupUser->is_owner = 1;
            $groupUser->is_manager = 1;
            $groupUser->save();

            // 已加入群
            $this->param['group_id'] = $group->id;
            $this->param['from_user_id'] = $user_id;
            $this->param['user_id'] = $user_id;
            $this->param['type'] = 0;
            $this->userGroupSave();

            // 推送
            $this->joinGroup($user_id,$group->id);

            // 群成员
            $this->param['group_id'] = $group->id;
            $this->param['from_user_id'] = $user_id;
            foreach ($id_array as $k => $v){
                $this->param['user_id'] = $v;
                $this->groupUserSave();
                $this->userGroupSave();
                $this->joinGroup($v,$group->id);
            }
            DB::commit();
        }catch (\Exception $e){
            $message = $e->getMessage();
            $this->errorHandle($message);
            DB::rollBack();
        }
        return $this->response();
    }

    private function joinGroup($user_id,$group_id)
    {
        if (Gateway::isUidOnline($user_id) && $group = Group::where('id',$group_id)->first()){
            $client_id = Gateway::getClientIdByUid($user_id);
            Gateway::joinGroup($client_id, $group->union_id);
            Gateway::sendToUid($user_id,json_encode(['code'=>1, 'msg'=> '新群聊', 'data'=> ['id'=>$group_id,'group_union_id'=>$group->union_id,'group_name'=>$group->name]]));
        }
    }

    private function groupUserSave()
    {
        if (GroupUser::where(['group_id'=>$this->param['group_id'],'user_id'=>$this->param['user_id']])->first() || !User::where('id',$this->param['user_id'])->first()){
            return;
        }
        $group_user = new GroupUser();
        $group_user->group_id = $this->param['group_id'];
        $group_user->user_id = $this->param['user_id'];
        $group_user->from_user_id = $this->param['from_user_id'];
        $group_user->type = $this->param['type'];
        $group_user->save();
    }

    private function userGroupSave()
    {
        if (UserGroup::where(['group_id'=>$this->param['group_id'],'user_id'=>$this->param['user_id']])->first()){
            return;
        }
        $user_group = new UserGroup();
        $user_group->user_id = $this->param['user_id'];
        $user_group->group_id = $this->param['group_id'];
        $user_group->is_top = 0;
        $user_group->name_group = User::where('id',$this->param['user_id'])->value('nickname');
        $user_group->save();
    }

    public function changeGroupOwner(ChangeGroupOwnerRequest $request)
    {
        $user_id = $request->user_id;
        $group_id = $request->group_id;
        if ($this->checkOwner($user_id,$group_id) === false){
            $this->infoHandle('你不是群主');
            return $this->response();
        }
        $new_user_id = $request->new_user_id;
        DB::beginTransaction();
        try {
            $old_owner = GroupUser::where(['group_id'=>$group_id,'is_owner'=>1,'user_id'=>$user_id])->first();
            $old_owner->is_owner = 0;
            $old_owner->save();

            $new_owner = GroupUser::where(['group_id'=>$group_id,'is_owner'=>0,'user_id'=>$new_user_id])->first();
            $new_owner->is_owner = 1;
            $new_owner->save();
            DB::commit();
        }catch (\Exception $e){
            $message = $e->getMessage();
            $this->errorHandle($message);
            DB::rollBack();
        }
        return $this->response();
    }

    private function checkOwner(int $user_id,$group_id)
    {
        $owner_id = (int) GroupUser::where('group_id',$group_id)->where('is_owner',1)->value('user_id');
        return $user_id === $owner_id;
    }

    public function show(Request $request, $groupId)
    {
        $user_id = $request->user_id;

        $group = Group::findOrFail($groupId);

        if ($group){

            $group['start_time'] = $group['start_time'] ?: '';
            $group['end_time'] = $group['end_time'] ?: '';
            $user_group = UserGroup::where(['user_id'=>$user_id,'group_id'=>$groupId])->first();
            $group['is_top'] = $user_group['is_top'] ?: 0;
            $group['name_group'] = $user_group['name_group'];
            $sql = DB::raw("select u.id,u.nickname as name,u.avatar,u.phone,u.area from hh_chat_group_users as g left join hh_user as u on g.user_id = u.id where g.group_id = $groupId");
            $users = DB::select($sql);
            $group['user'] = $users;
            $sql = DB::raw("select f.id,f.name,f.file_url,f.user_id,f.created_at,u.nickname as name from hh_chat_group_files as f left join hh_user as u on f.user_id = u.id where f.group_id = $groupId");
            $files = DB::select($sql);
            $group['file'] = $files;
            $this->data = $group;
        }

        return $this->response();
    }

    public function update(Request $request)
    {
        if ($this->checkOwner($request->user_id,$request->group_id) === false){
            $this->infoHandle('无权限修改');
            return $this->response();
        }

        $group = Group::where('id',$request->group_id)->first();
        $group->update($request->all());

        return $this->response();
    }

    public function updateAnnouncement(GroupAnnouncementRequest $request)
    {
        if ($request->user_id != GroupUser::where(['group_id'=>$request->group_id,'is_owner'=>1])->value('user_id')){
            $this->infoHandle('非管理员无权限');
            return $this->response();
        }

        $group = Group::where('id',$request->group_id)->first();
        $group->announcement = $request->announcement;
        $group->save();

        return $this->response();
    }

    public function updateLecture(GroupLectureRequest $request)
    {
        if ($request->user_id != GroupUser::where(['group_id'=>$request->group_id,'is_owner'=>1])->value('user_id')){
            $this->infoHandle('非管理员无权限');
            return $this->response();
        }

        $group = Group::where('id',$request->group_id)->first();
        $group->start_time = $request->start_time;
        $group->end_time = $request->end_time;
        $group->save();

        return $this->response();
    }

    public function joinToGroup(GroupJoinUserRequest $request)
    {
        $users_id_arr = explode(',',$request->users_id);
        $this->param['from_user_id'] = $request->user_id;
        $this->param['group_id'] = $request->group_id;
        $this->param['type'] = 0;
        try {
            foreach ($users_id_arr as $v){
                $this->param['user_id'] = $v;
                $this->userGroupSave();
                $this->groupUserSave();
                $this->joinGroup($v,$request->group_id);
            }
        }catch (\Exception $exception){
            $message = $exception->getMessage();
            $this->errorHandle($message);
        }

        return $this->response();
    }

    public function userJoinGroup(UserJoinGroupRequest $request)
    {
        if (!$group_id = Group::where(['union_id'=>$request->group_union_id,'code'=>$request->code])->value('id')){
            $this->infoHandle('进群码错误');
            return $this->response();
        }

        $this->param['group_id'] = $group_id;
        $this->param['user_id'] = $request->user_id;
        $this->param['type'] = 1;
        $this->param['from_user_id'] = 0;

        try {
            $this->userGroupSave();
            $this->groupUserSave();
            $this->joinGroup($request->user_id,$request->group_id);
        }catch (\Exception $exception){
            $message = $exception->getMessage();
            $this->errorHandle($message);
        }

        return $this->response();
    }

    public function outGroup(UserOutGroupRequest $request)
    {
        if (!$this->checkOwner($request->user_id,$request->group_id)){
            $this->infoHandle('非管理员无权限');
            return $this->response();
        }

        $res = $this->leave($request,'你已被管理员踢出群聊');
        if ($res !== true){
            $this->errorHandle($res);
        }

        return $this->response();
    }

    public function leaveGroup(UserLeaveGroupRequest $request)
    {
        if (!$this->checkOwner((int) $request->user_id,$request->group_id)){
            $this->infoHandle('群主不能退出');
            return $this->response();
        }

        $res = $this->leave($request,'你已退出群聊');
        if ($res !== true){
            $this->errorHandle($res);
        }

        return $this->response();
    }

    private function leave($request,$msg='退出群聊')
    {
        $where = ['user_id'=>$request->out_user_id,'group_id'=>$request->group_id];
        if (!GroupUser::where($where)->first()){
            return '不在群中';
        }

        DB::beginTransaction();
        try {
            GroupUser::where($where)->delete();
            UserGroup::where(['user_id'=>$request->out_user_id,'group_id'=>$request->group_id])->delete();
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }

        if (Gateway::isUidOnline($request->out_user_id) && $group = Group::where('id',$request->group_id)->first()){
            $client_id = Gateway::getClientIdByUid($request->out_user_id);
            Gateway::leaveGroup($client_id, $group->union_id);
            Gateway::sendToUid($request->out_user_id,json_encode(['code'=>2, 'msg'=> $msg, 'data'=> ['id'=>$request->group_id,'group_union_id'=>$group->union_id,'group_name'=>$group->name]]));
        }

        return true;
    }

    public function destroy(DestroyGroupRequest $request)
    {
        if (!$this->checkOwner($request->user_id,$request->group_id)){
            $this->infoHandle('非群主无权限');
            return $this->response();
        }

        DB::beginTransaction();
        try {
            $group_union_id = Group::where('id',$request->group_id)->value('union_id');
            Gateway::sendToGroup($group_union_id,json_encode(['code'=>3,'msg'=>'此群已被群主解散','data'=>[]]));
            Gateway::ungroup($group_union_id);
            GroupUser::where('group_id',$request->group_id)->delete();
            UserGroup::where('group_id',$request->group_id)->delete();
            GroupMessage::where('group_id',$request->group_id)->delete();
            Group::where('id',$request->group_id)->delete();
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->errorHandle($exception->getMessage());
        }

        return $this->response();
    }

}
