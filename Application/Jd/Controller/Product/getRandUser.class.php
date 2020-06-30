<?php
/* 主页菜单列表*/
namespace Jd\Controller\Product;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\Product\ProductAction;


class getRandUser extends BaseController {
    protected $_login = true;
    public function run(){
        try{
            $data = ProductAction::getRandUser();
            Response::outPut($data);
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}