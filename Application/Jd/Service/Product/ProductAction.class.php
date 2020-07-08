<?php
namespace Jd\Service\Product;

use Admin\Model\ConfigModel;
use Common\Action\BaseAction;
use Common\Common\Manager\DI;
use Common\Exception\Exception;
use Order\Model\PddOrderModel;
use Passport\Model\UserBroweGoodsModel;
use Passport\Model\UserCollectionModel;
use Passport\Model\UserCollectionStatisticsModel;
use Jd\Service\Common;
use Jd\Common\ApiException;
use Think\Log;
use Jd\Common\Redis;
class ProductAction extends BaseAction {

    //开放平台189----->多多进宝186账号
    private static $pdd_config = array(
        "my" => array(
            "client_id" =>'a1a144b6af2844a7aa6ff967df2eb7af',
            "client_secret"=>'6cb850b3bd0378cab2da81f83ce9ffe95c62a27d',
            "pid" =>'8501060_102897365'
        ),
        "zq" => array(
            "client_id" =>'674588b41be747d8aef42f1b32599600',
            "client_secret"=>'bd5d5c20b8d9bc3faa91ed88069694aee941abaf',
            "pid" =>'8308015_102892635'
        )
    );
    //京东请求地址
    private static $url = 'https://open.qqbuy.com/api';
    private static $ddxUrl = 'http://api.tbk.dingdanxia.com';
    private static $apikey = 'ag3U6pwUP4UbylHm97OImTlZoR01BXtD';
    private static $jdunionId = '1002889981';

        //所有秒杀配置key
    private static $seckillTypeList = ["ONE_SECKILL","TEN_SECKILL","CLEARANCE_PRICE","RETURN_COMMISSION_RATE","SECKILL_COMMISSION_RATE"];
    //秒杀商品key
    private static $seckillGoodsKey = ["ONE_SECKILL","TEN_SECKILL","CLEARANCE_PRICE"];
    //秒杀商品配置value
    private static $seckillValue = [];
    //价格范围
    private static $price_range = [
        "ONE_SECKILL"=>[
            "from"=>"50","to"=>"200"
        ],
        "TEN_SECKILL"=>[
            "from"=>"700","to"=>"1200"
        ],
        "CLEARANCE_PRICE"=>[
            "from"=>"3000","to"=>"5000"
        ],
    ];


//    //主题商品查询-----不区分平台
//    public static function getThemeGoodsList($data){
//        $ResponseData = Redis::getThemeGoodsList($data["theme_id"]);
//        if(!empty($ResponseData)){
//            return json_decode($ResponseData,true);
//        }
//        $http = DI::get("http");
//        $param = array(
//            'theme_id'=>$data["theme_id"]
//        );
//        $arr = self::base_para(self::$action["theme_goods_list"],$param);
//        $arr = $http->post(self::$url,$arr);
//        if($arr["status"] !=0){
//            Log::write(json_encode($data),'HTTP_ERROR_PDD');
//            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
//        }
//        $arr = json_decode($arr["data"],true);
//        $ResponseData =self::ArrangementParam($arr["theme_list_get_response"]["goods_list"]);
//
//        Redis::setThemeGoodsList($data["theme_id"],json_encode($ResponseData));
//        return $ResponseData;
//    }
    /*
     * 生成下单链接
     * $type 1.自买链接  2.分享链接
     * 区分平台
     */
    public static function purchaseUrl($goods_id){
//        $device_type = $GLOBALS["userInfo"]["device_type"];
//        if($device_type){
//            if(strtolower($device_type)=='android'){
//                $device_type = 'android';
//            }elseif(strtolower($device_type)=='ios'){
//                $device_type = 'ios';
//            }elseif(strtolower($device_type)=='mini'){
//                $device_type = 'mini';
//            }
//        }else{
//            $device_type = 'android';
//        }
        $userTag = $GLOBALS["userId"];
        $requestdData['apikey'] = self::$apikey;
        $requestdData['materialId'] = 'https://item.jd.com/'.$goods_id.'.html';
        $requestdData['unionId'] = self::$jdunionId;
        $requestdData['positionId'] = $userTag;
//        $requestdData['pid'] = 'android';
//        $requestdData['subUnionId'] = 'self';



//
//
//
//
//
//        $requestdDataSelf['skuId'] = $requestdData['skuId'] = $goods_id;
//        $requestdData['userTag'] = $userTag.'_'.$device_type;
        $data = self::http_get(self::$ddxUrl.'/jd/by_unionid_promotion',$requestdData);

        $requestdData['positionId'] = $GLOBALS["userId"]*100;
        $dataSelf = self::http_get(self::$ddxUrl.'/jd/by_unionid_promotion',$requestdData);


//        var_dump($data);
//        die();
//
//        $requestdDataSelf['userTag'] = $userTag.'_self'.'_'.$device_type;
//        $dataSelf = json_decode(self::http_get(self::$url.'/goods/getGoodsPromotionDeepLink',$requestdDataSelf), true);

        $data = json_decode($data,true);
        if($data["code"] !=200){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }

        if($dataSelf["code"] !=200){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }
//        if(!isset($data["promotionUrl"]) || !isset($data["deepLink"]) || !isset($dataSelf["promotionUrl"]) || !isset($dataSelf["deepLink"])){
//            Log::write(json_encode($data),'HTTP_ERROR_PDD_SHORT_URL');
//            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
//        }
        $short_url = $data["data"]["shortURL"];
        $url["short_url"] = $short_url;
        //唤醒app链接
//        $url["awaken_app_url"] =$data["deepLink"];

        $url["short_url_self"] = $dataSelf["data"]["shortURL"];
        //唤醒app链接
//        $url["awaken_app_url_self"] = $dataSelf["deepLink"];
        return $url;
    }

    /*
 * 商品详情
 * $data array
 * $type 默认0  1为秒杀需要获取轮播图
 */
    public static function getProductShareUrl($data,$type = 0){
        $goodsId = $data["goods_id"];
        $key_other = $goodsId.'_'.$type.'_jd:'.$GLOBALS["userId"];

        //获取用户版本判断跳转方式
        $app_version = $GLOBALS["userInfo"]["app_version"];
        if($GLOBALS["platformId"] == 2 && $app_version >= "1.5.5"){
            //中青1.5.5往后版本支持唤醒京东app
            $jump_state = 1;
        }else{
            //跳h5
            $jump_state = 0;
        }
        $otherinfo = Redis::getProductInfo($key_other);
        $otherinfo = '';
        if(!empty($otherinfo)){
            $ResponseDataOther = json_decode($otherinfo,true);
            $ResponseData['purchaseUrl'] = $ResponseDataOther['purchaseUrl'];
            $ResponseData['awaken_app_url'] = $ResponseDataOther['awaken_app_url'];
            $ResponseData['purchaseUrl_self'] = $ResponseDataOther['purchaseUrl_self'];
            $ResponseData['awaken_app_url_self'] = $ResponseDataOther['awaken_app_url_self'];
            $ResponseData['jump_state'] = $ResponseDataOther['jump_state'];
            $ResponseData['app_version'] = $ResponseDataOther['app_version'];
            $ResponseData['platformId'] = $ResponseDataOther['platformId'];
        }else{
            //下单链接
            $purchaseUrl = self::purchaseUrl($goodsId);
            $myResponseData["purchaseUrl"] = $ResponseData["purchaseUrl"] = $purchaseUrl["short_url"];
            $myResponseData["awaken_app_url"] = $ResponseData["awaken_app_url"] = $purchaseUrl["awaken_app_url"];
            $myResponseData["purchaseUrl_self"] = $ResponseData["purchaseUrl_self"] = $purchaseUrl["short_url_self"];
            $myResponseData["awaken_app_url_self"] = $ResponseData["awaken_app_url_self"] = $purchaseUrl["awaken_app_url_self"];
            //跳转状态
            $myResponseData["jump_state"] = $ResponseData["jump_state"] = $jump_state;
            $myResponseData["app_version"] = $ResponseData["app_version"] = $app_version;
            $myResponseData["platformId"] = $ResponseData["platformId"] = $GLOBALS["platformId"];
            $myResponseData["app_version"] = $ResponseData["app_version"] = $app_version;
            Redis::setProductInfo($key_other,json_encode($myResponseData));
        }
        $device_type = $GLOBALS["userInfo"]["device_type"];
        $ResponseData["device_type"] = $device_type;
        return $ResponseData;
    }

