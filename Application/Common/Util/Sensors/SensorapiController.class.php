<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20/020
 * Time: 11:04
 */
namespace Cron\Controller;
use Think\Controller;
class SensorapiController extends Controller{

    private $date;
    private $event_url = 'https://sc.baertt.com/api/events/report?token=4608021480fd786f1e7438d3d9b79403bdb2de5a32b8d9c633eeee7efbe4fcc5&project=kttproduction';
    private $distribution_url = 'https://sc.baertt.com/api/addictions/report?token=4608021480fd786f1e7438d3d9b79403bdb2de5a32b8d9c633eeee7efbe4fcc5&project=kttproduction';
    public function _initialize(){
        $this->date = date('Y-m-d',strtotime('-1 days'));
    }
    /*
     * 看热文统计
     * */
    public function channel_active($is_today = 0){
        set_time_limit(0);
        if($is_today){
            $this->date=date('Y-m-d');
        }
        $url = $this->event_url;

        $param = $this->params('$AppStart',array(array('uniqCount','$device_id'),array('uniqCount','$distinct_id')),'and',array(),array('channel'));
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['active_device'][$val['by_values'][0]] = $val['values'][0][0];//设备活跃
            $tmp['active_user'][$val['by_values'][0]] = $val['values'][0][1];//用户活跃
        }
        $tmp['active_device']['total'] = array_sum($tmp['active_device']);
        $tmp['active_user']['total'] = array_sum($tmp['active_user']);

