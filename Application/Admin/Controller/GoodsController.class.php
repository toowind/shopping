<?php
namespace Admin\Controller;
class GoodsController extends AdminController{

    /**
     * 置顶管理
     */
    function top(){
        $table = '__GOODS_TOP__ a';
        $join = '__GOODS__ b on a.goods_id = b.id';
        //查询订单总数
        $count = M()->table($table)->join($join)->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 20;
        $Page = new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit = $Page->firstRow.','.$Page->listRows;
        $order = 'a.sort DESC,a.id DESC';
        $field = 'a.sort,b.*';
        $list = M()->table($table)->join($join)->limit($limit)->field($field)->order($order)->select();
        $this->assign(array('list'=>$list,'page'=>$Page->show()));
        $this->meta_title = '置顶管理';
        $this->display();
    }
    /**
     * 删除置顶
     */
    function topdel(){
        $id = array_unique((array)I('ids',0));
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据!');
        }
        //删除这些选中的数据
        $map['goods_id'] = array('in',$ids);
        $GoodsTop_mod = M('GoodsTop');
        if($GoodsTop_mod->where($map)->delete()){

        }
        $this->success('删除成功');

    }
    /**
     * 置顶排序
     */
    function setTopSort(){
        $id = I('get.id');
        $sort = I('get.sort');
        if(!$id){
            $this->error('id不得为空');
        }
        if(!$sort && $sort != '0'){
            $this->error('排序不得为空');
        }
        if($sort > '999'){
            $this->error('排序值最大只允许999');
        }
        $map = [
            'goods_id'=>$id
        ];
        $save = array('sort'=>$sort);
        $GoodsTop_mod = M('GoodsTop');
        if($GoodsTop_mod->where($map)->save($save)){

        }
        $this->success('排序成功');
    }
    /**
     * 我的轮播
     */
    function focus(){
        $Focus_mod = M('GoodsFocus');
        $status = I('get.status');
        $map = array();
        if($status){
            if($status == '1'){
                $map['status'] = '1';
            }else{
                $map['status'] = '0';
            }
        }
        $count = $Focus_mod->where($map)->count();
        //分页处理
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $Page = new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit = $Page->firstRow.','.$Page->listRows;
        $order = 'sort DESC';
        //获取所有当前条件下的任务
        $list = $Focus_mod->where($map)->order($order)->limit($limit)->select();
        $this->assign(array('list'=>$list,'page'=>$Page->show(),'status'=>$status));
        $this->meta_title = '轮播管理';
        $this->display();
    }
    /**
     * 编辑轮播
     */
    function focusedit(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $Focus_mod = M('GoodsFocus');
        $info = $Focus_mod->where(array('id'=>$id))->find();
        if(!$info){
            $this->error('内容不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');
            $thumb = I('post.thumb','','trim');
            $jump = I('post.jump',0,'intval');
            $goods_id = I('post.goods_id',0,'intval');
            $status = I('post.status',0,'intval');
            $url = I('post.url','','trim');
            if($name == ''){
                $this->error('请填写轮播名称');
            }elseif($thumb == ''){
                $this->error('请上传封面图');
            }
            if($jump == 1 && !$goods_id){
                $this->error('请填写商品ID');
            }
            if($jump == 2 && !$url){
                $this->error('请填写跳转的链接地址');
            }
            $save = array();
            $save['name'] = $name;
            $save['thumb'] = $thumb;
            $save['jump'] = $jump;
            $save['goods_id'] = $goods_id;
            $save['url'] = $url;
            $save['status'] = $status;
            if($Focus_mod->where(array('id'=>$id))->save($save)){
                S('goods_focus',null);
                $this->success('编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $this->assign(array('info'=>$info));
            $this->meta_title = '编辑焦点图';
            $this->display();
        }
    }
    /**
     * 添加轮播
     */
    function focusadd(){
        if(IS_POST){
            $name = I('post.name','','trim');
            $thumb = I('post.thumb','','trim');
            $jump = I('post.jump',0,'intval');
            $goods_id = I('post.goods_id',0,'intval');
            $status = I('post.status',0,'intval');
            $url = I('post.url','','trim');
            if($name == ''){
                $this->error('请填写轮播名称');
            }elseif($thumb == ''){
                $this->error('请上传封面图');
            }
            if($jump == 1 && !$goods_id){
                $this->error('请填写商品ID');
            }
            if($jump == 2 && !$url){
                $this->error('请填写跳转的链接地址');
            }
            $insert = array();
            $insert['name'] = $name;
            $insert['thumb'] = $thumb;
            $insert['jump'] = $jump;
            $insert['goods_id'] = $goods_id;
            $insert['url'] = $url;
            $insert['status'] = $status;
            $insert['add_time'] = NOW_TIME;
            if(M('GoodsFocus')->add($insert)){
                S('goods_focus',null);
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->meta_title = '添加轮播';
            $this->display();
        }
    }
    /**
     * 设置轮播状态
     */
    function setfocusstatus(){
        $id = array_unique((array)I('ids',0));
        $status = I('get.status',0,'intval');
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(!$ids){
            $this->error('请选择要删除的内容');
        }
        $Focus_mod = M('GoodsFocus');
        $map = array();
        $save = array();
        $map['id'] = array('in',$ids);
        $save['status'] = $status;
        if($Focus_mod->where($map)->save($save)){
            S('goods_focus',null);
        }
        $this->success('修改成功');
    }
    /**
     * 删除轮播
     */
    function focusdel(){
        $id = array_unique((array)I('ids',0));
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据!');
        }
        //删除这些选中的数据
        $map['id'] = array('in',$ids);
        $Focus_mod = M('GoodsFocus');
        if($Focus_mod->where($map)->delete()){
            S('goods_focus',null);
        }
        $this->success('删除成功');
    }
    /**
     * 轮播排序
     */
    function setfocussort(){
        $id = I('get.id');
        $sort = I('get.sort');
        if(!$id){
            $this->error('id不得为空');
        }
        if(!$sort && $sort != '0'){
            $this->error('排序不得为空');
        }
        if($sort > '999'){
            $this->error('排序值最大只允许999');
        }
        $save = array('id'=>$id,'sort'=>$sort);
        $Focus_mod = M('GoodsFocus');
        if($Focus_mod->save($save)){
            S('goods_focus',null);
        }
        $this->success('排序成功');
    }
    /**
     * 商品列表
     */
    function index(){
        if(IS_AJAX && $_GET['type'] == 'cate'){        //获取分类
            $pid = I('get.pid',0,'intval');
            $map = [
                'pid'=>$pid,
                'status'=>'1'
            ];
            $sort = 'sort ASC,id DESC';
            $list = M('Category')->where($map)->field('id,title')->order($sort)->select();
            $list = $list ? $list : [];
            $this->ajaxReturn(['status'=>'1','info'=>$list]);
        }elseif(IS_AJAX && $_GET['type'] == 'format'){
            $type_id = I('get.type_id',0,'intval');
            $map = [
                'type_id'=>$type_id
            ];
            $sort = 'sort ASC,id DESC';
            $list = M('NatureFormat')->where($map)->field('id,name')->order($sort)->select();
            $list = $list ? $list : [];
            $this->ajaxReturn(['status'=>'1','info'=>$list]);
        }elseif(IS_AJAX && $_GET['type'] == 'param'){
            $format_id = I('get.format_id',0,'intval');
            $map = [
                'format_id'=>$format_id
            ];
            $list = M('NatureFormatParam')->where($map)->field('id,name')->select();
            $list = $list ? $list : [];
            $this->ajaxReturn(['status'=>'1','info'=>$list]);
        }elseif(IS_AJAX && $_POST['type'] == 'calculate'){       //计算
            $sequence = I('post.sequence');
            $data = I('post.data');
            $orders = [];
            foreach($sequence as $v){
                if($data[$v]){
                    $orders[] = $data[$v];
                }
            }
            $value = [];
            foreach($orders as $k=>$v){
                if($v){
                    $value[] = $v;
                }
            }
            $count = count($value);
            if($count == '1'){
                $return = [];
                foreach($value[0] as $v){
                    $return[][] = $v;
                }
                $this->ajaxReturn(['status'=>'1','info'=>$return]);
            }
            $return = $this->Descartes($value);
            $this->ajaxReturn(['status'=>'1','info'=>$return]);
        }elseif(IS_AJAX && $_POST['type'] == 'value'){
            $goods_id = I('post.goods_id');
            $value = I('post.value');
            $val = [];
            foreach($value as $v){
                if(substr($v,0,6) == 'datas_'){
                    $val[] = substr($v,6);
                }
            }
            $Param_mod = M('GoodsParam');
            $list = [];
            foreach($val as $v){
                $exs = explode('_',$v);
                sort($exs);
                $ex = implode(',',$exs);
                $where = [
                    'goods_id'=>$goods_id,
                    'relation'=>$ex
                ];
                $info = $Param_mod->where($where)->find();
                $list[] = [
                    'id'=>'datas_'.$v,          //价格
                    'value'=>$info['price']
                ];
                $list[] = [
                    'id'=>'datal_'.$v,              //库存
                    'value'=>$info['stock_num']
                ];
                $list[] = [
                    'id'=>'datax_'.$v,              //销量
                    'value'=>$info['sales_num']
                ];
            }
            $this->ajaxReturn(['status'=>'1','info'=>$list]);
        }
        $Goods_mod = M('Goods');
        $status = I('get.status',1,'intval');
        $keyword = I('get.keyword','','trim');
        $map = [];
        if($status == '1'){
            $map['status'] = $status;
        }else{
            $map['status'] = '0';
        }
        if($keyword){
            $map['title'] = ['like','%'.$keyword.'%'];
        }
        $count = $Goods_mod->where($map)->count();
        //分页处理
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $Page = new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit = $Page->firstRow.','.$Page->listRows;
        $order = 'id DESC';
        $list = $Goods_mod->where($map)->order($order)->limit($limit)->select();
        $this->assign(['list'=>$list,'page'=>$Page->show(),'keyword'=>$keyword,'status'=>$status]);
        $this->meta_title = '商品管理';
        $this->display();
    }
    /**
     * 商品置顶
     */
    function settop(){
        $id = I('get.ids',0,'intval');
        if(!$id){
            $this->error('操作失败');
        }
        $Goods_mod = M('Goods');
        $map = [];
        $map['id'] = $id;
        $info = $Goods_mod->where($map)->find();
        if(!$info){
            $this->error('商品不存在');
        }elseif($info['status'] != '1'){
            $this->error('该商品已经下架');
        }
        $GoodsTop_mod = M('GoodsTop');
        $map = [];
        $map['goods_id'] = $id;
        $count = $GoodsTop_mod->where($map)->count();
        if($count > 0){
            $this->error('当前商品已经置顶了');
        }
        $insert = [];
        $insert['goods_id'] = $id;
        $insert['sort'] = '0';
        $insert['add_time'] = NOW_TIME;
        if($GoodsTop_mod->add($insert)){
            $this->success('置顶成功');
        }else{
            $this->error('置顶失败');
        }
    }
    /**
     * 商品上架
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
        $Goods_mod = M('Goods');
        $map = [];
        $save = [];
        $map['id'] = array('in',$ids);
        $save['status'] = '1';
        if($Goods_mod->where($map)->save($save)){

        }
        $this->success('上架成功');
    }
    /**
     * 商品下架
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
        $Goods_mod = M('Goods');
        $map = [];
        $save = [];
        $map['id'] = array('in',$ids);
        $save['status'] = '0';
        if($Goods_mod->where($map)->save($save)){

        }
        $this->success('下架成功');
    }
    /**
     * 编辑商品
     */
    function edit(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $Goods_mod = M('Goods');
        $info = $Goods_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        $Data_mod = M('GoodsData');
        $info['content'] = $Data_mod->where(['id'=>$id])->getField('content');
        if(IS_POST){
            $title = I('post.title','','trim');
            $selling_point = I('post.selling_point','','trim');
            $thumb = I('post.thumb','','trim');
            $images = I('post.images');
            $catid_one = I('post.catid_one',0,'intval');
            $catid_two = I('post.catid_two',0,'intval');
            $catid_three = I('post.catid_three',0,'intval');
            $status = I('post.status',0,'intval');
            $content = I('post.content','','trim');
            if($title == ''){
                $this->error('请填写商品名称');
            }elseif($thumb == ''){
                $this->error('请上传封面图');
            }elseif(!$images){
                $this->error('请上传商品图');
            }elseif($catid_one == '0'){
                $this->error('请选择一级分类');
            }elseif($catid_two == '0'){
                $this->error('请选择二级分类');
            }elseif($catid_three == '0'){
                $this->error('请选择三级分类');
            }
            $images = json_encode($images);
            //获取类型ID
            $type_id = I('post.type',0,'intval');
            if($type_id == '0'){
                $this->error('请选择类型');
            }
            //获取规格排序ID
            $order_id = I('post.order');
            $order_id = implode(',',$order_id);
            if($order_id == ''){
                $this->error('请选择规格');
            }
            //获取规格ID
            $format_id = I('post.order');
            $format_ids = $format_id;
            $format_id = implode(',',$format_id);
            if($format_id == ''){
                $this->error('请选择规格');
            }
            //获取规格参数
            $param_id = I('post.param');
            $param_ids = $param_id;
            $param_id = implode(',',$param_id);
            if($param_id == ''){
                $this->error('请选择规格参数');
            }
            $FormatParam_mod = M('NatureFormatParam');
            //检验规格和参数的关联
            foreach($format_ids as $v){
                $where = [];
                $where['format_id'] = $v;
                $where['id'] = array('in',$param_ids);
                $count = $FormatParam_mod->where($where)->count();
                if($count == '0'){
                    $this->error('规格和参数有未关联数据');
                }
            }
            //获取规格明细
            $array = [];
            $priceList = [];
            $stock_num = 0;
            foreach($_POST as $k=>$v){
                if(substr($k,0,11) == 'price_datas'){
                    if(!$v){
                        $this->error('规则明细里填写不全');
                    }
                    $ex = explode('_',$k);
                    $last = substr($k,12);
                    unset($ex[0]);
                    unset($ex[1]);
                    $org = $ex;
                    sort($ex);      //升序
                    $ids = [];
                    foreach($ex as $val){
                        $ids[] = $val;
                    }
                    $ids = implode(',',$ids);
                    $priceList[] = $v;
                    $array[] = [
                        'param'=>$ids,
                        'param2'=>implode(',',$org),
                        'price'=>$v,            //售价
                        'stock_num'=>$_POST['stock_num_datal_'.$last]   //库存
                    ];
                    $stock_num += $_POST['stock_num_datal_'.$last];
                }
            }
            if(count($priceList) == '1'){
                $price = $priceList[0];
                $price_end = '0';
            }else{
                $max = max($priceList);
                $min = min($priceList);
                if($max == $min){
                    $price = $max;
                    $price_end = '0';
                }else{
                    $price = $min;
                    $price_end = $max;
                }
            }
            $line_price = I('post.line_price','','trim');       //划线价
            $Goods_mod = M('Goods');
            $Param_mod = M('GoodsParam');
            //开启事务
            $Goods_mod->startTrans();
            //编辑商品表
            $save = [
                'title'=>$title,
                'selling_point'=>$selling_point,
                'thumb'=>$thumb,
                'images'=>$images,
                'catid_one'=>$catid_one,
                'catid_two'=>$catid_two,
                'catid_three'=>$catid_three,
                'type_id'=>$type_id,
                'order_id'=>$order_id,
                'format_id'=>$format_id,
                'param_id'=>$param_id,
                'price'=>$price,
                'price_end'=>$price_end,
                'line_price'=>$line_price,
                'stock_num'=>$stock_num,
                'status'=>$status,
            ];
            $isUpdate = $Goods_mod->where(['id'=>$id])->save($save);
            //编辑商品详情
            $save2 = [
                'content'=>$content
            ];
            $isUpdate2 = $Data_mod->where(['id'=>$id])->save($save2);
            $param = [];
            $inc = 0;
            foreach($array as $k=>$v){
                $where = [
                    'goods_id'=>$id,
                    'relation'=>$v['param']
                ];
                //参数是否有添加
                if($Param_mod->where($where)->count() == 0){
                    $insert = [
                        'goods_id'=>$id,
                        'relation'=>$v['param'],
                        'param_id'=>$v['param2'],
                        'price'=>$v['price'],
                        'stock_num'=>$v['stock_num'],
                        'virtual_stock_num'=>$v['stock_num']
                    ];
                    $inc += $v['stock_num'];
                    $Param_mod->add($insert);
                }
                //参数是否有修改
                $info = $Param_mod->where($where)->find();
                if($info){
                    $save = [];
                    if($v['price'] != $info['price']){
                        $save['price'] = $v['price'];
                    }
                    if($v['stock_num'] != $info['stock_num']){
                        $save['stock_num'] = $v['stock_num'];
                    }
                    //是否有修改
                    if($save){
                        //修改库存只能加不能减
                        $ori_stock_num = $info['stock_num'];
                        $now_stock_num = $save['stock_num'];
                        if($now_stock_num < $ori_stock_num){
                            $this->error('库存只能增加不可以减少');
                        }
                        $increment = $now_stock_num - $ori_stock_num;
                        if($increment  > 0){        //虚拟库存进行增量
                            $save['virtual_stock_num'] = ['exp','virtual_stock_num+'.$increment];
                            $inc += $increment;
                        }
                        $Param_mod->where($where)->save($save);
                    }
                }
                $param[] = $v['param'];
            }
            //参数是否有删除
            $params = $Param_mod->where(['goods_id'=>$id])->select();
            $dec = 0;
            foreach($params as $v){
                if(!in_array($v['relation'],$param)){
                    if($v['stock_num'] - $v['sales_num'] > 0){
                        $this->error('只有库存售完才可以删除明细');
                    }
                    $dec += $v['sales_num'];
                    $Param_mod->where(['id'=>$v['id']])->delete();
                }
            }
            if($inc){
                $isUpdate3 = $Goods_mod->where(['id'=>$id])->setInc('virtual_stock_num',$inc);
            }
            if($dec){
                $isUpdate4 = $Goods_mod->where(['id'=>$id])->setDec('sales_num',$dec);
            }
            $Goods_mod->commit();       //事务提交
            $this->success('编辑成功');
        }else{
            $info['images'] = json_decode($info['images'],true);
            //查询所有已启用的类型
            $map = [
                'status'=>'1'
            ];
            $order = 'id DESC';
            $field = 'id,name';
            $types = M('NatureType')->where($map)->order($order)->field($field)->select();
            $this->assign(['types'=>$types,'info'=>$info]);
            $this->meta_title = '编辑商品';
            $this->display();
        }
    }
    /**
     * 添加商品
     */
    function add(){
        if(IS_POST){
            if($_GET['type'] == 'imageUp'){        //上传图片
                $imgsrc = $this->uploadPic($_FILES['upfile']);
                $this->ajaxReturn(array('state'=>'SUCCESS','url'=>$imgsrc));
            }elseif($_GET['type'] == 'catcherUp'){      //抓取远程图片
                $imgsrc = $this->fetchPic($_POST['upfile']);
                $this->ajaxReturn(array('tip'=>'远程图片抓取成功！','url'=>$imgsrc,'srcUrl'=>$_POST['upfile']));
            }
            $title = I('post.title','','trim');
            $selling_point = I('post.selling_point','','trim');
            $thumb = I('post.thumb','','trim');
            $images = I('post.images');
            $catid_one = I('post.catid_one',0,'intval');
            $catid_two = I('post.catid_two',0,'intval');
            $catid_three = I('post.catid_three',0,'intval');
            $status = I('post.status',0,'intval');
            $content = I('post.content','','trim');
            if($title == ''){
                $this->error('请填写商品名称');
            }elseif($thumb == ''){
                $this->error('请上传封面图');
            }elseif(!$images){
                $this->error('请上传商品图');
            }elseif($catid_one == '0'){
                $this->error('请选择一级分类');
            }elseif($catid_two == '0'){
                $this->error('请选择二级分类');
            }elseif($catid_three == '0'){
                $this->error('请选择三级分类');
            }
            $images = json_encode($images);
            //获取类型ID
            $type_id = I('post.type',0,'intval');
            if($type_id == '0'){
                $this->error('请选择类型');
            }
            //获取规格排序ID
            $order_id = I('post.order');
            $order_id = implode(',',$order_id);
            if($order_id == ''){
                $this->error('请选择规格');
            }
            //获取规格ID
            $format_id = I('post.order');
            $format_ids = $format_id;
            $format_id = implode(',',$format_id);
            if($format_id == ''){
                $this->error('请选择规格');
            }
            //获取规格参数
            $param_id = I('post.param');
            $param_ids = $param_id;
            $param_id = implode(',',$param_id);
            if($param_id == ''){
                $this->error('请选择规格参数');
            }
            $FormatParam_mod = M('NatureFormatParam');
            //检验规格和参数的关联
            foreach($format_ids as $v){
                $where = [];
                $where['format_id'] = $v;
                $where['id'] = array('in',$param_ids);
                $count = $FormatParam_mod->where($where)->count();
                if($count == '0'){
                    $this->error('规格和参数有未关联数据');
                }
            }
            //获取规格明细
            $array = [];
            $priceList = [];
            $stock_num = 0;
            foreach($_POST as $k=>$v){
                if(substr($k,0,11) == 'price_datas'){
                    if(!$v){
                        $this->error('规则明细里填写不全');
                    }
                    $ex = explode('_',$k);
                    $last = substr($k,12);
                    unset($ex[0]);
                    unset($ex[1]);
                    $org = $ex;
                    sort($ex);      //升序
                    $ids = [];
                    foreach($ex as $val){
                        $ids[] = $val;
                    }
                    $ids = implode(',',$ids);
                    $priceList[] = $v;
                    $array[] = [
                        'param'=>$ids,
                        'param2'=>implode(',',$org),
                        'price'=>$v,            //售价
                        'stock_num'=>$_POST['stock_num_datal_'.$last]   //库存
                    ];
                    $stock_num += $_POST['stock_num_datal_'.$last];
                }
            }
            if(count($priceList) == '1'){
                $price = $priceList[0];
                $price_end = '0';
            }else{
                $max = max($priceList);
                $min = min($priceList);
                if($max == $min){
                    $price = $max;
                    $price_end = '0';
                }else{
                    $price = $min;
                    $price_end = $max;
                }
            }
            $line_price = I('post.line_price','','trim');       //划线价
            $Goods_mod = M('Goods');
            $Data_mod = M('GoodsData');
            $Param_mod = M('GoodsParam');
            //开启事务
            $Goods_mod->startTrans();
            //插入商品表
            $insert = [
                'title'=>$title,
                'selling_point'=>$selling_point,
                'thumb'=>$thumb,
                'images'=>$images,
                'catid_one'=>$catid_one,
                'catid_two'=>$catid_two,
                'catid_three'=>$catid_three,
                'type_id'=>$type_id,
                'order_id'=>$order_id,
                'format_id'=>$format_id,
                'param_id'=>$param_id,
                'price'=>$price,
                'price_end'=>$price_end,
                'line_price'=>$line_price,
                'stock_num'=>$stock_num,
                'virtual_stock_num'=>$stock_num,
                'status'=>$status,
                'add_time'=>NOW_TIME
            ];
            $goods_id = $Goods_mod->add($insert);
            //插入商品详情表
            $insert = [
                'id'=>$goods_id,
                'content'=>$content
            ];
            $is_relation = $Data_mod->add($insert);
            $i = 0;
            $count = count($array);
            foreach($array as $k=>$v){
                $insert = [
                    'goods_id'=>$goods_id,
                    'relation'=>$v['param'],
                    'param_id'=>$v['param2'],
                    'price'=>$v['price'],
                    'stock_num'=>$v['stock_num'],
                    'virtual_stock_num'=>$v['stock_num'],       //虚拟库存
                ];
                $isadd = $Param_mod->add($insert);
                if($isadd){
                    $i++;
                }
            }
            if($goods_id && $count == $i){
                $Goods_mod->commit();       //事务提交
                $this->success('添加成功');exit;
            }else{
                $Goods_mod->rollback();     //事务回滚
                $this->error('添加失败');
            }
        }else{
            //查询所有已启用的类型
            $map = [
                'status'=>'1'
            ];
            $order = 'id DESC';
            $field = 'id,name';
            $types = M('NatureType')->where($map)->order($order)->field($field)->select();
            $this->assign(['types'=>$types]);
            $this->meta_title = '添加商品';
            $this->display();
        }
    }
    /**
     * 上传图片
     * @param $picAttribute
     */
    private function uploadPic($picAttribute){
        //创建远程目录
        $pathName = date_rand_dir(1);
        //生成图片地址
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $fileName = uniqid().$chars[mt_rand(0,25)].'.jpg';
        //生成标示前缀
        $prefix = 'list';
        //完整路径
        $fullPath = $prefix.'/'.$pathName.'/'.$fileName;
        $key = str_replace('/','_',$fullPath);
        //实例化七牛
        $storage = storage('Qiniu');
        //上传图片到七牛
        $avatar = $storage->put($key,file_get_contents($picAttribute['tmp_name']));
        return $avatar;
    }
    /**
     * 上传图片
     * @param $avatar
     */
    private function fetchPic($avatar){
        $avatar = explode('ue_separate_ue',$avatar);
        $tmpNames = array();
        foreach($avatar as $k=>$v){
            //创建远程目录
            $pathName = date_rand_dir(1);
            //生成图片地址
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            $fileName = uniqid().$chars[mt_rand(0,25)].'.jpg';
            //生成标示前缀
            $prefix = 'list';
            //完整路径
            $fullPath = $prefix.'/'.$pathName.'/'.$fileName;
            $key = str_replace('/','_',$fullPath);
            //实例化七牛
            $storage = storage('Qiniu');
            //上传图片到七牛
            $tmpNames[] = $storage->fetchUrl($key,$v);
        }
        return implode('ue_separate_ue' ,$tmpNames);
    }
    public function Descartes($t) {
        if(count($t) == 1) {                               // 判断参数个数是否为1
            return $t;  // 回调当前函数，并把第一个数组作为参数传入
        }
        $a = array_shift($t);        // 将 $t 中的第一个元素移动到 $a 中，$t 中索引值重新排序
        if( !is_array($a) ) {
            $a = array($a);
        }

        $a = array_chunk($a, 1);     // 分割数组 $a ，为每个单元1个元素的新数组
        do {
            $r = array();
            $b = array_shift($t);
            if( !is_array($b) ) {
                $b = array($b);
            }
            foreach($a as $p) {
                foreach(array_chunk($b, 1) as $q) {
                    $r[] = array_merge($p, $q);
                }
            }
            $a = $r;
        } while($t);

        return $r;
    }
}