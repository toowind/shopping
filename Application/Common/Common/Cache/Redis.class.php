<?php
/**
 * 功能：实现redis主从结构的支持
 */
namespace Common\Common\Cache;

use Think\Log;

class Redis
{
    private static $_reConnect = array();
    private $_config = array();
    private $_redisPool = array();

    //todo redis写操作 非全部待增加 (索引必须小写)
    private $_writeArr = array('set'=>1,'delete'=>1,'del'=>1,'incr'=>1,'decr'=>1,'decrby'=>1,'expire'=>1,
        'rpush'=>1,'lpush'=>1,'lpop'=>1,'rpop'=>1,'setnx'=>1,'setex'=>1,'sadd'=>1,'incrby'=>1,
        'srem'=>1,'blpop'=>1,'brpop'=>1,'zincrby'=>1,'zadd'=>1,'zrem'=>1,'zdelete'=>1,'hset'=>1,'hsetnx'=>1,
        'hmset'=>1,'hdel'=>1,'hincrby'=>1,'geoadd'=>1,'incrbyfloat'=>1
    );
    //超过执行时间则记录
    private $maxTime = 300;

    //上报ip
    private $reportHost = null;

    //上报端口
    private $reportPort = null;

    //上报id
    private $reportMid = null;

    //设置请求失败进行重试的次数,默认不进行失败重试
    private $_retryMax = 1;

    //是否为长连接
    private $isPConnect = false;

    private $reWriteMethod = array('getValue'=>'get');

    //长连接则ping操作
    private $doPing = true;

    //是否使用主连接
    private $userMaster = false;

    private  $dbx = 0;

    const MASTER = 'master';

    const SLAVE = 'slave';

    /**
     * RedisMs constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->_config = $config;
        $this->reportHost = isset($config['REDIS_HOST']) ? $config['REDIS_HOST'] : '';
        $this->reportPort = isset($config['REDIS_PORT']) ? $config['REDIS_PORT'] : 0;
    }

    /**
     * 创建redis对象
     * @param $config
     * @return bool|\Redis
     */
    private function _getConnect($config)
    {
        try{
            $startTime = microtime(true);
            $redis = new \Redis();
            $config['timeout'] = isset($config['timeout']) ? $config['timeout'] : 1;
            $this->isPConnect = (isset($this->_config['pConnect']) && $this->_config['pConnect']) ? true : false;

            if($this->isPConnect){
                $obj = $redis->pconnect($config['host'],$config['port'],$config['timeout']);
            }else{
                $obj =$redis->connect($config['host'],$config['port'],$config['timeout']);
            }
            if(!empty($config["auth"])){
                $ret = $redis->auth($config["auth"]);
            }

            $endTime = microtime(true);
            $runTime = 1000 * floatval($endTime - $startTime);
            $runTime = sprintf('%.2f',$runTime);
            if($runTime > 100){
                $log = array('action'=>'redisConnect','config'=>$config,'runTime'=>$runTime);
                Log::write($log,"NOTICE");
            }
            if($ret === false){
                //连接失败上报
                if($this->reportHost && $this->reportPort && $this->reportMid){
                    Log::write($this->reportHost.":". $this->reportPort,'REDIS_CONNECT_ERROR');
                }
                throw new \Exception('redisConnectFailed!config:'.json_encode($config));
            }
            $redis->select($this->dbx);

        }catch(\Exception $e){
            //redis连接失败日志记录
            $log = array('action'=>'redisConnect','exception'=>$e->getMessage());
            Log::write($log,"REDIS_CONNECT_ERROR");
            $redis = false;
        }
        return $redis;
    }

    /**
     * 避免对象被复制
     */
    public function __clone()
    {
        exit('该类为单例模式，禁止clone');
    }

