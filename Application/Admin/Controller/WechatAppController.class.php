<?php


namespace Admin\Controller;


class WechatAppController extends AdminController{
    public function goodsLists(){
        $this->assign(array()
        );
        $this->meta_title='订单列表';
        $this->display();
    }
    public function add(){
        $this->display();
    }
}