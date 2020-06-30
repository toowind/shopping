<?php
/**
 * 描述：发送http请求,本封装适用于服务器之间通过http协议进行交互的场景
 */
namespace Common\Common\Rpc\Http;


use Think\Log;

class HttpClient
{
    private static $_app = null;

    //curl句柄
    private $_curl = null;

    //设置请求失败进行重试的次数,默认不进行失败重试
    private $_retryMax = 0;

    //连接超时时间(默认1500ms)
    private $_connectTimeout = 1500;

    //设置curl执行时间(默认3000ms)
    private $_exeTimeout = 3000;

    //非200http_code 则返回-200异常
    const FAIL_CODE = -200;

    private $_startTime = null;

    private $_endTime = null;

    private $_url = null;

    private $_data = array();

    private $_cookie = '';

    private $_appendHash = true;

    private static $_hashId = null;
    //请求log
    private static $reqList = array();


    /**
     * 初始化 curl
     *
     * Client constructor.
     */
    public function __construct()
    {
        $this->_init();//初始化
    }

    /**
     * 销毁curl句柄
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    public static function getApp()
    {
        if(empty(self::$_app) || ! (self::$_app instanceof Client)){
            self::$_app = new self();
            self::$_app->_init();//初始化
        }
        return self::$_app;
    }

    /**
     * 设置header
     *
     * @param array $header
     * @return $this
     */
    public function setHeader(array $header = array())
    {
        if($header && $this->_curl){
            curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $header);
        }
        return $this;
    }

    /**
     * 设置代理
     *
     * @param $proxy
     * @return $this
     */
    public function setProxy($proxy)
    {
        if ($proxy) {
            curl_setopt($this->_curl, CURLOPT_PROXY, $proxy);
        }
        return $this;
    }

    /**
     * 设置cookie
     *
     * @param $cookie   string
     * @return $this
     */
    public function setCookie($cookie)
    {
        $this->_cookie = $cookie;
        curl_setopt($this->_curl, CURLOPT_COOKIE,$cookie);
        return $this;
    }

    /**
     * 执行请求
     */
    private function _init()
    {
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, true);//其中重定向
        curl_setopt($this->_curl, CURLOPT_MAXREDIRS, 3);//这是重定向次数，防止死循环
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);//设置是否直接输出结果
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT_MS, $this->_connectTimeout);//设置连接超时时间
        curl_setopt($this->_curl, CURLOPT_TIMEOUT_MS, $this->_exeTimeout);//设置curl执行时间
        //设置curl默认访问为IPv4
        curl_setopt($this->_curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    /**
     * 设置超时时间
     *
     * @param int $connectTimeout
     * @param int $exeTimeout
     * @return $this
     */
    public function setTimeout($connectTimeout = 0, $exeTimeout = 0)
    {
        if($connectTimeout > 0){
            curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout);//设置连接超时时间
            if($connectTimeout <= 1000) {
                curl_setopt($this->_curl, CURLOPT_NOSIGNAL, 1);//禁用信号，直接跳过DNS解析超时校验
            }
        }
        if($exeTimeout > 0){
            curl_setopt($this->_curl, CURLOPT_TIMEOUT_MS, $exeTimeout);//设置curl执行时间
        }
        return $this;
    }

    /**
     * 设置失败重试次数，默认不重试
     *
     * @param int $retryNum
     * @return $this
     */
    public function setRetry($retryNum = 0)
    {
        $this->_retryMax = intval($retryNum);
        return $this;
    }

    /**
     * https 请求 面证书验证和SSL加密算法校验
     *
     * @param string $bool
     */
    public function setHttps($bool = false)
    {
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, $bool); // 对认证证书来源的检查
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, $bool); // 从证书中检查SSL加密算法是否存在

        return $this;
    }

    /**
     * 是否需要hash 默认需要
     *
     * @param string $bool
     */
    public function setAppendHash($bool = true)
    {
        $this->_appendHash = $bool;

        return $this;
    }

    /**
     * 描述：进行post请求
     *
     * @param $url
     * @param array $data
     * @return array
     */
    public function post($url, $data = array())
    {
        $this->_url = $url;
        $this->_data = $data;
        if(empty($this->_curl) || !is_resource($this->_curl)){
            $this->_init();
        }
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        $this->setHttps();
        return $this->_getRs();
    }

    /**
     * 描述：进行get请求
     * @author jinj
     * @since  2016年5月25日
     * @param string $url 合法的url地址
     * @param array $data 数据
     * @return array $_result array('code'=>,'message'=>,'data'=>)code为0表示成功，非0表示失败
     */
    public function get($url, array $data = array())
    {
        $this->_url = $url;
        $this->_data = $data;
        if(empty($this->_curl) || ! is_resource($this->_curl)){
            $this->_init();
        }

        if(strpos($this->_url, '?') === false){
            $url .= '?'.http_build_query($data);
        }else{
            $url .= '&'.http_build_query($data);
        }
        curl_setopt($this->_curl, CURLOPT_HTTPGET, true);
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        return $this->_getRs();
    }

    /**
     * 返回唯一ID
     *
     * @return null|string
     */
    public static function getHashId() {
        if( ! self::$_hashId ){
            $str = ((mt_rand() << 1) | (mt_rand() & 1) ^ microtime(true));
            self::$_hashId = strtolower(base_convert($str, 10, 36));
        }
        return self::$_hashId;
    }

    /**
     * @return float
     */
    public function microTimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 描述：统一处理curl的结果信息
     * @return array
     * @throws \Exception
     */
    private function _getRs()
    {
        //请求开始时间
        $this->_startTime = $this->microTimeFloat();
        $retry = 0;
        //添加请求唯一id标示
        if($this->_appendHash && strpos($this->_url, 'requestHashId') === false){
            $hashId = self::getHashId();
            $uuid = $hashId.'_'.md5($this->_startTime);
            if(strpos($this->_url, '?') === false){
                $this->_url .= '?requestHashId='.$uuid;
            }else{
                $this->_url .= '&requestHashId='.$uuid;
            }
        }
        do{
            $retry++;
            $data = array();
            $data['status'] = 0;
            $data['data'] = curl_exec($this->_curl);
            $data['code'] = curl_errno($this->_curl);
            $data['message'] = curl_error($this->_curl);
            if($data['code'] && empty($data['message'])){
                $data['message'] = 'strError:'.curl_strerror($data['code']);
            }
            $httpInfo = curl_getinfo($this->_curl);
            $data['httpInfo'] = $httpInfo;
            $httpInfo['connect_time']= isset($httpInfo['connect_time']) ?floatval($httpInfo['connect_time']) : null;
            $httpInfo['total_time']= isset($httpInfo['total_time']) ? floatval($httpInfo['total_time']) : null;
            if( ! isset($httpInfo['http_code']) || ! in_array($httpInfo['http_code'], array(200))){
                $data['status'] = 1;
            }
        }while (isset($data) && ($data['code'] == 28) && $retry <= $this->_retryMax);
        //请求结束时间
        $this->_endTime = $this->microTimeFloat();
        //todo 记录请求时间 大于1秒则记入notice日志
        $runTime = ($this->_endTime - $this->_startTime);
        $runTime = sprintf('%.3f',$runTime);
        $result = $data;
        $resultData = json_decode($result['data'], true);
        $result['data'] = $resultData ? $resultData : $result['data'];

        self::$reqList[] = array('url'=>$this->_url,'data'=>$this->_data,'res'=>$data,'runTime'=>$runTime);

        //http错误日志
        if($data['status'] !== 0){
            $log = array('action'=>'httpFail','runTime'=>$runTime,'msg'=>array('url'=>$this->_url,'curlData'=>$this->_data,'result'=>json_decode($result,true)));
            Log::write($log,"CURL_ERROR");
        }else{
            //todo 目前主要调多多进宝数据，返回数据量大先不考虑记录请求成功日志
           // $log = array('action'=>'httpSuccess','msg'=>array('url'=>$this->_url,'runTime'=>$runTime,'curlData'=>$this->_data,'result'=>$resultData));
           // Log::write($log,"CURL_SUCCESS");
        }
        return $data;
    }

    /**
     * 描述：关闭连接
     * 在最后的时候调用该方法
     */
    public function close()
    {
        if( ! empty($this->_curl) && is_resource($this->_curl)){
            curl_close($this->_curl);
        }
        $debug = isset($_GET['debug']) ? $_GET['debug'] : null;
        if($debug === 'log' && self::$reqList){
            $msg = json_encode(self::$reqList);
            Core::write($msg,'debug');
            self::$reqList = array();
        }
        $this->_curl = null;
        return true;
    }
    /**
     * @param $url
     * @param array $data
     * @return array
     */
    public function post_param_no_http_build_query($url, $data = array())
    {
        $this->_url = $url;
        $this->_data = $data;
        if(empty($this->_curl) || !is_resource($this->_curl)){
            $this->_init();
        }
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        return $this->_getRs();
    }
}
