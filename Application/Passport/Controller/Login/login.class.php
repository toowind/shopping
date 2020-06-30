<?php
namespace Passport\Controller\Login;
use Common\Controller\BaseController;
use Common\Response\Response;
use Passport\Common\ApiException;
use Passport\Service\Login\LoginAction;

class login extends  BaseController {
    public function run(){

        $code = I("code",NULL,trim);
        try{
            if($code == NULL){
                ApiException::throwException(ApiException::CODE_IS_NULL);
            }
            $data = LoginAction::login($code);
            echo "<pre>";
            print_r($data);die;
        }catch (\Exception $e){
            Response::outPutFail($e->getCode(),$e->getMessage());
        }
    }
}
