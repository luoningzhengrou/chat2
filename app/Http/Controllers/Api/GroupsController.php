<?php

namespace App\Http\Controllers\Api;

use App\Http\Queries\GroupQuery;
use App\Http\Requests\Api\ChangeGroupOwnerRequest;
use App\Http\Requests\Api\DestroyGroupRequest;
use App\Http\Requests\Api\GroupAnnouncementRequest;
use App\Http\Requests\Api\GroupFindRequest;
use App\Http\Requests\Api\GroupJoinUserRequest;
use App\Http\Requests\Api\GroupLectureRequest;
use App\Http\Requests\Api\GroupMessageRequest;
use App\Http\Requests\Api\GroupRequest;
use App\Http\Requests\Api\GroupUpdateCodeRequest;
use App\Http\Requests\Api\UserJoinGroupRequest;
use App\Http\Requests\Api\UserLeaveGroupRequest;
use App\Http\Requests\Api\UserOutGroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMessage;
use App\Models\GroupUser;
use App\Models\User;
use App\Models\UserGroup;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $nu = env('CHAT_GROUP_MIN',6);
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
            $group->avatar = $request->avatar;
            $group->cert_id = $request->cert_id;
            $group->code = $request->code;
            $group->province_id = $request->province_id;
            $group->city_id = $request->city_id;
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
            $this->data['group_id'] = $group->id;
            DB::commit();
        }catch (\Exception $e){
            $message = $e->getTraceAsString();
            $this->errorHandle($message);
            $this->logHandle($message);
            DB::rollBack();
        }
        return $this->response();
    }

    public function find(GroupFindRequest $request)
    {
        if (!$group = Group::where('union_id',$request->group_union_id)->first()){
            $this->infoHandle('群不存在');
            return $this->response();
        }

        $cache_key = 'group_' . $request->group_union_id;
        $data = Cache::get($cache_key);
        if (!$data){
            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'union_id' => $group->union_id,
                'announcement' => $group->announcement,
                'avatar' => $group->avatar,
                'cert_name' => DB::table('category')->where('id',$group->cert_id)->value('name'),
                'province' => DB::table('city')->where('id',$group->province_id)->value('cityname'),
                'city' => DB::table('city')->where('id',$group->city_id)->value('cityname'),
            ];
            Cache::put($cache_key,$data,86400);
        }
        $data['num'] = GroupUser::where('group_id',$group->id)->count();
        $this->data = $data;
        return $this->response();
    }

    public function message(GroupMessageRequest $request, GroupMessage $groupMessage)
    {
        if (!User::where('id',$request->user_id)->first()){
            $this->infoHandle('非法访问');
            return $this->response();
        }
        if (!Gateway::isUidOnline($request->user_id)){
            $this->infoHandle('你已离线');
            return $this->response();
        }
        $group_union_id = Group::where('id',$request->group_id)->value('union_id');

        $groupMessage->user_id = $request->user_id;
        $groupMessage->group_id = $request->group_id;
        $groupMessage->content = $request->message;
        $groupMessage->type = $request->type;
        $groupMessage->save();

        $message = [
            'code' => 4,
            'message' => $request->message,
            'group_id' => $request->group_id,
            'from_user_id' => $request->user_id,
            'type' => $request->type
        ];
        Gateway::sendToGroup($group_union_id,json_encode($message));

        return $this->response();
    }

    private function joinGroup($user_id,$group_id)
    {
        if (Gateway::isUidOnline($user_id) && $group = Group::where('id',$group_id)->first()){
            $client_id = Gateway::getClientIdByUid($user_id);
            Gateway::joinGroup($client_id[0], $group->union_id);
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

        $field = 'g.id,g.union_id,g.name,g.avatar,g.cert_id,g.province_id,g.city_id,g.code,g.announcement,g.start_time,g.end_time,g.created_at,c.name as cert_name,p.cityname as province_name,t.cityname as city_name';
        $sql = DB::raw("select $field from hh_chat_groups as g left join hh_category as c on g.cert_id = c.id left join hh_city as p on g.province_id = p.id left join hh_city as t on g.city_id = t.id where g.id = $groupId limit 1");
        $group = DB::selectOne($sql);

        if ($group){

            $group->start_time = $group->start_time ?: '';
            $group->end_time = $group->end_time ?: '';
            $user_group = UserGroup::where(['user_id'=>$user_id,'group_id'=>$groupId])->first();
            $group->is_top = $user_group->is_top ?: 0;
            $group->name_group = $user_group->name_group;
            $sql = DB::raw("select u.id,u.nickname as name,u.avatar,u.phone,u.area,g.is_owner from hh_chat_group_users as g left join hh_user as u on g.user_id = u.id where g.group_id = $groupId order by g.id asc");
            $users = DB::select($sql);
            $group->user = $users;
            $sql = DB::raw("select f.id,f.name,f.file_url,f.user_id,f.created_at,u.nickname as username from hh_chat_group_files as f left join hh_user as u on f.user_id = u.id where f.group_id = $groupId order by f.id asc");
            $files = DB::select($sql);
            $group->file = $files;
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

    public function updateCode(GroupUpdateCodeRequest $request)
    {
        if (!$group = Group::where('id',$request->group_id)->first()){
            $this->infoHandle('群不存在');
            return $this->response();
        }
        if (!$this->checkOwner($request->user_id,$request->group_id)){
            $this->infoHandle('非群主不能修改');
            return $this->response();
        }

        $group->code = $request->code;
        $group->save();

        return $this->response();
    }

    public function joinToGroup(GroupJoinUserRequest $request)
    {
        if (!GroupUser::where(['group_id'=>$request->group_id,'user_id'=>$request->user_id])->first()){
            $this->infoHandle('怒不在群里，无法拉人');
            return $this->response();
        }
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
        $group_id = Group::where(['union_id'=>$request->group_union_id,'code'=>$request->code])->value('id');
        if (!$group_id){
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
            $this->joinGroup($request->user_id,$request->group_union_id);
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
        if ($this->checkOwner((int) $request->out_user_id,$request->group_id)){
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
            Gateway::leaveGroup($client_id[0], $group->union_id);
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
            GroupFile::where('group_id',$request->group_id)->delete();
            Group::where('id',$request->group_id)->delete();
            DB::commit();
            $res = 1;
        }catch (\Exception $exception){
            DB::rollBack();
            $this->errorHandle($exception->getMessage());
            $res = 0;
        }

        if ($res === 1){
            return response(null, 204);
        }

        return $this->response();
    }

}
