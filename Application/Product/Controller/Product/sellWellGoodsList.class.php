<?php
/* 热销榜单数据*/
namespace Product\Controller\Product;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Product\Service\Product\ProductAction;


class sellWellGoodsList extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            if(empty($data["page_size"]) || $data["page_size"] >50){
                $data["page_size"] = 50;
            }
            if(empty($data["page"]) || $data["page"] <1){

                $data["page"] = 1;
            }
            if(empty($data["sort_type"])){
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $page = $data["page"];
            $offset = ($page-1)*$data["page_size"];
            $responseType = isset($data["response_type"]) ? $data["response_type"] : "";
            $data = ProductAction::sellWellGoodsList($data["sort_type"],$offset,$data["page_size"],$responseType);
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}