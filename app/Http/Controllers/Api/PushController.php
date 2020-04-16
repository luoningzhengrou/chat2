<?php


namespace App\Http\Controllers\Api;


use App\Models\SaleIp;
use App\Models\SaleIpHistories;
use GatewayClient\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{
    protected $config;
    protected $log = 'push';

    public function __construct()
    {
        parent::__construct();
        $this->config = config('push.serial_code_config');
    }

    /**
     * 绑定
     * @param Request $request
     * @return JsonResponse
     */
    public function bind(Request $request)
    {
        $sale_id = $request->get('sale_id');
        $serial_code = $request->get('serial_code');
        if (!$sale_id || !$serial_code){
            $this->code = 401;
            $this->msg = 'Parameter error!';
            return $this->response();
        }
        if (!$this->checkSerialCode($serial_code)){
            $this->code = 403;
            $this->msg = 'Code is not allow!';
            return $this->response();
        }
//        if (!DB::table('sales')->where('id',$sale_id)->first()){
//            $this->code = 403;
//            $this->msg = 'Sales id does not exist!';
//            return $this->response();
//        }
        $uid = $this->config[$serial_code]['prefix'] . $sale_id;
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
     * @return JsonResponse
     */
    public function push(Request $request)
    {
        $sale_id = $request->get('sale_id');
        $serial_code = $request->get('serial_code');
        if (!$this->checkSerialCode($serial_code)){
            $this->code = 403;
            $this->msg = 'Code is not allow!';
            return $this->response();
        }
        if (!$this->checkIp($serial_code) && !$this->debug){
            $this->code = 403;
            $this->msg = 'IP is not allow!';
            return $this->response();
        }
        $uid = $this->config[$serial_code]['prefix'] . $sale_id;
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

    /**
     * 检查识别码
     * @param $code
     * @return bool
     */
    private function checkSerialCode($code)
    {
        if (in_array($code,array_keys($this->config))){
            return true;
        }
        return false;
    }

    /**
     * 检查 IP 白名单
     * @param $code
     * @return bool
     */
    private function checkIp($code)
    {
        if (!in_array($_SERVER['REMOTE_ADDR'],$this->config[$code]['ip_list'])){
            return true;
        }
        return false;
    }

}
