<?php
/* 订单列表*/

namespace Jd\Controller\Order;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\Order\OrderAction;


class getOrderSettled extends BaseController
{
    public function run() {
        try {
            $data = self::$_param;

            $params['uid'] = empty($data['uid']) ? 0 : (int)$data['uid'];
            $params['type'] = empty($data['type']) ? 0 : (int)$data['type']; //订单类型 1 自购 2 分享
            $params['page'] = 1;
            $params['page_size'] = 1;
            $params['status'] = 3; //订单状态 1 已付款；2 已完成；3 已结算；4 已失效

            $start_end_date = $this->getDate($data);
            $params = array_merge($params, $start_end_date);

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
        $date = empty($data['date']) ? 0 : (int)$data['date']; //年月
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