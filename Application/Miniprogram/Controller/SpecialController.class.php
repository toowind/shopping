<?php
namespace Miniprogram\Controller;
class SpecialController extends BaseController{


    /**
     * 获取专题详情
     */
    function getInfo(){
        $this->_checkSign();          //检测签名
        if(!IS_GET){
            $this->apiError('请求类型错误');
        }
        $special_id = I('get.special_id',0,'intval');       //获取专题ID
        if(!$special_id){
            $this->apiError('专题ID不存在');
        }
        $Special_mod = M('Special');
        $map = [
            'id'=>$special_id
        ];
        $info = $Special_mod->where($map)->field('add_time',true)->find();
        if(!$info){
            $this->apiError('专题不存在');
        }
        //是否在活动时间范围
        if(NOW_TIME > $info['start_time'] && NOW_TIME < $info['end_time']){
            $is_intime = '1';
        }else{
            $is_intime = '0';
        }
        //分类1的商品列表
        if($info['sort_one']){
            $where1 = [
                'id'=>['in',$info['sort_one']]
            ];
            $order1 = 'field(id,'.$info['sort_one'].')';
            $goodsList1 = $this->arrangement($where1,$order1);
        }else{
            $goodsList1 = [];
        }
        //分类2的商品列表
        if($info['sort_two']){
            $where2 = [
                'id'=>['in',$info['sort_two']]
            ];
            $order2 = 'field(id,'.$info['sort_two'].')';
            $goodsList2 = $this->arrangement($where2,$order2);
        }else{
            $goodsList2 = [];
        }
        if($info['sort_three']){
            //分类3的商品列表
            $where3 = [
                'id'=>['in',$info['sort_three']]
            ];
            $order3 = 'field(id,'.$info['sort_three'].')';
            $goodsList3 = $this->arrangement($where3,$order3);
        }else{
            $goodsList3 = [];
        }
        $list = [];
        if($goodsList1){
            $list[] = [
                'name'=>$info['names_one'],
                'goodslist'=>$goodsList1
            ];
        }
        if($goodsList2){
            $list[] = [
                'name'=>$info['names_two'],
                'goodslist'=>$goodsList2
            ];
        }
        if($goodsList3){
            $list[] = [
                'name'=>$info['names_three'],
                'goodslist'=>$goodsList3
            ];
        }
        //返回值
        $return = [];
        $return['name'] = $info['name'];                                                  //专题名称
        $return['list_banner'] = $info['list_banner'];                              //列表banner图
        $return['special_banner'] = $info['special_banner'];                   //专题banner图
        $return['ac_time_bg'] = $info['ac_time_bg'];                              //活动时间背景颜色
        $return['cate_bg'] = $info['cate_bg'];                                          //分类背景颜色
        $return['bg'] = $info['bg'];                                                            //背景颜色
        $return['goods_sm_icon'] = $info['goods_sm_icon'];                   //商品小图标
        $return['is_intime'] = $is_intime;                                                  //是否在活动时间范围
        $return['status'] = $info['status'];                                                //专题上下架状态
        $return['catelist'] = $list;
        $this->apiSuccess($return);
    }
    /**
     * 根据条件和查询字段和查询排序获取商品数据
     */
    private function arrangement($where,$order){
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
