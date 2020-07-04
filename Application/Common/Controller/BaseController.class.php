<?php

namespace Common\Controller;
use Common\Common\Cache\Redis;
use Common\Common\Manager\DI;
use Common\Common\Rpc\Http\HttpClient;
use Common\Exception\Exception;
use Common\Response\Response;
use Common\Util\AppConfig;
use Think\Controller;

class BaseController extends Controller{
    protected $_login = true;
    protected  static $_token = '';
    protected static $_param = array();
    public function __construct()
    {
        self::DI();
        self::Param();
        $this->checkLogin();
    }

    /** MiddleWare **/
    public static function DI(){
        DI::set("http",function(){
            return new HttpClient();
        });

        DI::set("appConf",function (){
            return new AppConfig;
        });

        DI::setShared("redis",function (){
            $redisConf = C("redis");
            return new Redis($redisConf);
        });
    }
    /** 数据处理**/
    public function Param(){
        header('Content-Type:application/json; charset=utf-8');
        $data = array_merge($_POST,$_GET);
        if (empty($data)){
            exit(json_encode(array("status"=>-1,"info"=>"参数不全")));
        }
        if(empty($data["token"])){
            exit(json_encode(array("status"=>-2,"info"=>"退出重新登录")));
        }
        self::$_param =  json_decode($data["data"],true);
        self::$_token = $data["token"];

    }
    /**用户登录态管理**/
    public function checkLogin(){
        if($this->_login == true){
            $token = self::$_token;
            file_put_contents('./log.txt', $token.PHP_EOL, FILE_APPEND);
            $userInfo = think_decrypt($token,'QGLGKU');
            $userInfo = json_decode($userInfo,true);
            file_put_contents('./log.txt', json_encode($userInfo).PHP_EOL, FILE_APPEND);

            if(!isset($userInfo["uid"])){
                $userInfo["uid"] = -1;
            }
            if(empty($userInfo) || !isset($userInfo["uid"])){
                header('Content-Type:application/json; charset=utf-8');
                Response::outPutFail(-3,"请重新登录");
            }
            if(empty($userInfo["platformId"]) || empty($userInfo["platform"])){
                header('Content-Type:application/json; charset=utf-8');
                Response::outPutFail(-4,"请退出重新登~");
            }
            $GLOBALS["userId"] = $userInfo["uid"];
            $GLOBALS["platform"] = $userInfo["platform"];
            $GLOBALS["platformId"] = $userInfo["platformId"];
            $GLOBALS["token"] = $token;
            $GLOBALS["userInfo"] = $userInfo;
        }
    }

    //公共参数
    private function mapList($idx = '')
    {
        $list = array(
            'api_version' => 'apiVersion',//api版本
            'session_id' => 'sessionId',//session id
            'timeStamp' => 'timeStamp',//时间戳
            'deviceId' => 'deviceId',//设备ID
            'imei' => 'imei',//设备号
            'channel' => 'channel',//渠道
            'package_name' => 'packageName',//包名
            'track_id' => 'trackId',//跟踪ID
            'net' => 'net',//网络类型
            'client_type' => 'clientType',//客户端类型
            'platform' => 'clientType',//客户端类型
            'client_version' => 'clientVersion',//客户端版本
            'token' => 'token',//用户登录态token
            'sdkVersion' => 'sdkVersion',//sdk版本
            'gender' => 'gender',//性别
            'statics_id' => 'staticsId',//统计ID
            'screen' => 'screen',//屏幕分辨率
            'userId' => 'userId',//用户ID
            'sign' => 'sign',//签名
            'deviceToken' => 'xingeToken',//xin ge Token
        );
        if ($idx) {
            return isset($list[$idx]) ? $list[$idx] : '';
        } else {
            return $list;
        }
    }

    /**
     * 发送 post 请求
     * @param string $url
     * @param array $param
     * @return array
     */
    protected function sendCurlPost($url = '',$param = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $return = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array($info, $return);
    }
}