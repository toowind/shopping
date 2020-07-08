<?php

namespace Jd\Service\User;

use Common\Action\BaseAction;
use Order\Model\JdUserModel;
use Order\Model\JdOrderModel;

class UserAction extends BaseAction
{

    /**
     * 静默登录
     * @param string $code
     * @return array
     */
    public function wechatStart($code = '') {
        $config = [
            'appid' => 'wxf5e4a55271562c73',
            'secret' => 'd76e6d15463b98df4528e256b926f32c',
            'grant_type' => 'authorization_code',
            'js_code' => '',
        ];
        $config['js_code'] = $code;

        $url = 'https://api.weixin.qq.com/sns/jscode2session?';
        $url .= http_build_query($config);
        list($http, $data) = get_curl_data($url);
        $data = json_decode($data, true);
        if ($http['http_code'] != 200 || empty($data['openid']) || empty($data['session_key'])) {
            return ['code' => 1, 'msg' => '执行失败'];
        }

        $params['uid'] = -1;
        $params['platformId'] = 3;
        $params['platform'] = 'zq';
        $params['device_type'] = 'mini';
        $data['token'] = think_encrypt(json_encode($params), "QGLGKU");
        $data['uid'] = -1;
        return ['code' => 0, 'msg' => 'success', 'data' => $data];
    }

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
     * @return mixed
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

    /**
     * 通过openid和unionid获取用户信息
     * 如果没有，则创建用户
     * @param array $userInfo
     * @param int $parent_id
     * @return int|mixed
     */
    public function wechatLogin($userInfo = [], $parent_id = 0) {
        $openid = empty($userInfo['openId']) ? 0 : $userInfo['openId'];
//        $unionid = empty($userInfo['unionId']) ? 0 : $userInfo['unionId'];
        $unionid = $openid;

        if (!$openid || !$unionid) {
            return 10; //失败
        }

        $params = [];
        $jdUserModel = new JdUserModel();
        $userRes = $jdUserModel->getUserDataByWechat($openid, $unionid);
        var_dump($userRes);
        die();
        if ($userRes) {
            $params = [
                'uid' => $userRes['id'],
                'nickname' => $userRes['nickname'],
                'avatar' => $userRes['avatar'],
                'channel' => 3,
                'platform' => 'zq',
                'platformId' => 3,
                'device_type' => 'mini',
            ];
        } else {
            $avatar = $userInfo['avatarUrl'];

            $url = YOUTH_API.'/Shop/uploadAvatar';
            $url .= "?unionid={$unionid}&avatar={$avatar}";
            $result = file_get_contents($url);

            $res = json_decode($result, 1);
            if($res && $res['code'] == 1){
                $avatar = $res['data'];
            }

            $data = [
                'user_id' => 0,
                'parent_id' => $parent_id,
                'nickname' => $userInfo['nickName'],
                'avatar' => $avatar,
                'openid' => $openid,
                'unionid' => $unionid,
                'channel' => 3,
                'add_time' => time(),
            ];
            $id =  $jdUserModel->setUserDataByWechat($data);
            if($id){
                $params = [
                    'uid' => $id,
                    'nickname' => $userInfo['nickName'],
                    'avatar' => $avatar,
                    'channel' => 3,
                    'platform' => 'zq',
                    'platformId' => 3,
                    'device_type' => 'mini',
                ];
            }
        }

        if($params){
            $token = think_encrypt(json_encode($params), "QGLGKU");

            $GLOBALS["userId"] = $params['uid'];
            $GLOBALS["platform"] = $params["platform"];
            $GLOBALS["platformId"] = $params["platformId"];
            $GLOBALS["token"] = $token;
            $GLOBALS["userInfo"] = $params;
            $GLOBALS["openid"] = $openid;
            $GLOBALS["unionid"] = $unionid;

            return [
                'token' => $token,
                'uid' => $params['uid'],
                'nickname' => $params['nickname'],
                'avatar' => $params['avatar']
            ];
        } else {
            return 20; //失败
        }
    }
}