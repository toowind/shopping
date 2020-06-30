<?php
namespace Wap\Controller;
class AlipayController extends BaseController{

    private $appId = '2018110962102563';

    private $rsaPrivateKey = 'MIIEpAIBAAKCAQEAytnGVWE+GZVcCNBmdaCXrBAAu45qINDsWwZWNArfKrrbcdZGPrZGYnRRjeQuNBLK0IfDSgWtWRtSI3dDZPgZ4moD8bc6vu/M6d21+6LGlJ1/C8k2KvRxoy+exr2QidZsBj+WjCCOd2VIO4QSzG62XJFiqjkznHpvpPeobqq2hTujpO3wXXkxMsae3De0+TdL7fDieSWhs57Htz/CZvgXGnG3YunHwX6RB+rVfPjoHywkNtUPtUyzXlOWQY99j4DWVNE1IuNmQXUAZHz62DVJRhyjdqtaP+A0x8On1HFLNsG5AqWPU0qZY6MJmrGfMHfTHvtWK62EAmbj4IoTPK79WwIDAQABAoIBAQC+99pyEsQlzakBW0C6SF/ByqcF64MaNdXts79/6yRB0/w35WPoMi19DsigbkrZFz/8pXNCwql5k/D2FpdJ/RBRSUFBVCBoFNWy7dkfdxTAYK10aQ8nzU0WNgSiUWA7g7PbBg4zCYtV9/HSpfoUn73q06wxWPyvucxVYBofZXPDZL2db5DRJH1e/tpeKkc9fNOYk3yJyLHZHBVpYsG+pW/2/XnSIUfBwYaFN4FqAquSwGoerF9ysBz7eB22BCgZGiswAA8JwrH9DPgItMF5gT04O3P/jrx+S4DtyEYJgqYETFpF0cLtETwsnOLWcc2M9aY3ouotXEB4uYS3yXRS/B7xAoGBAPdYUhkE/oJZtJ5qC6zcaGU58pFlhbDR6exl7jC38oybXZ9QDNjEa0S5hXpFOzTJM3SSOHc8Xcp6YaTwI7QXdHtERgcmvEeB+jjrjRAsoWUj8qz4ToeSwPixA0CNh4Cjd5TkYlnVUcfU8w4xwJPcT4lIfJBp4YwUPVSm5xVxedvfAoGBANHy4XCGQa22kpTE1r4IbUX2wjCGVGJ/PsZ/uzqgcdEuXqcO6IspasvRVE8BHrpolcGYr0qlEQBTurx5BZ+0qufIBr1iMS3NbwYm+EGDRvxvAU7AijtoPH13oaRFkwhYaQrS3uKtlGbZB3eMvRRDVvRIj75g5t90O5PqquY9qI4FAoGALRMMSwDs/IYqcx/yJrs3zxKjULnGhjWKwojEwl4TNmptwkWNQcdxoOGKIIETTAhKdzjaT2hR0z9AIhWc/Am2MWx8snrtnr5iAhNy7nqjotHNPJY3gV9OCUQAyre/9MJVXW3NOn+0Wo1FCdYpOQjR+bua75pL/wIFNzL/M5otUdMCgYEAnQlKztvdLI4vPc+twB6VfYuA3MLdQ39h+R95b/SqrYg9jD9+ePjVxPYiVaXE0jGAMnp+QxsMiG4Ycvki175PR2c6g1V664OJ09Q6ROZoplBxbfJecukYtdBRu4m+3LMkftATnwGuyu7ywt0mYI2t8LKFsiTRC7rpBU613dmoxfECgYBdH2n0qx15r0bMo+SbW56cI08ThWH3kt7H04vk7dEMrafm3Hm//8HRYeW9ecYzV29vy71oTzFI7uu7MA3XPPQ82ZFrfDi/vpVgVkQKwUM7wYC7s1I9xA9F34N2s1DI26kaLQKHMaXUpiX/VzIKDBj9NFvT0/y8gMCej9580gj9TQ==';

    private $alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjhOPGUGI6X9R9SfDEGWaomNDSxNbbKUjvdJnNTBdsyJ3/1qYEM2l4SDFH0R8lBWecAtRgwMXDm8npB3h1L+5CdWox2BieX06ZYN9AKvk0C6qWEaLsu4qieqUZRdqTk/ZlYQOQkel1Kin/oQIc8qM6SrCk0j8SVQYvY97AC5peBNuBo9kij9D7vlNxuQuWAZ8W+b8XdLoVDWaV7erLAYJZCc5MYV/MQohgvHVgarmRKs9+LS5Go2Xp4dPYnYSwRB0MH9dc9GvHfUANnjcAgQ43pZfLNYPEBRs38w3qbVe2qPS2E7qRBRIv9yIt96dxwXFTGMLSY9nVvV8k2rsGZR+SQIDAQAB';

