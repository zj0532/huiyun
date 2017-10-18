<?php
namespace app\app\controller;
//use app\index\controller\Common;
use \think\Log;


class Index extends Appcommon
{

    //添加编辑日程计划（暂时没用到）
    function addEventSchedule($id='')
    {
        $schedule = array();
        if(!empty($id)){
            $msg=findById("UserSchedule",array("id"=>$id),"id,title as name,startTime,endTime,holeday,aim,remark,address,color,isend,timemark");
            if($msg["code"]==='1'){
                $schedule=$msg["data"];
                if(!empty($schedule["startTime"])){
                    $schedule["startTime"]=date("Y-m-d",$schedule["startTime"]);
                }
                if(!empty($schedule["endTime"])) {
                    $schedule["endTime"] = date("Y-m-d",$schedule["endTime"]);
                }
            }
        }
        return json(array('code'=>'1','msg'=>'','data'=>$schedule));
    }
    //保存日程计划
    function saveEventSchedule()
    {
        $postData = input("request.");
        if(isset($postData['token'])){
            unset($postData['token']);
        }
        $postData["addtime"]=time();
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData['holeday'] = 0;
        //有结束时间的类型
        $postData["isend"]=1;
        $postData["startTime"]=strtotime(rtrim($postData["startTime"]," "));
        $postData["endTime"]=strtotime(rtrim($postData["endTime"]," "))+86399;
        $postData["timemark"]=0;

        $res = saveData("userSchedule",$postData);
        if($res['code']==1){
            //添加一条消息通知
            //只在新增的时候添加
            if(!(isset($postData['id']) && !empty($postData['id']))){
                $emailData = array(
                    'type'=>'1020001',
                    'title'=>$postData['title'],
                    'content'=>'日程开始时间:'.date("Y-m-d H:i",$postData['startTime']).';日程描述：'.$postData['aim'],
                    'relTable'=>'userSchedule',
                    'relId'=>$res['data']
                );
                $uid = session('userId');
                $this->sendAllMsg($uid,$emailData);
            }
        }
        return json($res);
    }
    //初始化日程表
    function initEventSchedule()
    {
        $userId=session("userId");
        $userheader = getField('user',array('id'=>$userId),'userheader','0');
        if(!empty($userheader)){
            $logo = getField('sysFile',array('id'=>$userheader),'savePath');
        }else{
            $logo = '';
        }
        $msg=getDataList("UserSchedule",array("adduserId"=>$userId),"id,startTime,endTime,aim,title,address,addtime,holeday,isend,remark,color");
        if($msg["code"]==="1"){
            $schedule=array();
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $tmp["id"]=$msg["data"][$i]["id"];
                $tmp["title"]=$msg["data"][$i]["title"];
                $tmp['address'] = $msg['data'][$i]['address'];
                $tmp['aim'] = $msg['data'][$i]['aim'];
                $tmp["startTime"]=date("Y-m-d H:i",$msg["data"][$i]["startTime"]);
                $tmp["color"]=$msg["data"][$i]["color"];
                $tmp["endTime"]=date("Y-m-d H:i",$msg["data"][$i]["endTime"]);
                $tmp['logo'] = $logo;
                $schedule[]=$tmp;
            }
            return json(array('code'=>'1','msg'=>'','data'=>$schedule));
        }else{
            return json(array('code'=>'0','msg'=>'','data'=>array()));
        }
    }


}
