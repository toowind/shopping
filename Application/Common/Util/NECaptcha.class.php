<?php 
namespace Common\Util;
use Common\Util\NECaptcha\lib\NECaptchaVerifier;
use Common\Util\NECaptcha\lib\SecretPair;
class NECaptcha {
    protected $captcha_id = "7d351b0291b94801aded2f7ff28c25f2";//验证码id
    protected $secret_id = "e5310db84a9bbd6316a9923104368574";//验证码密钥对id
    protected $secret_key = "1f89d5936a7d032fdfec3cbda6b57da1";// 验证码密钥对key
    protected $SecretPair;

    function __construct($config=array()){
        $config['secret_id'] && $this->secret_id = $config['secret_id'];
        $config['secret_key'] && $this->secret_key = $config['secret_key'];
        $config['captcha_id'] && $this->captcha_id = $config['captcha_id'];
        $this->SecretPair = new SecretPair($this->secret_id, $this->secret_key);
    }

    function verify($validate,$user=''){
        $verifier = new NECaptchaVerifier($this->captcha_id, $this->SecretPair);
        $user['user'] = $user; // 当前用户信息，值可为空
        $user = json_encode($user);
        $result = $verifier->verify($validate, $user);
        return $result;
    }
}
?>
