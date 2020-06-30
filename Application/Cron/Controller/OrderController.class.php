<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/16/016
 * Time: 20:21
 */
namespace Cron\Controller;
use Admin\Model\ConfigModel;
use Common\Common\Cache\Redis;
use Common\Common\Manager\DI;
use Common\Common\Rpc\Http\HttpClient;
use Think\Controller;
use Think\Log;

class PddscriptController extends Controller {
    private $url = 'https://gw-api.pinduoduo.com/api/router';
    private $client_id = '';
    private $client_secret  ='';
    private $platform = '';
    private static $seckillGoodsIdList = [];
    private static $v = 0;
    private static $seckill = 0;
    public function run(){
        set_time_limit(0);
        //后台设置佣金比
        $ConfigModel = new ConfigModel();
        $config = $ConfigModel->getValueByName(["RETURN_COMMISSION_RATE","SECKILL_COMMISSION_RATE","ONE_SECKILL","TEN_SECKILL","CLEARANCE_PRICE"]);

        $config = array_column($config,NULL,"name");
        $goodsIdStr = $config["ONE_SECKILL"]["value"].','.$config["TEN_SECKILL"]["value"].','.$config["CLEARANCE_PRICE"]["value"];
        $seckillGoodsIdList = explode(",",$goodsIdStr);
        if(empty($seckillGoodsIdList)){
            Log::write("后台读取秒杀商品异常","ERROR");
            exit("后台读取秒杀商品异常");
        }

        self::$seckillGoodsIdList = $seckillGoodsIdList;

        $v = bcdiv($config["RETURN_COMMISSION_RATE"]["value"],100,2);
        self::$v = $v;
        $seckill = bcdiv($config["SECKILL_COMMISSION_RATE"]["value"],100,2);
        self::$seckill = $seckill;

        $pddConfig = C("pdd_config");
        DI::setShared("redis",function (){
            $redisConf = C("redis");
            return new Redis($redisConf);
        });
        foreach($pddConfig as $key=>$val){
            $this->client_id = $val["client_id"];
            $this->client_secret = $val["client_secret"];
            $this->platform = $val["platform"];
            $this->cron_order_list();

        }
//        $this->updateOrderNum();
    }
    //更新订单数量统计表----修数据
    private function updateOrderNum(){
        $pddOrderModel = M("pdd_order");
        $userCollectionStationStatisticsModel = M("user_collection_statistics");
            $data = $pddOrderModel->field(["platformId","goods_id","goods_name"])->select();
            echo "<pre>";
            foreach ($data as $key=>$val){
                $res = $userCollectionStationStatisticsModel->where(["platformId"=>$val["platformid"],"goods_id"=>$val["goods_id"]])->find();
                if(!$res){
                    echo '新增';
                    $time = time();
                    $obj = $userCollectionStationStatisticsModel->add(
                        ["platformId"=>$val["platformid"],
                            "goods_id"=>$val["goods_id"],
                            "num"=>0,"update_time"=>$time,"create_time"=>$time,"order_num"=>1,
                            "goods_name"=>$val["goods_name"]
                        ]);
                }else{
                    echo "更新";
                    $order_num = $res["order_num"] +1;
                    $obj = $userCollectionStationStatisticsModel->where(["platformId"=>$val["platformid"],"goods_id"=>$val["goods_id"]])->save(["order_num"=>$order_num]);
                }
                print_r($res);
                echo "sql:".$userCollectionStationStatisticsModel->getLastSql();
                var_dump($obj);
            }

            //更新收藏数量
            $userCollectionModel = M("user_collection");
             $userCollectionStationStatisticsModel = M("user_collection_statistics");
            $data = $userCollectionModel->field(["goods_id","platformId"])->select();
            foreach($data as $key =>$val){
                $res = $userCollectionStationStatisticsModel->where(["platformId"=>$val["platformid"],"goods_id"=>$val["goods_id"]])->find();
                if(empty($res)){
                    //查询商品名称
                    $data = array(
                        "goods_id_list"=>'['.$val["goods_id"].']'
                    );
                    $para = $this->base_para('pdd.ddk.goods.detail',$data);
                    $goods_info = $this->get_curl_post($this->url,$para);
                    $goods_name = isset($goods_info["goods_detail_response"]["goods_details"][0]["goods_name"]) ? $goods_info["goods_detail_response"]["goods_details"][0]["goods_name"] : "";
                   if($goods_name == ""){
                       continue;
                   }
                    $time = time();
                    echo "新增";
                    $obj = $userCollectionStationStatisticsModel->add(
                        ["platformId"=>$val["platformid"],"goods_id"=>$val["goods_id"],"num"=>1,"update_time"=>$time,
                            "create_time"=>$time,"order_num"=>0,"goods_name"=>$goods_name
                        ]);
                }else{
                    echo "更新";
                    $num = $res["num"] +1;
                    $obj = $userCollectionStationStatisticsModel->where(["platformId"=>$val["platformid"],"goods_id"=>$val["goods_id"]])->save(["num"=>$num]);
                }
                echo "sql:".$userCollectionStationStatisticsModel->getLastSql();
                var_dump($obj);
             }

            echo "结束";
    }
    /*
     * 更新订单列表
     * 计划任务
     * */
    public function cron_order_list(){
        echo "<pre>";
        $page = 1;
        $limit = 100;
        $model = M('pdd_order');
        $redis = DI::get("redis");
        $last_update_time = $redis->get('last_update_time');//最后一次同步订单时间
        $endTime = "";
        for(;;){
            $data = array(
                'start_update_time' =>strtotime("00:00:00"),
                'end_update_time'   =>NOW_TIME,
                'page_size'         =>$limit,
                'page'              =>$page,
            );
            echo "第".$page."页".PHP_EOL;
            $para = $this->base_para('pdd.ddk.order.list.increment.get',$data);
            $res = $this->get_curl_post($this->url,$para);

            if($res){
                if(empty($res["order_list_get_response"]["order_list"])){
                    print_r($res);
                    $redis->set('last_update_time',NOW_TIME);//把最后更新时间缓存
                    break;
                }
                foreach($res['order_list_get_response']['order_list'] as $key=>$val){
                    if(empty($val["custom_parameters"])){
                        continue;
                    }
                    $arr = hashids_decode($val["custom_parameters"]);
                    if(empty($arr)){
                        continue;
                    }

                    $count = count($arr);
                    if($count == 2){
                        continue;
                    }

                    $platformId = $arr[1];
                    //拼多多佣金金额
                    $return_money = bcdiv($val["promotion_amount"],100,2);

                    //计算用户所得金额 单位元
                    if(in_array($val["goods_id"],self::$seckillGoodsIdList)){
                        //秒杀商品
                        $return_money = bcmul($return_money,self::$seckill,2);
                    }else{
                        //普通商品
                        $return_money = bcmul($return_money,self::$v,2);
                    }
                    print_r($val);

                    $order = array(
                        'uid'                   =>$arr[0],
                        'type'                  =>$arr[2],
                        'order_sn'              =>$val['order_sn'],
                        'goods_id'              =>$val['goods_id'],
                        'goods_name'            =>$val['goods_name'],
                        'goods_thumbnail_url'   =>$val['goods_thumbnail_url'],
                        'goods_quantity'        =>$val['goods_quantity'],//购买商品数量
                        'goods_price'           =>$val['goods_price'],//订单sku的单件价格，单位为分
                        'order_amount'          =>$val['order_amount'],//实际支付金额，单位为分
                        'p_id'                  =>$val['p_id'],//推广位ID
                        'promotion_rate'        =>$val['promotion_rate'],//佣金比例，千分比
                        'promotion_amount'      =>$val['promotion_amount'],//佣金金额，单位为分
                        'promotion_amount_true' =>$return_money, //用户所得金额
                        'order_status'          =>$val['order_status'],//订单状态
                        'order_status_desc'     =>$val['order_status_desc'],//订单简介
                        'order_create_time'     =>$val['order_create_time'],//订单创建时间
                        'order_pay_time'        =>$val['order_pay_time'],//订单支付时间
                        'order_group_success_time'      =>$val['order_group_success_time'],//成团时间
                        'order_verify_time'     =>$val['order_verify_time'],//审核时间
                        'order_modify_at'       =>$val['order_modify_at'],//最后更新时间
                        'add_time'              =>NOW_TIME,
                        'status'                =>1,
                        'rebate_time'           =>0,
                        'rebate_status'         =>0,
                        'platformId'              =>$platformId,
                        'promotion_amount_platform'=>$val["promotion_amount"] - ($return_money*100), //平台返利金额 = 所有返利金额减去用户返利金额
                    );

                    $cond['order_sn'] = $val['order_sn'];
                    if($model->where($cond)->find()){
                        $s = $model->where($cond)->save($order);
                        echo "更新:".$s;

                    }else{
                        $s = $model->add($order);
                        echo "新增:".$s;
                        //更新收藏、下单、商品统计表
                        $this->addColectionStatistics($platformId,$val["goods_id"],$val["goods_name"]);
                    }
                    //给用户返利
                    if($s){
                        //返利金额不为0开始返利
                        if($order["promotion_amount_true"] !=0){
                            //测试环境直接触发返金币
                            if($GLOBALS["env"] == "dev" && $platformId == 1){
                                $msg = array("money" => $return_money, "uid" => $order["uid"], "order_sn" => $order["order_sn"], "platformId" => $platformId,'order_data'=>$order);
                                $obj = setDataQue("rebate", json_encode($msg));
                                if (!$obj) {
                                    Log::write(json_encode($msg), "QUE_SET_REBATE_ERROR");
                                }
                            }else{
                                if($order["order_status"] ==5) {
                                    $msg = array("money" => $return_money, "uid" => $order["uid"], "order_sn" => $order["order_sn"], "platformId" => $platformId,'order_data'=>$order);
                                    $obj = setDataQue("rebate", json_encode($msg));
                                    if (!$obj) {
                                        Log::write(json_encode($msg), "QUE_SET_REBATE_ERROR");
                                    }
                                }
                            }

                        }
                    }
                    //更新用户行为记录表
                    $this->updateUserOrder($arr[0],$platformId);

                }
            }
            $page++;//下一页数据
        }

        //更新完订单触发给用户返利
        echo '结束1';
        $this->rebate();
    }
    //更新用户行为记录表
    private function updateUserOrder($userId,$platformId){
        $time = time();
        $userOrderListModel = M("pdd_user_order");
        if($userOrderListModel->where(["uid"=>$userId,"platformId"=>$platformId])->find()){
            $newData = array();
            $newData["shopping_lately_time"] = $time;
            $newData["order_lately_time"] = $time;
            $res = $userOrderListModel->where(["uid"=>$userId,"platformId"=>$platformId])->save($newData);
        }else{
            $newData = array();
            $newData["shopping_lately_time"] = $time;
            $newData["order_lately_time"] = $time;
            $newData["uid"] = $userId;
            $newData["platformId"] = $platformId;
            $res = $userOrderListModel->add($newData);
        }
    }
    //更新收藏、下单、商品统计表
    private function addColectionStatistics($platformId,$goodsId,$goodsName){
        $userCollectionStatisticsModel = M("user_collection_statistics");
        $res = $userCollectionStatisticsModel->where(["platformId"=>$platformId,"goods_id"=>$goodsId])->find();
        if(!$res){
            $data = array();
            $time = time();
            $data["platformId"] = $platformId;
            $data["goods_id"] = $goodsId;
            $data["num"] = 0;
            $data["update_time"] = $time;
            $data["create_time"] = $time;
            $data["goods_name"] = $goodsName;
            $obj = $userCollectionStatisticsModel->add($data);
        }else{
            $order_num = $res["order_num"]+1;
            $obj = $userCollectionStatisticsModel->where(["platformId"=>$platformId,"goods_id"=>$goodsId])->save(["order_num"=>$order_num]);
        }
    }
    //返利给用户
    function rebate(){
        $pddOrderModel = M('pdd_order');
        $httpClient = new HttpClient();
        $rebate_url = C("rebate_url");

        do{
            $msg = getDataQue("rebate");
            if($msg){
                $newMsg = json_decode($msg,true);
                $arr = $pddOrderModel->where(["order_sn"=>$newMsg["order_sn"]])->find();
                if(!empty($arr)){
                    //更新订单表数据
                    $pddOrderModel->startTrans();
                    $res = $pddOrderModel->where(["order_sn"=>$newMsg["order_sn"]])->save(["rebate_status"=>1]);
                    if($res){
                        //给用户发放奖励
                        if($newMsg["platformId"] == 2){
                            //中青
                            $data = array();
                            $data = array(
                                "money"=>$newMsg["money"],
                                "uid"=>$newMsg["uid"],
                                "orderId"=>$newMsg["order_sn"],
                            );
                            $token = think_encrypt(json_encode($data),'QGLGKU',10);
                            $url = "https://kd.youth.cn/WebApi/Pdd/AddAntReward";
                            $res = $httpClient->post($url,["token"=>$token]);
                            $arr = json_decode($res["data"],true);
                            if(in_array($arr["status"],[1,107])){
                                $pddOrderModel->commit();
                            }elseif (in_array($arr["status"],[101,102,106,108,109])){
                                //101 102 参数错误   106操作频繁（并发） 108插入数据库失败  109 奖励给予失败-----需要下次重试
                                setDataQue("rebate",$msg);
                            } else{
                                //103 用户不存在 104用户异常（拉黑） 105返利金额为0  107 奖励已经被领取
                                $pddOrderModel->rollback();
                            }
                        }else{
                            //蚂蚁
                            $this->mySendMoney($pddOrderModel,$newMsg,$rebate_url,$msg);
                        }
                    }else{
                        $pddOrderModel->rollback();
                        setDataQue("rebate",$msg);
                        Log::write("返利更新数据库状态失败：".$msg,"SET_REBATE_ERROR");
                    }
                }else{
                    //订单不存在直接跳过
                }
            }else{
                //都返还完了
                $start = false;
            }
        }while($start);

        echo '结束2';
    }

