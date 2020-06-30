<?php
namespace Miniprogram\Controller;
class GoodsController extends BaseController{

    /**
     * 获取规格明细详情
     */
    function getParamInfo(){
        $this->_checkSign();          //检测签名
        if(!IS_GET){
            $this->apiError('请求类型错误');
        }
        $goods_id = I('get.goods_id',0,'intval');       //获取商品ID
        if(!$goods_id){
            $this->apiError('商品ID不存在');
        }
        $Goods_mod = M('Goods');
        $map = [];
        $map['id'] = $goods_id;
        $info = $Goods_mod->where($map)->field('id,status')->find();
        if(!$info){
            $this->apiError('商品不存在');
        }
        $param_ids = I('get.param_ids','','trim');      //获取规格参数ID
        if($param_ids == ''){
            $this->apiError('规格参数ID不存在');
        }
        $params = explode(',',$param_ids);
        sort($params);
        //查询详细
        $GoodsParam_mod = M('GoodsParam');
        $map = [];
        $map['goods_id'] = $goods_id;
        $map['relation'] = implode(',',$params);
        $info = $GoodsParam_mod->where($map)->field('price,virtual_stock_num')->find();
        if(!$info){
            $this->apiError('规格明细不存在');
        }
        //返回值
        $return = [];
        $return['price'] = $info['price'];
        $return['stock_num'] = $info['virtual_stock_num'];
        $this->apiSuccess($return);
    }
    /**
     * 获取商品详情
     */
    function getInfo(){
        $this->_checkSign();          //检测签名
        if(!IS_GET){
            $this->apiError('请求类型错误');
        }
        $goods_id = I('get.goods_id',0,'intval');       //获取商品ID
        if(!$goods_id){
            $this->apiError('商品ID不存在');
        }
        $Goods_mod = M('Goods');
        $GoodsData_mod = M('GoodsData');
        $GoodsParam_mod = M('GoodsParam');
        $Format_mod = M('NatureFormat');
        $Param_mod = M('NatureFormatParam');
        //查询条件
        $map = [];
        $map['id'] = $goods_id;
        //查询字段
        $field = 'id,title,selling_point,thumb,images,price,price_end,virtual_stock_num,sales_num,format_id,param_id,status';
        $info = $Goods_mod->where($map)->field($field)->find();
        if(!$info){
            $this->apiError('商品不存在');
        }
        //解析多图
        $images = json_decode($info['images'],true);
        //获取商品详情
        $content = $GoodsData_mod->where($map)->getField('content');
        //获取商品规格参数
        $format_id = explode(',',$info['format_id']);
        $param_id = explode(',',$info['param_id']);
        $formats = [];
        foreach($format_id as $k=>$v){
            $where = [];
            $where['format_id'] = $v;
            $where['id'] = array('in',$param_id);
            $list = $Param_mod->where($where)->select();
            $params = [];
            foreach($list as $key=>$val){
                $params[$key]['param_id'] = $val['id'];
                $params[$key]['name'] = $val['name'];
            }
            $name = $Format_mod->where(['id'=>$v])->getField('name');
            $formats[$k]['format_id'] = $v;
            $formats[$k]['name'] = $name;
            $formats[$k]['params'] = $params;
        }
        //获取商品规格明细
        $where = [];
        $where['goods_id'] = $goods_id;
        $list = $GoodsParam_mod->where($where)->select();
        $params = [];
        foreach($list as $k=>$v){
            $params[$k]['param_ids'] = explode(',',$v['relation']);
            $params[$k]['price'] = $v['price'];
            $params[$k]['stock_num'] = $v['virtual_stock_num'];
        }
        //返回值
        $return = [];
        $return['title'] = $info['title'];                                                                //商品标题
        $return['selling_point'] = $info['selling_point'];                                      //商品卖点
        $return['thumb'] = $info['thumb'];                                                          //商品封面
        $return['images'] = $images;                                                                    //商品多图
        $return['price'] = $info['price'];                                                              //商品价格(低价)
        $return['price_end'] = $info['price_end'];                                              //商品价格(高价)
        $return['stock_num'] = $info['virtual_stock_num'];                               //商品剩余(虚拟库存)
        $return['sales_num'] = $info['sales_num'];                                             //商品销量
        $return['content'] = $content;                                                                  //商品详情
        $return['formats'] = $formats;                                                                 //规格参数
        $return['params'] = $params;                                                                    //规格明细
        $return['status'] = $info['status'];                                                           //商品状态
        $this->apiSuccess($return);
    }
}
