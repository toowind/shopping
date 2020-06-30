<?php
namespace Common\Storage\Redis;
class Redis{

    protected $Redis;

    protected $Prefix;

    function __construct(){
        static $cache = '';
        if(empty($cache)){
            $cache = \Think\Cache::getInstance('Redis');
        }
        $this->Redis = $cache;
        $this->Prefix = C('DATA_CACHE_PREFIX');
    }
    /**
     * 切换到指定的数据库
     */
    function select($index=0){
        return $this->Redis->select($index);
    }
    function lLen($name){
        return $this->Redis->lLen($this->Prefix.$name);
    }

    function rPush($name,$value){
        return $this->Redis->rPush($this->Prefix.$name,$value);
    }
    function rPop($name){
        return $this->Redis->rPop($this->Prefix.$name);
    }
    function lPop($name){
        return $this->Redis->lPop($this->Prefix.$name);
    }
    /*
     * $name $key值
     * $time 时间
     * */
    function brPop($name,$time=0){
        return $this->Redis->brPop(array($this->Prefix.$name),$time);
    }

    function lPush($name,$value){
        return $this->Redis->lPush($this->Prefix.$name,$value);
    }
    function lPushx($name,$value){
        return $this->Redis->lPushx($this->Prefix.$name,$value);
    }

    function getRange($name,$start=0,$end=-1){
        return $this->Redis->lRange($this->Prefix.$name,$start,$end);
    }
/*删除redis中的key*/
    function delete($name){
        if(is_array($name)){
            foreach($name as $key=>$val){
                $name[$key] = $this->Prefix.$val;
            }
        }else{
            $name = $this->Prefix.$name;
        }
        return $this->Redis->delete($name);
    }

    function exists($name){
        return $this->Redis->exists($this->Prefix.$name);
    }

    function get($name){
        return $this->Redis->__get($name);
    }

    function set($name,$value){
        return $this->Redis->__set($name,$value);
    }

    function incr($name){
        return $this->Redis->incr($this->Prefix.$name);
    }
    function incrBy($name,$val){
        return $this->Redis->incrBy($this->Prefix.$name,$val);
    }
    function hIncrBy($name,$field,$value=1){
        return $this->Redis->hIncrBy($this->Prefix.$name,$field,$value);
    }

    function hDel($name,$field){
        return $this->Redis->hDel($this->Prefix.$name,$field);
    }

    function hSet($name,$field,$value){
        return $this->Redis->hSet($this->Prefix.$name,$field,$value);
    }

    function getHashAll($name){
        return $this->Redis->hGetAll($this->Prefix.$name);
    }

    function getHash($name,$field){
        return $this->Redis->hMGet($this->Prefix.$name,$field);
    }

    function setHash($name,$value){
        return $this->Redis->hMset($this->Prefix.$name,$value);
    }

    function setEx($name,$expire,$value){
        return $this->Redis->setex($this->Prefix.$name,$expire,$value);
    }

    function setNx($name,$value){
        return $this->Redis->setnx($this->Prefix.$name,$value);
    }

    function sAdd($name,$value){
        return $this->Redis->sAdd($this->Prefix.$name,$value);
    }
    function sDiffStore($dstKey,$key1,$key2){
        return $this->Redis->sDiffStore($this->Prefix.$dstKey,$this->Prefix.$key1,$this->Prefix.$key2);
    }
    function sPop($name){
        return $this->Redis->sPop($this->Prefix.$name);
    }
    /**
     * 将哈希表key中的域field的值设置为 value,当且仅当域 field不存在
     */
    function hSetNx($name,$field,$value){
        return $this->Redis->hSetNx($this->Prefix.$name,$field,$value);
    }
    /**
     * 返回集合中的所有成员
     */
    function sMembers($name){
        return $this->Redis->sMembers($this->Prefix.$name);
    }
    /**
     * 判断member元素是否集合key的成员。
     */
    function sIsMember($name,$value){
        return $this->Redis->sIsMember($this->Prefix.$name,$value);
    }
    /**
     * 返回列表key中,下标为index的元素
     */
    function lIndex($name,$index){
        return $this->Redis->lIndex($this->Prefix.$name,$index);
    }
    /**
     * 让列表只保留指定区间内的元素,不在指定区间之内的元素都将被删除
     */
    function lTrim($name,$start,$stop){
        return $this->Redis->lTrim($this->Prefix.$name,$start,$stop);
    }
    /**
     * 为key设置生存时间,以时间戳为单位
     */
    function expireAt($name,$timestamp){
        return $this->Redis->expireAt($this->Prefix.$name,$timestamp);
    }
    /**
     * 以秒为单位返回给定key的剩余生存时间
     */
    function ttl($name){
        return $this->Redis->ttl($this->Prefix.$name);
    }
    /**
     * 返回或保存给定列表,集合,有序集合key中经过排序的元素
     */
    function sort($name,$option=null){
        return $this->Redis->sort($this->Prefix.$name,$option);
    }
    /**
     * 清空当前所有key
     */
    function flushDB(){
        return $this->Redis->flushDB();
    }
    /**
     * 标记一个事务块的开始
     */
    function multi(){
        return $this->Redis->multi();
    }
    /**
     * 执行所有事务块内的命令
     */
    function exec(){
        return $this->Redis->exec();
    }
    /**
     * 取消事务，放弃执行事务块内的所有命令。
     */
    function discard(){
        return $this->Redis->discard();
    }