    //蚂蚁用户发金币
    private function mySendMoney($pddOrderModel,$newMsg,$rebate_url,$msg){
        //蚂蚁
        $data = array();
        $data['uid'] = $newMsg["order_data"]["uid"];
        $data['order_id'] = $newMsg["order_data"]["order_sn"];
        $data['goods_name'] = $newMsg["order_data"]["goods_name"];
        $data['goods_id'] = $newMsg["order_data"]["goods_id"];
        $data['goods_price'] = $newMsg["order_data"]["goods_price"];//商品价格
        $data['goods_quantity'] = $newMsg["order_data"]["goods_quantity"];//商品数量
        $data['order_amount'] = $newMsg["order_data"]["order_amount"];//订单金额
        $data['promotion_amount_true'] = bcmul($newMsg["order_data"]["promotion_amount_true"],100);//用户所得金额
        $data['promotion_rate'] = $newMsg["order_data"]["promotion_rate"]; //佣金比
        if($data["promotion_amount_true"] == 0){
            return true;
        }
        ksort($data);
        $str = '';
        foreach ($data as $key=>$val){
            $str .= $key.'='.$val.'&';
        }
        $time = time();
        $buildString = substr($str, 0, -1).'78933y6re919ey9w7'.$time;
        $arr = $str.'sign='.md5($buildString).'&ts='.$time;
        $url = $rebate_url[$GLOBALS["env"]][$this->platform]."?".$arr;
        $httpClient = new HttpClient();
        $ResponseData = $httpClient->get($url);
        $arr = json_decode($ResponseData["data"],true);
        if(in_array($arr["status"],[1,2,3])){
            $pddOrderModel->commit();
        }elseif($arr["status"] == 0){
            //对方操作失败，进入重试
            $pddOrderModel->rollback();
            setDataQue("rebate",$msg);
            Log::write("蚂蚁返利失败，ResponseData：".json_encode($arr).'RequestData:'.$url,'REBATE_ERROR');
        }else{
            //2 用户不存在，3订单已返利，不需要进入重试
            $pddOrderModel->rollback();
            Log::write("蚂蚁返利异常，Response:".json_encode($arr.'RequestData:'.$url),'REBATE_ERROR');
        }
    }

