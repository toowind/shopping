<?php
namespace Common\Model;
class SmsModel{

    //错误信息
    protected $error = null;
    //短信配置
    private $config = array(
        'accountSid'=>'8a48b5514f73ea32014f8ca9492f3225',       //ACCOUNT SID
        'accountToken'=>'dab6849711b4488bb9d0d4367f2b3ca7',  //AUTH TOKEN
        'appId'=>'8aaf07085805254b015809f6476e0321',              //APP ID
        'sIP'=>'app.cloopen.com',                                                     //请求地址
        'sPort'=>'8883',                                                                    //请求端口
        'sVersion'=>'2013-12-26',                                                   //REST版本号
    );
    /**
     * 构造方法,用于构造短信实例
     * @param array $config 配置值,用于替换默认配置
     */
    function __construct($config=array()){
        $this->config = array_merge($this->config,$config);
        vendor('CCPRestSms.CCPRestSmsSDK');
    }
    /**
     * 返回短信实例的错误信息
     * @access public
     * @return string
     */
    public function getError(){
        return $this->error;
    }
    /**
     * 发送模板短信
     * @param string $mobile 手机号码集合,用英文逗号分开
     * @param array $datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
     * @param int $tempId 模板Id,测试应用和未上线应用填写1,正式应用上线后填写已申请审核的模板ID
     */
    function message_send($mobile,$datas,$tempId=34617){
        //初始化REST SDK
        $rest = new \REST($this->config['sIP'],$this->config['sPort'],$this->config['sVersion']);
        $rest->setAccount($this->config['accountSid'],$this->config['accountToken']);
        $rest->setAppId($this->config['appId']);
        //发送短信
        $result = $rest->sendTemplateSMS($mobile,$datas,$tempId);
        if($result->statusCode != 0){       //发送失败
            $this->error = (string)$result->statusMsg;      //记录错误信息
            return false;
        }else{                                          //发送成功
            return true;
        }
    }
    /**
     * 语音验证码
     * @param verifyCode 验证码内容，为数字和英文字母，不区分大小写，长度4-8位
     * @param playTimes 播放次数，1－3次
     * @param $mobile 接收号码
     * @param displayNum 显示的主叫号码
     * @param respUrl 语音验证码状态通知回调地址，云通讯平台将向该Url地址发送呼叫结果通知
     * @param lang 语言类型。取值en（英文）、zh（中文），默认值zh。
     * @param userData 第三方私有数据
     * @param welcomePrompt  欢迎提示音，在播放验证码语音前播放此内容（语音文件格式为wav）
     * @param playVerifyCode  语音验证码的内容全部播放此节点下的全部语音文件
     */
    function voiceVerify($mobile,$verifyCode){
        $playTimes = 3;
        $displayNum = '';   //显示主叫号码，显示权限由服务侧控制。
        $respUrl = '';     //语音验证码状态通知回调地址，云通讯平台将向该Url地址发送呼叫结果通知
        $lang = 'zh';     //播放的语言类型（暂不支持设置en，默认zh）。取值en（英文）、zh（中文），默认值zh。
        $userData = '';   //第三方私有数据，可在语音验证码状态通知中获取此参数。
        $welcomePrompt = '';/* wav格式的文件名，欢迎提示音，在播放验证码语音前播放此内容，配合verifyCode使用，默认值
                            空。语音文件通过官网上传审核后才可使用，放音文件的格式样本如
                            下：位速 128kbps，音频采样大小16位，频道 1(单声道)， 音频采样级别 8 kHz，音频格式
                            PCM，这样能保证放音的清晰度。*/
        $playVerifyCode = ''; //语音验证码的内容全部播放此节点下的全部语音文件
        // 初始化REST SDK
        $rest = new \REST($this->config['sIP'],$this->config['sPort'],$this->config['sVersion']);
        $rest->setAccount($this->config['accountSid'],$this->config['accountToken']);
        $rest->setAppId($this->config['appId']);

        $result = $rest->voiceVerify($verifyCode,$playTimes,$mobile,$displayNum,$respUrl,$lang,$userData,$welcomePrompt,$playVerifyCode);
        if($result == NULL ) {
            return false;
        }
        if($result->statusCode!=0) {
            /*            echo "error code :" . $result->statusCode . "<br>";
                        echo "error msg :" . $result->statusMsg . "<br>";
            */
            //TODO 添加错误处理逻辑
            $this->error = (string)$result->statusMsg;      //记录错误信息
            return false;
        } else{
            //TODO 添加成功处理逻辑
            return true;
        }
    }
}

