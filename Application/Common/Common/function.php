<?php
const ONETHINK_VERSION    = '1.1.141212';
const ONETHINK_ADDON_PATH = './Addons/';

function pdd_base_para($type,$params,$clientId,$clientSecret){
    //$token = $this->redis->get('pdd_user_access_token:'.$this->uid);
    $para = array(
        'type'      =>$type,
        'client_id' =>$clientId,
        'timestamp' =>time(),
        'data_type' =>'JSON',
        'version'   =>'V1',
        //'access_token'=>$token['access_token']
    );
    $para = array_merge($para,$params);
    $para['sign'] = pddSign($para,$clientSecret);
    return http_build_query($para);
}

function pddSign($para,$clientSecret){
    ksort($para);								                   //按字母升序重新排序
    $sequence = '';									                   //定义签名数列
    foreach($para as $k=>$v){		                   //拼接参数
        $sequence .= "{$k}{$v}";
    }
    $sequence = $clientSecret.$sequence.$clientSecret;//拼接密钥
    $sequence = strtoupper(md5($sequence));
    return $sequence;
}

/**
 * @param $para //签名的数组
 * @return string
 */
function pdd_sign($para){

}
//设置队列
function setDataQue($name,$message){
    $redisConfig = C("redis");
    $redis = new \Common\Common\Cache\Redis($redisConfig);
    return $redis->lpush($name,$message);
}
//读取队列
function getDataQue($name){
    $redisConfig = C("redis");
    $redis = new \Common\Common\Cache\Redis($redisConfig);
    return $redis->lpop($name);
}
/**
 * 获取分类信息(缓存数据)
 */
function getCategoryCache($name,$field=''){
    $Storage = storage('Redis');
    $Storage->select(4);
    $key = "category:{$name}:info";
    if(is_array($field)){
        //查询缓存的剩余时间
        $time = $Storage->ttl($key);
        if($time && $time >= -1){
            if($time < 20){                //剩余时间小于20秒
                $Storage->delete($key);     //清理key
            }else{      //否则进行缓存更新
                $set = array();
                foreach($field as $k=>$v){
                    if(is_array($v) && 'exp' == $v[0]){
                        $charac = stripos($v[1],'+') !== false ? '+' : '-';
                        $parse = explode($charac,$v[1]);
                        $Storage->hIncrBy($key,$k,($charac == '+' ? $parse[1] : '-'.$parse[1]));
                    }else{
                        $set[$k] = $v;
                    }
                }
                $set && $Storage->setHash($key,$set);
            }
        }
    }elseif(is_null($field)){                 //直接清空该缓存下的数据
        $Storage->exists($key) && $Storage->delete($key);
    }else{
        //查询缓存的剩余时间
        $time = $Storage->ttl($key);
        if($time && $time >= -1){
            if($time < 20){
                $Storage->delete($key);
            }else{
                if($field){
                    $field = explode(',',$field);
                    $info = $Storage->getHash($key,$field);
                    if(count($field) == 1){
                        return $info[$field[0]];
                    }else{
                        return $info;
                    }
                }else{
                    return $Storage->getHashAll($key);
                }
            }
        }
        $info = M('Category')->where(array('name'=>$name))->find();
        if(!$info){
            return false;
        }
        $Storage->multi();
        $Storage->setHash($key,$info);
        $Storage->expireAt($key,(NOW_TIME+259200));
        $Storage->exec();
        if($field){
            $field = explode(',',$field);
            if(count($field) == 1){
                return $info[$field[0]];
            }else{
                return array_intersect_key($info,array_flip($field));
            }
        }else{
            return $info;
        }
    }
}
/**
 * ID非规律性加密
 * @param string|array $data 要加密的内容,多个数组也可以
 * @param string $key 加密key
 * @param int $min_length 加密的最小长度,最少也是6位
 */
function hashids_encode($data,$key='ldfswkd',$min_length=15){
    $hashids=new \Common\Util\Hashids($key,$min_length);
    return $hashids->encode($data);
}

/**
 * ID非规律性解密
 * @param string $data 要解密的字符串
 * @param string $key 加密key
 * @param int $min_length 加密的最小长度,最少也是6位
 */
function hashids_decode($data,$key='ldfswkd',$min_length=15){
    $hashids=new \Common\Util\Hashids($key,$min_length);
    return $hashids->decode($data);
}

/**
 * 字符串转换为数组,主要用于把分隔符调整到第二个参数
 * @param  string $str  要分割的字符串
 * @param  string $glue 分割符
 */
function str2arr($str,$glue=','){
    return explode($glue,$str);
}
/**
 * 数组转换为字符串,主要用于把分隔符调整到第二个参数
 * @param array $arr 要连接的数组
 * @param string $glue 分割符
 */
