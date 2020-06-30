<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;

/**
 * 日志处理类
 */
class Log
{

    // 日志级别 从上到下，由低到高
    const EMERG  = 'EMERG'; // 严重错误: 导致系统崩溃无法使用
    const ALERT  = 'ALERT'; // 警戒性错误: 必须被立即修改的错误
    const CRIT   = 'CRIT'; // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR    = 'ERR'; // 一般错误: 一般性错误
    const WARN   = 'WARN'; // 警告性错误: 需要发出警告的错误
    const NOTICE = 'NOTIC'; // 通知: 程序可以运行但是还不够完美的错误
    const INFO   = 'INFO'; // 信息: 程序输出信息
    const DEBUG  = 'DEBUG'; // 调试: 调试信息
    const SQL    = 'SQL'; // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志信息
    protected static $log = array();
    protected static $_logId = null;

    // 日志存储
    protected static $storage = null;

    // 日志初始化
    public static function init($config = array())
    {
        $type  = isset($config['type']) ? $config['type'] : 'File';
        $class = strpos($type, '\\') ? $type : 'Think\\Log\\Driver\\' . ucwords(strtolower($type));
        unset($config['type']);
        self::$storage = new $class($config);
    }

    /**
     * 记录日志 并且会过滤未经设置的级别
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     * @return void
     */
    public static function record($message, $level = self::ERR, $record = false)
    {

        if ($record || false !== strpos(C('LOG_LEVEL'), $level)) {
            self::$log[] = "{$level}: {$message}\r\n";
        }
    }

    /**
     * 日志保存
     * @static
     * @access public
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    public static function save($type = '', $destination = '')
    {
        if (empty(self::$log)) {
            return;
        }

        if (empty($destination)) {
            $destination = C('LOG_PATH') . date('y_m_d') . '.log';
        }
        if (!self::$storage) {
            $type          = $type ?: C('LOG_TYPE');
            $class         = 'Think\\Log\\Driver\\' . ucwords($type);
            self::$storage = new $class();
        }
        $message = implode('', self::$log);
        self::$storage->write($message, $destination);
        // 保存后清空日志缓存
        self::$log = array();
    }

    /**
     * 日志直接写入
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     * @author ZhiGang Wen
     */
    public static function write($message, $level = self::ERR, $type = '', $destination = '')
    {
        if (!self::$storage) {
            $type               = $type ?: C('LOG_TYPE');
            $class              = 'Think\\Log\\Driver\\' . ucwords($type);
            $config['log_path'] = C('LOG_PATH');
            self::$storage      = new $class($config);
        }
        if (empty($destination)) {
            $destination = C('LOG_PATH') .$level.'_'. date('Y_m_d') . '.log';
        }
        $logInfo = self::LogInfo($level,$message);
        self::$storage->write($logInfo, $destination);
    }

    /***
     * @param $level
     * @param $message
     * @param int $index
     * @return string
     * @author ZhiGang Wen
     */
    public static function LogInfo($level,$message,$index = 0){
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if($index){
            $trace = array_slice($trace,$index);
        }
        $trace = array(
            'file'      =>  isset($trace[1]) && isset($trace[1]['file']) ? $trace[1]['file'] : null,
            'line'      =>  isset($trace[1]) && isset($trace[1]['line']) ? $trace[1]['line'] : null,
            'class'     =>  isset($trace[2]) && isset($trace[2]['class']) ? $trace[2]['class'] : null,
            'func'      =>  isset($trace[2]) && isset($trace[2]['function']) ? $trace[2]['function'] : null,
        );

        $log = array();
        $log["level"] = $level;
        $log["logId"] = self::genLogId();
        $log["c_time"] = date("Y-m-d H:i:s");
        $log['http_host'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $log['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $log['request_uri'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $log['post'] = isset($_POST) ? json_encode($_POST,JSON_UNESCAPED_UNICODE) : json_encode(array());
        $log['client_ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $log['local_ip'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        $log['func'] = isset($trace['func']) ? $trace['class'].':'.$trace['func'].':'.$trace['line'] : '';
        $log['func'] .= '[file: '.$trace['file'].'; line:'.$trace['line'].']';
        $log['user_id'] = (isset($GLOBALS['userId']) && is_numeric($GLOBALS['userId'])) ? "userId:".$GLOBALS['userId'] : -1;
        $log['source'] = (isset($GLOBALS['source']) && is_numeric($GLOBALS['source'])) ? "source:".$GLOBALS['source'] : -1;
        $log['msg'] = is_string($message) ? 'RETURN：'.$message : 'RETURN:'.json_encode($message,JSON_UNESCAPED_UNICODE);
        $strLog = implode('-==-', $log);
        return $strLog;
    }

    /**
     * @brief 创建唯一的序列化字段logId,主要为了查出一次请求中的所有log
     * @author ZhiGang Wen
     */
    public static function genLogId()
    {
        if ( !self::$_logId ) {
            $str = ((mt_rand() << 1) | (mt_rand() & 1) ^ intval(microtime(true)));
            $logId = strtoupper(base_convert($str, 10, 36));
            //补齐六位
            self::$_logId = str_pad($logId, 6, 'X');
        }
        return self::$_logId;
    }
}
