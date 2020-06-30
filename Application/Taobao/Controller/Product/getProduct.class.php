<?php
/* 商品详情页*/
namespace Taobao\Controller\Product;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Taobao\Service\Product\ProductAction;

class getProduct extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            if(empty($data["goods_id"])){
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $data = ProductAction::getProduct($data);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}