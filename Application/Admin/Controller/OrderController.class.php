<?php
namespace Admin\Controller;
class OrderController extends AdminController{

    /**
     * 订单管理
     */
    function index(){
        $status = I('get.status',0,'intval');
        $orderid = I('get.orderid','','trim');
        $map = [];
        if($orderid){
            $map['orderid'] = $orderid;
        }
        if($status){
            if($status == '1'){                             //未付款
                $map['status'] = '0';
                $map['send_type'] = '0';
            }elseif($status == '2'){                    //待发货
                $map['status'] = '1';
                $map['send_type'] = '0';
            }elseif($status == '3'){                    //已发货
                $map['status'] = '1';
                $map['send_type'] = '1';
            }elseif($status == '4'){                    //已完成
                $map['status'] = '1';
                $map['send_type'] = '2';
            }elseif($status == '5'){                    //已关闭
                $map['status'] = '2';
                $map['send_type'] = '0';
            }
        }
        $Order_mod = M('Order');
        $count = $Order_mod->where($map)->count();
        //分页处理
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $Page = new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit = $Page->firstRow.','.$Page->listRows;
        $order = 'id DESC';
        $list = $Order_mod->where($map)->order($order)->limit($limit)->select();
        if($list){
            $Relation_mod = M('OrderRelation');
            foreach($list as $k=>$v){
                $where = [];
                $where['orderid'] = $v['orderid'];
                $relation = $Relation_mod->where($where)->select();
                if($relation){
                    foreach($relation as $key=>$val){
                        $relation[$key]['param_name'] = json_decode($val['param_name'],true);
                    }
                }
                $list[$k]['relation'] = $relation;
            }
        }
        $this->assign(['list'=>$list,'page'=>$Page->show(),'orderid'=>$orderid,'status'=>$status]);
        $this->meta_title = '订单管理';
        $this->display();
    }
    /**
     * 查看详情
     */
    function seedetail(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $Order_mod = M('Order');
        $info = $Order_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('参数错误');
        }
        $this->assign(['info'=>$info]);
        $html = $this->fetch();
        $this->ajaxReturn(array('status'=>'1','info'=>$html));
    }
    /**
     * 商品发货
     */
    function delivery(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $Order_mod = M('Order');
        $info = $Order_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('参数错误');
        }
        if($info['status'] != '1' || $info['send_type'] != '0'){
            $this->error('订单状态不允许发货');
        }
        if(IS_POST){
            $express_id = I('post.express_id');                         //快递公司
            $express_number = I('post.express_number');         //快递单号
            if($express_number == ''){
                $this->error('请填写快递单号');
            }
            $save = array();
            $save['send_type'] = '1';               //已发货
            $save['express_id'] = $express_id;
            $save['express_number'] = $express_number;
            $save['send_time'] = NOW_TIME;
            $isupdate = $Order_mod->where(['id'=>$id])->save($save);
            if(!$isupdate){
                $this->error('执行失败,请刷新重试');
            }
            $this->success('执行成功');
        }else{
            $this->assign(['info'=>$info]);
            $html = $this->fetch();
            $this->ajaxReturn(array('status'=>'1','info'=>$html));
        }
    }
    /**
     * 交易关闭
     */
    function close(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $Order_mod = M('Order');
        $info = $Order_mod->where(['id'=>$id])->find();
        if($info['status'] != '0'){
            $this->error('只有未支付的订单才可以关闭交易');
        }
        $save = [];
        $save['status'] = '2';
        $isupdate = $Order_mod->where(['id'=>$id])->save($save);
        if(!$isupdate){
            $this->error('执行失败,请刷新重试');
        }
        $this->success('执行成功');
    }

}