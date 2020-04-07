<?php

use Illuminate\Http\Request;

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

Route::prefix('im')->namespace('Api')->group(function (){
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
    Route::post('cutout', 'UserController@delete');                 //删除PushController@push好友
    Route::post('up', 'UserController@top');                        //置顶/取消
    Route::post('pStatus', 'UserController@phoneStatus');           //获取手机状态
    Route::post('userId', 'UserController@getUserId');              //获取用户ID
    Route::post('picture', 'WebsocketController@sendPicture');      //发送图片
    Route::post('ban', 'UserController@checkBan');                  //查询是否封禁
    Route::post('complaint', 'UserController@complaint');           //投诉
    Route::post('banType', 'UserController@getBan');                //投诉类型
    Route::get('onlineList', 'WebsocketController@getOnlineList');  //获取所有在线客户端
});

Route::prefix('admin')->namespace('Api')->group(function (){
   Route::any('push', 'PushController@push');                      // 信息推送
   Route::any('bind', 'PushController@bind');                      // 绑定
});

