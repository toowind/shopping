<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * UCenter客户端配置文件
 * 注意：该配置文件请使用常量方式定义
 */

define('UC_APP_ID', 2); //应用ID
define('UC_API_TYPE', 'Model'); //可选值 Model / Service
define('UC_AUTH_KEY', 'b|tX$<@DGN!H*6%Tpl>yk"h-1;Yme{93ZAsOC?u5'); //加密KEY
define('UC_DB_DSN', 'mysql://shopping:FEWE!!##Ss2S2W2334SWX@rm-2ze9f4jy87k3d58y8.mysql.rds.aliyuncs.com:3306/shopping'); // 数据库连接，使用Model方式调用API必须配置此项
define('UC_TABLE_PREFIX', 'shop_'); // 数据表前缀，使用Model方式调用API必须配置此项
define('APP_DEBUG', true );

define('YOUTH_API', 'https://kandian.youth.cn');

define('FXK_DB_HOST', '127.0.0.1'); // 服务器地址
define('FXK_DB_NAME', 'shop_fxk'); // 数据库名
define('FXK_DB_USER', 'root'); // 用户名
define('FXK_DB_PWD', '123456'); // 密码
define('FXK_DB_PREFIX', 'fxk_'); // 数据库表前缀

return array(

);
