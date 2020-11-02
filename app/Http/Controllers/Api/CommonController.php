<?php


namespace App\Http\Controllers\Api;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{

    public function city()
    {
        $cache_key = $_SERVER['SERVER_NAME'] . '_city_cache';
        $provinces = Cache::get($cache_key);
        if (!$provinces){
            $provinces = DB::table('city')->where('type',1)->get()->toArray();
            foreach ($provinces as $k => &$v){
                $v->city = DB::table('city')->where(['pid'=>$v->id,'type'=>2])->get()->toArray();
            }
            Cache::put($cache_key,$provinces,env('CACHE_TIME',86400));
        }
        return $provinces;
    }


}
