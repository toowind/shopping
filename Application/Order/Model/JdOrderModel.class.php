<?php
/**
 * 京东分销订单表
 */

namespace Order\Model;

use Common\Common\Cache\Redis;
use Common\Common\Manager\DI;

class JdOrderModel extends BaseModel
{
    private $table = 'fxk_shop_order';

    /**
     * 根据时间获取用户的订单
     * @param int $uid
     * @param int $type
     * @param string $start
     * @param string $end
     * @param int $page 0:不分页
     * @param int $page_size
     * @param int $status
     * @return mixed
     */
    public function getOrderListByDate($uid = 0, $type = 1, $start = '', $end = '', $page = 0, $page_size = 10, $status = 0) {
        $field = 'source,date,type,order_id,goods_id,goods_name,goods_num,order_type,finish_time,order_time,cos_price,commission_rate,final_rate,price,add_time,update_time,valid_code,commission,commission_user,commission_platform,status,rate_config';

        $where = '';
        //订单状态 1 已付款；2 已完成；3 已结算；4 已失效
        if ($status == 1) {
            $where = " and `valid_code` = 16 and `status` = 0 ";
        } elseif ($status == 2) {
            $where = " and `valid_code` = 17 and `status` = 0 ";
        } elseif ($status == 3) {
            $where = " and `status` = 1 ";
        } elseif ($status == 4) {
            $where = " and `status` = -1 ";
        }

        if ($start && $start == $end) {
            if ($page) {
                $offset = ($page - 1) * $page_size;
                $sql = "select {$field} from `{$this->table}` where `uid` = {$uid}  and `type` = {$type} {$where} and `date` = '{$start}' order by id desc limit {$offset},{$page_size}";
            } else {
                $sql = "select {$field} from `{$this->table}` where `uid` = {$uid}  and `type` = {$type} {$where} and `date` = '{$start}'";
            }
        } else {
            if ($page) {
                $offset = ($page - 1) * $page_size;
                $sql = "select {$field} from `{$this->table}` where `uid` = {$uid} and `type` = {$type} {$where} and `date` >= '{$start}' and `date` < '{$end}' order by id desc limit {$offset},{$page_size}";
            } else {
                $sql = "select {$field} from `{$this->table}` where `uid` = {$uid} and `type` = {$type} {$where} and `date` >= '{$start}' and `date` < '{$end}'";
            }
        }

        return M('shop_order','fxk_',$this->db_config)->query($sql);
    }

    /**
     * @param int $source
     * @param array $goods_ids
     * @return mixed
     */
    public function getGoodsListByIds($source = 0, $goods_ids = []) {
        $field = 'source_id,bar_code,image';

        $goods_ids = implode(',', $goods_ids);

        $sql = "select {$field} from `fxk_store_product` where `source_id` = {$source}  and `bar_code` in ({$goods_ids})";
        return M('store_product','fxk_',$this->db_config)->query($sql);
    }

    /**
     * 根据时间获取用户的总收入
     * @param int $uid
     * @param int $type
     * @param string $start
     * @param string $end
     * @param int $status
     * @return mixed
     */
    public function getUserTotalIncomeByDate($uid = 0, $type = 0, $start = '', $end = '', $status = 0) {

        $where = '';
        //订单状态 1 已付款；2 已完成；3 已结算；4 已失效
        if ($status == 1) {
            $where = " and `valid_code` = 16 and `status` = 0 ";
        } elseif ($status == 2) {
            $where = " and `valid_code` = 17 and `status` = 0 ";
        } elseif ($status == 3) {
            $where = " and `status` = 1 ";
        } elseif ($status == 4) {
            $where = " and `valid_code` < 16 and `status` =0 ";
        } else {
            $where = " and `valid_code` >= 16  "; //有效订单
        }

        if ($start && $start == $end) {
            if ($type) {
                $sql = "select sum(commission_user) AS total_user_income from `{$this->table}` where `uid` = {$uid} {$where} and `valid_code` in (16,17,18) and `type` = {$type}  and `date` = '{$start}'";
            } else {
                $sql = "select sum(commission_user) AS total_user_income from `{$this->table}` where `uid` = {$uid} {$where} and `valid_code` in (16,17,18) and `date` = '{$start}'";
            }
        } else {
            if ($type) {
                $sql = "select sum(commission_user) AS total_user_income from `{$this->table}` where `uid` = {$uid} {$where} and `valid_code` in (16,17,18) and `type` = {$type} and `date` >= '{$start}' and `date` <= '{$end}'";
            } else {
                $sql = "select sum(commission_user) AS total_user_income from `{$this->table}` where `uid` = {$uid} {$where} and `valid_code` in (16,17,18) and `date` >= '{$start}' and `date` < '{$end}'";
            }
        }
        $result =  M('shop_order','fxk_',$this->db_config)->query($sql);

        return empty($result[0]['total_user_income']) ? 0 : $result[0]['total_user_income'];
    }

    /**
     * 获取用户的订单区间时间
     * @param int $uid
     * @param string $start
     * @return array
     */
    public function getUserOrder($uid = 0, $start = '') {
        $where['uid'] = $uid;
        $where['date'] = array('egt', $start); //大于等于
        $where['status'] = 1;
        $result_start = M('shop_order', 'fxk_', $this->db_config)->where($where)->order('date asc')->find();
        $start = empty($result_start['date']) ? 0 : $result_start['date'];

        $where = [];
        $where['uid'] = $uid;
        $where['status'] = 1;
        $result_end = M('shop_order', 'fxk_', $this->db_config)->where(['uid' => $uid])->order('date desc')->find();
        $end = empty($result_end['date']) ? 0 : $result_end['date'];

        return ['start_date' => $start, 'end_date' => $end];
    }

    /**
     * 获取用户佣金比
     * @return int
     */
    public function getUserRateConfig() {

        DI::setShared("redis", function () {
            $redisConf = C("redis");
            return new Redis($redisConf);
        });

        $redis = DI::get("redis");
        $result = $redis->get('fxk_user_rate_config');
        if (empty($result) && $result !== 0) {
            $res = M('system_config', 'fxk_', $this->db_config)->where(['menu_name' => 'user_rate_config'])->find();
            $result = empty($res['value']) ? 0 : (int)trim($res['value'], '"');
            if ($result < 0) {
                $result = 0;
            }

            $redis->set('fxk_user_rate_config', $result, 600);
        }

        return (int)$result;
    }
}