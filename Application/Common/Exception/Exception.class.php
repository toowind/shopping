<?php
namespace Common\Exception;

class Exception{
    /**
     * 错误码分三段
     * 1  00  001
     * 第一位代表系统级别公共错误
     * 第二三位代表模块编号
     * 第四五六位代表错误编号
     *
     * Passport  01
     * WeChart   02
     * Product   03
     * Order     04
     * Cart      05
     * Refund    06
     *
     */
    const DB_ERROR = 100001;
    const HTTP_ERROR = 100002;
    const PARAM_ERROR = 100003;
    const GOODS_NOT_FOUNT = 100004;
    public static $msgList = array(
        self::DB_ERROR => "数据库异常",
        self::HTTP_ERROR => "网络异常,稍后重试",
        self::PARAM_ERROR => "参数异常",
        self::GOODS_NOT_FOUNT => "商品不存在",

    );

    public static function throwException($code,$message = ''){

        if($message == ''){
            $message = isset(self::$msgList[$code]) ? self::$msgList[$code] : "";
        }
        throw new \Exception($message, $code);
    }
}