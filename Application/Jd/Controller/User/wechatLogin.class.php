<?php
/* 用户信息*/

namespace Jd\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;
use Jd\Service\User\UserAction;

class wechatLogin extends BaseController
{
    public function run() {
        try {
            $data = self::$_param;

            $encryptedData = empty($data["encryptedData"]) ? '' : $data["encryptedData"];
            $iv = empty($data["iv"]) ? '' : $data["iv"];
            $sessionKey = empty($data["session_key"]) ? '' : $data["session_key"];

            $userInfo = $this->getDecryptData($encryptedData,$iv,$sessionKey);
            if(is_numeric($userInfo)){
                $msg = [
                    1 => '参数不能为空',
                    2 => '执行失败',
                    3 => 'aes 解密失败 1',
                    4 => 'aes 解密失败 2',
                    5 => 'aes 解密失败 3',
                    6 => 'aes 解密失败 4',
                    7 => 'aes 解密失败 5',
                ];
                Response::outPutFail($userInfo, $msg[$userInfo]);
            } else {
                $result = UserAction::wechatLogin($userInfo);
                if(is_array($result)){
                    Response::outPut($result);
                } else {
                    Response::outPutFail($result, '操作失败');
                }
            }
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

    //获取用户信息
    private function getDecryptData($encryptedData, $iv, $sessionKey) {
        if (empty($encryptedData) || empty($iv) || empty($sessionKey)) {
            return 1; //参数不能为空
        }
        if ($data = $this->decryptData($encryptedData, $iv, $sessionKey)) {
            return $data;
        } else {
            return 2; //执行失败
        }
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $sessionKey string
     * @return int 成功0，失败返回对应的错误码
     */
    private function decryptData( $encryptedData, $iv,$sessionKey){
        if (strlen($sessionKey) != 24) {
            $this->apiError(100001,'aes 解密失败');
            return 3; //aes 解密失败 1
        }
        $aesKey=base64_decode(str_replace(" ","+",$sessionKey));
        if (strlen($iv) != 24) {
            return 4; //aes 解密失败 2
        }
        $aesIV=base64_decode(str_replace(" ","+",$iv));

        $aesCipher=base64_decode(str_replace(" ","+",$encryptedData));

        $result=openssl_decrypt( $aesCipher,"AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode($result,true);

        if(empty($dataObj)){
            return 5; //aes 解密失败 3
        }
//        if( $dataObj['watermark']['appid'] != 'wx4c5be752eac43c28' ){
//            return 6; //aes 解密失败 4
//        }
        if( $dataObj['watermark']['appid'] != 'wxf5e4a55271562c73' ){
            return 6; //aes 解密失败 4
        }

        if(!$dataObj['unionId']){
            return 7; //aes 解密失败 5
        }
        $data = $dataObj;
        return  $data;
//        return true;
    }
}