    //同步跳转地址
    function return_url(){
        $arr = $_GET;
        vendor('Alipay.aop.AopClient');
        $aop = new \AopClient();
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
        $aop->signType = 'RSA2';
        $signVerified = $aop->rsaCheckV1($arr,$this->alipayrsaPublicKey,'RSA2');
        if($signVerified){
            $orderid = $arr['out_trade_no'];
            $Order_mod = M('Order');
            //开启事务
            $Order_mod->startTrans();
            $map = [];
            $map['orderid'] = $orderid;
            $info = $Order_mod->where($map)->field('id,status')->lock(true)->find();
            if($info && $info['status'] == '0'){
                $Goods_mod = M('Goods');
                $GoodsParam_mod = M('GoodsParam');
                $Relation_mod = M('OrderRelation');
                //查询关联
                $relation = $Relation_mod->where($map)->field('goods_id,params_id,number')->select();
                foreach($relation as $k=>$v){
                    $save = [];
                    $save['sales_num'] = ['exp','sales_num+'.$v['number']];
                    $save['sales_sum'] = ['exp','sales_sum+'.$v['number']];
                    $isInc = $Goods_mod->where(['id'=>$v['goods_id']])->save($save);
                    unset($save['sales_sum']);
                    $isInc2 = $GoodsParam_mod->where(['id'=>$v['params_id']])->save($save);
                }
                $save = [];
                $save['status'] = '1';
                $isUpdate = $Order_mod->where(['id'=>$info['id']])->save($save);
                if($isInc && $isInc2 && $isUpdate){
                    $Order_mod->commit();
                }else{
                    $Order_mod->rollback();
                }
            }else{
                $Order_mod->rollback();
            }
        }
        $this->redirect('/mobile/#/myOrder');
    }
    //异步通知地址
    function notify_url(){
        $arr = $_POST;
        vendor('Alipay.aop.AopClient');
        $aop = new \AopClient();
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
        $aop->signType = 'RSA2';
        $signVerified = $aop->rsaCheckV1($arr,$this->alipayrsaPublicKey,'RSA2');
        if($signVerified){
            $orderid = $arr['out_trade_no'];
            $Order_mod = M('Order');
            //开启事务
            $Order_mod->startTrans();
            $map = [];
            $map['orderid'] = $orderid;
            $info = $Order_mod->where($map)->field('id,status')->lock(true)->find();
            if($info && $info['status'] == '0'){
                $Goods_mod = M('Goods');
                $GoodsParam_mod = M('GoodsParam');
                $Relation_mod = M('OrderRelation');
                //查询关联
                $relation = $Relation_mod->where($map)->field('goods_id,params_id,number')->select();
                foreach($relation as $k=>$v){
                    $save = [];
                    $save['sales_num'] = ['exp','sales_num+'.$v['number']];
                    $save['sales_sum'] = ['exp','sales_sum+'.$v['number']];
                    $isInc = $Goods_mod->where(['id'=>$v['goods_id']])->save($save);
                    unset($save['sales_sum']);
                    $isInc2 = $GoodsParam_mod->where(['id'=>$v['params_id']])->save($save);
                }
                $save = [];
                $save['status'] = '1';
                $isUpdate = $Order_mod->where(['id'=>$info['id']])->save($save);
                if($isInc && $isInc2 && $isUpdate){
                    $Order_mod->commit();
                }else{
                    $Order_mod->rollback();
                }
            }else{
                $Order_mod->rollback();
            }
        }
        echo 'ok';
    }



    function pay(){
        $orderid = I('get.orderid','','trim');
        if($orderid == ''){
            $this->ajaxReturn(['status'=>'0','info'=>'没有订单号']);
        }
        $Order_mod = M('Order');
        $map = [];
        $map['orderid'] = $orderid;
        $field = 'status,total_price';
        $info = $Order_mod->where($map)->field($field)->find();
        if(!$info || $info['status'] != '0'){
            $this->ajaxReturn(['status'=>'0','info'=>'无法发起支付']);
        }
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
        $aop->signType = 'RSA2';
        $request = new \AlipayTradeWapPayRequest();
        $request->setReturnUrl('http://api.baertt.com/wap/alipay/return_url');
        $request->setNotifyUrl('http://api.baertt.com/wap/alipay/notify_url');
        $request->setBizContent("{" .
            "\"out_trade_no\":\"$orderid\"," .
            "\"total_amount\":\"0.01\"," .
            "\"subject\":\"测试订单\"," .
            "\"product_code\":\"QUICK_WAP_PAY\"," .
            "}");
        $result = $aop->pageExecute($request);
        $this->show($result);
    }

}
