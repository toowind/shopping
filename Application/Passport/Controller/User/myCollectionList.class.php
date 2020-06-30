<?php
/* 我的收藏列表*/
namespace Passport\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;
use Passport\Service\User\CollectionAction;
use Passport\Service\User\UserAction;
use Think\Log;

class myCollectionList extends BaseController {
    protected $_login = true;
    public function run(){
        try{
            $data = self::$_param;
            $ResponseData = CollectionAction::getColectionList($data);
            Response::outPut($ResponseData);
        }catch (\Exception $e){
            $msg = array("code"=>$e->getCode(),"msg"=>$e->getMessage());
            Log::write($msg,"ERROR");
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}