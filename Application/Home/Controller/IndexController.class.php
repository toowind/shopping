<?php
namespace Home\Controller;
class IndexController extends HomeController{

    function index(){
        echo '默认访问位置';die;
        $this->redirect('/mobile');
    }

}