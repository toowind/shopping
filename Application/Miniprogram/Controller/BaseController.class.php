<?php
namespace Miniprogram\Controller;
use Think\Controller;
class BaseController extends Controller{


    protected function _initialize(){
        define('ACCESS',I('access','','trim'));                                         //网络类型
        define('CHANNEL',I('channel','','trim'));                                    //渠道号
        define('DEVICE_TYPE',I('device_type','','trim'));                      //设备类型
        define('APP_VERSION',I('app_version','','trim'));                     //微信版本号
        define('OS_VERSION',I('os_version','','trim'));                        //操作系统版本号
        define('SDK_VERSION',I('sdk_version','','trim'));                    //客户端基础库版本
        define('DEVICE_BRAND',I('device_brand','','trim'));                //手机品牌
        define('DEVICE_MODEL',I('device_model','','trim'));                //手机型号
        define('SCREEN',I('screen','','trim'));                                        //屏幕宽高度
        define('REQUEST_TIME',I('request_time',0,'intval'));             //请求发起的时间戳
        if(false === $config = S('DB_CONFIG_DATA')){
            $config = api('Config/lists');
            S('DB_CONFIG_DATA',$config);
        }
        C($config);
    }
    /**
     * 正确的返回请求
     */
    function apiSuccess($message){
        $this->ajaxReturn(['status'=>'1','info'=>$message]);
    }
    /**
     * 错误的返回请求
     */
    protected function apiError($message){
        $this->ajaxReturn(['status'=>'0','info'=>$message]);
    }
    /**
     * 检查签名认证,根据URL检测当前请求是否非法
     */
    protected function _checkSign(){
        $callback = I('');      //获取所有请求参数
        if(!$callback['token']){
            $this->apiError('签名不存在');
        }
        $token = $callback['token'];					            //获取签名
        unset($callback['token']);				                   //去除签名参数
        ksort($callback);								                   //按字母升序重新排序
        $sequence = '';									                   //定义签名数列
        foreach($callback as $k=>$v){			                   //拼接参数
            $sequence .= "{$k}={$v}";
        }
        $sequence .= 'zWplsuzJLrfw7o3SgGlMKSpupK2';//拼接加密token
        $sequence = md5($sequence);			                   //加密
        if($token != $sequence){                                     //签名检测
            $this->apiError('签名不正确');
        }
    }
}
