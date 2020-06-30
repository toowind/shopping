<?php
/**
 * 浏览商品记录表
 */
namespace Passport\Model;
use Common\Exception\Exception;
use Passport\Common\ApiException;
use Think\Model;

class UserBroweGoodsModel extends Model{
    protected $trueTableName  = "shop_user_browse_goods";
    //添加商品浏览记录
    public function addBrowse($goods_id){
        $data = [
            "uid"=>":uid",
            "goods_id"=>":goods_id",
            "platformId"=>":platformId",
            "browse_time"=>time(),
            "date"=>strtotime(date("Y-m-d"))
        ];
        $bind = [
            ":uid" => [$GLOBALS["userId"],\PDO::PARAM_INT],
            ":goods_id" => [$goods_id,\PDO::PARAM_INT],
            ":platformId" => [$GLOBALS["platformId"],\PDO::PARAM_INT],

        ];
        $obj = $this->bind($bind)->add($data);
        if(!$obj){
            ApiException::throwException(ApiException::ADD_BROWSE_ERROR);
        }
        return $obj;
    }

    //查询用户今天是否浏览过该商品
    public function fieldList($goods_id){
        $date = strtotime(date("Y-m-d"));
        $where = [
            "uid"=>":uid",
            "platformId"=>":platformId",
            "date"=>$date,
            "goods_id"=>":goods_id"
        ];
        $bind = [
            ":uid" => [$GLOBALS["userId"],\PDO::PARAM_INT],
            ":platformId" => [$GLOBALS["platformId"],\PDO::PARAM_INT],
            ":goods_id" =>[$goods_id,\PDO::PARAM_INT]
        ];
        $data = $this->field("id,uid,goods_id,platformId,date,browse_time")->bind($bind)->where($where)->select();
        return $data;
    }

    //查询用户最近七天的浏览记录
    public function getBrowseList($userId,$platformId,$page,$pageSize){
        //七天前的时间戳
        $time = strtotime(date("Y-m-d H:i:s",strtotime("-7 day")));
        $where = [
            "uid"=>":uid",
            "platformId"=>":platformId",
            "browse_time"=>array("egt",$time)
        ];
        $bind = [
            ":uid"=>[$userId,\PDO::PARAM_INT],
            ":platformId"=>[$platformId,\PDO::PARAM_INT],
        ];
        $data = $this->field("id,uid,goods_id,platformId,date,browse_time")
            ->bind($bind)
            ->where($where)
            ->order("browse_time desc")
            ->limit($page,$pageSize)->select();
        return $data;
    }

}