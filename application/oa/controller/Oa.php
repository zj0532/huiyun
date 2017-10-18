<?php
namespace app\oa\controller;
use app\common\controller\Baseservice;
use app\index\controller\Common;
use think\Db;
use think\Exception;
use think\Log;

class Oa extends Common{
    //园企互动，获取互动列表
    function getSuggest($key=''){
        $userCate=session("user.userCate");
        $con=array('a.iqbtId'=>session("iqbtId"));
        if(!empty($key)){
            $con['a.title'] = array('like','%'.$key.'%');
        }
        if($userCate=="1011002"){
            //如果是企业用户
            $etprsId=session("user.etprsId");
            $con["a.etprsId"]=$etprsId;
        }
        $base = new Baseservice();
        $res = $base->getSuggest($con);
        return $res;
    }


    function addSuggest()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("EtprsSuggest",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function sgstReply()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("EtprsSuggest",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveSuggest()
    {
        $postData=input("request.");
        if(empty($postData["id"])){
            $postData["iqbtId"]=session("user.iqbtId");
            $postData["etprsId"]=session("user.etprsId");
            $postData["adduserId"]=session("userId");
            $postData["addtime"]=time();
        }
        $table="EtprsSuggest";
        $msg = saveData($table,$postData,"添加/修改企业建议信息");
        if($msg["code"]==='1'){
            if(empty($postData['id'])){
                //给管理员发送微信通知
                $etprsName = getField('enterprise',array('id'=>session("etprsId")),'name');
                $wxData = array();
                if(config('open_wechat')){
                    $template_id = config('wx_tpl.demand');
                     $first = $etprsName."提出了新的需求/建议";
                     $wxData = array(
                         'tpl'=>$template_id,
                         'data'=>array(
                             'keyword1'=>$postData['title'],
                             'keyword2'=>date("Y-m-d:H:i",time()),
                             'keyword3'=>'等待处理',
                         ),
                         'first'=>$first,
                         'remark'=>'请登录系统，查看详情'
                     );
                }
                //发送站内信
                $emailData = array(
                    'title'=>'企业需求/建议',
                    'content'=>'您好，'.$etprsName.'提出新的需求/建议，请登录系统及时回复处理',
                    'type'=>'1020004',
                    'relTable'=>'etprsSuggest',
                    'relId'=>$msg['data'],
                );
                $optIds = getAdminIds('28',false);
                $this->sendAllMsg($optIds,$emailData,$wxData);
            }else{

                if(isset($postData['desc'])){
                    //管理员端回复，给企业发消息
                    $link= '需求/建议:'.$postData['title'];$status1 = " 管理员已经回复，回复内容为:".$postData['desc'];
                    $emailData = array(
                        'title'=>$link.'回复结果',
                        'content'=>'尊敬的客户您好：您提交的'.$link.$status1.',详情请查看相应栏目信息',
                        'type'=>'1020004',
                        'relTable'=>'etprsSuggest',
                        'relId'=>$postData['id'],
                    );
                    $wxData = array();
                    if(config('open_wechat')){
                        $template_id = config('wx_tpl.demand');
                        $wxData = array(
                            'tpl'=>$template_id,
                            'data'=>array(
                                'keyword1'=>$postData['title'],
                                'keyword2'=>date("Y-m-d:H:i",time()),
                                'keyword3'=>$postData['desc'],
                            ),
                            'first'=>'需求/建议回复消息',
                            'remark'=>'请登录系统，查看详情',
                        );
                    }
                    $uMsg = findById('EtprsSuggest',array('id'=>$postData['id']),'adduserId,etprsId');
                    if($uMsg['code']==1 && !empty($uMsg['data'])){
                        $uid = $uMsg['data']['adduserId'];
                        $etprsId = $uMsg['data']['etprsId'];
                    }else{
                        $uid = 0;
                        $etprsId = 0;
                    }
                    $this->sendAllMsg($uid,$emailData,$wxData);
                    //工作日志
                    $logData = array(
                        'etprsId'=>$etprsId,
                        'fmenuId'=>27,
                        'smenuId'=>28,
                        'objId'=>$postData['id'],
                        'content'=>'对标题为：“'.$postData['title'].'”的需求/建议进行了回复',
                    );
                    saveOaLog($logData);
                }

            }
        }
        return $msg;
    }
    function deleteSuggest(){
        $id=input("id");
        return deleteData("EtprsSuggest",$id,"删除企业建议信息");
    }

    function suggestdetail($id=0)
    {
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("EtprsSuggest",array("a.id"=>$id),"a.*,b.name as etprsname",$join);

        $data=array();
        if(!empty($msg['data'])){
            $data=$msg["data"];
        }
        Log::notice(json_encode($data));
        return view("",array("data"=>$data));
    }

    //服务反馈
    function assess($id=0){
        $c = array();
        if(!empty($id)){
            $msg=findByid("EtprsSuggest",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
                $tmplist=self::getDictStr("*","EtprsSuggest");
                $c=$this->setObjIdText($c,$tmplist);
            }
        }
        return view("",array("data"=>$c));
    }

    function saveAssess(){
        $id = input('id','');
        if(empty($id)){
            return array('code'=>0,'msg'=>'提交失败');
        }
        $assess = input('assess','');
        $data = array(
            'status'=>3,
            'assess'=>$assess
        );
        $res = saveDataByCon('EtprsSuggest',$data,array('id'=>$id));
        return $res;
    }


    //申请
    function aplActivity($id)
    {
        $postData["activityId"]=$id;
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["etprsId"]=session("user.etprsId");
        $postData["adduserId"]=session("userId");
        $table="ActivityApply";
        $msg= saveData($table,$postData,"保存活动");
        return $msg;
    }

    function aplStatus($id,$status)
    {
        if($status=="2"||$status=="3"){
            $join = [['activity b','a.activityId=b.id']];
            $actApl=findById("ActivityApply",array("a.id"=>$id),"a.adduserId,a.activityId,b.name",$join);
            if($actApl["code"]==='1'&&!empty($actApl["data"])){
                $actname=$actApl['data']["name"];
                $actUserId=$actApl['data']["adduserId"];
                $datam["toUserId"]=$actUserId;
                $datam["type"]="1020008";
                $datam["title"]="活动 ".$actname."申请".($status=="2"?"通过":"被拒绝");
                $datam["content"]="活动 ".$actname."申请".($status=="2"?"通过":"被拒绝")."，请确认。";
                $datam["iqbtId"]=session("iqbtId");
                $datam["addtime"]=time();
                $datam["oprtUserId"]=session("userId");

                saveData("SysMsg",$datam,'资源合作通知');
            }
        }

        return saveData("ActivityApply",array("id"=>$id,"status"=>$status));
    }

    //通知公告
    function getNews($type="",$title="")
    {
        $con = array();
         $con["a.iqbtId"]=session("user.iqbtId");
        if($type!=""){
            $con["a.type"]=$type;
        }
        if($title!=""){
            $con['a.title'] = array('like','%'.$title.'%');
        }
        $join = [['user b','a.adduserId=b.id',"left"]];
        $msg=getDataList("SysNotice",$con,"a.*,b.realname as username"," a.sort desc, a.id desc",$join);
        if($msg["code"]==="1"){
            $notice=$msg["data"];
            $tmplist=self::getDictStr("type","SysNotice");
            $notice=$this->setListIdText($notice,$tmplist);
            return $notice ;

        }else{
            return array();
        }
    }
    //手机端通知公告
    function etprsNewsMObile($uid=0)
    {
        if(empty($uid)){
            $uid = session('userId');
        }
        $con = array(
            'a.iqbtId'=>session('iqbtId'),
            'a.toUserId'=>$uid,
            'a.recycle'=>'0'
        );

        $msg=getDataList("SysMsg",$con,"a.*","a.id desc",array(),'','3');

        if($msg["code"]==="1"){
            foreach($msg['data'] as $key=>$value){
                $msg['data'][$key]['url'] = url('/oa/Oa/newsDetail',array('id'=>$value['id']));
            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    //手机端通知公告
    function etprsNewsList($uid=0)
    {
        if(empty($uid)){
            $uid = session('userId');
        }
        $con = array(
            'a.iqbtId'=>session('iqbtId'),
            'a.toUserId'=>$uid,
            'a.recycle'=>'0'
        );
        $msg=getDataList("SysMsg",$con,"a.*","a.id desc");
        if($msg["code"]==="1"){
            foreach($msg['data'] as $key=>$value){
                $msg['data'][$key]['url'] = url('/oa/Oa/newsDetail',array('id'=>$value['id']));
            }
            return view('',array('data'=>$msg["data"]));
        }else{
            return view('',array('data'=>array()));
        }
    }
    //公告详情
    function newsDetail($id=''){
        if(empty($id)){
            $uid = session('userId');
        }
        $info = findById('SysMsg',array('id'=>$id),'*');
        if($info['code']==1 && !empty($info['data'])){
            return view('',array('data'=>$info['data']));
        }else{
            return view('',array('data'=>array()));
        }
    }

    //删除通知公告
    function deleteNews(){
        $id=input("id");
        $name = getField('sysNotice',array('id'=>$id),'title');
        $res =  deleteData("SysNotice",$id,"删除通知公告");
        if($res['code']==1){
            //工作日志
            $logData = array(
                'etprsId'=>'0',
                'fmenuId'=>57,
                'smenuId'=>351,
                'objId'=>$id,
                'content'=>'删除了通知公告:'.$name
            );
            saveOaLog($logData);
        }
        return $res;
    }

    //添加通知公告
    public function addNews(){
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("SysNotice",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $savepath=getField("sysFile",array("id"=>$msg["data"]["banner"]),"savePath");
                $msg["data"]["savePath"]=$savepath;
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    //保存通知公告
    public function saveNews(){
        $postData=input("request.");
        $postData["iqbtId"]=session("iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=date("Y-m-d",time());
        $table="SysNotice";
        $res =  saveData($table,$postData,"保存通知公告");
        if($res['code']==1){
            if(!isset($postData['id']) ||empty($postData['id'])){
                //只在新增的时候发消息，编辑不再发送
                //发送消息
               /* //获取所有的入驻企业用户userId
                $join = [['enterprise b','b.id=a.etprsId']];
                $con = array(
                    'a.iqbtId'=>session('iqbtId'),
                    'a.userCate'=>'1011002',
                    'b.status'=>'1001016'
                );
                $userIds =getFieldArrry('user',$con,'a.id','',$join);
               */
                //通知公告，应该给孵化器里所有的人发，包括企业、管理员和导师
                $con = array(
                    'iqbtId'=>session('iqbtId'),
                    'status'=>'1012001'
                );
                $userIds = getFieldArrry('user',$con,'id');
                $smsData = array();
                $emailData = array(
                    'type'=>'1020009',
                    'title'=>'通知公告',
                    'content'=>'管理员发布了新的通知公告：'.$postData['title'],
                    'relTable'=>'sysNotice',
                    'relId'=>$res['data']
                );
               /* if($sms ==1) {
                    $data = array(
                        'name' => $postData['name'],
                        'time' => date("Y-m-d H:i", $postData['startTime']) . '—' . date("Y-m-d H:i", $postData['endTime']),
                    );
                    $tpl = config('sms_tpl_id.activity');
                    $smsData = array(
                        'tpl' => $tpl,
                        'data' => $data
                    );
                }*/
                $this->sendAllMsg($userIds,$emailData,array(),$smsData);
                //工作日志
                $logData = array(
                    'etprsId'=>'0',
                    'fmenuId'=>57,
                    'smenuId'=>351,
                    'objId'=>$res['data'],
                    'content'=>'发布新通知公告:'.$postData['title']
                );
                saveOaLog($logData);
            }
        }
        return $res;
    }

    function getFaqList($title=''){
        $con = array();
        $con["a.iqbtId"]=session("user.iqbtId");

        if($title!=""){
            $con['a.title'] = array('like','%'.$title.'%');
        }
        $join = [['user b','a.adduserId=b.id',"left"]];
        $msg=getDataList("faq",$con,"a.*,b.realname as username"," a.sort desc, a.id desc",$join);
        if($msg["code"]==="1"){
            $notice=$msg["data"];
            return $notice ;

        }else{
            return array();
        }
    }

    //添加FAQ（常见问题）
    function addFaq(){
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("faq",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveFaq(){
        $postData=input("request.");
        $postData["iqbtId"]=session("iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=date("Y-m-d",time());
        $table="faq";
        $res =  saveData($table,$postData,"保存常见问题");
        return $res;
    }

    function deleteFaq(){
        $id=input("id");
        $res =  deleteData("faq",$id,"删除常见问题");
        return $res;
    }



    //部门职责介绍，主要用于前台显示
    function addIntro(){
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("deptIntro",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveIntro(){
        $postData=input("request.");
        $postData["iqbtId"]=session("iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=date("Y-m-d",time());
        $table="deptIntro";
        return saveData($table,$postData,"保存部门介绍");
    }

    //删除部门
    function deleteIntro(){
        $id=input("id");
        return deleteData("deptIntro",$id,"删除部门介绍");
    }
    function getIntroList($title=''){
       $con=array('iqbtId'=>session("iqbtId"));
        if($title!=""){
            $con['title'] = array('like','%'.$title.'%');
        }
        $msg=getDataList("deptIntro",$con,"*"," sort desc");
        if($msg["code"]==="1"){
            $data=$msg["data"];
            return $data ;


        }else{
            return array();
        }
    }

    //工作日志，系统自动生成的操作记录，来自oa_actionlog表
    function actionloglist(){
        $typeArr = array('8','9','10','11','12','13','28','358');//日志类别数组
        $typedata = array();//日志类别
        $msg = getDataList('userMenu',array('id'=>array('in',$typeArr)),'id,name');
        if($msg['code']==1){
            $typedata = $msg['data'];
        }
        return view("",array('typedata'=>$typedata));
    }


    //获取工作日志列表
    function getActionlog(){
        $map = array(
            'a.iqbtId'=>session('iqbtId'),
        );
        $postData = input('request.');
        if(isset($postData['username']) && !empty($postData['username'])){
            $map['b.realname'] = array('like',"%".$postData['username']."%");
        }
        if(isset($postData['typeid']) && !empty($postData['typeid'])){
            $map['a.smenuId'] = $postData['typeid'];
        }
        if(isset($postData['etprsname']) && !empty($postData['etprsname'])){
            $map['c.name'] = array('like',"%".$postData['etprsname']."%");
        }
        $time_start = isset($postData['time_start'])? $postData['time_start']: '';
        $time_end = isset($postData['time_end'])? $postData['time_end']: '';
        if(!empty($time_start) && !empty($time_end)){
            $map['a.addtime'][] = array('gt',strtotime($time_start));
            $map['a.addtime'][] = array('lt',strtotime($time_end));
        }else{
            if(!empty($time_start)){
                $map['a.addtime'] = array('gt',strtotime($time_start));
            }
            if(!empty($time_end)){
                $map['a.addtime'] = array('lt',strtotime($time_end));
            }
        }

        $join = [['user b','a.adduserId=b.id','left'],['enterprise c','a.etprsId=c.id','left'],['user_menu d','a.smenuId=d.id','left']];
        $msg = getDataList('oaActionlog',$map,'a.addtime,a.content,b.realname as username,c.name as etprsname,d.name as typename','a.id desc',$join);
        $data = array();
        if($msg['code'] ==1){
            $data = $msg['data'];
            foreach($data as $key=>$value){
                $data[$key]['addtime'] = date("Y-m-d",$value['addtime']);
            }
        }
        return $data;
    }








    //钉钉考勤----------------------------------
    function dingindex()  {
        $iqbtId=session("iqbtId");
        $data=[];
        $dmsg=getDataList("dingDept",array("iqbtId"=>$iqbtId),"id,name,parentid,dingId");
        if(!empty($dmsg["data"])){
            $deptlist=$dmsg["data"];
        }else{
            $deptlist=self::getDeptList();
            self::initDepts($deptlist);
            for ($i = 0; $i < count($deptlist); $i++) {
                $deptlist[$i]["dingId"]=$deptlist[$i]["id"];
            }
        }
        if(!empty($deptlist)){
            foreach ($deptlist as $dept) {
                if($dept["id"]==1){
                    $data["name"]=$dept["name"];
                }
                if(isset($dept["parentid"])&&$dept["parentid"]==1){
                    $data["deptlist"][]=$dept;
                }
            }
        }
        return view("",array('data'=>$data));
    }

    function dingcldDepts($deptId=""){
        $depts=self::parentDepts($deptId);
        $cldDepts=[];
        $staffs=[];
        $cmsg=getDataList("dingDept",array("parentid"=>$deptId));
        if(!empty($cmsg['data'])){
            $cldDepts=$cmsg['data'];
        }
        $smsg=getDataList("dingStaff",array("department"=>$deptId));
        if(!empty($smsg['data'])){
            $staffs=$smsg['data'];
        }
        return view("",array('data'=>array("depts"=>$depts,"cldDepts"=>$cldDepts,"staffs"=>$staffs,"deptId"=>$deptId)));
    }

    function dingAttence($deptId="0",$userId='0',$start=0,$end=0)
    {
        if(empty($start)||empty($end)){
            $start=date("Y-m-d",strtotime(date("Y-m-d",time()))-6*86400);
            $end=date("Y-m-d",strtotime(date("Y-m-d",time()))+86399);
        }

        $depts=self::parentDepts($deptId);
        $staff=[];

        $smsg=findById("dingStaff",array("userid"=>array("like","%".$userId)));

        if(!empty($smsg['data'])){
            $staff=$smsg['data'];
            $userId=$staff["userid"];
        }

        $attences=[];
        $tmpattences2=[];

        //var_dump($start."~".$end);
        $tmpattences=self::getAttence($userId,$start." 00:00:00",$end." 23:59:59");
        $tmpattences=$tmpattences["recordresult"];
        foreach ($tmpattences as $attence){
            if(isset($attence["checkType"])){
                $tmpattences2[date("Y-m-d",$attence["workDate"]/1000)]["week"]=self::getTimeWeek($attence["userCheckTime"]/1000);
                $tmpattences2[date("Y-m-d",$attence["workDate"]/1000)][$attence["checkType"]]=date("Y-m-d H:i:s",$attence["userCheckTime"]/1000);
            }
        }

        foreach ($tmpattences2 as $k=>$v) {
            $tmp=array("day"=>$k);
            if(isset($v["OffDuty"])){
                $tmp["OffDuty"]=$v["OffDuty"];
            }
            if(isset($v["OnDuty"])){
                $tmp["OnDuty"]=$v["OnDuty"];
            }

            if(isset($v["week"])){
                $tmp["week"]=$v["week"];
            }
            $attences[]=$tmp;
        }
        return view("",array('data'=>array("depts"=>$depts,'staff'=>$staff,"attences"=>$attences,"deptId"=>$deptId),"start"=>$start,"end"=>$end));
    }

    function parentDepts($deptId,$depts=[]){
        $msg=findById("dingDept",["dingId"=>$deptId]);
        if(!empty($msg['data'])){
            array_unshift($depts,$msg['data']);
            if(isset($msg['data']["parentid"])&&!empty($msg['data']["parentid"])){
                $depts=self::parentDepts($msg['data']["parentid"],$depts);
            }
        }
        return $depts;
    }

    function cldDeptList($deptId=0)
    {
        $iqbtId=session("iqbtId");
        $dmsg=getDataList("dingDept",array("iqbtId"=>$iqbtId,"parentid"=>$deptId),"id,name,parentid,dingId");
        if(!empty($dmsg["data"])){
            $deptlist=$dmsg["data"];
        }else{
            $deptlist=self::getDeptList();
            self::initDepts($deptlist);
        }
        return $deptlist;
    }

    function getUserList($deptDingId=0)
    {
        $con=array("iqbtId"=>session("iqbtId"));
        if(!empty($deptDingId)){
            $con["department"]=$deptDingId;
        }
        $msg=getDataList("dingStaff",$con,"name,userid,id");
        if(!empty($msg["data"])){
            return $msg["data"];
        }else{
            return [];
        }
    }

    //第一次初始化部门。只添加。
    function initDepts($deptlist=array())
    {
        $token=self::getDingToken();
        if(!empty($token)) {
            foreach ($deptlist as $dept) {
                $dept["addtime"] = time();
                $dept["iqbtId"] = session("iqbtId");
                $dept["dingId"] = $dept["id"];
                unset($dept["id"]);
                $msg = saveData("dingDept", $dept);
                if (!empty($msg['data'])) {
                    $deptId = $dept["dingId"];
                    $url = "https://oapi.dingtalk.com/user/simplelist?access_token=" . $token . "&department_id=" . $deptId;
                    $ret = file_get_contents($url);
                    $userdata = json_decode($ret, true);
                    if(!empty($userdata["userlist"])){
                        foreach ($userdata["userlist"] as $user) {
                            $user["department"]=$deptId;
                            $user["iqbtId"] = session("iqbtId");
                            $user["addtime"] = time();

                            saveData("dingStaff",$user);
                        }
                    }
                }
            }
        }
    }

    function getDeptList(){
        $token=self::getDingToken();
        if(!empty($token)){
            $url="https://oapi.dingtalk.com/department/list?access_token=".$token;
            $ret=file_get_contents($url);
            $return=json_decode($ret,true);
            if(isset($return["department"])&&!empty($return["department"])){
                return $return["department"];
            }else{
                return [];
            }
        }else{
            return [];
        }
    }

    //考勤相关  获取考勤数据 只支持七天
    function getAttence($userId=0,$from=0,$end=0){
        if(empty($userId)){
            return json(array('code'=>'0','msg'=>'用户不能为空','data'=>[]));
        }
        if(empty($from)||empty($end)){
            return json(array('code'=>'0','msg'=>'开始时间和结束时间不能为空','data'=>[]));
        }
        $token=self::getDingToken();

        $end=date("Y-m-d H:i:s",strtotime($end));

        $url="https://oapi.dingtalk.com/attendance/listRecord?access_token=".$token;  //新接口
        $data = array("userIds" => [$userId],'checkDateFrom'=>$from,'checkDateTo'=>$end);
        $context = stream_context_create(array(
            'http' => array('method' => 'POST','header' => 'Content-type:application/json;charset=UTF-8','content' => json_encode($data),'timeout' => 30)
        ));
        $result = file_get_contents($url, false, $context);
        $return=json_decode($result,true);
        return $return;
    }


    function initdinginfo()
    {
        try{
            $iqbtId=session("iqbtId");
            // 删除部门信息和人员信息
            deleteByCon("dingDept",array("iqbtId"=>$iqbtId),"");
            deleteByCon("dingStaff",array("iqbtId"=>$iqbtId),"");
            $deptlist=self::getDeptList();
            self::initDepts($deptlist);
            return array('code'=>'1','msg'=>'','data'=>[]);
        }catch(\Exception $e){
            return array('code'=>'0','msg'=>$e->getMessage(),'data'=>[]);
        }

    }
    function getTimeWeek($time, $i = 0) {
        $weekarray = array("日","一", "二", "三", "四", "五", "六");
        $oneD = 24 * 60 * 60;
        return "周" . $weekarray[date("w", $time + $oneD * $i)];
    }

    /***
     *
     */
    function getmtaplNum($etprsId=0)
    {
        $con=array();
        if(!empty($etprsId)){
             $con["etprsId"]=$etprsId;
        }
        $con["startTime"]=array("gt",time());
        $con["status"]=0;
        $msg=findById("OaMeetroomApl",$con,"count(id) as num");
        $msg["num"]=0;
        if(!empty($msg['data'])){
             $msg["num"]=$msg["data"]["num"];
        }
        return $msg;
    }
}