    function keys($pattern){
        return $this->Redis->keys($pattern);
    }

    /**
     * List操作函数 根据参数 count 的值，移除列表中与参数 value 相等的元素。
     * count > 0 : 从表头开始向表尾搜索，移除与 value 相等的元素，数量为 count 。
     * count < 0 : 从表尾开始向表头搜索，移除与 value 相等的元素，数量为 count 的绝对值。
     * count = 0 : 移除表中所有与 value 相等的值。
     */
    function lRem($name,$value,$count=0){
        return $this->Redis->lRem($this->Prefix.$name,$value,$count);
    }
    /**
     * List操作函数   将值 value 插入到列表 key 当中，位于值 pivot 之前或之后。
     * @param $pivot list中存在的值
     * @param $position BEFORE OR AFTER
     * @param $value 需要插入的值
     */
    function lInsert($name,$value,$pivot,$position){
        return $this->Redis->lInsert($this->Prefix.$name,$position,$pivot,$value);
    }
    /**
     * Hash操作函数   查看哈希表 key 中，给定域 field 是否存在。
     * @param $name $key值
     * @param $field 字段值
     * @return true or false
     */
    function hExists($name,$field){
        return $this->Redis->hExists($this->Prefix.$name,$field);
    }
    /**
     * List操作函数 将列表 key 下标为 index 的元素的值设置为 value 。
     * @param $name $key值
     * @param $index 索引值
     * @return true or false
     */
    function lSet($name,$index,$value){
        return $this->Redis->lSet($this->Prefix.$name,$index,$value);
    }
    /**
     *有序操作函数 添加操作
     * @param $name $key值
     * @param $sort 排序值
     * @param $value 值
     * @return true or false
     */
    function zAdd($name,$sort=0,$value){
        if(!$value){
            return false;
        }
        return $this->Redis->zAdd($this->Prefix.$name,$sort,$value);
    }
    /**
     *有序SET操作函数 返回有序集 key 中，指定区间内的成员。
     * @param $name $key值
     * @param $start 开始位置
     * @param $end  结束位置
     * @param $withscores
     * @return true or false
     */
    function zRevRange($name, $start, $end, $withscores = null){
        return $this->Redis->zRevRange($this->Prefix.$name,$start,$end,$withscores);
    }
    /**
     *有序SET操作函数 返回有序集 key 中的成员数量
     * @param $name $key值
     * @return int
     */
    function zLen($name){
        return $this->Redis->zCard($this->Prefix.$name);
    }

    function zRank($name,$member){
        return $this->Redis->zRank($this->Prefix.$name,$member);
    }

    function zRevRank($name,$member){
        return $this->Redis->zRevRank($this->Prefix.$name,$member);
    }
    /**
     *有序SET操作函数 移除有序集 key 中的一个或多个成员，
     * @param $name $key值
     * @param $value  需要删除的值
     * @return int   删除的数量
     */
    function zRem($name,$value){
        return $this->Redis->zRem($this->Prefix.$name,$value);
    }
    /**
     *有序SET操作函数 为集合中的某个元素 增量+ $value
     * @param $name $key值
     * @param $value  需要增加的值
     * @param $member  需要被增加的值
     * @return int   删除的数量
     */
    function zIncrBy($name,$member,$value=1){
        return $this->Redis->zIncrBy($this->Prefix.$name,$value,$member);
    }

    function zScore($name,$field){
        return $this->Redis->zScore($this->Prefix.$name,$field);
    }

    function zRange($name,$start,$end,$withscores=false){
        return $this->Redis->zRange($this->Prefix.$name,$start,$end,$withscores);
    }

    function sRandMember($name,$count){
        return $this->Redis->sRandMember($this->Prefix.$name,$count);
    }
/*
 * 集合长度
 * */
    function sLength($name){
        return $this->Redis->sCard($this->Prefix.$name);
    }

    /*
     * 获取单个hash值
     * */
    function hGet($name,$field){
        return $this->Redis->hGet($this->Prefix.$name,$field);
    }

   function zRangeByScore($name, $start, $end, $options = array() ) {
       return $this->Redis->zRangeByScore($this->Prefix.$name,$start,$end,$options);
   }
    /*
  * 删除集合中的元素
  * */
    function sRem($name,$value){
        return $this->Redis->sRem($this->Prefix.$name,$value);
    }
    /*
   * 删除集合中的元素 根据score的范围
   * */
    function zRemRangeByScore($name,$start,$end){
        return $this->Redis->zRemRangeByScore($this->Prefix.$name,$start,$end);
    }
    function scan($iterator, $pattern = '', $count = 0){
        return $this->Redis->scan($iterator, $pattern, $count);
    }

    function getRangeTest($name,$start=0,$end=-1){
        return $this->Prefix.$name;
    }
}