<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;
use GatewayClient\Gateway;
use Illuminate\Support\Facades\DB;

class WebController extends Controller
{
    public function index()
    {
        return response()->file(public_path().'/index.html');
    }


    public function getOnlineList()
    {
        Gateway::$registerAddress = '127.0.0.1:1236';
        try {
            if ($list['total'] = Gateway::getAllUidCount()){
                $list['data'] = Gateway::getAllUidList();
                $i = 0;
                foreach ($list['data'] as $v){
                    $list['list'][$i]['user_id'] = $v;
                    $list['list'][$i]['username'] = DB::table('user')->where('id',$v)->value('nickname');
                    $i ++;
                }
                unset($list['data']);
            }
            $list['error'] = '';
        }catch (\Exception $exception){
            $list['error'] = $exception->getMessage();
        }
        return view('admin.index',compact('list'));
    }

}
