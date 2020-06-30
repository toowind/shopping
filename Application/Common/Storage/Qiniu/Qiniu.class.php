<?php
namespace Common\Storage\Qiniu;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
use Qiniu\Auth;
class Qiniu{

    //默认配置
    private $config = array(
        'accessKey'=>'cgwb5KYWteUZ_aNTCexxqomzYb7GK5-7ReQPmwzl',
        'secrectKey'=>'_lFVqsgRGHQeqgCHmEXA_6R2Vu9EW89QcvBEnEWk',
        'bucket'=>'test2018',
        'domain'=>'pem6bl21t.bkt.clouddn.com',
        'timeout'=>'3000'
    );
    //实例化API
    private $storage;
    //七牛图片存储模型
    private $pic_mod;
    private $pic2_mod;
    //上传后的返回策略(针对图片)
    private $returnBody = array('returnBody'=>'{"key":$(key),"hash":$(etag),"format":$(imageInfo.format),"width":$(imageInfo.width),"height":$(imageInfo.height)}');
    /**
     * 构造方法,用于构造七牛上传实例
     * @param array  $config 配置
     */
    function __construct($config=array()){
        $this->config = array_merge($this->config,$config);
        $this->pic_mod = M('QiniuPic');
        $this->pic2_mod = M('QiniuPic2');
    }
    /**
     * 获取上传七牛的文件信息
     * @param string $file 图片路径
     * @return array(format:图片类型;width:图片宽度;height:图片高度;colorModel:彩色空间)
     */
    function imageInfo($file){
        $info = get_curl_data($file.'?imageInfo');
        $return = json_decode($info['1'],true);
        return $return;
    }
    /**
     * 处理文件路径
     * @param string $filePath 完整路径
     */
    private function checkLink($filePath){
        if(substr($filePath,0,2) == './'){
            $imglink = C('WEB_URL').ltrim($filePath,'.');
        }elseif(stripos($filePath,ROOT_PATH) !== false){
            $imglink = C('WEB_URL').ltrim($filePath,ROOT_PATH);
        }else{
            $imglink = $filePath;
        }
        return $imglink;
    }
    /**
     * 从指定URL抓取资源上传到七牛
     * @param string $key 上传文件名
     * @param string $filePath 上传文件的路径
     */
    function fetchUrl($key,$filePath){
        //是否重复上传
        $imglink = $this->checkLink($filePath);
        $files = array();
        $files['md5'] = md5($imglink);
        $url = $this->pic_mod->where($files)->getField('url');
        if($url){
            $this->pic_mod->where($files)->setInc('total','1');
            return $url;
        }
        $bucket = new BucketManager($this->config['accessKey'],$this->config['secrectKey']);
        list($ret,$error) = $bucket->fetch($imglink,$this->config['bucket'],$key);
        if(isset($error)){      //上传失败
            return false;
        }
        //完整的图片路径
        $file = 'http://'.$this->config['domain'].'/'.$ret['key'];
        //获取图片的宽高
        $imageInfo = $this->imageInfo($file);
        if(!$imageInfo['height'] || !$imageInfo['width']){
            return false;
        }
        //插入到图片存储的数据库
        $insert = $files;
        $insert['url'] = $file;
        $insert['imglink'] = $imglink;
        $insert['width'] = $imageInfo['width'];
        $insert['height'] = $imageInfo['height'];
        $insert['ext'] = $imageInfo['format'];
        $insert['add_time'] = NOW_TIME;
        $this->pic_mod->addUni($insert);
        return $file;
    }
    /**
     * 将本地文件上传到七牛
     * @param string $key 上传文件名
     * @param string $filePath 上传文件的路径
     */
    function putFile($key,$filePath){
        //是否重复上传
        $imglink = $this->checkLink($filePath);
        $files = array();
        $files['md5'] = md5($imglink);
        $url = $this->pic_mod->where($files)->getField('url');
        if($url){
            $this->pic_mod->where($files)->setInc('total','1');
            return $url;
        }
        $auth = new Auth($this->config['accessKey'],$this->config['secrectKey']);
        $token = $auth->uploadToken($this->config['bucket']);
        $manager = new UploadManager();
        list($ret,$error) = $manager->putFile($token,$key,$filePath);
        if(isset($error)){      //上传失败
            return false;
        }
        //完整的图片路径
        $file = 'http://'.$this->config['domain'].'/'.$ret['key'];
        //获取图片的宽高
        $imageInfo = $this->imageInfo($file);
        if(!$imageInfo['height'] || !$imageInfo['width']){
            return false;
        }
        //插入到图片存储的数据库
        $insert = $files;
        $insert['url'] = $file;
        $insert['imglink'] = $imglink;
        $insert['width'] = $imageInfo['width'];
        $insert['height'] = $imageInfo['height'];
        $insert['ext'] = $imageInfo['format'];
        $insert['add_time'] = NOW_TIME;
        $this->pic_mod->add($insert);
        return $file;
    }
    /**
     * 将文件的二进制流上传到七牛
     * @param string $key 上传文件名
     * @param string $data 上传二进制流
     */
    function put($key,$data){
        $auth = new Auth($this->config['accessKey'],$this->config['secrectKey']);
        $returnBody = array('returnBody'=>'{"key":$(key),"hash":$(etag),"type":$(mimeType),"size":$(fsize),"width":$(imageInfo.width),"height":$(imageInfo.height)}');
        $token = $auth->uploadToken($this->config['bucket'],null,3600,$returnBody);
        $manager = new UploadManager();
        list($ret,$error) = $manager->put($token,$key,$data);
        if(isset($error)){      //上传失败
            return false;
        }
        //完整的图片路径
        $file = 'http://'.$this->config['domain'].'/'.$ret['key'];
        return $file;
    }
    /**
     * 通过HTTP POST 文件上传到七牛
     * @param string $key 上传文件名
     * @param string $data 表单获取的$_FILES全局变量
     */
    function put_files($key,$data,$returnAll=false){
        $files = array();
        $files['md5'] = md5_file($data['tmp_name']);
        $files['sha1'] = sha1_file($data['tmp_name']);
        //是否已经采集入库
        $image = $this->pic2_mod->where($files)->field('url,width,height')->find();
        if($image){     //直接返回相应的属性
            $this->pic2_mod->where($files)->setInc('total','1');
            return $image;
        }
        $auth = new Auth($this->config['accessKey'],$this->config['secrectKey']);
        $token = $auth->uploadToken($this->config['bucket'],null,3600,$this->returnBody);
        $manager = new UploadManager();
        list($ret,$error) = $manager->put($token,$key,file_get_contents($data['tmp_name']));
        if(isset($error)){      //上传失败
            return false;
        }
        //判断图片的宽高,用来检测是否上传失败
        if(!$ret['width'] || !$ret['height']){
            return false;
        }
        //完整的图片路径
        $file = 'http://'.$this->config['domain'].'/'.$ret['key'];
        //插入到图片存储的数据库
        $insert = $files;
        $insert['url'] = $file;
        $insert['width'] = $ret['width'];
        $insert['height'] = $ret['height'];
        $insert['ext'] = $ret['format'];
        $insert['add_time'] = NOW_TIME;
        //添加到记录表,用到MYSQL : on duplicate key update
        $Duplicate = array();
        $Duplicate['total'] = array('exp','total+1');
        $this->pic2_mod->add($insert,array(),$Duplicate);
        if($returnAll === true){
            return array(
                'url'=>$file,
                'width'=>$ret['width'],
                'height'=>$ret['height']
            );
        }else{
            return $file;
        }
    }
    function put_files_data($key,$data){
        $auth = new Auth($this->config['accessKey'],$this->config['secrectKey']);
        $returnBody = array('returnBody'=>'{"key":$(key),"hash":$(etag),"type":$(mimeType),"size":$(fsize),"width":$(imageInfo.width),"height":$(imageInfo.height)}');
        $token = $auth->uploadToken($this->config['bucket'],null,3600,$returnBody);
        $manager = new UploadManager();
        list($ret,$error) = $manager->put($token,$key,$data);
        if(isset($error)){      //上传失败
            return false;
        }
        return array(
            'url'=>'http://'.$this->config['domain'].'/'.$ret['key'],
            'type'=>$ret['type'],
            'size'=>$ret['size'],
            'width'=>$ret['width'],
            'height'=>$ret['height'],
        );
    }
    /**
     * 删除七牛图片
     * @param array $keys 删除的图片keys
     * @return 全部删除陈成功返回true ，任何一个删除失败 返回array记录单个key删除失败 的错误信息
     */
    function delete_file($keys){
        if($keys && is_array($keys)){
            //初始化BucketManager
            $bucketMgr = new BucketManager($this->config['accessKey'],$this->config['secrectKey']);
            //你要测试的空间， 并且这个key在你空间中存在
            $bucket = $this->config['bucket'];
            //删除$bucket 中的文件 $key
            $result = array();
            foreach($keys as $key=>$val){
                $file_info = pathinfo($val);
                $err = $bucketMgr->delete($bucket, $file_info['basename']);
                if($err !== null){
                    $result[$key] = $err->message();
                }
            }
            if(empty($result)){
                return true;
            }
            return $result;
        }
    }
    /**
     * 获取uploadToken
     */
    function getUploadToken(){
        $auth = new Auth($this->config['accessKey'],$this->config['secrectKey']);
        $token = $auth->uploadToken($this->config['bucket']);
        return $token;
    }
}