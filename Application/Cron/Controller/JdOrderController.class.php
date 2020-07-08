<?php

namespace Cron\Controller;

use Common\Common\Cache\Redis;
use Common\Common\Manager\DI;
use Jd\Service\Common\CommonAction;
use Think\Controller;

class JdOrderController extends Controller
{
    //订单侠apikey
    private static $apikey = 'ag3U6pwUP4UbylHm97OImTlZoR01BXtD';
    //订单侠API
    private static $url = 'http://api.tbk.dingdanxia.com';
    //分销客数据库配置
    private $db_config = [
        'DB_TYPE' => 'mysql', // 数据库类型
        'DB_HOST' => '127.0.0.1', // 服务器地址
        'DB_NAME' => 'shop_fxk', // 数据库名
        'DB_USER' => 'root', // 用户名
        'DB_PWD' => '123456',  // 密码
        'DB_PORT' => '3306', // 端口
        'DB_PREFIX' => 'fxk_', // 数据库表前缀
    ];

//    //获取调用买手API的token
//    private static function get_token() {
//        DI::setShared("redis", function () {
//            $redisConf = C("redis");
//            return new Redis($redisConf);
//        });
//
//        $redis = DI::get("redis");
//        $token = $redis->get('qqbuy:token');
//        if (!$token) {
//            $RequestTokenData = array("appKey" => "JyPDsz3D", "appSecret" => "7b1daa2cae5ba5b02af9611509790ce8");
//            $tokenData = self::http_get_notoken(self::$url . '/auth/getAccessTokenForApi', $RequestTokenData);
//            $rtokenData = json_decode($tokenData, true);
//            $token = $rtokenData["token"];
//            $token = $redis->set('qqbuy:token', $token, 36000);
//        }
//        return $token;
//    }
//    private static function http_get_notoken($url, array $data = array()) {
//        if (strpos($url, '?') === false) {
//            $url .= '?' . http_build_query($data);
//        } else {
//            $url .= '&' . http_build_query($data);
//        }
//        $curl = curl_init(); // 启动一个CURL会话
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_HEADER, 0);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
//        $tmpInfo = curl_exec($curl);
//        //关闭URL请求
//        curl_close($curl);
//        return $tmpInfo;
//    }


    /**
     * 脚本每分钟执行一次
     * 从买手API获取订单
     */
    public function order() {
        $time = date('YmdHi', time() - 60); //一分钟前的时间
        $this->saveOrderData($time);
    }

    /**
     * 脚本每小时执行一次
     * 从买手API获取订单
     */
    public function orderH() {
        $time = date('YmdH', strtotime("-8 hour")); //一小时前的时间
        $this->saveOrderData($time);
    }

    public function dealOrder() {
        for($i=1;$i<=72;$i++){
            $time = date('YmdH', strtotime("-{$i} hour")); //一小时前的时间
            $this->saveOrderData($time, 1);
        }
    }
    /**
     * 当天的订单
     * 从买手API获取订单
     */
    public function orderD() {
        $time = date('Ymd');
        $this->saveOrderData($time);
    }

    public function orderAll() {
        $dates = ['20200413','20200414','20200415','20200416','20200417','20200418','20200419',
            '20200420','20200421','20200422','20200423','20200424',];
        foreach ($dates as $key => $time) {
            $this->saveOrderData($time);
        }
    }

    /**
     * 保存订单数据
     * @param string $time
     */
    private function saveOrderData($time = '', $type = 3) {

        if(empty($time)){
            $time = date('YmdH', strtotime("-1 hour")); //一小时前的时间
        }

        $url = self::$url . '/jd/order_details';

        $param = [
            'apikey'=>self::$apikey,
            'key'=>'b57733a7028b090110e11bf2d46338547edf5f6a944d1770e87c9bc2cc2b505abecaf9215aea097a',
            'time' => $time,
            'type' => $type,
            'pageNo' => 1,
            'pageSize' => 500,
        ];
        $res_data = self::http_get($url, $param);

        echo $res_data;

        $res = json_decode($res_data, true);

        $data = [];
        if ($res && !empty($res['code']) && $res['code'] == 200) {
            $data = $res['data'];
        }

        if ($data && count($data) > 0) {

            //获取后台配置的用户分佣比例
            $user_rate = CommonAction::getUserRateConfig();
            $commission_config = ['user' => $user_rate, 'platform' => 100 - $user_rate]; //佣金分配配置

            $jd_order_model = M('shop_order', 'fxk_', $this->db_config);
            $time = time();

            //订单列表
            foreach ($data as $key => $value) {

                $user_tag = $value['skuList'][0]['positionId'];
//                $user_data = explode('_', $user_tag);
//                if (empty($user_data)) {
//                    break;
//                }

                $app_source = 3; // 1 ：中青安卓客户端； 2 ：中青IOS客户端 3:小程序
                if ($user_tag>1000000000) {
                    $uid = $user_tag/100;
                    $type = 1; //自购单

                } else {
                    $uid = $user_tag;
                    $type = 2; //分销单
                }

                $validCode = $value['validCode'];

                if($validCode == 15){ // 待付款
                    continue;
                }

                $order_id = $value['orderId'];
                $order_type = 1;
                $finish_time = $value['finishTime'];
                $order_time = $value['orderTime'];
                echo 'uid:'.$user_tag.PHP_EOL;
                $order_data = [
                    'date' => date('Y-m-d', ($order_time / 1000)),
                    'uid' => $uid,
                    'type' => $type, // 1自购 2分享
                    'order_id' => $order_id,
                    'order_type' => $order_type, //订单类型 1 单品订单 2:活动订单
                    'app_source' => $app_source, //1 ：中青安卓客户端； 2 ：中青IOS客户端
                    'finish_time' => $finish_time, //订单完成时间 毫秒
                    'order_time' => $order_time, //下单时间 毫秒
                    'valid_code' => $validCode, //订单状态码
                    'status' => 0, //结算状态 0 未结算; 1 已结算 每月25日后结算
                    'add_time' => $time, //同步时间
                    'update_time' => $time, //更新时间
                    'rate_config' => json_encode(['commission_config' => $commission_config]), //佣金分配配置

                    'parent_id' => $value['parentId'] ?: 0, //父单的订单id
                    'plus' => $value['plus'] ?: 0, //下单用户是否为plus会员
                ];

                //商品
                if (count($value['skuList'])) {
                    echo '->' . __LINE__ . '<-';
                    foreach ($value['skuList'] as $k => $v) {

                        $cos_price = $v['estimateCosPrice'] * 100;
                        $goods_id = $v['skuId'];
                        $goods_name = $v['skuName'];
                        $goods_num = $v['skuNum'];
                        $goods_return_num = $v['skuReturnNum'];
                        $final_rate = $v['finalRate'];
                        $commission_rate = $v['commissionRate'];
                        $price = $v['price'] * 100;
                        $commission = $v['estimateFee'] * 100;

                        //按照佣金分配比例，得出用户和平台的佣金
                        $commission_data = $this->getCommission($commission, $commission_config['user']);
                        $commission_user = $commission_data['commission_user']; //分给用户的佣金
                        $commission_platform = $commission - $commission_user; //分给平台的佣金

                        $total_commission_user = $commission_user * ($goods_num - $goods_return_num);
                        $total_commission_platform = $commission_platform * ($goods_num - $goods_return_num);


                        $goods_data = [
                            'cos_price' => $cos_price, //支付金额 单位分
                            'goods_id' => $goods_id,
                            'goods_name' => $goods_name,
                            'goods_num' => $goods_num,
                            'goods_return_num' => $goods_return_num, //退货数量
                            'commission_rate' => $commission_rate, //佣金比例
                            'final_rate' => $final_rate, //最终比例
                            'price' => $price, //商品价格 分
                            'commission' => $commission, //佣金
                            'commission_user' => $commission_user, //分给用户的佣金 单位分
                            'commission_platform' => $commission_platform, //分给平台的佣金 单位分
                            'total_commission_user' => $total_commission_user, //分给平台的总佣金 单位分
                            'total_commission_platform' => $total_commission_platform, //分给平台的总佣金 单位分
                            'note_commission' => $cos_price * ($commission_rate * 0.01) * ($final_rate * 0.01),

                            'pop_id' => $value['popId'] ?: 0, //店铺ID
//                            'is_pg_order' => $v['isPgOrder'], //是否拼购订单
//                            'is_red_packet_order' => $v['isRedPacketOrder'], //是否使用红包订单
                        ];

                        if($validCode == 3){
                            $goods_data['status'] = -1;
                        }

                        //订单号、商品id、订单状态码、退货数量
                        $unique_key = md5($order_id.$goods_id.$validCode.$goods_return_num);

                        $_order_res = $jd_order_model->where(['order_id' => $order_id, 'goods_id' => $goods_id])->find();
                        if (!$_order_res) {
                            echo '->' . __LINE__ . '<-';
                            $add_data = array_merge($order_data, $goods_data);
                            $jd_order_model->add($add_data);
                            echo '完成';
                        } else {
                            echo '->' . __LINE__ . '<-';

                            $user_rate_data = json_decode($_order_res['rate_config'], 1);
                            $_user_rate = empty($user_rate_data['commission_config']['user']) ? 0 : $user_rate_data['commission_config']['user'];
                            //按照佣金分配比例，得出用户和平台的佣金
                            $commission_data = $this->getCommission($commission, $_user_rate);
                            $commission_user = $commission_data['commission_user']; //分给用户的佣金
                            $commission_platform = $commission - $commission_user; //分给平台的佣金

                            $total_commission_user = $commission_user * ($goods_num - $goods_return_num);
                            $total_commission_platform = $commission_platform * ($goods_num - $goods_return_num);
                            $update_goods_data = [
                                'goods_return_num' => $goods_return_num, //退货数量
                                'commission_user' => $commission_user, //分给用户的佣金 单位分
                                'commission_platform' => $commission_platform, //分给平台的佣金 单位分
                                'total_commission_user' => $total_commission_user, //分给平台的总佣金 单位分
                                'total_commission_platform' => $total_commission_platform, //分给平台的总佣金 单位分

                                'valid_code' => $validCode,
                                'update_time' => $time,
                            ];
                            if ($finish_time) {
                                $update_goods_data['finish_time'] = $finish_time;
                            }
                            if($validCode == 3){
                                $update_goods_data['status'] = -1;
                                $update_goods_data['settle_time'] = time();
                            }

                            //订单号、商品id、订单状态码、退货数量
                            $_order_unique_key = md5($_order_res['order_id'] . $_order_res['goods_id'] . $_order_res['valid_code'] . $_order_res['goods_return_num']);
                            if ($unique_key != $_order_unique_key) {
                                $jd_order_model->where(['order_id' => $order_id, 'goods_id' => $goods_id])->save($update_goods_data);
                                echo '更新完成';
                            } else {
                                echo '没有更新';
                            }

                        }
                    }
                }
            }
        } else {
            echo '没有数据';
        }
    }

    /**
     * 根据比例获取用户和平台的佣金
     * @param int $commission
     * @param int $user_rate
     * @return array
     */
    private function getCommission($commission = 0, $user_rate = 0) {
        //按照佣金分配比例，得出用户和平台的佣金
        $commission_user = number_format($commission * ($user_rate / 100), 2); //分给用户的佣金
        $commission_platform = $commission - $commission_user; //分给平台的佣金

        return [
            'commission_user' => $commission_user,
            'commission_platform' => $commission_platform,
        ];
    }

    //发送get请求
    private static function http_get($url, array $data = array(), $is_post = 0) {
//        $headers[] = "token: " . self::get_token();
        if (!$is_post) {
            if (strpos($url, '?') === false) {
                $url .= '?' . http_build_query($data);
            } else {
                $url .= '&' . http_build_query($data);
            }
        }

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($is_post) {
            //声明使用POST方式来进行发送
            curl_setopt($curl, CURLOPT_POST, 1);
            //发送什么数据呢
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = "Content-Type: application/json";
            $headers[] = 'Content-Length: ' . strlen(json_encode($data));
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $tmpInfo = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;
    }

    /**
     * 订单结算
     */
    public function balance() {

        //每月25号之后执行结算操作
//        if (date('d') < 25) {
//            return;
//        }

        //上个月的月份
//        $month = date('Y-m-d', strtotime(" -1 month")); //比如 当前月份是4月
//        $start = date("Y-m-01", strtotime($month)); //比如 2020-03-01
//        $end = date("Y-m-01", strtotime("$start +1 month")); //比如 2020-04-01

//        $start = date("Y-m-01");
        $end = date("Y-m-01", strtotime(" +1 month"));

        $jd_order_model = M('shop_order', 'fxk_', $this->db_config);
        $user_model = M('user', 'fxk_', $this->db_config);
        $user_money_log_model = M('user_money_log', 'fxk_', $this->db_config);
//        $where['date'] = array('egt', $start); //大于等于
        $where['date'] = array('lt', $end); //小于
        $where['status'] = 0; //未结算
        $order_list = $jd_order_model->where($where)->limit(100)->select();
        foreach ($order_list as $key => $value) {
            //订单状态码 17:已完成、18:已结算
            if (in_array($value['valid_code'], [17, 18])) { //结算
                $jd_order_model->startTrans();
                $m1 = $jd_order_model->where(['id' => $value['id']])->save(['status' => 1, 'update' => time(), 'settle_time' => time()]);
                $now_money = $value['total_commission_user'] / 100;
                $save = [
                    'now_money' => ['exp', 'now_money+' . $now_money],
                    'settle_time' => date('Y-m-d'),
                ];
                $m2 = $user_model->where(['uid' => $value['uid']])->save($save);
                $m3_save = [
                    'uid' => $value['uid'],
                    'money' => $value['total_commission_user'],
                    'time' => time(),
                ];
                $m3 = $user_money_log_model->add($m3_save);
                if ($m1 && $m2 && $m3) {
                    $jd_order_model->commit();
                    echo '完成' . "\r\n";
                } else {
                    $jd_order_model->rollback();
                    echo '回退' . "\r\n";
                }
            } else { //标记失效
                $jd_order_model->where(['id' => $value['id']])->save(['status' => -1, 'update' => time(), 'settle_time' => time()]);
                echo '失效' . "\r\n";
            }
        }
    }

    /**
     * 财务统计 每天
     */
    public function financeDayCount() {

        $date = date('Y-m-d', strtotime("-1 day")); //一天前

        $jd_order_model = M('shop_order', 'fxk_', $this->db_config);
        $count_model = M('finance_day_count', 'fxk_', $this->db_config);

        $where['date'] = $date;
        $order_list = $jd_order_model->where($where)->select();

        $total_commission = $total_out = $commission_platform = $commission_user = 0;
        $user_num = $user_self_num = $user_share_num = $order_num = $effective_order_num = 0;
        $userArr = $selfArr = $shareArr = $orderArr = $effectiveOrderArr = [];
        foreach ($order_list as $key => $value) {
            //总人数
            if (!in_array($value['uid'], $userArr)) {
                $user_num += 1;
                $userArr[] = $value['uid'];
            }

            //自购人数
            if ($value['type'] == 1 && !in_array($value['uid'], $selfArr)) {
                $user_self_num += 1;
                $selfArr[] = $value['uid'];
            }

            //分销人数
            if ($value['type'] == 2 && !in_array($value['uid'], $shareArr)) {
                $user_share_num += 1;
                $shareArr[] = $value['uid'];
            }

            //订单总数
            if (!in_array($value['order'], $orderArr)) {
                $order_num += 1;
                $orderArr[] = $value['order'];
            }

            //有效订单总数
            if (in_array($value['valid_code'], [16, 17, 18]) && !in_array($value['order'], $effectiveOrderArr)) {
                $effective_order_num += 1;
                $effectiveOrderArr[] = $value['order'];
            }

            //佣金预计总收入
            $total_commission += $value['commission'];

            //退单总金额
            if (!in_array($value['valid_code'], [16, 17, 18])) {
                $total_out += $value['commission'];
            } else {
                //有效订单平台和用户佣金
                $commission_platform += $value['total_commission_platform'];
                $commission_user += $value['total_commission_user'];
            }
        }

        //查询"昨天"的总人数
        $t_user_num_res = $count_model->order('date desc')->find();
        if($t_user_num_res['date'] != $date){
            $t_user_num = empty($t_user_num_res['user_num']) ? 0 : $t_user_num_res['user_num'];
            $data['user_num'] = $user_num + $t_user_num; //总人数
        } else {
            $data['user_num'] = $user_num; //总人数
        }

        $data['date'] = $date;
        $data['user_self_num'] = $user_self_num; //自购人数
        $data['user_share_num'] = $user_share_num; //分销人数
        $data['order_num'] = $order_num; //订单总数
        $data['effective_order_num'] = $effective_order_num; //有效订单总数
        $data['total_commission'] = $total_commission; //佣金预计总收入
        $data['commission_platform'] = $commission_platform; //平台佣金收入
        $data['commission_user'] = $commission_user; //用户分佣收入
        $data['avg_user'] = number_format($commission_user / $effective_order_num, 2); //用户均单收入
        $data['avg_platform'] = number_format($commission_platform / $effective_order_num, 2); //平台均单收入

        $res = $count_model->where(['date' => $date])->find();
        if (!$res) {
            $count_model->add($data);
        } else {
            $count_model->where(['date' => $date])->save($data);
        }

        echo '完成';
    }

    /**
     * 财务统计 每月28号执行 处理上个月的数据
     */
    public function financeMonthCount() {

        //上个月的月份
//        $month = date('Y-m-d', strtotime(" -1 month")); //比如 当前月份是4月
        $month = date('Y-m-d'); //比如 当前月份是4月
        $start = date("Y-m-01", strtotime($month)); //比如 2020-03-01
        $end = date("Y-m-01", strtotime("$start +1 month")); //比如 2020-04-01

        $count_model = M('finance_month_count', 'fxk_', $this->db_config);

        //参与总人数
        $sql = "SELECT count(*) as user_num FROM(select uid FROM `fxk_shop_order` WHERE `date` < '{$end}' GROUP BY `uid`) temp";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['user_num'] = empty($result[0]['user_num']) ? 0 : $result[0]['user_num'];

        //自购人数
        $sql = "SELECT count(*) as user_self_num FROM(select uid FROM `fxk_shop_order` WHERE `type` = 1 AND `date` >= '{$start}' AND `date` < '{$end}' GROUP BY `uid`) temp";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['user_self_num'] = empty($result[0]['user_self_num']) ? 0 : $result[0]['user_self_num'];

        //分销人数
        $sql = "SELECT count(*) as user_share_num FROM(select uid FROM `fxk_shop_order` WHERE `type` = 2 AND `date` >= '{$start}' AND `date` < '{$end}' GROUP BY `uid`) temp";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['user_share_num'] = empty($result[0]['user_share_num']) ? 0 : $result[0]['user_share_num'];

        //订单总数
        $sql = "SELECT count(*) as order_num FROM(select order_id FROM `fxk_shop_order` WHERE `date` >= '{$start}' AND `date` < '{$end}' GROUP BY `order_id`) temp";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['order_num'] = empty($result[0]['order_num']) ? 0 : $result[0]['order_num'];

        //有效订单总数
        $sql = "SELECT count(*) as effective_order_num FROM(select order_id FROM `fxk_shop_order` WHERE `status` = 1 AND `date` >= '{$start}' AND `date` < '{$end}' GROUP BY `order_id`) temp";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['effective_order_num'] = empty($result[0]['effective_order_num']) ? 0 : $result[0]['effective_order_num'];

        //佣金实际总收入
        $sql = "select sum(commission) AS total_commission from `fxk_shop_order` where `date` >= '{$start}' AND `date` < '{$end}'";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['total_commission'] = empty($result[0]['total_commission']) ? 0 : $result[0]['total_commission'];

        //佣金总退单
        $sql = "select sum(commission) AS total_out from `fxk_shop_order` where `status` = -1 AND `date` >= '{$start}' AND `date` < '{$end}'";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['total_out'] = empty($result[0]['total_out']) ? 0 : $result[0]['total_out'];

        //用户分佣实际结算
        $sql = "select sum(total_commission_user) AS commission_user from `fxk_shop_order` where `status` = 1 AND `date` >= '{$start}' AND `date` < '{$end}'";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['commission_user'] = empty($result[0]['commission_user']) ? 0 : $result[0]['commission_user'];

        //用户分佣实际结算
        $sql = "select sum(total_commission_platform) AS commission_platform from `fxk_shop_order` where `status` = 1 AND `date` >= '{$start}' AND `date` < '{$end}'";
        $result = M('shop_order', 'fxk_', $this->db_config)->query($sql);
        $data['commission_platform'] = empty($result[0]['commission_platform']) ? 0 : $result[0]['commission_platform'];

        //日期
        $data['date'] = $start;

        $res = $count_model->where(['date' => $data['date']])->find();
        if (!$res) {
            $count_model->add($data);
        } else {
            $count_model->where(['date' => $data['date']])->save($data);
        }
        echo '完成';
    }
}
