<?php
namespace Jd\Common;
use Common\Common\Manager\DI;

class Redis{

    //缓存榜单数据---全平台通用
    public static function setSellWellGoodsList($key,$data){
        $key = Constant::SELL_WELL_GOODS_LIST.$key;
        $redis = DI::get("redis");
        $redis->set($key,$data,1800);
    }
    //获取榜单数据---全平台通用
    public static function getSellWellGoodsList($key){
        $key = Constant::SELL_WELL_GOODS_LIST.$key;
        $redis = DI::get("redis");
        return $redis->get($key);
    }
    //设置我的足迹----区分平台
    public static function setMyfootprint($goodsId){
        $key = "user_footprint_".$GLOBALS["platformId"].$GLOBALS["userId"];
        $redis = DI::get("redis");
        $redis->lpush($key,$goodsId);
    }

    //拉取我的浏览足迹---区分平台
    public static function getMyfootprint($offset,$limit){
        $key = "user_footprint_".$GLOBALS["platformId"].$GLOBALS["userId"];
        $redis = DI::get("redis");
        $redis->lrange($key,$offset,$limit);
    }
    //商品详情页缓存 30分钟-----全平台通用
    public static function setProductInfo($key,$productInfo){
        $key = Constant::PRODUCT_INFO.$key;
        $redis = DI::get("redis");
        $redis->set($key,$productInfo,1800);
    }

