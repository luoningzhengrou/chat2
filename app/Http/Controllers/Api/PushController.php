<?php


namespace App\Http\Controllers\Api;


use App\Models\SaleIp;
use App\Models\SaleIpHistories;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{

    protected $prefix = [
        'A01'   => 'admin_',
        'B01'   => 'home_',
        'C01'   => 'app_',
    ];
    protected $log = 'push';
    protected $write_ip = [
        '219.155.52.133',   // 公司
        '47.111.186.8',     // 测试
        '39.100.243.77',    // PY 测试
        '112.126.103.83',   // 正式
    ];

    public function __construct()
    {
        parent::__construct();
        $remote_url = $_SERVER['REMOTE_ADDR'];
        if (!$this->debug && !in_array($remote_url,$this->write_ip)){
            echo json_encode([
                'code' => 403,
                'msg'  => 'Forbidden',
                'data' => []
            ]);
            exit;
        }
    }

    /**
     * 绑定
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bind(Request $request)
    {
        $sale_id = $request->get('sale_id');
        $uid = 'admin_' . $sale_id;
        $client_id = $request->get('client_id');
        if (!empty($client_id)){
            try {
                Gateway::$registerAddress = '127.0.0.1:1236';
                Gateway::bindUid($client_id,$uid);
                $client_id_session = Gateway::getSession($client_id);
                $ip = $client_id_session['ip'];
                $ip = ip2long($ip);
                SaleIpHistories::create(['client_id'=>$client_id,'sale_id'=>$sale_id,'ip'=>$ip]);
                if ($ip_sale = SaleIp::where(['sale_id'=>$sale_id])->first()){
                    $ip_sale->ip = $ip;
                    $ip_sale->client_id = $client_id;
                    $ip_sale->save();
                }else{
                    SaleIp::create(['client_id'=>$client_id,'sale_id'=>$sale_id,'ip'=>$ip]);
                }
                Log::channel($this->log)->info('sale_id ' . $sale_id . ' bind client_id ' . $client_id . ';');
            }catch (\Exception $exception){
                $this->code = 500;
                $this->msg = $exception->getMessage();
                Log::channel($this->log)->info('sale_id ' . $sale_id . ' bind client_id ' . $client_id . 'failed: ' . $this->msg . ';');
            }
        }else{
            $this->code = 403;
            $this->msg = 'client_id can\'t be null!';
        }
        return $this->response();
    }

    /**
     * 推送接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function push(Request $request)
    {
        $sale_id = $request->get('sale_id');
        $uid = 'admin_' . $sale_id;
        $data = $request->get('data');
        if ($data != json_encode(json_decode($data,true),JSON_UNESCAPED_UNICODE)){
            $this->code = 501;
            $this->msg = 'Data is not json!';
            $this->response();
        }
        try {
            Gateway::$registerAddress = '127.0.0.1:1236';
            Log::channel($this->log)->info('sale_id: ' . $sale_id . ' Push Data: ' . $data . ';');
            if (Gateway::isUidOnline($uid) == 0){
                Log::channel($this->log)->info('sale_id: ' . $sale_id . ' Push Fail, Not online;');
            }else{
                Gateway::sendToUid($uid, $data);
                Log::channel($this->log)->info('sale_id: ' . $sale_id . ' Push Success;');
            }
        }catch (\Exception $e){
            $this->code = 400;
            $this->msg = $e->getMessage();
            Log::channel($this->log)->info('sale_id: ' . $sale_id . ' Push Fail: ' . $this->msg);
        }
        return $this->response();
    }

}
