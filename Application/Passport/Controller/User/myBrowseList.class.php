<?php
/* 我的足迹*/
namespace Passport\Controller\User;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;
use Passport\Service\User\BrowseAction;
use Passport\Service\User\UserAction;
use Think\Log;

class myBrowseList extends BaseController {
    protected $_login = true;
    public function run(){
        try{
            $data = self::$_param;
            $ResponseData = BrowseAction::getBrowseList($data);
            Response::outPut($ResponseData);
        }catch (\Exception $e){
            $msg = array("code"=>$e->getCode(),"msg"=>$e->getMessage());
            Log::write($msg,"ERROR");
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}