<?php


namespace WechatController;


use WechatApp\Controller\ApiController;

class WechatController extends ApiController {
    private $url = 'https://api.weixin.qq.com/sns/jscode2session?';
    private $configs = array(
        'secret'=>'eeaf5ad429730e7e274a1b25bf05d544',
        'appid'=>'wx5a5cac2f5ccc68de',
        'grant_type'=>'authorization_code',
        'js_code'=>'',
    );
    private $date_time;
    protected function _initialize(){
        $this->date_time = date('Y-m-d');
    }

    //获取sessionKey
    public function getCodeInfo(){
        $code = I('post.code','','trim');
        if(empty($code)) $this->apiError(100001,'参数错误');
        $this->configs['js_code'] = $code;
        $url = $this->url.http_build_query($this->configs);
        list($http,$data) =  get_curl_data($url);
        $data = json_decode($data,true);
        if($http['http_code'] != 200 || empty($data['openid'])|| empty($data['session_key'])){
            $this->apiError(200001,'执行失败');
        }
        $this->apiSuccess('执行成功',array('session_key' => $data['session_key']));
    }

    public function loginew(){
        $this->_checkSign();
        $encryptedData = I('post.encryptedData','','trim');
        $iv = I('post.iv','','trim');
        $sessionKey = I('post.session_key','','trim');
        $userInfo = $this->getDecryptData($encryptedData,$iv,$sessionKey);
        $platform = 3;
        $nickname = $userInfo['nickName'];
        $avatar = $userInfo['avatarUrl'];
        $sex = $userInfo['gender'];
        $openid = $userInfo['openId'];
        $oauth_token = $userInfo['unionId'];
        $channel = I('post.channel','','trim');
        $device_type = I('post.device_platform','','trim');
        $device_type =   strpos($device_type,'iPhone') !== false?1:2;
        $Connect_mod = D('Connect','Logic');
        $app_version = I('post.version','','trim');
        $inviteid = I("post.invite_id",0,'intval');
        $from = I("post.from",'kd','trim');
        $callback = $Connect_mod->binding($this->uid,$platform,$nickname,$avatar,$sex,$openid,$oauth_token,$channel,$device_type,$app_version,$inviteid,$from);
        if($callback === false){
            $error = $Connect_mod->getError();
            $this->apiError($error[0],$error[1]);
        }else{
            $this->apiSuccess('执行成功',$callback);
        }
    }

    //获取用户信息
    private function getDecryptData($encryptedData,$iv,$sessionKey){
        if(empty($encryptedData) || empty($iv) || empty($sessionKey)){
            $this->apiError(700001,'参数不能为空');
        }
        if($this->decryptData($encryptedData,$iv,$data,$sessionKey)){
            return $data;
        }
        $this->apiError(600001,'执行失败');
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     * @return int 成功0，失败返回对应的错误码
     */
    private function decryptData( $encryptedData, $iv,&$data,$sessionKey){
        if (strlen($sessionKey) != 24) {
            $this->apiError(100001,'aes 解密失败');
        }
        $aesKey=base64_decode(str_replace(" ","+",$sessionKey));
        if (strlen($iv) != 24) {
            $this->apiError(200001,'aes 解密失败');
        }
        $aesIV=base64_decode(str_replace(" ","+",$iv));

        $aesCipher=base64_decode(str_replace(" ","+",$encryptedData));

        $result=openssl_decrypt( $aesCipher,"AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode($result,true);

        if(empty($dataObj)){
            $this->apiError(300001,'aes 解密失败');
        }
        if( $dataObj['watermark']['appid'] != $this->configs['appid'] ){
            $this->apiError(400001,'aes 解密失败');
        }
        if(!$dataObj['unionId']){
            $this->apiError(500001,'aes 解密失败');
        }
        $data = $dataObj;
        return true;
    }
}