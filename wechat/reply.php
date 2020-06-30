<?php
define("TOKEN", "zqkd2020");

class Customer
{
    const APP_ID = 'wx4c5be752eac43c28'; //你自己的appid
    const APP_SECRET = 'b91bc67eb0ee15511462921d5b320ee4';//你自己生成的appSecret
    //校验服务器地址URL
    public function checkServer(){
        if (isset($_GET['echostr'])) {
            $this->valid();
        }else{
            $this->responseMsg();
        }
    }

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            header('content-type:text');
            echo $echoStr;
            exit;
        }else{
            echo $echoStr.'+++'.TOKEN;
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()
    {
        try{
            //此处推荐使用file_get_contents('php://input')获取后台post过来的数据
            $postStr = file_get_contents('php://input');
            if (!empty($postStr) && is_string($postStr)){
                $postArr = json_decode($postStr,true);
                if(!empty($postArr['MsgType']) && $postArr['MsgType'] == 'text'){
                    //用户给客服发送文本消息
                    if($postArr['Content'] == 1){
                        //接收到指定的文本消息，触发事件
                        $fromUsername = $postArr['FromUserName']; //发送者openid
                        $content = "1、点击下方链接安装【中青看点】APP，即可提现成功\n2、安装成功后，在【我的 - 我的青豆】中查看账单。\n3、安装链接：<a href='https://a.app.qq.com/o/simple.jsp?pkgname=cn.youth.news&ckey=CK1424286832564'>立即下载>></a>";
                        $this->requestTXT($fromUsername,$content);
                    }
                }
                else if(!empty($postArr['MsgType']) && $postArr['MsgType'] == 'image'){
                    //用户给客服发送图片消息，按需求设置
                }
                else if($postArr['MsgType'] == 'event' && $postArr['Event']=='user_enter_tempsession'){
                    //用户进入客服事件
                    $fromUsername = $postArr['FromUserName']; //发送者openid
                    $content = '你好,欢迎来到爆款严选，请回复数字1查看提现流程';
                    $this->requestTXT($fromUsername,$content);
                }
                else{
                    exit('error');
                }
            }else{
                echo "empty";
                exit;
            }
        }catch (Exception $e){
            echo $e->getMessage();
        }
    }

    //文本回复
    public function requestTXT($fromUsername,$content){
        $data=array(
            "touser"=>$fromUsername,
            "msgtype"=>"text",
            "text"=>array("content"=>$content)
        );
        $json = json_encode($data,JSON_UNESCAPED_UNICODE);
        $this->requestAPI($json);
    }

    //图片回复
    public function requestIMAGE($fromUsername,$media_id){
        $data=array(
            "touser"=>$fromUsername,
            "msgtype"=>"image",
            "image"=>array("media_id"=>$media_id)
        );
        $json = json_encode($data,JSON_UNESCAPED_UNICODE);
        $this->requestAPI($json);
    }

    public function requestAPI($json){
        $access_token = $this->get_accessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$access_token;
        $output = $this->curl_post($url,$json);
        if($output == 0){
            echo 'success';
            exit;
        }
    }

    //调用微信api，获取access_token，有效期7200s
    public function get_accessToken(){
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::APP_ID.'&secret='.self::APP_SECRET; //替换成自己的小程序id和secret
        $res = file_get_contents($url);
        $data = json_decode($res,true);
        $token = $data['access_token'];
        return $token;
    }

    //post方式请求接口
    public function curl_post($url,$data,$headers = null)
    {
        //$data 是一个 array() 数组；未编码
        $curl = curl_init();    // 启动一个CURL会话
        if(substr($url,0,5)=='https'){
            // 跳过证书检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            //只有在CURL低于7.28.1时CURLOPT_SSL_VERIFYHOST才支持使用1表示true，高于这个版本就需要使用2表示了（true也不行）。
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if($headers != null){
            //post请求中携带header参数
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        //返回api的json对象
        $response = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //返回json对象
        return $response;
    }
}

$customerObj = new Customer();
$customerObj->checkServer();
//$signature = $_GET["signature"];
//$timestamp = $_GET["timestamp"];
//$nonce = $_GET["nonce"];
//$str = $_GET["echostr"];
//$token = "zqkd2020";
//$tmpArr = array($token, $timestamp, $nonce);
//sort($tmpArr, SORT_STRING);
//$tmpStr = implode( $tmpArr );
//$tmpStr = sha1( $tmpStr );
//if( $tmpStr == $signature ){
//    echo   $str;
//}else{
//    echo  false;
//}