    private function execTaskMoney($uid,$score,$goods_name){

        $score = execTask('pdd_rebate',$uid,$goods_name,'',$score*100);
        if($score){
            $pdd_income = getUserInfoCache($uid,'pdd_income');  //记录用户历史返利金额
            UpUserInfo($uid,array('pdd_income'=>$pdd_income+$score));
            //记录用户的其他任务收益
            setUserCensus($uid,'charging_pdd_rebate',$score);//会被归档消除
            setUserCensus($uid,'pdd_rebate',$score);
            //记录用户的总收益
            setUserCensus($uid,'charging_money_all',$score);//会被归档消除
            setUserCensus($uid,'money_all',$score);
            //写入用户队列
            setUserQueue($uid);
            sensors_task_commit($uid,3,'','拼多多分享赚',1,$score,true);// 神策统计
            return true;
        }
        return false;
    }
    /**
     * @param $type    //请求接口名称
     * @param $params   //额外参数
     * @return string   返回值
     */
    private function base_para($type,$params){
        //$token = $this->redis->get('pdd_user_access_token:'.$this->uid);
        $para = array(
            'type'      =>$type,
            'client_id' =>$this->client_id,
            'timestamp' =>time(),
            'data_type' =>'JSON',
            'version'   =>'V1',
            //'access_token'=>$token['access_token']
        );
        $para = array_merge($para,$params);
        $para['sign'] = $this->sign($para);
        return http_build_query($para);
    }

    /**
     * @param $para //签名的数组
     * @return string
     */
    private function sign($para){
        ksort($para);								                   //按字母升序重新排序
        $sequence = '';									                   //定义签名数列
        foreach($para as $k=>$v){		                   //拼接参数
            $sequence .= "{$k}{$v}";
        }
        $sequence = $this->client_secret.$sequence.$this->client_secret;//拼接密钥
        $sequence = strtoupper(md5($sequence));
        return $sequence;
    }
    /**
     * curl通过连接获取数据
     * @param  [type] $url   [description]
     * @param  [type] $agent [description]
     * @return [type]        [description]
     */
    private function get_curl_post($url,$param,$content_type){
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_TIMEOUT,10); // 设置超时限制防止死循环
        curl_setopt($ch,CURLOPT_POSTFIELDS,$param);
        if($content_type){
            curl_setopt($ch,CURLOPT_HTTPHEADER,$content_type);
        }

        $return=curl_exec($ch);
        $info=curl_getinfo($ch);
        curl_close($ch);
        return json_decode($return,true);
    }
}
$obj = new PddscriptController();
$obj->run();
