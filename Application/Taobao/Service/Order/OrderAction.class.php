<?php
namespace Taobao\Service\Order;

use Common\Action\BaseAction;
use Order\Model\JdOrderModel;

class OrderAction extends BaseAction
{

    /**
     * 订单列表
     * @param array $paramData
     * @return mixed
     */
    public static function getOrderList($paramData = array()) {

        $uid = empty($paramData['uid']) ? 0 : $paramData['uid'];
        $type = empty($paramData['type']) ? 0 : $paramData['type'];
        $start = empty($paramData['start']) ? date('Y-m-d') : $paramData['start'];
        $end = empty($paramData['end']) ? date('Y-m-d') : $paramData['end'];
        $page = empty($paramData['page']) ? 0 : $paramData['page'];
        $page_size = empty($paramData['page_size']) ? 10 : $paramData['page_size'];
        $status = empty($paramData['status']) ? 0 : $paramData['status'];

        $jdOrderModel = new JdOrderModel();
//        $result['list'] = $jdOrderModel->getOrderListByDate($uid, $type, $start, $end, $page, $page_size, $status);
        $result['list'] = self::orderList($uid, $type, $start, $end, $page, $page_size, $status);
        $result['total_income'] = $jdOrderModel->getUserTotalIncomeByDate($uid, $type, $start, $end, $status);

        return $result;
    }

    private static function orderList($uid, $type, $start, $end, $page, $page_size, $status){
        $jdOrderModel = new JdOrderModel();
        $list = $jdOrderModel->getOrderListByDate($uid, $type, $start, $end, $page, $page_size, $status);

        $goods_ids = [];
        foreach ($list as $key => $value){
            $goods_ids[] = $value['goods_id'];
        }

        $goods = [];
        if($goods_ids){
            $goods_res = $jdOrderModel->getGoodsListByIds(1, $goods_ids);
            foreach ($goods_res as $key => $value){
                $goods[$value['bar_code']]['image'] = $value['image'];
            }
        }

        foreach ($list as $key => $value) {
            $list[$key]['image'] = empty($goods[$value['goods_id']]['image']) ? '' : $goods[$value['goods_id']]['image'];

            //用户占比
            $user_rate_data = json_decode($value['rate_config'], 1);
            $user_rate = empty($user_rate_data['commission_config']['user']) ? 0 : $user_rate_data['commission_config']['user'];

            $list[$key]['service_fee'] = 0;
            if ($value['final_rate'] < 100) {

                $p = $value['cos_price'] * ($value['commission_rate'] * 0.01);
                $list[$key]['service_fee'] = round(($p - $value['commission']) * ($user_rate * 0.01), 2);
                $list[$key]['user_rate'] = $user_rate;
            }

            //付款预估收入：实际支付金额 * 总佣金比例 * 最终比例 * 用户占比
            $expected_income = $value['cos_price'] * ($value['commission_rate'] / 100) * ($value['final_rate'] / 100) * ($user_rate / 100);
            $list[$key]['expected_income'] = number_format($expected_income, 2);

            //提成：总佣金比例 * 最终比例 * 用户占比
            $show_commission = ($value['commission_rate'] / 100) * ($value['final_rate'] / 100) * ($user_rate / 100) * 100;
            $list[$key]['show_commission'] = number_format($show_commission, 2);
        }

        return $list;
    }

    /**
     * 获取用户的收益
     * @param array $paramData
     * @return mixed
     */
    public static function getUserTotalIncome($paramData = array()) {
        $uid = empty($paramData['uid']) ? 0 : $paramData['uid'];
        $type = empty($paramData['type']) ? 0 : $paramData['type'];
        $start = empty($paramData['start']) ? date('Y-m-d') : $paramData['start'];
        $end = empty($paramData['end']) ? date('Y-m-d') : $paramData['end'];

        $jdOrderModel = new JdOrderModel();
        return $jdOrderModel->getUserTotalIncomeByDate($uid, $type, $start, $end);
    }

    /**
     * 订单汇总
     * @param array $paramData
     * @return mixed
     */
    public static function getOrderData($paramData = array()) {

        $uid = empty($paramData['uid']) ? 0 : $paramData['uid'];
        $type = empty($paramData['type']) ? 0 : $paramData['type'];
        $start = empty($paramData['start']) ? date('Y-m-d') : $paramData['start'];
        $end = empty($paramData['end']) ? date('Y-m-d') : $paramData['end'];
        $page = 0;
        $page_size = empty($paramData['page_size']) ? 10 : $paramData['page_size'];
        $status = 0;

        $jdOrderModel = new JdOrderModel();
        $res_data = $jdOrderModel->getOrderListByDate($uid, $type, $start, $end, $page, $page_size, $status);


        $total_order = $live_order = $price = $income = 0;
        foreach ($res_data as $key => $value){
            $total_order++;
            if($value['valid_code'] >= 16 && $value['status'] != -1){
                $price = $price + $value['cos_price'];
                $income = $income + $value['commission_user'];
                $live_order++;
            }
        }

        return [
            'total_order' => $total_order, //订单量
            'live_order' => $live_order, //有效订单量
            'price' => $price, //订单金额
            'income' => $income, //推广收益
        ];
    }


}