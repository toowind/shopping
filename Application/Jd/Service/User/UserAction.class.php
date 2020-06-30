<?php

namespace Jd\Service\User;

use Common\Action\BaseAction;
use Order\Model\JdUserModel;
use Order\Model\JdOrderModel;

class UserAction extends BaseAction
{

    /**
     * 获取结算弹窗数据
     * @param array $paramData
     * @return mixed
     */
    public static function getBalanceLayer($paramData = array()) {


        $uid = empty($paramData['uid']) ? 0 : $paramData['uid'];

        $jdUserModel = new JdUserModel();
        $userInfo = $jdUserModel->getUserData($uid);
        $settle_time = empty($userInfo['settle_time']) ? '2020-01-01' : $userInfo['settle_time'];
        $order_show_time = empty($userInfo['order_show_time']) ? '2020-01-01' : $userInfo['order_show_time'];

        $is_show = 0;
        $show_start_date = '';
        $show_end_date = '';
        if($settle_time > $order_show_time){
            $jdOrderModel = new JdOrderModel();
            $order = $jdOrderModel->getUserOrder($uid, $order_show_time);

            $show_start_date = $order['start_date'];
            $show_end_date = $order['end_date'];
            if(empty($show_start_date) && empty($show_end_date)){
                $jdUserModel->updateOrderShowTime($uid);
            } else {
                $is_show = 1;
            }
        }


        $show_date = '';
        if ($is_show) {

            $current_year = date('Y');

            $start = '';
            if($show_start_date){
                $year = date('Y', strtotime($show_start_date));
                if ($current_year == $year) {
                    $start = date('n', strtotime($show_start_date));
                } else {
                    $start = date('Y-m', strtotime($show_start_date));
                }
            }

            $year = date('Y', strtotime($show_end_date));
            if ($current_year == $year) {
                $end = date('n', strtotime($show_end_date));
            } else {
                $end = date('Y-m', strtotime($show_end_date));
            }

            if (empty($start)) {
                $show_date = ' ' . $end . '月 ';
            } else if ($start == $end) {
                $show_date = ' ' . $start . '月 ';
            } else {
                $show_date = ' ' . $start . ' ~ ' . $end . '月 ';
            }
        }

        $result['is_show'] = $is_show;
        $result['show_date'] = $show_date;

        $result['money'] = empty($userInfo['now_money']) ? 0 : $userInfo['now_money'] * 100;

        return $result;
    }

    /**
     * 检查用户是否存在
     * 如果不存在，保存用户信息
     * @param $uid
     * @param $nickname
     * @param $avatar
     */
    public function checkUserBaseInfo($uid, $nickname, $avatar) {
        $jdUserModel = new JdUserModel();
        if (!$jdUserModel->getUserData($uid)) {
            $data = [
                'uid' => $uid,
                'nickname' => $nickname,
                'avatar' => $avatar,
                'add_time' => time(),
            ];
            $jdUserModel->setUserData($data);
        }
    }

    /**
     * 保存用户订单结算显示时间
     * @param int $uid
     * @return bool
     */
    public function setUserOrderShowTime($uid = 0){
        $jdUserModel = new JdUserModel();
        $jdUserModel->updateOrderShowTime($uid);
        return true;
    }

    /**
     * 提现
     * @param $uid
     * @param $money 单位 分
     * @return array|bool
     */
    public function withdraw($uid, $money) {
        if (!$money) {
            return ['提现失败,余额不足', 1];
        }

        $jdUserModel = new JdUserModel();
        $result = $jdUserModel->withdraw($uid, $money);
        if($result){
            return true;
        }
        return ['提现失败,操作失败', 1];
    }



}