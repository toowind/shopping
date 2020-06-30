<?php
namespace Wap\Controller;
use Think\Controller;
class BaseController extends Controller{


    protected function _initialize(){
        if(false === $config = S('DB_CONFIG_DATA')){
            $config = api('Config/lists');
            S('DB_CONFIG_DATA',$config);
        }
        C($config);
    }
    /**
     * 根据条件和查询字段和查询排序获取商品数据
     */
    protected function arrangement($where,$order){
        $Goods_mod = M('Goods');
        $field = 'id,title,thumb,price,virtual_stock_num,status';
        $list = $Goods_mod->where($where)->field($field)->order($order)->select();
        $result = [];
        foreach($list as $k=>$v){
            $result[$k]['id'] = $v['id'];
            $result[$k]['title'] = $v['title'];
            $result[$k]['thumb'] = $v['thumb'];
            $result[$k]['price'] = $v['price'];
            $result[$k]['status'] = $v['status'];
            $result[$k]['stock_num'] = $v['virtual_stock_num'];
        }
        return $result;
    }
}
