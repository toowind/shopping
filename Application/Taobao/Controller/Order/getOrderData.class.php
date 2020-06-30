<?php
/* 订单汇总*/

namespace Taobao\Controller\Order;

use Common\Controller\BaseController;
use Common\Response\Response;
use Taobao\Service\Order\OrderAction;


class getOrderData extends BaseController
{
    public function run() {
        try {
            $data = self::$_param;

            $params['uid'] = empty($data['uid']) ? 0 : (int)$data['uid'];
            $params['type'] = 1; //订单类型 1 自购 2 分享

            $params['page'] = 0; //不分页
            $params['status'] = 0; //订单状态 0 全部 1 已付款；2 已完成；3 已结算；4 已失效

            $params['start'] = empty($data['start_date']) ? date('Y-m-d') : $data['start_date'];
            $params['end'] = empty($data['end_date']) ? date('Y-m-d') : $data['end_date'];

            //自购的订单数据
            $result['self'] = OrderAction::getOrderData($params);
            //分享的订单数据
            $params['type'] = 2;
            $result['share'] = OrderAction::getOrderData($params);
            //预估收入
            $params['type'] = 0;
            $result['total_income'] = OrderAction::getUserTotalIncome($params);

            $date = date('Y-m-d');
            //上月预估收入
            $params['start'] = date("Y-m-01",strtotime("$date -1 month"));
            $params['end'] = date("Y-m-01",strtotime($date));
            $result['last_month_total_income'] = OrderAction::getUserTotalIncome($params);
            //本月预估收入
            $params['start'] = date("Y-m-01",strtotime($date));
            $params['end'] = date("Y-m-01",strtotime("$date +1 month"));
            $result['month_total_income'] = OrderAction::getUserTotalIncome($params);
            Response::outPut($result);
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取开始时间和结束时间
     * @param $data
     * @return mixed
     */
    private function getDate($data) {
        $day = empty($data['day']) ? 0 : (int)$data['day']; //今天 昨天 近7天 近30天
        switch ($day) {
            default:
            case 1: //今天
                $start = $end = date('Y-m-d');
                break;
            case -1: //昨天
                $start = date('Y-m-d', strtotime("-1 day"));
                $end = date('Y-m-d');
                break;
            case 7: //近7天
                $start = date('Y-m-d', strtotime("-7 day"));
                $end = date('Y-m-d');
                break;
            case 30: //近30天
                $start = date('Y-m-d', strtotime("-30 day"));
                $end = date('Y-m-d');
                break;
        }

        $result['start'] = $start;
        $result['end'] = $end;
        return $result;
    }

}