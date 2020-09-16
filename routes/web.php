<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});
Route::prefix('websocket')->group(function (){
    Route::get('open', 'Api\WebsocketController@open');
});

Route::get('chat','Web\WebController@index');

Route::prefix('admin')->namespace('Web')->group(function (){
//    Route::get('onlineList', 'WebsocketController@getOnlineList');  //获取所有在线客户端
    Route::get('onlineList','WebController@getOnlineList');

});


