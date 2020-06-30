<?php
namespace Order\Service\Order;


use Common\Action\BaseAction;
use Order\Model\PddOrderModel;

class OrderAction extends BaseAction {
    private static $orderStateMap = [
        //预估入账中
        "1" => array(0,1,2,3),
        //已结算
        "2" => array(5),
        //返利失败
        "3" => array(4),
    ];
    //获取订单列表
    public static function getOrderList($data){
        $pddOrderModel = new PddOrderModel();
        $page = $data["page"] < 1 ? 1 : $data["page"];
        $pageSize = $data["page_size"] > 50 ? 50 : $data["page_size"];
        $orderState = self::$orderStateMap[$data["type"]];
        $orderList = $pddOrderModel->getOrderList($page,$pageSize,$orderState);

        $estimate_money = $pddOrderModel->getSumMoneyByOrderState(self::$orderStateMap[1]);
        $settlement_money = $pddOrderModel->getSumMoneyByOrderState(self::$orderStateMap[2]);
        $fail_money = $pddOrderModel->getSumMoneyByOrderState(self::$orderStateMap[3]);

        //预估入账中
        $ResponseData["estimate_money"] = $estimate_money[0]["money"] ? bcdiv($estimate_money[0]["money"],1,2) : 0;
        //已结算
        $ResponseData["settlement_money"] =  $settlement_money[0]["money"] ? bcdiv($settlement_money[0]["money"],1,2) : 0;
        //返利失败
        $ResponseData["fail_money"] =  $fail_money[0]["money"] ? bcdiv($fail_money[0]["money"],1,2) : 0;
        $ResponseData["order_list"] = $orderList;
        return $ResponseData;

    }
}