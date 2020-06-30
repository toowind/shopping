<?php
namespace Admin\Controller;
class SpecialController extends AdminController{

    /**
     * 专题列表
     */
    function index(){
        if(IS_AJAX && $_GET['type'] == 'selectGoods'){
            $html = $this->fetch('selectGoods');
            $this->ajaxReturn(array('status'=>'1','info'=>$html));
        }elseif(IS_AJAX && $_GET['type'] == 'selectGoodsList'){
            $page = I('get.page',1,'intval');
            $limit = I('get.limit',10,'intval');
            $Goods_mod = M('Goods');
            $map = [];
            $map['status'] = '1';
            $order = 'id DESC';
            $field = 'id,title,thumb,add_time';
            //获取总数
            $count = $Goods_mod->where($map)->count();
            if($count == 0){
                $this->ajaxReturn(['code'=>'0','msg'=>'','count'=>'0']);
            }
            //获取列表
            $list = $Goods_mod->where($map)->page($page,$limit)->field($field)->order($order)->select();
            $result = [];
            foreach($list as $k=>$v){
                $result[$k]['id'] = $v['id'];
                $result[$k]['title'] = $v['title'];
                $result[$k]['img'] = $v['thumb'];
                $result[$k]['thumb'] = '<img src="'.$v['thumb'].'" style="width:25px;height:25px"/>';
                $result[$k]['times'] = date('Y-m-d H:i:s',$v['add_time']);
            }
            $return = [
                'code'=>'0',
                'msg'=>'',
                'count'=>$count,
                'data'=>$result
            ];
            $this->ajaxReturn($return);
        }
        $Special_mod = M('Special');
        $status = I('get.status',1,'intval');
        $keyword = I('get.keyword','','trim');
        $map = [];
        if($status == '1'){
            $map['status'] = $status;
        }else{
            $map['status'] = '0';
        }
        if($keyword){
            $map['name'] = ['like','%'.$keyword.'%'];
        }
        $count = $Special_mod->where($map)->count();
        //分页处理
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $Page = new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit = $Page->firstRow.','.$Page->listRows;
        $order = 'id DESC';
        $list = $Special_mod->where($map)->order($order)->limit($limit)->select();
        if($list){
            foreach($list as $k=>$v){
                $list[$k]['start_time'] = date('Y-m-d H:i:s',$v['start_time']);
                $list[$k]['end_time'] = date('Y-m-d H:i:s',$v['end_time']);
            }
        }
        $this->assign(['list'=>$list,'page'=>$Page->show(),'keyword'=>$keyword,'status'=>$status]);
        $this->meta_title = '专题管理';
        $this->display();
    }
    /**
     * 专题下架
     */
    function del(){
        $id = array_unique((array)I('ids',0));
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据!');
        }
        $Special_mod = M('Special');
        $map = [];
        $save = [];
        $map['id'] = array('in',$ids);
        $save['status'] = '0';
        if($Special_mod->where($map)->save($save)){

        }
        $this->success('下架成功');
    }
    /**
     * 专题上架
     */
    function grounding(){
        $id = array_unique((array)I('ids',0));
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据!');
        }
        $Special_mod = M('Special');
        $map = [];
        $save = [];
        $map['id'] = array('in',$ids);
        $save['status'] = '1';
        if($Special_mod->where($map)->save($save)){

        }
        $this->success('上架成功');
    }
    /**
     * 编辑专题
     */
    function edit(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $Special_mod = M('Special');
        $info = $Special_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');                               //专题名称
            $list_banner = I('post.list_banner','','trim');             //列表banner图
            $special_banner = I('post.special_banner','','trim');   //专题banner图
            $ac_time_bg = I('post.ac_time_bg','','trim');
            $cate_bg = I('post.cate_bg','','trim');
            $bg = I('post.bg','','trim');
            $goods_sm_icon = I('post.goods_sm_icon','','trim');         //商品小图标
            $start_time = I('post.start_time','','trim');       //活动开始时间
            $end_time = I('post.end_time','','trim');           //活动结束时间
            $status = I('post.status',1,'intval');
            if($name == ''){
                $this->error('请填写专题名称');
            }elseif($list_banner == ''){
                $this->error('请上传列表banner图');
            }elseif($special_banner == ''){
                $this->error('请上传专题banner图');
            }elseif($ac_time_bg == ''){
                $this->error('请选择活动时间背景颜色');
            }elseif($cate_bg == ''){
                $this->error('请选择分类背景颜色');
            }elseif($bg == ''){
                $this->error('请选择背景颜色');
            }elseif($start_time == '' || $end_time == ''){
                $this->error('请选择活动时间');
            }
            //分类名称1
            $names_one = I('post.names_one','','trim');
            $sort_one = I('post.sort1');
            if($sort_one){
                if($names_one == ''){
                    $this->error('请填写分类名称1');
                }
                $sort_one = implode(',',$sort_one);
            }
            //分类名称2
            $names_two = I('post.names_two','','trim');
            $sort_two = I('post.sort2');
            if($sort_two){
                if($names_two == ''){
                    $this->error('请填写分类名称1');
                }
                $sort_two = implode(',',$sort_two);
            }
            //分类名称3
            $names_three = I('post.names_three','','trim');
            $sort_three= I('post.sort3');
            if($sort_three){
                if($names_three == ''){
                    $this->error('请填写分类名称1');
                }
                $sort_three = implode(',',$sort_three);
            }
            //编辑入库
            $save = [
                'name'=>$name,
                'list_banner'=>$list_banner,
                'special_banner'=>$special_banner,
                'ac_time_bg'=>$ac_time_bg,
                'cate_bg'=>$cate_bg,
                'bg'=>$bg,
                'goods_sm_icon'=>$goods_sm_icon,
                'start_time'=>strtotime($start_time),
                'end_time'=>strtotime($end_time),
                'names_one'=>$names_one,
                'sort_one'=>$sort_one,
                'names_two'=>$names_two,
                'sort_two'=>$sort_two,
                'names_three'=>$names_three,
                'sort_three'=>$sort_three,
                'status'=>$status,
            ];
            $isupdate = M('Special')->where(['id'=>$id])->save($save);
            if($isupdate){
                $this->success('编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            $Goods_mod = M('Goods');
            //提取分类1下的商品
            if($info['sort_one']){
                $where = [];
                $where['id'] = ['in',$info['sort_one']];
                $field = 'id,title,thumb';
                $order = 'field(id,'.$info['sort_one'].')';
                $info['sort_one'] = $Goods_mod->where($where)->field($field)->order($order)->select();
            }else{
                $info['sort_one'] = [];
            }
            //提取分类2下的商品
            if($info['sort_two']){
                $where = [];
                $where['id'] = ['in',$info['sort_two']];
                $order = 'field(id,'.$info['sort_two'].')';
                $info['sort_two'] = $Goods_mod->where($where)->field($field)->order($order)->select();
            }else{
                $info['sort_two'] = [];
            }
            //提取分类3下的商品
            if($info['sort_three']){
                $where = [];
                $where['id'] = ['in',$info['sort_three']];
                $order = 'field(id,'.$info['sort_three'].')';
                $info['sort_three'] = $Goods_mod->where($where)->field($field)->order($order)->select();
            }else{
                $info['sort_three'] = [];
            }
            $this->assign(['info'=>$info]);
            $this->meta_title = '编辑专题';
            $this->display();
        }
    }
    /**
     * 添加专题
     */
    function add(){
        if(IS_POST){
            $name = I('post.name','','trim');                               //专题名称
            $list_banner = I('post.list_banner','','trim');             //列表banner图
            $special_banner = I('post.special_banner','','trim');   //专题banner图
            $ac_time_bg = I('post.ac_time_bg','','trim');
            $cate_bg = I('post.cate_bg','','trim');
            $bg = I('post.bg','','trim');
            $goods_sm_icon = I('post.goods_sm_icon','','trim');         //商品小图标
            $start_time = I('post.start_time','','trim');       //活动开始时间
            $end_time = I('post.end_time','','trim');           //活动结束时间
            $status = I('post.status',1,'intval');
            if($name == ''){
                $this->error('请填写专题名称');
            }elseif($list_banner == ''){
                $this->error('请上传列表banner图');
            }elseif($special_banner == ''){
                $this->error('请上传专题banner图');
            }elseif($ac_time_bg == ''){
                $this->error('请选择活动时间背景颜色');
            }elseif($cate_bg == ''){
                $this->error('请选择分类背景颜色');
            }elseif($bg == ''){
                $this->error('请选择背景颜色');
            }elseif($start_time == '' || $end_time == ''){
                $this->error('请选择活动时间');
            }
            //分类名称1
            $names_one = I('post.names_one','','trim');
            $sort_one = I('post.sort1');
            if($sort_one){
                if($names_one == ''){
                    $this->error('请填写分类名称1');
                }
                $sort_one = implode(',',$sort_one);
            }
            //分类名称2
            $names_two = I('post.names_two','','trim');
            $sort_two = I('post.sort2');
            if($sort_two){
                if($names_two == ''){
                    $this->error('请填写分类名称1');
                }
                $sort_two = implode(',',$sort_two);
            }
            //分类名称3
            $names_three = I('post.names_three','','trim');
            $sort_three= I('post.sort3');
            if($sort_three){
                if($names_three == ''){
                    $this->error('请填写分类名称1');
                }
                $sort_three = implode(',',$sort_three);
            }
            //添加入库
            $insert = [
                'name'=>$name,
                'list_banner'=>$list_banner,
                'special_banner'=>$special_banner,
                'ac_time_bg'=>$ac_time_bg,
                'cate_bg'=>$cate_bg,
                'bg'=>$bg,
                'goods_sm_icon'=>$goods_sm_icon,
                'start_time'=>strtotime($start_time),
                'end_time'=>strtotime($end_time),
                'names_one'=>$names_one,
                'sort_one'=>$sort_one,
                'names_two'=>$names_two,
                'sort_two'=>$sort_two,
                'names_three'=>$names_three,
                'sort_three'=>$sort_three,
                'status'=>$status,
                'add_time'=>NOW_TIME
            ];
            $id = M('Special')->add($insert);
            if($id){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->meta_title = '添加专题';
            $this->display();
        }
    }

}