<?php
namespace Passport\Service\User;
use Common\Action\BaseAction;
use Common\Exception\Exception;
use Passport\Model\UserCollectionModel;
use Passport\Model\UserCollectionStatisticsModel;
use Product\Service\Product\ProductAction;
use Think\Log;

class CollectionAction extends BaseAction{
    /*
     * 收藏商品
     * state = 0 取消收藏  1 收藏
     */
    public static function addColection($data,$userId,$state){
        $goods_id = $data["goods_id"];
        $goods_name = $data["goods_name"];
        $CollectionModel = new UserCollectionModel();
        $obj = $CollectionModel->addColection($goods_id,$userId,$state);
        if(!$obj){
            Exception::throwException(Exception::HTTP_ERROR);
        }
        //收藏计入收藏列表统计--不需要事务
        if($state == 1){
            $userCollectionStatiticsModel = new UserCollectionStatisticsModel();
            $res = $userCollectionStatiticsModel->addNum($goods_id,$goods_name);
        }
        return ["state"=>$state];
    }
    //我的收藏
    public static function getColectionList($data){
        $userId = $GLOBALS["userId"];
        $platformId = $GLOBALS["platformId"];
        $userColectionModel = new UserCollectionModel();
        $page = $data["page"] < 1 ? 1 : $data["page"];
        $page = ($page-1)*$data["page_size"];
        $pageSize = $data["pageSize"] > 10 ? 10 : $data["page_size"];
        $arr = $userColectionModel->getColectionList($userId,$platformId,$page,$pageSize);
        if(empty($arr)){
            return [];
        }
        $goodsIdList = array_values(array_unique(array_column($arr,"goods_id")));
        //查询商品信息
        $Response = ProductAction::getGoodsList(["goods_id_list"=>$goodsIdList]);
        return $Response;

    }
}
