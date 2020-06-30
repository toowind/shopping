<?php
/**
 * 收藏表
 */
namespace Passport\Model;
use Think\Model;

class UserCollectionModel extends Model{
    protected $tableName  = "user_collection";
    public function addColection($goods_id,$userId,$state){
        //插入收藏表
        $platformId = $GLOBALS["platformId"];
        $where = [
            "uid"=>":uid",
            "goods_id"=>":goods_id",
            "platformId"=>":platformId"
        ];
        $bind = [
            ":uid"=>[$userId,\PDO::PARAM_INT],
            ":goods_id"=>[$goods_id,\PDO::PARAM_INT],
            ":platformId"=>[$platformId,\PDO::PARAM_INT]
        ];
        $sta = $this->bind($bind)->where($where)->find();

        $time = time();
        $data = [
            "uid"=>":uid",
            "goods_id"=>":goods_id",
            "state"=>":state",
            "platformId"=>":platformId"
        ];
        $bind2 = [
            ":uid"=>[$userId,\PDO::PARAM_INT],
            ":goods_id"=>[$goods_id,\PDO::PARAM_INT],
            ":state"=>[$state,\PDO::PARAM_INT],
            ":platformId"=>[$platformId,\PDO::PARAM_INT]
        ];
        if(empty($sta)){
            $data["update_time"] = $time;
            $data["create_time"] = $time;
            $obj = $this->bind($bind2)->add($data);
        }else{
            $data["update_time"] = $time;
            $obj = $this->bind($bind2)->where(["id"=>$sta["id"]])->save($data);
        }
        return $obj;
    }
    //收藏列表
    public function getColectionList($userId,$platformId,$page,$pageSize){
        $where = [
            "uid"=>":uid",
            "platformId"=>":platformId",
            "state"=>1
        ];
        $bind = [
            ":uid"=>[$userId,\PDO::PARAM_INT],
            ":platformId"=>[$platformId,\PDO::PARAM_INT],
        ];
        $data = $this->field("id,uid,goods_id,state,platformId,update_time,create_time")
            ->bind($bind)
            ->where($where)
            ->order("update_time desc")
            ->limit($page,$pageSize)
            ->select();
        return $data;
    }

    //查询是否收藏
    public function getCollectionByGoodsId($userId,$platformId,$goodsId){
        $where = [
            "uid"=>":uid",
            "platformId"=>":platformId",
            "goods_id"=>":goods_id"
        ];
        $bind = [
            ":uid"=>[$userId,\PDO::PARAM_INT],
            ":platformId"=>[$platformId,\PDO::PARAM_INT],
            ":goods_id"=>[$goodsId,\PDO::PARAM_INT],
        ];
        $data = $this->field("id,uid,goods_id,state,platformId,update_time,create_time")
            ->bind($bind)
            ->where($where)
            ->find();
        return $data;
    }

    //平台收藏榜单
    public function CollectionList($offset,$limit){
        $data = $this->field("id,uid,goods_id,state,platformId,update_time,create_time")->order("num desc")->limit($offset,$limit)->select();
        return $data;
    }

}