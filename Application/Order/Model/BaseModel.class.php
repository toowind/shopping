<?php

namespace Order\Model;

use Think\Model;

class BaseModel extends Model
{
    //分销客数据库配置
    public $db_config = [
        'DB_TYPE' => 'mysql', // 数据库类型
        'DB_HOST' => FXK_DB_HOST, // 服务器地址
        'DB_NAME' => FXK_DB_NAME, // 数据库名
        'DB_USER' => FXK_DB_USER, // 用户名
        'DB_PWD' => FXK_DB_PWD,  // 密码
        'DB_PORT' => '3306', // 端口
        'DB_PREFIX' => FXK_DB_PREFIX, // 数据库表前缀
    ];
}