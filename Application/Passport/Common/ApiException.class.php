<?php
/**
 * Passport模块级别异常管理
 * @author ZhiGang Wen
 */
namespace Passport\Common;

use Common\Exception\Exception;

class ApiException extends Exception {
    /**
     * 错误码分三段
     * 10  0  001
     * 第一二位子系统编号
     * 后四位代表错误码
     * Passport  2XXX
     * WeChart   3XXX
     * Product   4XXX
     * Order     5XX
     * Cart      6XX
     * Refund    7XX
     *
     */
    const CODE_IS_NULL = 101001;
    const ADD_BROWSE_ERROR = 101002;
    public static $msgList = array(
        self::CODE_IS_NULL => "code不能为空",
        self::ADD_BROWSE_ERROR=>"新增浏览记录失败~",
    );

    public static function throwException($code,$message = ''){
        if($message == ''){
            $message = isset(self::$msgList[$code]) ? self::$msgList[$code] : "";
        }

        throw new \Exception($message, $code);
    }
}