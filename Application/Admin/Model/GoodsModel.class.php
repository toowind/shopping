<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: huajie <banhuajie@163.com>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;


class GoodsModel extends Model {
    protected $trueTableName  = "shop_goods";
    public function getGoodsList($limit){
        $data = $this->limit($limit)->select();
        return $data;
    }
}