    public static function getConvertUrls($data){
//        $device_type = $GLOBALS["userInfo"]["device_type"];
//        if($device_type){
//            if(strtolower($device_type)=='android'){
//                $device_type = 'android';
//            }elseif(strtolower($device_type)=='ios'){
//                $device_type = 'ios';
//            }elseif(strtolower($device_type)=='mini'){
//                $device_type = 'mini';
//            }
//        }else{
//            $device_type = 'android';
//        }

        $catesName = ['0_200'=>'热卖','0_1'=>'精选','0_2'=>'大咖推荐','0_10'=>'9.9专区','0_25'=>'生活超市','0_27'=>'居家日用','0_26'=>'母婴','0_22'=>'爆品'];
        $curl = $data["url"];
        $materialUrls = $curl;
        $param = array(
            'apikey'=>self::$apikey,
            'url'=>$materialUrls,
        );
        $data = json_decode(self::http_get(self::$ddxUrl.'/jd/get_jd_skuid',$param, 1), true);
        if($data["code"] != 200 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        $goods_id = $data["data"];
        $materialId = 'https://item.jd.com/'.$goods_id.'.html';
        $cparam = array(
            'apikey'=>self::$apikey,
            'materialId'=>$materialId,
            'unionId'=>self::$jdunionId,
            'positionId'=> $GLOBALS["userId"]
        );

        $cdata = json_decode(self::http_get(self::$ddxUrl.'/jd/by_unionid_promotion',$cparam, 1), true);
        if($cdata["code"] != 200 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }

        $infoparam = array(
            'apikey'=>self::$apikey,
            'skuIds'=>$goods_id,
        );

        $infoData = json_decode(self::http_get(self::$ddxUrl.'/jd/query_goods_promotioninfo',$infoparam, 1), true);
        if($infoData["code"] != 200 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        $skuName = $infoData['data'][0]['goodsName'];
        $imgUrl = $infoData['data'][0]['imgUrl'];
        $unitPrice = $infoData['data'][0]['unitPrice'];



//        if($data["promotionInfo"][$curl]["code"] != 200){
//            Log::write(json_encode($data["promotionInfo"][$curl]),'HTTP_ERROR_PDD');
//            Exception::throwException(Exception::HTTP_ERROR);
//        }
//        $param['positionId'] = $GLOBALS["userId"].'_self_'.$device_type;
//        $dataSelf = json_decode(self::http_get(self::$ddxUrl.'/jd/by_unionid_promotion',$param, 1), true);
        $ResponseData["purchaseUrl"] = $cdata["data"]["shortURL"];
//        $ResponseData["awaken_app_url"] = $data["promotionInfo"][$curl]["deepLink"];
//        $ResponseData["purchaseUrl_self"] = $dataSelf["promotionInfo"][$curl]["resultUrl"];
//        $ResponseData["awaken_app_url_self"] = $dataSelf["promotionInfo"][$curl]["deepLink"];
        $ResponseData["goods_id"] = $goods_id;
        $ResponseData["goods_name"] = $skuName;
        $ResponseData["img"] = $imgUrl;
        $ResponseData["price"] = $unitPrice;
//        $ResponseData["discount"] = $dataSelf["promotionInfo"][$curl]["entity"]["discount"];
//        $ResponseData["is_pg"] = $dataSelf["promotionInfo"][$curl]["entity"]["isPg"];
//        $ResponseData["is_coupon"] = $dataSelf["promotionInfo"][$curl]["entity"]["isCoupon"];
//        $ResponseData["discountPrice"] = $dataSelf["promotionInfo"][$curl]["entity"]["discountPrice"];
//        $ResponseData["return_cash"] = bcmul($dataSelf["promotionInfo"][$curl]["entity"]["commission"],self::getUserPercent(),2);

//        if ($ResponseData["is_pg"]){
//            if($ResponseData["is_coupon"]){
//                $priceName = '券后价';
//            }else{
//                $priceName = '拼购价';
//            }
//        }else{
//            if($ResponseData["is_coupon"]){
//                $priceName = '券后价';
//            }else{
//                $priceName = '超低价';
//            }
//        }
//        $ResponseData["priceName"] = $priceName;
//
//        //商品详情页缓存key
//        $key = $ResponseData["goods_id"].'_0_jd';
//        //查询缓存
//        $goodsInfo = Redis::getProductInfo($key);
//        $goodsData = json_decode($goodsInfo,true);
//        $ResponseData["goods_category"] = $catesName[$goodsData["goods_category"]];
//        if(!$ResponseData["goods_category"]){
//            $sql = "select bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where bar_code='".$ResponseData["goods_id"]."' and source_id=1 order by id desc limit 1";
//            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
//            if($mysqli){
//                $result = mysqli_query($mysqli, $sql);
//                $goodsInfo = mysqli_fetch_assoc($result);
//                //店铺名称
//                $ResponseData["goods_category"] = $goodsInfo["cate_id"];
//                $ResponseData["cate_id_no"] = $goodsInfo["cate_id_no"];
//            }
//        }

        return $ResponseData;
    }

    public static function getConvertUrlsbak($data){
        $device_type = $GLOBALS["userInfo"]["device_type"];
        if($device_type){
            if(strtolower($device_type)=='android'){
                $device_type = 'android';
            }elseif(strtolower($device_type)=='ios'){
                $device_type = 'ios';
            }elseif(strtolower($device_type)=='mini'){
                $device_type = 'mini';
            }
        }else{
            $device_type = 'android';
        }

        $catesName = ['0_200'=>'热卖','0_1'=>'精选','0_2'=>'大咖推荐','0_10'=>'9.9专区','0_25'=>'生活超市','0_27'=>'居家日用','0_26'=>'母婴','0_22'=>'爆品'];
        $curl = $data["url"];
        $materialUrls[0] = urlencode($curl);
        $param = array(
            'apikey'=>self::$apikey,
            'materialUrls'=>$materialUrls,
            'userTag'=> $GLOBALS["userId"].'_'.$device_type
        );
        $data = json_decode(self::http_get(self::$url.'/tools/convertShortUrls2DeepLink',$param, 1), true);
        if($data["code"] != 200 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        if($data["promotionInfo"][$curl]["code"] != 200){
            Log::write(json_encode($data["promotionInfo"][$curl]),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        $param['userTag'] = $GLOBALS["userId"].'_self_'.$device_type;
        $dataSelf = json_decode(self::http_get(self::$url.'/tools/convertShortUrls2DeepLink',$param, 1), true);
        $ResponseData["purchaseUrl"] = $data["promotionInfo"][$curl]["resultUrl"];
        $ResponseData["awaken_app_url"] = $data["promotionInfo"][$curl]["deepLink"];
        $ResponseData["purchaseUrl_self"] = $dataSelf["promotionInfo"][$curl]["resultUrl"];
        $ResponseData["awaken_app_url_self"] = $dataSelf["promotionInfo"][$curl]["deepLink"];
        $ResponseData["goods_id"] = $dataSelf["promotionInfo"][$curl]["entity"]["skuId"];
        $ResponseData["goods_name"] = $dataSelf["promotionInfo"][$curl]["entity"]["skuName"];
        $ResponseData["img"] = $dataSelf["promotionInfo"][$curl]["entity"]["imgUrl"];
        $ResponseData["price"] = $dataSelf["promotionInfo"][$curl]["entity"]["price"];
        $ResponseData["discount"] = $dataSelf["promotionInfo"][$curl]["entity"]["discount"];
        $ResponseData["is_pg"] = $dataSelf["promotionInfo"][$curl]["entity"]["isPg"];
        $ResponseData["is_coupon"] = $dataSelf["promotionInfo"][$curl]["entity"]["isCoupon"];
        $ResponseData["discountPrice"] = $dataSelf["promotionInfo"][$curl]["entity"]["discountPrice"];
        $ResponseData["return_cash"] = bcmul($dataSelf["promotionInfo"][$curl]["entity"]["commission"],self::getUserPercent(),2);

        if ($ResponseData["is_pg"]){
            if($ResponseData["is_coupon"]){
                $priceName = '券后价';
            }else{
                $priceName = '拼购价';
            }
        }else{
            if($ResponseData["is_coupon"]){
                $priceName = '券后价';
            }else{
                $priceName = '超低价';
            }
        }
        $ResponseData["priceName"] = $priceName;

        //商品详情页缓存key
        $key = $ResponseData["goods_id"].'_0_jd';
        //查询缓存
        $goodsInfo = Redis::getProductInfo($key);
        $goodsData = json_decode($goodsInfo,true);
        $ResponseData["goods_category"] = $catesName[$goodsData["goods_category"]];
        if(!$ResponseData["goods_category"]){
            $sql = "select bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where bar_code='".$ResponseData["goods_id"]."' and source_id=1 order by id desc limit 1";
            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
            if($mysqli){
                $result = mysqli_query($mysqli, $sql);
                $goodsInfo = mysqli_fetch_assoc($result);
                //店铺名称
                $ResponseData["goods_category"] = $goodsInfo["cate_id"];
                $ResponseData["cate_id_no"] = $goodsInfo["cate_id_no"];
            }
        }

        return $ResponseData;
    }

    //查询当前登录用户是否收藏指定商品
    public static function getCollectionStatus($goodsId){
        $userCollectionModel = new UserCollectionModel();
        $arr = $userCollectionModel->getCollectionByGoodsId($GLOBALS["userId"],$GLOBALS["platformId"],$goodsId);
        if(empty($arr)){
            return "0";
        }
        return $arr["state"];
    }

    //获取用户分佣比例
    public static function getUserPercent(){
//        $commission = bcdiv(Common\CommonAction::getUserRateConfig(), 100, 2) ? : 0.50;
        $commission = 1.0;
        return bcmul($commission,0.9,3);//乘以最终比例
//        return bcdiv(Common\CommonAction::getUserRateConfig(), 100, 2) ? : 0.50;
    }
    //首页
    public static function getHomeList(){
//        //统计入口PV UV
//        Redis::setShopPv();
//        Redis::setShopUv();

        $ResponseData = Redis::getHomeInfo();
        if(!empty($ResponseData)){
            $ResponseData = json_decode($ResponseData,true);
//            unset($ResponseData['label'][1]);
            return $ResponseData;
        }
        $data = self::http_get(self::$url.'/category/queryCategory');
        $data = json_decode($data, true);
        foreach($data["list"] as $key=>$item){
//            if('0_1'==$item["key"]){
//                continue;
//            }
            $key = $item["key"];
            $value = $item["value"];
            $label[$key]["parent_opt_id"] = 0;
            $label[$key]["level"] = 1;
            $label[$key]["opt_name"] = $value;
            $label[$key]["opt_id"] = $key;

        }
//        die();
//        $label = ["精选","榜单","百货","女装","男装","食品","水果","母婴","鞋包","内衣","美妆","电器","家纺","文具","运动","虚拟","汽车","家装","家具","医药"];
//        $http = DI::get("http");
//        $param = array(
//            'parent_opt_id'=> 0
//        );
//        $data = self::base_para(self::$action["menu_list"],$param);
//        $data = $http->post(self::$url,$data);
//        if($data["status"] != 0 ){
//            Log::write(json_encode($data),'HTTP_ERROR_PDD');
//            Exception::throwException(Exception::HTTP_ERROR);
//        }
//        $data = json_decode($data["data"],true);
//        $data = !empty($data["goods_opt_get_response"]["goods_opt_list"]) ? $data["goods_opt_get_response"]["goods_opt_list"] : array();
//        $ResponseData = $newLable = array();
//        $data = array_column($data,NULL,"opt_name");
//        foreach($label as $key=>$val){
//            if(array_key_exists($val,$data)){
//                $newLable[$val] = $data[$val];
//            }elseif ($val == "榜单"){
//                $newLable[$val] = ["parent_opt_id"=>0,"level"=>1,"opt_name"=>"榜单","opt_id"=>-1];
//            }
//        }
        $ResponseData["label"] = array_values($label);
//        $ResponseData["label"][0]["opt_name"] = "推荐";
//        //省钱福利社
//        $jumpUrl = array(
//            "my"=>array(
////                array("name"=>"天天拆红包","url"=>"https://mobile.yangkeduo.com/duo_three_red_packet.html?pid=8501060_102901047&cpsSign=CR_190731_8501060_102901047_29a59073886720fcf2c316f5a128bdfa&duoduo_type=2"),
////                array("name"=>"幸运抽免单","url"=>"https://mobile.yangkeduo.com/duo_roulette.html?pid=8501060_102901066&cpsSign=CL_190731_8501060_102901066_f85ac1930ffd0c5dde634b3eb4da725f&duoduo_type=2"),
////                array("name"=>"1.9包邮","url"=>"https://mobile.yangkeduo.com/duo_nine_nine.html?pid=8501060_102901091&cpsSign=CM_190731_8501060_102901091_ca79255cef7aa8a106d5c03b029a5ad4&duoduo_type=2"),
////                array("name"=>"限时秒杀","url"=>"https://mobile.yangkeduo.com/duo_transfer_channel.html?resourceType=4&pid=8501060_102901151&cpsSign=CE_190731_8501060_102901151_7fb1819d4fb6f5eef1a56ea067351c96&duoduo_type=2"),
////                array("name"=>"今日爆款","url"=>"https://mobile.yangkeduo.com/duo_today_burst.html?pid=8501060_102901127&cpsSign=CM_190731_8501060_102901127_febd41ebfcb384794e6f2eebed9ccee6&duoduo_type=2"),
////                array("name"=>"清仓专区","url"=>"","type"=>1), //主题列表
//                "url"=>"https://mobile.yangkeduo.com/duo_transfer_channel.html?resourceType=4&pid=8501060_102901151&cpsSign=CE_190806_8501060_102901151_8171db8872ead70c946ababee78521e1&duoduo_type=2"
//            ),
//            "zq"=>array(
////                array("name"=>"天天拆红包","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_three_red_packet.html%3Fpid%3D8308015_102892635%26auto_open%3D1%26cpsSign%3DCR_190731_8308015_102892635_3ba8c0bf662395a7e3f64d711740803e%26range_items%3D2%253A200%253A300%26duoduo_type%3D2"),
////                array("name"=>"幸运抽免单","url"=>"https://mobile.yangkeduo.com/duo_roulette.html?pid=8308015_102892635&cpsSign=CL_190731_8308015_102892635_c7e13155bb99373b8f67c72febd9ce88&range_items=1%3A100%3A1000%2C2%3A200%3A250&duoduo_type=2&launch_pdd=1"),
////                array("name"=>"1.9包邮","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_nine_nine.html%3Fpid%3D8308015_102892635%26cpsSign%3DCM_190731_8308015_102892635_60a2807af9aa6de2d5aa901ae6985e4b%26duoduo_type%3D2"),
////                array("name"=>"限时秒杀","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_transfer_channel.html%3FresourceType%3D4%26pid%3D8308015_102892635%26cpsSign%3DCE_190731_8308015_102892635_33d0f47e65db99200ee3be2e19d15990%26duoduo_type%3D2"),
////                array("name"=>"今日爆款","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_today_burst.html%3Fpid%3D8308015_102892635%26cpsSign%3DCM_190731_8308015_102892635_a8642e835c3bb588bc5acf3726fafcf0%26duoduo_type%3D2"),
////                array("name"=>"清仓专区","url"=>"","type"=>1), //主题列表
//                "url"=>"https://mobile.yangkeduo.com/duo_transfer_channel.html?resourceType=4&pid=8308015_102892635&cpsSign=CE_190806_8308015_102892635_0af165e19d6c7563e1ae5b5f53bbd5b0&duoduo_type=2"
//            ),
//        );
//        $ResponseData["welfare"] = $jumpUrl[$GLOBALS["platform"]]["url"];

        //订单返利----先去掉默认0
//        $pddOrderModel = new PddOrderModel();
//        $money = $pddOrderModel->getPromotionAmountTrueByUserId();
//        $rebate = !empty($money[0]["money"]) ? $money[0]["money"] : 0;
        $ResponseData["rebate"] = 0;
        $ResponseData["banner"] = ['image'=>'','url'=>''];

        Redis::setHomeInfo(json_encode($ResponseData));
        return $ResponseData;
    }

//    //分页拉取主题
//    public static function getThemeList($page,$page_size){
//        $k = $page."_".$page_size;
//        //总体一级缓存30分钟
//        $ResponseData = Redis::getThemeList($k);
//        if(!empty($ResponseData)){
//            return json_decode($ResponseData,true);
//        }
//
//        //读取主题列表缓存---主题单独二级缓存
//        $ThemeList = Redis::getTheme($k);
//        if(empty($ThemeList)){
//            //分页拉取主题
//            $http = DI::get("http");
//            $param = [
//                "page"=>$page,
//                "page_size"=>$page_size
//            ];
//            $data = self::base_para(self::$action["theme"],$param);
//            $data = $http->post(self::$url,$data);
//            if($data["status"] != 0 ){
//                Log::write(json_encode($data),'HTTP_ERROR_PDD');
//                Exception::throwException(Exception::HTTP_ERROR);
//            }
//            $data = json_decode($data["data"],true);
//            $theme = !empty($data["theme_list_get_response"]["theme_list"]) ? $data["theme_list_get_response"]["theme_list"] : array();
//            if(empty($theme)){
//                return array();
//            }
//            Redis::setTheme($k,json_encode($theme));
//        }else{
//            $theme  = json_decode($ThemeList,true);
//        }
//
//        //查询后台设置返利佣金比
//        $configModel = new ConfigModel();
//        $config = $configModel->getValueByName(["RETURN_COMMISSION_RATE"]);
//        if(empty($config)){
//            Log::write("Config error",'HTTP_ERROR');
//            Exception::throwException(Exception::HTTP_ERROR);
//        }
//        $v = bcdiv($config[0]["value"],100,2);
//
//
//        //拉取每个主题的商品列表
//        foreach ($theme as $key=>$val){
//            //每个主题下的商品id-----三级缓存
//            $goodsListInfo = self::getThemeGoodsList(["theme_id"=>$val["id"]]);
//            if(count($goodsListInfo) >20){
//                $goodsList = array_slice($goodsListInfo,0,6);
//            }
//            $promotion_rate = array_sum(array_column($goodsList,"promotion_rate"));
//            $averageCostSavings = bcdiv($promotion_rate,6,2);
//            $averageCostSavings = bcmul($averageCostSavings,$v,2)*100;
//            $theme[$key]["average_cost_savings"] = $averageCostSavings."%";
//
//            $theme[$key]["goods_list"] = $goodsList;
//        }
//
//        //设置缓存
//        Redis::setThemeList(json_encode($theme),$k);
//        return $theme;
//    }

    //查询商品列表----不区分平台
    public static function getGoodsList($paramData = array()){
        $x = [
            "0_200"=>"31",//热卖
            "0_1"=>"32",//精选
            "0_2"=>"23",//大咖推荐
            "0_10"=>"10",//9.9专区
            "0_25"=>"25",//生活超市
            "0_27"=>"27",//居家日用
            "0_26"=>"26",//母婴
            "0_22"=>"22",//爆品
        ];
        $RequestData = array();
        $RequestData["apikey"] =  self::$apikey;
        //根据标签id搜索
        if(isset($paramData["opt_id"]) && $paramData["opt_id"] !='')  $RequestData["eliteId"] = intval($x[$paramData["opt_id"]]);
        //page默认1
        if(isset($paramData["page"])) $RequestData["pageIndex"] = $paramData["page"];
        //page_size 默认100
        if(isset($paramData["page_size"]))   $RequestData["pageSize"] = $paramData["page_size"];

        $user_percent = self::getUserPercent();
        //读取缓存
        $key_goods_list = md5(json_encode($paramData));
        $ResponseData = Redis::getSearch($key_goods_list);
//        if(!empty($ResponseData)){
//            $ResponseData = json_decode($ResponseData,true);
//            foreach ($ResponseData as $k=>$item){
//                if(!isset($item['user_percent'])){
//                    $ResponseData[$k]['user_percent'] = $user_percent;
//                }
//                if($item['goods_id']=='1207624000'){
//                    unset($ResponseData[$k]);
//                }
//            }
//            sort($ResponseData);
//            $key = 'goods_id';
//            $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
//            $ResponseData = self::assoc_unique($ResponseData, $key);
//            if (count($RequestData)){
//                return $ResponseData;
//            }
//        }

        $data = self::http_get(self::$ddxUrl.'/jd/query_jingfen_goods', $RequestData);
        $data = json_decode($data,true);
        $page_size = $RequestData["pageSize"];
        $start = ($RequestData["pageIndex"]-1)*$page_size;
//        if($RequestData["cid3"]=='0_22' || $data["code"]!=200){
        if($data["code"]!=200){
            $sql = "select distinct bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where cate_id_no='".$RequestData["cid3"]."' AND source_id=1 AND price<10 and commission_share>=20 order by order_count_30days desc,id desc limit {$start}, {$page_size}";
            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
            if($mysqli){
                $result = mysqli_query($mysqli, $sql);
                $key = 0;
                $responseData = array();
                $ResponseData = array();
                while ($goodsInfo = mysqli_fetch_assoc($result)){
                    $responseData[$key]["goods_id"] = $ResponseData[$key]["goods_id"] = isset($goodsInfo["bar_code"]) ? $goodsInfo["bar_code"] : 0;
                    $responseData[$key]["shop_id"] = $ResponseData[$key]["shop_id"] = isset($goodsInfo["shop_id"]) ? $goodsInfo["shop_id"] : 0;
                    $responseData[$key]["goods_name"] = $ResponseData[$key]["goods_name"] = isset($goodsInfo["store_name"]) ? $goodsInfo["store_name"] : "";
                    $responseData[$key]["goods_thumbnail_url"] = $ResponseData[$key]["goods_thumbnail_url"] = isset($goodsInfo["image"]) ? $goodsInfo["image"] : "";

                    //优惠券金额
                    $coupon_discount = isset($goodsInfo["coupon_discount"]) ? $goodsInfo["coupon_discount"] : 0;
                    $responseData[$key]["coupon_discount"] = $ResponseData[$key]["coupon_discount"] = $coupon_discount;

                    //价格
                    $min_group_price = isset($goodsInfo["ot_price"]) ? $goodsInfo["ot_price"] : 0;
                    $responseData[$key]["min_group_price"] = $ResponseData[$key]["min_group_price"] = $min_group_price;

                    //京东佣金比例 百分比
                    $promotion_rate = bcdiv($goodsInfo["commission_share"],100,2);
                    $responseData[$key]["promotion_rate"] = $ResponseData[$key]["promotion_rate"] = $promotion_rate;
                    //京东佣金
                    //单价  减去 优惠券
                    $responseData[$key]["present_price"] = $ResponseData[$key]["present_price"] = isset($goodsInfo["price"]) ? $goodsInfo["price"] : 0;
                    $responseData[$key]["isCoupon"] = $ResponseData[$key]["isCoupon"] = isset($goodsInfo["is_coupon"]) ? $goodsInfo["is_coupon"] : 0;
                    $responseData[$key]["isPg"] = $ResponseData[$key]["isPg"] = isset($goodsInfo["is_pg"]) ? $goodsInfo["is_pg"] : 0;
                    $responseData[$key]["discountPrice"] = $ResponseData[$key]["discountPrice"] = isset($goodsInfo["price"]) ? $goodsInfo["price"] : 0;
                    $responseData[$key]["pingouPrice"] = $ResponseData[$key]["pingouPrice"] = isset($goodsInfo["pingou_price"]) ? $goodsInfo["pingou_price"] : 0;
                    $commission = isset($goodsInfo["commission"]) ? $goodsInfo["commission"] : 0;
//            $ResponseData[$key]["return_cash"] = bcmul($commission,0.1, 2);
                    $responseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash"] = $commission;
                    $responseData[$key]["comments"] = $ResponseData[$key]["comments"] = isset($goodsInfo["comments"]) ? $goodsInfo["comments"] : 0;
                    $responseData[$key]["goodCommentsShare"] = $ResponseData[$key]["goodCommentsShare"] = isset($goodsInfo["goods_comments_share"]) ? $goodsInfo["goods_comments_share"] : 0;
                    $responseData[$key]["orderCount30days"] = $ResponseData[$key]["orderCount30days"] = isset($goodsInfo["order_count_30days"]) ? $goodsInfo["order_count_30days"] : 0;



                    if ($ResponseData[$key]["isPg"]){
                        if($ResponseData[$key]["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '拼购价';
                        }
                    }else{
                        if($ResponseData[$key]["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '超低价';
                        }
                    }
                    $responseData[$key]["priceName"] = $ResponseData[$key]["priceName"] = $priceName;
                    $ResponseData[$key]['user_percent'] = $user_percent;
                    $key++;
                }
                mysqli_free_result($goodsInfo);
                mysqli_close($mysqli);
                Redis::setSearch($key_goods_list,json_encode($responseData));
                $key_uni = 'shop_id';
//                $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
                $ResponseData = self::assoc_unique($ResponseData, $key_uni);
                return $ResponseData;
            }
        }
        $goodsList = !empty($data["data"]) ? $data["data"] : array();
        if(empty($goodsList)){
            return array();
        }
        //整理数据
        $ResponseData = self::ArrangementParam($goodsList);
        Redis::setSearch($key_goods_list,json_encode($ResponseData));
        if(!empty($ResponseData) && count($ResponseData)){
            foreach ($ResponseData as $k=>$item){

                if(!isset($item['user_percent'])){
                    $ResponseData[$k]['user_percent'] = $user_percent;
                }
                if($item['goods_id']=='1207624000'){
                    unset($ResponseData[$k]);
                }
            }
            sort($ResponseData);
        }
        $key = 'goods_id';
        $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
        $ResponseData = self::assoc_unique($ResponseData, $key);
        return $ResponseData;
    }
    //查询商品列表----不区分平台
    public static function getGoodsListbak($paramData = array()){
        $RequestData = array();
        //根据标签id搜索
        if(isset($paramData["opt_id"]) && $paramData["opt_id"] !='')  $RequestData["cid3"] = $paramData["opt_id"];
        //page默认1
        if(isset($paramData["page"])) $RequestData["pageIndex"] = $paramData["page"];
        //page_size 默认100
        if(isset($paramData["page_size"]))   $RequestData["pageSize"] = $paramData["page_size"];

        $user_percent = self::getUserPercent();
        //读取缓存
        $key_goods_list = md5(json_encode($paramData));
        $ResponseData = Redis::getSearch($key_goods_list);
//            if(!empty($ResponseData) && $RequestData["cid3"]!='0_22'){
        if(!empty($ResponseData)){
            $ResponseData = json_decode($ResponseData,true);
            foreach ($ResponseData as $k=>$item){
                if(!isset($item['user_percent'])){
                    $ResponseData[$k]['user_percent'] = $user_percent;
                }
                if($item['goods_id']=='1207624000'){
                    unset($ResponseData[$k]);
                }
            }
            sort($ResponseData);
            $key = 'goods_id';
            $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
            $ResponseData = self::assoc_unique($ResponseData, $key);
            if (count($RequestData)){
                return $ResponseData;
            }
        }
//        if($RequestData["cid3"]!='0_22'){
//            $data = self::http_get('https://open.qqbuy.com/api/goods/queryGoodsForApi', $RequestData);
//            $data = json_decode($data,true);
//        }
        $data = self::http_get('https://open.qqbuy.com/api/goods/queryGoodsForApi', $RequestData);
        $data = json_decode($data,true);
        $page_size = $RequestData["pageSize"];
        $start = ($RequestData["pageIndex"]-1)*$page_size;
//        if($RequestData["cid3"]=='0_22' || $data["code"]!=200){
        if($data["code"]!=200){
            $sql = "select distinct bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where cate_id_no='".$RequestData["cid3"]."' AND source_id=1 AND price<10 and commission_share>=20 order by order_count_30days desc,id desc limit {$start}, {$page_size}";
            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
            if($mysqli){
                $result = mysqli_query($mysqli, $sql);
                $key = 0;
                $responseData = array();
                $ResponseData = array();
                while ($goodsInfo = mysqli_fetch_assoc($result)){
                    $responseData[$key]["goods_id"] = $ResponseData[$key]["goods_id"] = isset($goodsInfo["bar_code"]) ? $goodsInfo["bar_code"] : 0;
                    $responseData[$key]["shop_id"] = $ResponseData[$key]["shop_id"] = isset($goodsInfo["shop_id"]) ? $goodsInfo["shop_id"] : 0;
                    $responseData[$key]["goods_name"] = $ResponseData[$key]["goods_name"] = isset($goodsInfo["store_name"]) ? $goodsInfo["store_name"] : "";
                    $responseData[$key]["goods_thumbnail_url"] = $ResponseData[$key]["goods_thumbnail_url"] = isset($goodsInfo["image"]) ? $goodsInfo["image"] : "";

                    //优惠券金额
                    $coupon_discount = isset($goodsInfo["coupon_discount"]) ? $goodsInfo["coupon_discount"] : 0;
                    $responseData[$key]["coupon_discount"] = $ResponseData[$key]["coupon_discount"] = $coupon_discount;

                    //价格
                    $min_group_price = isset($goodsInfo["ot_price"]) ? $goodsInfo["ot_price"] : 0;
                    $responseData[$key]["min_group_price"] = $ResponseData[$key]["min_group_price"] = $min_group_price;

                    //京东佣金比例 百分比
                    $promotion_rate = bcdiv($goodsInfo["commission_share"],100,2);
                    $responseData[$key]["promotion_rate"] = $ResponseData[$key]["promotion_rate"] = $promotion_rate;
                    //京东佣金
                    //单价  减去 优惠券
                    $responseData[$key]["present_price"] = $ResponseData[$key]["present_price"] = isset($goodsInfo["price"]) ? $goodsInfo["price"] : 0;
                    $responseData[$key]["isCoupon"] = $ResponseData[$key]["isCoupon"] = isset($goodsInfo["is_coupon"]) ? $goodsInfo["is_coupon"] : 0;
                    $responseData[$key]["isPg"] = $ResponseData[$key]["isPg"] = isset($goodsInfo["is_pg"]) ? $goodsInfo["is_pg"] : 0;
                    $responseData[$key]["discountPrice"] = $ResponseData[$key]["discountPrice"] = isset($goodsInfo["price"]) ? $goodsInfo["price"] : 0;
                    $responseData[$key]["pingouPrice"] = $ResponseData[$key]["pingouPrice"] = isset($goodsInfo["pingou_price"]) ? $goodsInfo["pingou_price"] : 0;
                    $commission = isset($goodsInfo["commission"]) ? $goodsInfo["commission"] : 0;
//            $ResponseData[$key]["return_cash"] = bcmul($commission,0.1, 2);
                    $responseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash"] = $commission;
                    $responseData[$key]["comments"] = $ResponseData[$key]["comments"] = isset($goodsInfo["comments"]) ? $goodsInfo["comments"] : 0;
                    $responseData[$key]["goodCommentsShare"] = $ResponseData[$key]["goodCommentsShare"] = isset($goodsInfo["goods_comments_share"]) ? $goodsInfo["goods_comments_share"] : 0;
                    $responseData[$key]["orderCount30days"] = $ResponseData[$key]["orderCount30days"] = isset($goodsInfo["order_count_30days"]) ? $goodsInfo["order_count_30days"] : 0;



                    if ($ResponseData[$key]["isPg"]){
                        if($ResponseData[$key]["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '拼购价';
                        }
                    }else{
                        if($ResponseData[$key]["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '超低价';
                        }
                    }
                    $responseData[$key]["priceName"] = $ResponseData[$key]["priceName"] = $priceName;
                    $ResponseData[$key]['user_percent'] = $user_percent;
                    $key++;
                }
                mysqli_free_result($goodsInfo);
                mysqli_close($mysqli);
                Redis::setSearch($key_goods_list,json_encode($responseData));
                $key_uni = 'shop_id';
//                $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
                $ResponseData = self::assoc_unique($ResponseData, $key_uni);
                return $ResponseData;
            }
        }
        $goodsList = !empty($data["list"]) ? $data["list"] : array();
        if(empty($goodsList)){
            return array();
        }
        //整理数据
        $ResponseData = self::ArrangementParam($goodsList);
        Redis::setSearch($key_goods_list,json_encode($ResponseData));
        if(!empty($ResponseData) && count($ResponseData)){
            foreach ($ResponseData as $k=>$item){

                if(!isset($item['user_percent'])){
                    $ResponseData[$k]['user_percent'] = $user_percent;
                }
                if($item['goods_id']=='1207624000'){
                    unset($ResponseData[$k]);
                }
            }
            sort($ResponseData);
        }
        $key = 'goods_id';
        $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
        $ResponseData = self::assoc_unique($ResponseData, $key);
        return $ResponseData;
    }
    //查询商品列表测试----不区分平台
    public static function getGoodsListTest($paramData = array()){
        exit(self::get_token());
        $RequestData = array();
        //根据标签id搜索
        if(isset($paramData["opt_id"]) && $paramData["opt_id"] !='')  $RequestData["cid3"] = $paramData["opt_id"];
        //page默认1
        if(isset($paramData["page"])) $RequestData["pageIndex"] = $paramData["page"];
        //page_size 默认100
        if(isset($paramData["page_size"]))   $RequestData["pageSize"] = $paramData["page_size"];

        $user_percent = self::getUserPercent();
        //读取缓存
        $key_goods_list = md5(json_encode($paramData));
        $ResponseData = Redis::getSearch($key_goods_list);
//        $ResponseData['token'] = self::get_token();
        if(!empty($ResponseData) && $RequestData["cid3"]!='0_22'){
            $ResponseData = json_decode($ResponseData,true);
            foreach ($ResponseData as $k=>$item){
                if(!isset($item['user_percent'])){
                    $ResponseData[$k]['user_percent'] = $user_percent;
                }
                if($item['goods_id']=='1207624000'){
                    unset($ResponseData[$k]);
                }
            }
            sort($ResponseData);
            $key = 'goods_id';
            $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
            $ResponseData = self::assoc_unique($ResponseData, $key);
            if (count($RequestData)){
                return $ResponseData;
            }
        }
        if($RequestData["cid3"]!='0_22'){
            $data = self::http_get('https://open.qqbuy.com/api/goods/queryGoodsForApi', $RequestData);
            $data = json_decode($data,true);
        }
        $page_size = $RequestData["pageSize"];
        $start = ($RequestData["pageIndex"]-1)*$page_size;
        if($RequestData["cid3"]=='0_22' || $data["code"]!=200){
            $sql = "select distinct bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where cate_id_no='".$RequestData["cid3"]."' AND source_id=1 AND price<10 and commission_share>=20 order by order_count_30days desc,id desc limit {$start}, {$page_size}";
            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
            if($mysqli){
                $result = mysqli_query($mysqli, $sql);
                $key = 0;
                $responseData = array();
                $ResponseData = array();
                while ($goodsInfo = mysqli_fetch_assoc($result)){
                    $responseData[$key]["goods_id"] = $ResponseData[$key]["goods_id"] = isset($goodsInfo["bar_code"]) ? $goodsInfo["bar_code"] : 0;
                    $responseData[$key]["shop_id"] = $ResponseData[$key]["shop_id"] = isset($goodsInfo["shop_id"]) ? $goodsInfo["shop_id"] : 0;
                    $responseData[$key]["goods_name"] = $ResponseData[$key]["goods_name"] = isset($goodsInfo["store_name"]) ? $goodsInfo["store_name"] : "";
                    $responseData[$key]["goods_thumbnail_url"] = $ResponseData[$key]["goods_thumbnail_url"] = isset($goodsInfo["image"]) ? $goodsInfo["image"] : "";

                    //优惠券金额
                    $coupon_discount = isset($goodsInfo["coupon_discount"]) ? $goodsInfo["coupon_discount"] : 0;
                    $responseData[$key]["coupon_discount"] = $ResponseData[$key]["coupon_discount"] = $coupon_discount;

                    //价格
                    $min_group_price = isset($goodsInfo["ot_price"]) ? $goodsInfo["ot_price"] : 0;
                    $responseData[$key]["min_group_price"] = $ResponseData[$key]["min_group_price"] = $min_group_price;

                    //京东佣金比例 百分比
                    $promotion_rate = bcdiv($goodsInfo["commission_share"],100,2);
                    $responseData[$key]["promotion_rate"] = $ResponseData[$key]["promotion_rate"] = $promotion_rate;
                    //京东佣金
                    //单价  减去 优惠券
                    $responseData[$key]["present_price"] = $ResponseData[$key]["present_price"] = isset($goodsInfo["price"]) ? $goodsInfo["price"] : 0;
                    $responseData[$key]["isCoupon"] = $ResponseData[$key]["isCoupon"] = isset($goodsInfo["is_coupon"]) ? $goodsInfo["is_coupon"] : 0;
                    $responseData[$key]["isPg"] = $ResponseData[$key]["isPg"] = isset($goodsInfo["is_pg"]) ? $goodsInfo["is_pg"] : 0;
                    $responseData[$key]["discountPrice"] = $ResponseData[$key]["discountPrice"] = isset($goodsInfo["price"]) ? $goodsInfo["price"] : 0;
                    $responseData[$key]["pingouPrice"] = $ResponseData[$key]["pingouPrice"] = isset($goodsInfo["pingou_price"]) ? $goodsInfo["pingou_price"] : 0;
                    $commission = isset($goodsInfo["commission"]) ? $goodsInfo["commission"] : 0;
//            $ResponseData[$key]["return_cash"] = bcmul($commission,0.1, 2);
                    $responseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash"] = $commission;
                    $responseData[$key]["comments"] = $ResponseData[$key]["comments"] = isset($goodsInfo["comments"]) ? $goodsInfo["comments"] : 0;
                    $responseData[$key]["goodCommentsShare"] = $ResponseData[$key]["goodCommentsShare"] = isset($goodsInfo["goods_comments_share"]) ? $goodsInfo["goods_comments_share"] : 0;
                    $responseData[$key]["orderCount30days"] = $ResponseData[$key]["orderCount30days"] = isset($goodsInfo["order_count_30days"]) ? $goodsInfo["order_count_30days"] : 0;



                    if ($ResponseData[$key]["isPg"]){
                        if($ResponseData[$key]["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '拼购价';
                        }
                    }else{
                        if($ResponseData[$key]["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '超低价';
                        }
                    }
                    $responseData[$key]["priceName"] = $ResponseData[$key]["priceName"] = $priceName;
                    $ResponseData[$key]['user_percent'] = $user_percent;
                    $key++;
                }
                mysqli_free_result($goodsInfo);
                mysqli_close($mysqli);
                Redis::setSearch($key_goods_list,json_encode($responseData));
                $key_uni = 'shop_id';
//                $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
                $ResponseData = self::assoc_unique($ResponseData, $key_uni);
                return $ResponseData;
            }
        }
        $goodsList = !empty($data["list"]) ? $data["list"] : array();
        if(empty($goodsList)){
            return array();
        }
        //整理数据
        $ResponseData = self::ArrangementParam($goodsList);
        Redis::setSearch($key_goods_list,json_encode($ResponseData));
        if(!empty($ResponseData) && count($ResponseData)){
            foreach ($ResponseData as $k=>$item){

                if(!isset($item['user_percent'])){
                    $ResponseData[$k]['user_percent'] = $user_percent;
                }
                if($item['goods_id']=='1207624000'){
                    unset($ResponseData[$k]);
                }
            }
            sort($ResponseData);
        }
        $key = 'goods_id';
        $ResponseData = self::distinct($ResponseData, $RequestData["pageIndex"], $RequestData["cid3"], $RequestData["pageSize"]);
        $ResponseData = self::assoc_unique($ResponseData, $key);
        return $ResponseData;
    }
    public static function distinct($goods_arr, $page, $opt_id, $page_size){
        if($page<=1){
            return $goods_arr;
        }else{
            foreach ($goods_arr as $k=>$item){
                for($i=1;$i<$page;$i++){
                    $paramData["opt_id"] = $opt_id;
                    $paramData["page"] = $i;
                    $paramData["page_size"] = $page_size;
                    $key = md5(json_encode($paramData));
                    $responseData = Redis::getSearch($key);
                    if(!empty($responseData)){
                        $responseData = json_decode($responseData,true);
                        $goods_ids = array_column($responseData, 'goods_id');
//                        var_dump(in_array($item['goods_id'], $goods_ids));
                        if (in_array($item['goods_id'], $goods_ids)){
//                            echo $item['goods_id'].PHP_EOL;
                            unset($goods_arr[$k]);
                        }
                    }
                }
            }
            sort($goods_arr);
            return $goods_arr;
        }

    }

    //查询商品列表----不区分平台
    public static function getGoodsListByKeyword($paramData = array()){
        $RequestData = array();
        $RequestData["apikey"] = self::$apikey;

        //根据关键词搜索
        if(!empty($paramData["keyword"])){
            $keyword = $paramData["keyword"];
            if(strpos($paramData["keyword"],'https')){
                $kw = preg_match_all("/(?:【)(.*)(?:】)/i",$paramData["keyword"], $result);
                if(!empty($kw[1][0])){
                    $keyword = $kw[1][0];
                }
            }
            $RequestData["keyword"] = $keyword;
        }

        //page默认1
        if(isset($paramData["page"])) $RequestData["pageIndex"] = $paramData["page"];

        //page_size 默认100
        if(isset($paramData["page_size"]))   $RequestData["pageSize"] = $paramData["page_size"];



        //排序字段
        if(isset($paramData["sortName"]))   $RequestData["sortName"] = $paramData["sortName"];
        //升降序
        if(isset($paramData["sort"]))   $RequestData["sort"] = $paramData["sort"];

        //过滤标签
//        if(isset($paramData["skuTags"]))   $RequestData["skuTags"] = $paramData["skuTags"];

        //读取缓存
        $key = md5(json_encode($RequestData));
        $ResponseData = Redis::getSearch($key);
        $user_percent = self::getUserPercent();
//        if(!empty($ResponseData)){
//            $ResponseData = json_decode($ResponseData,true);
//            foreach ($ResponseData as $k=>$item){
//                if(!isset($item['user_percent'])){
//                    $ResponseData[$k]['user_percent'] = $user_percent;
//                }
//            }
//            $key = 'goods_id';
//            $ResponseData = self::assoc_unique($ResponseData, $key);
//            return $ResponseData;
//        }

        $rdata = self::http_get(self::$ddxUrl.'/jd/query_goods',$RequestData);
        $data = json_decode($rdata,true);
        if($data["code"] != 200){
            Log::write($data,'ERROR');
            return array();
        }
        $goodsList = !empty($data["data"]) ? $data["data"] : array();
        if(empty($goodsList)){
            return array();
        }
        //整理数据
        $ResponseData = self::ArrangementParam($goodsList);
        Redis::setSearch($key,json_encode($ResponseData));
        if(!empty($ResponseData) && count($ResponseData)){
            foreach ($ResponseData as $k=>$item){
                if(!isset($item['user_percent'])){
                    $ResponseData[$k]['user_percent'] = $user_percent;
                }
            }
        }
        $key = 'goods_id';
        $ResponseData = self::assoc_unique($ResponseData, $key);
        return $ResponseData;
    }

    public static function assoc_unique($arr, $key) {
        $tmp_arr = array();
        $back_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
                $back_arr[] = $v;
            }
        }
//        sort($arr); //sort函数对数组进行排序
//        return $arr;
        return $back_arr;
    }

    //查询商品列表----不区分平台
    public static function getGoodsListImport($paramData = array()){
        $cates = ['0_200','0_1','0_2','0_10','0_25','0_27','0_26','0_22'];
        $catesName = ['0_200'=>'热卖','0_1'=>'精选','0_2'=>'大咖推荐','0_10'=>'9.9专区','0_25'=>'生活超市','0_27'=>'居家日用','0_26'=>'母婴','0_22'=>'爆品'];

          $curCateNum = Redis::getCurNum();
        var_dump($curCateNum);
        $curCateNum= $curCateNum ? : 1;
          $curCateIdx = $curCateNum;
          $curCateIdx %= 8;

          $cid = $cates[$curCateIdx];
          $idx = Redis::getCurIdx($cid);
          var_dump($idx);
          $sidx =  $idx ? : 1;
          $sidx %= 10;
          $sidx +=1;
          $start = ($sidx-1)*30+1;
          $end = ($sidx-1)*30+30;

//        foreach ($cates as $cid){
            for ($page=$start;$page<=$end;$page++){
//            //page默认1
//            if(isset($paramData["page"])) $RequestData["pageIndex"] = $page;
//            //page_size 默认100
//            if(isset($paramData["page_size"]))   $RequestData["pageSize"] = 50;
                $RequestData["pageIndex"] = $page;
                $RequestData["pageSize"] = 50;
                $RequestData["cid3"] =  $cid;
                $rdata = self::http_get(self::$url.'/goods/queryGoodsForApi',$RequestData);
                $data = json_decode($rdata,true);
//        var_dump($data);
                if($data["code"] != 200){
                    Log::write($data,'ERROR');
                    return array();
                }
                $goodsList = !empty($data["list"]) ? $data["list"] : array();
                if(empty($goodsList)){
                    Redis::setCurIdx($cates[$curCateIdx], $idx+1);
                    Redis::setCurNum($curCateNum+1);
                    return array();
                }
//        var_dump($goodsList);
                //imginfo
                $sql = "REPLACE INTO `fxk_store_product` (`source_id`, `bar_code`, `store_name`, `brand_name`, `image`, `imginfo`,`cate_id`, `cate_id_no`,`keyword`, `ot_price`, `price`, `pingou_price`, `coupon_discount`, `commission_share`, `commission`, `is_pg`, `is_coupon`, `order_count_30days`, `comments`, `goods_comments_share`, `is_show`,`add_time`,`shop_id`)
VALUES";
                $add_time = time();
                if (count($goodsList)){
                    foreach ($goodsList as $item){
                        $source_id = 1;
                        $bar_code = $item['skuId'];
                        $store_name = $item['skuName'];
                        $brand_name = $item['brandName'];
                        $shop_id = $item['shopId'];

                        $image= $item['imgUrl'];
                        $imageinfo= $item['imageInfo'];
                        $cate_id = $catesName[$cid];
                        $cate_id_no = $cid;
                        $keyword= '';
                        $ot_price= $item['price'];
                        $price= $item['discountPrice'];
                        $pingouPrice= $item['pingouPrice'];


                        $coupon_discount= $item['discount'];
                        $commission_share = $item['commissionShare'];
                        $commission = $item['commission'];
                        $is_pg= $item['isPg'];
                        $is_coupon = $item['isCoupon'];
                        $order_count_30days = $item['orderCount30days'];
                        $comments = $item['comments'];
                        $goods_comments_share = $item['goodCommentsShare'];
                        $is_show = 1;
                        $sql .= "({$source_id},'".$bar_code."', '".$store_name."','".$brand_name."', '".$image."', '".$imageinfo."','".$cate_id."', '".$cate_id_no."', '".$keyword."', {$ot_price}, {$price},{$pingouPrice}, {$coupon_discount}, {$commission_share}, {$commission}, {$is_pg}, {$is_coupon}, {$order_count_30days}, {$comments}, {$goods_comments_share}, {$is_show}, {$add_time}, {$shop_id}),";
                    }
                    $len = strlen($sql);
                    $sql = substr($sql, 0, $len-1);
//                    echo $sql;
                    $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
//        $mysqli = mysqli_connect("127.0.0.1","root","","test");
                    if (!$mysqli) {
                        echo "mysql_contect_error " . mysqli_connect_error()."\n";
                        exit();
                    }
//                    echo $sql.PHP_EOL;
                    $res = $mysqli->query($sql);
                    mysqli_close($mysqli);
                    Redis::setCurIdx($cates[$curCateIdx], $idx+1);
                    Redis::setCurNum($curCateNum+1);
//                    echo 'idx:'.$idx.'done'.PHP_EOL;
                }
            }
//        }

    }
//    //热销榜单商品列表
//    public static function sellWellGoodsList($sort_type=1,$offset=0,$limit=30,$responseType){
//
//        $key = $sort_type."_".$offset."_".$limit.'_'.$responseType;
//        //查询缓存
//        $ResponseData = Redis::getSellWellGoodsList($key);
//        if(!empty($ResponseData)){
//            $ResponseData = json_decode($ResponseData,true);
//            foreach ($ResponseData as $key => $value){
//                if($ResponseData[$key]['coupon_discount'] == 0){
//                    unset($ResponseData[$key]);
//                }
//                $list1 = $value;
//            }
//            $ResponseData = array_values($ResponseData);
//            if($responseType ==1){
//                foreach ($list1 as $k => $val){
//                    if($list1[$k]['coupon_discount'] == 0){
//                        unset($list1[$k]);
//                    }
//                }
//                $list1 = array_values($list1);
//                $ResponseData["img"] ='Public/Product/img/sellWell.png';
//                $ResponseData["name"] ="夏家店清凉驾到，低至5.9元起";
//                $ResponseData["list"] = $list1;
//            }
//            return $ResponseData;
//        }
//        //取 收藏榜单数据
//        if($sort_type == 3){
//            $userCollectionModel = new UserCollectionStatisticsModel();
//            $data = $userCollectionModel->getList($offset,$limit);
//            if(!empty($data)){
//                $goodsIdList = array_column($data,"goods_id");
//                $list = self::getGoodsList(["goods_id_list"=>$goodsIdList]);
//                goto Response;
//            }else{
//                goto to;
//            }
//        }
//        to:
//        $http = DI::get("http");
//        $param = array(
//            'sort_type'=> $sort_type,
//            'offset'=>$offset,
//            'limit'=>$limit
//        );
//        $data = self::base_para(self::$action["sell_well"],$param);
//        $data = $http->post(self::$url,$data);
//        if($data["status"] != 0 ){
//            Log::write(json_encode($data),'HTTP_ERROR_PDD');
//            Exception::throwException(Exception::HTTP_ERROR);
//        }
//        $data = json_decode($data["data"],true);
//        $data = !empty($data["top_goods_list_get_response"]["list"]) ? $data["top_goods_list_get_response"]["list"] : array();
//        $list = self::ArrangementParam($data,1);
//
//        Response:
//        if($responseType ==1){
//            foreach ($list as $key1 => $value1){
//                if($list[$key1]['coupon_discount'] == 0){
//                    unset($list[$key1]);
//                }
//            }
//            $list = array_values($list);
//            $ResponseData["img"] ='Public/Product/img/sellWell.png';
//            $ResponseData["name"] ="夏家店清凉驾到，低至5.9元起";
//            $ResponseData["list"] = $list;
//        }else{
//            foreach ($list as $key1 => $value1){
//                if($list[$key1]['coupon_discount'] == 0){
//                    unset($list[$key1]);
//                }
//            }
//            $list = array_values($list);
//            $ResponseData = $list;
//        }
//        //缓存榜单数据
//        Redis::setSellWellGoodsList($key,json_encode($ResponseData));
//        return $ResponseData;
//    }

    //获取佣金率及秒杀商品
    public static function getCommissionRateAndSeckillGoodsList(){

        $key = md5(json_encode(self::$seckillTypeList));
        $config = Redis::getSeckillConfig($key);
        if(empty($config)){
            $where = self::$seckillTypeList;
            //后台设置返利比率
            $ConfigModel = new ConfigModel();
            $config = $ConfigModel->getValueByName($where);
            self::$seckillValue = $config;
            //设置缓存
            Redis::setSeckillConfig($key,json_encode($config));
        }else{
            $config = json_decode($config,true);
        }

        $config = array_column($config,NULL,"name");
        if(empty($config["RETURN_COMMISSION_RATE"]) || empty($config["SECKILL_COMMISSION_RATE"])){
            ApiException::throwException(ApiException::COMMISSION_ERROR);
        }

        //整理所有秒杀商品
        $seckillGoodsList = [];
        foreach ($config as $key=>$val){
            if(in_array($key,self::$seckillTypeList)){
                $goodsId = explode(',',$val["value"]);
                foreach ($goodsId as $v){
                    array_push($seckillGoodsList,$v);
                }
            }
        }
        $config["goods_id_list"] = $seckillGoodsList;
        return $config;
    }

    //统一处理列表数据
    public static function ArrangementParam($goodsList,$otherList= 1){
        $ResponseData = array();
        $ext = [
            "52828027298",
            "63915247163",
            "1974258248",
            "65265695083",
            "13485128555",
            "25528177792",
            "61544649036",
            "100000100725",
            "66618445017",
            "67357125480",
            "67807570244",
            "31947482099",
            "47861443831",
            "50704836359",
            "10777221157",
            "63903330964",
            "10749976331",
            "29815324069",
            "40722731944",
            "10777776437",
            "10787600023",
            "22619871043",
            "10777868876",
            "40084407118",
            "10785413374",
            "57223673913",
        ];
        foreach($goodsList as $key=>$val){
//            if(strpos($val["skuName"],'淫羊藿') !== false || strpos($val["skuName"],'补肾') !== false|| strpos($val["skuName"],'避孕') !== false|| strpos($val["skuName"],'伟哥') !== false|| strpos($val["skuName"],'性冷淡') !== false|| strpos($val["skuName"],'震动棒') !== false|| strpos($val["skuName"],'助勃') !== false|| strpos($val["skuName"],'延时喷剂') !== false|| strpos($val["skuName"],'飞机杯') !== false || strpos($val["skuName"],'情趣') !== false || strpos($val["skuName"],'自慰') !== false || strpos($val["skuName"],'房事') !== false || strpos($val["skuName"],'润滑') !== false || strpos($val["skuName"],'男用') !== false || strpos($val["skuName"],'女用') !== false || strpos($val["skuName"],'挑逗') !== false || strpos($val["skuName"],'阴茎') !== false || strpos($val["skuName"],'露毛') !== false){
//                continue;
//            }
//            if(in_array($val["skuId"], $ext)){
//                continue;
//            }
            $ResponseData[$key]["goods_id"] = isset($val["skuId"]) ? $val["skuId"] : 0;
            $ResponseData[$key]["goods_name"] = isset($val["skuName"]) ? $val["skuName"] : "";
            $ResponseData[$key]["goods_thumbnail_url"] = isset($val["imageInfo"]["imageList"][0]["url"]) ? $val["imageInfo"]["imageList"][0]["url"] : "";

            //优惠券金额
            $coupon_discount = isset($val["couponInfo"]["couponList"]["0"]["discount"]) ? $val["couponInfo"]["couponList"]["0"]["discount"] : 0;
            $ResponseData[$key]["coupon_discount"] = $coupon_discount;

            //价格
            $min_group_price = isset($val["priceInfo"]["price"]) ? $val["priceInfo"]["price"] : 0;
            $ResponseData[$key]["min_group_price"] = $min_group_price;

            //京东佣金比例 百分比
            $promotion_rate = bcdiv($val["commissionInfo"]["commissionShare"],100,2);
            $ResponseData[$key]["promotion_rate"] = $promotion_rate;
            //京东佣金
            //单价  减去 优惠券
            $ResponseData[$key]["present_price"] = isset($val["pinGouInfo"]["pingouPrice"]) ? $val["pinGouInfo"]["pingouPrice"] : ($min_group_price-$coupon_discount);
            $ResponseData[$key]["isCoupon"] = isset($val["couponInfo"]["couponList"]) ? count($val["couponInfo"]["couponList"]) : 0;
            $ResponseData[$key]["isPg"] = isset($val["pinGouInfo"]["pingouPrice"]) ? ($val["pinGouInfo"]["pingouPrice"]>0) : 0;
            $ResponseData[$key]["discountPrice"] = isset($val["pinGouInfo"]["pingouPrice"]) ? $val["pinGouInfo"]["pingouPrice"] : ($min_group_price-$coupon_discount);
            $ResponseData[$key]["pingouPrice"] = isset($val["pinGouInfo"]["pingouPrice"]) ? $val["pinGouInfo"]["pingouPrice"] : 0;
//            $commission = isset($val["commissionInfo"]["commission"]) ? $val["commissionInfo"]["commission"] : 0;
//            $ResponseData[$key]["return_cash"] = bcmul($commission,0.1, 2);
            $commission =  bcmul($ResponseData[$key]["present_price"], $promotion_rate, 2);
            $ResponseData[$key]["return_cash_total"] = $ResponseData[$key]["return_cash"] = $commission;
            $ResponseData[$key]["comments"] = isset($val["comments"]) ? $val["comments"] : 0;
            $ResponseData[$key]["goodCommentsShare"] = isset($val["goodCommentsShare"]) ? $val["goodCommentsShare"] : 0;
            $ResponseData[$key]["orderCount30days"] = isset($val["inOrderCount30Days"]) ? $val["inOrderCount30Days"] : 0;



            if ($ResponseData[$key]["isPg"]){
                if($ResponseData[$key]["isCoupon"]){
                    $priceName = '券后价';
                }else{
                    $priceName = '拼购价';
                }
            }else{
                if($ResponseData[$key]["isCoupon"]){
                    $priceName = '券后价';
                }else{
                    $priceName = '超低价';
                }
            }
            $ResponseData[$key]["priceName"] = $priceName;



            //判断是否秒杀商品
            if($val["isSeckill"]){
                //秒杀商品
                $ResponseData[$key]["goods_type"] = 1;
            }else{
                //普通商品
                $ResponseData[$key]["goods_type"] = 0;
            }
        }
        return array_unique($ResponseData, SORT_REGULAR);
    }

    //首页---秒杀商品---递归读取未下架商品
    public static function getGoodsInfoByNum($num,$value,$form,$to){
        //判断是否都是下架商品id
        $count = Redis::getLowerShelfGoodsId();
        if(!empty($count) && $count >count($value)){
            //查询价格接近商品
            $paramData = [
                "sort_type"=>9, //券后价升序
                "page_size"=>10,
                "range_list"=>json_encode([
                    [
                        "range_id"=>1,
                        "range_from"=>$form,
                        "range_to"=>$to
                    ]
                ])
            ];
            $goodsInfo = self::getGoodsList($paramData);
            if(empty($goodsInfo)){
                return [];
            }else{
                return $goodsInfo[0];
            }
        }
        //循环取商品id--取余
        $k = bcmod($num,count($value));
        if($k==0){
            $goodsId = end($value);
        }else{
            $goodsId = $value[$k-1];
        }
        //查询商品详情页信息
        $goodsInfo = self::getProductInfo(["goods_id"=>$goodsId],1);
        if(empty($goodsInfo)){
            //存储五分钟内下架商品id
            Redis::setLowerShelfGoodsId($goodsId);
            $goodsInfo = self::getGoodsInfoByNum($num +1,$value);
        }
        return $goodsInfo;
    }
//    //首页---秒杀商品
//    public static function getSeckillInfo($data){
//        $num = isset($data["num"]) ? $data["num"] : 1;
//        //查询缓存
//        $ResponseData = Redis::getSpecialOfferSeckill($num);
//        if(!empty($ResponseData)){
//            return json_decode($ResponseData,true);
//        }
//
//        //查询缓存---后台配置的秒杀商品id
//        $key = md5(json_encode(self::$seckillTypeList));
//        $config = Redis::getSeckillConfig($key);
//        if(empty($config)){
//            //读取后台设置秒杀商品id
//            $configModel = new ConfigModel();
//            $config = $configModel->getValueByName(self::$seckillTypeList);
//            Redis::setSeckillConfig($key,json_encode($config));
//        }else{
//            $config = json_decode($config,true);
//        }
//
//        self::$seckillValue = $config;
//        //拼多多商品id有的是string、float 无法直接作为数组key
//        foreach($config as $key=>$val){
//            if(in_array($val["name"],self::$seckillGoodsKey)){
//                $value = explode(',',$val["value"]);
//                if(empty($value)){
//                    Log::write("读取秒杀商品配置异常");
//                    Exception::throwException(Exception::HTTP_ERROR);
//                }
//                $form = self::$price_range[$val["form"]];
//                $to = self::$price_range[$val["to"]];
//                //循环取商品id--取余
//                $goodsInfo = self::getGoodsInfoByNum($num,$value,$form,$to);
//                $configs[$key]["goodsId"] = $goodsInfo["goods_id"];
//                $configs[$key]["type"] = $val["name"];
//                $configs[$key]["info"] = $goodsInfo;
//                if($val["name"] == "ONE_SECKILL"){
//                    $oneSeckill[] = $configs[$key];
//                }elseif($val["name"] == "TEN_SECKILL"){
//                    $tenSeckill[] = $configs[$key];
//                }else{
//                    $clearancePrice[] = $configs[$key];
//                }
//            }
//        }
//        $ResponseData = array_merge($oneSeckill,$tenSeckill,$clearancePrice);
//        Redis::setSpecialOfferSeckill($num,json_encode($ResponseData));
//        return $ResponseData;
//    }

//    //秒杀商品列表页
//    public static function getSeckillList($type,$page,$page_size){
//        $ResponseData = [];
//        //查询缓存
//        $key = $type.'_'.$page.'_'.$page_size;
//        $ResponseData = Redis::getSeckillList($key);
//        if(!empty($ResponseData)){
//            return json_decode($ResponseData,true);
//        }
//        //banner
//        $bannerList = [
//            "CLEARANCE_PRICE"=>'Public/Product/img/CLEARANCE_PRICE.png',
//            "TEN_SECKILL"=>'Public/Product/img/TEN_SECKILL.png',
//            "ONE_SECKILL"=>'Public/Product/img/ONE_SECKILL.png'
//        ];
//        $ResponseData["img"] = $bannerList[$type];
//        $ResponseData["list"] = [];
//
//        //查询后台设置秒杀的商品
//        $configModel = new ConfigModel();
//        $data = $configModel->getValueByName([$type]);
//        if(empty($data)){
//            return $ResponseData;
//        }
//        $goodsId = explode(',',$data[0]["value"]);
//        $goodsIdList = array_slice($goodsId,$page,$page_size);
//        if(empty($goodsIdList)){
//            return $ResponseData;
//        }
//
//        //获取商品列表
//        $ResponseData["list"] = self::getGoodsList(["goods_id_list"=>$goodsIdList]);
//
//        //设置缓存
//        Redis::setSeckillList($key,json_encode($ResponseData));
//        return $ResponseData;
//    }

    //请求数据处理
    private static function base_para($type,$params = array()){
        $para = array(
            'type'      =>$type,
            'client_id' =>self::$pdd_config[$GLOBALS["platform"]]["client_id"],
            'timestamp' =>time(),
            'data_type' =>'JSON',
            'version'   =>'V1',
            //'access_token'=>$token['access_token']
        );
        $para = array_merge($para,$params);
        $para['sign'] = self::sign($para);
        return $para;
    }



    /**
     * @param $para //签名的数组
     * @return string
     */
    private static function sign($para){
        ksort($para);								                   //按字母升序重新排序
        $sequence = '';									                   //定义签名数列
        foreach($para as $k=>$v){		                   //拼接参数
            $sequence .= "{$k}{$v}";
        }
        $sequence = self::$pdd_config[$GLOBALS["platform"]]["client_secret"].$sequence.self::$pdd_config[$GLOBALS["platform"]]["client_secret"];//拼接密钥
        $sequence = strtoupper(md5($sequence));
        return $sequence;
    }

    private static function http_get($url, array $data = array(), $is_post=0)
    {
        $headers[] = "token: ".self::get_token();
        if(!$is_post){
            if(strpos($url, '?') === false){
                $url .= '?'.http_build_query($data);
            }else{
                $url .= '&'.http_build_query($data);
            }
        }

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if($is_post){
            //声明使用POST方式来进行发送
            curl_setopt($curl, CURLOPT_POST, 1);
            //发送什么数据呢
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = "Content-Type: application/json";
            $headers[] = 'Content-Length: '.strlen(json_encode($data));
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $tmpInfo = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;
    }

    private static function get_token()
    {
        $token = Redis::getQqbuyToken();
        if(!$token){
            $RequestTokenData = array("appKey"=>"JyPDsz3D","appSecret"=>"7b1daa2cae5ba5b02af9611509790ce8");
            $tokenData = self::http_get_notoken(self::$url.'/auth/getAccessTokenForApi',$RequestTokenData);
            $rtokenData = json_decode($tokenData,true);
            $token = $rtokenData["token"];
            Redis::setQqbuyToken($token);
        }
        return $token;
    }

    private static function http_get_notoken($url, array $data = array())
    {
        if(strpos($url, '?') === false){
            $url .= '?'.http_build_query($data);
        }else{
            $url .= '&'.http_build_query($data);
        }
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;
    }

    /**
     * 获取接口数据
     * @param string $api_url
     * @param array $data
     * @return array|mixed
     */
    public function getApiData($api_url = '', $data = []) {

        if (false === strpos($api_url, self::$url)) {
            $api_url = self::$url . $api_url;
        }

        $result = [];
        if ($api_url) {
            $res = self::http_get($api_url, $data);
            $result = json_decode($res, true);
        }
        return $result;
    }

    //查询商品列表----不区分平台
    public static function getGoodsListInit($paramData = array()){
        $cates = ['0_200','0_1','0_2','0_10','0_25','0_27','0_26','0_22'];
        $catesName = ['0_200'=>'热卖','0_1'=>'精选','0_2'=>'大咖推荐','0_10'=>'9.9专区','0_25'=>'生活超市','0_27'=>'居家日用','0_26'=>'母婴','0_22'=>'爆品'];

        $curCateNum = Redis::getCurNumHot();
        $curCateNum= $curCateNum ? : 1;
        $curCateIdx = $curCateNum;
        $curCateIdx %= 8;

        $cid = $cates[$curCateIdx];
        $idx = Redis::getCurIdxHot($cid);
        $sidx =  $idx ? : 1;
        $sidx %= 10;
        $sidx +=1;
        $start = ($sidx-1)*20+1;
        $end = ($sidx-1)*20+20;

//        foreach ($cates as $cid){
        for ($page=$start;$page<=$end;$page++){
//            //page默认1
//            if(isset($paramData["page"])) $RequestData["pageIndex"] = $page;
//            //page_size 默认100
//            if(isset($paramData["page_size"]))   $RequestData["pageSize"] = 50;
            $RequestData["pageIndex"] = $page;
            $RequestData["pageSize"] = 50;
            $RequestData["cid3"] =  $cid;
            $rdata = self::http_get(self::$url.'/goods/queryGoodsForApi',$RequestData);
            $data = json_decode($rdata,true);
//        var_dump($data);
            if($data["code"] != 200){
                Log::write($data,'ERROR');
                return array();
            }
            $goodsList = !empty($data["list"]) ? $data["list"] : array();
            if(empty($goodsList)){
                Redis::setCurIdxHot($cates[$curCateIdx], $idx+1);
                Redis::setCurNumHot($curCateNum+1);
                return array();
            }
//        var_dump($goodsList);
            //imginfo
//            $sql = "REPLACE INTO `fxk_store_product` (`source_id`, `bar_code`, `store_name`, `brand_name`, `image`, `imginfo`,`cate_id`, `cate_id_no`,`keyword`, `ot_price`, `price`, `pingou_price`, `coupon_discount`, `commission_share`, `commission`, `is_pg`, `is_coupon`, `order_count_30days`, `comments`, `goods_comments_share`, `is_show`,`add_time`,`shop_id`)
//VALUES";
//            $add_time = time();
            if (count($goodsList)){
                foreach ($goodsList as $item){
                    $key = $item['skuId'].'_0_jd';
//                    $source_id = 1;
//                    $bar_code = $item['skuId'];
//                    $store_name = $item['skuName'];
//                    $brand_name = $item['brandName'];
//                    $shop_id = $item['shopId'];
//
//                    $image= $item['imgUrl'];
//                    $imageinfo= $item['imageInfo'];
//                    $cate_id = $catesName[$cid];
//                    $cate_id_no = $cid;
//                    $keyword= '';
//                    $ot_price= $item['price'];
//                    $price= $item['discountPrice'];
//                    $pingouPrice= $item['pingouPrice'];
//
//
//                    $coupon_discount= $item['discount'];
//                    $commission_share = $item['commissionShare'];
//                    $commission = $item['commission'];
//                    $is_pg= $item['isPg'];
//                    $is_coupon = $item['isCoupon'];
//                    $order_count_30days = $item['orderCount30days'];
//                    $comments = $item['comments'];
//                    $goods_comments_share = $item['goodCommentsShare'];

                    //店铺名称
                    $ResponseData["mall_name"] = $item['brandName'];
                    //店铺id
                    $ResponseData["mall_id"] = $item['shopId'];
//        //商品标签名称
//        $ResponseData["opt_name"] = $goodsInfo["opt_name"];
                    //轮播图
                    $ResponseData["goods_gallery_urls"] = empty(json_decode($item['imageInfo'], true)) ? [] :json_decode($item['imageInfo'], true) ;
//                    $ResponseData["play_url"] = $goodsInfo["videoList"][0]["playUrl"] ? : '';

                    //商品名称
                    $ResponseData["goods_name"] = $item['skuName'];
                    //商品id
                    $ResponseData["goods_id"] = $item['skuId'];
                    //返佣百分比
                    $promotion_rate = bcdiv($item['commissionShare'],100,2);
                    $ResponseData["promotion_rate"] = $promotion_rate;
                    //最小单买价
                    $min_group_price = $item['price'];
                    $ResponseData["min_group_price"] = $min_group_price;
                    //优惠券价格
                    $coupon_discount = $item['discount'];
                    $ResponseData["coupon_discount"] = $coupon_discount;
                    $ResponseData["return_cash_total"] = $ResponseData["return_cash"] = $item['commission'];
                    $ResponseData["isCoupon"] = $item['isCoupon'];
                    $ResponseData["isPg"] = $item['isPg'];
                    $ResponseData["pingouPrice"] = $item['pingouPrice'];
                    $ResponseData["discountPrice"] = $item["discountPrice"];
                    $ResponseData["comments"] = $item["comments"];
                    $ResponseData["goodCommentsShare"] = $item["goodCommentsShare"].'%';
                    $ResponseData["orderCount30days"] = $item["orderCount30days"];
                    $ResponseData["goods_category"] = $cid;
                    if ($ResponseData["isPg"]){
                        if($ResponseData["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '拼购价';
                        }
                    }else{
                        if($ResponseData["isCoupon"]){
                            $priceName = '券后价';
                        }else{
                            $priceName = '超低价';
                        }
                    }
                    $ResponseData["priceName"] = $priceName;
                    if ($ResponseData["goods_id"]){
                        //设置缓存
                        Redis::setProductInfo($key,json_encode($ResponseData));
                    }

//                    echo $key.'-'.date('Y-m-d H:i:s',time()).PHP_EOL;
//                    die();

//                    $is_show = 1;
//                    $sql .= "({$source_id},'".$bar_code."', '".$store_name."','".$brand_name."', '".$image."', '".$imageinfo."','".$cate_id."', '".$cate_id_no."', '".$keyword."', {$ot_price}, {$price},{$pingouPrice}, {$coupon_discount}, {$commission_share}, {$commission}, {$is_pg}, {$is_coupon}, {$order_count_30days}, {$comments}, {$goods_comments_share}, {$is_show}, {$add_time}, {$shop_id}),";
                }
//                $len = strlen($sql);
//                $sql = substr($sql, 0, $len-1);
//                    echo $sql;
//                $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
////        $mysqli = mysqli_connect("127.0.0.1","root","","test");
//                if (!$mysqli) {
//                    echo "mysql_contect_error " . mysqli_connect_error()."\n";
//                    exit();
//                }
//                echo $sql.PHP_EOL;
//                $res = $mysqli->query($sql);
                Redis::setCurIdxHot($cates[$curCateIdx], $idx+1);
                Redis::setCurNumHot($curCateNum+1);

                echo 'idx:'.$idx.'done'.PHP_EOL;
            }
        }
//        }

    }

    public static function getRandUser()
    {
        $rtn = self::http_get('https://kandian.youth.cn/Wechat/Video/getUser');
        $rtn_arr = json_decode($rtn,true);
        if($rtn_arr['status']){
            return $rtn_arr['data'];
        }

    }

    /*
 * 商品详情
 * $data array
 * $type 默认0  1为秒杀需要获取轮播图
 */
    public static function getProductInfo($data,$type = 0){
        $goodsId = $data["goods_id"];
        //商品详情页缓存key
        $key = $goodsId.'_'.$type.'_jd';

        //统计PV
        Redis::setGoodsInfoPv($goodsId);
        //统计UV
        Redis::setGoodsInfoUv($goodsId);


        //查询缓存
        $goodsInfo = Redis::getProductInfo($key);
        $checkResponseData = $ResponseData = json_decode($goodsInfo,true);
        if(!empty($goodsInfo) && !is_null($ResponseData['goods_id']) && count($ResponseData['goods_gallery_urls'])){
            $ResponseData["user_percent"] = self::getUserPercent();
            if (count($checkResponseData)){
                return $ResponseData;
            }
        }else{
            $sql = "select bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where bar_code='".$goodsId."' and source_id=1 order by id desc limit 1";
            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
            if($mysqli){
                $result = mysqli_query($mysqli, $sql);
                $goodsInfo = mysqli_fetch_assoc($result);
                //店铺名称
                $ResponseData["mall_name"] = $goodsInfo["brand_name"];
                //店铺id
                $ResponseData["mall_id"] = $goodsInfo["shop_id"];
                //轮播图
                $ResponseData["goods_gallery_urls"] = empty(json_decode($goodsInfo["imginfo"], true)) ? [] :json_decode($goodsInfo["imginfo"], true) ;

                //商品名称
                $ResponseData["goods_name"] = $goodsInfo["store_name"];
                //商品id
                $ResponseData["goods_id"] = $goodsInfo["bar_code"];
                //返佣百分比
                $promotion_rate = bcdiv($goodsInfo["commission_share"],100,2);
                $ResponseData["promotion_rate"] = $promotion_rate;
                //最小单买价
                $min_group_price = $goodsInfo["ot_price"];
                $ResponseData["min_group_price"] = $min_group_price;
                //优惠券价格
                $coupon_discount = $goodsInfo["coupon_discount"];
                $ResponseData["coupon_discount"] = $coupon_discount;
                $ResponseData["return_cash_total"] = $ResponseData["return_cash"] = $goodsInfo["commission"];
                $ResponseData["isCoupon"] = $goodsInfo["is_coupon"];
                $ResponseData["isPg"] = $goodsInfo["is_pg"];
                $ResponseData["pingouPrice"] = $goodsInfo["pingou_price"];
                $ResponseData["discountPrice"] = $goodsInfo["price"];
                $ResponseData["comments"] = $goodsInfo["comments"];
                $ResponseData["goodCommentsShare"] = $goodsInfo["goods_comments_share"].'%';
                $ResponseData["orderCount30days"] = $goodsInfo["order_count_30days"];
                if ($ResponseData["isPg"]){
                    if($ResponseData["isCoupon"]){
                        $priceName = '券后价';
                    }else{
                        $priceName = '拼购价';
                    }
                }else{
                    if($ResponseData["isCoupon"]){
                        $priceName = '券后价';
                    }else{
                        $priceName = '超低价';
                    }
                }
                $ResponseData["priceName"] = $priceName;
                mysqli_close($mysqli);
                $checkResponseData = $ResponseData;
                if(count($checkResponseData) && !is_null($goodsInfo["bar_code"])){
                    Redis::setProductInfo($key,json_encode($ResponseData));
                    $ResponseData["user_percent"] = self::getUserPercent();
                    return $ResponseData;
                }
            }
        }
        $param = array(
            'skuId'=>$goodsId
        );
//        $data = self::base_para(self::$action["goods_info"],$param);
        $data = self::http_get(self::$url.'/goods/getGoodsInfoForApi',$param);
        $data = json_decode($data,true);

        if(empty($data["goodsInfo"]) && $type == 0){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }else if(empty($data["goodsInfo"]) && $type == 1){
            return [];
        }
        $goodsInfo = $data["goodsInfo"];
        //店铺名称
        $ResponseData["mall_name"] = $goodsInfo["shopName"];
        //店铺id
        $ResponseData["mall_id"] = $goodsInfo["shopId"];
//        //商品标签名称
//        $ResponseData["opt_name"] = $goodsInfo["opt_name"];
        //轮播图
        $ResponseData["goods_gallery_urls"] = empty(json_decode($goodsInfo["imageInfo"], true)) ? [] :json_decode($goodsInfo["imageInfo"], true) ;
        $ResponseData["play_url"] = $goodsInfo["videoList"][0]["playUrl"] ? : '';

        //商品名称
        $ResponseData["goods_name"] = $goodsInfo["skuName"];
        //商品id
        $ResponseData["goods_id"] = $goodsInfo["skuId"];
        //返佣百分比
        $promotion_rate = bcdiv($goodsInfo["commissionShare"],100,2);
        $ResponseData["promotion_rate"] = $promotion_rate;
        //最小单买价
        $min_group_price = $goodsInfo["price"];
        $ResponseData["min_group_price"] = $min_group_price;
        //优惠券价格
        $coupon_discount = $goodsInfo["discount"];
        $ResponseData["coupon_discount"] = $coupon_discount;
        $ResponseData["return_cash_total"] = $ResponseData["return_cash"] = $goodsInfo["commission"];
        $ResponseData["isCoupon"] = $goodsInfo["isCoupon"];
        $ResponseData["isPg"] = $goodsInfo["isPg"];
        $ResponseData["pingouPrice"] = $goodsInfo["pingouPrice"];
        $ResponseData["discountPrice"] = $goodsInfo["discountPrice"];
        $ResponseData["comments"] = $goodsInfo["comments"];
        $ResponseData["goodCommentsShare"] = $goodsInfo["goodCommentsShare"].'%';
        $ResponseData["orderCount30days"] = $goodsInfo["orderCount30days"];
        if ($ResponseData["isPg"]){
            if($ResponseData["isCoupon"]){
                $priceName = '券后价';
            }else{
                $priceName = '拼购价';
            }
        }else{
            if($ResponseData["isCoupon"]){
                $priceName = '券后价';
            }else{
                $priceName = '超低价';
            }
        }
        $ResponseData["priceName"] = $priceName;
        //设置缓存
        Redis::setProductInfo($key,json_encode($ResponseData));

        $ResponseData["user_percent"] = self::getUserPercent();
        if ($ResponseData["goods_id"] && $ResponseData["goods_name"]){
            return $ResponseData;
        }else{
            return [];
        }
    }

    /*
* 商品详情
* $data array
* $type 默认0  1为秒杀需要获取轮播图
*/
    public static function getProduct($data,$type = 0){
        $param = array(
            'categoryId'=>0
        );
//        $data = self::base_para(self::$action["goods_info"],$param);
        $data = self::http_get(self::$url.'/goods/queryGoodsCategoryForApi',$param);
        $data = json_decode($data,true);
        if($data['categoryList']){
            foreach ($data['categoryList'] as $item){
                echo $item['name'].PHP_EOL;
                $param2 = array(
                    'categoryId'=>$item['categoryId']
                );
                $data2 = self::http_get(self::$url.'/goods/queryGoodsCategoryForApi',$param2);
                $data2 = json_decode($data2,true);
                if($data2['categoryList']){
                    foreach ($data2['categoryList'] as $item2){
                        echo '__'.$item2['name'].PHP_EOL;
//                        $param3 = array(
//                            'categoryId'=>$item2['categoryId']
//                        );
//                        $data3 = self::http_get(self::$url.'/goods/queryGoodsCategoryForApi',$param3);
//                        $data3 = json_decode($data3,true);
//                        if($data3['categoryList']){
//                            echo '____'.$item2['name'].PHP_EOL;
//                        }
                    }

                }
            }
        }
//        $goodsId = $data["goods_id"];
//        //商品详情页缓存key
//        $key = $goodsId.'_'.$type.'_jd';
//
//        //统计PV
//        Redis::setGoodsInfoPv($goodsId);
//        //统计UV
//        Redis::setGoodsInfoUv($goodsId);
//
//
//        //查询缓存
//        $goodsInfo = Redis::getProductInfo($key);
//        $checkResponseData = $ResponseData = json_decode($goodsInfo,true);
//        $ResponseData['token'] = self::get_token();
//        if(!empty($goodsInfo) && !is_null($ResponseData['goods_id']) && count($ResponseData['goods_gallery_urls'])){
//            $ResponseData["user_percent"] = self::getUserPercent();
//            if (count($checkResponseData)){
//                return $ResponseData;
//            }
//        }else{
//            $sql = "select bar_code,store_name,shop_id,brand_name,image,imginfo,cate_id_no,ot_price,price,pingou_price,coupon_discount,commission_share,commission,is_pg,is_coupon,order_count_30days,comments,goods_comments_share from fxk_store_product where bar_code='".$goodsId."' and source_id=1 order by id desc limit 1";
//            $mysqli = mysqli_connect("rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com","shop_fxk","RWEGRTEt3DFGrtHGJ5DFGwexF","shop_fxk");
//            if($mysqli){
//                $result = mysqli_query($mysqli, $sql);
//                $goodsInfo = mysqli_fetch_assoc($result);
//                //店铺名称
//                $ResponseData["mall_name"] = $goodsInfo["brand_name"];
//                //店铺id
//                $ResponseData["mall_id"] = $goodsInfo["shop_id"];
//                //轮播图
//                $ResponseData["goods_gallery_urls"] = empty(json_decode($goodsInfo["imginfo"], true)) ? [] :json_decode($goodsInfo["imginfo"], true) ;
//
//                //商品名称
//                $ResponseData["goods_name"] = $goodsInfo["store_name"];
//                //商品id
//                $ResponseData["goods_id"] = $goodsInfo["bar_code"];
//                //返佣百分比
//                $promotion_rate = bcdiv($goodsInfo["commission_share"],100,2);
//                $ResponseData["promotion_rate"] = $promotion_rate;
//                //最小单买价
//                $min_group_price = $goodsInfo["ot_price"];
//                $ResponseData["min_group_price"] = $min_group_price;
//                //优惠券价格
//                $coupon_discount = $goodsInfo["coupon_discount"];
//                $ResponseData["coupon_discount"] = $coupon_discount;
//                $ResponseData["return_cash_total"] = $ResponseData["return_cash"] = $goodsInfo["commission"];
//                $ResponseData["isCoupon"] = $goodsInfo["is_coupon"];
//                $ResponseData["isPg"] = $goodsInfo["is_pg"];
//                $ResponseData["pingouPrice"] = $goodsInfo["pingou_price"];
//                $ResponseData["discountPrice"] = $goodsInfo["price"];
//                $ResponseData["comments"] = $goodsInfo["comments"];
//                $ResponseData["goodCommentsShare"] = $goodsInfo["goods_comments_share"].'%';
//                $ResponseData["orderCount30days"] = $goodsInfo["order_count_30days"];
//                if ($ResponseData["isPg"]){
//                    if($ResponseData["isCoupon"]){
//                        $priceName = '券后价';
//                    }else{
//                        $priceName = '拼购价';
//                    }
//                }else{
//                    if($ResponseData["isCoupon"]){
//                        $priceName = '券后价';
//                    }else{
//                        $priceName = '超低价';
//                    }
//                }
//                $ResponseData["priceName"] = $priceName;
//                mysqli_close($mysqli);
//                $checkResponseData = $ResponseData;
//                if(count($checkResponseData) && !is_null($goodsInfo["bar_code"])){
//                    Redis::setProductInfo($key,json_encode($ResponseData));
//                    $ResponseData["user_percent"] = self::getUserPercent();
//                    return $ResponseData;
//                }
//            }
//        }
//        $param = array(
//            'skuId'=>$goodsId
//        );
////        $data = self::base_para(self::$action["goods_info"],$param);
//        $data = self::http_get(self::$url.'/goods/getGoodsInfoForApi',$param);
//        $data = json_decode($data,true);
//
//        if(empty($data["goodsInfo"]) && $type == 0){
//            Log::write(json_encode($data),'HTTP_ERROR_PDD');
//            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
//        }else if(empty($data["goodsInfo"]) && $type == 1){
//            return [];
//        }
//        $goodsInfo = $data["goodsInfo"];
//        //店铺名称
//        $ResponseData["mall_name"] = $goodsInfo["shopName"];
//        //店铺id
//        $ResponseData["mall_id"] = $goodsInfo["shopId"];
////        //商品标签名称
////        $ResponseData["opt_name"] = $goodsInfo["opt_name"];
//        //轮播图
//        $ResponseData["goods_gallery_urls"] = empty(json_decode($goodsInfo["imageInfo"], true)) ? [] :json_decode($goodsInfo["imageInfo"], true) ;
//        $ResponseData["play_url"] = $goodsInfo["videoList"][0]["playUrl"] ? : '';
//
//        //商品名称
//        $ResponseData["goods_name"] = $goodsInfo["skuName"];
//        //商品id
//        $ResponseData["goods_id"] = $goodsInfo["skuId"];
//        //返佣百分比
//        $promotion_rate = bcdiv($goodsInfo["commissionShare"],100,2);
//        $ResponseData["promotion_rate"] = $promotion_rate;
//        //最小单买价
//        $min_group_price = $goodsInfo["price"];
//        $ResponseData["min_group_price"] = $min_group_price;
//        //优惠券价格
//        $coupon_discount = $goodsInfo["discount"];
//        $ResponseData["coupon_discount"] = $coupon_discount;
//        $ResponseData["return_cash_total"] = $ResponseData["return_cash"] = $goodsInfo["commission"];
//        $ResponseData["isCoupon"] = $goodsInfo["isCoupon"];
//        $ResponseData["isPg"] = $goodsInfo["isPg"];
//        $ResponseData["pingouPrice"] = $goodsInfo["pingouPrice"];
//        $ResponseData["discountPrice"] = $goodsInfo["discountPrice"];
//        $ResponseData["comments"] = $goodsInfo["comments"];
//        $ResponseData["goodCommentsShare"] = $goodsInfo["goodCommentsShare"].'%';
//        $ResponseData["orderCount30days"] = $goodsInfo["orderCount30days"];
//        if ($ResponseData["isPg"]){
//            if($ResponseData["isCoupon"]){
//                $priceName = '券后价';
//            }else{
//                $priceName = '拼购价';
//            }
//        }else{
//            if($ResponseData["isCoupon"]){
//                $priceName = '券后价';
//            }else{
//                $priceName = '超低价';
//            }
//        }
//        $ResponseData["priceName"] = $priceName;
//        //设置缓存
//        Redis::setProductInfo($key,json_encode($ResponseData));
//
//        $ResponseData["user_percent"] = self::getUserPercent();
//        if ($ResponseData["goods_id"] && $ResponseData["goods_name"]){
//            return $ResponseData;
//        }else{
//            return [];
//        }
    }
    public static function getActivityList(){
        $ResponseData = Redis::getActivityInfo();
        if(!empty($ResponseData)){
            $ResponseData = json_decode($ResponseData,true);
            return $ResponseData;
        }
        $data = self::http_get(self::$url.'/activity/getActivityList');
        $data = json_decode($data, true);
        if($data["code"]!=200){
            Log::write($data,'ERROR');
            return array();
        }
        if(count($data["activityList"])){
            Redis::setActivityInfo(json_encode($data["activityList"]));
            return $data["activityList"];
        }else{
            return array();
        }
    }

}