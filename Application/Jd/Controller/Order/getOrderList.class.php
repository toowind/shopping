<?php
/* 订单列表*/

namespace Jd\Controller\Order;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\Order\OrderAction;


class getOrderList extends BaseController
{
    public function run() {
        try {
            $data = self::$_param;

            $params['uid'] = empty($data['uid']) ? 0 : (int)$data['uid'];
            $params['type'] = empty($data['type']) ? 0 : (int)$data['type']; //订单类型 1 自购 2 分享

            $params['page'] = empty($data['page']) ? 1 : (int)$data['page'];
            $params['page_size'] = empty($data['page_size']) ? 8 : (int)$data['page_size'];
            $params['status'] = empty($data['status']) ? 0 : (int)$data['status']; //订单状态 1 已付款；2 已完成；3 已结算；4 已失效
            $params['start'] = empty($data['start_date']) ? date('Y-m-d') : $data['start_date'];
            $params['end'] = empty($data['end_date']) ? date('Y-m-d') : $data['end_date'];

            $data = OrderAction::getOrderList($params);
            Response::outPut($data);
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
        $date = empty($data['date']) ? 0 : $data['date']; //年月
        if ($day) {
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
        } else {
            $start = date("Y-m-01", strtotime($date)); //2020-04-01
            $end = date("Y-m-d", strtotime("$start +1 month")); //2020-05-01
        }

        $result['start'] = $start;
        $result['end'] = $end;
        return $result;
    }

}