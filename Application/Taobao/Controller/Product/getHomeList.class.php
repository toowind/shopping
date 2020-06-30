<?php
/* 主页菜单列表*/
namespace Taobao\Controller\Product;

use Common\Controller\BaseController;
use Common\Response\Response;
use Taobao\Service\Product\ProductAction;


class getHomeList extends BaseController {
    protected $_login = true;
    public function run(){

        try{
            header("Access-Control-Request-Methods:GET, POST, PUT, DELETE, OPTIONS");
            header('Access-Control-Allow-Headers:x-requested-with,content-type,test-token,test-sessid');
            $data = ProductAction::getHomeList();
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}