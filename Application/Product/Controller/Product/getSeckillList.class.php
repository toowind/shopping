<?php
/* 精品特价秒杀*/
namespace Product\Controller\Product;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Product\Service\Product\ProductAction;

class getSeckillList extends BaseController {
    private static $type = [
        "ONE_SECKILL","TEN_SECKILL","CLEARANCE_PRICE"
    ];
    public function run(){
        try{
            $data = self::$_param;
            if(!in_array($data["type"],self::$type) || empty($data["page"]) || empty($data["page_size"])){
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $page = $data["page"] < 1 ? 1 : $data["page"];
            $page_size = $data["page_size"] > 10 ? 10 : $data["page_size"];
            $page = ($page-1)*$page_size;
            $data = ProductAction::getSeckillList($data["type"],$page,$page_size);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}