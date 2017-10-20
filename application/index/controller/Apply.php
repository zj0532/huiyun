<?php
namespace app\index\controller;
use app\index\controller\Common;
use think\Db;
use think\Exception;
use think\Log;
use app\common\controller\Baseapply;

class Apply extends Common{

    /***
     *配置流程的，获取下一次的状态
     */
    function getNextStatus($status)
    {
        if($status=='1001000'){
            return $status;
        }
        $arr=array(
            "apllist"=>1001011,
            "batchapl"=>1001012,
            "enterapl"=>1001013,
            "roomdstb"=>1001014,
            "enteriqbt"=>1001015
        );
        $iqbtId=session("iqbtId");
        $stepmsg=findById("enterStep",array("iqbtId"=>$iqbtId),"apllist,batchapl,enterapl,roomdstb,enteriqbt");
        if(!empty($stepmsg['data'])){
            $steps=$stepmsg['data'];
            foreach ($steps as $k=>$v){
                $tmpstatus=$arr[$k];
                if($tmpstatus>=$status&&$v=='1'){
                    return $tmpstatus;
                }
            }
        }else{
            return $status;
        }
    }



    //
    function etprsroomapl($msg=""){
        return view("",array("msg"=>$msg));
    }

    //企业：新增房间申请
    function getEtprsRoomApl($etprsId=0)
    {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=getDataList("etprsAplRoom",array("a.etprsId"=>$etprsId,'a.iqbtId'=>session("iqbtId")),"a.*,b.name as etprsName","a.addtime desc",$join);
        if($msg["code"]==="1"){
            if(!empty($msg["data"])){
                for ($i = 0; $i < count($msg['data']); $i++) {
                    $msg['data'][$i]["addtime"]=date("Y-m-d",$msg['data'][$i]["addtime"]);
                }
            }else{
                return array();
            }
            return $msg["data"];
        }else{
            return array();
        }
    }

