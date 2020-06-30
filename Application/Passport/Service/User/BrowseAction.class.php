<?php
/*浏览记录 */
namespace Passport\Service\User;
use Common\Action\BaseAction;
use Common\Exception\Exception;
use Passport\Model\UserBroweGoodsModel;
use Passport\Model\UserCollectionModel;
use Product\Service\Product\ProductAction;
use Think\Log;

class BrowseAction extends BaseAction{

    //查询浏览记录
    public static function getBrowseList($data){
        $page = $data["page"] < 1 ? 1 : $data["page"];
        $pageSize = $data["page_size"] >30 ? 30 : $data["page_size"];
        $userId = $GLOBALS["userId"];
        $platformId = $GLOBALS["platformId"];

        $page = ($page-1)*$pageSize;
        $userBrowseGoodsModel = new UserBroweGoodsModel();
        $data = $userBrowseGoodsModel->getBrowseList($userId,$platformId,$page,$pageSize);
        if(empty($data)){
            return array();
        }
        $goodsIdList = array_values(array_unique(array_column($data,"goods_id")));
        //查询商品信息
        $goodsInfo = ProductAction::getGoodsList(["goods_id_list"=>$goodsIdList]);

        foreach ($data as $key=>$val){
            $key = $val["goods_id"];
            $newData["$key"] = $val;
        }
        foreach($goodsInfo as $key=>$val){
            $goods_id = $val["goods_id"];
            $goods["$goods_id"] = $val;
        }

        $Response = array();
        foreach($newData as $key=>$val){
            $k = date("Y-m-d",$val["browse_time"]);
            $key = $val["goods_id"];
            if(isset($goods[$val["goods_id"]])){
                $Response[$k][] = $goods[$val["goods_id"]];
            }
        }

        return $Response;
    }
}
