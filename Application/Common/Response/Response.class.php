<?php
/**
 * 统一管理返回客户端数据
 * @author ZhiGang Wen
 */
namespace Common\Response;
class Response{
    /**
     * @param $data
     * @param string $msg
     * @param int $status
     * @author ZhiGang Wen
     */
    public static function outPut($data,$msg = 'success',$status = 0){
        $data = $data ? $data : [];
        $result = array(
            'status' => $status,
            'info'    => $msg,
            'data'   => $data,
        );
        $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';

        $allow_origin = array(
            'https://highlights.youth.cn',
            'https://kandian.youth.cn',
            'https://kd.youth.cn',
        );
        header("Access-Control-Request-Methods:GET, POST, PUT, DELETE, OPTIONS");
        header('Access-Control-Allow-Headers: X-Requested-With,Content-Type');
        header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Credentials: true');
        if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);
        }
        exit(json_encode($result));
    }

    /**
     * @param $data
     * @param int $status
     * @param string $msg
     * @author ZhiGang Wen
     */
    public static function outPutFail($status = 1,$msg = '',$data){
        $data = $data ? (object) $data : [];
        $result = array(
            'status' => $status,
            'info'    => $msg,
            'data'   => $data,
        );
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($result));
    }

    /**
     * 消息流返回成功
     * @param string $msg
     * @param array $raw
     * @author ZhiGang Wen
     */
    public static function queApiReturnSuccess($msg = 'ok', $raw = array()){

        $msg = trim(str_replace(array("\n", "\r\n", "<br>"), " ", $msg));
        $data = array();
        $data["code"] = 200;
        $data["desc"] = $msg;
        $data["data"] = is_array($raw) ? $raw : NULL;
        header("Content-Type:text/json;charset=utf-8");
        $return = json_encode($data);
        exit($return);
    }

    /**
     * 消息流返回失败
     * @param string $msg
     * @param array $raw
     * @param int $status
     * @author ZhiGang Wen
     */
    public static function queApiReturnFail($msg = '', $raw = array(), $status = 100)
    {
        $msg = trim(str_replace(array("\n", "\r\n", "<br>"), " ", $msg));
        $data = array();
        $data["code"] = intval($status);
        $data["desc"] = $msg;
        $data["data"] = is_array($raw) ? $raw : NULL;
        header("Content-Type:text/json;charset=utf-8");
        $return = json_encode($data);
        exit($return);
    }
}
