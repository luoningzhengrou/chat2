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
        if ($token == null || $token == '' || !User::where('token',$token)->first()){
            echo json_encode([
                'code' => 403,
                'msg'  => '未登录',
                'data' => []
            ]);
            exit;
        }
        return $next($request);
    }
}
