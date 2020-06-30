<?php
namespace Admin\Controller;
class NatureController extends AdminController{

    /**
     * 类型列表
     */
    function index(){
        $NatureType_mod = M('NatureType');
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
        $count = $NatureType_mod->where($map)->count();
        //分页处理
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $Page = new \Think\Page($count,$listRows);
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $limit = $Page->firstRow.','.$Page->listRows;
        $order = 'id DESC';
        $list = $NatureType_mod->where($map)->order($order)->limit($limit)->select();
        $this->assign(['list'=>$list,'page'=>$Page->show(),'keyword'=>$keyword,'status'=>$status]);
        $this->meta_title = '类型管理';
        $this->display();
    }
    /**
     * 属性列表
     */
    function attribute(){
        $id = I('get.id',0,'intval');
        $NatureType_mod = M('NatureType');
        if($id){
            $info = $NatureType_mod->where(['id'=>$id])->find();
            if(!$info){
                $this->error('不存在或已经删除');
            }
            $Attribute_mod = M('NatureAttribute');
            $Param_mod = M('NatureAttributeParam');
            $map = [];
            $map['type_id'] = $id;
            $count = $Attribute_mod->where($map)->count();
            //分页处理
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
            $Page = new \Think\Page($count,$listRows);
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $limit = $Page->firstRow.','.$Page->listRows;
            $order = 'sort ASC,id DESC';
            $list = $Attribute_mod->where($map)->order($order)->limit($limit)->select();
            if($list){
                foreach($list as $k=>$v){
                    $where = [
                        'attribute_id'=>$v['id']
                    ];
                    $params = $Param_mod->where($where)->field('name')->select();
                    $list[$k]['param'] = $params;
                }
            }
            $this->assign(['list'=>$list,'page'=>$Page->show()]);
        }
        //查询所有已启用的类型
        $map = [
            'status'=>'1'
        ];
        $order = 'id DESC';
        $field = 'id,name';
        $types = $NatureType_mod->where($map)->order($order)->field($field)->select();
        $this->assign(['id'=>$id,'types'=>$types]);
        $this->meta_title = '属性管理';
        $this->display();
    }
    /**
     * 添加属性
     */
    function attributeadd(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $NatureType_mod = M('NatureType');
        $info = $NatureType_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');
            $mold = I('post.mold',0,'intval');
            $sort = I('post.sort',0,'intval');
            $param = I('post.param');
            if($name == ''){
                $this->error('请填写规格名称');
            }elseif(!$param){
                $this->error('请添加规格参数');
            }elseif($sort > 999){
                $this->error('排序值最高999');
            }
            $Attribute_mod = M('NatureAttribute');
            $Param_mod = M('NatureAttributeParam');
            //开启事务
            $Attribute_mod->startTrans();
            $insert = [
                'type_id'=>$info['id'],
                'name'=>$name,
                'sort'=>$sort
            ];
            $addid = $Attribute_mod->add($insert);
            //添加参数
            $insert2 = [];
            foreach($param as $v){
                $insert2[] = [
                    'attribute_id'=>$addid,
                    'name'=>$v
                ];
            }
            $isadd = $Param_mod->addAll($insert2);
            //对类型增加一次规格数量的关联数
            $isinc = $NatureType_mod->where(['id'=>$id])->setInc('attribute_num',1);
            //事务判断
            if($addid && $isadd && $isinc){
                $Attribute_mod->commit();       //事务提交
                $this->success('添加成功');
            }else{
                $Attribute_mod->rollback();     //事务回滚
                $this->error('添加失败');
            }
        }else{
            $this->assign(['info'=>$info]);
            $this->meta_title = '添加属性';
            $this->display();
        }
    }
    /**
     * 编辑属性
     */
    function attributeedit(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $NatureType_mod = M('NatureType');
        $info = $NatureType_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        $Attribute_mod = M('NatureAttribute');
        $Param_mod = M('NatureAttributeParam');
        $attribute_id = I('get.attribute_id',0,'intval');
        $info2 = $Attribute_mod->where(['id'=>$attribute_id])->find();
        if(!$info2){
            $this->error('不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');
            $mold = I('post.mold',0,'intval');
            $sort = I('post.sort',0,'intval');
            $param = I('post.param');
            if($name == ''){
                $this->error('请填写规格名称');
            }elseif(!$param){
                $this->error('请添加规格参数');
            }elseif($sort > 999){
                $this->error('排序值最高999');
            }
            $save = [
                'name'=>$name,
                'mold'=>$mold,
                'sort'=>$sort
            ];
            $Attribute_mod->where(array('id'=>$attribute_id))->save($save);
            //参数是否有添加
            foreach($param as $v){
                $where = [
                    'attribute_id'=>$info2['id'],
                    'name'=>$v
                ];
                if($Param_mod->where($where)->count() == 0){
                    $insert = [
                        'attribute_id'=>$info2['id'],
                        'name'=>$v
                    ];
                    $Param_mod->add($insert);
                }
            }
            //参数是否有删除
            $where = [
                'attribute_id'=>$info2['id']
            ];
            $params = $Param_mod->where($where)->field('id,name')->select();
            foreach($params as $v){
                if(!in_array($v['name'],$param)){
                    $Param_mod->where(['id'=>$v['id']])->delete();
                }
            }
            $this->success('编辑成功');
        }else{
            $where = [
                'attribute_id'=>$info2['id']
            ];
            $info2['param'] = $Param_mod->where($where)->field('name')->select();
            $this->assign(['info'=>$info,'info2'=>$info2]);
            $this->meta_title = '编辑属性';
            $this->display();
        }
    }
    /**
     * 删除属性
     */
    function attributedel(){
        $id = array_unique((array)I('ids',0));
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据!');
        }
        $NatureType_mod = M('NatureType');
        $Attribute_mod = M('NatureAttribute');
        $Param_mod = M('NatureAttributeParam');
        foreach($ids as $v){
            $info = $Attribute_mod->find($v);
            if($info){
                //开启事务
                $Attribute_mod->startTrans();
                $isDel = $Attribute_mod->where(['id'=>$v])->delete();
                $where = [
                    'attribute_id'=>$v
                ];
                $isDel2 = $Param_mod->where($where)->delete();
                $isDec = $NatureType_mod->where(['id'=>$info['type_id']])->setDec('attribute_num',1);
                if($isDel && $isDel2 && $isDec){
                    $Attribute_mod->commit();       //事务提交
                }else{
                    $Attribute_mod->rollback();     //事务回滚
                    $this->error('删除失败');
                }
            }
        }
        $this->success('删除成功');
    }
    /**
     * 属性排序
     */
    function setAttributeSort(){
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
        $Attribute_mod = M('NatureAttribute');
        if($Attribute_mod->save($save)){

        }
        $this->success('排序成功');
    }
    /**
     * 规格列表
     */
    function format(){
        $id = I('get.id',0,'intval');
        $NatureType_mod = M('NatureType');
        if($id){
            $info = $NatureType_mod->where(['id'=>$id])->find();
            if(!$info){
                $this->error('不存在或已经删除');
            }
            $Format_mod = M('NatureFormat');
            $Param_mod = M('NatureFormatParam');
            $map = [];
            $map['type_id'] = $id;
            $count = $Format_mod->where($map)->count();
            //分页处理
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
            $Page = new \Think\Page($count,$listRows);
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $limit = $Page->firstRow.','.$Page->listRows;
            $order = 'sort ASC,id DESC';
            $list = $Format_mod->where($map)->order($order)->limit($limit)->select();
            if($list){
                foreach($list as $k=>$v){
                    $where = [
                        'format_id'=>$v['id']
                    ];
                    $params = $Param_mod->where($where)->field('name')->select();
                    $list[$k]['param'] = $params;
                }
            }
            $this->assign(['list'=>$list,'page'=>$Page->show()]);
        }
        //查询所有已启用的类型
        $map = [
            'status'=>'1'
        ];
        $order = 'id DESC';
        $field = 'id,name';
        $types = $NatureType_mod->where($map)->order($order)->field($field)->select();
        $this->assign(['id'=>$id,'types'=>$types]);
        $this->meta_title = '规格管理';
        $this->display();
    }
    /**
     * 规格排序
     */
    function setSort(){
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
        $Format_mod = M('NatureFormat');
        if($Format_mod->save($save)){

        }
        $this->success('排序成功');
    }
    /**
     * 删除规格
     */
    function formatedel(){
        $id = array_unique((array)I('ids',0));
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据!');
        }
        $NatureType_mod = M('NatureType');
        $Format_mod = M('NatureFormat');
        $Param_mod = M('NatureFormatParam');
        foreach($ids as $v){
            $info = $Format_mod->find($v);
            if($info){
                //开启事务
                $Format_mod->startTrans();
                $isDel = $Format_mod->where(['id'=>$v])->delete();
                $where = [
                    'format_id'=>$v
                ];
                $isDel2 = $Param_mod->where($where)->delete();
                $isDec = $NatureType_mod->where(['id'=>$info['type_id']])->setDec('format_num',1);
                if($isDel && $isDel2 && $isDec){
                    $Format_mod->commit();       //事务提交
                }else{
                    $Format_mod->rollback();     //事务回滚
                    $this->error('删除失败');
                }
            }
        }
        $this->success('删除成功');
    }
    /**
     * 编辑规格
     */
    function formatedit(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $NatureType_mod = M('NatureType');
        $info = $NatureType_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        $Format_mod = M('NatureFormat');
        $Param_mod = M('NatureFormatParam');
        $format_id = I('get.format_id',0,'intval');
        $info2 = $Format_mod->where(['id'=>$format_id])->find();
        if(!$info2){
            $this->error('不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');
            $sort = I('post.sort',0,'intval');
            $param = I('post.param');
            if($name == ''){
                $this->error('请填写规格名称');
            }elseif(!$param){
                $this->error('请添加规格参数');
            }elseif($sort > 999){
                $this->error('排序值最高999');
            }
            $save = [
                'name'=>$name,
                'sort'=>$sort
            ];
            $Format_mod->where(array('id'=>$format_id))->save($save);
            //参数是否有添加
            foreach($param as $v){
                $where = [
                    'format_id'=>$info2['id'],
                    'name'=>$v
                ];
                if($Param_mod->where($where)->count() == 0){
                    $insert = [
                        'format_id'=>$info2['id'],
                        'name'=>$v
                    ];
                    $Param_mod->add($insert);
                }
            }
            //参数是否有删除
            $where = [
                'format_id'=>$info2['id']
            ];
            $params = $Param_mod->where($where)->field('id,name')->select();
            foreach($params as $v){
                if(!in_array($v['name'],$param)){
                    $Param_mod->where(['id'=>$v['id']])->delete();
                }
            }
            $this->success('编辑成功');
        }else{
            $where = [
                'format_id'=>$info2['id']
            ];
            $info2['param'] = $Param_mod->where($where)->field('name')->select();
            $this->assign(['info'=>$info,'info2'=>$info2]);
            $this->meta_title = '编辑规格';
            $this->display();
        }
    }
    /**
     * 添加规格
     */
    function formatadd(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $NatureType_mod = M('NatureType');
        $info = $NatureType_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');
            $sort = I('post.sort',0,'intval');
            $param = I('post.param');
            if($name == ''){
                $this->error('请填写规格名称');
            }elseif(!$param){
                $this->error('请添加规格参数');
            }elseif($sort > 999){
                $this->error('排序值最高999');
            }
            $Format_mod = M('NatureFormat');
            $Param_mod = M('NatureFormatParam');
            //开启事务
            $Format_mod->startTrans();
            $insert = [
                'type_id'=>$info['id'],
                'name'=>$name,
                'sort'=>$sort
            ];
            $addid = $Format_mod->add($insert);
            //添加参数
            $insert2 = [];
            foreach($param as $v){
                $insert2[] = [
                    'format_id'=>$addid,
                    'name'=>$v
                ];
            }
            $isadd = $Param_mod->addAll($insert2);
            //对类型增加一次规格数量的关联数
            $isinc = $NatureType_mod->where(['id'=>$id])->setInc('format_num',1);
            //事务判断
            if($addid && $isadd && $isinc){
                $Format_mod->commit();       //事务提交
                $this->success('添加成功');
            }else{
                $Format_mod->rollback();     //事务回滚
                $this->error('添加失败');
            }
        }else{
            $this->assign(['info'=>$info]);
            $this->meta_title = '添加规格';
            $this->display();
        }
    }
    /**
     * 类型的启用与禁用
     */
    function setStatus(){
        $id = array_unique((array)I('ids',0));
        $status = I('get.status',0,'intval');
        $ids = array();
        foreach($id as $v){
            $v && $ids[] = $v;
        }
        if(!$ids){
            $this->error('请选择要删除的内容');
        }
        $NatureType_mod = M('NatureType');
        $map = [];
        $save = [];
        $map['id'] = array('in',$ids);
        $save['status'] = $status;
        if($NatureType_mod->where($map)->save($save)){

        }
        $this->success('修改成功');
    }
    /**
     * 删除类型
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
        $NatureType_mod = M('NatureType');
        $map = [];
        $save = [];
        $map['id'] = array('in',$ids);
        if($NatureType_mod->where($map)->delete()){

        }
        $this->success('删除成功');
    }
    /**
     * 编辑类型
     */
    function edit(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误');
        }
        $NatureType_mod = M('NatureType');
        $info = $NatureType_mod->where(['id'=>$id])->find();
        if(!$info){
            $this->error('不存在或已经删除');
        }
        if(IS_POST){
            $name = I('post.name','','trim');
            $status = I('post.status',0,'intval');
            if($name == ''){
                $this->error('请填写类型名称');
            }elseif(mb_strlen($name,'utf-8') > 50){
                $this->error('类型名称太长了');
            }
            $NatureType_mod = M('NatureType');
            //是否唯一
            $map = [
                'id'=>['neq',$id],
                'name'=>$name
            ];
            $count = $NatureType_mod->where($map)->count();
            if($count > 0){
                $this->error('类型名称重复了');
            }
            //修改数据库
            $save = [
                'name'=>$name,
                'status'=>$status
            ];
            $isup = $NatureType_mod->where(['id'=>$id])->save($save);
            if($isup){
                $this->success('编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $this->assign(['info'=>$info]);
            $this->meta_title = '编辑类型';
            $this->display();
        }
    }
    /**
     * 添加类型
     */
    function add(){
        if(IS_POST){
            $name = I('post.name','','trim');
            $status = I('post.status',0,'intval');
            if($name == ''){
                $this->error('请填写类型名称');
            }elseif(mb_strlen($name,'utf-8') > 50){
                $this->error('类型名称太长了');
            }
            $NatureType_mod = M('NatureType');
            //是否唯一
            $map = [
                'name'=>$name
            ];
            $count = $NatureType_mod->where($map)->count();
            if($count > 0){
                $this->error('类型名称重复了');
            }
            //插入数据库
            $insert = [
                'name'=>$name,
                'status'=>$status,
                'add_time'=>NOW_TIME
            ];
            $id = $NatureType_mod->add($insert);
            if($id){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->meta_title = '添加类型';
            $this->display();
        }

    }
}