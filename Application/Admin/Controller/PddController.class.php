<?php
namespace Admin\Controller;
use Admin\Model\GoodsModel;
use Common\Common\Manager\DI;
use Product\Common\Redis;

class PddController extends AdminController{

    private $order_status_text = [
        "-1"=>'未支付',
        "0"=>'已支付',
        "1"=>'已成团',
        "2"=>'确认收货',
        "3"=>'审核成功',
        "4"=>'审核失败',
        "5"=>'已经结算',
        "8"=>'非多多进宝商品',
    ];// 订单状态
    private $platform_text = [
        "1"=>'蚂蚁',
        "2"=>'中青',
    ];//来源
    private $rebate_status_text = [
        "0"=>'未返利',
        "5"=>'返利成功',
        "4"=>'返利失败',
    ];//返利状态

    private   $SortingRules = [
        "1"=>"收藏升序",
        "2"=>"收藏降序",
        "3"=>"订单量升序",
        "4"=>"订单量降序",
    ];

    /**
     * 拼多多订单列表
     */
    public function pdd_order_list(){
        $Data_mod=M('pdd_order');
        $order_sn=I('get.order_sn','','trim'); // 订单号
        $uid=I('get.uid','','intval'); // 用户ID
        $platform=I('get.platform',0,'intval');//订单来源
        $order_status=I('get.order_status','','trim');//订单状态
        $start_time=I('get.start_time',date('Y-m-d 00:00:00',time()));//下单时间
        $end_time=I('get.end_time',date('Y-m-d 23:59:59',time()));//下单时间
        $goods_id=I('get.goods_id','','intval');//商品ID
        $rebate_status=I('get.rebate_status','','trim');//返利状态

        //订单返利金额
        $order_amount_start = I("get.order_amount_start","");
        $order_amount_end = I("get.order_amount_end","");
        //用户返利金额
        $promotion_amount_true_start = I("get.promotion_amount_true_start","");
        $promotion_amount_true_end = I("get.promotion_amount_true_end","");
        //平台返利金额
        $promotion_amount_platform_start = I("get.promotion_amount_platform_start","");
        $promotion_amount_platform_end = I("get.promotion_amount_platform_end","");

        //查询指定时间内所有数据,包含汇总
        $map=array();
        if($order_sn){
            $map['order_sn'] = $order_sn;
        }
        if($uid){
            $map['uid']=$uid;
        }
        if($rebate_status!=''){
            if($rebate_status == 5){
                //返利成功
                $map['rebate_status'][] = array("in",[5]);
            }elseif($rebate_status == 4){
                //返利失败
                $map['rebate_status'][] = array("in",[4]);
            }else{
                //未返利
                $map['rebate_status'][] = array("in",[0,1,2,3,8]);
            }
        }
        if($platform){
            $map['platformId']=$platform;
        }
        if($order_status!=''){
            $map['order_status']=$order_status;
        }
        if($start_time){
            $map['order_create_time'][]=array('egt',strtotime($start_time));
        }
        if($end_time){
            $map['order_create_time'][]=array('lt',strtotime($end_time));
        }
        if($goods_id){
            $map['goods_id']=$goods_id;
        }

        if($order_amount_start){
            $map["order_amount"][] = ["egt",$order_amount_start*100];
        }
        if($order_amount_end) {
            $map["order_amount"][] = ["elt",$order_amount_end*100];
        }

        if($promotion_amount_true_start){
            $map["promotion_amount_true"][] = ["egt",$promotion_amount_true_start];
        }
        if($promotion_amount_true_end) {
            $map["promotion_amount_true"][] = ["elt",$promotion_amount_true_end];
        }

        if($promotion_amount_platform_start) {
            $map["promotion_amount_platform"][] = ["egt",$promotion_amount_platform_start*100];
        }
        if($promotion_amount_platform_end) {
            $map["promotion_amount_platform"][] = ["elt",$promotion_amount_platform_end*100];
        }

        //订单状态 返利状态等
        $order_status_text = $this->order_status_text;
        $platform_text = $this->platform_text;

        $rebate_status_text = $this->rebate_status_text;

        $count=$Data_mod->where($map)->count();

        //分页处理
        $listRows=C('LIST_ROWS')>0?C('LIST_ROWS'):10;
        $Page=new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit=$Page->firstRow.','.$Page->listRows;
        $list=$Data_mod->where($map)->limit($limit)->order('order_create_time DESC')->select();
//        echo $Data_mod->getLastSql();die;
        if($list){
            foreach($list as $k=>$v){
                $list[$k]['order_status'] = $order_status_text[$v["order_status"]];
                $list[$k]['promotion_amount_true'] = $v['promotion_amount_true']; //用户返利金额
                $list[$k]['promotion_amount_platform']= $v["promotion_amount_platform"]/100; //平台返利金额
                $list[$k]['platform_text'] = isset($platform_text[$v['platformid']]) ? $platform_text[$v['platformid']]:"";
                $list[$k]['rebate_status_text'] = isset($rebate_status_text[$v['rebate_status']])?$rebate_status_text[$v['rebate_status']]:"";
                //订单金额
                $list[$k]['order_amount'] = $v["order_amount"]/100;
            }
        }
        //用户返利总金额
        $promotion_amount_true = $Data_mod->field('sum(promotion_amount_true) as promotion_amount_true')->where(["rebate_status"=>1])->find();
        $sum["promotion_amount_true"] = empty($promotion_amount_true["promotion_amount_true"]) ? 0 : bcdiv($promotion_amount_true["promotion_amount_true"],1,2);
        //平台返利总额
        $promotion_amount = $Data_mod->field('sum(promotion_amount) as promotion_amount')->where(["order_status"=>5])->find();
        $sum['promotion_amount_platform'] = empty($promotion_amount["promotion_amount"]) ? 0 : bcdiv($promotion_amount["promotion_amount"],100,2);

        //循环处理搜索框订单状态
        foreach($order_status_text as $key=>$val){
            $order_status_texts[$key]["value"] = $val;
            $order_status_texts[$key]["key"] = $key;
        }
        //循环处理搜索框返利状态
        foreach($rebate_status_text as $key=>$val){
            $rebate_status_texts[$key]["key"] = $key;
            $rebate_status_texts[$key]["value"] = $val;

        }
        $search = [
            "uid"=>$uid,
            "start_time"=>$start_time,"end_time"=>$end_time,
            "order_sn"=>$order_sn,"platform"=>$platform,"goods_id"=>$goods_id,

            "order_amount_start"=>$order_amount_start,
            "order_amount_end"=>$order_amount_end,
            "promotion_amount_true_start"=>$promotion_amount_true_start,
            "promotion_amount_true_end"=>$promotion_amount_true_end,
            "promotion_amount_platform_start"=>$promotion_amount_platform_start,
            "promotion_amount_platform_end"=>$promotion_amount_platform_end,

            ];
        $this->assign(array(
            '_list'=>$list,'sum'=>$sum,'page'=>$Page->show(),
             "search"=>$search,
                'order_status_text'=>$order_status_texts,'platform_text'=>$platform_text,'rebate_status_text'=>$rebate_status_texts,
                "order_status"=>$order_status,"rebate_status"=>$rebate_status,
            )
        );
        $this->meta_title='订单列表';
        $this->display();
    }

