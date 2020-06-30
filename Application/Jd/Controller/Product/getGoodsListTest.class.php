<?php
/* 商品列表*/
namespace Jd\Controller\Product;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\Product\ProductAction;


class getGoodsListTest extends BaseController {
    public function run(){
        try{

            $data = self::$_param;
            if(empty($data["page_size"]) || $data["page_size"] >50) $data["page_size"] = 50;
            if(empty($data["page"]) || $data["page_size"] <1) $data["page_size"] = 10;
            $data = ProductAction::getGoodsListTest($data);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}