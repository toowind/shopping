<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Passport\Model;
use Think\Model;
/**
 * 用户收藏统计表
 */
class UserCollectionStatisticsModel extends Model{
    protected $trueTableName  = "shop_user_collection_statistics";

    public function addNum($goodsId,$goodsName){
        $platformId = $GLOBALS["platformId"];
        $time = time();
       // $sql = "insert into ".$this->trueTableName."(platformId,goods_id,num,update_time,create_time) value('".$platformId."','".$goodsId."','1','".$time."','".$time."') ON DUPLICATE KEY UPDATE num = num +1,update_time = '".$time."'";
        $res = $this->bind([":platformId"=>[$platformId,\PDO::PARAM_INT],":goods_id"=>[$goodsId,\PDO::PARAM_INT]])
            ->where(["platformId"=>":platformId","goods_id"=>":goods_id"])
            ->find();
        if(!empty($res)){
            $res = $this->where(["id"=>$res["id"]])->save(["num"=>$res["num"]+1,"update_time"=>$time]);
        }else{
            $res = $this->bind([":platformId"=>[$platformId,\PDO::PARAM_INT],":goods_id"=>[$goodsId,\PDO::PARAM_INT],":goods_name"=>[$goodsName,\PDO::PARAM_STR]])
                ->add(["platformId"=>":platformId","goods_id"=>":goods_id","num"=>1,"update_time"=>$time,"create_time"=>$time,"goods_name"=>":goods_name"]);
        }
        return $res;
    }
    //获取收藏排行榜
    public function getList($offset,$limit){
        $data = $this->field("id,platformId,goods_id,num,update_time,create_time")
            ->bind([":platformId"=>[$GLOBALS["platformId"],\PDO::PARAM_INT]])
            ->where(["platformId"=>":platformId"])
            ->order("num desc")->limit($offset,$limit)->select();
        return $data;
    }
}