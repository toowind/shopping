<?php
namespace Product\Service\Product;

use Admin\Model\ConfigModel;
use Common\Action\BaseAction;
use Common\Common\Manager\DI;
use Common\Exception\Exception;
use Order\Model\PddOrderModel;
use Passport\Model\UserBroweGoodsModel;
use Passport\Model\UserCollectionModel;
use Passport\Model\UserCollectionStatisticsModel;
use Product\Common\ApiException;
use Think\Log;
use Product\Common\Redis;
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
    //拼多多请求地址
    private static $url = 'https://gw-api.pinduoduo.com/api/router';
    private static $action = array(
        'goods_list'    =>'pdd.goods.list.get',
        'goods_info' =>'pdd.ddk.goods.detail',//获取商品详情页----->单个商品id
        'cat_list'      =>'pdd.goods.cats.get', //获取所有商品类目信息
        'order_list'    =>'pdd.ddk.order.list.increment.get',//根据时间范围拉取所有推广信息
        'order_info'    =>'pdd.ddk.order.detail.get',//查询订单详情--->单个订单号
        'share_url'     =>'pdd.ddk.goods.promotion.url.generate', //生成商城频道pdd.ddk.resource.url.gen
        'search'        =>'pdd.ddk.goods.search',//商品搜索---->关键词、标签id、各种排序规则、是否只返回优惠券商品、类目id、商品id列表、店铺类型、推广位id、是否为品牌商品
        'theme'         =>'pdd.ddk.theme.list.get', //拉取所有主题
        'menu_list'     =>'pdd.goods.opt.get', //商品标签列表
        'sell_well'     =>'pdd.ddk.top.goods.list.query',//热销榜单--->实时热销榜、实时收益榜
        'pdd_order_list'=>'pdd.ddk.order.list.range.get',
        'theme_goods_list'=>'pdd.ddk.theme.goods.search',
    );
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


    //主题商品查询-----不区分平台
    public static function getThemeGoodsList($data){
        $ResponseData = Redis::getThemeGoodsList($data["theme_id"]);
        if(!empty($ResponseData)){
            return json_decode($ResponseData,true);
        }
        $http = DI::get("http");
        $param = array(
            'theme_id'=>$data["theme_id"]
        );
        $arr = self::base_para(self::$action["theme_goods_list"],$param);
        $arr = $http->post(self::$url,$arr);
        if($arr["status"] !=0){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }
        $arr = json_decode($arr["data"],true);
        $ResponseData =self::ArrangementParam($arr["theme_list_get_response"]["goods_list"]);

        Redis::setThemeGoodsList($data["theme_id"],json_encode($ResponseData));
        return $ResponseData;
    }
    /*
     * 生成下单链接
     * $type 1.自买链接  2.分享链接
     * 区分平台
     */
    public static function purchaseUrl($goods_id,$type = 1){
        $http = DI::get("http");
        $param = array(
            'p_id' =>self::$pdd_config[$GLOBALS["platform"]]["pid"],
            'goods_id_list' => '['.$goods_id.']',
            'generate_short_url' =>'true',
            'custom_parameters' =>hashids_encode([$GLOBALS["userId"],$GLOBALS["platformId"],$type])
        );
        $data = self::base_para(self::$action["share_url"],$param);
        $data = $http->post(self::$url,$data);
        if($data["status"] !=0 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }

        $data = json_decode($data["data"],true);
        if(!isset($data["goods_promotion_url_generate_response"]["goods_promotion_url_list"][0]["short_url"])){
            Log::write(json_encode($data),'HTTP_ERROR_PDD_SHORT_URL');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }
        $short_url = $data["goods_promotion_url_generate_response"]["goods_promotion_url_list"][0]["short_url"];
        //生成唤醒app链接
        $newUrl = "pddopen://?appKey=".self::$pdd_config[$GLOBALS["platform"]]["client_id"]."&packageId=cn.youth.news&backUrl=";
        $backUrl = "youthkd://splash";
        $newUrl = $newUrl.urlencode($backUrl)."&h5Url=".urlencode($short_url);

        $url["short_url"] = $short_url;
        $url["awaken_app_url"] =$newUrl;
        return $url;
    }

    /*
     * 商品详情
     * $data array
     * $type 默认0  1为秒杀需要获取轮播图
     */
    public static function getProductInfo($data,$type = 0){
        $goodsId = $data["goods_id"];
        //商品详情页缓存key
        $key = $goodsId.'_'.$type;
        //统计PV
        Redis::setGoodsInfoPv($goodsId);
        //统计UV
        Redis::setGoodsInfoUv($goodsId);

        //设置我的足迹---第二版去掉我的足迹
//        $userBrowGoodsModel = new UserBroweGoodsModel();
//        $arr = $userBrowGoodsModel->fieldList($goodsId);
//        if(empty($arr)){
//            $res = $userBrowGoodsModel->addBrowse($goodsId);
//            if(!$res){
//                Exception::throwException(Exception::HTTP_ERROR);
//            }
//        }

        //获取用户版本判断跳转方式
        $app_version = $GLOBALS["userInfo"]["app_version"];
        if($GLOBALS["platformId"] == 2 && $app_version >= "1.5.5"){
            //中青1.5.5往后版本支持唤醒拼多多app
            $jump_state = 1;
        }else if($GLOBALS["platformId"] == 1){
            //蚂蚁平台默认1 走客户端跳转
            $jump_state = 1;
        }else{
            //跳h5
            $jump_state = 0;
        }

        //获取后台设置返佣规则及秒杀商品
        $Config = self::getCommissionRateAndSeckillGoodsList();

        //普通商品返利比率
        $v = $Config["RETURN_COMMISSION_RATE"]["value"];
        $v = bcdiv($v,100,2);

        //秒杀商品返利比率
        $seckill = $Config["SECKILL_COMMISSION_RATE"]["value"];
        $seckill = bcdiv($seckill,100,2);

        //秒杀商品列表
        $seckillGoodsList = $Config["goods_id_list"];
        //查询缓存
        $goodsInfo = Redis::getProductInfo($key);
        if(!empty($goodsInfo)){
            $ResponseData = json_decode($goodsInfo,true);
            //首页秒杀商品拉详情只返回基本信息
            if($type == 1){
                return $ResponseData;
            }
            ############################以下数据需要实时获取#############################
            //是否收藏
            $ResponseData["collection"]= self::getCollectionStatus($goodsId);
            //生成下单地址
            $purchaseUrl = self::purchaseUrl($goodsId);
            $ResponseData["purchaseUrl"] = $purchaseUrl["short_url"];
            $ResponseData["awaken_app_url"] = $purchaseUrl["awaken_app_url"];
            //跳转状态
            $ResponseData["jump_state"] = $jump_state;
            //当前用户所在平台
            $ResponseData["platformId"] = $GLOBALS["platformId"];
            $ResponseData["app_version"] = $app_version;
            //商品状态 0 普通商品  1秒杀商品
            $ResponseData["goods_status"] = 0;
            if(in_array($goodsId,$seckillGoodsList)){
                $ResponseData["goods_type"] = 1;
                //秒杀商品重新计算返现金额=（最小拼团价-优惠券）*返佣千分比*后台设置秒杀专用佣金比
                $return_cash = bcsub($ResponseData["min_group_price"],$ResponseData["coupon_discount"],2)*$ResponseData["promotion_rate"];
                $return_cash = bcmul($return_cash,$seckill,2);
                $ResponseData["return_cash"] = $return_cash;
                //计算折扣
                $ResponseData["discount"] = bcmul($ResponseData["available_price"]/$ResponseData["min_group_price"],10,1);
            }
            return $ResponseData;
        }

        $http = DI::get("http");
        $param = array(
            'goods_id_list'=>'['.$goodsId.']'
        );
        $data = self::base_para(self::$action["goods_info"],$param);
        $data = $http->post(self::$url,$data);
        $data = json_decode($data["data"],true);
        if(empty($data["goods_detail_response"]) && $type == 0){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            ApiException::throwException(ApiException::GOODS_INFO_ERROR);
        }else if(empty($data["goods_detail_response"]) && $type == 1){
            return [];
        }
        $goodsInfo = $data["goods_detail_response"]["goods_details"][0];
        //店铺名称
        $ResponseData["mall_name"] = $goodsInfo["mall_name"];
        //店铺id
        $ResponseData["mall_id"] = $goodsInfo["mall_id"];
        //商品标签名称
        $ResponseData["opt_name"] = $goodsInfo["opt_name"];
        //轮播图
        $ResponseData["goods_gallery_urls"] = empty($goodsInfo["goods_gallery_urls"]) ? [] :$goodsInfo["goods_gallery_urls"] ;
        //商品名称
        $ResponseData["goods_name"] = $goodsInfo["goods_name"];
        //商品id
        $ResponseData["goods_id"] = $goodsInfo["goods_id"];
        //返佣千分比
        $promotion_rate = bcdiv($goodsInfo["promotion_rate"],1000,3);
        $ResponseData["promotion_rate"] = $promotion_rate;
        //销量
        $ResponseData["sales_tip"] = $goodsInfo["sales_tip"];
        //最小单买价
        $min_group_price = bcdiv($goodsInfo["min_group_price"],100,2);
        $ResponseData["min_group_price"] = $min_group_price;
        //优惠券价格
        $coupon_discount = bcdiv($goodsInfo["coupon_discount"],100,2);
        $ResponseData["coupon_discount"] = $coupon_discount;
        //券后价
        $price = $min_group_price - $coupon_discount;
        //我方商品佣金
        $wePromotion = bcmul( $price,$promotion_rate,4);
        //判断商品类型
        if(in_array($goodsInfo["goods_id"],$seckillGoodsList)){
            //秒杀商品
            $return_cash = bcmul($wePromotion ,$seckill,2);
            $ResponseData["goods_status"] = 1;
        }else{
            //普通商品
            $return_cash = bcmul($wePromotion ,$v,2);
            $ResponseData["goods_type"] = 0;
        }
        //下单返现
        $ResponseData["return_cash"] = $return_cash;
        //到手价
        $available_price = $min_group_price - $coupon_discount - $return_cash;
        $ResponseData["available_price"] = "$available_price";
        //优惠券到期时间
        if($goodsInfo["coupon_end_time"] !=0){
            $ResponseData["coupon_end_time"] = date("Y-m-d",$goodsInfo["coupon_end_time"]);
        }else{
            $ResponseData["coupon_end_time"] = "";
        }
        //计算折扣
        $ResponseData["discount"] = bcmul($available_price/$min_group_price,10,1);
        //设置缓存
        Redis::setProductInfo($key,json_encode($ResponseData));
        //type == 1 首页秒杀商品只返回基本信息即可
        if($type == 1){
            return $ResponseData;
        }
        ############################以下参数需要实时变动###########################################
        //下单链接
        $purchaseUrl = self::purchaseUrl($goodsInfo["goods_id"]);
        $ResponseData["purchaseUrl"] = $purchaseUrl["short_url"];
        $ResponseData["awaken_app_url"] = $purchaseUrl["awaken_app_url"];
        //跳转状态
        $ResponseData["jump_state"] = $jump_state;
        $ResponseData["app_version"] = $app_version;
        $ResponseData["platformId"] = $GLOBALS["platformId"];
        //是否收藏
        $ResponseData["collection"] = self::getCollectionStatus($goodsId);
        $ResponseData["app_version"] = $app_version;
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

    //首页
    public static function getHomeList(){
        //统计入口PV UV
        Redis::setShopPv();
        Redis::setShopUv();

        $ResponseData = Redis::getHomeInfo();
        if(!empty($ResponseData)){
            return json_decode($ResponseData,true);
        }
        $label = ["精选","榜单","百货","女装","男装","食品","水果","母婴","鞋包","内衣","美妆","电器","家纺","文具","运动","虚拟","汽车","家装","家具","医药"];
        $http = DI::get("http");
        $param = array(
            'parent_opt_id'=> 0
        );
        $data = self::base_para(self::$action["menu_list"],$param);
        $data = $http->post(self::$url,$data);
        if($data["status"] != 0 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        $data = json_decode($data["data"],true);
        $data = !empty($data["goods_opt_get_response"]["goods_opt_list"]) ? $data["goods_opt_get_response"]["goods_opt_list"] : array();
        $ResponseData = $newLable = array();
        $data = array_column($data,NULL,"opt_name");
        foreach($label as $key=>$val){
            if(array_key_exists($val,$data)){
                $newLable[$val] = $data[$val];
            }elseif ($val == "榜单"){
                $newLable[$val] = ["parent_opt_id"=>0,"level"=>1,"opt_name"=>"榜单","opt_id"=>-1];
            }
        }
        $ResponseData["label"] = array_values($newLable);
        $ResponseData["label"][0]["opt_name"] = "推荐";
        //省钱福利社
        $jumpUrl = array(
            "my"=>array(
//                array("name"=>"天天拆红包","url"=>"https://mobile.yangkeduo.com/duo_three_red_packet.html?pid=8501060_102901047&cpsSign=CR_190731_8501060_102901047_29a59073886720fcf2c316f5a128bdfa&duoduo_type=2"),
//                array("name"=>"幸运抽免单","url"=>"https://mobile.yangkeduo.com/duo_roulette.html?pid=8501060_102901066&cpsSign=CL_190731_8501060_102901066_f85ac1930ffd0c5dde634b3eb4da725f&duoduo_type=2"),
//                array("name"=>"1.9包邮","url"=>"https://mobile.yangkeduo.com/duo_nine_nine.html?pid=8501060_102901091&cpsSign=CM_190731_8501060_102901091_ca79255cef7aa8a106d5c03b029a5ad4&duoduo_type=2"),
//                array("name"=>"限时秒杀","url"=>"https://mobile.yangkeduo.com/duo_transfer_channel.html?resourceType=4&pid=8501060_102901151&cpsSign=CE_190731_8501060_102901151_7fb1819d4fb6f5eef1a56ea067351c96&duoduo_type=2"),
//                array("name"=>"今日爆款","url"=>"https://mobile.yangkeduo.com/duo_today_burst.html?pid=8501060_102901127&cpsSign=CM_190731_8501060_102901127_febd41ebfcb384794e6f2eebed9ccee6&duoduo_type=2"),
//                array("name"=>"清仓专区","url"=>"","type"=>1), //主题列表
                "url"=>"https://mobile.yangkeduo.com/duo_transfer_channel.html?resourceType=4&pid=8501060_102901151&cpsSign=CE_190806_8501060_102901151_8171db8872ead70c946ababee78521e1&duoduo_type=2"
            ),
            "zq"=>array(
//                array("name"=>"天天拆红包","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_three_red_packet.html%3Fpid%3D8308015_102892635%26auto_open%3D1%26cpsSign%3DCR_190731_8308015_102892635_3ba8c0bf662395a7e3f64d711740803e%26range_items%3D2%253A200%253A300%26duoduo_type%3D2"),
//                array("name"=>"幸运抽免单","url"=>"https://mobile.yangkeduo.com/duo_roulette.html?pid=8308015_102892635&cpsSign=CL_190731_8308015_102892635_c7e13155bb99373b8f67c72febd9ce88&range_items=1%3A100%3A1000%2C2%3A200%3A250&duoduo_type=2&launch_pdd=1"),
//                array("name"=>"1.9包邮","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_nine_nine.html%3Fpid%3D8308015_102892635%26cpsSign%3DCM_190731_8308015_102892635_60a2807af9aa6de2d5aa901ae6985e4b%26duoduo_type%3D2"),
//                array("name"=>"限时秒杀","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_transfer_channel.html%3FresourceType%3D4%26pid%3D8308015_102892635%26cpsSign%3DCE_190731_8308015_102892635_33d0f47e65db99200ee3be2e19d15990%26duoduo_type%3D2"),
//                array("name"=>"今日爆款","url"=>"https://mobile.yangkeduo.com/app.html?launch_url=duo_today_burst.html%3Fpid%3D8308015_102892635%26cpsSign%3DCM_190731_8308015_102892635_a8642e835c3bb588bc5acf3726fafcf0%26duoduo_type%3D2"),
//                array("name"=>"清仓专区","url"=>"","type"=>1), //主题列表
                "url"=>"https://mobile.yangkeduo.com/duo_transfer_channel.html?resourceType=4&pid=8308015_102892635&cpsSign=CE_190806_8308015_102892635_0af165e19d6c7563e1ae5b5f53bbd5b0&duoduo_type=2"
            ),
        );
        $ResponseData["welfare"] = $jumpUrl[$GLOBALS["platform"]]["url"];

        //订单返利----先去掉默认0
//        $pddOrderModel = new PddOrderModel();
//        $money = $pddOrderModel->getPromotionAmountTrueByUserId();
//        $rebate = !empty($money[0]["money"]) ? $money[0]["money"] : 0;
        $ResponseData["rebate"] = 0;

        Redis::setHomeInfo(json_encode($ResponseData));
        return $ResponseData;
    }

    //分页拉取主题
    public static function getThemeList($page,$page_size){
        $k = $page."_".$page_size;
        //总体一级缓存30分钟
        $ResponseData = Redis::getThemeList($k);
        if(!empty($ResponseData)){
            return json_decode($ResponseData,true);
        }

        //读取主题列表缓存---主题单独二级缓存
        $ThemeList = Redis::getTheme($k);
        if(empty($ThemeList)){
            //分页拉取主题
            $http = DI::get("http");
            $param = [
                "page"=>$page,
                "page_size"=>$page_size
            ];
            $data = self::base_para(self::$action["theme"],$param);
            $data = $http->post(self::$url,$data);
            if($data["status"] != 0 ){
                Log::write(json_encode($data),'HTTP_ERROR_PDD');
                Exception::throwException(Exception::HTTP_ERROR);
            }
            $data = json_decode($data["data"],true);
            $theme = !empty($data["theme_list_get_response"]["theme_list"]) ? $data["theme_list_get_response"]["theme_list"] : array();
            if(empty($theme)){
                return array();
            }
            Redis::setTheme($k,json_encode($theme));
        }else{
            $theme  = json_decode($ThemeList,true);
        }

        //查询后台设置返利佣金比
        $configModel = new ConfigModel();
        $config = $configModel->getValueByName(["RETURN_COMMISSION_RATE"]);
        if(empty($config)){
            Log::write("Config error",'HTTP_ERROR');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        $v = bcdiv($config[0]["value"],100,2);


        //拉取每个主题的商品列表
        foreach ($theme as $key=>$val){
            //每个主题下的商品id-----三级缓存
            $goodsListInfo = self::getThemeGoodsList(["theme_id"=>$val["id"]]);
            if(count($goodsListInfo) >20){
                $goodsList = array_slice($goodsListInfo,0,6);
            }
            $promotion_rate = array_sum(array_column($goodsList,"promotion_rate"));
            $averageCostSavings = bcdiv($promotion_rate,6,2);
            $averageCostSavings = bcmul($averageCostSavings,$v,2)*100;
            $theme[$key]["average_cost_savings"] = $averageCostSavings."%";

            $theme[$key]["goods_list"] = $goodsList;
        }

        //设置缓存
        Redis::setThemeList(json_encode($theme),$k);
        return $theme;
    }

    //查询商品列表----不区分平台
    public static function getGoodsList($paramData = array()){
        $http = DI::get("http");
        $RequestData = array();
        //根据标签id搜索
        if(isset($paramData["opt_id"]) && $paramData["opt_id"] !=0)  $RequestData["opt_id"] = $paramData["opt_id"];

        //根据推广位pid搜索
        if(isset($paramData["pid"]) && $paramData["pid"] !=0)  $RequestData["pid"] = $paramData["pid"];

        //根据商品id搜索
        if(!empty($paramData["goods_id_list"]))  $RequestData["goods_id_list"] = $paramData["goods_id_list"];

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
        if(isset($paramData["page"])) $RequestData["page"] = $paramData["page"];

        //page_size 默认100
        if(isset($paramData["page_size"]))   $RequestData["page_size"] = $paramData["page_size"];

        //根据商品id进行搜索
        if(isset($paramData["goods_id_list"]))   $RequestData["goods_id_list"] = json_encode($paramData["goods_id_list"]);


        //排序规则
        /*
         * 0综合排序
         * ;1-按佣金比率升序;2-按佣金比例降序;
         * 5-按销量升序;6-按销量降序;
         * ;7-优惠券金额排序升序;8-优惠券金额排序降序;
         * ;9-券后价升序排序;10-券后价降序排序;
         */
        if(isset($paramData["sort_type"]))   $RequestData["sort_type"] = $paramData["sort_type"];
        $RequestData["with_coupon"] = 'true'; //只返回有优惠券的商品

        //范围查询
        if(isset($paramData["range_list"])) $RequestData["range_list"] = $paramData["range_list"];

        //读取缓存
        $key = md5(json_encode($RequestData));
        $ResponseData = Redis::getSearch($key);
        if(!empty($ResponseData)){
            return json_decode($ResponseData,true);
        }
        $data = self::base_para(self::$action["search"],$RequestData);
        $data = $http->post(self::$url,$data);
        $data = json_decode($data["data"],true);
        if(!empty($data["error_response"])){
            Log::write($data,'ERROR');
            return array();
        }
        $goodsList = !empty($data["goods_search_response"]["goods_list"]) ? $data["goods_search_response"]["goods_list"] : array();
        if(empty($goodsList)){
            return array();
        }
        //整理数据
        $ResponseData = self::ArrangementParam($goodsList);

        Redis::setSearch($key,json_encode($ResponseData));
        return $ResponseData;
    }

    //热销榜单商品列表
    public static function sellWellGoodsList($sort_type=1,$offset=0,$limit=30,$responseType){

        $key = $sort_type."_".$offset."_".$limit.'_'.$responseType;
        //查询缓存
        $ResponseData = Redis::getSellWellGoodsList($key);
        if(!empty($ResponseData)){
            $ResponseData = json_decode($ResponseData,true);
            foreach ($ResponseData as $key => $value){
                if($ResponseData[$key]['coupon_discount'] == 0){
                    unset($ResponseData[$key]);
                }
                $list1 = $value;
            }
            $ResponseData = array_values($ResponseData);
            if($responseType ==1){
                foreach ($list1 as $k => $val){
                    if($list1[$k]['coupon_discount'] == 0){
                        unset($list1[$k]);
                    }
                }
                $list1 = array_values($list1);
                $ResponseData["img"] ='Public/Product/img/sellWell.png';
                $ResponseData["name"] ="夏家店清凉驾到，低至5.9元起";
                $ResponseData["list"] = $list1;
            }
            return $ResponseData;
        }
        //取 收藏榜单数据
        if($sort_type == 3){
            $userCollectionModel = new UserCollectionStatisticsModel();
            $data = $userCollectionModel->getList($offset,$limit);
            if(!empty($data)){
                $goodsIdList = array_column($data,"goods_id");
                $list = self::getGoodsList(["goods_id_list"=>$goodsIdList]);
                goto Response;
            }else{
                goto to;
            }
        }
        to:
        $http = DI::get("http");
        $param = array(
            'sort_type'=> $sort_type,
            'offset'=>$offset,
            'limit'=>$limit
        );
        $data = self::base_para(self::$action["sell_well"],$param);
        $data = $http->post(self::$url,$data);
        if($data["status"] != 0 ){
            Log::write(json_encode($data),'HTTP_ERROR_PDD');
            Exception::throwException(Exception::HTTP_ERROR);
        }
        $data = json_decode($data["data"],true);
        $data = !empty($data["top_goods_list_get_response"]["list"]) ? $data["top_goods_list_get_response"]["list"] : array();
        $list = self::ArrangementParam($data,1);

        Response:
        if($responseType ==1){
            foreach ($list as $key1 => $value1){
                if($list[$key1]['coupon_discount'] == 0){
                    unset($list[$key1]);
                }
            }
            $list = array_values($list);
            $ResponseData["img"] ='Public/Product/img/sellWell.png';
            $ResponseData["name"] ="夏家店清凉驾到，低至5.9元起";
            $ResponseData["list"] = $list;
        }else{
            foreach ($list as $key1 => $value1){
                if($list[$key1]['coupon_discount'] == 0){
                    unset($list[$key1]);
                }
            }
            $list = array_values($list);
            $ResponseData = $list;
        }
        //缓存榜单数据
        Redis::setSellWellGoodsList($key,json_encode($ResponseData));
        return $ResponseData;
    }

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
        //获取后台设置佣金率及所有秒杀商品列表
        $config = self::getCommissionRateAndSeckillGoodsList();

        //普通商品佣金率
        $v = bcdiv($config["RETURN_COMMISSION_RATE"]["value"],100,2);

        //秒杀商品佣金率
        $seckill = bcdiv($config["SECKILL_COMMISSION_RATE"]["value"],100,2);

        //秒杀商品列表
        $seckillGoodsList = $config["goods_id_list"];

        $ResponseData = array();
        foreach($goodsList as $key=>$val){
            $ResponseData[$key]["goods_id"] = isset($val["goods_id"]) ? $val["goods_id"] : 0;
            $ResponseData[$key]["goods_name"] = isset($val["goods_name"]) ? $val["goods_name"] : "";
            $ResponseData[$key]["goods_thumbnail_url"] = isset($val["goods_thumbnail_url"]) ? $val["goods_thumbnail_url"] : "";

            //优惠券金额
            $coupon_discount = isset($val["coupon_discount"]) ? bcdiv($val["coupon_discount"],100,0) : 0;
            $ResponseData[$key]["coupon_discount"] = $coupon_discount;

            //最小拼团价
            $min_group_price = isset($val["min_group_price"]) ? bcdiv($val["min_group_price"],100,2) : 0;
            $ResponseData[$key]["min_group_price"] = $min_group_price;

            //拼多多佣金比例 千分比
            $promotion_rate = bcdiv($val["promotion_rate"],1000,3);
            $ResponseData[$key]["promotion_rate"] = $promotion_rate;
            //拼多多佣金
            //单价  减去 优惠券
            $Present_price = $min_group_price - $coupon_discount;
            $promotion_rate_moeny = bcmul($promotion_rate,$Present_price,4);

            //判断是否秒杀商品
            if(in_array($val["goods_id"],$seckillGoodsList)){
                //秒杀商品
                $rebate_price = bcmul($promotion_rate_moeny,$seckill,2);
                $ResponseData[$key]["goods_type"] = 1;
            }else{
                //普通商品
                $rebate_price = bcmul($promotion_rate_moeny,$v,2);
                $ResponseData[$key]["goods_type"] = 0;
            }
            //返利金额
            $ResponseData[$key]["rebate_price"] = $rebate_price;

            //到手价格
            $present_price = isset($val["min_group_price"]) ? bcsub(bcsub($min_group_price,$coupon_discount,2),$rebate_price,2) : 0; //现价=最小拼团价-优惠券价-返利价格
            $ResponseData[$key]["present_price"] = $present_price < 0 ? 0 : $present_price;
            $ResponseData[$key]["sales_tip"] = isset($val["sales_tip"]) ? $val["sales_tip"] : ""; //销量

            //折扣比
            $ResponseData[$key]["discount"] = bcmul($present_price/$min_group_price,10,1);
            //优惠券过期时间
            if($val["coupon_end_time"] != 0 ){
                $coupon_end_time = isset($val["coupon_end_time"]) ? date("Y-m-d",$val["coupon_end_time"]) : 0;
                $ResponseData[$key]["coupon_end_time"] =  "优惠券将于".$coupon_end_time."过期";
            }else{
                $ResponseData[$key]["coupon_end_time"] = "";
            }

        }
        return $ResponseData;
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
    //首页---秒杀商品
    public static function getSeckillInfo($data){
        $num = isset($data["num"]) ? $data["num"] : 1;
        //查询缓存
        $ResponseData = Redis::getSpecialOfferSeckill($num);
        if(!empty($ResponseData)){
            return json_decode($ResponseData,true);
        }

        //查询缓存---后台配置的秒杀商品id
        $key = md5(json_encode(self::$seckillTypeList));
        $config = Redis::getSeckillConfig($key);
        if(empty($config)){
            //读取后台设置秒杀商品id
            $configModel = new ConfigModel();
            $config = $configModel->getValueByName(self::$seckillTypeList);
            Redis::setSeckillConfig($key,json_encode($config));
        }else{
            $config = json_decode($config,true);
        }

        self::$seckillValue = $config;
        //拼多多商品id有的是string、float 无法直接作为数组key
        foreach($config as $key=>$val){
            if(in_array($val["name"],self::$seckillGoodsKey)){
                $value = explode(',',$val["value"]);
                if(empty($value)){
                    Log::write("读取秒杀商品配置异常");
                    Exception::throwException(Exception::HTTP_ERROR);
                }
                $form = self::$price_range[$val["form"]];
                $to = self::$price_range[$val["to"]];
                //循环取商品id--取余
                $goodsInfo = self::getGoodsInfoByNum($num,$value,$form,$to);
                $configs[$key]["goodsId"] = $goodsInfo["goods_id"];
                $configs[$key]["type"] = $val["name"];
                $configs[$key]["info"] = $goodsInfo;
                if($val["name"] == "ONE_SECKILL"){
                    $oneSeckill[] = $configs[$key];
                }elseif($val["name"] == "TEN_SECKILL"){
                    $tenSeckill[] = $configs[$key];
                }else{
                    $clearancePrice[] = $configs[$key];
                }
            }
        }
        $ResponseData = array_merge($oneSeckill,$tenSeckill,$clearancePrice);
        Redis::setSpecialOfferSeckill($num,json_encode($ResponseData));
        return $ResponseData;
    }

    //秒杀商品列表页
    public static function getSeckillList($type,$page,$page_size){
        $ResponseData = [];
        //查询缓存
        $key = $type.'_'.$page.'_'.$page_size;
        $ResponseData = Redis::getSeckillList($key);
        if(!empty($ResponseData)){
            return json_decode($ResponseData,true);
        }
        //banner
        $bannerList = [
            "CLEARANCE_PRICE"=>'Public/Product/img/CLEARANCE_PRICE.png',
            "TEN_SECKILL"=>'Public/Product/img/TEN_SECKILL.png',
            "ONE_SECKILL"=>'Public/Product/img/ONE_SECKILL.png'
        ];
        $ResponseData["img"] = $bannerList[$type];
        $ResponseData["list"] = [];

        //查询后台设置秒杀的商品
        $configModel = new ConfigModel();
        $data = $configModel->getValueByName([$type]);
        if(empty($data)){
            return $ResponseData;
        }
        $goodsId = explode(',',$data[0]["value"]);
        $goodsIdList = array_slice($goodsId,$page,$page_size);
        if(empty($goodsIdList)){
            return $ResponseData;
        }

        //获取商品列表
        $ResponseData["list"] = self::getGoodsList(["goods_id_list"=>$goodsIdList]);

        //设置缓存
        Redis::setSeckillList($key,json_encode($ResponseData));
        return $ResponseData;
    }

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
}