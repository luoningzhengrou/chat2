<?php

/*out
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 聊天
Route::prefix('im')->namespace('Api')->group(function (){
    // 单聊
    Route::post('bind', 'WebsocketController@bind');                //绑定
    Route::post('user', 'UserController@find');                     //查找好友
    Route::post('out', 'UserController@send');                      //发送好友请求
    Route::post('addInfo', 'UserController@getAddInfo');            //获取添加好友请求
    Route::post('inc', 'UserController@add');                       //添加好友
    Route::post('chat', 'WebsocketController@chat');                //聊天
    Route::post('entry', 'UserController@list');                    //获取好友列表
    Route::post('cat', 'UserController@friend');                    //获取好友信息
    Route::post('past', 'UserController@history');                  //获取聊天记录
    Route::post('all', 'UserController@getAllUnSendMessage');       //获取未上线消息
    Route::post('info', 'UserController@deleteOne');                //删除单条聊天信息
    Route::post('allInfo', 'UserController@deleteHistory');         //删除聊天窗口
    Route::post('black', 'UserController@addBlack');                //加入/解除黑名单
    Route::post('blackList', 'UserController@blackList');           //黑名单列表
    Route::post('shield', 'UserController@shield');                 //显示隐藏权限
    Route::post('cutout', 'UserController@delete');                 //删除好友
    Route::post('up', 'UserController@top');                        //置顶/取消
    Route::post('pStatus', 'UserController@phoneStatus');           //获取手机状态
    Route::post('userId', 'UserController@getUserId');              //获取用户ID
    Route::post('picture', 'WebsocketController@sendPicture');      //发送图片
    Route::post('ban', 'UserController@checkBan');                  //查询是否封禁
    Route::post('complaint', 'UserController@complaint');           //投诉
    Route::post('complaint/user','UserController@userComplaint');   //投诉 新
    Route::post('banType', 'UserController@getBan');                //投诉类型
    Route::post('messageResponse', 'UserController@messageResponse');                //投诉类型
    // 群聊
    Route::get('onlineList', 'WebsocketController@getOnlineList');                          //获取所有在线客户端
    Route::post('groups', 'GroupsController@store')->name('groups.store');                  // 创建群聊
    Route::post('group/message', 'GroupsController@message')->name('group.message');        // 发送群消息
    Route::patch('groups', 'GroupsController@update')->name('groups.update');               // 修改群信息(公告进群码学习时间)
    Route::post('groups/owner', 'GroupsController@changeGroupOwner')->name('groups.owner'); // 转让群主
    Route::get('groups/{groupId}/show' ,'GroupsController@show')->name('groups.show');      // 查看群信息
    Route::patch('user', 'UserController@updateGroup')->name('user.group.name');            // 修改群昵称
    Route::patch('announcement', 'GroupsController@updateAnnouncement');                    // 修改群公告
    Route::patch('lecture', 'GroupsController@updateLecture')->name('groups.lecture');      // 修改群学习时间
    Route::patch('group/code', 'GroupsController@updateCode')->name('group.code');          // 修改进群码
    Route::post('group', 'GroupsController@joinToGroup')->name('group.join');               // 拉人进群
    Route::get('group/{groupId}', 'GroupsController@find')->name('group.find');             // 搜索群聊
    Route::post('group/code', 'GroupsController@userJoinGroup')->name('group.user.join');   // 用户主动进群
    Route::post('group/out', 'GroupsController@outGroup')->name('group.out');               // 踢出群聊
    Route::post('group/leave', 'GroupsController@leaveGroup')->name('group.leave');         // 退出群聊
    Route::delete('group/destroy', 'GroupsController@destroy')->name('group.destroy');      // 解散群
    Route::get('common/city', 'CommonController@city')->name('common.city');                // 获取城市
    Route::get('my/group','GroupsController@myGroups')->name('my.group');                   // 我的群聊
    Route::post('group/complaint', 'GroupsController@complaint')->name('group.complaint');  // 投诉
    Route::patch('group/{groupId}/name', 'GroupsController@updateName')->name('group.name');// 修改群名称
    Route::patch('group/{groupId}/top', 'GroupsController@top')->name('group.top');         // 群聊置顶
    Route::get('reasons', 'GroupsController@reasons')->name('group.reasons');               // 获取投诉类型
    Route::get('search', 'GroupsController@search')->name('group.search');                  // 搜索 整合搜索个人和群
    Route::get('discovery', 'GroupsController@discovery')->name('group.discovery');         // 群推荐
});

// 推送接口
Route::prefix('admin')->namespace('Api')->group(function (){
   Route::any('push', 'PushController@push');                      // 信息推送
   Route::any('bind', 'PushController@bind');                      // 绑定
});