    /**
     * 读写分离
     *
     * @param string $type
     * @param bool $isConnect
     * @return mixed
     * @throws \Exception
     */
    private function _getConn($type = self::SLAVE,$isConnect = false)
    {
        if($isConnect || ! isset($this->_redisPool[$type])){
            $this->_redisPool[$type] = [];
            $config = isset($this->_config[$type]) ? $this->_config[$type] : array();
            if(empty($config)){
                throw new \Exception('redisConfigIsError,config:'.json_encode($this->_config));
            }
            $arrHost = array();
            $hostArr = explode(',',$config['REDIS_HOST']);
            foreach($hostArr as $host){
                $timeout = isset($config['timeout']) ? $config['timeout'] : 1;
                $arrHost[] = array('host'=>$host,'port'=>$config['REDIS_PORT'],'timeout'=>$timeout,'auth'=>$config["REDIS_AUTH"]);
            }
            if($arrHost){
                //随机下
                shuffle($arrHost);
                foreach($arrHost as $v) {
                    $redis = $this->_getConnect($v);
                    if ($redis) {
                        $this->_redisPool[$type][] = $redis;
                        break;
                    }
                }
            }
        }
        $num = isset($this->_redisPool[$type]) ? count($this->_redisPool[$type]) : 0;
        $index = ($num > 1) ? rand(0,($num - 1)) : 0;
        $redis = isset($this->_redisPool[$type][$index]) ? $this->_redisPool[$type][$index] : false;
        $redis->select($this->dbx);
        return $redis;
    }

    /**
     * 解决 TTL为0的bug
     *
     * @param $key
     * @return bool
     */
    public function get($key)
    {
        $ttl = $this->ttl($key);
        if($ttl == 0){
            $this->delete($key);
            return false;
        }
        return $this->getValue($key);
    }

    /**
     * @param $host
     * @param $port
     * @param $mid
     * @return $this
     */
    public function setReportConf($host,$port,$mid){
        $this->reportHost = $host;
        $this->reportPort = $port;
        $this->reportMid = $mid;
        return $this;
    }

    /**
     * 强制主
     *
     * @return $this
     */
    public function switchMaster(){
        $this->userMaster = true;
        return $this;
    }

    /**
     * 强制从
     *
     * @return $this
     */
    public function switchSlave(){
        $this->userMaster = false;
        return $this;
    }

    /**
     * 设置重试连接次数
     *
     * @param $count
     * @return $this
     */
    public function setRetryMax($count){
        $this->_retryMax = $count;
        return $this;
    }

    /**
     * @param $method
     * @param array $params
     * @return bool|mixed
     * @throws \Exception
     */
    public function __call($method, $params = array())
    {
        if($method == "select"){
            $this->dbx = $params[0];
        }
        if(isset($this->reWriteMethod[$method])){
            $method = $this->reWriteMethod[$method];
        }
        $type = self::SLAVE;
        //是否为写操作
        $idx = strtolower($method);
        if(isset($this->_writeArr[$idx])){
            $type = self::MASTER;
        }
        if($this->userMaster){
            $type = self::MASTER;
        }
        $objRedis = $this->_getConn($type);
        //长连接 ping 操作
        $status = true;
        if($this->isPConnect && $this->doPing){
            $this->doPing = false;
            $this->ping();
        }
        $this->doPing = true;


        if( ! $objRedis || ! is_a($objRedis, 'Redis')){
            $log = array('redisConnectFailed,type'.$type.',config:'.json_encode($this->_config));
            Log::write($log,"NOTICE");
            throw new \Exception('redisConnectFailed');
        }
        if( ! method_exists($objRedis, $method)){
            $log = array('redisUndefinedMethod:'.$method.',params:'.json_encode($params));
            Log::write($log,"NOTICE");
            throw new \Exception('method_not_exists,method:'.$method);
        }

        $startTime = microtime(true);
        $result = call_user_func_array(array($objRedis,$method), $params);
        $endTime = microtime(true);
        $runTime = 1000 * floatval($endTime - $startTime);
        $runTime = sprintf('%.2f',$runTime);
        if(bccomp($runTime,$this->maxTime,2) == 1){
            $log = array('action'=>'redis','method'=>$method,'params'=>$params,'runTime'=>$runTime);
            Log::write($log,"NOTICE");
        }
        return $result;
    }
}
