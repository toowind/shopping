<?php
/* 主题列表*/
namespace Product\Controller\Product;

use Common\Controller\BaseController;
use Common\Response\Response;
use Product\Service\Product\ProductAction;


class getThemeList extends BaseController {
    public function run(){
        try{
            $data = self::$_param;
            $page = $data["page"] < 1 ? 1 : $data["page"];
            $page_size = $data["page_size"] >4 ? 4 : $data["page_size"];
            $ResponseData = ProductAction::getThemeList($page,$page_size);
            Response::outPut($ResponseData);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}