    //申请新增房间
    function addroomapl($id=0)
    {
        $etprsId=session("etprsId");
        $emsg=findById("enterprise",array("id"=>$etprsId),"status");

        if(!empty($emsg["data"])){
            if($emsg["data"]["status"]!="1001016"){
                return view("etprsroomapl",["msg"=>"企业状态错误，不能申请"]);
            }
        }else{
            return view("");
        }

        $data=array();
        if(!empty($id)){
            $msg=findById("etprsAplRoom",array("id"=>$id),"*");
            if(isset($msg["data"])&&!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        $join = [['estate_room b','a.roomId=b.id',"left"]];
        $roommsg=getDataList("EstateRoomEtprs",array("a.etprsId"=>$etprsId,"a.status"=>array("<>",2),'a.iqbtId'=>session("iqbtId")),"sum(b.totalarea) as area","a.id",$join);
        $data["currarea"]=$roommsg["data"][0]["area"];
        $roomNos = '';
        $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId")),"id,roomNo");
        if(!empty($msg["data"])){
            foreach($msg["data"] as $no){
                $roomNos.=",".$no["roomNo"];
            }
        }
        $roomNos=trim($roomNos,",");
        $data["roomNo"]=$roomNos;
        return view("",array("data"=>$data));
    }

    function saveRoomApl()
    {
        $etprsId=session("etprsId");
        $iqbtId=session("iqbtId");
        $userId=session("userId");
        $postData=input("request.");
        $postData["etprsId"]=$etprsId;
        $postData["adduserId"]=$userId;
        $postData["iqbtId"]=$iqbtId;
        $postData["addtime"]=time();
        $res = saveData("etprsAplRoom",$postData,"申请增加房间");
        if($res['code']==1){
            if(empty($postData['id'])){
                $etprsName = getField('enterprise',array('id'=>$etprsId),'name');
                $wxData = array();
                if(config('open_wechat')){
                    $template_id = config('wx_tpl.newApply');
                    $wxData = array(
                        'tpl'=>$template_id,
                        'data'=>array(
                            'keyword1'=>$etprsName,
                            'keyword2'=>'申请加租房间'
                        ),
                        'first'=>'申请加租房间通知',
                        'remark'=>'请登录系统，及时查看处理'
                    );
                }
                //发送站内信
                $optIds = getAdminIds('8',false);
                $emailData = array(
                    'title'=>'新增房间申请',
                    'content'=>'您好，有新的申请：'.$etprsName.'申请新增房间，请登录系统及时审核处理',
                    'type'=>'1020002',
                    'relTable'=>'etprsAplRoom',
                    'relId'=>$res['data'],
                    'stype'=>1,
                );
                $this->sendAllMsg($optIds,$emailData,$wxData);
            }

        }
        return $res;

    }

    function deltRoomApl($id)
    {
        return deleteData("etprsAplRoom",$id,"删除新增房间申请");
    }

    //获取所有入驻申请
    function getApllist($etprs="",$contact="",$apltype="",$type="apl")
    {
        $base = new Baseapply();
        $result = $base->getApllist($etprs,$contact,$apltype,$type);
        return $result;

    }
    //查看申请详情
    function roomaplDetail($id=0)
    {
        if(!empty($id)){
            $join = [['enterprise b','a.etprsId=b.id',"left"]];
            $msg=findById("etprsAplRoom",array("a.id"=>$id),"b.name as etprsname,b.entertime,b.rgsttime,b.lealPerson,a.*",$join);
            $data=array();
            if($msg["code"]==='1'){
                $data=$msg["data"];
            }
            return view("",array("data"=>$data));
        }else{
            return view("",array("data"=>array()));
        }
    }
    function etprsaplDetail($type,$id=0)
    {
        $v=$type."aplDetail";
        $etprsId=0;
        if(!empty($id)){
            $join = [['enterprise b','a.etprsId=b.id',"left"]];
            $msg=findById("etprsApl",array("a.id"=>$id),"b.name as etprsname,a.*",$join);
            $data=array();
            if($msg["code"]==='1'){
                $data=$msg["data"];
                $etprsId=$data["etprsId"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $data=$this->setObjIdText($data,$tmplist);
            }
            return view($v,array("data"=>$data,"etprsId"=>$etprsId));
        }else{
            return view($v,array("data"=>array(),"etprsId"=>$etprsId));
        }
    }
    //发送审核短息通知
    function sendCheckMsg($mobile,$tpl_id,$info=array()){

        $res = sendSms($mobile,$info['msg'],$tpl_id);
        if ($res['code'] == "1") {
            //把该条数据保存到数据库
            //发送成功,做记录
            $data = array(
                'mobile' => $mobile,
                'msg' => $info['code'],
                'type' => $info['type'],
                'addtime' => time(),
                'adduserId'=>session('userId'),
                'iqbtId'=>session('iqbtId'),
            );
            saveData('SmsLog', $data);
            return array('code' => '1', 'msg' => '', 'data' => '发送成功');
        } else {
            return array('code' => '0', 'msg' => $res['msg'], 'data' => '');
        }
    }

    function setAplStatus($table,$id,$status,$sms="1"){
        $apply = new Baseapply();
        $res = $apply->setAplStatus($table,$id,$status,$sms);
        return $res;
    }

    //企业材料初审退出
    function setFirstBack($etprsId=0,$status="")
    {
        $emsg=findById("enterprise",array("id"=>$etprsId),"id,name");
        if(!empty($emsg["data"])){
            $etprsName=$emsg["data"]["name"];
        }else{
            $etprsName = '';
        }
        return view("",array("id"=>$etprsId,"status"=>$status,"etprsName"=>$etprsName));
    }
    //点击材料初审退回保存按钮
    function passFirstBack(){
        $postData=input("request.");
        if(empty($postData['desc'])){
            return array('code'=>0,'msg'=>'退出理由不能为空');
        }
        if(empty($postData['id'])){
            return array('code'=>'0','msg'=>'参数错误');
        }else{
            $id = $postData['id'];
        }
        //保存退回备注
        $note["etprsId"]=$id;
        $note['aplId'] = getField('etprsApl',array('etprsId'=>$id),'id');
        $note["content"]=$postData["desc"];
        $note["status"]='1001011';
        $note["iqbtId"]=session("iqbtId");
        $note["addtime"]=time();
        $note["adduserId"]=session("userId");
        $note["type"]=4;
        saveData("etprsAplNote",$note,"材料初审退回备注");

        if(isset($postData['sms'])&& $postData['sms'] ==1){
            $sms = 1;
        }else{
            $sms = 0;
        }
        $status = '1001000';
        $table = 'enterprise';
        $apply = new Baseapply();
        $res = $apply->setAplStatus($table,$id,$status,$sms);
        return $res;

    }

    //续约管理，审核操作
    function setRenewStatus($table,$id,$status,$sms="0")
    {
        $base = new Baseapply();
        $res = $base->setRenewStatus($table,$id,$status,$sms);
        return $res;
    }



    //确认续约以后，确认房间缴费类型
    function renewRoom($id=0){
        $aplmsg=findById("etprsAplRenew",array('id'=>$id),"startTime,endTime,roomNo,etprsId");
        if(!empty($aplmsg["data"])) {
            $roomNo = $aplmsg["data"]["roomNo"];
            $roomMsg = getDataList("estateRoom", array('roomNo' => array("in", explode(",", $roomNo))), "*");
        }
        if($roomMsg['code']==1 &&!empty($roomMsg['data'])){
            $roomdata = $roomMsg['data'];
        }else{
            $roomdata = array();
        }
        foreach($roomdata as $key=> $value){
            //剔除工位
            if($value['type']==1){
                if(!empty($value['feeOptIds'])){
                    $optNames = getFieldArrry('feeItemOpt',array('id'=>array('in',explode(",",$value['feeOptIds']))),'name');
                    $roomdata[$key]['optNames'] = implode(",",$optNames);
                }
            }else{
                unset($roomdata[$key]);
            }

        }
        //header("Content-Type: text/html;charset=utf-8");
       // print_r($roomdata);exit();
        return view("",array('data'=>$roomdata));
    }
    //确认续约以后，更改房间缴费类型 $id  房间ID
    function renewRoomOpt($id=0){

        //查找续约的缴费项目
        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $imsg=getDataList("feeItemCfg",array("a.feetype"=>"1030002",'a.iqbtId'=>session("iqbtId"),'b.about'=>'1'),"a.id,a.itemId,a.optId,b.name as itemName","",$join);
        $items=array();
        if(!empty($imsg["data"])){
            $items=$imsg["data"];
        }
        $roomMsg = findById('estateRoom',array('id'=>$id),'*');
        if(!empty($roomMsg['data'])){
            $roomdata = $roomMsg['data'];
        }else{
            $roomdata = array();
        }
        //header("Content-Type: text/html;charset=utf-8");
        // print_r($roomdata);exit();
        return view("",array('data'=>$roomdata,'items'=>$items));
    }
    function saveRenewRoomOpt(){
        $postData=input("request.");
        if(isset($postData['optId'])){
            $optIds = trim(implode(",",$postData['optId']),",");
        }
        $msg = saveDataByCon("EstateRoom",array('feeOptIds'=>$optIds),array("id"=>$postData["id"]));
        return $msg;
    }

    // 复审通知,获取所有入驻申请
    function getBatchApl($etprs="",$contact="",$apltype="")
    {
        $base = new Baseapply();
        $res = $base->getBatchApl($etprs,$contact,$apltype);
        return $res;
    }

    //创建复审批次
    function setBatch($ids="",$names="")
    {
        if(empty($names)){
            $names="没有选择需要复审通知的申请";
        }else{
            $names=trim($names,",");
        }
        return view("",array("names"=>$names,"ids"=>join(",",$ids)));
    }

    //保存批次，
    function saveBatch(){
        $postData=input("request.");
        $sms = input('sms','0');
        if(!isset($postData['ids']) || empty($postData['ids'])){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        if(!isset($postData['batch'])){
            $postData['batch'] = '';
        }
        if(!isset($postData['batchTime'])){
            $postData['batchTime'] = '';
        }
        if(!isset($postData['batchAddress'])){
            $postData['batchAddress'] = '';
        }
        if(!isset($postData['batchRemark'])){
            $postData['batchRemark'] = '';
        }
        $base = new Baseapply();
        $res = $base->saveBatch($postData,$sms);
        return $res;
    }

    //入孵申请的企业信息
    function exportApply(){
        $ids = input('id');
        $types = input('type');
        $filename = "入孵申请表";
        $this->export($ids,$filename,$types);
    }
    //导出复审通知的企业信息
    function exportRetrial(){
        $ids = input("id");
        $filename = "复审通知表";
        $this->export($ids,$filename);
    }

    //导出要答辩的企业信息
    function exportBatch(){

        $ids = input("id");
        $filename = "导师复审表";
        $this->export($ids,$filename);
    }
    //导出同意入驻
    function exportEnter(){
        $ids = input('id');
        $filename = "同意入驻表";
        $this->export($ids,$filename);
    }
    //房间分配表
    function exportRoom(){
        $ids = input("id");
        $types = input('type');
        $filename = "房间分配表";
        $this->export($ids,$filename,$types,"1");
    }
    //孵化入驻
    function exportIqbt(){
        $ids = input("id");
        $filename = "孵化入驻表";
        $this->export($ids,$filename,"","1");
    }
    /*
     * $ids         id号
     * $filename    入孵申请表
     * $types       类型
     * $isRoom
     */
    function export($ids,$filename,$types='',$isRoom="0"){
        if(empty($ids)){
            return array('code'=>0,'msg'=>'请选择要导出的数据');
        }
        if(empty($types)){
            //$typs为空，代表只有入驻申请，没有房间申请
            $con2=array('b.id'=>array('in',$ids));
            $etprsapl=array();
            $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];

            $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.type,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId,b.addtime","b.addtime desc",$join2);
            if(!empty($etprsaplmsg["data"])){
                $etprsapl=$etprsaplmsg["data"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $etprsapl=$this->setListIdText($etprsapl,$tmplist);
            }
            $info =  $etprsapl;
        }else{
            $arrId = explode(",",$ids);
            $arrType = explode(",",$types);
            $roomIds = array();
            $aplIds = array();
            foreach($arrId as $key=>$value){
                if($arrType[$key] =="seated"){
                    $roomIds[] = $value;
                }else{
                    $aplIds[] = $value;
                }
            }
            if(!empty($aplIds)){
                $strIds = implode(",",$aplIds);
                $con2=array('b.id'=>array('in',$strIds));
                $etprsapl=array();
                $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];
                $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.type,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId,b.addtime","b.addtime desc",$join2);

                if(!empty($etprsaplmsg["data"])){
                    $etprsapl=$etprsaplmsg["data"];
                    $tmplist=self::getDictStr("*","EtprsApl");
                    $etprsapl=$this->setListIdText($etprsapl,$tmplist);
                }
            }else{
                $etprsapl = array();
            }

            if(!empty($roomIds)){
                $strRoom = implode(",",$roomIds);
                $con1 = array('a.id'=>array('in',$strRoom));
                $roomapl = array();
                $join1 = [['enterprise b','a.etprsId=b.id',"left"],["etprsApl c",'a.etprsId=c.etprsId',"left"]];
                $roomaplmsg=getDataList("etprsAplRoom",$con1,"a.id,b.`name` as etprsName,'roomapl' as apltype,b.contact,a.mobile,c.industry,c.workstyle,'seated' as type,a.etprsId,a.addtime","a.addtime desc",$join1);
                if(!empty($roomaplmsg["data"])){
                    $roomapl=$roomaplmsg["data"];
                    $tmplist=self::getDictStr("*","EtprsApl");
                    $roomapl=$this->setListIdText($roomapl,$tmplist);
                }
            }else{
                $roomapl = array();
            }
            $info = array_merge($roomapl,$etprsapl);
        }
        if($isRoom =="1"){
            for ($i = 0; $i < count($info); $i++) {
                $etprsId=$info[$i]["etprsId"];
                $roomNos="";
                $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId),"id,roomNo");
                if(!empty($msg["data"])){
                    foreach($msg["data"] as $no){
                        $roomNos.=",".$no["roomNo"];
                    }
                }
                $roomNos=trim($roomNos,",");
                $info[$i]["roomNos"]=$roomNos;
            }
        }
        $data = array();
        foreach($info as $key=>$value){
            $data[$key]['etprsName'] = $value['etprsName'];
            if($value['apltype'] =='0'){
                $data[$key]['apltype'] = '企业入驻';
            }elseif($value['apltype']=='1'){
                $data[$key]['apltype'] = '团队入驻';
            }elseif($value['apltype']=='roomapl'){
                $data[$key]['apltype'] = '加租房间';
            }else{
                $data[$key]['apltype'] = '未知类型';
            }
            $data[$key]['contact'] = $value['contact'];
            $data[$key]['mobile'] = $value['mobile'];
            $data[$key]['industry'] = $value['industryText'];
            $data[$key]['worktype'] = $value['workstyleText'];
            if($isRoom =="1"){
                $data[$key]['roomNos'] = $value['roomNos'];
            }
            $data[$key]['time']  = date("Y-m-d",$value['addtime']);
        }
        if($isRoom =="1"){
            $header = array('企业名称','申请类型','联系人','联系电话','行业类型','办公方式','已分配房间','申请时间');
        }else{
            $header = array('企业名称','申请类型','联系人','联系电话','行业类型','办公方式','申请时间');
        }
        vendor("PHPExcel");
        vendor("PHPExcel.Writer.Excel5");
        vendor("PHPExcel.IOFactory");
        getExcel($filename,$header,$data);
    }

    //复审通知，退回页面
    function setetprsback($ids="",$names="")
    {
        if(empty($names)){
            $names="没有选择需要退回的申请";
        }else{
            $names=trim($names,",");
        }
        return view("",array("names"=>$names,"ids"=>join(",",$ids),"status"=>"1001012"));
    }

    //这是在复审通知的时候的退回
    function setAplBack()
    {
        $postData=input("request.");
        if(!isset($postData['content']) || empty($postData['content'])){
            return array('code'=>'0','msg'=>'请输入退回原因','data'=>array());
        }
        if(!isset($postData['ids']) || empty($postData['ids'])){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        $postData['status'] = '1001012'; //保存备注的时候用到了，
        $base = new Baseapply();
        $res = $base->setAplBack($postData);
        return $res;
    }

    //导师复审，单独拿出来，已经复审过的过滤掉，不再显示
    function getTutorReApl($etprs="",$contact=""){
        $base = new Baseapply();
        $res = $base->getTutorReApl($etprs,$contact);
        return $res;
    }

    //同意入驻
    function getRetrialApl($etprs="",$contact=""){
        $base = new Baseapply();
        $res = $base->getRetrialApl($etprs,$contact);
        return $res;
    }


    //复审详情页
    /**
     * @param int $id  申请ID
     * @param string $type  企业申请类型
     * @return array|\think\response\View
     */
    function retrial($id=0,$type="etprs")
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->retrial($id);

        $data = $res['data'];
        $note = $res['note'];
        $grade = $res['grade'];
        $etprsId = $res['etprsId'];
        return view("",array("data"=>$data,"type"=>$type,"note"=>$note,'grade'=>$grade,'etprsId'=>$etprsId));
    }

    function saveRetrialInfo()
    {
        $postData=input("request.");
        $noteId = '0';
        if(isset($postData['id'])){
            $noteId = $postData['id'];
            unset($postData['id']);
        }
        $content = '';
        if(isset($postData['content'])){
            $content = $postData['content'];
            unset($postData['content']);
        }
        if(!isset($postData['aplId'])){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        $aplId = $postData['aplId'];
        unset($postData['aplId']);
        $base = new Baseapply();
        $res = $base->saveRetrialInfo($postData,$aplId,$content,$noteId);
        return $res;
    }

    //同意入驻，查看入驻详情，评分信息
    function enterAplInfo($id=0,$type="etprs")
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->enterAplInfo($id);
        $res['type'] = $type;
        $checktutor=getField("enterStep",array("iqbtId"=>session("iqbtId")),"retrialapl",'1');
        if(!empty($checktutor)){
            $tutorflag=1;
        }else{
            $tutorflag = 0;
        }
        $res['tutorflag'] = $tutorflag;

        return view("",$res);

    }

    //同意入驻，同意或者拒绝操作
    function saveEnterNote()
    {
        $postData=input("request.");
        if(isset($postData['id']) && !empty($postData['id'])){
            $aplId = $postData['id'];
        }else{
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        if(isset($postData['content'])){
            $content = $postData['content'];
        }else{
            $content = '';
        }
        if(isset($postData['status'])){
            $status = $postData['status'];
        }else{
            $status = '1001014';
        }
        if(isset($postData['sms'])){
            $sms = $postData['sms'];
        }else{
            $sms = '0';
        }
        $base = new Baseapply();
        $res = $base->saveEnterNote($aplId,$content,$status,$sms);
        return $res;
    }

    //房间分配，获取待分配的列表
    function getRoomDitbApl($etprs="",$contact="",$apltype="",$type="apl")
    {
        $base = new Baseapply();
        $res = $base->getRoomDitbApl($etprs,$contact,$apltype,$type);
        return $res;
    }

    //分配房间页面
    function distrib($id)
    {
        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $imsg=getDataList("feeItemCfg",array("a.feetype"=>"1030001",'a.iqbtId'=>session("iqbtId"),'b.about'=>'1'),"a.id,a.itemId,a.optId,b.name as itemName","",$join);
        $items=array();
        if(!empty($imsg["data"])){
            $items=$imsg["data"];
        }
        return view("",array("id"=>$id,"items"=>$items,'iqbtId'=>session('iqbtId')));
    }
    //初始化楼层,传楼的ID
    function initFloor($id="")
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->initFloor($id);
        return $res;
    }
    //初始化房间
    function initFloorRoom($id="",$floor="")
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'请输入楼的ID','data'=>array());
        }
        if(empty($floor)){
            return array('code'=>'0','msg'=>'请输入具体楼层','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->initFloorRoom($id,$floor);
        return $res;
    }

    //单个房间的信息,已经分配有企业的房间
    function initetprsroom($id='')
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'请输入房间ID','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->initetprsroom($id);
        return $res;
    }

    //未分配的房间，点击进行分配
    function initemptyroom($id,$etprsId)
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'请传入房间ID','data'=>array());
        }
        if(empty($etprsId)){
            return array('code'=>'0','msg'=>'企业ID不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->initemptyroom($id,$etprsId);
        return $res;
    }

    //分配操作，保存分配房间
    function dstbEtprsRoom(){

        $postData=input("request.");
        /* postData 必须包含的以下4个字段
         * postData = array(
            'startTime'=>'开始时间，字符串类型的，因为在公共函数里用了strtotime()进行了转换,如，2017-09-28',
            'endTime' =>'结束时间,也是字符串类型的，',
            'roomId'  =>'房间ID',
            'etprsId' =>'企业Id',

        )*/
        if(!isset($postData['startTime']) || empty($postData['startTime'])){
            return array('code'=>'0','msg'=>'请选择开始时间','data'=>array());
        }
        if(!isset($postData['endTime']) || empty($postData['endTime'])){
            return array('code'=>'0','msg'=>'请选择结束时间','data'=>array());
        }
        if(!isset($postData['roomId'])){
            return array('code'=>'0','msg'=>'缺少参数roomId','data'=>array());
        }
        if(!isset($postData['etprsId'])){
            return array('code'=>'0','msg'=>'缺少参数etprsId','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->dstbEtprsRoom($postData);
        return $res ;
    }

    //分配未使用的房间，取消重置 ； 过期的房间，释放重置
    function roomCancel($roomid=''){

        if(empty($roomid)){
            return array('code'=>0,'msg'=>'参数错误','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->roomCancel($roomid);
        return $res;
    }

    //签约入驻 ，获取列表页
    function getEnterApl($etprs="",$contact="",$apltype="",$status="1001015")
    {
        $base = new Baseapply();
        $res = $base->getEnterApl($etprs,$contact,$apltype,$status);
        return $res;
    }


    //企业端查看复审信息
    function gradeInfo($id){
        if(empty($id)){
            return array('code'=>'0','msg'=>'申请ID不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->gradeInfo($id);
        //var_dump($res);

        return view("",$res);
    }

    function etprsAplInfo($id=0,$type="etprs",$etprsId=0) {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }
        $data=array();
        $notes=array();
        if(empty($id)){
            $amsg=findById("etprsApl",array("etprsId"=>$etprsId),"id",array());
            if(!empty($amsg["data"])){
                 $id=$amsg["data"]["id"];
            }
        }
        if(!empty($id)){
            $join = [['enterprise b','a.etprsId=b.id',"left"]];
            $msg=findById("etprsApl",array("a.id"=>$id),"b.name as etprsname,b.batch,b.batchTime,b.batchAddress,b.batchRemark,b.status,a.*",$join);
            if($msg["code"]==='1'){
                $data=$msg["data"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $data=$this->setObjIdText($data,$tmplist);
            }

            $join2 = [['enterprise b','a.etprsId=b.id',"left"],['user c','a.adduserId=c.id',"left"]];
            $aplmsg=getDataList("etprsAplNote",array("a.aplId"=>$id,'a.iqbtId'=>session("iqbtId"),'a.type'=>array('in',['1','2','4'])),"a.id,a.aplId,a.type,a.content,b.name as etprsName,c.realname as userName,a.etprsId,a.status,a.addtime","a.id desc",$join2);

            if($aplmsg["code"]=='1'){
                if(!empty($aplmsg['data'])){
                    $notes=$aplmsg["data"][0];
                }
            }

            $roomNos="";
            $etprsId=$data["etprsId"];
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId")),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.=",".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,",");
            $data["roomNos"]=$roomNos;
        }
        $steps=[];
        $stepsmsg=findById("EnterStep",array("iqbtId"=>session("iqbtId")));
        if(!empty($stepsmsg["data"])){
            $steps=$stepsmsg["data"];
        }
        return view("",array("data"=>$data,"type"=>$type,"notes"=>$notes,"etprsId"=>$etprsId,'steps'=>$steps));
    }

    function setEtprsTutor($etprsId)
    {
        $msg=findById("enterprise",array("id"=>$etprsId),"id,tutorIds");
        if(!empty($msg["data"])){
            $ids=$msg["data"]["tutorIds"];
        }
        return view("",array("etprsId"=>$etprsId,"tutorIds"=>$ids));
    }

    //签约入驻，导师分配，保存导师信息
    function saveEtprsTutor($etprsId,$tutorIds)
    {
        if(empty($etprsId)){
            return array('code'=>'0','msg'=>'企业ID不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->saveEtprsTutor($etprsId,$tutorIds);
        return $res;
    }

    //申请新增房间
    function addrenewapl($id=0)
    {
        if (request()->isPost()){
            $etprsId=session("etprsId");

            $iqbtId=session("iqbtId");
            $userId=session("userId");
            $postData=input("request.");
            $postData["etprsId"]=$etprsId;
            $postData["adduserId"]=$userId;
            $postData["iqbtId"]=$iqbtId;
            $postData["addtime"]=time();
            $postData["roomNo"]=join(",",$postData["roomNo"]);

            $postData["startTime"]=strtotime($postData["startTime"]);
            $postData["endTime"]=strtotime($postData["endTime"]);
            unset($postData["sltopt"]);
            $msg=saveData("etprsAplRenew",$postData,"申请续约");
            if($msg['code']==1){
                //只有在新增的时候发消息，编辑不再发送
                if(empty($postData['id'])){
                    $etprsName = getField('enterprise',array('id'=>$etprsId),'name');
                    $wxData = array();
                    //给管理员发送微信通知
                    if(config('open_wechat')){
                        $template_id = config('wx_tpl.newApply');
                         $wxData = array(
                             'tpl'=>$template_id,
                             'data'=>array(
                                 'keyword1'=>$etprsName,
                                 'keyword2'=>'等待处理'
                             ),
                             'first'=>'续约申请通知',
                             'remark'=>'请登录系统，及时查看处理'
                         );
                    }
                    //发送站内信
                    $emailData = array(
                        'title'=>'续约申请',
                        'content'=>'您好，有新的申请：'.$etprsName.'申请续约，请登录系统及时审核处理',
                        'type'=>'1020002',
                        'relTable'=>'etprsAplRenew',
                        'relId'=>$msg['data'],
                        'stype'=>7,
                    );
                    $optIds = getAdminIds('36',false);
                    $this->sendAllMsg($optIds,$emailData,$wxData);
                }

            }
            return $msg;
        }else{
            $etprsId=session("etprsId");
            $entertime=date("Y-m-d",time());
            $rgsttime=date("Y-m-d",time());
            $emsg=findById("enterprise",array("id"=>$etprsId),"status,name,id,entertime,rgsttime,pactquittime");
            if(!empty($emsg["data"])){
                $etprs=$emsg["data"];
                if($emsg["data"]["status"]!="1001016"){
                    return view("etprsroomapl",["msg"=>"企业状态错误，不能申请"]);
                }
                if(!empty($emsg["data"]["rgsttime"])){
                    $rgsttime=$emsg["data"]["rgsttime"];
                }
                if(!empty($emsg["data"]["entertime"])){
                    $entertime=date("Y-m-d",$emsg["data"]["entertime"]);
                }
                if(!empty($emsg["data"]["entertime"])){
                    $starttime=strtotime("+1 day",$emsg["data"]["pactquittime"]);
                }

            }else{
                return view("");
            }

            $data=array();
            if(!empty($id)){
                $msg=findById("etprsAplRenew",array("id"=>$id),"*");
                if(isset($msg["data"])&&!empty($msg["data"])){
                    $data=$msg["data"];
                }
            }

            $data["settled"]=$entertime;
            $data["rgsttime"]=$rgsttime;
            if(empty($data["startTime"])){
                $data["startTime"]=$starttime;
            }
            if(empty($data["endTime"])){
                $data["endTime"]=strtotime("+1 year",$data["startTime"]-86400);
            }
            list($y1,$m1,$d1)=explode("-",date("Y-m-d",$data["startTime"]));
            list($y2,$m2,$d2)=explode("-",date("Y-m-d",$data["endTime"]+86400));
            $ylen=$y2-$y1;
            $mlen=$m2-$m1;
            $len=$ylen*12+$mlen;
            $d=$d2-$d1;
            if($mlen>0){
                if ($d < 0)
                {
                    $len -= 1;
                }
            }
            $data["months"]=$len;
            return view("",array("data"=>$data,"etprs"=>$etprs));
        }
    }

    function initEndtime($startTime='',$months=0)
    {
        if(!empty($startTime)&&!empty($months)){
            return date("Y-m-d",strtotime("-1 day",strtotime("+".$months." month",strtotime($startTime))));
        }else{
            return date("Y-m-d",time());
        }
    }
    function getEtprsRenewApl()
    {
        $etprsId = input('etprsId',0);
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $cate=session("user.userCate");
        $con=array('a.iqbtId'=>session("iqbtId"));
        $con["a.etprsId"]=$etprsId;
       /* if($cate=="1011002"){
            //获取当前企业所有申请
            $con["a.etprsId"]=$etprsId;
        }else{
            //获取当前所有申请（待审核）
            $con["status"]="1027001";
        }*/
        $msg=getDataList("etprsAplRenew",$con,"a.*,b.name as etprsName","a.addtime desc",$join);
        if($msg["code"]==="1"){
            if(!empty($msg["data"])){
                for ($i = 0; $i < count($msg['data']); $i++) {
                    $msg['data'][$i]["addtime"]=date("Y-m-d",$msg['data'][$i]["addtime"]);
                    $msg['data'][$i]["startTime"]=date("Y-m-d",$msg['data'][$i]["startTime"]);
                    $msg['data'][$i]["endTime"]=date("Y-m-d",$msg['data'][$i]["endTime"]);
                    $tmplist=self::getDictStr("*","EtprsAplRenew");
                    $msg['data']=$this->setListIdText($msg['data'],$tmplist);
                }
            }else{
                return array();
            }

            return $msg["data"];
        }else{
            return array();
        }
    }

    function getEtprsQuitApl($etprsId=0)
    {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $cate=session("user.userCate");
        $con=array('a.iqbtId'=>session("iqbtId"));
        $con["a.etprsId"]=$etprsId;
       /* if($cate=="1011002"){
            //获取当前企业所有申请
            $con["a.etprsId"]=$etprsId;
        }else{
            //获取当前所有申请（待审核）
            $con["status"]="1027001";
        }*/
        $msg=getDataList("etprsAplQuit",$con,"a.*,b.name as etprsName,b.entertime,b.rgsttime","a.addtime desc",$join);
        if($msg["code"]==="1"){
            if(!empty($msg["data"])){
                for ($i = 0; $i < count($msg['data']); $i++) {
                    $msg['data'][$i]["addtime"]=date("Y-m-d ",$msg['data'][$i]["addtime"]);
                    $msg['data'][$i]["entertime"]=date("Y-m-d ",$msg['data'][$i]["entertime"]);
                   // $msg['data'][$i]["startTime"]=date("Y-m-d",$msg['data'][$i]["startTime"]);
                  //  $msg['data'][$i]["endTime"]=date("Y-m-d",$msg['data'][$i]["endTime"]);
                    $tmplist=self::getDictStr("*","EtprsAplQuit");
                    $msg['data']=$this->setListIdText($msg['data'],$tmplist);
                }
            }else{
                return array();
            }

            return $msg["data"];
        }else{
            return array();
        }
    }
    function getRenewApl($status='1027001')
    {
        $base = new Baseapply();
        $res = $base->getRenewApl($status);
        return $res;
    }
    function deltRenewApl($id)
    {
        return deleteData("etprsAplRenew",$id,"删除续约申请");
    }

    function renewdetail($id=0)
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'申请ID不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->renewdetail($id);
        return view("",array('data'=>$res));
    }


    //申请退出
    function addquitapl($id=0)
    {
        $etprs=array();
        $etprsId=session("etprsId");
        $emsg=findById("enterprise",array("id"=>$etprsId),"status,name,id");

        if(!empty($emsg["data"])){
            $etprs=$emsg["data"];
            if($emsg["data"]["status"]!="1001016"){
                return view("etprsroomapl",["msg"=>"企业状态错误，不能申请"]);
            }
        }else{
            return view("");
        }

        $data=array();
        if(!empty($id)){
            $msg=findById("etprsAplQuit",array("id"=>$id),"*");
            if(isset($msg["data"])&&!empty($msg["data"])){
                $data=$msg["data"];
            }
        }

        $roomNos="";
        $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId")),"id,roomNo");
        if(!empty($msg["data"])){
            foreach($msg["data"] as $no){
                $roomNos.=",".$no["roomNo"];
            }
        }
        $roomNos=trim($roomNos,",");
        $data["roomNos"]=$roomNos;
        return view("",array("data"=>$data,"etprs"=>$etprs));
    }

 /*   function saveEtprsquitapl()
    {
        $etprsId=session("etprsId");
        $iqbtId=session("iqbtId");
        $userId=session("userId");
        $postData=input("request.");
        $postData["etprsId"]=$etprsId;
        $postData["adduserId"]=$userId;
        $postData["iqbtId"]=$iqbtId;
        $postData["addtime"]=time();
        if(isset($postData["types"])&&$postData["types"]=="1"){
            //强制退出直接审核通过
            $postData["status"]="1028002";
        }
        $msg=saveData("etprsAplQuit",$postData,"申请退出");
        if($msg["code"]==='1'){
                //给管理员发送微信通知
                $etprsName = getField('enterprise',array('id'=>$etprsId),'name');
                $wxData = array();
                if(config('open_wehchat')){
                    $template_id = config('wx_tpl.newApply');
                    $wxData = array(
                        'tpl'=>$template_id,
                        'data'=>array(
                            'keyword1'=>$etprsName,
                            'keyword2'=>'等待处理'
                        ),
                        'first'=>'退出申请通知',
                        'remark'=>'请登录系统，及时查看处理'
                    );
                }
                //发送站内信
                $emailData = array(
                    'title'=>'退出申请',
                    'content'=>'您好，有新的申请：'.$etprsName.'申请退出，请登录系统及时审核处理',
                    'type'=>'1020002',
                );
                $optIds = getAdminIds('33',false);
                $this->sendAllMsg($optIds,$emailData,$wxData);
            }
        return $msg;
    }
*/
    //强制退出
    function addfcsquitapl($id=0)
    {
        if (request()->isPost()){
            $iqbtId=session("iqbtId");
            $userId=session("userId");
            $postData=input("request.");
            $postData["adduserId"]=$userId;
            $postData["iqbtId"]=$iqbtId;
            $postData["addtime"]=time();
            $postData['quitdate'] = date("Y-m-d",time());
            if($postData["types"]=="1"){
                //强制退出直接审核通过
                $postData["status"]="1028002";
                $postData["adminUserId"]=session("userId");
            }
            $msg=saveData("etprsAplQuit",$postData,"强制退出");
            if($msg["code"]==='1'){
                $etprsId=$postData["etprsId"];
                //企业设置为孵化完成
                if(!empty($etprsId)){
                    $data=array();
                    $data["id"]=$etprsId;
                    $data["status"]="1001017";
                    $data["quittime"]=time();
                    saveData("enterprise",$data);
                }

                //房间空置
                if(!empty($etprsId)){
                    $room=array();
                    $room["status"]="0";
                    $room["etprsId"]="0";
                    saveDataByCon("estateRoom",$room,array("etprsId"=>$etprsId));
                    $eroom=array();
                    $eroom["status"]="2";
                    saveDataByCon("estateRoomEtprs",$eroom,array("etprsId"=>$etprsId));
                }
                //工作日志，强制清退要记录谁清退的
                $logData = array(
                    'etprsId'=>$etprsId,
                    'fmenuId'=>15,
                    'smenuId'=>33,
                    'content'=>'强制清退了该企业',
                );
                saveOaLog($logData);

            }
            return $msg;
        }else{
            $data=array();
            if(!empty($id)){
                $msg=findById("etprsAplQuit",array("id"=>$id),"*");
                if(isset($msg["data"])&&!empty($msg["data"])){
                    $data=$msg["data"];
                }
            }

            return view("",array("data"=>$data));
        }
    }

    function deltQuitApl($id)
    {
        return deleteData("etprsAplQuit",$id,"删除退出申请");
    }

    //退出管理，查看退出详情
    function quitdetail($id=0)
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->quitdetail($id);
        return view("",array('data'=>$res));
    }

    /**
     * 退出管理 获取退出企业列表
     * @param string $status 状态：1028001：管理员待审核，1028002：物业待审核, 1028003:财务待审核， 1028004：待退款（所有审核都通过的）
     * @return array
     */
    function getQuitApl($status="1028001")
    {
        $base = new Baseapply();
        $res = $base->getQuitApl($status);
        return $res;
    }

    function saveQuitApl()
    {
        $postData=input("request.");

        $iqbtId=session("iqbtId");
        $etprsId=session("etprsId");
        $postData["iqbtId"]=$iqbtId;
        $postData["etprsId"]=$etprsId;
        $postData["addtime"]=time();
        $postData["adduserId"]=session("userId");
        $msg=saveData("etprsAplQuit",$postData,"申请退出");
        if($msg['code']==1){
            //给管理员发送微信通知
            $etprsName = getField('enterprise',array('id'=>$etprsId),'name');
            $wxData = array();
            if(config('open_wehchat')){
                $template_id = config('wx_tpl.newApply');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$etprsName,
                        'keyword2'=>'等待处理'
                    ),
                    'first'=>'退出申请通知',
                    'remark'=>'请登录系统，及时查看处理'
                );
            }
            //发送站内信
            $emailData = array(
                'title'=>'退出申请',
                'content'=>'您好，有新的申请：'.$etprsName.'申请退出，请登录系统及时审核处理',
                'type'=>'1020002',
                'relTable'=>'etprsAplQuit',
                'relId'=>$msg['data'],
                'stype'=>8
            );
            $optIds = getAdminIds('33',false);
            $this->sendAllMsg($optIds,$emailData,$wxData);
        }
        return $msg;
    }

    //退出管理，管理员通过、拒绝操作
    function passQuitApl()
    {
        $postData=input("request.");
        if(!isset($postData['id']) || empty($postData['id'])){
            return array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array());
        }
        if(!isset($postData['status']) || empty($postData['status'])){
            return array('code'=>'0','msg'=>'审核状态不能为空','data'=>array());
        }
        $quitId = $postData['id'];
        $status = $postData['status'];
        $adminDesc = '';
        if(isset($postData['adminDesc'])){
            $adminDesc = $postData['adminDesc'];
        }
        $base = new Baseapply();
        $res = $base->passQuitApl($quitId,$status,$adminDesc);
        return $res;
    }

    //退出管理,管理员审核通过或者拒绝
    function setQuitApl($id=0,$status="")
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array());
        }
        if(empty($status)){
            return array('code'=>'0','msg'=>'审核状态不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->setQuitApl($id,$status);
        return view("",$res);
    }

    //强制清退企业的时候用的,获取企业相关的房间信息和法人信息
    function initEtprsRoomNos($etprsId='')
    {
        if(empty($etprsId)){
            return array("code"=>0,"msg"=>"企业ID错误",'data'=>array());
        }
        $base = new Baseapply();
        $res = $base->initEtprsRoomNos($etprsId);
        return $res;
    }

    //退出管理，物业管理员退出管理
    function setEstateQuitApl($id=0,$status="")
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array());
        }
        if(empty($status)){
            return array('code'=>'0','msg'=>'审核状态不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->setEstateQuitApl($id,$status);
        return view("",$res);
    }


    function saveEstateQuitApl()
    {
        $postData=input("request.");
        if(!isset($postData['id']) || empty($postData['id'])){
            return array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array());
        }
        if(!isset($postData['status']) || empty($postData['status'])){
            return array('code'=>'0','msg'=>'审核状态不能为空','data'=>array());
        }
        $quitId = $postData['id'];
        $status = $postData['status'];
        $estateDesc = '';
        if(isset($postData['estateDesc'])){
            $estateDesc = $postData['estateDesc'];
        }
        $base = new Baseapply();
        $res = $base->saveEstateQuitApl($quitId,$status,$estateDesc);
        return $res;
    }

    //退出管理，财务退出操作
    function setFiceQuitApl($id='',$status='')
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array());
        }
        if(empty($status)){
            return array('code'=>'0','msg'=>'审核状态不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->setFiceQuitApl($id,$status);
        $items = array();
        $res['items'] = $items;
        return view("",$res);
    }

    //退出管理，财务人员通过，同时生成退费记录
    function saveFiceQuitApl()
    {
        $postData=input("request.");
        if(!isset($postData['id']) || empty($postData['id'])){
            return array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array());
        }
        if(!isset($postData['status']) || empty($postData['status'])){
            return array('code'=>'0','msg'=>'审核状态不能为空','data'=>array());
        }
        $quitId = $postData['id'];
        $status = $postData['status'];
        $ficeDesc = '';
        if(isset($postData['ficeDesc'])){
            $ficeDesc = $postData['ficeDesc'];
        }
        $base = new Baseapply();
        $res = $base->saveFiceQuitApl($quitId,$status,$ficeDesc);
        return $res;
    }


    function getEtprsPact($etprsId=0)
    {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }
        $base = new Baseapply();
        $res = $base->getEtprsPact($etprsId);
        return $res;
    }

    function addPact($etprsId=0,$id=0)
    {
        if(empty($etprsId)){
             $etprsId=session("etprsId");
        }
        $data=array();
        if(!empty($id)){
            $msg=findById("pact",array("a.id"=>$id),"*");
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        return view("",array("etprsId"=>$etprsId,"data"=>$data));
    }

    function saveEtprsPact(){
        $pact=input("request.");
        $pact["iqbtId"]=session("iqbtId");
        $pact["addtime"]=time();
        $pact["adduserId"]=session("userId");
        $msg=saveData("pact",$pact,"添加合同");
        return $msg;
    }
    function deltPact($id)
    {
        if(empty($id)){
            return array('code'=>'0','msg'=>'合同ID不能为空','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->deltPact($id);
        return $res;
    }

    //签约入驻，确定入驻，设置入驻时间
    function setEnterTime($etprsId='')
    {
        if(empty($etprsId)){
            return array('code'=>'0','msg'=>'参数错误','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->setEnterTime($etprsId);
        return view("",$res);
    }

    //签约入驻，保存入驻时间
    function saveEnterTime(){
        $data=input("request.");
        if(!isset($data['id']) || empty($data['id'])){
            return array('code'=>'0','msg'=>'企业ID不能为空','data'=>array());
        }
        if(!isset($data['entertime']) || empty($data['entertime'])){
            return array('code'=>'0','msg'=>'入驻时间不能为空','data'=>array());
        }
        if(!isset($data['pactquittime']) || empty($data['pactquittime'])){
            return array('code'=>'0','msg'=>'退出时间不能为空','data'=>array());
        }
        $etprsId=$data["id"];
        $ettime=strtotime($data["entertime"]);
        $qttime=strtotime($data["pactquittime"]);
        $sms = 0;
        if(isset($data['sms'])){
            $sms = $data['sms'];
        }
        $base = new Baseapply();
        $res = $base->saveEnterTime($etprsId,$ettime,$qttime,$sms);
        return $res ;
    }

    function checkApl($etprsIqbtId=0,$iqbtId=0)
    {
        return view("",array("iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId));
    }

    function checkAplIqbt($etprsIqbtId=0,$iqbtId=0)
    {
        $iqbtName="";
        $iqbts=array();
        if(!empty($iqbtId)){
             $msg=findById("incubator",array("id"=>$iqbtId),"id,name");
            if(!empty($msg["data"])){
                $iqbtName=$msg["data"]["name"];
            }
        }else{
            if(!empty($etprsIqbtId)){
                $msg=getDataList("incubator",array("etprsIqbtId"=>$etprsIqbtId),"id,name");
                if(!empty($msg["data"])){
                     if(count($msg["data"])==1){
                         $iqbtName=$msg["data"][0]["name"];
                     }elseif(count($msg["data"])>1){
                         $iqbts=$msg["data"];
                     }else{
                         $this->redirect(url("/user/Login/login",array("iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,"msg"=>"没有可选择的孵化器")));
                     }
                }
            }
        }

        return view("",array("iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,"iqbtName"=>$iqbtName,"iqbts"=>$iqbts));
    }

    //完善申请信息
    function etprsapl(){
        $data = array();
        //获取入孵申请信息
        $aplmsg=findById("EtprsApl",array("etprsId"=>session("etprsId")),"*",array(),"0","id desc");
        if(!empty($aplmsg["data"])){
             if($aplmsg["data"]["apltype"]=="1"){
                 $this->redirect(url("/index/Apply/teamapl"));
             }
            $aplId=$aplmsg["data"]["id"];
            $data = $aplmsg['data'];
        }else{
            $this->redirect(url("/user/Login/login"));
        }
        /*$iqbtmsg=getDataList("incubator",array(),"id,name");
        $iqbts=array();
        if($iqbtmsg["code"]==='1'&&!empty($msg["data"])){
            $iqbts=$msg["data"];
        }*/
        return view("",array("id"=>$aplId,'data'=>$data));
    }

    function saveetprsapl()
    {
        $etprsId=session("etprsId");
        $postData=input("request.");
        if(is_array($postData['worktype'])){
            $postData['worktype'] = implode(",",$postData['worktype']);
        }
        $postData["etprsId"]=$etprsId;
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $postData["type"]="etprs";
        $msg= saveData("etprsApl",$postData,"申请入驻");
        if($msg["code"]==='1'){
            $aplId=$postData["id"];
            if(!empty($aplId)){
                deleteByCon("etprsApl",array("id"=>array("<>",$aplId),"etprsId"=>session("etprsId"),'iqbtId'=>session("iqbtId")),"删除其它申请");
            }
            //保存信息到etprs_info表里
            $data = array();
            $data['highetprs'] = $postData['highetprs'];
            $data['industry'] = $postData['industry'];
            $data['technical'] = $postData['technical'];
            $data['taxpayertype'] = $postData['taxpayertype'];
            $data['worktype'] = $postData['worktype'];
            $data['total'] = $postData['total'];
            $data['doctor'] = $postData['doctor'];
            $data['junior'] = $postData['junior'];
            $data['thousand'] = $postData['thousand'];
            $data['student'] = $postData['student'];
            $data['iqbtId'] = session("iqbtId");
            $data['etprsId'] = $etprsId;
            $data["adduserId"]=session("userId");
            $data['rgstment'] = $postData['capital'];
            $data['lealPerson'] = $postData['lealPerson'];
            $data['charter'] = $postData['charter'];
            $data["addtime"]=time();
            $etprsInfo=findById("EtprsInfo",array("etprsId"=>$etprsId),"id");
            if(!empty($etprsInfo["data"])){
                $data["id"]=$etprsInfo["data"]["id"];
            }
            saveData('etprsInfo',$data,'企业维护信息初始值');
            $m1=saveData("enterprise",array("id"=>$etprsId,"lealPerson"=>$postData["leader"],'contact'=>$postData['leader'],'rgsttime'=>$postData['rgsttime'],'name'=>$postData["name"],"status"=>"1001011",'total'=>$postData['total']),"将企业对应的IqbtId修改");
            $m2=saveData("user",array("status"=>"1012001","id"=>session("userId"),'realname'=>$postData['leader'],'email'=>$postData['email']),"将登录用户设置为已申请的正常用户");
            //获取当前用户所在总孵化器，并获取权限
            $imsg=findById("incubator",array("id"=>session("iqbtId")),"etprsIqbtId");
            if(!empty($imsg["data"])){
                session("etprsIqbtId",$imsg["data"]["etprsIqbtId"]);
            }
            //发送消息，使用新的封装的函数
            $wxData = array();
            if(config('open_wechat')) {
                $keysArr = array(
                    'keyword1' => $postData['name'],
                    'keyword2' => '入孵申请'
                );
                $template_id = config('wx_tpl.newApply');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>$keysArr,
                    'first'=>'新项目入孵申请',
                    'remark'=>'请登录系统，及时查看处理'
                );
            }
            $emailData = array(
                'title'=>'新入孵申请',
                'content'=>'您好，'.$postData['name'].'申请入驻孵化器，请登录系统及时审核处理',
                'type'=>'1020002',
                'relTable'=>'enterprise',
                'relId'=>$etprsId,
                'stype'=>1
            );
            $adminIds = getAdminIds('8',false);
            $this->sendAllMsg($adminIds,$emailData,$wxData);


            /*
            if(config('open_wechat')){
                //给管理员发送微信通知
                $adminIds = getAdminIds();
                $push = new wechatPush();
                $push->newApply($adminIds,$postData['name']);
            }

            //发送站内信
            $optIds = getAdminIds('8',false);
            $info = array(
                'title'=>'新的入孵申请',
                'content'=>'您好，有新的申请：'.$postData['name'].'申请入驻孵化器，请登录系统及时审核处理',
                'type'=>'1020002',
                'addtime'=>time()
            );
            sendEmail($optIds,$info,session("userId"));
            */
        }

        //首次登陆，缓存logo、孵化器名称,二维码等相关信息
        $userMsg = findById('user',array('id'=>session('userId')),'*');
        if($userMsg['code']==1 && !empty($userMsg['data'])){
            $user = $userMsg['data'];
            //获取logo并缓存
            $logo = '/img/111.jpg';
            //获取孵化器的名字 并缓存
            if(strpos(','.$user["roleIds"].',',',1,')!==false && !empty($user["etprsIqbtId"])){
                //孵化器企业管理员，应该读取孵化器企业的名字
                $name = getField('etprsIqbt',array('id'=>$user['etprsIqbtId']),'name');

            }elseif(!empty($user['iqbtId'])){
                $name = getField('incubator',array('id'=>$user['iqbtId']),'name');
                if(!empty($user['logo'])){
                    $logo = getField('sysFile',array('id'=>$user['logo']),'savePath');
                }

            }else{
                $name = "中联慧云孵化器管理系统";
            }
            session('sysName',$name);
            session('logo',$logo);
            //  print_r($user);exit();
            session('user', $user);
        }


        return $msg;
    }

    function teamapl()
    {
        $data = array();
        $aplmsg=findById("EtprsApl",array("etprsId"=>session("etprsId")),"*",array(),"0","id desc");
        if(!empty($aplmsg["data"])){
            if($aplmsg["data"]["apltype"]=="0"){
                $this->redirect(url("/index/Apply/etprsapl"));
            }
            $aplId=$aplmsg["data"]["id"];
            $data = $aplmsg['data'];

        }else{
            $this->redirect(url("/user/Login/login"));
        }

        /*$iqbtmsg=getDataList("incubator",array(),"id,name");
        $iqbts=array();
        if($iqbtmsg["code"]==='1'&&!empty($msg["data"])){
            $iqbts=$msg["data"];
        }*/
        return view("",array("id"=>$aplId,'data'=>$data));
    }

    function saveteamapl()
    {
        $etprsId=session("etprsId");
        $postData=input("request.");
        if(is_array($postData['worktype'])){
            $postData['worktype'] = implode(",",$postData['worktype']);
        }
        $postData["etprsId"]=$etprsId;
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $postData["type"]="team";
        $msg= saveData("etprsApl",$postData,"申请入驻");
        if($msg["code"]==='1'){
            if(isset($postData["id"])&&!empty($postData["id"])) {
                $aplId = $postData["id"];
                deleteByCon("etprsApl", array("id" => array("<>", $aplId), "etprsId" => session("etprsId"), 'iqbtId' => session("iqbtId")), "删除其它申请");
            }
            //保存信息到etprs_info表里
            $data = array();
            $data['industry'] = $postData['industry'];
            $data['technical'] = $postData['technical'];
            $data['worktype'] = $postData['worktype'];
            $data['total'] = $postData['total'];
            $data['doctor'] = $postData['doctor'];
            $data['junior'] = $postData['junior'];
            $data['thousand'] = $postData['thousand'];
            $data['student'] = $postData['student'];
            $data['iqbtId'] = session("iqbtId");
            $data['etprsId'] = $etprsId;
            $data["adduserId"]=session("userId");
            $data["addtime"]=time();
            $etprsInfo=findById("EtprsInfo",array("etprsId"=>$etprsId),"id");
            if(!empty($etprsInfo["data"])){
                $data["id"]=$etprsInfo["data"]["id"];
            }
            saveData('etprsInfo',$data,'企业维护信息初始值');
            saveData("enterprise",array("id"=>$etprsId,"lealPerson"=>$postData["leader"],'contact'=>$postData['leader'],'name'=>$postData["name"],"status"=>"1001011",'total'=>$postData['total']),"将企业对应的IqbtId修改");
            saveData("user",array("status"=>"1012001","id"=>session("userId"),'realname'=>$postData['leader'],'email'=>$postData['email']),"将登录用户设置为已申请的正常用户");
            //session("etprsIqbtId",$postData["iqbtId"]);
            $imsg=findById("incubator",array("id"=>session("iqbtId")),"etprsIqbtId");
            if(!empty($imsg["data"])){
                session("etprsIqbtId",$imsg["data"]["etprsIqbtId"]);
            }

            //发送消息，使用新的封装的函数
            $wxData = array();
            if(config('open_wechat')) {
                $keysArr = array(
                    'keyword1' => $postData['name'],
                    'keyword2' => '入孵申请'
                );
                $template_id = config('wx_tpl.newApply');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>$keysArr,
                    'first'=>'新项目入孵申请',
                    'remark'=>'请登录系统，及时查看处理'
                );
            }
            $emailData = array(
                'title'=>'新入孵申请',
                'content'=>'您好，'.$postData['name'].'申请入驻孵化器，请登录系统及时审核处理',
                'type'=>'1020002',
                'relTable'=>'enterprise',
                'relId'=>$etprsId,
                'stype'=>1
            );
            $adminIds = getAdminIds('8',false);
            $this->sendAllMsg($adminIds,$emailData,$wxData);

           /* if(config('open_wechat')){
                //给管理员发送微信通知
                  $adminIds = getAdminIds();
                  $push = new wechatPush();
                  $push->newApply($adminIds,$postData['name']);
            }

            //发送站内信
            $optIds = getAdminIds('8',false);
            $info = array(
                'title'=>'新的入孵申请',
                'content'=>'您好，有新的申请：'.$postData['name'].'申请入驻孵化器，请登录系统及时审核处理',
                'type'=>'1020002',
                'addtime'=>time()
            );
            sendEmail($optIds,$info,session("userId"));
           */
        }
        //首次登陆，缓存logo、孵化器名称,二维码等相关信息
        $userMsg = findById('user',array('id'=>session('userId')),'*');
        if($userMsg['code']==1 && !empty($userMsg['data'])){
            $user = $userMsg['data'];
            //获取logo并缓存
            $logo = '/img/111.jpg';
            //获取孵化器的名字 并缓存
            if(strpos(','.$user["roleIds"].',',',1,')!==false && !empty($user["etprsIqbtId"])){
                //孵化器企业管理员，应该读取孵化器企业的名字
                $name = getField('etprsIqbt',array('id'=>$user['etprsIqbtId']),'name');

            }elseif(!empty($user['iqbtId'])){
                $name = getField('incubator',array('id'=>$user['iqbtId']),'name');
                if(!empty($user['logo'])){
                    $logo = getField('sysFile',array('id'=>$user['logo']),'savePath');
                }

            }else{
                $name = "中联慧云孵化器管理系统";
            }
            session('sysName',$name);
            session('logo',$logo);
            //  print_r($user);exit();
            session('user', $user);
        }

        return $msg;
    }

    function addEtprsAplPdt($id=0)
    {
        $pdt=array();
        if(!empty($id)){
            $msg=findById("etprsAplProduct",array("id"=>$id),"id,pdtname,pdtdesc");
            if(!empty($msg["data"])){
                $pdt=$msg["data"];
            }
        }
        return view("",array("data"=>$pdt));
    }

    function saveAplPdt()
    {
        $postData=input("request.");
        $postData["etprsId"]=session("etprsId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("iqbtId");
        $table="etprsAplProduct";
        $msg= saveData($table,$postData,"添加/修改");
        return $msg;
    }

    function getPdts($etprsId=0)
    {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }

        $con=array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId"));
        $table="etprsAplProduct";
        $msg=getDataList($table,$con,"id,pdtname,pdtdesc");
        if(empty($msg["data"])){
            return array();
        }else{
            return $msg["data"];
        }
    }

    function dlteAplPdt($id)
    {
        return deleteData("etprsAplProduct",$id,"删除申请产品");
    }

    function setEtprsIqbt($iqbtId,$apltype)
    {
        $userId=session("userId");
        $etprsId=session("etprsId");
        $data=array("apltype"=>$apltype,"iqbtId"=>$iqbtId,"etprsId"=>$etprsId,'addtime'=>time());
        $msg=findById("EtprsApl",array("etprsId"=>$etprsId),"id");
        if(!empty($msg["data"])){
            $data["id"]=$msg["data"]["id"];
        }
        $amsg=saveData("EtprsApl",$data);
        if(!empty($amsg["data"])){
            saveDataByCon("user",array("status"=>"1012004","iqbtId"=>$iqbtId),array("id"=>$userId));
            saveDataByCon("enterprise",array("iqbtId"=>$iqbtId,"status"=>"1001002",'apltype'=>$apltype),array("id"=>$etprsId));
            saveDataByCon("etprsInfo",array("iqbtId"=>$iqbtId),array("etprsId"=>$etprsId));
            session("iqbtId",$iqbtId);
            $imsg=findById("incubator",array("id"=>session("iqbtId")),"etprsIqbtId");
            if(!empty($imsg["data"])){
                session("etprsIqbtId",$imsg["data"]["etprsIqbtId"]);
            }
            return $amsg;
        }else{
            return $amsg;
        }
    }

    //签约入驻阶段的取消入驻
    function setCancel($id='',$sms="2"){

        if(empty($id)){
            return array('code'=>0,'msg'=>'参数错误','data'=>array());
        }
        $base = new Baseapply();
        $res = $base->setCancel($id,$sms);
        return $res;
    }



    function exportEtprsApply($etprsId=0){
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("etprsApl",array("a.etprsId"=>$etprsId),"b.name as etprsname,a.*",$join);
        $data=array();
        if($msg["code"]==='1'){
            $data=$msg["data"];
            $tmplist=self::getDictStr("*","EtprsApl");
            $data=$this->setObjIdText($data,$tmplist);
        }

        vendor("PHPWord");
        vendor("PHPWord.IOFactory");

        // New Word Document
        $PHPWord = new \PHPWord();

        // New portrait section
        $PHPWord->addParagraphStyle('pStyle', array('align' => 'center'));
        $PHPWord->addFontStyle('titleStyle', array('bold' => true, 'color' => '000000', 'size' => 16));
        //首页 ---start------------------------------------------------------------------------------------------------------------------------------------
        $section = $PHPWord->createSection();
        $section->addTextBreak(1);
        $section->addTextBreak(1);
        $PHPWord->addFontStyle('rStyle', array('bold' => true, 'color' => '000000', 'size' => 22));
        $PHPWord->addParagraphStyle('pStyle', array('align' => 'center'));
        $section->addText($data['etprsname'], 'rStyle', 'pStyle');
        $section->addTextBreak(2);

        // Add table
        $styleTable = array('borderSize' => 0, 'borderColor' => '000000','cellMargin' => 80);
        $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);
        $table = $section->addTable('myOwnTableStyle');
        $fontStyle = array('bold' => true, 'align' => 'right','size'=>11);
        $fontStyle2 = array('bold' => false, 'align' => 'left','size'=>11);

        // Add more rows / cells
        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("企业名称", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['etprsname'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("希望入驻时间", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['planintime'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("入驻期限(年)", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['timeline'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("需求面积（㎡）", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['area'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("需求办公方式", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['workstyleText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("法人代表", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['lealPerson'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("注册资本(万元)", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['capital'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("纳税人类型", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['taxpayertypeText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("营业执照", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['charter']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("上一年度财务报表", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['lastficereport']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("是否高新企业", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['highetprs']==1?"是":"否",$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("高新企业认定时间", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText(empty($data['highetprstime'])?"":$data['highetprstime'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("高新企业认定证书", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['highetprscert']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("行业类型", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['industryText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("技术领域", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['technicalText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("负责人", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['leader'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("性别", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['sexText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("联系方式", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['mobile'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("邮箱", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['email'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("身份证号", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['idcard'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('valign' => "right"))->addText("最高学历", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['eduText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("身份证", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['idcartfile']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("学历证", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['edufile']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("主要负责人创业特征", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['worktypeText'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'restart','valign' => "right"))->addText("人员情况", $fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText("人员数量（人）",$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText("博士（人）",$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText("大专以上（人）",$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText("千人计划（人）",$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText("吸纳应届大学生（人）",$fontStyle);

        $table->addRow(400);
        $table->addCell(0, array('rowMerge' => 'continue'));
        $table->addCell(1280, array('valign' => "center"))->addText($data['total'],$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText($data['doctor'],$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText($data['junior'],$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText($data['thousand'],$fontStyle);
        $table->addCell(1280, array('valign' => "center"))->addText($data['student'],$fontStyle);

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'restart','valign' => "right"))->addText("主要产品及服务", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "center"))->addText("产品名称",$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("说明",$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $pdts=self::getPdts($etprsId);
        if(count($pdts)>0){
            foreach ($pdts as $pdt) {
                $table->addRow(400);
                $table->addCell(0, array('rowMerge' => 'continue'));
                $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "center"))->addText($pdt["pdtname"],$fontStyle2);
                $table->addCell(0, array('cellMerge' => 'continue'));
                $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center"))->addText($pdt["pdtdesc"],$fontStyle2);
                $table->addCell(0, array('cellMerge' => 'continue'));
                $table->addCell(0, array('cellMerge' => 'continue'));
            }
        }else{
            $table->addRow(400);
            $table->addCell(0, array('rowMerge' => 'continue'));
            $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "center"))->addText("没有记录",$fontStyle2);
            $table->addCell(0, array('cellMerge' => 'continue'));
            $table->addCell(0, array('cellMerge' => 'continue'));
            $table->addCell(0, array('cellMerge' => 'continue'));
            $table->addCell(0, array('cellMerge' => 'continue'));
        }







        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("项目计划书", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['projectdesc']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("知识产权", $fontStyle);
        $cell=$table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"));
        $filepath=self::initwordfile($data['patent']);
        if(!empty($filepath)){
            $cell->addImage($filepath,array('width'=>400, 'height'=>250,'align'=>'left'));
        }else{
            $cell->addText("");
        }
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('valign' => "right"))->addText("其它未尽事项说明", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "left"))->addText($data['desc'],$fontStyle2);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        //Add image
        /*$section->addImage('logo.jpg', array('width'=>100, 'height'=>100,'align'=>'right'));*/

        /*$objWrite = \PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
        $objWrite->save('word/tmp/' . time() . '.docx');*/


        /**设置在浏览器下载**/
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control:must-revalidate， post-check=0， pre-check=0');
        header('Content-Type:application/force-download');
        header('Content-Type:application/vnd.ms-word');
        header('Content-Type:application/octet-stream');
        header('Content-Type:application/download');
        header('Content-Disposition:attachment;filename=' . $data['etprsname']."入驻申请信息" . '.docx');
        header('Content-Transfer-Encoding:binary');
        $objWriter = \PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
        $objWriter->save('php://output');
    }

    function initwordfile($fileid)
    {
        if(!empty($fileid)){
            $filepath=getField("SysFile",array("id"=>$fileid),"savePath");
            if(is_file("public/".$filepath)){
                $ispic=self::isImage("public/".$filepath);
                if($ispic){
                    return "public/".$filepath;
                }else{
                    return "";
                }
            }else{
                return "";
            }
        }else{
            return "";
        }
    }
    function isImage($filename){
        $types = '.gif|.jpeg|.png|.bmp';//定义检查的图片类型
        if(file_exists($filename)){
            $info = getimagesize($filename);
            $ext = image_type_to_extension($info['2']);
            return stripos($types,$ext);
        }else{
            return false;
        }
    }

}