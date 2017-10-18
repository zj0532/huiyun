<?php
namespace app\service\controller;
use app\common\controller\Baseservice;
use think\Controller;
use app\index\controller\Common;
use think\Db;
use think\Exception;
use think\Log;


class Service extends Common{

    function getFaqs($type)
    {
        $con["a.iqbtId"]=session("user.iqbtId");
        $con["a.type"]=$type;
        $join = [['user b','a.adduserId=b.id',"left"]];
        $msg=getDataList("SysNotice",$con,"a.*,b.realname as username","a.addtime desc",$join);
        if($msg["code"]==="1"){
            return $msg["data"];
        }else{
            return array();
        }
    }

    function addFaq($type)
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("SysNotice",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c,'type'=>$type));
    }
    function saveFaq(){
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=date("Y-m-d",time());
        $table="SysNotice";
        return saveData($table,$postData,"添加/修改新闻资讯");
    }
    function deleteFaqs(){
        $id=input("id");
        return deleteData("SysNotice",$id,"删除新闻资讯");
    }

    function etprsFaq()
    {
        $con["a.type"]=2;
        $msg=getDataList("SysNotice",$con,"a.*","a.addtime desc");
        if($msg["code"]==="1"){
            return view("",array("faqs"=>$msg["data"]));
        }else{
            return view("",array("faqs"=>array()));
        }
    }


