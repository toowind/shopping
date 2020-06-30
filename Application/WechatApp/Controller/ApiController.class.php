<?php
namespace WechatApp\Controller;
use Think\Controller\RestController;
class ApiController extends RestController{
    protected $max_id;                                                         //上翻最大ID
    protected $since_id;                                                       //下拉最大ID
    protected $uid;                                                               //用户ID
    protected $phone_code;                                                 //用户手机唯一标示
    protected $catid;                                                            //分类ID
    protected $max_time;                                                    //上翻请求当前时间
    protected $min_time;                                                     //下拉请求当前时间
    protected $allowMethod = array('get','post','put');     //REST允许的请求类型列表
    protected $allowType = array('html','xml','json');       //REST允许请求的资源类型列表

    protected $access;
    protected $channel;
    protected $app_version;
    protected $version_code;
    protected $device_platform;
    protected $os_version;
    protected $os_api;
    protected $device_model;
    protected $request_time;
    protected $device_id ;
    protected $uuid;
    protected $openudid;
    /**
     * 架构函数
     * @access public
     */
    function __construct(){
        //初始化参数
        $this->uid = I(REQUEST_METHOD.'.uid',0,'intval');
        $this->catid = I(REQUEST_METHOD.'.catid',0,'intval');
        $this->app_version = I(REQUEST_METHOD.'.version','','trim');
        $this->channel =I(REQUEST_METHOD.'.channel','','trim');
        //读取配置信息(缓存)
        if(false === $config = S('WechatApp_DB_CONFIG_DATA')){
            $config = api('Config/lists');
            S('WechatApp_DB_CONFIG_DATA',$config);
        }
        C($config);
        parent::__construct();
    }
    /**
     * 封装where查询条件
     * @param $field 需求字段
     * @return array
     */
    protected function get_where($field){
        if($this->since_id && !$this->max_id){
            $map[$field] = array('gt',$this->since_id);
        }elseif(!$this -> since_id && $this->max_id){
            $map[$field] = array('lt',$this->max_id);
        }else{
            $map = array();
        }
        return $map;
    }
    /**
     * 生成json返回信息
     * @param boole $success 成功或失败
     * @param int $error_code 错误码,如果$success为true,这里为0
     * @param string $message 提示内容
     * @param array $extra 输出需要的返回值
     * @return json
     */
    protected function apiReturn($success,$error_code=0,$message=null,$extra=null){
        $result = array();
        $result['success'] = $success;
        $result['error_code'] = $error_code;
        $message !== null && $result['message'] = $message;
        foreach($extra as $key=>$value){
            $result[$key] = $value;
        }
        //将返回信息进行编码
        $this->response($result,$this->_type);
    }
    /**
     * 正确的返回请求
     * @param string $message 提示信息
     * @param mixed $extra 数据列表
     */
    protected function apiSuccess($message,$extra=null){
        return $this->apiReturn(true,'0',$message,$extra);
    }
    /**
     * 错误的返回请求
     * @param int $error_code 错误编码
     * @param string $message 提示信息
     * @param mixed $extra 数据列表
     */
    protected function apiError($error_code,$message,$extra=null){
        return $this->apiReturn(false,$error_code,$message,$extra);
    }
    /**
     * 找不到接口时调用该函数
     */
    protected function _empty(){
        $this->apiError('404','找不到该接口');
    }
    /**
     * 间隔时间检测
     * @param string $name 当前用户标示
     * @param int $time 最小间隔时间,默认为5秒
     */
    protected function _check_time($name,$time='5'){
        $key = $name.'_check_time';
        $value = S($key);
        if($value !== false){
            if($value > '0' && NOW_TIME - $value < $time){
                $this->apiError('100008','请求太频繁了');
            }
        }
        S($key,NOW_TIME,3600);
        return true;
    }
    /**
     * 检查签名认证,根据URL检测当前请求是否非法
     */
    protected function _checkSign(){
        $callback = IS_GET ? $_GET : $_POST;			     //请求类型和获取参数
        $sign = $callback['sign'];					           //获取签名
        !$sign && $this->apiError("100002","签名不存在");
        unset($callback['sign']);				                //去除签名参数
        ksort($callback);								       //按字母升序重新排序
        $sequence = '';									      //定义签名数列
        foreach($callback as $k=>$v){			             //拼接参数
            $sequence .= "{$k}={$v}";
        }
        $sequence .= C("WECHAT_APP_KEY");					//拼接key
        $sequence = md5($sequence);			                //加密
        if($sign != $sequence){                             //签名检测
            $this->apiError("100004","签名不正确");
        }
    }
    //获取版本号和平台
    public function getAppVersion(){

        $device_type = I('device_type',0);    //获取请求平台
        $device_platform = I('device_platform');
        if(empty($device_type)){
            $device_type = $device_platform=='android' ? 2 : 1;
        }
        $apv = I('client_version','');      //获取版本号
        if(empty($apv)){
            $apv = I('app_version','');
        }
        $param['apv']=$apv;     //版本号
        $param['pt']=$device_type ;         //1 IOS 2 安卓  平台
        return $param;
    }

    /**
     * 获取微信JS配置
     * @param string $account_id 微信ID
     * @param string $account_secrct 微信秘钥
     */
    protected function getWxJsConf($account_id,$account_secrct){
        //获取tiket
        $jsapi_ticket = $this->wxjsapitiket($account_id,$account_secrct);
        //获取签名
        $noncestr = $this->getRandChar();
        //拼合返回值
        return array(
            'jsapi_ticket'=>$jsapi_ticket,
            'noncestr'=>$noncestr,
            'timestamp'=>NOW_TIME,
        );
    }
    //获取tiket
    protected function wxjsapitiket($account_id,$account_secrct){
        $key = 'wx_jsapi_tiket_'.$account_id;
        $tiket = S($key);
        if(!$tiket || NOW_TIME > $tiket['my_expire_time']){
            $token = $this->wxjstoken($account_id,$account_secrct);
            $url ='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$token.'&type=jsapi';
            $tiket = get_curl_data($url);
            $tiket = json_decode($tiket[1],true);
            if($tiket['errcode'] == '0'){
                $tiket['my_expire_time'] = NOW_TIME + $tiket['expires_in'] - 100;
                S($key,$tiket,$tiket['expires_in'] - 100);
            }
        }
        return $tiket['ticket'];
    }

    //获取token
    protected function wxjstoken($account_id,$account_secrct){
        $key = 'wx_jsapi_token_'.$account_id;
        $token = S($key);
        if(!$token || NOW_TIME > $token['my_expire_time']){
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$account_id.'&secret='.$account_secrct;
            $token = get_curl_data($url);
            $token = json_decode($token[1],true);
            if($token['access_token']){
                $token['my_expire_time'] = NOW_TIME + $token['expires_in'] - 100;
                S($key,$token,$token['expires_in'] -100);
            }
        }
        return $token['access_token'];
    }
    /**
     * 获取微信JS需要的签名
     */
    protected function getRandChar($length=6){
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;
        for($i=0;$i<$length;$i++){
            $str .= $strPol[rand(0,$max)];
        }
        return $str;
    }

}