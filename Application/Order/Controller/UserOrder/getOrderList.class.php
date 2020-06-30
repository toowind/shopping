<?php
/* 获取订单列表*/
namespace Order\Controller\UserOrder;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Order\Service\Order\OrderAction;
use Think\Log;


class getOrderList extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            if(!isset($data["page"]) || !isset($data["page_size"])|| !isset($data["type"])){
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $ResponseData = OrderAction::getOrderList($data);
            Response::outPut($ResponseData);
        }catch (\Exception $e){
            $msg = array("code"=>$e->getCode(),"msg"=>$e->getMessage());
            Log::write($msg,"ERROR");
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}