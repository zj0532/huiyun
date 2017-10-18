<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/29
 * Time: 19:49
 * 孵化服务功能的操作类,电脑端和手机端公用的操作
 */

namespace app\common\controller;
use think\Db;

class Baseservice extends Base
{
    /**
     * 园企互动，获取园企互动列表
     * @param array $map 查询的条件，电脑端因为有企业端，所以要分开
     * @return array
     */
    function getSuggest($con= array()){

        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=getDataList("EtprsSuggest",$con,"a.*,b.name","a.status asc,a.addtime desc",$join);
        if($msg["code"]==="1"){
            $tmplist=self::getDictStr("*","EtprsSuggest");
            $msg['data']=$this->setListIdText($msg['data'],$tmplist);
            for ($i = 0; $i < count($msg['data']); $i++) {
                $msg['data'][$i]["addtime"]=date("Y-m-d H:i:s",$msg['data'][$i]["addtime"]);
            }

            return $msg["data"];
        }else{
            return array();
        }
    }

    /**
     * 服务活动，对活动申请进行操作，通过或者拒绝
     * @param $id  活动申请的ID
     * @param $status 操作状态
     * @return array
     */
    function aplStatus($id,$status)
    {
        if($status=="2"||$status=="3"){
            $join = [['activity b','a.activityId=b.id']];
            $actApl=findById("ActivityApply",array("a.id"=>$id),"a.adduserId,a.etprsId,a.activityId,b.name,b.id",$join);
            if($actApl["code"]==='1'&&!empty($actApl["data"])){
                $res = saveData("ActivityApply",array("id"=>$id,"status"=>$status));
                if($res['code']==1){
                    $actname=$actApl['data']["name"];
                    $type="1020005";
                    $title="活动: ".$actname."申请".($status=="2"?"通过":"被拒绝");
                    $content="活动: ".$actname."申请".($status=="2"?"通过":"被拒绝")."，请确认。";
                    $emailData = array(
                        'type'=>$type,
                        'title'=>$title,
                        'content'=>$content,
                        'relTable'=>'activityApply',
                        'relId'=>$id,
                    );
                    $wxData = array();
                    //发送微信通知
                    if(config('open_wechat')){
                        $key2 = $status==2 ?'审核通过':'审核拒绝';
                        $template_id = config('wx_tpl.activityCheck');
                        $wxData = array(
                            'tpl'=>$template_id,
                            'data'=>array(
                                'keyword1'=>$actname,
                                'keyword2'=>$key2,
                            ),
                            'first'=>'活动审核通知',
                            'remark'=>'请登录系统，查看详情'
                        );
                    }
                    $actUserId=$actApl['data']["adduserId"];
                    $this->sendAllMsg($actUserId,$emailData,$wxData);
                    //工作日志：
                    $logData = array(
                        'etprsId'=>$actApl['data']['etprsId'],
                        'fmenuId'=>27,
                        'smenuId'=>30,
                        'objId'=>$id,
                        'content'=>$title,
                    );
                    saveOaLog($logData);
                }
                return $res;
            }
        }
        return array('code'=>0,'msg'=>'操作错误','data'=>array());
    }

    /**
     * 服务活动，关闭活动
     * @param string $id 活动ID
     * @return array
     */
    function closeActivity($id=''){
        //将未审核申请设置为拒绝
        saveDataByCon("activityApply",array("status"=>"3"),array("activityId"=>$id,'status'=>'1'));
        //工作日志
        $actName = getField('activity',array('id'=>$id),'name');
        $logData = array(
            'etprsId'=>'0',
            'fmenuId'=>27,
            'smenuId'=>30,
            'objId'=>$id,
            'content'=>'关闭了活动:'.$actName
        );
        saveOaLog($logData);
        return saveData("Activity",array("id"=>$id,"close"=>1),"关闭活动");
    }

