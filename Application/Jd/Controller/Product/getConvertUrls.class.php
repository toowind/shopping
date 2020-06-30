<?php
/* 商品详情页*/
namespace Jd\Controller\Product;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Jd\Service\Product\ProductAction;

class getConvertUrls extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            if(empty($data["url"])){
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $data = ProductAction::getConvertUrls($data);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}