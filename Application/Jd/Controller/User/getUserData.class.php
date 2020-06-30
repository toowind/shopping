<?php
/* 用户信息*/

namespace Jd\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\Common\CommonAction;
use Jd\Service\User\UserAction;


class getUserData extends BaseController
{
    public function run() {
        try {

            $data['userInfo'] = $GLOBALS['userInfo'];
            //结算提示
            $data['balance_layer'] = UserAction::getBalanceLayer(['uid' => $data['userInfo']['uid']]);
            $data['money'] = $data['balance_layer']['money'];

            $data['config'] = CommonAction::getUserRateConfig();

            //检查用户信息是否保存数据库
            $uid = $data['userInfo']['uid'] ?: 0;
            $nickname = $data['userInfo']['nickname'] ?: '';
            $avatar = $data['userInfo']['avatar'] ?: '';
            if($uid){
                UserAction::checkUserBaseInfo($uid, $nickname, $avatar);
            }

            Response::outPut($data);
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

}