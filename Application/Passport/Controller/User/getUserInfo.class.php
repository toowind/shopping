<?php
/* 收藏接口*/
namespace Passport\Controller\User;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;

use Passport\Service\User\CollectionAction;
use Think\Log;

class getUserInfo extends BaseController {
    protected $_login = true;
    public function run(){
        try{
            $ResponseData = $GLOBALS["userInfo"];
            Response::outPut($ResponseData);
        }catch (\Exception $e){
            $msg = array("code"=>$e->getCode(),"msg"=>$e->getMessage());
            Log::write($msg,"ERROR");
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}