        $param = $this->params('AppfirstLogin',array('unique'),'or',array(),array('signUpResource'));   //注册设备
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['new_device_num'][$val['by_values'][0]]= $val['values'][0][0];
        }
        $tmp['new_device_num']['total'] = array_sum($tmp['new_device_num']);

        //新增用户
        $param = $this->params('$AppStart',array('unique'),'and',array(array('$is_first_day','isTrue',array())),array('channel'));
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['new_user_num'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['new_user_num']['total'] = array_sum($tmp['new_user_num']);

        $cond = array(
            array('task_type','equal',array('独立任务')),
            array('task_type_sec','equal',array('看看赚')),
            array('if_finished','isTrue',array()),
        );
        $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel'));//看看赚人数
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['task_kankan_num'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['task_kankan_num']['total'] = array_sum($tmp['task_kankan_num']);

        $cond = array(
            array('task_type','equal',array('热门任务')),
            array('task_name','equal',array('阅读任意文章')),
        );
        $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel'));//阅读人数
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['read_num'][$val['by_values'][0]] = $val['values'][0][0];   //新用户阅读人数
        }
        $tmp['read_num']['total'] = array_sum($tmp['read_num']);

        $cond = array(
            array('task_type','equal',array('新手任务')),
            array('task_name','equal',array('天天种红包')),
            );
        $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel'));//七天领红包
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['task_red_num'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['task_red_num']['total'] = array_sum($tmp['task_red']);

        $cond = array(
            array('task_type','equal',array('热门任务')),
            array('task_name','equal',array('分享被阅读')),
            array('is_new_user','equal',array('0')),
        );
        $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel'));//分享被阅读
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['task_bread_num'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['task_bread_num']['total'] = array_sum($tmp['task_bread_num']);

        //friends 徒弟ID   distinctid 师傅ID
        $param = $this->params('Invite_friends_new',array(array('uniqCount','friends_id'),array('uniqCount','$distinct_id')),'and',array(),array('channel'));//填写邀请码
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['fillcode_num'][$val['by_values'][0]] = $val['values'][0][0];
            $tmp['inviter_num'][$val['by_values'][0]] = $val['values'][0][1];
        }
        $tmp['fillcode_num']['total'] = array_sum($tmp['fillcode_num']);
        $tmp['inviter_num']['total'] = array_sum($tmp['inviter_num']);


        $param = $this->params('share',array('unique'),'and',array(array('is_new_user','equal',array('0'))),array('channel'));//分享
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['task_share_num'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['task_share_num']['total'] = array_sum($tmp['share']);



        $param = $this->params('withdraw',array('unique'),'and',array(),array('channel'));//提现
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['draw_num'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['draw_num']['total'] = array_sum($tmp['draw_num']);


        $cond = array(array('cash_count','equal',array(1)));
        $param = $this->params('withdraw',array('unique'),'and',$cond,array('channel'));//1元提现
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['one_with_draw'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['one_with_draw']['total'] = array_sum($tmp['one_with_draw']);


        $param = $this->params('finish_invite_new',array(array('SUM','award_money')),'and',array(),array('channel'));//拉新支出
        $data = $this->get_curl_post($url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp['pull_new_money'][$val['by_values'][0]] = $val['values'][0][0];
        }
        $tmp['pull_new_money']['total'] = array_sum($tmp['pull_new_money']);




        $channel=M('Channel')->field('channel_id')->select();
        $channel[]=array('channel_id'=>'total');
        $data = array();
        foreach($channel as $k=>$v){
            $data[$v['channel_id']] = array(
                'date'              =>$this->date,
                'channel'           =>$v['channel_id'],                         //渠道号
                'active_device'     =>$tmp['active_device'][$v['channel_id']]?:0,  //活跃设备
                'active_user'       =>$tmp['active_user'][$v['channel_id']]?:0,    //提现用户数
                'draw_num'          =>$tmp['draw_num'][$v['channel_id']]?:0, //活跃用户
                'read_num'          =>$tmp['read_num'][$v['channel_id']]?:0,      //阅读数
                'task_kankan_num'   =>$tmp['task_kankan_num'][$v['channel_id']]?:0,          //分享
                'fillcode_num'      =>$tmp['fillcode_num'][$v['channel_id']]?:0,     //看看赚
                'task_red_num'      =>$tmp['task_red_num'][$v['channel_id']]?:0,//阅读
                'task_share_num'    =>$tmp['task_share_num'][$v['channel_id']]?:0,        //天天红包
                'task_bread_num'    =>$tmp['task_bread_num'][$v['channel_id']]?:0,     //邀请

                'one_with_draw'     =>$tmp['one_with_draw'][$v['channel_id']]?:0,        //新增设备
                'pull_new_money'    =>$tmp['pull_new_money'][$v['channel_id']]?:0,      //新增用户
                'inviter_num'       =>$tmp['inviter_num'][$v['channel_id']]?:0,  //参与邀请人数
                'new_device_num'    =>$tmp['new_device_num'][$v['channel_id']]?:0,  //参与邀请人数
                'new_user_num'      =>$tmp['new_user_num'][$v['channel_id']]?:0,  //参与邀请人数

                'date'              =>$this->date
            );
        }

        $model = M('channel_active_data');
        foreach($data as $key=>$val){
            $cond = array(
                'date' =>$this->date,
                'channel'    =>$key
            );
            if($model->where($cond)->find()){
                $model->where($cond)->save($val);
                echo $key."数据更新成功\r\n";
            }else{
                $model->addUni($val);
                echo $key."数据写入成功\r\n";
            }
        }
        echo 'complete';
    }

    /*
     * $table string  要查哪张表
     * $aggregator array(general,general,unique) general总次数   unique触发用户数   average人均次数
     * $relation  string and or
     * $cond  array()
     * $fields  array('ad_id','pos')
     * $from_date 查询开始i日期
     * $to_date   查询结束日期
     * $bucket_params   查询区间
     * */
    private function params($table,$aggregator=array(),$relation = 'and',$cond=array(),$fields=array(),$from_date='',$to_date='',$bucket_params=array()){
        foreach($aggregator as $agg){
            $conditions = [];
            if(is_array($agg)){
                if(isset($agg['filter'])){
                    foreach ($agg['filter']['condition'] as $k=>$v){
                        $conditions[] = array(
                            'field'     =>  'event.'.$table.'.'.$v[0],
                            'function'  =>  $v[1],
                            'params'    =>  $v[2]
                        );
                    }
                    $measures[] = array(
                        'event_name'    => $table,         //事件名  pushScQueue的 action参数值
                        'aggregator'    => $agg[0],            //聚合  general总次数   unique触发用户数   average人均次数
                        'filter'         => [
                            'conditions'=>$conditions,
                            'relation'  =>$agg['filter']['relation']
                        ]
                    );
                }else{
                    $measures[] = array(
                        'event_name'    => $table,         //事件名  pushScQueue的 action参数值
                        'aggregator'    => $agg[0],            //聚合  general总次数   unique触发用户数   average人均次数
                        'field'         => 'event.'.$table.'.'.$agg[1]
                    );
                }


            }else{
                $measures[] = array(
                    'event_name'    => $table,         //事件名  pushScQueue的 action参数值
                    'aggregator'    => $agg            //聚合  general总次数   unique触发用户数   average人均次数
                );
            }
        }
        foreach($fields as $field){
            if(is_array($field)){
                //公共属性
                foreach ($field as $v){
                    $by_fields[] = 'user.'.$v;
                }
            }else{
                //事件属性
                $by_fields[] = 'event.'.$table.'.'.$field;
            }
        }
        $conditions = [];
        foreach($cond as $con){
            $conditions[] = array(
                'field'     =>  'event.'.$table.'.'.$con[0],
                'function'  =>  $con[1],
                'params'    =>  $con[2]
            );
        }
        $para = array(
            'measures'      =>$measures,
            'unit'          =>'day',
            'filter'        =>array(            //筛选条件
                'relation'  =>$relation,
                'conditions'=>$conditions
            ),
            'by_fields'     =>$by_fields?:array(),   //按哪个字段查看
            'sampling_factor'   =>64,
            'from_date'     =>$from_date ==''? $this->date : $from_date,      //查询的起始时间
            'to_date'       =>$to_date == '' ? $this->date : $to_date,      //查询的结束日期
            'approx'        =>false,
            'use_cache'     =>false
        );
        $bucket_param_arr = [];
        if(!empty($bucket_params)){
            foreach($bucket_params as $con){
                $bucket_param_arr[ 'event.'.$table.'.'.$con[0]][] = $con[1];
            }
            $para['bucket_params'] =  $bucket_param_arr;
        }
        return $para;
    }
    /*
     * 分布分析参数
     * $event string  要查哪个事件
     * $measure_type string 要查哪个属性
     * $unit string  时间单位  day week month
     * $relation  string and or  事件筛选关系
     *  * $cond  array()    事件筛选项
     * $user_relation  string and or    用户属性筛选关系
     * $user_cond  array()   用户属性筛选项
     * $result_bucket_param  array() 区间选项
     * $from_date 查询开始i日期
     * $to_date   查询结束日期
     * $measure   查询条件
     * */
    private function distributionParams($event,$measure_type,$unit,$relation = 'and',$cond=array(),$user_relation='and',$user_cond=array(),$result_bucket_param=array(),$from_date='',$to_date='',$measure=array()){
        $conditions = [];
        foreach($cond as $con){
            $conditions[] = array(
                'field'     =>  'event.'.$event.'.'.$con[0],
                'function'  =>  $con[1],
                'params'    =>  $con[2]
            );
        }
        $user_conditions = [];
        foreach($user_cond as $con){
            $user_conditions[] = array(
                'field'     =>  'user.'.$con[0],
                'function'  =>  $con[1],
                'params'    =>  $con[2]
            );
        }
        $measures = [];
        if($measure){
            $measures = array(
                'event_name'    => $event,         //事件名  pushScQueue的 action参数值
                'aggregator'    => $measure[0],            //聚合  general总次数   unique触发用户数   average人均次数
                'field'         => 'event.'.$event.'.'.$measure[1]
            );
        }
        $para = array(
            'rangeText'=>'昨日',
            'event_name'      =>$event,
            'measure_type'      =>$measure_type,
            'unit'          =>$unit,
            'filter'        =>array(            //筛选条件
                'relation'  =>$relation,
                'conditions'=>$conditions
            ),
            'user_filter'        =>array(            //筛选条件
                'relation'  =>$user_relation,
                'conditions'=>$user_conditions
            ),
            'sampling_factor'   =>64,
            'from_date'     =>$from_date ==''? $this->date : $from_date,      //查询的起始时间
            'to_date'       =>$to_date == '' ? $this->date : $to_date,      //查询的结束日期
            'use_cache'     =>false,
            'result_bucket_param'          =>$result_bucket_param,
        );
        $measures && $para['measure'] = $measures;
        return $para;
    }

    /*
    * curl通过连接获取数据
    */
    private function get_curl_post($url,$param){
        //线上
        $contentType  = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        );
        //测试
//        $url = 'https://sc.baertt.com/api/events/report?project=kttdefault';
//        $contentType  = array(
//            "Content-type: application/json;charset='utf-8'",
//            "sensorsdata-token: GKxEHdvQu102hmciJfashCz1UGDgJNpLion8gAZj5EZZu6nhChTtZrthTYpblVekHMJSOnUmet02VblETpOz1MEF8tC4CYawLqCzcteB7KgdoOJboLu2rlMvSq6WJpbt",
//            "Accept: application/json",
//            "Cache-Control: no-cache",
//            "Pragma: no-cache",
//        );
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,10); // 设置超时限制防止死循环
        curl_setopt($ch,CURLOPT_POSTFIELDS,$param);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$contentType);
        curl_setopt($ch,CURLINFO_HEADER_OUT,true);
        $return=curl_exec($ch);
        $err = curl_errno($ch);
        $er_msg  = curl_error($ch);
        $info=curl_getinfo($ch);
        curl_close($ch);
        return json_decode($return,true);
    }
    /**
     *  激励视频每日统计
     */
    public function stimulate_video_census(){
        $date = $this->date;
        // 客户端激励视频埋点   RewardVideo
        $arr = [
            'RewardVideoReady'=>'show_num',
            'RewardVideoShow'=>'click_num'
        ];
        $where = [
            ['ad_event','equal',array_keys($arr)],
        ];
        $bucket_params = [
            ['brand',null]
        ];
        $param = $this->params('RewardVideo',['general','unique'],'and',$where,array('brand','play_entrance','ad_event'),$date,$date,$bucket_params);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp2 = $tmp3 = $tmp4 = [];
        foreach($data['rows'] as $key=>$val){
            //渠道 每日统计
            $front2 = $val['by_values'][0];
            if($front2 != null){
                $tmp2[$front2]['channel'] = $val['by_values'][0];
                $tmp2[$front2][$arr[$val['by_values'][2]].'_pv'] += $val['values'][0][0];
                $tmp2[$front2][$arr[$val['by_values'][2]].'_uv'] += $val['values'][0][1];
            }
            // 入口每日统计
            if($val['by_values'][1] != null){
                $front3 = 'entry_'.$val['by_values'][1];
                $tmp3[$front3]['entry'] = $val['by_values'][1];
                $tmp3[$front3][$arr[$val['by_values'][2]].'_pv'] += $val['values'][0][0];
                $tmp3[$front3][$arr[$val['by_values'][2]].'_uv'] += $val['values'][0][1];
            }
        }
        //整体统计
        $param = $this->params('RewardVideo',['general','unique'],'and',$where,array('ad_event'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp2[0]['channel'] = '0';
            $tmp2[0][$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
            $tmp2[0][$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            $tmp3[0]['entry'] = '0';
            $tmp3[0][$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
            $tmp3[0][$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
        }
        // 后台激励视频埋点 task_click
        $map1 = [
          'general','unique',['SUM','coin_counts']
        ];
        $arr = [
            '激励视频(首页)'=>1,
            '激励视频(时段奖励)'=>2,
            '激励视频(摇红包)'=>3,
            '激励视频(签到)'=>4,
            '激励视频(摇红包幸运任务)'=>5,
            '激励视频(看看赚任务)'=>6,
            '幸运大转盘'=>7,
        ];
        $where = [
            ['task_type_sec','equal',['激励视频']],
            ['task_name','equal',array_keys($arr)],
        ];
        $param = $this->params('mission_commit',$map1,'and',$where,array('utm_source','task_name'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            //渠道 每日统计
            $front2 = $val['by_values'][0];
            if($front2 != null){
                $tmp2[$front2]['channel'] = $front2;
                $tmp2[$front2]['play_num_pv'] += $val['values'][0][0];
                $tmp2[$front2]['play_num_uv'] += $val['values'][0][1];
                $tmp2[$front2]['score'] += $val['values'][0][2];
            }
            $tmp2[0]['channel'] = '0';
            $tmp2[0]['play_num_pv'] += $val['values'][0][0];
            $tmp2[0]['play_num_uv'] += $val['values'][0][1];
            $tmp2[0]['score'] += $val['values'][0][2];

            // 入口每日统计
            $front3 = 'entry_'.$arr[$val['by_values'][1]];
            $tmp3[$front3]['entry'] = $arr[$val['by_values'][1]];
            $tmp3[$front3]['play_num_pv'] += $val['values'][0][0];
            $tmp3[$front3]['play_num_uv'] += $val['values'][0][1];
            $tmp3[$front3]['score'] += $val['values'][0][2];
            $tmp3[0]['entry'] = '0';
            $tmp3[0]['play_num_pv'] += $val['values'][0][0];
            $tmp3[0]['play_num_uv'] += $val['values'][0][1];
            $tmp3[0]['score'] += $val['values'][0][2];

        }
        // 汇总
        $param = $this->params('mission_commit',$map1,'and',$where,array(),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $tmp2[0]['channel'] = '0';
            $tmp3[0]['entry'] = '0';
            $tmp2[0]['play_num_pv'] = $tmp3[0]['play_num_pv'] = $val['values'][0][0];
            $tmp2[0]['play_num_uv'] = $tmp3[0]['play_num_uv'] = $val['values'][0][1];
            $tmp2[0]['score'] =  $tmp3[0]['score'] = $val['values'][0][2];
        }
        // 渠道入库
        $model2 = M('stimulate_video_channel');
        foreach ($tmp2 as $k=>$v){
            $cond = [];
            $cond['date_time'] =$date;
            $cond['channel'] =$v['channel'];
            $v['date_time'] = $date;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
        // 入口入库
        $model3 = M('stimulate_video_entry');
        foreach ($tmp3 as $k=>$v){
            $cond = [];
            $cond['date_time'] =$date;
            $cond['entry'] =$v['entry'];
            $v['date_time'] = $date;
            if($model3->where($cond)->find()){
                $model3->where($cond)->save($v);
            }else{
                $model3->addUni($v);
            }
        }
    }

    /**
     *  看广告统计
     */
    public function task_ad_by_day(){
        $date = $this->date;
        // 客户端看广告埋点   ApiAdState
        $arr = [
            'adApiShow'=>'ad_show',
            'adApiClick'=>'ad_click'
        ];
        $where = [
            ['ad_event','equal',array_keys($arr)],
            ['adViewTemplate','equal',[0]]
        ];
        $param = $this->params('ApiAdState',['general','unique'],'and',$where,array('brand','ad_event'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp2 = [];
        foreach($data['rows'] as $key=>$val){
            //渠道 每日统计
            if($val['by_values'][0] != null){
                $front2 = $val['by_values'][0];
                $tmp2[$front2]['brand'] =  $val['by_values'][0];
                $tmp2[$front2][$arr[$val['by_values'][1]]."_pv"] = $val['values'][0][0];
                $tmp2[$front2][$arr[$val['by_values'][1]]."_uv"] = $val['values'][0][1];
            }
            $tmp2[0]['brand'] = '0';
            $tmp2[0][$arr[$val['by_values'][1]]."_pv"] += $val['values'][0][0];
            $tmp2[0][$arr[$val['by_values'][1]]."_uv"] += $val['values'][0][1];
        }
        //mission_commit
        $condition = array(
            array('task_name','equal',array('点广告')),
            array('task_type_sec','equal',array('看看赚')),
            array('if_finished','isTrue',array())
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('utm_source'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if($val['by_values'][0] != null){
                    //渠道 每日统计
                    $front2 = 'channel_'.$val['by_values'][0];
                    $tmp2[$front2]['brand'] =  $val['by_values'][0];
                    $tmp2[$front2]['score_num'] = $val['values'][0][0];
                    $tmp2[$front2]['score_user'] = $val['values'][0][1];
                    $tmp2[$front2]['score_total'] = $val['values'][0][2];
                }
                $tmp2[0]['brand'] = '0';
                $tmp2[0]['score_num'] += $val['values'][0][0];
                $tmp2[0]['score_user'] += $val['values'][0][1];
                $tmp2[0]['score_total'] += $val['values'][0][2];
            }
        }
        // 渠道入库
        $model2 = M('task_ad_by_day');
        foreach ($tmp2 as $k=>$v){
            $cond = [];
            $cond['date_time'] =$date;
            $cond['brand'] =$v['brand'];
            $v['date_time'] = $date;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
    }

    /**
     *  看广告每日统计
     */
    public function task_ad_census(){
        $date = $this->date;
        //task_click
        $arr = [
            '看广告入口点击'=>'ad_button',
        ];
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $param = $this->params('task_click',array('general','unique'),'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        //overall_click
        $arr = [
            '看广告开始赚钱'=>'start',
            '看广告继续赚钱'=>'continue'
        ];
        $where = [
            ['kankanzhuan1_click','equal',array_keys($arr)]
        ];
        $param = $this->params('overall_click',array('general','unique'),'and',$where,array('kankanzhuan1_click'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        // mission_commit
        $condition = array(
            array('task_name','equal',array('点广告')),
            array('if_finished','isTrue',array()),
            array('task_type_sec','equal',array('看看赚')),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['score_num'] = $val['values'][0][0];
                $tmp['complete_user'] = $val['values'][0][1];
                $tmp['score'] = $val['values'][0][2];
            }
        }

        if(empty($tmp)){
            exit;
        }
        $tmp['date_time'] = $date;
        $cond['date_time'] =$date;
        $model = M('task_ad_census');
        if($model->where($cond)->find()){
            $model->where($cond)->save($tmp);
        }else{
            $model->addUni($tmp);
        }
    }

    /**
     *  看热文广告统计
     */
    public function task_hot_by_day(){
        set_time_limit(0);
        $date = $this->date;
        $arr = [
            '看热文ID曝光'=>'ad_show',
            '看热文ID点击'=>'ad_click',
            '看热点ID曝光'=>'ad_show',
            '看热点ID点击'=>'ad_click',
            '搜索赚ID曝光'=>'ad_show',
            '搜索赚ID点击'=>'ad_click',
        ];
        // 看热文广告埋点   task_click
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $bucket_params = [
          ['hot_id',null]
        ];
        $param = $this->params('task_click',['general','unique'],'and',$where,array('hot_id','task_name'),$date,$date,$bucket_params);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp2 = [];
        foreach($data['rows'] as $key=>$val){
            if(in_array($val['by_values'][1],['看热点ID曝光','看热点ID点击'])){
                $type = 2;
            }else if(in_array($val['by_values'][1],['搜索赚ID曝光','搜索赚ID点击'])){
                $type = 3;
            }else{
                $type = 1;
            }
            $front = 'type_'.$type;
            if($val['by_values'][0] != null){
                //渠道 每日统计
                $front2 =$front.'_'.$val['by_values'][0];
                $tmp2[$front2]['type'] =  $type;
                $tmp2[$front2]['brand'] =  $val['by_values'][0];
                $tmp2[$front2]['task_name'] =  $val['by_values'][1];
                $tmp2[$front2][$arr[$val['by_values'][1]].'_pv'] = $val['values'][0][0];
                $tmp2[$front2][$arr[$val['by_values'][1]].'_uv'] = $val['values'][0][1];
            }
        }
        //mission_commit
        $condition = array(
            array('task_name','equal',array('看热文','看热点','首页搜索')),
            array('task_type_sec','equal',array('看看赚')),
            array('if_finished','isTrue',array())
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $bucket_params = [
            ['utm_source',null]
        ];
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('utm_source','task_name'),$date,$date,$bucket_params);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(in_array($val['by_values'][1],['看热点'])){
                    $type = 2;
                }else if(in_array($val['by_values'][1],['首页搜索'])){
                    $type = 3;
                }else{
                    $type = 1;
                }
                $front = 'type_'.$type;
                if($val['by_values'][0] != null){
                    //渠道 每日统计
                    $front2 =$front.'_'.$val['by_values'][0];
                    $tmp2[$front2]['type'] =  $type;
                    $tmp2[$front2]['brand'] =  $val['by_values'][0];
                    $tmp2[$front2]['score_num'] = $val['values'][0][0];
                    $tmp2[$front2]['score_user'] = $val['values'][0][1];
                    $tmp2[$front2]['score_total'] = $val['values'][0][2];
                }
            }
        }
        if(!$tmp2){
            exit();
        }
        foreach ($tmp2 as $k=>$v){
            $tmp2["type_total"]['type'] = 0;
            $tmp2["type_{$v['type']}"]['type'] = $v['type'];
            $tmp2["type_{$v['type']}"]['brand'] = $tmp2["type_total"]['brand'] = '0';
            $tmp2["type_{$v['type']}"]['score_num'] += $v['score_num'];
            $tmp2["type_{$v['type']}"]['score_user'] += $v['score_user'];
            $tmp2["type_{$v['type']}"]['score_total'] += $v['score_total'];
            $tmp2["type_{$v['type']}"][$arr[$v['task_name']].'_pv'] += $v[$arr[$v['task_name']].'_pv'];
            $tmp2["type_{$v['type']}"][$arr[$v['task_name']].'_uv'] += $v[$arr[$v['task_name']].'_uv'];
            $tmp2["type_total"]['score_num'] += $v['score_num'];
            $tmp2["type_total"]['score_user'] += $v['score_user'];
            $tmp2["type_total"]['score_total'] += $v['score_total'];
            $tmp2["type_total"][$arr[$v['task_name']].'_pv'] += $v[$arr[$v['task_name']].'_pv'];
            $tmp2["type_total"][$arr[$v['task_name']].'_uv'] += $v[$arr[$v['task_name']].'_uv'];
            unset($tmp2[$k]['task_name']);
        }
        // 渠道入库
        $model2 = M('task_hot_by_day');
        foreach ($tmp2 as $k=>$v){
            $cond = [];
            $cond['date_time'] =$date;
            $cond['type'] = $v['type'];
            $cond['brand'] =$v['brand'];
            $v['date_time'] = $date;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
    }

    /**
     *  看热文每日统计
     */
    public function task_hot_census(){
        $date = $this->date;
        $arr = [
            '看热文入口点击'=>'task_hot_btn',
            '看热文开始赚钱点击'=>'get_money_btn',
            '看热点入口点击'=>'task_hot_btn',
            '看热点开始赚钱点击'=>'get_money_btn',
            '搜索赚入口点击'=>'task_hot_btn',
            '搜索赚开始赚钱点击'=>'get_money_btn',
        ];
        //task_click
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $param = $this->params('task_click',array('general','unique'),'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(in_array($val['by_values'][0],['看热文入口点击','看热文开始赚钱点击'])){
                    $type = 1;
                }else if(in_array($val['by_values'][0],['搜索赚入口点击','搜索赚开始赚钱点击'])){
                    $type = 3;
                }else if(in_array($val['by_values'][0],['看热点入口点击','看热点开始赚钱点击'])){
                    $type = 2;
                }
                $front = 'type_'.$type;
                $tmp[$front]['type'] = $type;
                $tmp[$front]['task_name'] = $val['by_values'][0];
                $tmp[$front][$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$front][$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        //mission_commit
        $condition = array(
            array('task_name','equal',array('看热文','看热点','首页搜索')),
            array('if_finished','isTrue',array())
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(in_array($val['by_values'][0],['看热文'])){
                    $type = 1;
                }else if(in_array($val['by_values'][0],['首页搜索'])){
                    $type = 3;
                }else{
                    $type = 2;
                }
                $front = 'type_'.$type;
                $tmp[$front]['type'] = $type;
                $tmp[$front]['score_times'] = $val['values'][0][0];
                $tmp[$front]['score_user'] = $val['values'][0][1];
                $tmp[$front]['score_total'] = $val['values'][0][2];
            }
        }
        if(empty($tmp)){
            exit;
        }
        foreach ($tmp as $k=>$v){
            $tmp["type_total"]['type'] = 0;
            $tmp["type_total"]['score_times'] += $v['score_times'];
            $tmp["type_total"]['score_user'] += $v['score_user'];
            $tmp["type_total"]['score_total'] += $v['score_total'];
            $tmp["type_total"][$arr[$v['task_name']].'_pv'] += $v[$arr[$v['task_name']].'_pv'];
            $tmp["type_total"][$arr[$v['task_name']].'_uv'] += $v[$arr[$v['task_name']].'_uv'];
            unset($tmp[$k]['task_name']);
        }
        // 渠道入库
        $model2 = M('task_hot_census');
        foreach ($tmp as $k=>$v){
            $cond = [];
            $cond['date_time'] =$date;
            $cond['type'] = $v['type'];
            $v['date_time'] = $date;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
    }

    /**
     *  摇红包每日统计
     */

    public function shake_red_by_day(){
        $date = $this->date;
        $arr = [
            '悬浮按钮展示'=>'hover_show',
            '悬浮按钮点击'=>'hover_click'
        ];
        //shake_red
        $where = [
            ['task_type2','equal',array_keys($arr)]
        ];
        $param = $this->params('shake_red',array('general','unique'),'and',$where,array('task_type2'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }

        }
        if(empty($tmp)){
            exit;
        }
        $tmp['date_time'] = $date;
        $cond['date_time'] =$date;
        $model = M('shake_red_by_day');
        if($model->where($cond)->find()){
            $model->where($cond)->save($tmp);
        }else{
            $model->addUni($tmp);
        }
    }

    /**
     *  app普通按钮每日统计
     */
    public function btn_by_day(){
        $date = $this->date;
        $arr = [
            '便民曝光'=>'bianmin_btn'
        ];
        //overall_click
        $where = [
            ['button_name','equal',array_keys($arr)]
        ];
        $param = $this->params('overall_click',array('general','unique'),'and',$where,array('button_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        if(empty($tmp)){
            exit;
        }
        $tmp['date_time'] = $date;
        $cond['date_time'] =$date;
        $model = M('btn_by_day');
        if($model->where($cond)->find()){
            $model->where($cond)->save($tmp);
        }else{
            $model->addUni($tmp);
        }
    }

    /**
     *  分渠道任务统计
     */
    public function channel_task_census(){
        $date = $this->date;
        // 整点点击事件  task_click
        $map = ['general', 'unique'];

        $channels = [
            '阅读文章10分钟'=>'read_article',
            '分享文章被好友阅读'=>'share_article',
            '观看视频5分钟'=>'video',
            '看广告'=>'ad',
            '浏览热文送金币'=>'hot_article',
            '逛淘宝,先领券'=>'taobao',
            '看看赚'=>'kankanzhuan',
            '关注公众号赚金币'=>'gongzhonghao',
            '玩小程序赚零钱'=>'applet',
            '天天种红包'=>'day_day_red',
            '支付宝到店大红包'=>'alipay',
        ];
//        $channels = [
//            '阅读任意文章 热门任务'=>'read_article',
//            '分享文章被好友阅读 推荐任务'=>'share_article',
//            '观看任意视频 热门任务'=>'video',
//            '看广告 推荐任务'=>'ad',
//            '浏览热文送金币 推荐任务'=>'hot_article',
//            '逛淘宝,先领券 热门任务'=>'taobao',
//            '看看赚'=>'kankanzhuan',
//            '关注公众号赚金币 推荐任务'=>'gongzhonghao',
//            '玩小程序赚零钱 热门任务'=>'applet',
//            '天天领红包 新手任务'=>'day_day_red',
//            '支付宝到店大红包 热门任务'=>'alipay',
//        ];
       $arr = [
           '0'=>'active',
           '1'=>'newly',
       ];
        //task_click
        $where = [
            ['task_name','equal',array_keys($channels)]
        ];
        $param = $this->params('task_click',$map,'and',$where,array('channel','task_name','is_new_user'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $val['by_values'][2] === null && $val['by_values'][2] = 0;
                if($val['by_values'][0] != null){
                    $front = $val['by_values'][0];
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_pv'] = $val['values'][0][0];
                    $tmp[$front][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_uv'] = $val['values'][0][1];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_pv'] += $val['values'][0][0];
                $tmp[0][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_uv'] += $val['values'][0][1];
            }
        }
        // 时段奖励
        $time_reward = ['首页领取时段奖励入口'=>'time_reward'];
        $where = [
            ['time_reward','equal',array_keys($time_reward)]
        ];
        $param = $this->params('overall_click',$map,'and',$where,array('channel','time_reward','is_new_user'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                $val['by_values'][2] === null && $val['by_values'][2] = 0;
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front][$time_reward[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_pv'] = $val['values'][0][0];
                    $tmp[$front][$time_reward[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_uv'] = $val['values'][0][1];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0][$time_reward[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_pv'] += $val['values'][0][0];
                $tmp[0][$time_reward[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_uv'] += $val['values'][0][1];
            }
        }
        //签到    mission_commit
        $where = [
            ['task_name','equal',['签到']]
        ];
        $param = $this->params('mission_commit',$map,'and',$where,array('channel','is_new_user'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $val['by_values'][1] === null && $val['by_values'][1] = 0;
                if($val['by_values'][0] != null){
                    $front = $val['by_values'][0];
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['sign_'.$arr[$val['by_values'][1]].'_pv'] = $val['values'][0][0];
                    $tmp[$front]['sign_'.$arr[$val['by_values'][1]].'_uv'] = $val['values'][0][1];
                }

                $tmp[0]['channel'] = '0';
                $tmp[0]['sign_'.$arr[$val['by_values'][1]].'_pv'] += $val['values'][0][0];
                $tmp[0]['sign_'.$arr[$val['by_values'][1]].'_uv'] += $val['values'][0][1];
            }
        }

        // 今日用户   新增
        $cond = [];
        $param = $this->params('AppfirstLogin',array('unique'),'and',$cond,array('signUpResource'));
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['user_newly'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['user_newly'] += $val['values'][0][0];
            }
        }
        // 今日用户 活跃用户
        $cond = [
            ['isLogin','isTrue',[]],
            ['is_new_user','equal',['0']],
        ];
        $param = $this->params("$"."AppStart",array('unique'),'and',$cond,array('channel'));
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['user_active'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['user_active'] += $val['values'][0][0];
            }
        }
        // 渠道入库
        $model2 = M('channel_task_census');
        foreach ($tmp as $k=>$v){
            $v['user_num'] = '0';
            isset($v['user_newly']) && $v['user_num']+= $v['user_newly'];
            isset($v['user_active']) && $v['user_num']+= $v['user_active'];
            $v['date_time'] = $date;
            $cond = [];
            $cond['date_time'] =$date;
            $cond['channel'] =$v['channel'];
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }

    }

    /**
     * 广告来源统计
    */
    public function ad_source_census(){
        $date = $this->date;
        $arr = [
            '百度'=>'BAIDU',
            '百度联盟'=>'BAIDU',
            '头条联盟'=>'TOUTIAO',
            '搜狗联盟'=>'SOUGOU',
            '广点通'=>'DianGuan',
            '头条'=>'TOUTIAO',
            '讯飞'=>'FLY',
            '晒博'=>'ShaiBo',
            '旺脉'=>'WangMai',
            'JiaTou'=>'JiaTou',
            'HuZhong'=>'HuZhong',
            '快友'=>'KuaiYou',
        ];
        $arr1 = ['开屏'=>'open_ad','大图'=>'info_ad'];
        //overall_click
        $where = [
            ['adSource','equal',array_keys($arr)],
            ['adType','equal',array_keys($arr1)]
        ];
        //广告展示 分来源统计
        $param = $this->params('adShow',array('general','unique'),'and',$where,array('adSource','adType'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $front = $arr[$front];
                    $tmp[$front]['source'] = $front;
                    $tmp[$front][$arr1[$val['by_values'][1]].'_show_pv'] = $val['values'][0][0];
                    $tmp[$front][$arr1[$val['by_values'][1]].'_show_uv'] = $val['values'][0][1];
                }
            }
        }
        //广告展示 总的统计
        $param = $this->params('adShow',array('general','unique'),'and',$where,array('adType'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[0]['source'] = '0';
                $tmp[0][$arr1[$val['by_values'][0]].'_show_pv'] = $val['values'][0][0];
                $tmp[0][$arr1[$val['by_values'][0]].'_show_uv'] = $val['values'][0][1];
            }
        }
        //广告点击 分来源统计
        $param = $this->params('adClick',array('general','unique'),'and',$where,array('adSource','adType'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $front = $arr[$front];
                    $tmp[$front]['source'] = $front;
                    $tmp[$front][$arr1[$val['by_values'][1]].'_click_pv'] = $val['values'][0][0];
                    $tmp[$front][$arr1[$val['by_values'][1]].'_click_uv'] = $val['values'][0][1];
                }
            }
        }
        //广告点击 总统计
        $param = $this->params('adClick',array('general','unique'),'and',$where,array('adType'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[0]['source'] = '0';
                $tmp[0][$arr1[$val['by_values'][0]].'_click_pv'] = $val['values'][0][0];
                $tmp[0][$arr1[$val['by_values'][0]].'_click_uv'] = $val['values'][0][1];
            }
        }
        if($tmp){
            $model = M('ad_source_census');
            foreach ($tmp as $k=>$v){
                $cond = [];
                $cond['date_time'] = $date;
                $cond['source'] = $v['source'];
                $v['date_time'] = $date;
                if($model->where($cond)->find()){
                    $model->where($cond)->save($v);
                }else{
                    $model->addUni($v);
                }
            }
        }
        exit();
    }

    /**
     *  渠道分享文章阅读
     */
    public function share_article_census(){
        $date = $this->date;
        $map = ['general', 'unique'];
        $channels = [
            '微信好友'=>'share_wxhy',
            '微信朋友圈'=>'share_pyq'
        ];
        $arr = [
            '0'=>'active',
            '1'=>'newly',
        ];
        //share 神策事件
        $where = [
            ['be_read','notSet',[]],
        ];
        $param = $this->params('share',$map,'and',$where,array('channel','shareType','is_new_user'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $val['by_values'][2] === null && $val['by_values'][2] = 0;
                $front = $val['by_values'][0];
                if(isset($channels[$val['by_values'][1]])){
                    if($front != null){
                        // 微信好友/朋友圈 PV/UV
                        $tmp[$front]['channel'] = $front;
                        $tmp[$front][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_pv'] = $val['values'][0][0];
                        $tmp[$front][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_uv'] = $val['values'][0][1];
                    }
                    $tmp[0]['channel'] = '0';
                    $tmp[0][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_pv'] += $val['values'][0][0];
                    $tmp[0][$channels[$val['by_values'][1]].'_'.$arr[$val['by_values'][2]].'_uv'] += $val['values'][0][1];
                }
            }
        }
        //分享次数 分享用户数
        //share 神策事件
        $where = [
            ['be_read','notSet',[]],
        ];
        $param = $this->params('share',$map,'and',$where,array('channel','is_new_user'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $val['by_values'][1] === null && $val['by_values'][1] = 0;
                $front = $val['by_values'][0];
                if($front != null){
                    // 总共的PV/UV
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['share_'.$arr[$val['by_values'][1]].'_pv'] = $val['values'][0][0];
                    $tmp[$front]['share_'.$arr[$val['by_values'][1]].'_uv'] = $val['values'][0][1];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['share_'.$arr[$val['by_values'][1]].'_pv'] += $val['values'][0][0];
                $tmp[0]['share_'.$arr[$val['by_values'][1]].'_uv'] += $val['values'][0][1];
            }
        }

        //share 神策事件  统计 分享被阅读数
        $where = [
            ['be_read','equal',[1]],
        ];
        $param = $this->params('share',['unique'],'and',$where,array('channel','is_new_user'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $val['by_values'][1] === null && $val['by_values'][1] = 0;
                $front = $val['by_values'][0];
                if($front != null){
                    // 总共的PV/UV
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['bread_'.$arr[$val['by_values'][1]].'_num'] = $val['values'][0][0];
                    $tmp[$front]['bread_'.$arr[$val['by_values'][1]].'_num'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['bread_'.$arr[$val['by_values'][1]].'_num'] += $val['values'][0][0];
            }
        }
        //渠道新增人数/活跃人数
        $channel_data_mod = M('channel_task_census');
        $data = $channel_data_mod->where(['date_time'=>$date])->field('channel,user_newly,user_active,user_num')->select();
        $res = [];
        if($data){
            foreach($data as $key=>$val){
                $res[$val['channel']] = $val;
            }
        }
        if(!empty($tmp)){
            // 渠道入库
            $model2 = M('channel_share_article_census','wx_','DB_HOST_COUNT');
            foreach ($tmp as $k=>$v){
                if(isset($res[$v['channel']])){
                    $v['user_newly_num'] = $res[$v['channel']]['user_newly'];
                    $v['user_active_num'] = $res[$v['channel']]['user_active'];
                    $v['user_num'] = $res[$v['channel']]['user_num'];
                }
                $v['date_time'] = $date;
                $cond = [];
                $cond['date_time'] =$date;
                $cond['channel'] =$v['channel'];
                if($model2->where($cond)->find()){
                    $model2->where($cond)->save($v);
                }else{
                    $model2->addUni($v);
                }
            }
        }

    }

    /**
     *  阅读统计 --  阅读篇数
     */
    public function count_read(){
        $date = $this->date;
        //viewContentDetail 神策事件
        $where = [
            ['contentID','isSet',[]],
            ['uid','isSet',[]],
        ];
        $result_bucket_param = [1,2,3,4,5,6,10,20,50];
        $user_where=[];
        $param = $this->distributionParams('viewContentDetail','times','day','and',$where,'and',$user_where,$result_bucket_param,$date,$date);
        $data = $this->get_curl_post($this->distribution_url,json_encode($param));
        $tmp = [];
        if($data['rows']) {
            foreach ($data['rows'] as $key => $val) {
                $tmp['read_num'] = $val['total_people'];
                foreach ($val['cells'] as $v){
                    if($v['bucket_start'] == 1 && $v['bucket_end'] == 2){
                        $tmp['read_1_num'] = $v['people'];
                        $tmp['read_1_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 2 && $v['bucket_end'] == 3){
                        $tmp['read_2_num'] = $v['people'];
                        $tmp['read_2_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 3 && $v['bucket_end'] == 4){
                        $tmp['read_3_num'] = $v['people'];
                        $tmp['read_3_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 4 && $v['bucket_end'] == 5){
                        $tmp['read_4_num'] = $v['people'];
                        $tmp['read_4_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 5 && $v['bucket_end'] == 6){
                        $tmp['read_5_num'] = $v['people'];
                        $tmp['read_5_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 6 && $v['bucket_end'] == 10){
                        $tmp['read_6_10_num'] = $v['people'];
                        $tmp['read_6_10_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 10 && $v['bucket_end'] == 20){
                        $tmp['read_10_20_num'] = $v['people'];
                        $tmp['read_10_20_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 20 && $v['bucket_end'] == 50){
                        $tmp['read_20_50_num'] = $v['people'];
                        $tmp['read_20_50_num_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 50){
                        $tmp['read_50_num'] = $v['people'];
                        $tmp['read_50_num_rate'] = $v['percent'];
                    }
                }
                $tmp['date_time'] = $val['by_value'];
            }
            //新增人数/活跃人数
            $channel_data_mod = M('channel_task_census');
            $data = $channel_data_mod->where(['date_time'=>$date,'channel'=>'0'])->field('channel,user_newly,user_active,user_num')->find();
            // 入库
            if($tmp){
                if($data){
                    $tmp['user_newly_num'] = $data['user_newly'];
                    $tmp['user_active_num'] = $data['user_active'];
                    $tmp['user_num'] = $data['user_num'];
                }
                $model2 = M('count_read','wx_','DB_HOST_COUNT');

                $cond = [];
                $cond['date_time'] =$date;
                if($model2->where($cond)->find()){
                    $model2->where($cond)->save($tmp);
                }else{
                    $model2->addUni($tmp);
                }
            }
        }


    }

    /**
     *  阅读统计 --  阅读时长
     */
    public function count_readtime(){
        $date = $this->date;
        //viewContentDetail 神策事件
        $where = [
            ['contentID','isSet',[]],
            ['uid','isSet',[]],
            ['read_time','isSet',[]],
        ];
        $measure = ['SUM','read_time'];
        $result_bucket_param = [120,300,600,900,1200,1800,3600];
        $user_where=[];
        $param = $this->distributionParams('viewContentDetail','times','day','and',$where,'and',$user_where,$result_bucket_param,$date,$date,$measure);
        $data = $this->get_curl_post($this->distribution_url,json_encode($param));
        $tmp = [];
        if($data['rows']) {
            foreach ($data['rows'] as $key => $val) {
                $tmp['read_num'] = $val['total_people'];
                $tmp['date_time'] = $date;
                foreach ($val['cells'] as $v){
                    if($v['bucket_end'] == 120){
                        $tmp['readtime_0_2_num'] = $v['people'];
                        $tmp['readtime_0_2_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 120 && $v['bucket_end'] == 300){
                        $tmp['readtime_2_5_num'] = $v['people'];
                        $tmp['readtime_2_5_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 300 && $v['bucket_end'] == 600){
                        $tmp['readtime_5_10_num'] = $v['people'];
                        $tmp['readtime_5_10_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 600 && $v['bucket_end'] == 900){
                        $tmp['readtime_10_15_num'] = $v['people'];
                        $tmp['readtime_10_15_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 900 && $v['bucket_end'] == 1200){
                        $tmp['readtime_15_20_num'] = $v['people'];
                        $tmp['readtime_15_20_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 1200 && $v['bucket_end'] == 1800){
                        $tmp['readtime_20_30_num'] = $v['people'];
                        $tmp['readtime_20_30_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 1800 && $v['bucket_end'] == 3600){
                        $tmp['readtime_30_60_num'] = $v['people'];
                        $tmp['readtime_30_60_rate'] = $v['percent'];
                    }
                    if($v['bucket_start'] == 3600){
                        $tmp['readtime_60_num'] = $v['people'];
                        $tmp['readtime_60_rate'] = $v['percent'];
                    }
                }

            }
            //阅读时长
            $aggr = array(
                array('SUM','read_time')
            );
            $param = $this->params('viewContentDetail',$aggr,'and',$where,array(),$date,$date);

            $data = $this->get_curl_post($this->event_url,json_encode($param,true));
            if($data['rows']){
                foreach($data['rows'] as $key=>$val){
                    $tmp['read_time'] = $val['values'][0][0];
                }
            }
            //新增人数/活跃人数
            $channel_data_mod = M('channel_task_census');
            $data = $channel_data_mod->where(['date_time'=>$date,'channel'=>'0'])->field('channel,user_newly,user_active,user_num')->find();
            // 入库
            if($tmp){
                if($data){
                    $tmp['user_newly_num'] = $data['user_newly'];
                    $tmp['user_active_num'] = $data['user_active'];
                    $tmp['user_num'] = $data['user_num'];
                }
                $model2 = M('count_readtime','wx_','DB_HOST_COUNT');

                $cond = [];
                $cond['date_time'] =$date;
                if($model2->where($cond)->find()){
                    $model2->where($cond)->save($tmp);
                }else{
                    $model2->addUni($tmp);
                }
            }
        }


    }

    /**
     *  晒收入统计
     */
    public function sun_income(){
        $date = $this->date;
        $arr = [
            '晒收入页保存相册点击'=>'save_album_btn',
            '晒收入页立即分享点击'=>'share_btn',
        ];
        //overall_click   晒收入相关按钮点击事件   晒收入页保存相册 立即分享
        $where = [
            ['task_center','equal',array_keys($arr)]
        ];
        $param = $this->params('overall_click',array('general','unique'),'and',$where,array('task_center'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        // 晒收入 任务中心 晒收入按钮点击
        $arr = [
            '晒收入'=>'sun_income_btn',
        ];
        //task_click
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $param = $this->params('task_click',array('general','unique'),'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        // 晒收入 任务中心 收入明细炫耀收入按钮点击
        $arr = [
            '收入明细炫耀收入按钮点击'=>'show_off_btn',
        ];
        //revenue_details_page_click
        $where = [
            ['task_center','equal',array_keys($arr)]
        ];
        $param = $this->params('overall_click',array('general','unique'),'and',$where,array('task_center'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp[$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        // 晒收入 后端埋点 mission_commit（完成人数 次数 金币总和）
        $condition = array(
            array('task_name','equal',array('晒收入'))
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['complete_pv'] = $val['values'][0][0];
                $tmp['complete_uv'] = $val['values'][0][1];
                $tmp['total_score'] = $val['values'][0][2];
            }
        }
        if(empty($tmp)){
            exit;
        }
        $tmp['date_time'] = $cond['date_time'] = $date;
        $model = M('count_sun_income','wx_','DB_HOST_COUNT');
        if($model->where($cond)->find()){
            $model->where($cond)->save($tmp);
        }else{
            $model->addUni($tmp);
        }
    }

    /**
     *  分渠道新增用户
     */
     public function channel_new_user(){
         set_time_limit(0);
         $date=$this->date;
         $url = $this->event_url;
         $tmp = array();
         // 新增设备
         $cond = [
             ['$'.'is_first_day','isTrue',[]]
         ];
         $param = $this->params('AppInstall',array( array('uniqCount','$'.'device_id')),'and',$cond,array('DownloadChannel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_device_num'] = $val['values'][0][0];
                 }
             }
         }
         //总新增设备
         $param = $this->params('AppInstall',array( array('uniqCount','$'.'device_id')),'and',$cond,array(),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_device_num'] = $val['values'][0][0];
             }
         }
         // 新增用户
         $cond = [
             ['$'.'is_first_day','isTrue',[]]
         ];
         $param = $this->params("$"."AppStart",array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_user_num'] = $val['values'][0][0];
                 }
             }
         }
         // 总新增用户
         $param = $this->params("$"."AppStart",array('unique'),'and',$cond,array(),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_user_num'] = $val['values'][0][0];
             }
         }
         // 注册用户  注册设备
         $cond = [];
         $param = $this->params('AppfirstLogin',array('unique',array('uniqCount','device_id')),'and',$cond,array('signUpResource','user_test'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 $front_2 = $val['by_values'][1];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     if($front_2 != null){
                         if($front_2 == '奇数'){
                             $tmp[$front]['new_register_odd_num'] = $val['values'][0][0];
                         }else if($front_2 == '偶数'){
                             $tmp[$front]['new_register_even_num'] = $val['values'][0][0];
                         }
                     }
                     $tmp[$front]['new_register_num'] += $val['values'][0][0];
                     $tmp[$front]['new_register_device_num'] += $val['values'][0][1];
                 }
             }
         }
         // 总注册用户  总注册设备
         $param = $this->params('AppfirstLogin',array('unique',array('uniqCount','device_id')),'and',$cond,array('user_test'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 $tmp[0]['channel'] = '0';
                 if($front != null){
                     if($front == '奇数'){
                         $tmp[0]['new_register_odd_num'] = $val['values'][0][0];
                     }else if($front == '偶数'){
                         $tmp[0]['new_register_even_num'] = $val['values'][0][0];
                     }
                 }
                 $tmp[0]['new_register_num'] += $val['values'][0][0];
                 $tmp[0]['new_register_device_num'] += $val['values'][0][1];
             }
         }
         // 总活跃用户  总活跃设备
         $cond = [];
         $param = $this->params('$'.'AppStart',array('unique',array('uniqCount','$'.'device_id')),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['active_user_num'] = $val['values'][0][0];
                     $tmp[$front]['active_device_num'] = $val['values'][0][1];
                 }
             }
         }
         // 总活跃用户  总活跃设备
         $param = $this->params('$'.'AppStart',array('unique',array('uniqCount','$'.'device_id')),'and',$cond,array(),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['active_user_num'] = $val['values'][0][0];
                 $tmp[0]['active_device_num'] = $val['values'][0][1];
             }
         }
         // 成功收徒的师傅用户数
         $cond = [
             ['is_new_user','equal',['1']]
         ];
         $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_invite_num'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_invite_num'] += $val['values'][0][0];
             }
         }
         // 尾数为8的成功收徒的师傅用户数
         $cond = [
             ['is_new_user','equal',['1']],
             ['$'.'distinct_id','rlike',["^\d+8$"]],
             ['channel','equal',["c1001"]],
         ];
         $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_invite_num_8'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_invite_num_8'] += $val['values'][0][0];
             }
         }
         // 1元提现 0.3元提现
         $cond = [
             ['exchange_result','equal',['成功']],
             ['cash_count','equal',[1,0.3]],
             ['is_new_user','equal',['1']],
         ];
         $param = $this->params('withdraw',array('unique'),'and',$cond,array('channel','cash_count'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 $front_2 = $val['by_values'][1];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     if($front_2 == 0.3){
                         $tmp[$front]['new_other_withdraw_num'] = $val['values'][0][0];
                     }else if($front_2 == 1){
                         $tmp[$front]['new_one_withdraw_num'] = $val['values'][0][0];
                     }
                 }
                 $tmp[0]['channel'] = '0';
                 if($front_2 == 0.3){
                     $tmp[0]['new_other_withdraw_num'] += $val['values'][0][0];
                 }else if($front_2 == 1){
                     $tmp[0]['new_one_withdraw_num'] += $val['values'][0][0];
                 }
             }
         }
         // 提现用户数
         $cond = [
             ['exchange_result','equal',['成功']],
             ['is_new_user','equal',['1']],
         ];
         $param = $this->params('withdraw',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_withdraw_num'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_withdraw_num'] += $val['values'][0][0];
             }
         }
         // 尾数为8的提现用户金额
         $cond = [
             ['exchange_result','equal',['成功']],
             ['is_new_user','equal',['1']],
             ['$'.'distinct_id','rlike',["^\d+8$"]],
             ['channel','equal',["c1001"]],
         ];
         $param = $this->params('withdraw',array(array('SUM','cash_count')),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_withdraw_score_8'] = $val['values'][0][0]*10000;
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_withdraw_score_8'] += $val['values'][0][0]*10000;
             }
         }
         // 邀请支出
         $cond = [
             ['award_money_type','equal',['邀请成功奖励']],
         ];
         $param = $this->params('finish_invite_new',array(array('SUM','award_money')),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_invite_more_score'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_invite_more_score'] += $val['values'][0][0];
             }
         }
         // 阅读用户数
         $cond = [
             ['is_new_user','equal',['1']],
         ];
         $param = $this->params('viewContentDetail',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_read_num'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_read_num'] += $val['values'][0][0];
             }
         }
         //天天领红包用户
         $task_arr = [
             '天天种红包'=>'new_day_day_red_num',
         ];
         $cond = [
             ['is_new_user','equal',['1']],
             ['task_name','equal',array_keys($task_arr)],
             ['if_finished','isTrue',[]],
         ];
         $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel','task_name'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front][$task_arr[$val['by_values'][1]]] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0][$task_arr[$val['by_values'][1]]] += $val['values'][0][0];
             }
         }
         //完成看看赚用户
         $task_arr = [
             '看看赚'=>'new_kankan_num',
         ];
         $cond = [
             ['is_new_user','equal',['1']],
             ['task_type_sec','equal',array_keys($task_arr)],
             ['if_finished','isTrue',[]],
         ];
         $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel','task_type_sec'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front][$task_arr[$val['by_values'][1]]] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0][$task_arr[$val['by_values'][1]]] += $val['values'][0][0];
             }
         }
         //完成阅读赚用户
         $cond = [
             ['is_new_user','equal',['1']],
             ['task_type_sec','equal',['红包赚','阅读赚']],
             ['if_finished','isTrue',[]],
         ];
         $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_ydz_num'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_ydz_num'] += $val['values'][0][0];
             }
         }
         // 领取新手红包用户
         $cond = [
             ['is_new_user','equal',['1']],
             ['is_success','isTrue',[]],
         ];
         $param = $this->params('get_novice_red',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_novice_red_num'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_novice_red_num'] += $val['values'][0][0];
             }
         }
         //分享文章用户数 分享文章被阅读用户数
         $cond = [
             ['is_new_user','equal',['1']],
         ];
         $param = $this->params('share',array('unique'),'and',$cond,array('channel','be_read'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     if($val['by_values'][1] == '1'){
                         $tmp[$front]['new_share_article_bread_num'] = $val['values'][0][0];
                     }else{
                         $tmp[$front]['new_share_article_num'] = $val['values'][0][0];
                     }
                 }
                 $tmp[0]['channel'] = '0';
                 if($val['by_values'][1] == '1'){
                     $tmp[0]['new_share_article_bread_num'] += $val['values'][0][0];
                 }else{
                     $tmp[0]['new_share_article_num'] += $val['values'][0][0];
                 }
             }
         }
         // 新增用户 提现页面的访问数(UV)
         //overall_click
         $cond = [
             ['task_center','equal',['立即提现']],
             ['is_new_user','equal',['1']],
         ];
         $param = $this->params('overall_click',array('unique'),'and',$cond,array('channel'),$date,$date);
         $data = $this->get_curl_post($url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front = $val['by_values'][0];
                 if($front != null){
                     $tmp[$front]['channel'] = $front;
                     $tmp[$front]['new_withdraw_page_uv'] = $val['values'][0][0];
                 }
                 $tmp[0]['channel'] = '0';
                 $tmp[0]['new_withdraw_page_uv'] += $val['values'][0][0];
             }
         }
         if(empty($tmp)){
             exit;
         }
         // 渠道入库
         $model2 = M('channel_new_census','wx_','DB_HOST_COUNT');
         foreach ($tmp as $k=>$v){
             $v['date_time'] = $date;
             $cond = [];
             $cond['date_time'] =$date;
             $cond['channel'] =$v['channel'];
             if($model2->where($cond)->find()){
                 $model2->where($cond)->save($v);
             }else{
                 $model2->addUni($v);
             }
         }
     }

    /**
     *  列表拉取统计
     */
    public function article_pull(){
        $date = $this->date;
        //viewContentDetail 神策事件
        $where = [];
        $result_bucket_param = [1,2,3,4,5,6,7,8,9,10,11,21,51,101,501,1001,2001];
        $user_where=[];
        $param = $this->distributionParams('article_pull','times','day','and',$where,'and',$user_where,$result_bucket_param,$date,$date);
        $data = $this->get_curl_post($this->distribution_url,json_encode($param));
        $tmp = [];
        if(isset($data['rows'][0]['cells']) && $data['rows'][0]['cells']) {
            $val = $data['rows'][0]['cells'];
            foreach ($result_bucket_param as $k=>$v){
                if($val[0]['bucket_start'] == $v && $val[0]['bucket_end'] == $result_bucket_param[$k+1]){
                    $tmp["pull_user_{$v}"] =$val[0]['people'];
                    array_shift($val);
                }
            }
            $tmp['date_time'] = $data['rows'][0]['by_value'];
        }
        // 统计总次数 人均次数
        $cond = [];
        $param = $this->params('article_pull',array('general','unique','average'),'and',$cond,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['pv'] = $val['values'][0][0];
                $tmp['uv'] = $val['values'][0][1];
                $tmp['avg'] = $val['values'][0][2];
            }
        }
        if($tmp){
            $model2 = M('article_pull_census');
            $cond = [];
            $cond['date'] = $tmp['date'] = $date;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($tmp);
            }else{
                $model2->addUni($tmp);
            }
        }

    }

    /**
     *  分渠道活跃用户
     */
    public function channel_active_user(){
        set_time_limit(0);
        $date=$this->date;
        $url = $this->event_url;
        $tmp = array();
        //渠道设备用户信息
        $channel_data_mod = M('channel_new_census','wx_','DB_HOST_COUNT');
        $data = $channel_data_mod->where(['date_time'=>$date])->field('channel,new_device_num,new_user_num,new_register_num,new_register_device_num,active_user_num,active_device_num')->select();
        $res = [];
        if($data){
            foreach($data as $key=>$val){
                $res[$val['channel']] = $val;
            }
        }
        // 成功收徒的师傅用户数
        $cond = [];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_invite_num'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_invite_num'] += $val['values'][0][0];
            }
        }
        // 尾数为8的成功收徒的师傅用户数
        $cond = [
            ['$'.'distinct_id','rlike',["^\d+8$"]],
            ['channel','equal',["c1001"]],
        ];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_invite_num_8'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_invite_num_8'] += $val['values'][0][0];
            }
        }
        // 1元提现
        $cond = [
            ['exchange_result','equal',['成功']],
            ['cash_count','equal',[1]]
        ];
        $param = $this->params('withdraw',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_one_withdraw_num'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_one_withdraw_num'] += $val['values'][0][0];
            }
        }
        // 提现用户数
        $cond = [
            ['exchange_result','equal',['成功']],
        ];
        $param = $this->params('withdraw',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_withdraw_num'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_withdraw_num'] += $val['values'][0][0];
            }
        }
        // 尾数为8的提现用户金额
        $cond = [
            ['exchange_result','equal',['成功']],
            ['$'.'distinct_id','rlike',["^\d+8$"]],
            ['channel','equal',["c1001"]],
        ];
        $param = $this->params('withdraw',array(array('SUM','cash_count')),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_withdraw_score_8'] = $val['values'][0][0]*10000;
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_withdraw_score_8'] += $val['values'][0][0]*10000;
            }
        }
        // 邀请支出
        $cond = [
            ['award_money_type','equal',['邀请成功奖励']],
        ];
        $param = $this->params('finish_invite_new',array(array('SUM','award_money')),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_invite_more_score'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_invite_more_score'] += $val['values'][0][0];
            }
        }
        // 阅读用户数
        $cond = [];
        $param = $this->params('viewContentDetail',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['active_read_num'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['active_read_num'] += $val['values'][0][0];
            }
        }
        // 天天领红包用户
        $task_arr = [
            '天天种红包'=>'active_day_day_red_num',
        ];
        $cond = [
            ['task_name','equal',array_keys($task_arr)],
            ['if_finished','isTrue',[]],
        ];
        $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel','task_name'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front][$task_arr[$val['by_values'][1]]] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0][$task_arr[$val['by_values'][1]]] += $val['values'][0][0];
            }
        }
        // 完成看看赚用户
        $task_arr = [
            '看看赚'=>'active_kankan_num',
        ];
        $cond = [
            ['task_type_sec','equal',array_keys($task_arr)],
            ['if_finished','isTrue',[]],
        ];
        $param = $this->params('mission_commit',array('unique'),'and',$cond,array('channel','task_type_sec'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front][$task_arr[$val['by_values'][1]]] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0][$task_arr[$val['by_values'][1]]] += $val['values'][0][0];
            }
        }
        //分享文章用户数 分享文章被阅读用户数
        $cond = [];
        $param = $this->params('share',array('unique'),'and',$cond,array('channel','be_read'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    if($val['by_values'][1] == '1'){
                        $tmp[$front]['active_share_article_bread_num'] = $val['values'][0][0];
                    }else{
                        $tmp[$front]['active_share_article_num'] = $val['values'][0][0];
                    }
                }
                $tmp[0]['channel'] = '0';
                if($val['by_values'][1] == '1'){
                    $tmp[0]['active_share_article_bread_num'] += $val['values'][0][0];
                }else{
                    $tmp[0]['active_share_article_num'] += $val['values'][0][0];
                }
            }
        }
        if(empty($tmp)){
            exit;
        }
        // 渠道入库
        $model2 = M('channel_active_census','wx_','DB_HOST_COUNT');
        foreach ($tmp as $k=>$v){
            if(isset($res[$v['channel']])){
                $v['new_device_num'] = $res[$v['channel']]['new_device_num'];
                $v['new_user_num'] = $res[$v['channel']]['new_user_num'];
                $v['new_register_num'] = $res[$v['channel']]['new_register_num'];
                $v['new_register_device_num'] = $res[$v['channel']]['new_register_device_num'];
                $v['active_user_num'] = $res[$v['channel']]['active_user_num'];
                $v['active_device_num'] = $res[$v['channel']]['active_device_num'];
            }
            $v['date_time'] = $date;
            $cond = [];
            $cond['date_time'] =$date;
            $cond['channel'] =$v['channel'];
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
    }


    /*
     * 红包赚统计
     * */
    public function task_red_census(){

        $url_list  = M('task_red')->field('id,type')->select();
        foreach($url_list as $val){
            $conf[$val['id']] = $val['type'];
        }

        $fields = array(
            'general',
            'unique',
            array('sum','coin_counts')
        );
        $cond = array(
            array('task_type_sec','equal',array('阅读赚')),
            array('if_finished','isTrue',array()),
        );
        $param = $this->params('mission_commit',$fields,'and',$cond,array('utm_source'),'','',array(array('hot_id',null)));
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        foreach($data['rows'] as $key=>$val){
            $result[$val['by_values'][0]] = array(
                'url_id'=>$val['by_values'][0],
                'pv'    =>$val['values'][0][0],
                'uv'    =>$val['values'][0][1],
                'coins' =>$val['values'][0][2],
                'type'  =>$conf[$val['by_values'][0]],
                'date'  =>$this->date
            );
        }
        foreach($result as $key=>$val){
            $result['total']['url_id']='total';
            $result['total']['pv']+=$val['pv'];
            $result['total']['uv']+=$val['uv'];
            $result['total']['coins']+=$val['coins'];
            $result['total']['type']=0;
            $result['total']['date']=$this->date;
        }

        $type = array(
            '30秒阅读红包'  =>1,
            '60秒阅读红包'  =>2,
            '30秒浏览红包'  =>3,
            '60秒浏览红包'  =>4,
        );
        $fields = array(
            'general',
            'unique',
            array('sum','coin_counts')
        );
        $cond = array(
            array('task_type_sec','equal',array('阅读赚')),
            array('if_finished','isTrue',array()),
        );
        $param = $this->params('mission_commit',$fields,'and',$cond,array('task_name'),'','',array(array('hot_id',null)));
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        foreach($data['rows'] as $key=>$val){
            $result[] = array(
                'url_id'=>$type[$val['by_values'][0]],
                'pv'    =>$val['values'][0][0],
                'uv'    =>$val['values'][0][1],
                'coins' =>$val['values'][0][2],
                'type'  =>$type[$val['by_values'][0]],
                'date'  =>$this->date
            );
        }
        $model = M('task_red_census');
        foreach($result as $key=>$data){
            $cond = array(
                'url_id'=>$data['url_id'],
                'date'  =>$this->date
            );
            if($model->where($cond)->find()){
                $model->where($cond)->save($data);
            }else{
                $model->addUni($data);
            }
        }
    }

    /**
     * 签到统计
     */
    public function sign_census(){
        $date = $this->date;
        // 整点点击事件  task_click
        $map = ['general', 'unique'];
        $channels = [
            '签到点击'=>'sign',
            '赚取更多'=>'earn_more',
            '弹窗换一换'=>'popup_change',
            '弹窗看看赚任务点击'=>'popup_kkz',
            '弹窗看热文任务点击'=>'popup_krw',
            '弹窗看热点任务点击'=>'popup_krd',
            '弹窗搜索热词任务点击'=>'popup_hot_words',
            '弹窗试玩赚任务点击'=>'popup_try_play',
            '弹窗晒收入任务点击'=>'popup_sun_income',
            '弹窗评论送金币任务点击'=>'popup_comment',
            '弹窗邀请赚任务点击'=>'popup_invite_make',
            '弹窗唤醒徒弟任务点击'=>'popup_awake',
            '弹窗邀请亲友任务点击'=>'popup_invite_relatives',
            '弹窗阅读文章任务点击'=>'popup_read_article',
            '弹窗红包赚任务点击'=>'popup_hbz',
            '弹窗领取神秘任务'=>'popup_mystery_task',
            '弹窗邀请好友任务点击'=>'popup_invite_friends',
        ];
        $where = [
            ['task_type_sec','equal',['签到']]
        ];
        $param = $this->params('task_click',$map,'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(isset($channels[$val['by_values'][0]])){
                    $tmp[$channels[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                    $tmp[$channels[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
                }
            }
        }
        if(!$tmp){
            exit();
        }
        $tmp['date_time'] = $date;
        // 渠道入库
        $model2 =  M('count_sign','wx_','DB_HOST_COUNT');
        $cond = [];
        $cond['date_time'] =$date;
        if($model2->where($cond)->find()){
            $model2->where($cond)->save($tmp);
        }else{
            $model2->addUni($tmp);
        }
        $this->task_red_census();
    }

    /**
     * 搜索统计
     */
    public function search_words_census(){
        $date = $this->date;
        $tmp = [];
        // 首页搜索点击   整点点击事件  overall_click
        $map = ['general', 'unique'];
        $where = [
            ['click_event','equal',['首页搜索入口点击']]
        ];
        $param = $this->params('overall_click',$map,'and',$where,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['home_click_pv'] = $val['values'][0][0];
                $tmp['home_click_uv'] = $val['values'][0][1];
            }
        }
        // 日常任务搜索点击   整点点击事件  task_click
        $map = ['general', 'unique'];
        $where = [
            ['task_name','equal',['搜索热词']],
            ['task_type','equal',['日常任务']]
        ];
        $param = $this->params('task_click',$map,'and',$where,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['daily_task_click_pv'] = $val['values'][0][0];
                $tmp['daily_task_click_uv'] = $val['values'][0][1];
            }
        }
        //搜索热词任务完成情况   做任务事件 mission_commit
        $condition = array(
            array('task_name','equal',array('搜索热词')),
            array('if_finished','isTrue',array()),
            array('finished_num','greater',array('0')),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['score_num'] = $val['values'][0][0];
                $tmp['score_num_uv'] = $val['values'][0][1];
                $tmp['score_total'] = $val['values'][0][2];
            }
        }
        //搜索热词任务完成情况   做1次
        $result_bucket_param = [1,2];
        $user_where=[];
        $param = $this->distributionParams('mission_commit','times','day','and',$condition,'and',$user_where,$result_bucket_param,$date,$date);
        $data = $this->get_curl_post($this->distribution_url,json_encode($param));
        if(isset($data['rows'][0]['cells']) && $data['rows'][0]['cells']) {
            $val = $data['rows'][0]['cells'];
            foreach ($result_bucket_param as $k=>$v){
                if($val[0]['bucket_start'] == $v && $val[0]['bucket_end'] == $result_bucket_param[$k+1]){
                    if ($v == 1){
                        $tmp["finish_num_{$v}"] =$val[0]['people'];
                    }
                    array_shift($val);
                }
            }
        }
        //搜索热词任务完成情况   全部完成的人数
        $condition = array(
            array('task_name','equal',array('搜索热词')),
            array('if_finished','isTrue',array()),
            array('finished_num','greater',array('0')),
            array('is_finished_all','isTrue',array()),
        );
        $aggr = array(
            'unique',
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['finish_num_all'] = $val['values'][0][0];
            }
        }
        //活跃设备
        $channel_data_mod = M('channel_new_census','wx_','DB_HOST_COUNT');
        $data = $channel_data_mod->where(['date_time'=>$date,'channel'=>'0'])->field('channel,active_device_num')->find();
        if($data){
            $tmp['active_device_num'] = $data['active_device_num'];
        }
        if(!$tmp){
            exit();
        }
        $tmp['date_time'] = $date;
        // 渠道入库
        $model2 =  M('count_search_words','wx_','DB_HOST_COUNT');
        $cond = [];
        $cond['date_time'] =$date;
        if($model2->where($cond)->find()){
            $model2->where($cond)->save($tmp);
        }else{
            $model2->addUni($tmp);
        }
        $this->task_red_census();
    }


    /**
     * 分任务统计完成情况
     */
    public function count_task_finish(){
        ini_set('default_socket_timeout',-1);
        set_time_limit(0);
        $date = $this->date;
        $tmp = [];
        //分任务统计  奖励次数 支出金额  做任务事件 mission_commit
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','isNotEmpty',array())
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                $tmp[$front]['task_name'] =$front;
                $tmp[$front]['score_num'] = $val['values'][0][0];
                $tmp[$front]['score_user_num'] = $val['values'][0][1];
                $tmp[$front]['score_total'] = $val['values'][0][2];
            }
        }
        //分任务统计  全部完成的人数  做任务事件 mission_commit
        $channels = ['看看赚每天一次性任务','红包赚每天一次性任务','看热文','看热点','30秒阅读红包','60秒阅读红包','2分钟阅读红包','3分钟阅读红包','搜索热词','激励视频(看看赚任务)','30秒浏览红包','60秒浏览红包','首页搜索','热门搜索','最热搜索'];
        $condition = array(
            array('task_name','equal',$channels),
            array('is_finished_all','isTrue',array()),
        );
        $aggr = array('unique');
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                $tmp[$front]['task_name'] =$front;
                $tmp[$front]['finish_num_all'] = $val['values'][0][0];
            }
        }
        //完成一次的人数  做任务事件 mission_commit
        foreach ($channels as $key=>$val){
            $condition = array(
                array('task_name','equal',[$val]),
                array('if_finished','isTrue',array())
            );
            $result_bucket_param = [1,2];
            $user_where=[];
            $param = $this->distributionParams('mission_commit','times','day','and',$condition,'and',$user_where,$result_bucket_param,$date,$date);
            $data = $this->get_curl_post($this->distribution_url,json_encode($param));
            if(isset($data['rows'][0]['cells']) && $data['rows'][0]['cells']) {
                $cells = $data['rows'][0]['cells'];
                foreach ($result_bucket_param as $k=>$v){
                    if($cells[0]['bucket_start'] == $v && $cells[0]['bucket_end'] == $result_bucket_param[$k+1]){
                        if ($v == 1) {
                            $tmp[$val]["finish_num_{$v}"] = $cells[0]['people'];
                            $tmp[$val]['task_name'] =$val;
                            break;
                        }
                        array_shift($cells);
                    }
                }
            }
        }
        //领取时段奖励
        $condition = array(
            array('task_name','equal',['开宝箱(时段奖励)']),
            array('if_finished','isTrue',array()),
        );
        $result_bucket_param = [24,25];
        $user_where=[];
        $param = $this->distributionParams('mission_commit','times','day','and',$condition,'and',$user_where,$result_bucket_param,$date,$date);
        $data = $this->get_curl_post($this->distribution_url,json_encode($param));
        if(isset($data['rows'][0]['cells']) && $data['rows'][0]['cells']) {
            $cells = $data['rows'][0]['cells'];
            foreach ($result_bucket_param as $k=>$v){
                if($cells[0]['bucket_start'] == $v && $cells[0]['bucket_end'] == $result_bucket_param[$k+1]){
                    if ($v == 24) {
                        $tmp['开宝箱(时段奖励)']["finish_num_all"] = $cells[0]['people'];
                        $tmp['开宝箱(时段奖励)']['task_name'] ='领取时段奖励';
                        break;
                    }
                    array_shift($cells);
                }
            }
        }
        //额外统计
        $arr = [
            '看热文入口点击'=>['task_id'=>3,'task_name'=>'看热文'],
            '看热点入口点击'=>['task_id'=>4,'task_name'=>'看热点'],
            '看视频入口点击'=>['task_id'=>10,'task_name'=>'看视频'],
            '看看赚入口'=>['task_id'=>1,'task_name'=>'看看赚每天一次性任务'],
            '30秒阅读红包'=>['task_id'=>5,'task_name'=>'30秒阅读红包'],
            '1分钟阅读红包'=>['task_id'=>6,'task_name'=>'60秒阅读红包'],
            '30秒浏览红包'=>['task_id'=>31,'task_name'=>'30秒浏览红包'],
            '60秒浏览红包'=>['task_id'=>32,'task_name'=>'60秒浏览红包'],
            '热门搜索'=>['task_id'=>41,'task_name'=>'热门搜索'],
            '搜索赚热门搜索'=>['task_id'=>41,'task_name'=>'热门搜索'],
            '搜索赚热词搜索'=>['task_id'=>9,'task_name'=>'搜索热词'],
            '搜索热词'=>['task_id'=>9,'task_name'=>'搜索热词'],
            '玩游戏赚金币'=>['task_id'=>55,'task_name'=>'玩游戏赚金币(闲玩)'],
            '高额赚试玩领赏金'=>['task_id'=>55,'task_name'=>'玩游戏赚金币(闲玩)'],
            '大转盘页面'=>['task_id'=>57,'task_name'=>'大转盘'],
            '搜索赚最热搜索'=>['task_id'=>60,'task_name'=>'最热搜索'],
            '最热搜索'=>['task_id'=>60,'task_name'=>'最热搜索'],
            '简单赚分享被阅读赚金币'=>['task_id'=>42,'task_name'=>'简单赚分享被阅读得金币'],
            '简单赚阅读得金币'=>['task_id'=>49,'task_name'=>'简单赚阅读得金币'],
            '简单赚阅读文章赚金币'=>['task_id'=>61,'task_name'=>'简单赚阅读文章赚金币'],
        ];
        //task_click
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $param = $this->params('task_click',['general','unique'],'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(isset($arr[$val['by_values'][0]])){
                    $front = $arr[$val['by_values'][0]];
                    $tmp[$front['task_name']]['task_name'] = $front['task_name'];
                    $tmp[$front['task_name']]['entrance_pv'] += $val['values'][0][0];
                    $tmp[$front['task_name']]['entrance_uv'] += $val['values'][0][1];
                }
            }
        }
        //首页搜索入口点击
        $where = [
            ['click_event','equal',['首页搜索入口点击']],
        ];
        $param = $this->params('overall_click',['general','unique'],'and',$where,array('click_event'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = '搜索热词';
                $tmp[$front]['task_name'] = $front;
                $tmp[$front]['entrance_pv'] += $val['values'][0][0];
                $tmp[$front]['entrance_uv'] += $val['values'][0][1];
            }
        }
        //简单赚阅读文章赚金币额外统计
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','contain',array("简单赚阅读文章")),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['简单赚阅读文章赚金币']['task_name'] ='简单赚阅读文章赚金币';
                $tmp['简单赚阅读文章赚金币']['score_num'] = $val['values'][0][0];
                $tmp['简单赚阅读文章赚金币']['score_user_num'] = $val['values'][0][1];
                $tmp['简单赚阅读文章赚金币']['score_total'] = $val['values'][0][2];
            }
        }
        //大转盘额外统计
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','contain',array("大转盘")),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['大转盘']['task_name'] ='大转盘';
                $tmp['大转盘']['score_num'] = $val['values'][0][0];
                $tmp['大转盘']['score_user_num'] = $val['values'][0][1];
                $tmp['大转盘']['score_total'] = $val['values'][0][2];
            }
        }
        //激励视频统计
        $data_mod = M('stimulate_video_channel');
        $data = $data_mod->where(['date_time'=>$date,'channel'=>'0'])->find();
        if($data){
            $tmp['激励视频']['task_name'] = '激励视频';
            $tmp['激励视频']['entrance_pv'] = $data['show_num_pv'];
            $tmp['激励视频']['entrance_uv'] = $data['show_num_uv'];
            $tmp['激励视频']['score_num'] = $data['play_num_pv'];
            $tmp['激励视频']['score_user_num'] = $data['play_num_uv'];
            $tmp['激励视频']['score_total'] = $data['score'];
        }
        if(!$tmp){
            exit();
        }
        //活跃设备 活跃用户
        $active_device_num = $new_welfare_num = $active_user_num = 0;
        $channel_data_mod = M('channel_new_census','wx_','DB_HOST_COUNT');
        $data = $channel_data_mod->where(['date_time'=>$date,'channel'=>'0'])->field('channel,active_device_num,active_user_num')->find();
        if($data){
            $active_device_num = $data['active_device_num'];
            $active_user_num = $data['active_user_num'];
        }
        //新手期用户
        $cond = [
            ['is_new_welfare','equal',['1']]
        ];
        $param = $this->params("$"."AppStart",array('unique'),'and',$cond,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $new_welfare_num = $val['values'][0][0];
            }
        }
        // 渠道入库
        $model2 =  M('count_task_finish','wx_','DB_HOST_COUNT');
        foreach ($tmp as $k=>$v){
            $v['date_time'] = $date;
            $v['active_device_num'] = $active_device_num;
            $v['active_user_num'] = $active_user_num;
            $v['new_welfare_num'] = $new_welfare_num;
            $cond = [];
            $cond['date_time'] =$date;
            $cond['task_name'] = $v['task_name'];
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
        exit();
    }

    /**
     *  任务中心  任务汇总统计
     */
     public function count_task_center(){
         set_time_limit(0);
         ini_set('memory_limit', '256M');
         //查询条件
         $map = array();
         $start_time = I('get.start_time',date('Y-m-d',strtotime('-1 day')));
         $end_time = I('get.end_time',date('Y-m-d',strtotime('-1 day')));
         //overall_click
         $where = [
             ['task_center','equal',['立即提现']],
             ['is_new_user','equal',['1']],
         ];
         $param = $this->params('overall_click',array('general','unique'),'and',$where,array('task_center'),$start_time,$end_time);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         $tmp = [];
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 foreach ($val['values'] as $k=>$v){
                     $series = date("Y-m-d",strtotime($data['series'][$k]));
                     $tmp[$series]['pv'] = $v[0];
                     $tmp[$series]['uv'] = $v[1];
                 }
             }
         }
         //查询时间范围
         $map['date_time'][] = array('egt',$start_time);
         $map['date_time'][] = array('lt',date('Y-m-d',strtotime($end_time)+60*60*24));
         $map['channel'] = '0';

         $order = 'date_time ASC';
         $field = 'date_time,new_register_num,new_other_withdraw_num,id';

         //根据条件查询出所有订单
         $list = M('channel_new_census','wx_','DB_HOST_COUNT')->where($map)->field($field)->order($order)->select();

         if ($list) {
             foreach ($list as $k=>$v){
                 if(isset($tmp[$v['date_time']])){
                     $list[$k]['pv'] = $tmp[$v['date_time']]['pv'];
                     $list[$k]['uv'] = $tmp[$v['date_time']]['uv'];
                 }
             }
         }
        if(!$list){
             exit();
        }
         //导出到excel
         import('Org.Util.PHPExcel');
         $objPHPExcel = new \PHPExcel();
         /* 设置输出的excel文件为2007兼容格式 */
         $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
         /* 设置当前的sheet */
         $objPHPExcel->setActiveSheetIndex(0);
         $objActSheet = $objPHPExcel->getActiveSheet();
         /* 设置宽度 */
         $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
         $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
         $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
         $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
         $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
         $objActSheet->setTitle('提现数据');
         //设置第一行内容
         $i = '1';
         $j = 'A';
         $objActSheet->setCellValue($j . $i, '日期');
         $j = 'B';
         $objActSheet->setCellValue($j . $i, '新增用户数');
         $j = 'C';
         $objActSheet->setCellValue($j . $i, '0.3元提现成功用户数');
         $j = 'D';
         $objActSheet->setCellValue($j . $i, 'PV');
         $j = 'E';
         $objActSheet->setCellValue($j . $i, 'UV');
         $i=2;

         foreach ($list as $k => $v) {
             $j = 'A';
             $objActSheet->setCellValue($j . $i, $v['date_time']);
             $j = 'B';
             $objActSheet->setCellValue($j . $i, $v['new_register_num']);
             $j = 'C';
             $objActSheet->setCellValue($j . $i, $v['new_other_withdraw_num']);
             $j = 'D';
             $objActSheet->setCellValue($j . $i, $v['pv']);
             $j = 'E';
             $objActSheet->setCellValue($j . $i, $v['uv']);
             $i++;
         }
         //定义文件名
         $fileName = '提现数据导出'.$start_time.'--'.$end_time.'.xlsx';

         /* 生成到浏览器，提供下载 */
         ob_end_clean();  //清空缓存
         header("Pragma: public");
         header("Expires: 0");
         header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
         header("Content-Type:application/force-download");
         header("Content-Type:application/vnd.ms-execl");
         header("Content-Type:application/octet-stream");
         header("Content-Type:application/download");
         header('Content-Disposition:attachment;filename="' . $fileName . '"');
         header("Content-Transfer-Encoding:binary");
         $objWriter->save('php://output');
         exit();
     }

     /**
      * 任务中心  任务按钮点击汇总统计
      */
     public function count_task_btn(){
         $date = $this->date;
         // 整点点击事件  task_click
         $map = ['general', 'unique'];
         $positions = [
             '任务中心'=>['position_id'=>1,'position_name'=>'任务中心'],
             '首页'=>['position_id'=>2,'position_name'=>'首页'],
             '我的'=>['position_id'=>3,'position_name'=>'我的'],
             '底部tab'=>['position_id'=>4,'position_name'=>'底部tab'],
             '看看赚'=>['position_id'=>5,'position_name'=>'看看赚'],
             '高额赚'=>['position_id'=>6,'position_name'=>'高额赚'],
             '弹窗'=>['position_id'=>8,'position_name'=>'弹窗'],
             '计时赚'=>['position_id'=>9,'position_name'=>'计时赚'],
         ];
         //task_click
         $where = [
             array('position','equal',array_keys($positions)),
             array('task_name','isNotEmpty',array())
         ];
         $param = $this->params('task_click',$map,'and',$where,array('position','task_name'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         $tmp = [];
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = $val['by_values'][0];
                 $front2 = $val['by_values'][1];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //首页激励视频 RewardVideo
         $channels = [
             'RewardVideoShow'=>['task_name'=>'首页置顶激励视频']
         ];
         $positions = [
             '1'=>['position_id'=>2,'position_name'=>'首页'],
         ];
         $where = [
             ['ad_event','equal',array_keys($channels)],
             ['play_entrance','equal',array_keys($positions)],
         ];
         $param = $this->params('RewardVideo',['general','unique'],'and',$where,array('play_entrance','ad_event'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = $positions[$val['by_values'][0]]['position_name'];
                 $front2 = $channels[$val['by_values'][1]]['task_name'];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$val['by_values'][0]]['position_id'];
                 $tmp[$front]['position_name'] = $front1;
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //时段奖励
         $positions = [
             '首页'=>['position_id'=>2,'position_name'=>'首页'],
         ];
         $where = [
             ['task_name','equal',['时段奖励入口点击']],
         ];
         $param = $this->params('sontask_click',['general','unique'],'and',$where,array('task_name'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = '首页';
                 $front2 = $val['by_values'][0];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //摇红包按钮
         $positions = [
             '首页'=>['position_id'=>2,'position_name'=>'首页'],
         ];
         $where = [
             ['shake_red_click','equal',['领取金币按钮点击']],
         ];
         $param = $this->params('shake_red_new',['general','unique'],'and',$where,array('shake_red_click'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = '首页';
                 $front2 = $val['by_values'][0];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //搜索
         $positions = [
             '首页'=>['position_id'=>2,'position_name'=>'首页'],
         ];
         $where = [
             ['click_event','equal',['首页搜索入口点击']],
         ];
         $param = $this->params('overall_click',['general','unique'],'and',$where,array('click_event'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = '首页';
                 $front2 = $val['by_values'][0];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //我的页面按钮统计
          $positions = [
             '我的页面'=>['position_id'=>3,'position_name'=>'我的'],
         ];
         $where = [
             ['page','equal',['我的页面']],
             ['entrance_click','isNotEmpty',[]],
         ];
         $bucket_params = [
             ['entrance_click',null]
         ];
         $param = $this->params('button_click',['general','unique'],'and',$where,array('page','entrance_click'),$date,$date,$bucket_params);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = $val['by_values'][0];
                 $front2 = $val['by_values'][1];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         // 我的页面banner统计
         $positions = [
             '我的页面'=>['position_id'=>3,'position_name'=>'我的'],
         ];
         $where = [
             ['page','equal',['我的页面']],
             ['entrance_click','isNotEmpty',[]],
         ];
         $bucket_params = [
             ['entrance_click',null]
         ];
         $param = $this->params('banner_click',['general','unique'],'and',$where,array('page','entrance_click'),$date,$date,$bucket_params);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = $val['by_values'][0];
                 $front2 = $val['by_values'][1];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //收益说明页面
         $channels = ['收益说明页面展示','收益说明页面收徒','收益说明页面查看奖励明细','收益说明页面赚取更多金币'];
         //overall_click
         $where = [
             ['task_center','equal',$channels]
         ];
         $param = $this->params('overall_click',['general','unique'],'and',$where,array('task_center'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1 = '收益说明页';
                 $front2 = $val['by_values'][0];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = 7;
                 $tmp[$front]['position_name'] = '收益说明';
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         //底部tab页统计 enterTab
         $positions = [
             '底部tab'=>['position_id'=>4,'position_name'=>'底部tab'],
         ];
         $map = ['general', 'unique'];
         $where = [
             array('tabName','isNotEmpty',array())
         ];
         $bucket_params = [
             ['tabName',null]
         ];
         $param = $this->params('enterTab',$map,'and',$where,array('tabName'),$date,$date,$bucket_params);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $front1= '底部tab';
                 $front2 = $val['by_values'][0];
                 $front = $front1."_".$front2;
                 $tmp[$front]['position_id'] = $positions[$front1]['position_id'];
                 $tmp[$front]['position_name'] = $positions[$front1]['position_name'];
                 $tmp[$front]['task_name'] = $front2;
                 $tmp[$front]['button_pv'] = $val['values'][0][0];
                 $tmp[$front]['button_uv'] = $val['values'][0][1];
             }
         }
         if(!$tmp){
             exit();
         }
         //活跃设备
         $active_device_num = $new_welfare_num = 0;
         $channel_data_mod = M('channel_new_census','wx_','DB_HOST_COUNT');
         $data = $channel_data_mod->where(['date_time'=>$date,'channel'=>'0'])->field('channel,active_device_num')->find();
         if($data){
             $active_device_num = $data['active_device_num'];
         }
         //新手期用户
         $cond = [
             ['is_new_welfare','equal',['1']]
         ];
         $param = $this->params("$"."AppStart",array('unique'),'and',$cond,array(),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $new_welfare_num = $val['values'][0][0];
             }
         }
         // 渠道入库
         $model2 = M('count_task_btn','wx_','DB_HOST_COUNT');
         foreach ($tmp as $k=>$v){
             $v['date_time'] = $date;
             $v['active_device_num'] = $active_device_num;
             $v['new_welfare_num'] = $new_welfare_num;
             $cond = [];
             $cond['date_time'] =$date;
             $cond['position_id'] = $v['position_id'];
             $cond['task_name'] =$v['task_name'];
             if($model2->where($cond)->find()){
                 $model2->where($cond)->save($v);
             }else{
                 $model2->addUni($v);
             }
         }
     }

     /**
      *  拼多多 汇总统计
      */
     public function pdd_order_count(){
         set_time_limit(0);
         $date = $this->date;
         //$date = '2019-04-25';
         // 整点点击事件  task_click
         $map = ['general', 'unique'];
         //task_click
         $where = [
             array('task_name','equal',['分销得返利'])
         ];
         $param = $this->params('task_click',$map,'and',$where,array('task_name'),$date,$date);
         $data = $this->get_curl_post($this->event_url,json_encode($param,true));
         $tmp = [];
         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $tmp['entry_pv'] = $val['values'][0][0];
                 $tmp['entry_uv'] = $val['values'][0][1];
             }
         }
         // 拼多多订单完成情况
         $cond = [];
         $cond['order_pay_time'][] = array('egt',strtotime($date));
         $cond['order_pay_time'][] = array('lt',strtotime($date)+86400);
         $Data_mod = M('pdd_order');
         //预估收入金额
         $tmp['promotion_amount'] = $Data_mod->where($cond)->sum('promotion_amount');
         //预估返现金额
         $tmp['cashback_amount'] = $Data_mod->where($cond)->sum('promotion_amount_true');
         //支付笔数
         $tmp['pay_num'] = $Data_mod->where($cond)->count();
         //成团笔数
         $cond = [];
         $cond['order_group_success_time'][] = array('egt',strtotime($date));
         $cond['order_group_success_time'][] = array('lt',strtotime($date)+86400);
         $tmp['group_num'] = $Data_mod->where($cond)->count();
         //实际给用户支出金额
         $condition = array(
             array('if_finished','isTrue',array()),
             array('finished_num','greater',array('0')),
             array('task_name','equal',array('拼多多分享赚')),
         );
         $aggr = array(
             array('SUM','coin_counts')
         );
         $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);

         $data = $this->get_curl_post($this->event_url,json_encode($param,true));

         if($data['rows']){
             foreach($data['rows'] as $key=>$val){
                 $tmp['cashback_amount_real'] = $val['values'][0][0]/100;
             }
         }
         if(!$tmp){
             exit();
         }
         // 渠道入库
         $model2 = M('count_pdd','wx_','DB_HOST_COUNT');

         $cond = [];
         $cond['date_time'] = $tmp['date_time'] = $date;
         if($model2->where($cond)->find()){
             $model2->where($cond)->save($tmp);
         }else{
             $model2->addUni($tmp);
         }

     }

    /**
     *  简单赚分享文章阅读
     */
    public function simple_share_article(){
        $date = $this->date;
        $map = ['general', 'unique'];
        //share_simple_article 神策事件
        //分享次数 分享用户数
        $where = [
            ['be_read','isSet',[]],
        ];
        $arr = ['share','share_beread'];
        $param = $this->params('share_simple_article',$map,'and',$where,array('article_id','be_read'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                $front2 = $val['by_values'][1];

                if($front != null){
                    $front = 'article_'.$front;
                    $tmp[$front]['article_id'] = $val['by_values'][0];
                    $tmp[$front][$arr[$front2]."_pv"] = $val['values'][0][0];
                    $tmp[$front][$arr[$front2]."_uv"] = $val['values'][0][1];
                }
                $tmp[0]['article_id'] = 0;
                $tmp[0][$arr[$front2]."_pv"] += $val['values'][0][0];
                $tmp[0][$arr[$front2]."_uv"] += $val['values'][0][1];
            }
        }
        //分享次数 分享用户数
        $where = [
            ['be_read','isSet',[]],
        ];
        $arr = ['share','share_beread'];
        $param = $this->params('share_simple_article',$map,'and',$where,array('article_id','be_read'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                $front2 = $val['by_values'][1];

                if($front != null){
                    $front = 'article_'.$front;
                    $tmp[$front]['article_id'] = $val['by_values'][0];
                    $tmp[$front][$arr[$front2]."_pv"] = $val['values'][0][0];
                    $tmp[$front][$arr[$front2]."_uv"] = $val['values'][0][1];
                }
                $tmp[0]['article_id'] = 0;
                $tmp[0][$arr[$front2]."_pv"] += $val['values'][0][0];
                $tmp[0][$arr[$front2]."_uv"] += $val['values'][0][1];
            }
        }
        //分享次数 分享用户数
        $where = [
            ['is_success','isTrue',[]],
        ];
        $map = [
            'general',
            array('SUM','score')
        ];
        $param = $this->params('share_simple_article',$map,'and',$where,array('article_id'),$date,$date);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $front = 'article_'.$front;
                    $tmp[$front]['article_id'] = $val['by_values'][0];
                    $tmp[$front]['score_times'] = $val['values'][0][0];
                    $tmp[$front]['score'] = $val['values'][0][1];
                }
                $tmp[0]['article_id'] = 0;
                $tmp[0]['score_times'] += $val['values'][0][0];
                $tmp[0]['score'] += $val['values'][0][1];
            }
        }
        if(empty($tmp)){
            exit();
        }
        // 渠道入库
        $model2 = M('simple_share_article','wx_','DB_HOST_COUNT');
        foreach ($tmp as $k=>$v){
            $v['date_time'] = $date;
            $cond = [];
            $cond['date_time'] =$date;
            $cond['article_id'] =$v['article_id'];
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }

    }

    /**
     *  简单赚阅读得金币统计
     */
    public function simple_article_read(){
        set_time_limit(0);
        $date = $this->date;
        //分hotid统计PVUV
        $where = [
            ['task_name','equal',['简单赚阅读得金币']]
        ];
        $bucket_params = [
            ['hot_id',null]
        ];
        $param = $this->params('task_click',['general','unique'],'and',$where,array('hot_id'),$date,$date,$bucket_params);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp2 = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    //渠道 每日统计
                    $front2 ='hot_id'.$front;
                    $tmp2[$front2]['hot_id'] =  $front;
                    $tmp2[$front2]['click_pv'] =  $val['values'][0][0];
                    $tmp2[$front2]['click_uv'] =  $val['values'][0][1];
                }
            }
        }

        //统计总PV/UV
        $where = [
            ['task_name','equal',['简单赚阅读得金币']]
        ];
        $param = $this->params('task_click',['general','unique'],'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp2[0]['hot_id'] =  0;
                $tmp2[0]['click_pv'] =  $val['values'][0][0];
                $tmp2[0]['click_uv'] =  $val['values'][0][1];
            }
        }
        //分hotid统计给钱次数
        $condition = array(
            array('task_name','equal',array('简单赚阅读得金币')),
            array('if_finished','isTrue',array())
        );
        $aggr = array(
            'general',
            array('SUM','coin_counts')
        );
        $bucket_params = [
            ['utm_source',null]
        ];
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('utm_source'),$date,$date,$bucket_params);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    //渠道 每日统计
                    $front2 ='hot_id'.$front;
                    $tmp2[$front2]['hot_id'] =  $front;
                    $tmp2[$front2]['score_num'] = $val['values'][0][0];
                    $tmp2[$front2]['score_total'] = $val['values'][0][1];
                }
                $tmp2[0]['hot_id'] =  0;
                $tmp2[0]['score_num'] += $val['values'][0][0];
                $tmp2[0]['score_total'] += $val['values'][0][1];
            }
        }
        if(!$tmp2){
            exit();
        }
        // 渠道入库
        $model2 = M('simple_article_read','wx_','DB_HOST_COUNT');
        foreach ($tmp2 as $k=>$v){
            $cond = [];
            $cond['date_time'] =$date;
            $cond['hot_id'] = $v['hot_id'];
            $v['date_time'] = $date;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
    }

    /**
     *  悬赏任务统计
     */
    public function reward_task_census(){
        set_time_limit(0);
        $date = $this->date;
        $arr = [
            '悬赏任务立即报名按钮'=>'sign_up',
            '悬赏任务立即提交按钮'=>'submit',
        ];
        // 分任务ID统计按钮点击
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $bucket_params = [
            ['utm_source',null]
        ];
        $param = $this->params('task_click',['general','unique'],'and',$where,array('utm_source','task_name'),$date,$date,$bucket_params);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        $tmp2 = [];
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = 'task_'.$val['by_values'][0];
                if($val['by_values'][0] != null){
                    $tmp2[$front]['task_id'] =  $val['by_values'][0];
                    $tmp2[$front][$arr[$val['by_values'][1]].'_pv'] = $val['values'][0][0];
                    $tmp2[$front][$arr[$val['by_values'][1]].'_uv'] = $val['values'][0][1];
                }
            }
        }
        // 所有任务按钮点击统计
        $where = [
            ['task_name','equal',array_keys($arr)]
        ];
        $param = $this->params('task_click',['general','unique'],'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp2[0]['task_id'] =  0;
                $tmp2[0][$arr[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                $tmp2[0][$arr[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
            }
        }
        //mission_commit
        $condition = array(
            array('task_name','equal',array('悬赏任务')),
            array('if_finished','isTrue',array())
        );
        $aggr = array(
            'general',
            array('SUM','coin_counts')
        );
        $bucket_params = [
            ['utm_source',null]
        ];
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('utm_source'),$date,$date,$bucket_params);

        $data = $this->get_curl_post($this->event_url,json_encode($param,true));

        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = 'task_'.$val['by_values'][0];
                if($val['by_values'][0] != null){
                    //渠道 每日统计
                    $tmp2[$front]['task_id'] =  $val['by_values'][0];
                    $tmp2[$front]['score_num'] = $val['values'][0][0];
                    $tmp2[$front]['score_total'] = $val['values'][0][1];
                }
                $tmp2[0]['task_id'] =  0;
                $tmp2[0]['score_num'] += $val['values'][0][0];
                $tmp2[0]['score_total'] += $val['values'][0][1];
            }
        }
        if(!$tmp2){
            exit();
        }
        // 所有任务按钮点击统计
        $where = [
            ['task_name','equal',['高额悬赏任务']]
        ];
        $reward_uv = 0;
        $param = $this->params('task_click',['unique'],'and',$where,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $reward_uv = $val['values'][0][0];
                break;
            }
        }
        // 渠道入库
        $model2 = M('reward_task_census','wx_','DB_HOST_COUNT');
        foreach ($tmp2 as $k=>$v){
            if($v['task_id']!=0){
                $taskInfo = getRewardTaskCache($v['task_id']);
                $v['title'] = $taskInfo['title'];
                $v['unit_rate_price'] = $taskInfo['unit_rate_price'];
            }
            $cond = [];
            $cond['date_time'] =$date;
            $cond['task_id'] = $v['task_id'];
            $v['date_time'] = $date;
            $v['reward_uv'] = $reward_uv;
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
    }

    /**
     *  分渠道邀请活动统计
     */
    public function channel_invite_census(){
        set_time_limit(0);
        $date=$this->date;
        $url = $this->event_url;
        $tmp = array();
        //渠道设备用户信息
        $channel_data_mod = M('channel_active_census','wx_','DB_HOST_COUNT');
        $data = $channel_data_mod->where(['date_time'=>$date])->field('channel,new_device_num,new_user_num,new_register_num,new_register_device_num,active_user_num,active_device_num,active_invite_num')->select();
        $res = [];
        if($data){
            foreach($data as $key=>$val){
                $res[$val['channel']] = $val;
            }
        }
        // 渠道新手期用户
        $cond = [
            ['is_new_welfare','equal',['1']]
        ];
        $param = $this->params('$'.'AppStart',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['new_welfare_num'] = $val['values'][0][0];
                }
            }
        }
        // 新手期用户
        $param = $this->params('$'.'AppStart',array('unique'),'and',$cond,array(),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[0]['channel'] = '0';
                $tmp[0]['new_welfare_num'] = $val['values'][0][0];
            }
        }
        // 分渠道分是否新手期统计成功收徒的师傅用户数
        $cond = [];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel','is_new_welfare'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    if($val['by_values'][1] == '1'){
                        $tmp[$front]['invite_num_new'] = $val['values'][0][0];
                    }else if($val['by_values'][1] == '0') {
                        $tmp[$front]['invite_num_old'] = $val['values'][0][0];
                    }
                }
            }
        }
        //分是否新手期统计成功收徒的师傅用户数
        $cond = [];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('is_new_welfare'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[0]['channel'] = '0';
                if($val['by_values'][0] == '1'){
                    $tmp[0]['invite_num_new'] = $val['values'][0][0];
                }else if($val['by_values'][0] == '0') {
                    $tmp[0]['invite_num_old'] = $val['values'][0][0];
                }
            }
        }
        // 分渠道分是否首次邀请统计成功收徒的师傅用户数
        $cond = [
            ['invite_number','equal',["0"]]
        ];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['invite_num_first'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['invite_num_first'] += $val['values'][0][0];
            }
        }
        $cond = [
            ['invite_number','greater',["0"]]
        ];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['invite_num_not_first'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['invite_num_not_first'] += $val['values'][0][0];
            }
        }
        if(empty($tmp)){
            exit;
        }
        // 渠道入库
        $model2 = M('channel_invite_census','wx_','DB_HOST_COUNT');
        foreach ($tmp as $k=>$v){
            if(isset($res[$v['channel']])){
                $v['new_device_num'] = $res[$v['channel']]['new_device_num'];
                $v['new_user_num'] = $res[$v['channel']]['new_user_num'];
                $v['new_register_num'] = $res[$v['channel']]['new_register_num'];
                $v['new_register_device_num'] = $res[$v['channel']]['new_register_device_num'];
                $v['active_user_num'] = $res[$v['channel']]['active_user_num'];
                $v['active_device_num'] = $res[$v['channel']]['active_device_num'];
                $v['invite_num'] = $res[$v['channel']]['active_invite_num'];
                isset($v['new_welfare_num']) && $v['old_user_num'] = $res[$v['channel']]['active_user_num']-$v['new_welfare_num'];
            }
            $v['date_time'] = $date;
            $v['invite_detail_score'] = json_encode(['1'=>['date'=>$date,'invite_score'=>0,'invite_extra_score'=>0]]);
            $v['invite_score'] = 0;
            $v['invite_extra_score'] = 0;
            $cond = [];
            $cond['date_time'] =$date;
            $cond['channel'] =$v['channel'];
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
        // 邀请支出
        $tmp = [];
        $type = [
            "邀请成功奖励"=>"invite_score",
            "进贡"=>"invite_extra_score",
        ];
        $cond = [
            ['award_money_type','equal',array_keys($type)],
            ['award_step','between',['1','3']]
        ];
        $param = $this->params('finish_invite_new',array(array('SUM','award_money')),'and',$cond,array('channel','award_money_type','award_step'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $channel = $val['by_values'][0];
                $award_money_type = $val['by_values'][1];
                $award_step = $val['by_values'][2]-1;
                $date_time =  date("Y-m-d",(strtotime($date) - 3600*24*$award_step));
                if($channel != null){
                    $tmp[$date_time.'_'.$channel]['date_time'] = $date_time;
                    $tmp[$date_time.'_'.$channel]['channel'] = $channel;
                    $tmp[$date_time.'_'.$channel]['award_step'] = $val['by_values'][2];
                    $tmp[$date_time.'_'.$channel][$type[$award_money_type]] = $val['values'][0][0];
                }
                $tmp[$date_time.'_0']['date_time'] = $date_time;
                $tmp[$date_time.'_0']['channel'] = '0';
                $tmp[$date_time.'_0']['award_step'] = $val['by_values'][2];
                $tmp[$date_time.'_0'][$type[$award_money_type]] += $val['values'][0][0];
            }
        }
        if(empty($tmp)){
            exit;
        }
        foreach ($tmp as $k=>$v){
            $tmp[$k]['invite_extra_score'] = $v['invite_extra_score'] = $v['invite_extra_score']>0 ? $v['invite_extra_score']-$v['invite_score'] : 0;
            $cond = [];
            $cond['date_time'] =$v['date_time'];
            $cond['channel'] =$v['channel'];
            $info = $model2->where($cond)->find();
            if($info){
                $save = $detail_save = [];
                $invite_detail_score = json_decode($info['invite_detail_score'],true);
                if(isset($v['invite_score'])){
                    $save['invite_score'] = array("exp","invite_score+".$v['invite_score']);
                    $invite_detail_score[$v['award_step']]['invite_score'] = $v['invite_score'];
                }
                if(isset($v['invite_extra_score'])){
                    $save['invite_extra_score'] = array("exp","invite_extra_score+".$v['invite_extra_score']);
                    $invite_detail_score[$v['award_step']]['invite_extra_score'] = $v['invite_extra_score'];
                }
                if(!empty($invite_detail_score)){
                    $invite_detail_score[$v['award_step']]['date'] = $date;
                    $save['invite_detail_score'] = json_encode($invite_detail_score);
                }
                if(!empty($save)){
                    $model2->where($cond)->save($save);
                }
            }
        }
    }

    /**
     *  分渠道邀请活动统计（邀请1好友30元）
     */
    public function channel_invite_activity(){
        set_time_limit(0);
        $date=$this->date;
        $url = $this->event_url;
        $tmp = array();
        //渠道设备用户信息
        $channel_data_mod = M('channel_active_census','wx_','DB_HOST_COUNT');
        $data = $channel_data_mod->where(['date_time'=>$date])->field('channel,new_device_num,new_user_num,new_register_num,new_register_device_num,active_user_num,active_device_num,active_invite_num')->select();
        $res = [];
        if($data){
            foreach($data as $key=>$val){
                $res[$val['channel']] = $val;
            }
        }
        // 分渠道统计成功收徒的师傅用户数
        $cond = [
            ['activity_name','equal',["邀请1好友30元"]]
        ];
        $param = $this->params('Invite_friends_new',array('unique'),'and',$cond,array('channel'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if($front != null){
                    $tmp[$front]['channel'] = $front;
                    $tmp[$front]['finish_num'] = $val['values'][0][0];
                }
                $tmp[0]['channel'] = '0';
                $tmp[0]['finish_num'] += $val['values'][0][0];
            }
        }
        if(empty($tmp)){
            exit;
        }
        // 渠道入库
        $model2 = M('channel_invite_activity','wx_','DB_HOST_COUNT');
        foreach ($tmp as $k=>$v){
            if(isset($res[$v['channel']])){
                $v['new_device_num'] = $res[$v['channel']]['new_device_num'];
                $v['new_user_num'] = $res[$v['channel']]['new_user_num'];
                $v['new_register_num'] = $res[$v['channel']]['new_register_num'];
                $v['new_register_device_num'] = $res[$v['channel']]['new_register_device_num'];
                $v['active_user_num'] = $res[$v['channel']]['active_user_num'];
                $v['active_device_num'] = $res[$v['channel']]['active_device_num'];
            }
            $v['date_time'] = $date;
            $v['activity_name'] = '邀请1好友30元';
            $v['invite_score'] = 0;
            $v['invite_extra_score'] = 0;
            $v['invite_detail_score'] = json_encode(['1'=>['date'=>$date,'invite_score'=>0,'invite_extra_score'=>0]]);
            $cond = [];
            $cond['activity_name'] ='邀请1好友30元';
            $cond['date_time'] =$date;
            $cond['channel'] =$v['channel'];
            if($model2->where($cond)->find()){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
        // 邀请支出
        $tmp = [];
        $type = [
            "邀请成功奖励"=>"invite_score",
            "双倍进贡"=>"invite_extra_score",
        ];
        $cond = [
            ['award_money_type','equal',array_keys($type)],
            ['award_step','greater',['0']],
            ['activity_name','equal',["邀请1好友30元"]]
        ];
        $param = $this->params('finish_invite_new',array(array('SUM','award_money')),'and',$cond,array('channel','award_money_type','award_step'),$date,$date);
        $data = $this->get_curl_post($url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $channel = $val['by_values'][0];
                $award_money_type = $val['by_values'][1];
                $award_step = $val['by_values'][2]-1;
                $date_time =  date("Y-m-d",(strtotime($date) - 3600*24*$award_step));
                if($channel != null){
                    $tmp[$date_time.'_'.$channel]['date_time'] = $date_time;
                    $tmp[$date_time.'_'.$channel]['channel'] = $channel;
                    $tmp[$date_time.'_'.$channel]['award_step'] = $val['by_values'][2];
                    $tmp[$date_time.'_'.$channel][$type[$award_money_type]] = $val['values'][0][0];
                }
                $tmp[$date_time.'_0']['date_time'] = $date_time;
                $tmp[$date_time.'_0']['channel'] = '0';
                $tmp[$date_time.'_0']['award_step'] = $val['by_values'][2];
                $tmp[$date_time.'_0'][$type[$award_money_type]] += $val['values'][0][0];
            }
        }
        if(empty($tmp)){
            exit;
        }
        foreach ($tmp as $k=>$v){
            $cond = [];
            $cond['activity_name'] ='邀请1好友30元';
            $cond['date_time'] =$v['date_time'];
            $cond['channel'] =$v['channel'];
            $info = $model2->where($cond)->find();
            if($info){
                $save = $detail_save = [];
                $invite_detail_score = json_decode($info['invite_detail_score'],true);
                if(isset($v['invite_score'])){
                    $save['invite_score'] = array("exp","invite_score+".$v['invite_score']);
                    $invite_detail_score[$v['award_step']]['invite_score'] = $v['invite_score'];
                }
                if(isset($v['invite_extra_score'])){
                    $save['invite_extra_score'] = array("exp","invite_extra_score+".$v['invite_extra_score']);
                    $invite_detail_score[$v['award_step']]['invite_extra_score'] = $v['invite_extra_score'];
                }
                if(!empty($invite_detail_score)){
                    $invite_detail_score[$v['award_step']]['date'] = $date;
                    $save['invite_detail_score'] = json_encode($invite_detail_score);
                }
                if(!empty($save)){
                    $model2->where($cond)->save($save);
                }
            }
        }
    }

    /**
     *  分渠道新手用户成本
     */
    public function channel_new_cost(){
        set_time_limit(0);
        $date=$this->date;
        $url = $this->event_url;
        //测试代码
//        $date1 = '2019-06-11';
//        $date2 = '2019-06-24';
//        $timestamp1=strtotime($date1);
//        $timestamp2=strtotime($date2);
//        $days=($timestamp2-$timestamp1)/86400+1;
       // for($i=0;$i<$days;$i++){
            //$date=date('Y-m-d',$timestamp1+(86400*$i));
            $tmp = array();
            //渠道设备用户信息
            $channel_data_mod = M('channel_active_census','wx_','DB_HOST_COUNT');
            $data = $channel_data_mod->where(['date_time'=>$date])->field('channel,new_device_num,new_user_num,new_register_num,new_register_device_num,active_user_num,active_device_num,active_invite_num')->select();
            $res = [];
            if($data){
                foreach($data as $key=>$val){
                    $res[$val['channel']] = $val;
                }
            }
            // 分渠道统计新手用户的支出
            $map = [
                ['SUM','coin_counts']
            ];
            $where = [
                ['is_new_welfare','equal',['true']],
                ['if_finished','isTrue',[]],
                ['new_welfare_number','greater',['0']],
            ];
            $bucket_params = [
                ['channel',null]
            ];
            $param = $this->params('mission_commit',$map,'and',$where,array('channel','new_welfare_number'),$date,$date,$bucket_params);
            $data = $this->get_curl_post($url,json_encode($param,true));
            if($data['rows']) {
                foreach ($data['rows'] as $key => $val) {
                    $channel = $val['by_values'][0];
                    $new_welfare_number = ((int)$val['by_values'][1]) - 1;
                    $date_time = date("Y-m-d", (strtotime($date) - 3600 * 24 * $new_welfare_number));
                    if ($channel != null) {
                        $tmp[$date_time . '_' . $channel]['date_time'] = $date_time;
                        $tmp[$date_time . '_' . $channel]['channel'] = $channel;
                        $tmp[$date_time . '_' . $channel]['new_welfare_number'] = $val['by_values'][1];
                        $tmp[$date_time . '_' . $channel]['task_score'] = $val['values'][0][0];
                    }
                    $tmp[$date_time . '_0']['date_time'] = $date_time;
                    $tmp[$date_time . '_0']['channel'] = '0';
                    $tmp[$date_time . '_0']['new_welfare_number'] = $val['by_values'][1];
                    $tmp[$date_time . '_0']['task_score'] += $val['values'][0][0];
                }
            }
            // 分渠道统计新手用户的提现支出
            $map = [
                ['SUM','cash_count']
            ];
            $where = [
                ['is_new_welfare','equal',['true']],
                ['exchange_result','equal',['成功']],
                ['new_welfare_number','greater',['0']],
            ];
            $bucket_params = [
                ['channel',null]
            ];
            $param = $this->params('withdraw',$map,'and',$where,array('channel','new_welfare_number'),$date,$date,$bucket_params);
            $data = $this->get_curl_post($url,json_encode($param,true));
            if($data['rows']){
                foreach($data['rows'] as $key=>$val){
                    $channel = $val['by_values'][0];
                    $new_welfare_number = ((int)$val['by_values'][1])-1;
                    $date_time =  date("Y-m-d",(strtotime($date) - 3600*24*$new_welfare_number));
                    if($channel != null){
                        $tmp[$date_time.'_'.$channel]['date_time'] = $date_time;
                        $tmp[$date_time.'_'.$channel]['channel'] = $channel;
                        $tmp[$date_time.'_'.$channel]['new_welfare_number'] = $val['by_values'][1];
                        $tmp[$date_time.'_'.$channel]['withdraw_score'] = $val['values'][0][0]*C('SCORE_RATIO');
                    }
                    $tmp[$date_time.'_0']['date_time'] = $date_time;
                    $tmp[$date_time.'_0']['channel'] = '0';
                    $tmp[$date_time.'_0']['new_welfare_number'] = $val['by_values'][1];
                    $tmp[$date_time.'_0']['withdraw_score'] += $val['values'][0][0]*C('SCORE_RATIO');
                }
            }
            if(empty($tmp)){
                exit();
            }
            // 渠道入库
            $model2 = M('channel_new_cost','wx_','DB_HOST_COUNT');
            foreach ($tmp as $k=>$v){
                $cond = [];
                $cond['date_time'] =$v['date_time'];
                $cond['channel'] =$v['channel'];
                $info = $model2->where($cond)->find();
                if($v['date_time']==$date){
                    if(isset($res[$v['channel']])){
                        $v['new_device_num'] = $res[$v['channel']]['new_device_num'];
                        $v['new_user_num'] = $res[$v['channel']]['new_user_num'];
                        $v['new_register_num'] = $res[$v['channel']]['new_register_num'];
                        $v['new_register_device_num'] = $res[$v['channel']]['new_register_device_num'];
                    }
                    $v['date_time'] = $date;
                    $v['task_detail_score'] = json_encode(['1'=>['date'=>$date,'task_score'=>isset($v['task_score'])?$v['task_score']:0]]);
                    $v['withdraw_detail_score'] = json_encode(['1'=>['date'=>$date,'withdraw_score'=>isset($v['withdraw_score'])?$v['withdraw_score']:0]]);
                    unset($v['new_welfare_number']);
                    if($info){
                        $model2->where($cond)->save($v);
                    }else{
                        $model2->addUni($v);
                    }
                }elseif(!empty($info)){
                    $save = $detail_save = [];
                    $task_detail_score = json_decode($info['task_detail_score'],true);
                    $withdraw_detail_score = json_decode($info['withdraw_detail_score'],true);
                    if(isset($v['task_score'])){
                        if(isset($task_detail_score[$v['new_welfare_number']]['task_score'])){
                            $save['task_score'] = ($info['task_score']-$task_detail_score[$v['new_welfare_number']]['task_score'])+$v['task_score'];
                        }else{
                            $save['task_score'] = array("exp","task_score+".$v['task_score']);
                        }
                        $task_detail_score[$v['new_welfare_number']]['task_score'] = $v['task_score'];
                        $task_detail_score[$v['new_welfare_number']]['date'] = $date;
                        $save['task_detail_score'] = json_encode($task_detail_score);
                    }
                    if(isset($v['withdraw_score'])){
                        if(isset($withdraw_detail_score[$v['new_welfare_number']]['withdraw_score'])){
                            $save['withdraw_score'] = ($info['withdraw_score']-$withdraw_detail_score[$v['new_welfare_number']]['withdraw_score'])+$v['withdraw_score'];
                        }else{
                            $save['withdraw_score'] = array("exp","withdraw_score+".$v['withdraw_score']);
                        }
                        $withdraw_detail_score[$v['new_welfare_number']]['withdraw_score'] = $v['withdraw_score'];
                        $withdraw_detail_score[$v['new_welfare_number']]['date'] = $date;
                        $save['withdraw_detail_score'] = json_encode($withdraw_detail_score);
                    }
                    if(!empty($save)){
                        $model2->where($cond)->save($save);
                    }
                }
            }
        }

    /**
     * 视频模块统计
     */
    public function count_video(){
        set_time_limit(0);
        $date=$this->date;
        $tmp = [];
        //视频模块
        $condition = array(
            array('video_event','equal',array('refresh','loadMore')),
        );
        $aggr = array('general', 'unique');
        $param = $this->params('videoModule',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[1]['refresh_pv'] = $val['values'][0][0];
                $tmp[1]['refresh_uv'] =$val['values'][0][1];
            }
        }
        $condition = array(
            array('video_event','equal',array('play')),
        );
        $aggr = array('general', 'unique');
        $param = $this->params('videoModule',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[1]['play_pv'] = $val['values'][0][0];
                $tmp[1]['play_uv'] =$val['values'][0][1];
            }
        }
        //小视频
        $condition = array(
            array('video_event','equal',array('refresh','loadMore')),
        );
        $aggr = array('general', 'unique');
        $param = $this->params('littleVideoModule',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[2]['refresh_pv'] = $val['values'][0][0];
                $tmp[2]['refresh_uv'] =$val['values'][0][1];
            }
        }
        $condition = array(
            array('video_event','equal',array('play')),
        );
        $aggr = array('general', 'unique');
        $param = $this->params('littleVideoModule',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[2]['play_pv'] = $val['values'][0][0];
                $tmp[2]['play_uv'] =$val['values'][0][1];
            }
        }
        //做任务统计 mission_commit
        $channels = [
          '普通视频'=>1,
          '好兔小视频'=>2,
        ];
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','equal',array('观看视频','观看视频额外奖励')),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('utm_source'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if(isset($channels[$front])){
                    $tmp[$channels[$front]]['complete_num'] = $val['values'][0][0];
                    $tmp[$channels[$front]]['complete_user'] =$val['values'][0][1];
                    $tmp[$channels[$front]]['score'] = $val['values'][0][2];
                }
            }
        }
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','equal',array('观看视频','观看视频额外奖励')),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[0]['complete_num'] = $val['values'][0][0];
                $tmp[0]['complete_user'] =$val['values'][0][1];
                $tmp[0]['score'] = $val['values'][0][2];
            }
        }
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','equal',array('观看视频额外奖励')),
            array('finished_num','equal',array(120)),
        );
        $aggr = array('unique');
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp[0]['complete_all_user'] = $val['values'][0][0];
            }
        }
        if(!$tmp){
            exit();
        }
        //渠道设备用户信息
        $channel_data_mod = M('channel_active_census','wx_','DB_HOST_COUNT');
        $active_info = $channel_data_mod->where(['date_time'=>$date,'channel'=>'0'])->field('channel,new_device_num,new_user_num,new_register_num,new_register_device_num,active_user_num,active_device_num,active_invite_num')->find();
        // 入库
        $model2 = M('count_video','wx_','DB_HOST_COUNT');
        foreach ($tmp as $k=>$v){
            if(!empty($active_info)){
                $v['active_user_num'] = $active_info['active_user_num'];
            }
            $v['date_time'] = $date;
            $v['type'] = $k;
            $cond = [];
            $cond['date_time'] =$date;
            $cond['type'] = $k;
            $info = $model2->where($cond)->find();
            if($info){
                $model2->where($cond)->save($v);
            }else{
                $model2->addUni($v);
            }
        }
        exit();
    }

    /**
     * 锁屏模块统计
     */
    public function count_lock_screen(){
        set_time_limit(0);
        $date=$this->date;
        $tmp = [];
        $channels = [
            '左滑'=>'left_slide',
            '刷新文章'=>'refresh',
            '锁屏赚'=>'entrance',
            '右滑'=>'right_slide',
            '文章底部按钮'=>'bottom_btn',
        ];
        //task_click
        $condition = array(
            array('position','equal',array('锁屏')),
            array('task_name','equal',array('锁屏赚')),
        );
        $aggr = array('general', 'unique');
        $param = $this->params('task_click',$aggr,'or',$condition,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(isset($channels[$val['by_values'][0]])){
                    $tmp[$channels[$val['by_values'][0]].'_pv'] = $val['values'][0][0];
                    $tmp[$channels[$val['by_values'][0]].'_uv'] = $val['values'][0][1];
                }
            }
        }
        // mission_commit
        $channels = [
            '锁屏开宝箱金币'=>'box',
            '锁屏右滑解锁奖励'=>'right_slide',
        ];
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','equal',array('锁屏开宝箱金币','锁屏右滑解锁奖励')),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                if(isset($channels[$val['by_values'][0]])){
                    $tmp[$channels[$val['by_values'][0]].'_num'] = $val['values'][0][0];
                    $tmp[$channels[$val['by_values'][0]].'_user_num'] = $val['values'][0][1];
                    $tmp[$channels[$val['by_values'][0]].'_score'] = $val['values'][0][2];
                }
            }
        }
        // 锁屏 时段奖励 支出金币
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','equal',array('开宝箱(时段奖励)')),
            array('utm_source','equal',array('锁屏左滑')),
        );
        $aggr = array(array('SUM','coin_counts'));
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['left_slide_score'] = $val['values'][0][0];
            }
        }
        if(!$tmp){
            exit();
        }
        // 入库
        $model2 = M('count_lock_screen','wx_','DB_HOST_COUNT');
        $cond = [];
        $cond['date_time'] = $date;
        $tmp['date_time'] = $date;
        $info = $model2->where($cond)->find();
        if($info){
            $model2->where($cond)->save($tmp);
        }else{
            $model2->addUni($tmp);
        }
        exit();
    }

    /**
     * 视频模块统计
     */
    public function count_rotary_table(){
        set_time_limit(0);
        $date=$this->date;
        $tmp = [];
        //大转盘点击统计 task_click
        $channels = [
            '大转盘页面'=>'entrance',
            '大转盘抽奖点击'=>'shake',
            '大转盘大图广告'=>'shake_ad',
            '大转盘翻倍奖励'=>'reward_video',
            '大转盘广告展示'=>'js_ad',
        ];
        $condition = array(
            array('task_name','contain',array("大转盘")),
        );
        $aggr = array(
            'general',
            'unique'
        );
        $param = $this->params('task_click',$aggr,'and',$condition,array('task_name'),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $front = $val['by_values'][0];
                if(isset($channels[$front])){
                    $tmp[$channels[$front].'_pv'] = $val['values'][0][0];
                    $tmp[$channels[$front].'_uv'] = $val['values'][0][1];
                }
            }
        }
        //大转盘给钱统计 mission_commit
        $condition = array(
            array('if_finished','isTrue',array()),
            array('task_name','contain',array("大转盘")),
        );
        $aggr = array(
            'general',
            'unique',
            array('SUM','coin_counts')
        );
        $param = $this->params('mission_commit',$aggr,'and',$condition,array(),$date,$date);
        $data = $this->get_curl_post($this->event_url,json_encode($param,true));
        if($data['rows']){
            foreach($data['rows'] as $key=>$val){
                $tmp['score_num'] = $val['values'][0][0];
                $tmp['score_user_num'] = $val['values'][0][1];
                $tmp['score_total'] = $val['values'][0][2];
            }
        }
        if(!$tmp){
            exit();
        }
        // 入库
        $model2 = M('count_rotary_table','wx_','DB_HOST_COUNT');
        $tmp['date_time'] = $date;
        $cond = [];
        $cond['date_time'] =$date;
        $info = $model2->where($cond)->find();
        if($info){
            $model2->where($cond)->save($tmp);
        }else{
            $model2->addUni($tmp);
        }
        exit();
    }
}