    //获取商品详情页缓存-----全平台通用
    public static function getProductInfo($goodsId){
        $key = Constant::PRODUCT_INFO.$goodsId;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //首页标签缓存 10分钟-----全平台通用
    public static function setHomeInfo($value){
        $key = Constant::HOME_INFO;
        $redis = DI::get("redis");
        $redis->set($key,$value,1800);
    }

    //读取首页缓存-----全平台通用
    public static function getHomeInfo(){
        $key = Constant::HOME_INFO;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //主题缓存 30分钟------全平台通用
    public static function setThemeList($data,$k){
        $key = Constant::THEME_LIST.$k;
        $redis = DI::get("redis");
        $redis->set($key,$data,1800);
    }

    //查询主题缓存-----全平台通用
    public static function getThemeList($key){
        $key = Constant::THEME_LIST.$key;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //主题商品列表缓存 30分钟----全平台通用
    public static function setThemeGoodsList($themeId,$data){
        $key = Constant::THEME_GOODS_LIST.$themeId;
        $redis = DI::get("redis");
        $redis->set($key,$data,1820);
    }

    //查询主题商品缓存-----全平台通用
    public static function getThemeGoodsList($themeId){
        $key = Constant::THEME_GOODS_LIST.$themeId;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //搜索缓存 30 分钟------全平台通用
    public static function setSearch($where,$data){
        $key = Constant::SEARCH_LIST.$where;
        $redis = DI::get("redis");
        $redis->set($key,$data,1800);
    }
    //读取搜索缓存-----全平台通用
    public static function getSearch($where){
        $key = Constant::SEARCH_LIST.$where;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //统计Pv---区分平台
    public static function setGoodsInfoPv($goods_id){
        $key = Constant::GOODS_INFO_PV.$GLOBALS["platform"];
        $redis = DI::get("redis");
        $redis->ZINCRBY($key,"+1",$goods_id);
    }
    //获取商品pv-----区分平台
    public static function getGoodsInfoPv($goodsId){
        $key = Constant::GOODS_INFO_PV.$GLOBALS["platform"];
        $redis = DI::get("redis");
        return $redis->ZSCORE($key,$goodsId);
    }

    //统计商品UV----区分平台
    public static function setGoodsInfoUv($goodsId){
        $key = Constant::GOODS_INFO_UV.$GLOBALS["platform"]."_".$goodsId;
        $redis = DI::get("redis");
        $redis->SADD($key,$GLOBALS["userId"]);
    }

    //获取商品UV ---区分平台
    public static function getGoodsInfoUv($goodsId){
        $key = Constant::GOODS_INFO_UV.$GLOBALS["platform"]."_".$goodsId;
        $redis = DI::get("redis");
        return $redis->SCARD($key);
    }

    //统计商城UV----区分平台
    public static function setShopUv(){
        $key = Constant::SHOP_UV.$GLOBALS["platform"]."_".date("Y-m-d");
        $redis = DI::get("redis");
        $redis->SADD($key,$GLOBALS["userId"]);
    }

    //统计商城PV-----区分平台
    public static function setShopPv(){
        $key = Constant::SHOP_PV.$GLOBALS["platform"]."_".date("Y-m-d");
        $redis = DI::get("redis");
        $redis->INCR($key);
    }

    //设置缓存爆品特价秒杀---全平台通用
    public static function setSpecialOfferSeckill($num,$data){
        $key = Constant::SPECIAL_OFFER_SECKILL.$num;
        $redis = DI::get("redis");
        $redis->set($key,$data,300);
    }

    //获取爆品特价秒杀-----全平台通用
    public static function getSpecialOfferSeckill($num){
        $key = Constant::SPECIAL_OFFER_SECKILL.$num;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //设置特价秒杀列表页----全平台通用
    public static function setSeckillList($k,$data){
        $key = Constant::SECKILL_LIST.$k;
        $redis = DI::get("redis");
        $redis->set($key,$data,300);
    }

    //获取特价秒杀列表页----全平台通用
    public static function getSeckillList($k){
        $key = Constant::SECKILL_LIST.$k;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //缓存主题
    public static function setTheme($key,$data){
        $key = Constant::THEME.$key;
        $redis = DI::get("redis");
        $redis->set($key,$data,1810);
    }

    //读取主题
    public static function getTheme($key){
        $key = Constant::THEME.$key;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //秒杀商品配置缓存
    public static function setSeckillConfig($key,$data){
        $key = Constant::SECKILL_CONFIG_INFO.$key;
        $redis = DI::get("redis");
        $redis->set($key,$data,300);
    }

    //获取秒杀商品配置
    public static function getSeckillConfig($key){
        $key = Constant::SECKILL_CONFIG_INFO.$key;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //设置下架商品id
    public static function setLowerShelfGoodsId($goodsId){
        $key = Constant::LOWER_SHELF_GOODS_ID_LIST;
        $redis = DI::get("redis");
        $obj = $redis->EXISTS($key);
        $redis->SADD($key,$goodsId);
    }

    //获取下架商品id
    public static function getLowerShelfGoodsId(){
        $key = Constant::LOWER_SHELF_GOODS_ID_LIST;
        $redis = DI::get("redis");
        return $redis->SCARD($key);
    }
    //缓存买手token
    public static function setQqbuyToken($data){
        $key = Constant::QQBUY_TOKEN;
        $redis = DI::get("redis");
        $redis->set($key,$data,36000);
    }

    //读取买手token
    public static function getQqbuyToken(){
        $key = Constant::QQBUY_TOKEN;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //缓存买手token
    public static function setQqbuyGoodsPage($data, $prefix){
        $key = 'qqbuy_jd_goods_page_'.$prefix;
        $redis = DI::get("redis");
        $redis->set($key,$data,3600000000);
    }

    //读取买手token
    public static function getQqbuyGoodsPage($prefix){
        $key = 'qqbuy_jd_goods_page_'.$prefix;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //统计Page---区分平台
    public static function setGoodsInfoPage($cateId){
        $key = Constant::GOODS_INFO_PV.$GLOBALS["platform"].'_jd';
        $redis = DI::get("redis");
        $redis->ZINCRBY($key,"+1",$cateId);
    }
    //获取商品page-----区分平台
    public static function getGoodsInfoPage($cateId){
        $key = Constant::GOODS_INFO_PV.$GLOBALS["platform"].'_jd';
        $redis = DI::get("redis");
        return $redis->ZSCORE($key,$cateId);
    }

    //缓存买手idx
    public static function setCurIdx($cate, $idx){
        $key = 'qqbuy_jd_goods_idx_'.$cate;
        $redis = DI::get("redis");
        $redis->set($key,$idx,3600*24*365*10);
    }

    //读取买手idx
    public static function getCurIdx($cate){
        $key = 'qqbuy_jd_goods_idx_'.$cate;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //缓存买手num
    public static function setCurNum($idx){
        $key = 'qqbuy_jd_goods_catenum';
        $redis = DI::get("redis");
        $redis->set($key,$idx,3600*24*365*10);
    }

    //读取买手num
    public static function getCurNum(){
        $key = 'qqbuy_jd_goods_catenum';
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //缓存买手idx
    public static function setCurIdxHot($cate, $idx){
        $key = 'qqbuy_jd_goods_idx_hot_'.$cate;
        $redis = DI::get("redis");
        $redis->set($key,$idx,3600*24*365*10);
    }

    //读取买手idx
    public static function getCurIdxHot($cate){
        $key = 'qqbuy_jd_goods_idx_hot_'.$cate;
        $redis = DI::get("redis");
        return $redis->get($key);
    }

    //缓存买手num
    public static function setCurNumHot($idx){
        $key = 'qqbuy_jd_goods_catenum_hot';
        $redis = DI::get("redis");
        $redis->set($key,$idx,3600*24*365*10);
    }

    //读取买手num
    public static function getCurNumHot(){
        $key = 'qqbuy_jd_goods_catenum_hot';
        $redis = DI::get("redis");
        return $redis->get($key);
    }

}