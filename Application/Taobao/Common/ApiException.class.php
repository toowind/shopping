<?php
/**
 * Product模块级别异常管理
 * @author ZhiGang Wen
 */
namespace Taobao\Common;

use Common\Exception\Exception;

class ApiException extends Exception {
    /**
     * 错误码分三段
     * 10  0  001
     * 第一二位子系统编号
     * 后四位代表错误码
     * Passport  2X
     * WeChart   3X
     * Product   4X
     * Order     5X
     * Cart      6X
     * Refund    7X
     *
     */
    const CODE_IS_NULL = 401001;
    const GOODS_ID_LIST = 401002;
    const GOODS_INFO_ERROR = 401003;
    const COMMISSION_ERROR = 401004;
    public static $msgList = array(
        self::CODE_IS_NULL => "code不能为空",
        self::GOODS_ID_LIST => "商品id列表不能为空",
        self::GOODS_INFO_ERROR =>"该商品下架了",
        self::COMMISSION_ERROR =>"佣金比例异常",
    );

    public static function throwException($code,$message = ''){
        if($message == ''){
            $message = isset(self::$msgList[$code]) ? self::$msgList[$code] : "";
        }

        throw new \Exception($message, $code);
    }
}