    /**
     * 会议室管理 通过申请或者拒绝申请
     * @param $id 会议室申请ID
     * @param $status 操作状态 1：通过 2：拒绝
     * @return array
     */
    function roomAplStat($id,$status)
    {
        if($status=="1"||$status=="2"){

            $msg=findById("OaMeetroomApl",array("id"=>$id),"roomId,etprsId,adduserId,startTime,endTime");

            if($msg["code"]=='1'&&!empty($msg["data"])){

                if($status ==1){
                    $mtrCon="id !=".$id." and roomId='".$msg["data"]["roomId"]."' and status=1 and ((startTime>='".$msg["data"]["startTime"]."' and startTime<'".$msg["data"]["endTime"]."') or (endTime>'".$msg["data"]["startTime"]."' and endTime<='".$msg["data"]["endTime"]."') or (startTime<='".$msg["data"]["startTime"]."' and endTime>='".$msg["data"]["endTime"]."'))";
                    $cmsg=findById("OaMeetroomApl",$mtrCon,"id");
                    if(!empty($cmsg["data"])){
                        return array("code"=>0,'msg'=>"与已申请记录时间冲突，请查看当前会议室申请记录",'data'=>array());
                    }
                }
                $mtrCon="id !=".$id." and roomId='".$msg["data"]["roomId"]."' and status=1 and ((startTime>='".$msg["data"]["startTime"]."' and startTime<'".$msg["data"]["endTime"]."') or (endTime>'".$msg["data"]["startTime"]."' and endTime<='".$msg["data"]["endTime"]."') or (startTime<='".$msg["data"]["startTime"]."' and endTime>='".$msg["data"]["endTime"]."'))";
                $cmsg=findById("OaMeetroomApl",$mtrCon,"id");
                if(!empty($cmsg["data"])){
                    return array("code"=>0,'msg'=>"与已申请记录时间冲突，请查看当前会议室申请记录",'data'=>array());
                }
                $roomapl=$msg["data"];
                $roomId=$roomapl["roomId"];
                $adduserId=$roomapl["adduserId"];
                //var_dump($roomapl);
                if(!empty($roomId)){
                    $roomname=getField("OaMeetroom",array("id"=>$roomId),'name');
                    $title="会议室:".$roomname." 申请".($status=="1"?"通过":"拒绝");
                    $content="会议室 ".$roomname."申请".($status=="1"?"通过":"拒绝")."，请及时查看。";
                    //发送消息
                    $emailData = array(
                        'type'=>'1020003',
                        'title'=>$title,
                        'content'=>$content,
                        'relTable'=>'oaMeetroomApl',
                        'relId'=>$id,
                    );
                    $wxData = array();
                    //微信通知
                    if(config('open_wechat')){
                        $template_id = config('wx_tpl.roomCheck');
                        $key2 = date("Y-m-d H:i",$roomapl['startTime']).'—'.date("Y-m-d H:i",$roomapl['endTime']);
                        $key3 = $status =="1"?'审核通过':'审核拒绝';
                        $wxData = array(
                            'tpl'=>$template_id,
                            'data'=>array(
                                'keyword1'=>$roomname,
                                'keyword2'=>$key2,
                                'keyword3'=>$key3,
                            ),
                            'first'=>'会议室审核通知',
                            'remark'=>'请登录系统查看详情'
                        );
                    }
                    $this->sendAllMsg($adduserId,$emailData,$wxData);
                    //工作日志
                    $logData = array(
                        'etprsId'=>$roomapl['etprsId'],
                        'fmenuId'=>27,
                        'smenuId'=>29,
                        'objId'=>$id,
                        'content'=>$title.',时间：'.date("Y-m-d H:i",$roomapl['startTime']).'—'.date("Y-m-d H:i",$roomapl['endTime'])
                    );
                    saveOaLog($logData);
                }

            }
        }
        return saveData("OaMeetroomApl",array("id"=>$id,'status'=>$status));
    }
}