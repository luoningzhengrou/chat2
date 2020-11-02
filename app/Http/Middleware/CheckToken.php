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
        $user_id = $request->get('user_id');
        $uri = $request->getUri();
        $array = explode('/',$uri);
        if (array_search('admin',$array)){
            return $next($request);
        }
        $token = $request->get('token');
        $user = User::where('token',$token)->orWhere('user_token',$token)->first();
        if (!$token || !$user){
                echo json_encode([
                    'code' => 403,
                    'msg'  => '未登录',
                    'data' => []
                ]);
                exit;
        }
        if ($user_id && $user->id != $user_id){
            echo json_encode([
                'code' => 403,
                'msg'  => '用户信息错误',
                'data' => []
            ]);
            exit;
        }
        return $next($request);
    }
}
