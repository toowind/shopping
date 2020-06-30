<?php
namespace Wap\Controller;
class ApiController extends BaseController{

    protected function _initialize(){
        if(IS_AJAX == false){
            $this->ajaxReturn(['status'=>'0','info'=>'请求方式错误']);
        }
        parent::_initialize();
    }
    /**
     * 获取各个订单状态的数量
     */
    function getOrderNum(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        //获取代付款的订单数
        $Order_mod = M('Order');
        $map = [];
        $map['uid'] = $uid;
        $map['status'] = '0';
        $map['send_type'] = '0';
        $count1 = $Order_mod->where($map)->count();
        //获取待发货的订单数
        $map['status'] = '1';
        $count2 = $Order_mod->where($map)->count();
        //获取待收货的订单数
        $map['status'] = '1';
        $map['send_type'] = '1';
        $count3 = $Order_mod->where($map)->count();
        //获取已完成的订单数
        $map['status'] = '1';
        $map['send_type'] = '2';
        $count4 = $Order_mod->where($map)->count();
        //返回值
        $return = [
            'status'=>'1',
            'info'=>[
                'notpayment'=>$count1,
                'notsending'=>$count2,
                'notcollect'=>$count3,
                'iscomplete'=>$count4
            ]
        ];
        $this->ajaxReturn($return);
    }
    /**
     * 取消订单
     */
    function cancelOrder(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $orderid = I('post.orderid','','trim');      //订单号
        if(!$orderid){
            $this->error('订单号不存在');
        }
        $Order_mod = M('Order');
        //开启事务
        $Order_mod->startTrans();
        $field = 'id,uid,status';
        $info = $Order_mod->where(['orderid'=>$orderid])->field($field)->lock(true)->find();
        if(!$info){
            $Order_mod->rollback();
            $this->error('订单不存在');
        }elseif($info['uid'] != $uid){
            $Order_mod->rollback();
            $this->error('这个订单不是你的');
        }elseif($info['status'] != '0'){
            $Order_mod->rollback();
            $this->error('只有未支付的订单才可以取消');
        }
        $save = [];
        $save['status'] = '2';        //交易关闭
        $save['last_time'] = NOW_TIME;
        $isupdate = $Order_mod->where(['orderid'=>$orderid])->save($save);
        if(!$isupdate){
            $Order_mod->rollback();
            $this->error('操作失败');
        }
        $Order_mod->commit();
        $this->ajaxReturn(['status'=>'1','info'=>'取消成功']);
    }
    /**
     * 确认收货
     */
    function confirmOrder(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $orderid = I('post.orderid','','trim');      //订单号
        if(!$orderid){
            $this->error('订单号不存在');
        }
        $Order_mod = M('Order');
        //开启事务
        $Order_mod->startTrans();
        $field = 'id,uid,status,send_type';
        $info = $Order_mod->where(['orderid'=>$orderid])->field($field)->lock(true)->find();
        if(!$info){
            $Order_mod->rollback();
            $this->error('订单不存在');
        }elseif($info['uid'] != $uid){
            $Order_mod->rollback();
            $this->error('这个订单不是你的');
        }elseif($info['status'] != '1' || $info['send_type'] != '1'){
            $Order_mod->rollback();
            $this->error('不允许修改订单状态');
        }
        $save = [];
        $save['send_type'] = '2';
        $save['last_time'] = NOW_TIME;
        $isupdate = $Order_mod->where(['orderid'=>$orderid])->save($save);
        if(!$isupdate){
            $Order_mod->rollback();
            $this->error('执行失败');
        }
        $Order_mod->commit();
        $this->ajaxReturn(['status'=>'1','info'=>'交易完成']);
    }
    /**
     * 获取订单详情
     */
    function getOrderInfo(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $orderid = I('get.orderid','','trim');      //订单号
        if(!$orderid){
            $this->error('订单号不存在');
        }
        $Order_mod = M('Order');
        //查询条件
        $map = [];
        $map['orderid'] = $orderid;
        //查询字段
        $field = 'uid,total_price,username,mobile,province,city,county,address,content,status,send_type,add_time,express_id,express_number,send_time';
        $info = $Order_mod->where($map)->field($field)->find();
        if(!$info){
            $this->error('订单不存在');
        }elseif($info['uid'] != $uid){
            $this->error('当前订单不是你的');
        }
        $Relation_mod = M('OrderRelation');
        $relation = $Relation_mod->where($map)->field('goods_id,price,number,total_price,title,thumb,param_name')->select();
        if($relation){
            foreach($relation as $key=>$val){
                $relation[$key]['param'] = json_decode($val['param_name'],true);
                unset($relation[$key]['param_name']);
            }
        }
        $info['relation'] = $relation;
        if($info['send_type'] == '2'){
            if($info['express_id'] == '1'){
                $info['express_name'] = '圆通';
            }elseif($info['express_id'] == '2'){
                $info['express_name'] = '韵达';
            }elseif($info['express_id'] == '3'){
                $info['express_name'] = '申通';
            }elseif($info['express_id'] == '4'){
                $info['express_name'] = '中通';
            }elseif($info['express_id'] == '5'){
                $info['express_name'] = '顺丰';
            }elseif($info['express_id'] == '6'){
                $info['express_name'] = '全峰';
            }elseif($info['express_id'] == '7'){
                $info['express_name'] = '天天';
            }
            unset($info['express_id']);
        }else{
            unset($info['express_id']);
            unset($info['express_number']);
            unset($info['send_time']);
        }
        unset($info['uid']);
        //返回值
        $return = [
            'status'=>'1',
            'info'=>$info
        ];
        $this->ajaxReturn($return);
    }
    /**
     * 获取订单列表
     */
    function getOrderList(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $status = I('get.status',0,'intval');       //订单状态,0:全部订单;1:未付款,2:待发货;3:已发货;4:已完成;5:交易关闭
        $page = I('get.page',1,'intval');            //页数
        if(!in_array($status,[0,1,2,3,4,5])){
            $this->error('订单状态不正确');
        }
        $Order_mod = M('Order');
        $map = [];
        $map['uid'] = $uid;
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
        //查询字段
        $field = 'id,orderid,total_price,express_id,express_number,send_time,add_time,status,send_type';
        //分页条件(多取出一条来判断是否有下一页)
        $limit = (($page-1)*10).',11';
        //排序条件
        $order = 'id DESC';
        //查询数据
        $list = $Order_mod->where($map)->field($field)->order($order)->limit($limit)->select();
        if(!$list){     //没有数据,返回空值
            $this->ajaxReturn(['status'=>'1','info'=>[]]);
        }
        $Relation_mod = M('OrderRelation');
        $hasnext = count($list) == '11' ? '1' : '0';
        $result = [];
        $i = 0;
        foreach($list as $k=>$v){
            if($hasnext == '1' && $k == '10'){
                break;
            }else{
                $result[$i]['orderid'] = $v['orderid'];
                $result[$i]['total_price'] = $v['total_price'];
                $result[$i]['add_time'] = $v['add_time'];
                $result[$i]['status'] = $v['status'];
                $result[$i]['send_type'] = $v['send_type'];
                $where = [];
                $where['orderid'] = $v['orderid'];
                $relation = $Relation_mod->where($where)->field('goods_id,price,number,total_price,title,thumb,param_name')->select();
                if($relation){
                    foreach($relation as $key=>$val){
                        $relation[$key]['param'] = json_decode($val['param_name'],true);
                        unset($relation[$key]['param_name']);
                    }
                }
                $result[$i]['relation'] = $relation;
                if(in_array($status,[3,4])  || $v['send_type'] == 2){
                    if($v['express_id'] == '1'){
                        $result[$i]['express_name'] = '圆通';
                    }elseif($v['express_id'] == '2'){
                        $result[$i]['express_name'] = '韵达';
                    }elseif($v['express_id'] == '3'){
                        $result[$i]['express_name'] = '申通';
                    }elseif($v['express_id'] == '4'){
                        $result[$i]['express_name'] = '中通';
                    }elseif($v['express_id'] == '5'){
                        $result[$i]['express_name'] = '顺丰';
                    }elseif($v['express_id'] == '6'){
                        $result[$i]['express_name'] = '全峰';
                    }elseif($v['express_id'] == '7'){
                        $result[$i]['express_name'] = '天天';
                    }
                    $result[$i]['express_number'] = $v['express_number'];
                    $result[$i]['send_time'] = $v['send_time'];
                }
                $i++;
            }
        }
        //返回值
        $return = [
            'status'=>'1',
            'info'=>[
                'list'=>$result,
                'hasnext'=>$hasnext
            ]
        ];
        $this->ajaxReturn($return);
    }
    /**
     * 提交订单
     */
    function submitOrder(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $goods_id = I('post.goods_id',0,'intval');       //获取商品ID
        $param_ids = I('post.param_ids','','trim');      //获取规格参数ID
        $number = I('post.number',1,'intval');               //商品数量
        $address_id = I('post.address_id',0,'intval');  //地址ID
        $content = I('post.content','','trim');               //用户留言
        if(!$goods_id){
            $this->error('商品ID不存在');
        }elseif(mb_strlen($content,'utf-8') > 140){
            $this->error('留言不能超过140个字符');
        }
        $Goods_mod = M('Goods');
        //查询字段
        $field = 'id,title,thumb,virtual_stock_num,status';
        $info = $Goods_mod->where(['id'=>$goods_id])->field($field)->find();
        if(!$info){
            $this->error('商品不存在');
        }elseif($info['status'] != '1'){
            $this->error('商品已下架');
        }elseif($info['virtual_stock_num'] == '0'){
            $this->error('商品库存不够了');
        }elseif($info['virtual_stock_num'] < $number){
            $this->error('购买的数量大于库存');
        }
        if($param_ids == ''){
            $this->error('规格参数ID不存在');
        }elseif($address_id == 0){
            $this->error('请选择地址');
        }
        //获取规格参数名称
        $Param_mod = M('NatureFormatParam');
        $where = [];
        $where['id'] = ['in',$param_ids];
        $order = 'field(id,'.$param_ids.')';
        $paramNames = $Param_mod->where($where)->field('name')->order($order)->select();
        if(!$paramNames){
            $this->error('规格参数不正确');
        }
        $paramNames = getSubByKey($paramNames,'name');
        $paramNames = json_encode($paramNames);
        //解析地址
        $Address_mod = M('Address');
        $addressInfo = $Address_mod->where(['id'=>$address_id])->find();
        if(!$addressInfo){
            $this->error('提交的地址不存在');
        }elseif($addressInfo['uid'] != $uid){
            $this->error('提交的地址不是你的');
        }
        $params = explode(',',$param_ids);
        sort($params);
        $GoodsParam_mod = M('GoodsParam');
        //开启事务
        $GoodsParam_mod->startTrans();
        $map = [];
        $map['goods_id'] = $goods_id;
        $map['relation'] = implode(',',$params);
        $info2 = $GoodsParam_mod->where($map)->field('id,price,virtual_stock_num')->lock(true)->find();
        if(!$info2){
            $GoodsParam_mod->rollback();        //事务回滚
            $this->error('规格明细不存在');
        }elseif($info2['virtual_stock_num'] == '0'){
            $GoodsParam_mod->rollback();
            $this->error('商品库存不够了');
        }elseif($info2['virtual_stock_num'] < $number){
            $GoodsParam_mod->rollback();
            $this->error('购买的数量大于库存');
        }
        //总商品减去虚拟库存
        $isDec = $Goods_mod->where(['id'=>$goods_id])->setDec('virtual_stock_num',$number);
        //商品明细减去虚拟库存
        $isDec2 = $GoodsParam_mod->where(['id'=>$info2['id']])->setDec('virtual_stock_num',$number);
        //获取订单号
        $orderid = build_orderid();
        //计算总价格
        $totalPrice = $info2['price'] * $number;
        //提交订单
        $Order_mod = M('Order');
        //插入订单表
        $insert = [];
        $insert['orderid'] = $orderid;
        $insert['uid'] = $uid;
        $insert['total_price'] = $totalPrice;
        $insert['username'] = $addressInfo['username'];
        $insert['mobile'] = $addressInfo['mobile'];
        $insert['province'] = $addressInfo['province'];
        $insert['city'] = $addressInfo['city'];
        $insert['county'] = $addressInfo['county'];
        $insert['address'] = $addressInfo['address'];
        $insert['content'] = htmlspecialchars($content);
        $insert['status'] = '0';                                    //未付款
        $insert['add_time'] = NOW_TIME;                //下单时间
        $id = $Order_mod->add($insert);
        $Relation_mod = M('OrderRelation');
        //插入订单关系表
        $insert2 = [];
        $insert2['orderid'] = $orderid;
        $insert2['uid'] = $uid;
        $insert2['goods_id'] = $goods_id;
        $insert2['params_id'] = $info2['id'];
        $insert2['price'] = $info2['price'];
        $insert2['total_price'] = $totalPrice;
        $insert2['number'] = $number;
        $insert2['title'] = $info['title'];
        $insert2['thumb'] = $info['thumb'];
        $insert2['param_id'] = implode(',',$params);
        $insert2['param_name'] = $paramNames;
        $insert2['add_time'] = NOW_TIME;
        $isRelation = $Relation_mod->add($insert2);
        //事务完成性判断
        if($isDec && $isDec2 && $id && $isRelation){
            $GoodsParam_mod->commit();          //事务提交
            //返回值
            $return = [
                'status'=>'1',
                'info'=>[
                    'orderid'=>$orderid
                ]
            ];
            $this->ajaxReturn($return);
        }else{
            $GoodsParam_mod->rollback();        //事务回滚
            $this->error('订单提交失败');
        }
    }
    /**
     * 删除地址
     */
    function delAddress(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $address_id = I('post.address_id',0,'intval');      //要删除的地址ID
        if(!$address_id){
            $this->error('未选择要删除的地址');
        }
        $Address_mod = M('Address');
        //查询条件
        $map = [];
        $map['id'] = $address_id;
        $info = $Address_mod->where($map)->field('id,uid')->find();
        if(!$info){
            $this->error('要删除的地址不存在');
        }elseif($info['uid'] != $uid){
            $this->error('要删除的地址不是你的');
        }
        //从数据库删除
        $isDel = $Address_mod->where($map)->delete();
        if(!$isDel){
            $this->error('删除失败');
        }
        $this->ajaxReturn(['status'=>'1','info'=>'删除成功']);
    }
    /**
     * 设置地址为默认
     */
    function setDefaultAddress(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $address_id = I('post.address_id',0,'intval');      //要设置默认的地址ID
        if(!$address_id){
            $this->error('未选择要设置的地址');
        }
        $Address_mod = M('Address');
        //查询条件
        $map = [];
        $map['id'] = $address_id;
        $info = $Address_mod->where($map)->field('id,uid,is_default')->find();
        if(!$info){
            $this->error('要设置的地址不存在');
        }elseif($info['uid'] != $uid){
            $this->error('要设置的地址不是你的');
        }
        //是否应是默认的了
        if($info['is_default'] == '1'){
            $this->ajaxReturn(['status'=>'1','info'=>'设置成功']);
        }
        //否则重新设置
        $Address_mod->where(['uid'=>$uid])->save(['is_default'=>'0']);
        $Address_mod->where($map)->save(['is_default'=>'1']);
        $this->ajaxReturn(['status'=>'1','info'=>'设置成功']);
    }
    /**
     * 获取所有的地址
     */
    function getAddress(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $Address_mod = M('Address');
        //查询条件
        $map = [];
        $map['uid'] = $uid;
        //排序条件
        $order = 'is_default = 1 DESC,id DESC';
        //查询数据
        $list = $Address_mod->where($map)->order($order)->select();
        if(!$list){     //没有数据,返回空值
            $this->ajaxReturn(['status'=>'1','info'=>[]]);
        }
        $result = [];
        foreach($list as $k=>$v){
            unset($v['uid']);
            unset($v['add_time']);
            $result[$k] = $v;
        }
        //返回值
        $return = [
            'status'=>'1',
            'info'=>$result
        ];
        $this->ajaxReturn($return);
    }
    /**
     * 编辑收货地址
     */
    function editAddress(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $address_id = I('post.address_id',0,'intval');      //要编辑的地址ID
        if(!$address_id){
            $this->error('未选择要编辑的地址');
        }
        $Address_mod = M('Address');
        //查询条件
        $map = [];
        $map['id'] = $address_id;
        $info = $Address_mod->where($map)->field('add_time',true)->find();
        if(!$info){
            $this->error('要编辑的地址不存在');
        }elseif($info['uid'] != $uid){
            $this->error('要编辑的地址不是你的');
        }
        $username = I('post.username','','trim');                   //收货人
        $mobile = I('post.mobile','','trim');                             //手机号
        $province = I('post.province','','trim');                       //省
        $city = I('post.city','','trim');                                     //市
        $county = I('post.county','','trim');                            //县
        $address = I('post.address','','trim');                        //详细地址
        $is_default = I('post.is_default',0,'intval');               //是否为默认地址
        if($username == ''){
            $this->error('请填写收货人');
        }elseif($mobile == ''){
            $this->error('请填写手机号');
        }elseif(!checkMobile($mobile)){
            $this->error('手机号格式不正确');
        }elseif($province == '' || $city == '' || $county == ''){
            $this->error('请选择所在地区');
        }elseif($address == ''){
            $this->error('请填写详细地址');
        }elseif(mb_strlen($address,'utf-8') > 150){
            $this->error('详细地址长度超过了限制');
        }
        //如果设置了默认地址
        if($is_default == 1){
            $Address_mod->where(['uid'=>$uid])->save(['is_default'=>'0']);
        }
        //编辑到数据库
        $save = [];
        $save['username'] = $username;
        $save['mobile'] = $mobile;
        $save['province'] = $province;
        $save['city'] = $city;
        $save['county'] = $county;
        $save['address'] = $address;
        $save['is_default'] = $is_default;
        $Address_mod->where($map)->save($save);
        $this->ajaxReturn(['status'=>'1','info'=>'编辑成功']);
    }
    /**
     * 新增收货地址
     */
    function addAddress(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }
        $Address_mod = M('Address');
        //查询条件
        $map = [];
        $map['uid'] = $uid;
        //地址最多添加5个
        $count = $Address_mod->where($map)->count();
        if($count > 5){
            $this->error('地址最多添加5个');
        }
        $username = I('post.username','','trim');                   //收货人
        $mobile = I('post.mobile','','trim');                             //手机号
        $province = I('post.province','','trim');                       //省
        $city = I('post.city','','trim');                                     //市
        $county = I('post.county','','trim');                            //县
        $address = I('post.address','','trim');                        //详细地址
        $is_default = I('post.is_default',0,'intval');               //是否为默认地址
        if($username == ''){
            $this->error('请填写收货人');
        }elseif($mobile == ''){
            $this->error('请填写手机号');
        }elseif(!checkMobile($mobile)){
            $this->error('手机号格式不正确');
        }elseif($province == '' || $city == '' || $county == ''){
            $this->error('请选择所在地区');
        }elseif($address == ''){
            $this->error('请填写详细地址');
        }elseif(mb_strlen($address,'utf-8') > 150){
            $this->error('详细地址长度超过了限制');
        }
        //如果设置了默认地址
        if($is_default == 1){
            $Address_mod->where($map)->save(['is_default'=>'0']);
        }
        //添加到数据库
        $insert = [];
        $insert['uid'] = $uid;
        $insert['username'] = $username;
        $insert['mobile'] = $mobile;
        $insert['province'] = $province;
        $insert['city'] = $city;
        $insert['county'] = $county;
        $insert['address'] = $address;
        $insert['is_default'] = $is_default;
        $insert['add_time'] = NOW_TIME;
        $isAdd = $Address_mod->add($insert);
        if(!$isAdd){
            $this->error('添加收货地址失败');
        }
        $this->ajaxReturn(['status'=>'1','info'=>'添加成功']);
    }
    /**
     * 获取当前用户信息
     */
    function getUserInfo(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $uid = getUidByToken();
        if($uid == '0'){        //未登录
            $this->error('用户未登录');
        }else{
            $userinfo = M('User')->where(['uid'=>$uid])->field('mobile,status')->find();
            //返回值
            $return = [
                'status'=>'1',
                'info'=>$userinfo,
            ];
            $this->ajaxReturn($return);
        }
    }
    /**
     * 退出登录
     */
    function loginOut(){
        cookie('TOKEN',null);
        cookie('TOKEN_ID',null);
        $this->ajaxReturn(['status'=>'1','info'=>'退出成功']);
    }
    /**
     * 手机登录
     */
    function mobileLogin(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $mobile = I('post.mobile','','trim');             //手机号
        $verify = I('post.verify','','trim');              //验证码
        if($mobile == ''){
            $this->error('请输入手机号');
        }elseif(!checkMobile($mobile)){
            $this->error('手机号格式不正确');
        }elseif($verify == ''){
            $this->error('请输入验证码');
        }
        //查询条件
        $map = [
            'mobile'=>$mobile
        ];
        //检测短信
        $Send_mod = M('MobileSend');
        $sendinfo = $Send_mod->where($map)->find();
        if(!$sendinfo){
            $this->error('没有短信记录');
        }
        //短信是否过期(10分钟)
        if($sendinfo['add_time'] + 600 < NOW_TIME){
            $this->error('验证码超时,请重新发送');
        }
        //验证码是否正确
        if($sendinfo['value'] != $verify){
            $this->error('验证码不正确');
        }
        $Mobile_mod = M('Mobile');
        $info = $Mobile_mod->where($map)->find();
        if($info){          //已注册
            $uid = $info['uid'];
            //删除短信记录
            $Send_mod->where($map)->delete();
        }else{                //未注册
            $User_mod = M('User');
            //开启事务
            $User_mod->startTrans();
            //添加到用户表
            $insert = [];
            $insert['mobile'] = $mobile;
            $insert['last_login_time'] = NOW_TIME;
            $insert['last_login_ip'] = get_client_ip(1,true);
            $insert['status'] = '1';
            $insert['reg_ip'] = get_client_ip(1,true);
            $insert['reg_time'] = NOW_TIME;
            $uid = $User_mod->add($insert);
            //添加到手机关联表
            $insert2 = [];
            $insert2['mobile'] = $mobile;
            $insert2['uid'] = $uid;
            $insert2['add_time'] = NOW_TIME;
            $isbind = $Mobile_mod->add($insert2);
            //删除短信记录
            $isdel = $Send_mod->where($map)->delete();
            //判断事务的完整性
            if($uid && $isbind && $isdel){
                $User_mod->commit();            //事务提交
            }else{
                $User_mod->rollback();          //事务回滚
                $this->error('登录失败');
            }
        }
        //生成cookie
        $cookie = [
            'uid'=>$uid,
            'add_time'=>NOW_TIME
        ];
        $token = serialize($cookie);
        $token = think_encrypt($token);
        $token_id = md5($token.'ldzsshop');
        cookie('TOKEN',$token);
        cookie('TOKEN_ID',$token_id);
        //返回值
        $this->ajaxReturn(['status'=>'1','info'=>'登录成功']);
    }
    /**
     * 发送验证码
     */
    function sendSms(){
        if(!IS_POST){
            $this->error('请求类型错误');
        }
        $mobile = I('post.mobile','','trim');       //需要短信验证的手机号
        $type = I('post.type',0,'intval');           //验证类型,1:绑定手机;2:找回密码;3:登录用户
        if($mobile == ''){
            $this->error('请填写手机号');
        }elseif(!checkMobile($mobile)){
            $this->error('手机号格式不正确');
        }elseif(!in_array($type,[1,2,3])){
            $this->error('验证类型不正确');
        }
        $Mobile_mod = M('Mobile');
        if($type == 1){                     //绑定手机
            $this->error('功能未开放');
        }elseif($type == 2){            //找回密码
            $this->error('功能未开放');
        }
        $Send_mod = M('MobileSend');
        //生成随机验证码
        $value = mt_rand(100000,999999);
        //判断是否已经存在记录
        $sendinfo = $Send_mod->where(['mobile'=>$mobile])->find();
        if($sendinfo){
            $save = [];
            //判断时间内的有效性(60秒)
            if($sendinfo['add_time'] + 60 > NOW_TIME){
                $this->error('60秒只能发送一次');
            }
            //判断次数的有效性(3次)
            $datetime = strtotime(date('Y-m-d'));
            if($sendinfo['add_time'] >= $datetime && $sendinfo['total'] > 3){
                $this->error('发送次数超过了限制');
            }elseif($sendinfo['add_time'] < $datetime){
                $save['total'] = '1';       //重置次数
            }else{                                  //次数++
                $save['total'] = ['exp','total+1'];
            }
            //如果两次的发送内容一致的话
            if($value == $sendinfo['value']){
                while($value == $sendinfo['value']){
                    $value = mt_rand(100000,999999);
                }
            }
            $save['value'] = $value;
            $save['add_time'] = NOW_TIME;
            if(!$Send_mod->where(['mobile'=>$mobile])->save($save)){
                $this->error('短信发送失败');
            }
        }else{
            $insert = [];
            $insert['mobile'] = $mobile;
            $insert['value'] = $value;
            $insert['add_time'] = NOW_TIME;
            if(!$Send_mod->add($insert)){
                $this->error('短信发送失败');
            }
            //发送短信
            if(!message_send($mobile,[$value,'10分钟'],313398)){
                $this->error('短信发送失败');
            }
        }
        $this->ajaxReturn(['status'=>'1','info'=>'发送成功']);
    }
    /**
     * 获取商品的规格明细信息
     */
    function getGoodsParam(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $goods_id = I('get.goods_id',0,'intval');       //获取商品ID
        if(!$goods_id){
            $this->error('商品ID不存在');
        }
        $Goods_mod = M('Goods');
        $map = [];
        $map['id'] = $goods_id;
        $info = $Goods_mod->where($map)->field('id,status')->find();
        if(!$info){
            $this->error('商品不存在');
        }
        $param_ids = I('get.param_ids','','trim');      //获取规格参数ID
        if($param_ids == ''){
            $this->error('规格参数ID不存在');
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
            $this->error('规格明细不存在');
        }
        //返回值
        $return = [];
        $return['price'] = $info['price'];
        $return['stock_num'] = $info['virtual_stock_num'];
        $this->ajaxReturn(['status'=>'1','info'=>$return]);
    }
    /**
     * 获取商品详情
     */
    function getGoodsInfo(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $goods_id = I('get.goods_id',0,'intval');       //获取商品ID
        if(!$goods_id){
            $this->error('商品ID不存在');
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
        $field = 'id,title,selling_point,thumb,images,price,price_end,line_price,virtual_stock_num,sales_num,format_id,param_id,status';
        $info = $Goods_mod->where($map)->field($field)->find();
        if(!$info){
            $this->error('商品不存在');
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
            $where['id'] = ['in',$param_id];
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
            $params[$k]['param_ids'] = explode(',',$v['param_id']);
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
        $return['line_price'] = $info['line_price'];                                               //划线价
        $return['stock_num'] = $info['virtual_stock_num'];                               //商品剩余(虚拟库存)
        $return['sales_num'] = $info['sales_num'];                                             //商品销量
        $return['content'] = $content;                                                                  //商品详情
        $return['formats'] = $formats;                                                                 //规格参数
        $return['params'] = $params;                                                                    //规格明细
        $return['status'] = $info['status'];
        $this->ajaxReturn(['status'=>'1','info'=>$return]);
    }
    /**
     * 获取专题详情
     */
    function getSpecialInfo(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        $special_id = I('get.special_id',0,'intval');       //获取专题ID
        if(!$special_id){
            $this->error('专题ID不存在');
        }
        $Special_mod = M('Special');
        $map = [
            'id'=>$special_id
        ];
        $info = $Special_mod->where($map)->field('add_time',true)->find();
        if(!$info){
            $this->error('专题不存在');
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
        $return['start_time'] =$info['start_time'];
        $return['end_time'] =$info['end_time'];
        $return['status'] = $info['status'];                                                //专题上下架状态
        $return['catelist'] = $list;
        $this->ajaxReturn(['status'=>'1','info'=>$return]);
    }
    /**
     * 获取首页数据
     */
    function getHomeInfo(){
        if(!IS_GET){
            $this->error('请求类型错误');
        }
        //获取轮播
        if(false === $focus = S('goods_focus')){
            $map = array();
            $map['status'] = '1';
            $field = 'id,name,thumb,jump,goods_id,url';
            $order = 'sort DESC';
            $focus = M('GoodsFocus')->where($map)->field($field)->order($order)->select();
            $focus = $focus ? $focus : [];
            S('goods_focus',$focus,259200);
        }
        //获取置顶
        $table = '__GOODS_TOP__ a';
        $join = '__GOODS__ b on a.goods_id = b.id';
        $order = 'a.sort DESC,a.id DESC';
        $field = 'b.id,b.title,b.thumb,b.price,b.virtual_stock_num as stock_num,b.status';
        $list = M()->table($table)->join($join)->field($field)->order($order)->select();
        //取专题
        $Special_mod = M('Special');
        $map = [
            'id'=>['in','1,2,3']
        ];
        $special = $Special_mod->where($map)->limit(3)->field('id,name,list_banner')->select();
        //返回值
        $return = [];
        $return['focus'] = $focus;
        $return['goods_list'] = $list;
        $return['special'] = $special;
        $this->ajaxReturn(['status'=>'1','info'=>$return]);
    }
}
