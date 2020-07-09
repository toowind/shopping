<?php
/* 小程序静默授权*/

namespace Jd\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\User\UserAction;

class wechatStart extends BaseController
{
    public function run() {
        try {
            $data = self::$_param;
            $code = empty($data["code"]) ? '' : $data["code"];
            if (!$code) {
                $this->ajaxReturn(array('status' => 0, 'code' => '100044', 'msg' => 'code不存在'));
                Response::outPutFail(1, 'code不存在');
            }

            $result = UserAction::wechatStart($code);
            if (isset($result['code']) && $result['code'] == 0) {
                Response::outPut($result['data']);
            } else {
                Response::outPutFail($result['code'], $result['msg']);
            }
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

}