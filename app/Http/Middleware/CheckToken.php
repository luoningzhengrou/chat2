<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->get('token');
        $user_id = $request->get('user_id');
        $uri = $request->getUri();
        $array = explode('/',$uri);
        if (!array_search('admin',$array)){
            if (!$token || !User::where('token',$token)->orWhere('user_token',$token)->first()){
                echo json_encode([
                    'code' => 403,
                    'msg'  => '未登录',
                    'data' => []
                ]);
                exit;
            }
            if ($user_id){
                if ($user_id != User::where('token',$token)->orWhere('user_token',$token)->value('id')){
                    echo json_encode([
                        'code' => 404,
                        'msg'  => '无权访问',
                        'data' => []
                    ]);
                    exit;
                }
            }
        }
        return $next($request);
    }
}
