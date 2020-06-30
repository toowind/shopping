<?php
/* 主题列表*/
namespace Product\Controller\Product;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Product\Service\Product\ProductAction;


class getThemeGoodsList extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            if(!isset($data["theme_id"])){
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $data = ProductAction::getThemeGoodsList($data);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}