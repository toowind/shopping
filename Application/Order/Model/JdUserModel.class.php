<?php
/**
 * 京东分销订单表
 */

namespace Order\Model;

use Think\Model;

class JdUserModel extends BaseModel
{
    /**
     * 获取用户信息
     * @param int $uid
     * @return mixed
     */
    public function getUserData($uid = 0) {
        return M('user', 'fxk_', $this->db_config)->where(['uid' => $uid])->find();
    }

    /**
     * 保存用户信息
     * @param $data
     * @return mixed
     */
    public function setUserData($data){
        return M('user', 'fxk_', $this->db_config)->add($data);
    }

    /**
     * 更新结算显示时间
     * @param int $uid
     * @return mixed
     */
    public function updateOrderShowTime($uid = 0) {
        return M('user', 'fxk_', $this->db_config)->where(['uid' => $uid])->save(['order_show_time' => date('Y-m-d')]);
    }

    /**
     * 提现到青豆
     * @param $uid
     * @param $money
     * @return bool
     */
    public function withdraw($uid, $money) {
        M()->startTrans();
        $save = [
            'now_money' => 0, //全部取现
        ];
        $m1 = M('user', 'fxk_', $this->db_config)->where(['uid' => $uid])->save($save);

        $install = [
            'uid' => $uid,
            'money' => $money,
            'time' => time(),
        ];
        $m2 = M('user_withdraw_log', 'fxk_', $this->db_config)->add($install);
        if ($m1 && $m2) {
            M()->commit();
            return true;
        } else {
            M()->rollback();
            return false;
        }
    }

}