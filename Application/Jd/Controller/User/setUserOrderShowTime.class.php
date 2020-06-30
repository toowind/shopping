<?php
/* 保存用户信息*/

namespace Jd\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\User\UserAction;


class setUserOrderShowTime extends BaseController
{
    public function run() {
        try {

            $uid = $GLOBALS['userId'];
            $data = UserAction::setUserOrderShowTime($uid);

            Response::outPut($data);
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

}