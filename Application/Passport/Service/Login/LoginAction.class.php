<?php
namespace Passport\Service\Login;
use Common\Action\BaseAction;
use Common\Util\AppConfig;

class LoginAction extends BaseAction{
    public static function login($code){
        //调微信
         $appConf = new AppConfig;
         $url = $appConf('wx.access_token.get_url');
         var_dump($url);die;
    }
}