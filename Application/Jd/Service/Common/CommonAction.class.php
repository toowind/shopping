<?php
namespace Jd\Service\Common;

use Common\Action\BaseAction;
use Order\Model\JdOrderModel;

class CommonAction extends BaseAction
{

    /**
     * 获取用户佣金比
     * @return int
     */
    public static function getUserRateConfig() {
        $jdOrderModel = new JdOrderModel();
        return $jdOrderModel->getUserRateConfig();
    }
}