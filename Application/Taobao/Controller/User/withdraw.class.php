<?php
/* 提现*/

namespace Taobao\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;
use Taobao\Service\User\UserAction;
use Order\Model\JdUserModel;


class withdraw extends BaseController
{
    public function run() {
        try {
            $uid = $GLOBALS['userId'];
            $jdUserModel = new JdUserModel();
            $userInfo = $jdUserModel->getUserData($uid);
            $money = $userInfo['now_money'] ?: 0;
            $money = $money * 100; //元 -》分
            if (!money) {
                Response::outPut([], '提现失败,余额不足', 1);
            }

            $result_w = UserAction::withdraw($uid, $money);
            if($result_w !== true){
                Response::outPut([], $result_w[0], $result_w[1]);
            }

            $score = 100 * $money; // 分 -》青豆
            $params['score'] = $score;
            $params['uid'] = $uid;
            $param_data['param'] = think_encrypt(json_encode($params), "QGLGKU");

            $url = YOUTH_API.'/Shop/withdraw';
            $result = $this->sendCurlPost($url, $param_data);
            $res = json_decode($result[1], 1);
            if($res){
                if($res['code'] == 1){
                    Response::outPut($res, $res['msg'], 0);
                } else {
                    Response::outPut($res, $res['msg'], '1'.$res['code']);
                }
            } else {
                Response::outPut($param_data, '请求超时', '1120');
            }
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

}