//会议室管理
    function meetrooms($key=""){
        $con=array('iqbtId'=>session("user.iqbtId"));
        if(!empty($key)){
            $con["name"]=array("like","%".$key."%");
        }
        $msg=getDataList("OaMeetroom",$con,"*");
        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $roomId=$msg["data"][$i]["id"];
                $cmsg=findById("OaMeetroomApl",array("iqbtId"=>session("user.iqbtId"),'status'=>0,'roomId'=>$roomId,'startTime'=>array('gt',time())),"count(id) as c");
                if($msg["code"]==='1'){
                    $msg["data"][$i]["c"]=$cmsg["data"]["c"];
                }
            }
            return view("",array("rooms"=>$msg["data"]));
        }else{
            return array();
        }
    }

    function etprsmeetrooms($key=""){
        $con=array('iqbtId'=>session("user.iqbtId"));
        if(!empty($key)){
            $con["name"]=array("like","%".$key."%");
        }
        $msg=getDataList("OaMeetroom",$con,"*");
        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $roomId=$msg["data"][$i]["id"];
                $cmsg=findById("OaMeetroomApl",array("iqbtId"=>session("user.iqbtId"),'status'=>0,'roomId'=>$roomId,'etprsId'=>session("etprsId"),'startTime'=>array('gt',time())),"count(id) as c");
                if($msg["code"]==='1'){
                    $msg["data"][$i]["c"]=$cmsg["data"]["c"];
                }
            }
            return view("",array("rooms"=>$msg["data"]));
        }else{
            return array();
        }
    }

    function meetroom($id='0')
    {
        return view("",array("roomId"=>$id));
    }
    function etprsMeetRoom($id='0')
    {
        return view("",array("roomId"=>$id));
    }

    function getRoomApl($roomId='')
    {
        $data=self::getAplRecord($roomId);
        $msg=findById("OaMeetroom",array("id"=>$roomId),"deviceDesc");
        if($msg["code"]==='1'){
            $data["deviceDesc"]=$msg["data"]["deviceDesc"];
        }else{
            $data["deviceDesc"]="";
        }
        return $data;
    }

    function getEtprsRoomApl($roomId='')
    {
        $etprsId=session("etprsId");
        $data=self::getAplRecord($roomId,$etprsId);
        $msg=findById("OaMeetroom",array("id"=>$roomId),"id,name,deviceDesc");
        if($msg["code"]==='1'){
            $data["deviceDesc"]=$msg["data"]["deviceDesc"];
            $data["id"]=$msg["data"]["id"];
            $data["name"]=$msg["data"]["name"];
        }else{
            $data["deviceDesc"]="";
        }
        return $data;
    }

    function aplDetail($aplId=0)
    {
        $join = [['enterprise b','a.etprsId=b.id'],['oa_meetroom c','a.roomId=c.id']];
        $msg=findById("OaMeetroomApl",array("a.id"=>$aplId),"a.*,b.name as etprsname,c.name,c.address",$join);
        $data=array();
        if(!empty($msg['data'])){
            $data=$msg["data"];
        }
        return view("",array("data"=>$data));
    }

    function aplRcdDetail($aplId=0)
    {
        $join = [['enterprise b','a.etprsId=b.id'],['oa_meetroom c','a.roomId=c.id']];
        $msg=findById("OaMeetroomApl",array("a.id"=>$aplId),"a.*,b.name as etprsname,c.name,c.address",$join);
        $data=array();
        if(!empty($msg['data'])){
            $data=$msg["data"];
        }
        return view("",array("data"=>$data));
    }

    //会议室管理
    function getMeetroom()
    {
        $con=array('iqbtId'=>session("user.iqbtId"));
        $msg=getDataList("OaMeetroom",$con,"*");
        if($msg["code"]==="1"){
            /*$tmplist=self::getDictStr("*","OaMeetroom");
            $msg['data']=$this->setListIdText($msg['data'],$tmplist);*/
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $msg["data"][$i]["apl"]=self::getAplRecord($msg["data"][$i]["id"]);
                $msg['data'][$i]['unhandle'] = count($msg['data'][$i]['apl']['apls']);

            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addMeetroom()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("OaMeetroom",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                if(!empty($msg['data'])){
                    if(!empty($msg['data']['banner'])){
                        $picArr = explode(',',$msg['data']['banner']);
                        $savepath=getField("sysFile",array("id"=>$picArr[0]),"savePath");
                    }else{
                        $savepath = '';
                    }
                    $msg["data"]["savePath"]=$savepath;
                }
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveMeetroom()
    {
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        /*$postData["etprsId"]=session("user.etprsId");*/
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="OaMeetroom";
        $res = saveData($table,$postData,"添加/修改会议室信息");
        if($res['code']==1){
            if(!isset($postData['id'])|| empty($postData['id'])){
                //只在添加的时候做工作日志
                $logData = array(
                    'etprsId'=>0,
                    'fmenuId'=>27,
                    'smenuId'=>29,
                    'objId'=>$res['data'],
                    'content'=>'添加会议室：'.$postData['name'],
                );
                saveOaLog($logData);
            }
        }
        return $res;
    }
    function deleteMeetroom(){
        $id=input("id",0);
        //如果当前会议室已经有申请记录了，则不允许删除
        $roomAplMsg = getDataList('OaMeetroomApl',array('iqbtId'=>session('iqbtId'),'roomId'=>$id),'id');
        if($roomAplMsg['code']==1 && !empty($roomAplMsg['data'])){
            return array('code'=>0,'msg'=>'当前会议室已经被使用了，不能删除。');
        }
        return deleteData("OaMeetroom",$id,"删除会议室信息");
    }

    //申请会议室
    function addMeetroomApl($roomId,$name)
    {
        return view("",array("roomId"=>$roomId,"roomname"=>$name));
    }
    function reApl($id)
    {
        $msg=findById("OaMeetroomApl",array("id"=>$id),"*");
        if($msg["code"]==='1'){
            $roomId=$msg["data"]["roomId"];
            $name=getField("OaMeetroom",array("id"=>$roomId),"name");
            return view("addMeetroomApl",array("data"=>$msg["data"],"roomId"=>$roomId,"roomname"=>$name));
        }else{
            return null;
        }
    }

    /**
     * 会议室管理，申请处理，通过或者拒绝
     * @param $id 申请ID
     * @param string $status 状态：1：通过 2 拒绝
     * @return array
     */
    function roomAplStat($id,$status='')
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'申请ID不能为空','data'=>array());
        }
        $base = new Baseservice();
        $res = $base->roomAplStat($id,$status);
        return $res;
    }
    function saveMeetroomApl()
    {
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["etprsId"]=session("user.etprsId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();

        $postData["startTime"]=strtotime($postData["startTime"]);
        $postData["endTime"]=strtotime($postData["endTime"]);
        if((int)($postData["endTime"])<=(int)($postData["startTime"])){
            return returnResult("global_info","global_endtime_error");
        }
        //判断使用已经在使用
        $table="OaMeetroomApl";
        $mtrCon="";
        if(!empty($postData["id"])){
            $mtrCon="id !=".$postData["id"]." and ";
        }
        $mtrCon=$mtrCon."roomId='".$postData["roomId"]."' and status=1 and ((startTime>='".$postData["startTime"]."' and startTime<'".$postData["endTime"]."') or (endTime>'".$postData["startTime"]."' and endTime<='".$postData["endTime"]."') or (startTime<='".$postData["startTime"]."' and endTime>='".$postData["endTime"]."'))";
        //echo $mtrCon;die();
        $msg=findById($table,$mtrCon,"id");
        if(!empty($msg["data"])){
            return array("code"=>0,'msg'=>"与已申请记录时间冲突，请查看当前会议室申请记录");
        }


        $res = saveData($table,$postData,"添加/修改会议室申请");
        if($res['code']==1){
            //给管理员发通知
            $optIds = getAdminIds('29',false);
            $etprsName = getField('enterprise',array('id'=>session('user.etprsId')),'name');
            $roomName = getField('oaMeetroom',array('id'=>$postData['roomId']),'name');
            $emailData = array(
                'title'=>'会议室申请',
                'content'=>'您好，'.$etprsName.' 申请使用'.$roomName.'，请登录系统及时审核处理',
                'type'=>'1020003',
                'relTable'=>'oaMeetroomApl',
                'relId'=>$res['data']
            );
            $wxData = array();
            //发送微信消息
            if(config('open_wechat')){
                $template_id = config('wx_tpl.roomApply');
                $time = '开始时间:'.date("Y-m-d H:i",$postData['startTime']).',结束时间：'.date("Y-m-d H:i",$postData['endTime']);
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$etprsName,
                        'keyword2'=>$roomName,
                        'keyword3'=>$time,
                    ),
                    'first'=>'会议室申请通知',
                    'remark'=>'请登录系统，及时查看处理'
                );
            }
            $this->sendAllMsg($optIds,$emailData,$wxData);
        }
        return $res;
    }

    function getAplRecord($roomId,$fetprsId='')

    {
        $userCate=session("user.userCate");
        $con=array('a.iqbtId'=>session("iqbtId"),"a.roomId"=>$roomId);
        $etprsId=0;
        if($userCate=="1011002"){//如果是企业用户
            $etprsId=session("user.etprsId");
            //$con["a.etprsId"]=$etprsId;
        }
        $join = [['enterprise b','a.etprsId=b.id']];

        if(!empty($fetprsId)){
            $con["a.etprsId"]=$fetprsId;
        }
        $msg=getDataList("OaMeetroomApl",$con,"a.*,b.name","a.startTime desc",$join);
        $apls=array();
        $pass=array();
        $history=array();
        if($msg["code"]==='1'){
            $records=$msg["data"];
            foreach ($records as $apl) {
                $now=time();
                $status=$apl["status"];
                switch ($status){
                    case "0":
                        if($apl["startTime"]>$now){
                            $apl["statusText"]="已申请";
                        }else{
                            $apl["statusText"]="已过期";
                        }
                        break;
                    case "1":
                        $apl["statusText"]="已通过";
                        break;
                    case "2":
                        $apl["statusText"]="未通过";
                        break;

                }
                $apl["startEnd"]=date("Y-m-d H:i",$apl["startTime"])." - ".date("Y-m-d H:i",$apl["endTime"]);
                //该企业对该会议室历史使用次数
                $htynum = 0;
                $map = array(
                    'roomId'=>$roomId,
                    'etprsId'=>$apl['etprsId'],
                    'status'=>1,
                    'iqbtId'=>session('iqbtId')
                );
                $htyMsg = getDataList('OaMeetroomApl',$map,'id');
                if($htyMsg['code']==1 && !empty($htyMsg['data'])){
                    $htynum = count($htyMsg['data']);
                }
                $apl['htynum'] = $htynum;

                if ($userCate=="1011001"&&$apl["startTime"]>$now&&$status=='0') {
                    //开始使用时间大于当前时间，未审核的/未通过审核的 申请记录的申请 $apls
                    $apls[]=$apl;
                }else if($userCate=="1011002"&&$apl["etprsId"]==$etprsId&&$apl["startTime"]>$now&&($status=='2'||$status=='0')){
                    $apls[]=$apl;
                } else  if ($apl["startTime"]>$now&&$status=='1'){
                    //开始时间大于当前时间，已通过审核的申请记录 $pass
                    $pass[]=$apl;
                }else  if ($userCate=="1011001"&&$apl["startTime"]<$now){
                    //管理员 ，开始时间小于当前时间，已经审核 status==1 $history
                    $history[]=$apl;
                }else if($userCate=="1011002"&&$apl["etprsId"]==$etprsId&&$apl["startTime"]<$now){
                    //企业，开始时间小于当前时间，所有 $history
                    $history[]=$apl;
                }
            }
        }
        return array('apls'=>$apls,'pass'=>$pass,'history'=>$history);

    }
    //公共服务活动。第一期直接添加活动，不做申请审批。简化流程，后面根据需要增加


    function getActivity($key="")
    {
        $con=array("a.iqbtId"=>session("iqbtId"));
        if(!empty($key)){
            $con["a.name"]=array("like","%".$key."%");
        }
        $join = [['user b','a.appliUserId=b.id']];
        $msg=getDataList("activity",$con,"a.id,a.name,a.startTime,a.endTime,a.desc,a.budget,a.appliUserId,a.status,a.close,b.name as username","a.close asc",$join);

        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $msg["data"][$i]["startTime"] = date("Y-m-d H:i", $msg["data"][$i]["startTime"]);
                $msg["data"][$i]["endTime"] = date("Y-m-d H:i", $msg["data"][$i]["endTime"]);
                //申请
                $actId = $msg["data"][$i]["id"];

                $unhandle = 0;

                $join2 = [['enterprise b', 'a.etprsId=b.id', "left"]];
                $aplmsg = getDataList("activityApply", array("a.activityId" => $actId), "a.id,a.contact,a.mobile,a.position,a.number,a.addtime,a.etprsId,b.name as etprsName,a.status", "a.addtime", $join2);
                if ($aplmsg["code"] === "1" && !empty($aplmsg["data"])) {
                    for ($j = 0; $j < count($aplmsg["data"]); $j++) {
                        $aplmsg["data"][$j]["addtime"] = date("Y-m-d H:i", $aplmsg["data"][$j]["addtime"]);

                        if ($aplmsg['data'][$j]['status'] == 1) {
                            $unhandle += 1;
                        }

                    }
                    $msg["data"][$i]["apls"] = json_encode($aplmsg["data"]);
                } else {
                    $msg["data"][$i]["apls"] = json_encode(array());
                }

                $msg['data'][$i]['unhandle'] = $unhandle;
            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    function getEtprsActivity()
    {
        $userId=session("userId");
        $join = [['activity_apply b','a.id=b.activityId and b.adduserId='.$userId,"left"]];
        $msg=getDataList("activity",array("a.iqbtId"=>session("iqbtId"),'a.endTime'=>array("gt",time())),"a.id,a.name,a.startTime,a.endTime,a.desc,a.budget,a.appliUserId,a.status,a.close,b.status as aplstatus","a.close asc",$join);
        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $msg["data"][$i]["startTime"]=date("Y-m-d H:i",$msg["data"][$i]["startTime"]);
                $msg["data"][$i]["endTime"]=date("Y-m-d H:i",$msg["data"][$i]["endTime"]);
            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addActivity($id=0)
    {
        if(empty($id)){
            return view("");
        }else{
            $msg=findById("activity",array("id"=>$id));
            if($msg["code"]==="1"){
                if(!empty($msg['data'])){
                    if(!empty($msg['data']['banner'])){
                        $picArr = explode(',',$msg['data']['banner']);
                        $savepath=getField("sysFile",array("id"=>$picArr[0]),"savePath");
                    }else{
                        $savepath = '';
                    }
                    $msg["data"]["savePath"]=$savepath;
                    $actvt=$msg["data"];
                }

            }
            return view("",array("data"=>$actvt));
        }
    }
    function saveActivity()
    {
        $sms = 0;
        $postData=input("request.");
        if(isset($postData['id'])&&!empty($postData['id'])){
            //如果是编辑，需要判断一下是否已经有申请的，如果有，则不允许编辑
            $actApl=findById("ActivityApply",array("activityId"=>$postData['id']),'id');
            if($actApl['code']==1 && !empty($actApl['data'])){
                return array('code'=>0,'msg'=>'活动已经开始，且有企业已经申请，不允许编辑');
            }

        }
        $postData["startTime"]=strtotime($postData["startTime"]);
        $postData["endTime"]=strtotime($postData["endTime"]);
        $postData["appliUserId"]=session("userId");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("iqbtId");
        $table="Activity";
        if(isset($postData['sms'])){
            $sms = $postData['sms'];
            unset($postData['sms']);
        }
        $msg= saveData($table,$postData,"保存活动");
        if($msg['code']==1){

            if(!isset($postData['id']) ||empty($postData['id'])){
                //只在新增的时候发消息，编辑不再发送
                //发送消息
                //获取所有的入驻用户userId
                $join = [['enterprise b','b.id=a.etprsId']];
                $con = array(
                    'a.iqbtId'=>session('iqbtId'),
                    'a.userCate'=>'1011002',
                    'b.status'=>'1001016'
                );
                $userIds =getFieldArrry('user',$con,'a.id','',$join);
                $smsData = array();
                $emailData = array(
                    'type'=>'1020005',
                    'title'=>'活动通知',
                    'content'=>'管理员发布了新的活动：'.$postData['name'].',报名时间为：'.date("Y-m-d H:i", $postData['startTime']) . '—' . date("Y-m-d H:i", $postData['endTime']),
                    'relTable'=>'acitvity',
                    'relId'=>$msg['data'],
                );
                if($sms ==1) {
                    $data = array(
                        'name' => $postData['name'],
                        'time' => date("Y-m-d H:i", $postData['startTime']) . '—' . date("Y-m-d H:i", $postData['endTime']),
                    );
                    $tpl = config('sms_tpl_id.activity');
                    $smsData = array(
                        'tpl' => $tpl,
                        'data' => $data
                    );
                }
                $this->sendAllMsg($userIds,$emailData,array(),$smsData);
                //工作日志
                $logData = array(
                    'etprsId'=>'0',
                    'fmenuId'=>27,
                    'smenuId'=>30,
                    'objId'=>$msg['data'],
                    'content'=>'发布新活动:'.$postData['name']
                );
                saveOaLog($logData);
            }

        }

        return $msg;
    }

    //添加申请活动信息
    function addAplActivity($id=0)
    {

        $name = getField('activity',array('id'=>$id),'name');
        return view("", array('activityId' => $id,'name'=>$name));
    }

    //申请
    function aplActivity()
    {
        $postData = input('request.');
        $activityName = $postData['activityName'];
        unset($postData['activityName']);
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["etprsId"]=session("user.etprsId");
        $postData["adduserId"]=session("userId");
        $table="ActivityApply";
        $msg= saveData($table,$postData,"保存活动");
        if($msg["code"]==='1') {
            $etprsName = getField('enterprise', array('id' => session("user.etprsId")), 'name');
            $wxData = array();
            $emailData = array(
                'title' => '活动报名通知',
                'content' => '您好，' . $etprsName . '报名参加活动:'.$activityName.'，请登录系统及时回复处理',
                'type' => '1020005',
                'relTable'=>$table,
                'relId'=>$msg['data'],
            );
            if(config('open_wehchat')){
                //给管理员发送微信通知
                $template_id = config('wx_tpl.activityApply');
                $first = "您好，有公司申请参加活动：".$activityName;
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$postData['contact'],
                        'keyword2'=>$etprsName,
                        'keyowrd3'=>$postData['position'],
                        'keyword4'=>$postData['mobile'],
                    ),
                    'first'=>$first,
                    'remark'=>'请登录系统，尽快查看处理'
                );

            }
            $optIds = getAdminIds('30', false);
            $this->sendAllMsg($optIds,$emailData,$wxData);
        }
        return $msg;
    }

    /**服务活动，活动审核操作，通过或者拒绝
     * @param $id 申请ID
     * @param $status 状态 2：通过， 3：拒绝
     * @return array
     */
    function aplStatus($id,$status='')
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'申请ID不能为空','data'=>array());
        }
        $base = new Baseservice();
        $res = $base->aplStatus($id,$status);
        return $res;
    }
    function deleteActivity(){
        $id=input("id");
        $actApl=findById("ActivityApply",array("activityId"=>$id),'id');
        if($actApl['code']==1 && !empty($actApl['data'])){
            return array('code'=>0,'msg'=>'活动已经开始，且有企业已经申请，不允许删除');
        }
        return deleteData("Activity",$id,"删除活动");
    }

    //服务活动，关闭活动
    function closeActivity(){
        $id=input("id");
        if(empty($id)){
            return array('code'=>'0','msg'=>'参数不能为空','data'=>array());
        }
        $base = new Baseservice();
        $res = $base->closeActivity($id);
        return $res;
    }

    function recordActivity($id)
    {
        if(empty($id)){
            return view("");
        }else{
            $msg=findById("activity",array("id"=>$id));
            if($msg["code"]==="1"){
                $actvt=$msg["data"];
            }
            return view("",array("data"=>$actvt));
        }
    }
    function saveRecordActivity($id)
    {
        $postData=input("request.");
        $table="Activity";
        $msg= saveData($table,$postData,"保存活动");
        return $msg;
    }

    //工作日志
    function getLogs()
    {
        $con=array('a.iqbtId'=>session("user.iqbtId"),'a.adduserId'=>session('userId'));
        $join = [['user b','a.adduserId=b.id',"left"]];
        $msg=getDataList("OaWorklog",$con,"a.*,b.realname as name","a.addtime desc",$join);
        if($msg["code"]==="1"){
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addLog()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("OaWorklog",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        ;
        return view("",array("data"=>$c));
    }
    function saveLog(){
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="OaWorklog";
        return saveData($table,$postData,"添加/修改工作日志");
    }
    function deleteLog(){
        $id=input("id");
        return deleteData("OaWorklog",$id,"删除工作日志");
    }

    function detail($table,$id)
    {
        $field="title,content";
        if($table=="activity"){
            $field="`name` as title,`desc` as content";
        }
        $msg=findById($table,array("id"=>$id),$field);
        return view("",array("data"=>$msg["data"]));
    }

    function etprslist(){
        //数量查询
        $numlist = array();
        $numlist['total'] = db('enterprise')->where(array('isDelete'=>0,'status'=>array('not in',['1001000','1001001','1001002','1001003']),'iqbtId'=>session("iqbtId")))->count('id');//全部企业个数
        $numlist['enter'] = db('enterprise')->where(array('isDelete'=>0,'status'=>'1001016','iqbtId'=>session("iqbtId")))->count();//在孵
        $numlist['apply'] = db('enterprise')->where(array('isDelete'=>0,'status'=>array("in","1001011,1001012,1001013,1001014,1001015"),'iqbtId'=>session("iqbtId")))->count();//申请中#
        $numlist['finish']=db('enterprise')->where(array('isDelete'=>0,'status'=>'1001017','iqbtId'=>session("iqbtId")))->count();//毕业
        $numlist['over'] = db('enterprise')->where(array('isDelete'=>0,'status'=>array("in","1001020,1001021"),'iqbtId'=>session("iqbtId")))->count();//强制退出
        return view("",array('numlist'=>$numlist));
    }

    function getEtprsList()
    {
        $postData = input("request.");
        $sql="select a.id,a.`name` as etprsName,a.apltype,a.contact,a.mobile,b.industry,b.etprsId,a.tutorIds,b.total from ibt_enterprise as a left join ibt_etprs_info as b on a.id=b.etprsId where ";
       // $con2['a.iqbtId'] = session('iqbtId');
        $sql=$sql."a.iqbtId=".session("iqbtId");
        $sql = $sql." and a.isDelete=0 ";
        $schflag=isset($postData["schflag"])?$postData["schflag"]:"all";

        switch ($schflag){
            case "ing":
                //$con2["a.status"]="1001016";
                $sql=$sql." and a.status=1001016";
                break;
            case "apl":
                $sql=$sql." and a.status in ('1001011','1001012','1001013','1001014','1001015')";
                //$con2["a.status"]=array("in","1001011,1001012,1001013,1001014,1001015");;
                break;
            case "gradt":
                $con2["a.status"]="1001017";
                $sql=$sql." and a.status=1001017";
                break;
            case "quit":
                $sql=$sql." and a.status in ('1001021','1001020')";
                //$con2["a.status"]="1001000";
                break;
            default:
                $sql=$sql." and a.status in ('1001021','1001020','1001011','1001012','1001013','1001014','1001015','1001016','1001017')";
                break;
        }
        //企业名称
        if(isset($postData['name'])&&$postData['name'] !=""){
         //   $con2['a.name'] = array("like","%".$postData['name']."%");
            $sql=$sql." and a.name like '%".$postData['name']."%'";
        }
        //申请类型
        if(isset($postData['apltype'])&&$postData['apltype'] !=""){
         //   $con2['a.apltype'] = $postData['apltype'];
            $sql=$sql." and a.apltype = ".$postData['apltype'];
        }
        //成立时间
        if(isset($postData['rgst_start'])&&!empty($postData['rgst_start'])&&isset($postData['rgst_end'])&&!empty($postData['rgst_end'])){
         //   $con2['a.rgsttime'] = array('between time',[$postData['rgst_start'],$postData['rgst_end']]);
            $sql=$sql." and unix_timestamp(a.rgsttime) between  unix_timestamp('".$postData['rgst_start']."') and unix_timestamp('".$postData['rgst_end']."')";
        }

        //入驻时间
        if(isset($postData['enter_start'])&&!empty($postData['enter_start'])&&isset($postData['enter_end'])&&!empty($postData['enter_end'])){
         //   $con2['a.entertime'] = array('between time',[$postData['rgst_start'],$postData['rgst_end']]);
            $sql=$sql." and a.entertime between  unix_timestamp('".$postData['enter_start']."') and unix_timestamp('".$postData['enter_end']."')";
        }
        //到期时间
        if(isset($postData['quit_start'])&&!empty($postData['quit_start'])&&isset($postData['quit_end'])&&!empty($postData['quit_end'])){
            //   $con2['a.entertime'] = array('between time',[$postData['rgst_start'],$postData['rgst_end']]);
            $sql=$sql." and a.pactquittime between  unix_timestamp('".$postData['quit_start']."') and unix_timestamp('".$postData['quit_end']."')";
        }
        //行业类型
        if(isset($postData['industry'])&&$postData['industry']!=""){
         //   $con2['b.industry'] = $postData['industry'];
            $sql=$sql." and b.industry = ".$postData['industry'];
        }
        //技术领域
        if(isset($postData['technical'])&&$postData['technical']!=""){
        //    $con2['b.technical'] = $postData['technical'];
            $sql=$sql." and b.technical = ".$postData['technical'];
        }
        //人员情况
        if(!empty($postData['people'])&&!empty($postData['peoplenum'])){
            $key = 'b.'.$postData['people'];
            switch($postData['peoplenum']){
                case 1:
               //     $con2[$key] = array('elt','5');
                    $sql=$sql." and ".$key." <5 ";
                    break;
                case 2:
               //     $con2[$key] = array('between','6,10');
                    $sql=$sql." and ".$key." between 6 and 10 ";
                    break;
                case 3:
                //    $con2[$key] = array('between','10,20');
                    $sql=$sql." and ".$key." between 11 and 20 ";
                    break;
                case 4:
               //     $con2[$key] = array('between','20,50');
                    $sql=$sql." and ".$key." between 21 and 60 ";
                    break;
                case 5:
               //     $con2[$key] = array('egt','50');
                    $sql=$sql." and ".$key." >50";
                    break;
                default:break;
            }
        }

        //知识产权
        if(isset($postData['iprapl']) && $postData['iprapl']!=""){
            $key = 'b.iprapl';
            switch($postData['iprapl']){
                case 1:
               //     $con2[$key] = array('elt','5');
                    $sql=$sql." and ".$key." <5 ";
                    break;
                case 2:
                 //   $con2[$key] = array('between','6,10');
                    $sql=$sql." and ".$key." between 6 and 10 ";
                    break;
                case 3:
                //    $con2[$key] = array('between','10,20');
                    $sql=$sql." and ".$key." between 11 and 20 ";
                    break;
                case 4:
                  //  $con2[$key] = array('between','20,50');
                    $sql=$sql." and ".$key." between 21 and 60 ";
                    break;
                case 5:
                 //   $con2[$key] = array('egt','50');
                    $sql=$sql." and ".$key." >50";
                    break;
                default:break;
            }
        }
        //导师
        if(isset($postData['tutorIds'])&& $postData['tutorIds']!=""){
            //$con2["CONCAT(',','a.tutorIds',',')"] = array('like',"%,".$postData['tutor'].",%");
            $tutorsql="";
            foreach ($postData['tutorIds'] as $tutorId) {
                $tutorsql=$tutorsql." or CONCAT(',',a.tutorIds,',') like '%,".$tutorId.",%'";
            }
            $tutorsql=substr($tutorsql,3).")";
            if(!empty($tutorsql)){
                $sql=$sql." and (".$tutorsql;
            }

        }
        //是否高新企业
        if(isset($postData['highetprs'])&& $postData['highetprs']!=""){
            $sql=$sql." and b.highetprs = ".$postData['highetprs'];
        }
        //是否欠费#todo
        $feeids="";
        if(isset($postData['fee_status'])){
            $feecon=array("iqbtId"=>session("iqbtId"),'status'=>0);
            $feemsg=getDataList("fee_rcd",$feecon," distinct etprsId as etprsId");
            if(!empty($feemsg["data"])){
                foreach ($feemsg["data"] as $fee) {
                    $feeids=$feeids.",".$fee["etprsId"];
                }
                $feeids=trim($feeids,",");
            }
            if(empty($postData['fee_status'])){
                //欠费
                if(!empty($feeids)){
                    $sql =$sql." and a.id in(".$feeids.")";
                }
            }else{
                //不欠费
                if(!empty($feeids)){
                    $sql =$sql." and a.id not in(".$feeids.")";
                }
            }

        }

        //分配房间
        $roomids="";
        if(!empty($postData['build'])||!empty($postData['floor'])||!empty($postData['roomNo'])){
            if(isset($postData['build'])&& $postData['build']!=""){
                //房间表
                $map['buildId'] = $postData['build'];
            }
            if(isset($postData['floor']) && $postData['floor']!=""){
                $map['floor'] = $postData['floor'];
            }
            if(isset($postData['roomNo'])&&!empty($postData['roomNo'])){
                $roomNo=getField("EstateRoom",array("id"=>$postData['roomNo']),"roomNo");
                //$map['roomNo'] = array('like','%'.$roomNo.'%');
                $map['roomNo'] = $roomNo;
            }
            if(!empty($map)){
                $etprsIds = getFieldArrry('EstateRoom',$map,'etprsId');
                if(!empty($etprsIds)){
                    $roomids = array_unique($etprsIds);
                }
            }
            if(empty($roomids)){
                $roomids=[0];
            }
            $sql =$sql." and a.id in(".join(",",$roomids).")";;
        }
        //Log::notice($sql);
        $etprsapl=Db::query($sql);
        if(!empty($etprsapl)){
            $tmplist=self::getDictStr("*","EtprsApl");
            $etprsapl=$this->setListIdText($etprsapl,$tmplist);
        }
        for ($i = 0; $i < count($etprsapl); $i++) {
            $etprsId=$etprsapl[$i]["id"];
            $roomNos="";
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.="，".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,"，");
            $etprsapl[$i]["roomNos"]=$roomNos;

            $tutorIds=$etprsapl[$i]["tutorIds"];
            if(!empty($tutorIds)){
                $tutors="";
                 $tmsg=getDataList("tutor",array("id"=>array("in",$tutorIds)),"name");
                if(!empty($tmsg["data"])){
                    $tutors=$tutors.",".$tmsg["data"][0]["name"];
                }
                $tutors=trim($tutors,",");
                $etprsapl[$i]["tutors"]=$tutors;
            }else{
                $etprsapl[$i]["tutors"]="";
            }

            $endmsg=findById("estateRoomEtprs",array("etprsId"=>$etprsId),"max(endTime) as endTime");
            if(!empty($endmsg["data"]["endTime"])){
                $etprsapl[$i]["endTime"]=date("Y-m-d",$endmsg["data"]["endTime"]);
            }else{
                $etprsapl[$i]["endTime"]="-";
            }
        }

        return $etprsapl;
    }

    //房间申请记录
    function getEtprsRoomLog($etprsId=''){
        if(empty($etprsId)){
            $etprsId = session('etprsId');
        }
        $con = array(
            'a.iqbtId'=>session('iqbtId'),
            'a.etprsId'=>$etprsId,
        );
        $join = [['estateRoom b','b.id=a.roomId'],'left'];
        $msg=getDataList("estateRoomEtprs",$con,"a.*,b.type,b.floor,b.roomNo,b.totalarea,b.buildId","a.roomId desc",$join);
      //  print_r($msg);exit();
        $data=$msg["data"];
        for ($i = 0; $i < count($data); $i++) {
            $type = $data[$i]['type'];
            if($type ==1){
                $data[$i]['typeStr'] = "办公室";
            }else{
                $data[$i]['typeStr'] = "工&nbsp;&nbsp;&nbsp;&nbsp;位";
            }
            $status=$data[$i]["status"];
            if($status=="2"){
                $data[$i]['statusStr'] = '已结束';
            }elseif($status =='1'){
                $data[$i]['statusStr'] = '正在使用';
            }elseif($status=='0'){
                $data[$i]['statusStr'] = '已分配未缴费';
            }else{
                $data[$i]['statusStr'] = '未知状态';
            }
            $data[$i]['buildName'] = getField('estateBuilding',array('id'=>$data[$i]['buildId']),'name','');
            $data[$i]['startTime'] = date("Y-m-d",$data[$i]['startTime']);
            $data[$i]['endTime'] = date("Y-m-d",$data[$i]['endTime']);
            $data[$i]['floor'] = $data[$i]['floor'].'层';
        }
      //  print_r($data);exit();
        return $data;
    }


    function etprsInfo($etprsId=0)
    {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }
        //基本信息
        $base=array();
        $msg=findById("enterprise",array("a.id"=>$etprsId),"a.*");
        if(!empty($msg["data"])){
            $base=$msg["data"];
            $tmplist=self::getDictStr("*","Enterprise");
            $base=$this->setObjIdText($base,$tmplist);

            $roomNos="";
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.=",".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,",");
            $base["roomNos"]=$roomNos;

            $tmsg=getFieldArrry("tutor",array("id"=>array("in",$base["tutorIds"])),"name");
            if(!empty($tmsg)){
                $base["tutors"]=join(",",$tmsg);
            }else{
                $base["tutors"]="";
            }
        }

        //扩展信息
        $extendInfo = array();
        $exMsg = findByid('etprsInfo',array('a.etprsId'=>$etprsId),'a.*');
        if(!empty($exMsg['data'])){
          //  $extendInfo = $exMsg['data'];
            // $tmplist1=self::getDictStr("*","EtprsInfo");
            //  $extendInfo=$this->setObjIdText($extendInfo,$tmplist1);

            $extendInfo=$exMsg["data"];
            $tmplist=self::getDictStr("*","EtprsInfo");
            $extendInfo=$this->setObjIdText($extendInfo,$tmplist);

        }
      //   header("Content-Type: text/html;charset=utf-8");
     //      print_r($extendInfo);exit();
        //入孵申请信息
        $data=array();
        if(empty($id)){
            $amsg=findById("etprsApl",array("etprsId"=>$etprsId),"id",array());
            if(!empty($amsg["data"])){
                $id=$amsg["data"]["id"];
            }
        }
        if(!empty($id)){
            $join = [['enterprise b','a.etprsId=b.id',"left"]];
            $msg=findById("etprsApl",array("a.id"=>$id),"b.name as etprsname,b.batch,b.status,a.*",$join);
            if($msg["code"]==='1'){
                $data=$msg["data"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $data=$this->setObjIdText($data,$tmplist);
            }
            $roomNos="";
            $etprsId=$data["etprsId"];
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.=",".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,",");
            $data["roomNos"]=$roomNos;
        }

        $type=$data["type"];
        //申请记录

        //缴费记录

        //合同记录

        return view("",array("base"=>$base,"apl"=>$data,"etprsId"=>$etprsId,'exData'=>$extendInfo,"type"=>$type));

    }

    //企业信息维护
    function updateInfo($etprsId=0){
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }

        $etprsInfo=findById("EtprsInfo",array("etprsId"=>$etprsId),"*");
        if(!empty($etprsInfo["data"])){
            $data=$etprsInfo["data"];
            if(!empty($data['charter'])){
                $data['savePath'] = getField('sysFile',array('id'=>$data['charter']),'savePath');
            }else{
                $data['savePath'] = '';
            }
        }else{
            $data = array();
        }
        $baseInfo = findById('enterprise',array('id'=>$etprsId),"*");
        if($baseInfo['code']==1 && !empty($baseInfo['data'])){
            if(empty($baseInfo['data']['rgsttime'])){
                //注册时间为空，说明是团队申请，且没有注册公司维护信息
                $data['etprsname'] = '';
                $data['rgsttime'] = date("Y-m-d",time());
            }else{
                $data['etprsname'] = $baseInfo['data']['name'];
                $data['rgsttime'] = $baseInfo['data']['rgsttime'];
            }
        }
        return view("",array('data'=>$data,'etprsId'=>$etprsId));
    }

    function saveUpdateInfo(){
        $postData=input("request.");
        if(!isset($postData['etprsId']) || empty($postData['etprsId'])){
            $postData['etprsId'] =session("etprsId");
        }
        $base['id'] = $postData['etprsId'];
        $base['total'] = $postData['total'];
        if(isset($postData['etprsname'])){
            $base['name'] = $postData['etprsname'];
            unset($postData['etprsname']);
        }
        if(isset($postData['rgsttime'])){
            $base['rgsttime'] = $postData['rgsttime'];
            unset($postData['rgsttime']);
        }
        saveData('enterprise',$base,'维护企业信息');
        $msg= saveData("EtprsInfo",$postData,"维护企业信息");
        return $msg;
    }


    //企业端新增报表
    function addState(){
        $etprsId = session('etprsId');
        $join = [['etprs_info b','a.id=b.etprsId',"left"]];
        $map = array('a.id'=>$etprsId);
        $msg = findById('enterprise',$map,"a.id,a.name,a.contact,a.mobile,b.total,b.doctor,b.thousand,b.student,b.highetprs",$join);
        if($msg['code']==1){
            $data = $msg['data'];
        }else{
            $data = array();
        }
        return view("",array('data'=>$data));
    }
    //企业端获取月报表列表
    function getStateList(){
        $etprsId = session('etprsId');
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg = getDataList('statement',array('a.etprsId'=>$etprsId),'a.*,b.name as etprsname',"a.month desc",$join);
        if($msg['code'] ==1){
            $data = $msg['data'];
        }else {
            $data = array();
        }
        return $data;
    }
    function saveState(){
        $postData=input("request.");
        if(!empty($postData['month'])){
            $date = explode("-",$postData['month']);
            $year = intval($date[0]);
            $months = intval($date[1]);
            $quarter = intval(ceil($months/3));

        }else{
            return array('code'=>0,'msg'=>'请选择报表的月份');
        }
        //查询当前企业当前月份是否填报
        $con = array(
            'iqbtId'=>session('iqbtId'),
            'etprsId'=>session('etprsId'),
            'years'=>$year,
            'months'=>$months
        );
        $msg = findById('statement',$con,'id');
        if($msg['code']==1 && !empty($msg['data'])){
            return array('code'=>0,'msg'=>'当前月份的报表已经上报，不能重复填报');
        }
        //把信息更新到etrps_info表里
        $data = array();
        $data['total'] = $postData['total'];
        $data['doctor'] = $postData['doctor'];
        $data['thousand'] = $postData['thousand'];
        $data['student'] = $postData['student'];
        $data['highetprs'] = $postData['highetprs'];
        $etprsId = $postData['etprsId'];
        saveDataByCon('EtprsInfo',$data,array('etprsId'=>$etprsId));
        $postData['iqbtId'] = session('iqbtId');
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $postData['hightime'] = strtotime($postData['hightime']);
        $postData['years'] = $year;
        $postData['quarter'] = $quarter;
        $postData['months'] = $months;
        $table="statement";
        return saveData($table,$postData,"添加月报表");
    }

    function stateDetail($id=""){
        if(!empty($id)){
            $msg = findById('statement',array('id'=>$id),'a.*');
            if($msg['code'] ==1){
                $data = $msg['data'];
            }else{
                $data = array();
            }
            return view('',array('data'=>$data));
        }
    }

    //管理员端获取月报表列表
    function getMonthList(){
        $iqbtId = session('iqbtId');
        $status = "1001016";
        $name = input('name');
        $years = input('years',0);
        $months = input('months',0);
        $map = array(
            'a.iqbtId'=>$iqbtId,
            'a.status'=>$status
        );
        if($name!=""){
            $map['a.name'] = array('like','%'.$name.'%');
        }
        if(!empty($years)){
            $con['years'] = $years;
        }else{
            $con['years'] = date('Y',time());
        }
        if(!empty($months)){
            $con['months'] = $months;
        }else{
            $con['months'] = date('n');
        }
        $etprsMsg = getDataList('enterprise',$map,'a.id,a.name,a.mobile,a.contact,a.entertime','a.id desc');
        if($etprsMsg['code'] ==1 &&!empty($etprsMsg['data'])){
            $etprsList = $etprsMsg['data'];
            foreach($etprsList as $key=>$value){
                $con['etprsId'] = $value['id'];
                $stateMsg = findById('statement',$con,'id');
                if($stateMsg['code']==1 && !empty($stateMsg['data'])){
                    $etprsList[$key]['status'] = true;
                }else{
                    $etprsList[$key]['status'] = false;
                }
            }

        }else{
            $etprsList = array();
        }
        //   print_r($etprsList);exit();
        return $etprsList;
    }


    function etprsState($etprsId=''){
        if(empty($etprsId)){
            return array('code'=>0,'msg'=>'参数错误');
        }
        return view('',array('etprsId'=>$etprsId));
    }

    //单个企业的数据汇总
    function etprsSummary(){
        $etprsId = input('etprsId',0);
        if(empty($etprsId)){
            return '';
        }
        $years = input('years',0);
        $quarter = input('quarter',0);
        $months = input('months',0);
        $map = array(
            'iqbtId'=>session('iqbtId'),
            'etprsId'=>$etprsId,
        );
        if(!empty($years)){
            $map['years'] = $years;
        }
        if(!empty($quarter)){
            $map['quarter'] = $quarter;
        }
        if(!empty($months)){
            $map['months'] = $months;
        }
       $data = self::sum_sheet($map);
       return $data;
    }

    //全部企业的数据汇总
    function totalSummary(){
        $years = input('years',0);
        $quarter = input('quarter',0);
        $months = input('months',0);
        $map = array(
            'iqbtId'=>session('iqbtId'),
        );
        if(!empty($years)){
            $map['years'] = $years;
        }
        if(!empty($quarter)){
            $map['quarter'] = $quarter;
        }
        if(!empty($months)){
            $map['months'] = $months;
        }
        $data = array('income'=>0.0,'tax'=>0.0,'total'=>0,'doctor'=>0,'thousand'=>0,'student'=>0,'invent'=>0,'rdinput'=>0.0,
            'highetprs'=>'','investment'=>0.0);
        //先查询有多少个企业
        $etprsIds = getFieldArrry('statement',$map,'etprsId');
        $sheet = array();
        if(!empty($etprsIds)){
            $etprsIds = array_unique($etprsIds);
            //先统计每个企业的最新数据，然后再汇总全部数据
            foreach($etprsIds as $key=>$val){
                $where = $map;
                $where['etprsId'] = $val;
                $sheet[$key] = self::sum_sheet($where);
            }
            $data['etprsTotal'] = count($etprsIds);
            foreach($sheet as $value){
                $data['income'] += $value['income'];
                $data['tax'] += $value['tax'] ;
                $data['total'] += $value['total'];
                $data['doctor'] += $value['doctor'];
                $data['thousand'] += $value['thousand'];
                $data['student'] += $value['student'];
                $data['invent'] += $value['invent'];
                $data['rdinput'] +=$value['rdinput'];
                $data['highetprs'] += $value['highetprs']=="是"?1:0;
                $data['investment'] += $value['investment'];

            }
            return $data;
        }else{
            return $data;
        }
    }

    //单个企业报表数据汇总
    function sum_sheet($where){
        $data = array('income'=>0.0,'tax'=>0.0,'total'=>0,'doctor'=>0,'thousand'=>0,'student'=>0,'invent'=>0,'rdinput'=>0.0,
            'highetprs'=>'','mainbus'=>'','inventname'=>"",'investment'=>0.0,'honor'=>'');
        $stateMsg = getDataList('statement',$where,'*','years asc,months asc');
        if($stateMsg['code']==1 && !empty($stateMsg['data'])){
            //数据汇总
            foreach($stateMsg['data'] as $value){
                $data['income'] += $value['income'];//月总营业额，累计
                $data['tax'] += $value['tax'] ; //月总税收,累计
                $data['total'] = $value['total'];//职工总人数，最新
                $data['doctor'] = $value['doctor'];//博士，最新
                $data['thousand']= $value['thousand'];//千人计划
                $data['student'] = $value['student'];//应届大学生，
                $data['invent'] += $value['invent'];//申请专利数，累计
                $data['rdinput'] +=$value['rdinput'];//研发经费投入,累计
                $data['highetprs'] = ($value['highetprs']==1)?'是':'否';//是否高企，最新
                $data['mainbus'] = $value['mainbus'];  //主营业务
                $data['inventname'] .= empty($value['inventname'])?'':$value['inventname'].';';//专利名称，累计
                $data['investment'] += $value['investment'];//获天使投资额 累计
                $data['honor']  .= empty($value['honor'])?'':$value['honor'].';';
            }

        }
        return $data;
    }


    //某一个企业的全部报表
    function getEtprsState($etprsId=''){

        $years = input('years',0);
        $quarter = input('quarter',0);
        $months = input('months',0);
        $map = array(
            'iqbtId'=>session('iqbtId'),
        );
        if(!empty($etprsId)){
           $map['etprsId'] = $etprsId;
        }
        if(!empty($years)){
            $map['years'] = $years;
        }
        if(!empty($quarter)){
            $map['quarter'] = $quarter;
        }
        if(!empty($months)){
            $map['months'] = $months;
        }
        $stateMsg = getDataList('statement',$map,'*','years desc,months desc');
        if($stateMsg['code']==1 && !empty($stateMsg['data'])){
            $stateList = $stateMsg['data'];
        }else{
            $stateList = array();
        }
        return $stateList;
    }

    //导出月报管理中的数据
    function exportMonth(){
        $ids = input("id");
        $status = input('status');
        if(empty($ids)){
            return array('code'=>0,'msg'=>'请选择要导出的数据');
        }
        $arrId = explode(",",$ids);
        $arrstatus = explode(",",$status);
        $idStatus = array_combine($arrId,$arrstatus);
        $data = array();
        $con = array('id'=>array('in',$arrId));
        $etprsMsg = getDataList('enterprise',$con,'a.id,a.name,a.mobile,a.contact,a.entertime','a.id desc');
        if($etprsMsg['code'] ==1 &&!empty($etprsMsg['data'])){
            $etprsList = $etprsMsg['data'];
            foreach($etprsList as $key=>$value){
              $data[$key]['name'] = $value['name'];
              $data[$key]['contact'] = $value['contact'];
              $data[$key]['mobile'] = $value['mobile'];
              $data[$key]['entertime'] = date("Y-m-d",$value['entertime']);
              $data[$key]['status'] = ($idStatus[$value['id']] =='true'? "已提交报表":"未提交报表");
            }
            $filename = "月报管理";
            $header = array('企业名称','联系人','联系电话','入驻时间','提交报表状态');
            vendor("PHPExcel");
            vendor("PHPExcel.Writer.Excel5");
            vendor("PHPExcel.IOFactory");
            getExcel($filename,$header,$data);
        }
    }

    //导出单个企业或者全部报表
    function exportState(){
        $ids = input("id");
        $data = array();
        if(empty($ids)){
            return array('code'=>0,'msg'=>'请选择要导出的数据');
        }
        $header = array('企业名称','报表月份','联系人','联系电话','填报时间','主营业务','月总营业额（万元）','月总税收（万元）','企业职工总数','博士',
            '千人计划','应届大学生人数','申请专利数','专利名称','研发经费投入（万元）','获天使或风险投资额（万元）','是否高企','高企认定时间','获得荣誉');
        $arrId = explode(",",$ids);
        $stateMsg = getDataList('statement',array('id'=>array('in',$arrId)),'*','years desc,months desc');
        if($stateMsg['code']==1 && !empty($stateMsg['data'])){
            foreach($stateMsg['data'] as $key=>$value){
                $data[$key]['etprsname'] = $value['etprsname'];
                $data[$key]['month'] = $value['month'];
                $data[$key]['person'] = $value['person'];
                $data[$key]['mobile'] = $value['mobile'];
                $data[$key]['addtime'] = date("Y-m-d",$value['addtime']);
                $data[$key]['mainbus'] = $value['mainbus'];
                $data[$key]['income'] = $value['income'];
                $data[$key]['tax'] = $value['tax'];
                $data[$key]['total'] = $value['total'];
                $data[$key]['doctor'] = $value['doctor'];
                $data[$key]['thousand'] = $value['thousand'];
                $data[$key]['student'] = $value['student'];
                $data[$key]['invent'] = $value['invent'];
                $data[$key]['inventname'] = $value['inventname'];
                $data[$key]['rdinput'] = $value['rdinput'];
                $data[$key]['investment'] = $value['investment'];
                $data[$key]['highetprs'] = $value['highetprs']=='1'?'是':'否';
                $data[$key]['hightime'] = $value['highetprs']=='1'?date("Y-m-d",$value['hightime']):'-----';
                $data[$key]['honor'] = $value['honor'];
            }
            $filename = "月报数据";
            vendor("PHPExcel");
            vendor("PHPExcel.Writer.Excel5");
            vendor("PHPExcel.IOFactory");
            getExcel($filename,$header,$data);
        }

    }
    //对于那些没有交报表的企业，可以发送消息提醒
    function addMsg(){
        $data['etprsId'] = input('etprsId');
        $data['years'] = input('years');
        $data['months'] = input('months');
        return view('',$data);
    }

    function saveMsg(){
        $postData = input('request.');
        $str = $postData['years'].'年'.$postData['months'].'月份';
        $etprsIds = $postData['etprsId'];
        if(empty($etprsIds)){
            return array('code'=>0,'msg'=>'企业参数错误');
        }
        //可以群发
        $idArrs = explode(",",$etprsIds);
        //获取userId 和企业名称
        $join = [['user b','a.id=b.etprsId']];
        $con = array('a.id'=>array('in',$idArrs),'b.userCate'=>'1011002');
        $etprsMsg = getDataList('enterprise',$con,'a.name,a.mobile,b.realname as username,b.id as userid','a.id asc',$join);
        $etprsStr = '';
        $userIdArr = array();
        $userIdStr ='';
        if($etprsMsg['code']==1 && !empty($etprsMsg['data'])){
            foreach($etprsMsg['data'] as $value){
                $etprsStr .=$value['name'].'('.$value['username'].'),';
                $userIdArr[] = $value['userid'];
                $userIdStr .= $value['userid'].',';
            }
        }
        //发邮件
        $data = array(
            "iqbtId"=>session("iqbtId"),
            "addtime"=>time(),
            'userId' => session("userId"),
            'toUserId'=>$userIdStr,
            'toUserName'=>$etprsStr,
            'title'=>'月报表催收通知',
            'content'=>$postData['desc'],
            'status'=>1
        );
        $res = saveData('SysOutbox',$data,'发送站内信');
        if($res['code']==1) {
            $emailData = array(
                'type' => '1020008',
                'title' => '月报表催收通知',
                'content' => $postData['desc'],
            );
            //说明发件箱保存成功，然后分别把信息保存到收件箱
            $smsData = array();
            if(isset($postData['sms'])&&$postData['sms']==1){
                $tpl_id = config('sms_tpl_id.state');
                $smsData = array(
                    'tpl'=>$tpl_id,
                    'data'=>array(
                        'time'=>$str,
                    ),
                );
            }
            $this->sendAllMsg($userIdArr,$emailData,array(),$smsData);

            return array('code'=>'1','msg'=>'发送成功','data'=>'发送成功');
        }else{
            return $res;
        }
    }

    function exportEtprs(){
        $postData = input("request.");
        $sql="select a.id,a.`name` as etprsName,a.apltype,a.addtime,a.contact,a.entertime,a.status,a.mobile,b.industry,b.etprsId,a.tutorIds,b.total from ibt_enterprise as a left join ibt_etprs_info as b on a.id=b.etprsId where ";
        $sql=$sql."a.iqbtId=".session("iqbtId");
        $schflag=isset($postData["schflag"])?$postData["schflag"]:"all";
        switch ($schflag){
            case "ing":
                //$con2["a.status"]="1001016";
                $sql=$sql." and a.status=1001016";
                break;
            case "apl":
                $sql=$sql." and a.status in ('1001011','1001012','1001013','1001014','1001015')";
                //$con2["a.status"]=array("in","1001011,1001012,1001013,1001014,1001015");;
                break;
            case "gradt":
                $con2["a.status"]="1001017";
                $sql=$sql." and a.status=1001017";
                break;
            case "quit":
                $sql=$sql." and a.status in ('1001021','1001020')";
                //$con2["a.status"]="1001000";
                break;
            default:
                $sql=$sql." and a.status in ('1001021','1001020','1001011','1001012','1001013','1001014','1001015','1001016','1001017')";
                break;
        }
        //企业名称
        if(isset($postData['name'])&&$postData['name'] !=""){
            $sql=$sql." and a.name like '%".$postData['name']."%'";
        }
        //申请类型
        if(isset($postData['apltype'])&&$postData['apltype'] !=""){
            $sql=$sql." and a.apltype = ".$postData['apltype'];
        }
        //成立时间
        if(isset($postData['rgst_start'])&&!empty($postData['rgst_start'])&&isset($postData['rgst_end'])&&!empty($postData['rgst_end'])){
            $sql=$sql." and unix_timestamp(a.rgsttime) between  unix_timestamp('".$postData['rgst_start']."') and unix_timestamp('".$postData['rgst_end']."')";
        }

        //入驻时间
        if(isset($postData['enter_start'])&&!empty($postData['enter_start'])&&isset($postData['enter_end'])&&!empty($postData['enter_end'])){
            $sql=$sql." and a.entertime between  unix_timestamp('".$postData['enter_start']."') and unix_timestamp('".$postData['enter_end']."')";
        }
        //行业类型
        if(isset($postData['industry'])&&$postData['industry']!=""){
            $sql=$sql." and b.industry = ".$postData['industry'];
        }
        //技术领域
        if(isset($postData['technical'])&&$postData['technical']!=""){
            $sql=$sql." and b.technical = ".$postData['technical'];
        }
        if(!empty($postData['people'])&&!empty($postData['peoplenum'])){
            $key = 'b.'.$postData['people'];
            switch($postData['peoplenum']){
                case 1:
                    $sql=$sql." and ".$key." <5 ";
                    break;
                case 2:
                    $sql=$sql." and ".$key." between 6 and 10 ";
                    break;
                case 3:
                    $sql=$sql." and ".$key." between 11 and 20 ";
                    break;
                case 4:
                    $sql=$sql." and ".$key." between 21 and 60 ";
                    break;
                case 5:
                    $sql=$sql." and ".$key." >50";
                    break;
                default:break;
            }
        }

        //知识产权
        if(isset($postData['iprapl']) && $postData['iprapl']!=""){
            $key = 'b.iprapl';
            switch($postData['iprapl']){
                case 1:
                    $sql=$sql." and ".$key." <5 ";
                    break;
                case 2:
                    $sql=$sql." and ".$key." between 6 and 10 ";
                    break;
                case 3:
                    $sql=$sql." and ".$key." between 11 and 20 ";
                    break;
                case 4:
                    $sql=$sql." and ".$key." between 21 and 60 ";
                    break;
                case 5:
                    $sql=$sql." and ".$key." >50";
                    break;
                default:break;
            }
        }
        //导师
        if(isset($postData['tutorIds'])&& $postData['tutorIds']!=""){
            //$con2["CONCAT(',','a.tutorIds',',')"] = array('like',"%,".$postData['tutor'].",%");
            $tutorsql="";
            foreach ($postData['tutorIds'] as $tutorId) {
                $tutorsql=$tutorsql." or CONCAT(',',a.tutorIds,',') like '%,".$tutorId.",%'";
            }
            $tutorsql=substr($tutorsql,3).")";
            if(!empty($tutorsql)){
                $sql=$sql." and (".$tutorsql;
            }

        }
        //是否高新企业
        if(isset($postData['highetprs'])&& $postData['highetprs']!=""){
            $sql=$sql." and b.highetprs = ".$postData['highetprs'];
        }
        //是否欠费#todo
        $feeids="";
        if(isset($postData['fee_status'])){
            $feecon=array("iqbtId"=>session("iqbtId"),'status'=>0);
            $feemsg=getDataList("fee_rcd",$feecon," distinct etprsId as etprsId");
            if(!empty($feemsg["data"])){
                foreach ($feemsg["data"] as $fee) {
                    $feeids=$feeids.",".$fee["etprsId"];
                }
                $feeids=trim($feeids,",");
            }
            if(empty($postData['fee_status'])){
                //欠费
                if(!empty($feeids)){
                    $sql =$sql." and a.id in(".$feeids.")";
                }
            }else{
                //不欠费
                if(!empty($feeids)){
                    $sql =$sql." and a.id not in(".$feeids.")";
                }
            }

        }

        //分配房间
        $roomids="";
        if(!empty($postData['build'])||!empty($postData['floor'])||!empty($postData['roomNo'])){
            if(isset($postData['build'])&& $postData['build']!=""){
                //c 房间表
                $map['buildId'] = $postData['build'];
            }
            if(isset($postData['floor']) && $postData['floor']!=""){
                $map['floor'] = $postData['floor'];
            }
            /*if(isset($postData['roomNo'])&&$postData['roomNo']!=""){
                $map['c.roomNo'] = array('like','%'.$postData['roomNo'].'%');
            }*/
            if(isset($postData['roomNo'])&&!empty($postData['roomNo'])){
                $roomNo=getField("EstateRoom",array("id"=>$postData['roomNo']),"roomNo");
                //$map['roomNo'] = array('like','%'.$roomNo.'%');
                $map['roomNo'] = $roomNo;
            }
            if(!empty($map)){
                $etprsIds = getFieldArrry('EstateRoom',$map,'etprsId');
                if(!empty($etprsIds)){
                    $roomids = array_unique($etprsIds);
                }
            }
            if(empty($roomids)){
                $roomids="0";
            }
            $sql =$sql." and a.id in(".$roomids.")";
        }
        $etprsapl=Db::query($sql);
        if(!empty($etprsapl)){
            $tmplist=self::getDictStr("*","EtprsApl");
            $etprsapl=$this->setListIdText($etprsapl,$tmplist);
        }
        for ($i = 0; $i < count($etprsapl); $i++) {
            $etprsId=$etprsapl[$i]["id"];
            $roomNos="";
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.="，".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,"，");
            $etprsapl[$i]["roomNos"]=$roomNos;

            $tutorIds=$etprsapl[$i]["tutorIds"];
            if(!empty($tutorIds)){
                $tutors="";
                $tmsg=getDataList("tutor",array("id"=>array("in",$tutorIds)),"name");
                if(!empty($tmsg["data"])){
                    $tutors=$tutors.",".$tmsg["data"][0]["name"];
                }
                $tutors=trim($tutors,",");
                $etprsapl[$i]["tutors"]=$tutors;
            }else{
                $etprsapl[$i]["tutors"]="";
            }

            $endmsg=findById("estateRoomEtprs",array("etprsId"=>$etprsId),"max(endTime) as endTime");
            if(!empty($endmsg["data"]["endTime"])){
                $etprsapl[$i]["endTime"]=date("Y-m-d",$endmsg["data"]["endTime"]);
            }else{
                $etprsapl[$i]["endTime"]="---";
            }
        }

        if($etprsapl){
            $data = array();
            foreach($etprsapl as $key=>$val){

                $data[$key]['etprsName'] = $val['etprsName'];
                $data[$key]['contact'] = $val['contact'];
                $data[$key]['mobile'] = $val['mobile'];
                $data[$key]['industry'] = $val['industryText'];
                $data[$key]['tutors'] = $val['tutors'];
                $data[$key]['room']  = $val['roomNos'];
                $data[$key]['total'] = $val['total'];
                $data[$key]['aplltime']  = date("Y-m-d",$val['addtime']);
                $data[$key]['apltype'] = $val['apltype'] =='0'?"团队入驻":"企业入驻";
                $data[$key]['entertime'] = empty($val['entertime'])?'---':date("Y-m-d",$val['entertime']);
                $data[$key]['endtime'] = $val['endTime'];
                if($val['status']=="1001016"){
                    $statusText = "在孵企业";
                }elseif($val['status']=="1001017"){
                    $statusText = "毕业企业";
                }elseif($val['status']=="1001020"||$val['status']=="1001021"){
                    $statusText = "中途退出";
                }else{
                    $statusText = "申请中";
                }
                $data[$key]['status'] = $statusText;
            }
            $filename = "企业信息表";
            $header = array('企业名称','联系人','联系电话','行业类型','导师','已分配房间','团队人数','申请时间','申请类型','入驻时间','到期时间','企业状态');
            vendor("PHPExcel");
            vendor("PHPExcel.Writer.Excel5");
            vendor("PHPExcel.IOFactory");
            getExcel($filename,$header,$data);

        }
    }



}