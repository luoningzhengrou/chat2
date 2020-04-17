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

    public function login()
    {
        return view('admin.login');
    }


    public function getOnlineList()
    {
        try {
            if ($data['total'] = Gateway::getAllUidCount()){
                $user_list = Gateway::getAllUidList();
                $data['list'] = DB::table('user')
                    ->leftJoin('chat_ips','user.id', '=', 'chat_ips.user_id')
                    ->whereIn('user.id',$user_list)
                    ->select('user.id','user.nickname','chat_ips.ip')
                    ->get();
            }
            $data['error'] = '';
        }catch (\Exception $exception){
            $data['error'] = $exception->getMessage();
        }
        if (!isset($data['total'])){
            $data['total'] = 0;
        }
        if (!isset($data['list'])){
            $data['list'] = [];
        }
        $data['total'] = count($data['list']);
        return view('admin.index',compact('data'));
    }

}
