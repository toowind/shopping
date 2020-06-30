<?php
/**
 * 拼多多订单表
 */
namespace Order\Model;
use Think\Model;

class PddOrderModel extends Model{
    protected $trueTableName  = "shop_pdd_order";
    //获取订单信息
    public function getOrderList($page,$pageSize,$orderState){
        $offset = ($page-1)*$pageSize;
        $orderState = implode(",",$orderState);
        $field = "order_sn,goods_id,goods_name,goods_thumbnail_url,order_create_time,order_status,promotion_amount_true";

        $sql = "select $field from ".$this->trueTableName." where uid = ".$GLOBALS["userId"]." and platformId = ".$GLOBALS["platformId"]." and order_status in ($orderState)  order by id desc limit $offset,$pageSize";
        $data = $this->query($sql);
        return $data;
    }
    //查询某个用户的总共返利金额
    public function getPromotionAmountTrueByUserId(){
        $sql = 'select sum(promotion_amount_true) as money from shop_pdd_order where uid = '.$GLOBALS["userId"]." and platformId = ".$GLOBALS["platformId"]." and rebate_status = 1";
        return $this->query($sql);
    }
    //查询某个状态的返利金额
    public function getSumMoneyByOrderState($orderState){
        $order_states = implode(",",$orderState);
        $sql = "select sum(promotion_amount_true) as money  from ".$this->trueTableName ." where  uid = ".$GLOBALS["userId"]. " and platformId = ".$GLOBALS["platformId"]. " and order_status in (".$order_states.")";
        return $this->query($sql);
    }
}