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
    Route::post('bind', 'WebsocketController@bind');
    Route::post('chat', 'WebsocketController@chat');
    Route::post('user', 'UserController@find');
    Route::post('out', 'UserController@send');
    Route::post('inc', 'UserController@add');
    Route::post('entry', 'UserController@list');
    Route::post('past', 'UserController@history');
    Route::post('cutout', 'UserController@delete');
    Route::post('all', 'UserController@getAllMessage');
});