    /**
     * 商城用户管理
     */
    public function pdd_user_order(){
        $PddUserOrderModel=M('pdd_user_order');
        $PddOrderModel = M("pdd_order");
        $uid=I('get.uid','','intval'); // 用户ID
        $platform=I('get.platform',0,'intval');//订单来源
        $shopping_start_time=I('get.shopping_start_time','');
        $shopping_end_time=I('get.shopping_end_time','');
        $order_start_time=I('get.order_start_time',"");
        $order_end_time=I('get.order_end_time',"");
        //查询指定时间内所有数据,包含汇总
        $map=array();
        if($uid){
            $map['uid']=$uid;
        }
        if($platform){
            $map['platformId']=$platform;
        }
        if($shopping_start_time){
            $map['shopping_lately_time'][]=array('egt',strtotime($shopping_start_time));
        }
        if($shopping_end_time){
            $map['shopping_lately_time'][]=array('lt',strtotime($shopping_end_time)+86400);
        }
        if($order_start_time){
            $map['order_lately_time'][]=array('egt',strtotime($order_start_time));
        }
        if($order_end_time){
            $map['order_lately_time'][]=array('lt',strtotime($order_end_time)+86400);
        }
        //订单状态 返利状态等
        $platform_text = $this->platform_text;
        $count=$PddUserOrderModel->where($map)->count();
        //分页处理
        $listRows=C('LIST_ROWS')>0?C('LIST_ROWS'):10;
        $Page=new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit=$Page->firstRow.','.$Page->listRows;
        $list=$PddUserOrderModel->where($map)->limit($limit)->order('id DESC')->select();
        $userIdList = array_column($list,"uid");
        foreach ($list as $key => $val){
            $uid = $val["uid"];
            $newList["$uid"] = $val;
        }

        //查询用户订单数据
        $ResponseData = array();
        if(!empty($userIdList)){
            $data = $PddOrderModel->where(["uid"=>["in",$userIdList]])->select();
            $ResponseData = array();
            if($data){
                foreach ($data as $key=>$val){
                    $uid = $val["uid"];
                    $ResponseData["$uid"]["uid"] = $uid;
                    $ResponseData["$uid"]["platform_text"] = $this->platform_text[$val["platformid"]];
                    $ResponseData["$uid"]["rebate_pending_order_num"] = 0;
                    $ResponseData["$uid"]["rebate_complete_order_num"] = 0;
                    $ResponseData["$uid"]["rebate_order_num"] = 0;

                    $ResponseData["$uid"]["rebate_pending_amount"] = 0;
                    $ResponseData["$uid"]["rebate_complete_amount"] = 0;
                    $ResponseData["$uid"]["rebate_amount"] = 0;
                    if(in_array($val["order_status"],[0,1,2,3])){
                        //待结算
                        $ResponseData["$uid"]["rebate_pending_order_num"] = $ResponseData["$uid"]["rebate_pending_order_num"] + 1;
                        $ResponseData["$uid"]["rebate_pending_amount"] = $ResponseData["$uid"]["rebate_pending_amount"] + $val["promotion_amount_true"] ;

                    }else if(in_array($val["order_status"],[5])){
                        //已结算
                        $ResponseData["$uid"]["rebate_complete_order_num"] =  $ResponseData["$uid"]["rebate_complete_order_num"] +1;
                        $ResponseData["$uid"]["rebate_complete_amount"] =  $ResponseData["$uid"]["rebate_complete_amount"] + $val["promotion_amount_true"];
                    }
                    //总订单
                    $ResponseData["$uid"]["rebate_order_num"] = $ResponseData["$uid"]["rebate_pending_order_num"]  + $ResponseData["$uid"]["rebate_complete_order_num"];
                    $ResponseData["$uid"]["rebate_amount"] = $ResponseData["$uid"]["rebate_pending_amount"]  + $ResponseData["$uid"]["rebate_complete_amount"];

                    $ResponseData["$uid"]["shopping_lately_time"] = isset($newList[$uid]) ? $newList[$uid]["shopping_lately_time"] : "--";
                    $ResponseData["$uid"]["order_lately_time"] = isset($newList[$uid]) ? $newList[$uid]["order_lately_time"] : "--";
                }

            }
        }
        $redis = new \Common\Common\Cache\Redis(C("redis"));
        //商城入口PV UV 统计
        for($i=0;$i<14;$i++){
            $date = date("Y-m-d",strtotime("-$i day"));
            $zq_pv = $redis->get("shop_pv_zq_".$date);
            $zq_uv = $redis->SCARD("shop_uv_zq_".$date);

            $my_pv = $redis->get("shop_pv_my_".$date);
            $my_uv = $redis->SCARD("shop_uv_my_".$date);

            $uvPv[$date]["zq_pv"] = $zq_pv ? $zq_pv : 0;
            $uvPv[$date]["zq_uv"] = $zq_uv ? $zq_uv : 0;

            $uvPv[$date]["my_pv"] = $my_pv ? $my_pv : 0;
            $uvPv[$date]["my_uv"] = $my_uv ? $my_uv : 0;
            $uvPv[$date]["date"] = $date;
        }
        $this->assign(array('_list'=>$ResponseData,
            'page'=>$Page->show(),'start_time'=>$shopping_start_time,'end_time'=>$shopping_end_time,
            'uid'=>"",'order_sn'=>$order_start_time,'order_status'=>$order_start_time,
            'platform'=>$platform,
            'platform_text'=>$platform_text,
            'order_start_time'=>$order_start_time,
            'order_end_time' => $order_end_time,
            'pvUv'=>$uvPv
        ));
        $this->meta_title='用户订单管理';
        $this->display();
    }

