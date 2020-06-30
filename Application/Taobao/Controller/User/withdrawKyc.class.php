<?php
/* 用户信息*/

namespace Taobao\Controller\User;

use Common\Controller\BaseController;
use Common\Response\Response;


class withdrawKyc extends BaseController
{
    public function run() {
        try {

            $data = self::$_param;

            $params['uid'] = $GLOBALS['userId'];
            $params['card'] = empty($data['card']) ? 0 : trim($data['card']); //身份证号
            $params['name'] = empty($data['name']) ? 0 : trim($data['name']); //姓名

            $param_data['param'] = think_encrypt(json_encode($params),"QGLGKU");

            $url = YOUTH_API.'/Shop/withdrawKyc';
            $result = $this->sendCurlPost($url, $param_data);

            //清除用户缓存
            $url = YOUTH_API.'/Shop/clearUserCache';
            $this->sendCurlPost($url, $param_data);

            $res = json_decode($result[1], 1);
            if($res){
                if($res['code'] == 1){
                    Response::outPut($res['data'], $res['msg'], '0');
                } else {
                    Response::outPut($res['data'], $res['msg'], '1'.$res['code']);
                }
            } else {
                Response::outPut($param_data, '请求超时', '1120');
            }
        } catch (\Exception $e) {
            Response::outPutFail($e->getCode(), $e->getMessage());
        }
    }

}