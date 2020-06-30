<?php
return array(
    'REDIS_HOST'=>'r-2zet2uquz8xvjhp9na.redis.rds.aliyuncs.com',
    'REDIS_PORT'=>'6379',
    'REDIS_AUTH'=>'F#@R#@FSDWE32sWEWSxw22s',
    'REDIS_PASSWORD'=>'F#@R#@FSDWE32sWEWSxw22s',
    /* 数据库配置 */
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => 'rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com', // 服务器地址
    'DB_NAME'   => 'shopping', // 数据库名
    'DB_USER'   => 'shopping', // 用户名
    'DB_PWD'    => 'FEWESs2S2W2334SWX12w3sx',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'shop_', // 数据库表前缀

    'SESSION_TYPE'=>'Redis',
    'SESSION_EXPIRE'=>36000,
    'SESSION_PREFIX'=>'sess:home:',
    'SESSION_REDIS_HOST'=>'r-2zet2uquz8xvjhp9na.redis.rds.aliyuncs.com',
    'SESSION_REDIS_PORT'=>'6379',
    'SESSION_REDIS_AUTH'=>'F#@R#@FSDWE32sWEWSxw22s',

    'redis'=>array(
        'master' => array(
            'REDIS_HOST'=>'r-2zet2uquz8xvjhp9na.redis.rds.aliyuncs.com',
            'REDIS_PORT'=>'6379',
            'REDIS_AUTH'=>'F#@R#@FSDWE32sWEWSxw22s',
        ),
        'slave' => array(
            'REDIS_HOST'=>'r-2zet2uquz8xvjhp9na.redis.rds.aliyuncs.com',
            'REDIS_PORT'=>'6379',
            'REDIS_AUTH'=>'F#@R#@FSDWE32sWEWSxw22s',
        )
    )

);