    /*
     * 商品管理
     */
    public function order_goods_list(){
        $sort = I("sorting_rules",2,"intval");
        $goodsId = I("goods_id","");

        $redis = new \Common\Common\Cache\Redis(C("redis"));
        $pddOrderModel = M("pdd_order");
        $userCollectionStatisticsModel = M("user_collection_statistics");
        $where = [];
        if($goodsId != NULL) $where["goods_id"] = "$goodsId";
        $count=$userCollectionStatisticsModel->where($where)->count();

        //分页处理
        $listRows=C('LIST_ROWS')>0?C('LIST_ROWS'):10;
        $Page=new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit=$Page->firstRow.','.$Page->listRows;
        $order = "";
        switch ($sort){
            case 1:
                $order = " num asc";
                break;
            case 2:
                $order = " num desc";
                break;
            case 3:
                $order = " order_num asc";
                break;
            case 4:
                $order = " order_num desc";
                break;
            default:
                $order = "";
        }
        $data = $userCollectionStatisticsModel->where($where)->order($order)->limit($limit)->select();
        $ResponseData = array();
        foreach ($data as $key=>$val){
            $goods_id = $val["goods_id"];
            $ResponseData[$key]["goods_id"] = $goods_id;
            $ResponseData[$key]["goods_name"] =$val["goods_name"];
            $ResponseData[$key]["num"] = $val["num"];
            $ResponseData[$key]["order_num"] = $val["order_num"];
            if($val["platformid"] == 2){
                $pv_key = "goods_info_pv_zq";
                $uv_key = "goods_info_uv_zq_".$goods_id;
            }else{
                $pv_key = "goods_info_pv_my";
                $uv_key = "goods_info_uv_my_".$goods_id;
            }


            $pv = $redis->ZSCORE($pv_key,$goods_id);
            $uv = $redis->SCARD($uv_key);
            $ResponseData[$key]["pv"] = !empty($pv) ? $pv : 0;
            $ResponseData[$key]["uv"] = !empty($uv) ? $uv : 0;

            $ResponseData[$key]["platform"] = $this->platform_text[$val["platformid"]];
        }
        //商品排序规则
        $SortingRules = $this->SortingRules;
        $this->meta_title = "平台商品";
        $this->assign([
            "_list"=>$ResponseData,'page'=>$Page->show(),
            "SortingRules"=>$SortingRules,
            "sorting"=>$sort,
            "goods_id"=>$goodsId
        ]);
        $this->display();
    }

