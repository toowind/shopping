<?php
/* 收藏接口*/
namespace Passport\Controller\User;

use Common\Controller\BaseController;
use Common\Exception\Exception;
use Common\Response\Response;

use Passport\Service\User\CollectionAction;
use Think\Log;

class addColection extends BaseController {
    protected $_login = true;
    public function run(){
        try{
            $data = self::$_param;
            $userId = $GLOBALS["userId"];
            if(!isset($data["goods_id"]) || !isset($data["state"])){
                Log::write(json_encode($_REQUEST),'ERROR');
                Exception::throwException(Exception::PARAM_ERROR);
            }
            $ResponseData = CollectionAction::addColection($data,$userId,$data["state"]);
            Response::outPut($ResponseData);
        }catch (\Exception $e){
            $msg = array("code"=>$e->getCode(),"msg"=>$e->getMessage());
            Log::write($msg,"ERROR");
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}