function arr2str($arr,$glue=','){
    return implode($glue,$arr);
}
/**
 * 字符串截取,支持中文和其他编码
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 */
function msubstr($str,$start=0,$length,$charset='utf-8',$suffix=true){
    if(function_exists('mb_substr'))
        $slice = mb_substr($str,$start,$length,$charset);
    elseif(function_exists('iconv_substr')){
        $slice = iconv_substr($str,$start,$length,$charset);
        false === $slice && $slice = '';
    }else{
        $re['utf-8'] = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/';
        $re['gb2312'] = '/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/';
        $re['gbk'] = '/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/';
        $re['big5'] = '/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/';
        preg_match_all($re[$charset],$str,$match);
        $slice = join('',array_slice($match[0],$start,$length));
    }
    return mb_strlen($str,$charset) > $length ? $slice . '...' : $slice;
}
/**
 * 返回数组中指定的一列
 * @param $pArray 需要取出数组列的多维数组(或结果集)
 * @param $pKey 需要返回值的列,它可以是索引数组的列索引,或者是关联数组的列的键
 * @param $pCondition 作为返回数组的索引/键的列,它可以是该列的整数索引,或者字符串键值
 * @return 返回新的一维数组
 */
function getSubByKey($pArray,$pKey='',$pCondition=''){
    if(version_compare(PHP_VERSION,'5.5.0','>=')){
        return array_column($pArray,$pKey,$pCondition);
    }
    $result = array();
    $i = 0;
    if(is_array($pArray)){
        foreach($pArray as $temp_array){
            is_object($temp_array) && $temp_array = (array)$temp_array;
            $result[$pCondition && isset($temp_array[$pCondition]) ? $temp_array[$pCondition] : $i] = ('' == $pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : '';
            $i++;
        }
        return $result;
    }else{
        return false;
    }
}
/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间,单位(秒)
 */
function think_encrypt($data,$key='',$expire=0){
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    for($i = 0; $i < $len; $i++){
        if($x == $l) $x = 0;
        $char .= substr($key,$x,1);
        $x++;
    }
    $str = sprintf('%010d',$expire ? $expire + time() : 0);
    for($i = 0; $i < $len; $i++){
        $str .= chr(ord(substr($data,$i,1)) + (ord(substr($char,$i,1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}
/**
 * 系统解密方法
 * @param string $data 要解密的字符串(必须是think_encrypt方法加密的字符串)
 * @param string $key 加密密钥
 */
function think_decrypt($data,$key=''){
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-','_'),array('+','/'),$data);
    $mod4 = strlen($data) % 4;
    if($mod4){
        $data .= substr('====',$mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data,0,10);
    $data = substr($data,10);
    if($expire > 0 && $expire < time()){
        return '';
    }
    $x = 0;
    $len  = strlen($data);
    $l = strlen($key);
    $char = $str = '';
    for($i = 0; $i < $len; $i++){
        if($x == $l) $x = 0;
        $char .= substr($key,$x,1);
        $x++;
    }
    for($i = 0; $i < $len; $i++){
        if(ord(substr($data,$i,1)) < ord(substr($char,$i,1))){
            $str .= chr((ord(substr($data,$i,1)) + 256) - ord(substr($char,$i,1)));
        }else{
            $str .= chr(ord(substr($data,$i,1)) - ord(substr($char,$i,1)));
        }
    }
    return base64_decode($str);
}
/**
 * 数据签名认证
 * @param array $data 被认证的数据
 * @return string 签名
 */
function data_auth_sign($data){
    //数据类型检测
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}
/**
 * 系统非常规MD5加密方法
 * @param string $str 要加密的字符串
 * @return string 加密后的字符串
 */
function think_sys_md5($str,$key='ldzszmt'){
    return '' === $str ? '' : md5(sha1($str) . $key);
}
/**
 * 对查询结果集进行排序
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型,asc正向排序 desc逆向排序 nat自然排序
 * @return array 排序后的结果集
 */
function list_sort_by($list,$field,$sortby='asc'){
    if(is_array($list)){
        $refer = $resultSet = array();
        foreach($list as $i=>$data)
            $refer[$i] = &$data[$field];
        switch($sortby){
            case 'asc':          //正向排序
                asort($refer);
                break;
           case 'desc':// 逆向排序
                arsort($refer);
                break;
           case 'nat': // 自然排序
                natcasesort($refer);
                break;
       }
       foreach ( $refer as $key=> $val)
           $resultSet[] = &$list[$key];
       return $resultSet;
   }
   return false;
}
/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array 转换后的结果集
 */
function list_to_tree($list,$pk='id',$pid='pid',$child='_child',$root=0){
    //创建Tree
    $tree = array();
    if(is_array($list)){
        //创建基于主键的数组引用
        $refer = array();
        foreach($list as $key=>$data){
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach($list as $key=>$data){
            //判断是否存在parent
            $parentId = $data[$pid];
            if($root == $parentId){
                $tree[] = &$list[$key];
            }else{
                if(isset($refer[$parentId])){
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}
/**
 * 格式化字节大小
 * @param number $size 字节数
 * @param string $delimiter 数字和单位分隔符
 * @return string 格式化后的带单位的大小
 */
function format_bytes($size,$delimiter=''){
    $units = array('B','KB','MB','GB','TB','PB');
    for($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2).$delimiter.$units[$i];
}
/**
 * 设置跳转页面URL
 * 使用函数再次封装，方便以后选择不同的存储方式（目前使用cookie存储）
 */
function set_redirect_url($url){
    cookie('redirect_url',$url);
}
/**
 * 获取跳转页面URL
 * @return string 跳转页URL
 */
function get_redirect_url(){
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}
/**
 * 处理插件钩子
 * @param string $hook   钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook,$params=array()){
    \Think\Hook::listen($hook,$params);
}
/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name){
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}
/**
 * 插件显示内容里生成访问插件的url
 * @param string $url 地址
 * @param array $param 参数
 */
function addons_url($url,$param = array()){
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');
    //解析URL带的参数
    if(isset($url['query'])){
        parse_str($url['query'],$query);
        $param = array_merge($query,$param);
    }
    //基础参数
    $params = array('_addons'=>$addons,'_controller'=>$controller,'_action'=>$action);
    $params = array_merge($params,$param); //添加额外参数
    return U('Addons/execute',$params);
}
/**
 * 时间戳格式化
 * @param int $time 时间戳
 * @return string 完整的时间显示
 */
function time_format($time=NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}
/**
 * 获取分类信息并缓存分类
 * @param integer $id 分类ID
 * @param string $field 要获取的字段名
 * @return string 分类信息
 */
function get_category($id,$field = null){
    static $list;
    if(empty($id) || !is_numeric($id)){        //非法分类ID
        return '';
    }
    //读取缓存数据
    empty($list) && $list = S('sys_category_list');
    //获取分类名称
    if(!isset($list[$id])){
        $cate = M('Category')->find($id);
        if(!$cate || 1 != $cate['status']){ //不存在分类,或分类被禁用
            return '';
        }
        $list[$id] = $cate;
        S('sys_category_list',$list);       //重新缓存
    }
    return is_null($field) ? $list[$id] : $list[$id][$field];
}
/**
 * 调用系统的API接口方法(静态方法)
 * @param string  $name 格式 [模块名]/接口名/方法名
 * @param array|string $vars 参数
 */
function api($name,$vars=array()){
    $array = explode('/',$name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array? array_pop($array) : 'Common';
    $callback = $module.'\\Api\\'.$classname.'Api::'.$method;
    if(is_string($vars)){
        parse_str($vars,$vars);
    }
    return call_user_func_array($callback,$vars);
}
/**
 * 调用Common下的storage方法
 * @param $name
 * @return \Common\Storage\$name
 */
function storage($name,$vars=array()){
    static $_model  = array();
    $guid = $name.to_guid_string($vars);
    if(!isset($_model[$guid])){
        $class = '\\Common\\Storage\\'.$name.'\\'.$name;
        $_model[$guid] = new $class($vars);
    }
    return $_model[$guid];
}
/**
 * 调用Common下的Model方法
 * @param $name 模块名
 * @author shaoby
 */
function mblock($name){
    static $_model  = array();
    if(!isset($_model[$name])){
        $class = '\\Common\\Model\\'.$name.'Model';
        $_model[$name] = new $class;
    }
    return $_model[$name];
}
/**
 * curl通过连接获取数据
 */
function get_curl_data($url,$compression='',$agent='',$refer=''){
    if(empty($agent)){
        $agent= 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36';
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($refer){
        curl_setopt($ch, CURLOPT_REFERER, $refer);//带来的Referer
    }
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时限制防止死循环
    if($compression!=''){
        curl_setopt($ch, CURLOPT_ENCODING, $compression);//压缩
    }
    curl_setopt($ch, CURLOPT_URL,$url);
    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return array($info,$result);
}
/**
 * 产生时间和随机数的目录
 * @param  int $length 输出长度
 * @param  string $chars 可选的 ，默认为 abcdefghijklmnopqrstuvwxyz0123456789
 */
function date_rand_dir($length, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789') {
    $hash = '';
    $max = strlen($chars) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return date('Ym/d/d').$hash;
}
/**
 * 手机号格式检测
 * @param string $mobile
 * @return boole 检测成功或失败
 */
function checkMobile($mobile){
    if(!preg_match('/^1[0-9]{10}$/',$mobile)){
        return false;
    }
    return true;
}
/**
 * 短信接口,函数需要PHP环境支持Soap
 * @param string $mobile 手机号码
 * @param string $message 需要发送的信息
 * @return boole
 */
function message_send($mobile,$message,$tempId=34617){
    return mblock('Sms')->message_send($mobile,$message,$tempId);
}
/**
 * 通过cookie检测用户的uid
 */
function getUidByToken(){
    $token = cookie('TOKEN');
    $token_id = cookie('TOKEN_ID');
    if(!$token || !$token_id){
        return false;
    }
    //检测加密
    if(md5($token.'ldzsshop') != $token_id){
        return false;
    }
    $token = think_decrypt($token);
    $token = unserialize($token);
    if(!$token || !is_array($token)){
        return false;
    }
    return $token['uid'];
}
/**
 * 生成订单号
 * @return string
 */
function build_orderid(){
    return date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).rand(10000000,99999999);
}