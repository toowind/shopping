<?php
/* 精品特价秒杀*/
namespace Product\Controller\Product;

use Common\Controller\BaseController;
use Common\Response\Response;
use Product\Service\Product\ProductAction;


class getSeckillInfo extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            $data = ProductAction::getSeckillInfo($data);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}