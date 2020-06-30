<?php
/* 主页菜单列表*/
namespace Product\Controller\Product;

use Common\Controller\BaseController;
use Common\Response\Response;
use Product\Service\Product\ProductAction;


class getHomeList extends BaseController {
    protected $_login = true;
    public function run(){
        try{
            $data = ProductAction::getHomeList();
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}