    //拼多多商品管理
    public function pdd_goods_list(){
        //商品id
        $goodsId = I("get.goods_id","");
        //排序
        $sorting_rules = I("get.sorting_rules","");
        $end_time = I("get.end_time","");


        //原价
        $min_group_price_start = I("get.min_group_price_start","","intval");
        $min_group_price_end = I("get.min_group_price_end","","intval");
        //优惠券
        $coupon_discount_start = I("get.coupon_discount_start","","intval");
        $coupon_discount_end = I("get.coupon_discount_end","","intval");
        //券后价
        $coupon_after_price_start = I("get.coupon_after_price_start","","intval");
        $coupon_after_price_end = I("get.coupon_after_price_end","","intval");
        //佣金
        $promotion_money_start = I("get.promotion_money_start","","intval");
        $promotion_money_end = I("get.promotion_money_end","","intval");
        //返佣比
        $promotion_rate_start = I("get.promotion_rate_start","","intval");
        $promotion_rate_end = I("get.promotion_rate_end","","intval");
        $str = " id ";
        $search = [
            "goods_id"=>$goodsId,
            "min_group_price_start"=>$min_group_price_start,
            "min_group_price_end"=>$min_group_price_end,
            "coupon_discount_start"=>$coupon_discount_start,
            "coupon_discount_end"=>$coupon_discount_end,
            "coupon_after_price_start"=>$coupon_after_price_start,
            "coupon_after_price_end"=>$coupon_after_price_end,
            "promotion_money_start"=>$promotion_money_start,
            "promotion_money_end"=>$promotion_money_end,
            "promotion_rate_start"=>$promotion_rate_start,
            "promotion_rate_end"=>$promotion_rate_end,
        ];

        $goodsModel = M("goods");
        if($goodsId != "")  $where["goods_id"] = $goodsId;

        if($end_time !=""){
            $where["coupon_end_time"][] = ["elt",strtotime($end_time)];
            $str = " coupon_end_time";
        }
        if($min_group_price_start !=""){
            $where["min_group_price"][] = ["egt",$min_group_price_start*100];
            $str = "min_group_price";
        }
        if($min_group_price_end !=""){
            $where["min_group_price"][] = ["elt",$min_group_price_end*100];
            $str = "min_group_price";
        }

        if($coupon_discount_start !="") {
            $where["coupon_discount"][] = ["egt",$coupon_discount_start*100];
            $str = "coupon_discount";
        }
        if($coupon_discount_end !="") {
            $where["coupon_discount"][] = ["elt",$coupon_discount_end*100];
            $str = "coupon_discount";
        }

        if($coupon_after_price_start !=""){
            $where["coupon_after_price"][] = ["egt",$coupon_after_price_start*100];
            $str = "coupon_after_price";
        }
        if($coupon_after_price_end !="") {
            $where["coupon_after_price"][] = ["elt",$coupon_after_price_end*100];
            $str = "coupon_after_price";
        }

        if($promotion_money_start !=""){
            $where["promotion_money"][] = ["egt",$promotion_money_start*100];
            $str = "promotion_money";
        }
        if($promotion_money_end !="") {
            $where["promotion_money"][] = ["elt",$promotion_money_end*100];
            $str = "promotion_money";
        }

        if($promotion_rate_start !="") {
            $where["promotion_rate"][] = ["egt",$promotion_rate_start*10];
            $str = "promotion_rate";
        }

        if($promotion_rate_end !="") {
            $where["promotion_rate"][] = ["elt",$promotion_rate_end*10];
            $str = "promotion_rate";
        }
        $str = $sorting_rules == 2 ? $str." asc" : $str." desc";

        $count = $goodsModel->where($where)->count("id");
        $listRows=C('LIST_ROWS')>0?C('LIST_ROWS'):10;
        $Page=new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit=$Page->firstRow.','.$Page->listRows;


        $data = $goodsModel->where($where)->order($str)->limit($limit)->select();

        //整理数据格式
        foreach ($data as $key=>$val){
            $data[$key]["min_group_price"] = $val["min_group_price"]/100;
            $data[$key]["coupon_discount"] = $val["coupon_discount"]/100;
            $data[$key]["coupon_start_time"] = date("Y-m-d H:i:s",$val["coupon_start_time"]);
            $data[$key]["coupon_end_time"] = date("Y-m-d H:i:s",$val["coupon_end_time"]);
            $data[$key]["coupon_after_price"] = $val["coupon_after_price"]/100;
            $data[$key]["promotion_money"] = $val["promotion_money"]/100;
            $data[$key]["promotion_rate"] = ($val["promotion_rate"]/10).'%';

        }

        $SortingRules = [
            "降序","升序"
        ];
        $this->meta_title = "拼多多商品";
        $this->assign([
            "list"=>$data,
            'page'=>$Page->show(),
            "SortingRules"=>$SortingRules,
            "search"=>$search,
            "end_time"=>$end_time,
            "sorting"=>$sorting_rules

        ]);
        $this->display();
    }
}