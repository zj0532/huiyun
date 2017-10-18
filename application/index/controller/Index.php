<?php
namespace app\index\controller;
use think\Db;
use app\common\controller\Base;
class Index extends Common{

//注释//注释
//注释
    //阿里云发送短信接口
    function test(){
        $base = new Base();
        $base->base();
        exit();

        $url = __ROOT__;echo $url;exit();
        import('alsms.Samples.Sms.sms',EXTEND_PATH);
        $instance = new \PublishBatchSMSMessageDemo();
        $con = array(
            'iqbtId'=>session('iqbtId'),
            'status'=>'1001016',
            'id'=>array('in',['73','71','72'])
        );
        $mobileArr = getFieldArrry('enterprise',$con,'mobile');
      //  print_r($mobileArr);exit();
        $data = array(
            'name'=>'财税公开课',
            'time'=>'2017-08-23 —— 2017-08-26'
        );
        $tpl = config('sms_tpl_id.activity');
        $res = $instance->run($mobileArr,$data,$tpl);
        if($res =='1'){
            echo '发送成功ddd'."<br/>";
            print_r($res);
        }else{
            print_r($res);
        }

    }

    function index()
    {
        $roleId=session("user.roleIds");
        $roleMsg = findByid('UserRole',array('id'=>$roleId),'*');
        if($roleMsg['code']==1 && !(empty($roleMsg['data']))){
            $homepageIds = $roleMsg['data']['homepageIds'];
        }
        if(!empty($homepageIds)){
            $roleArr = explode(",",$homepageIds);
        }else{
            $roleArr = array();
        }
        $moduleArr = array(
            'apl'=>'7',
            'reapl'=>'8',
            'room'=>'10',
            'roomin'=>'11',
            'sch_dan'=>'3',
            'sch_imp'=>'2',
            'sch_com'=>'1',
            'notice_estate'=>'4',
            'notice_work'=>'5',
            'notice_other'=>'6'
        );
        foreach($moduleArr as $key=>$value){
            if(in_array($value,$roleArr)){
                $data[$key]['status'] = 1;
            }else{
                $data[$key]['status'] = 0;
            }
        }
        //待审核
        $con1=array("a.status"=>"1027001",'a.iqbtId'=>session("iqbtId"));
        $con2=array("a.status"=>"1001011",'a.iqbtId'=>session("iqbtId"));
        $join1 = [['enterprise b','a.etprsId=b.id',"left"],["etprsApl c",'a.etprsId=c.etprsId',"left"]];
        $roomaplmsg=getDataList("etprsAplRoom",$con1,"a.id","a.addtime desc",$join1);
        if(!empty($roomaplmsg["data"])){
            $num1=count($roomaplmsg["data"]);
        }else{
            $num1 = 0;
        }
        $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];
        $etprsaplmsg=getDataList("enterprise",$con2,"b.id","b.addtime desc",$join2);
        if(!empty($etprsaplmsg["data"])){
            $num2=count($etprsaplmsg["data"]);
        }else{
            $num2 = 0;
        }
        $data['apl']['num'] = $num1+$num2;
        //待复审
        $con3 = array("a.status"=>"1001013",'a.iqbtId'=>session("iqbtId"));
        $etprsapl=getDataList("enterprise",$con3,"b.id","b.addtime desc",$join2);
        if(!empty($etprsapl["data"])){
            $num3=count($etprsapl["data"]);
        }else{
            $num3 = 0;
        }
        $data['reapl']['num'] = $num3;
        //待分配房间
        $con4=array("a.status"=>"1027002",'a.iqbtId'=>session("iqbtId"));
        $roomaplmsg4=getDataList("etprsAplRoom",$con4,"a.id","a.addtime desc",$join1);
        if(!empty($roomaplmsg4["data"])){
            $num4=count($roomaplmsg4["data"]);
        }else{
            $num4 = 0;
        }
        $con5=array("a.status"=>"1001014",'a.iqbtId'=>session("iqbtId"));
        $etprsaplmsg5=getDataList("enterprise",$con5,"b.id","b.addtime desc",$join2);
        if(!empty($etprsaplmsg5["data"])){
            $num5=count($etprsaplmsg5["data"]);
        }else{
            $num5 = 0;
        }
        $data['room']['num'] = $num4+$num5;
        //待入驻
        $con6=array("a.status"=>"1001015",'a.iqbtId'=>session("iqbtId"));
        $etprsaplmsg6=getDataList("enterprise",$con6,"b.id","b.addtime desc",$join2);
        if(!empty($etprsaplmsg6["data"])){
            $num6=count($etprsaplmsg6["data"]);
        }else{
            $num6 = 0;
        }
        $data['roomin']['num'] = $num6;
        //当前日程
        $start=strtotime(date("Y-m-d",time()));
        $end=strtotime(date("Y-m-d",strtotime("+1 day")))-1;
        $sch1=getField("userSchedule",array("addUserId"=>session("userId"),'color'=>'#ed5565','startTime'=>array('BETWEEN',[$start,$end])),"count(id) as c",'0');
        $sch2=getField("userSchedule",array("addUserId"=>session("userId"),'color'=>'#f8ac59','startTime'=>array('BETWEEN',[$start,$end])),"count(id) as c",'0');
        $sch3=getField("userSchedule",array("addUserId"=>session("userId"),'color'=>'#5CB85C','startTime'=>array('BETWEEN',[$start,$end])),"count(id) as c",'0');
        $data['sch_dan']['num'] = $sch1;//紧急日程
        $data['sch_imp']['num'] = $sch2;//重要
        $data['sch_com']['num'] = $sch3;//普通
        $data['time'] = time();
        //会议室申请 1020003
        $room_num = getField('SysMsg',array('touserId'=>session('userId'),'type'=>'1020003','iqbtId'=>session('iqbtId'),'status'=>0),"count(id) as c",'0');
        $data['notice_estate']['num'] = $room_num;
        //内部资源通知1020004
        $res_num = getField('SysMsg',array('touserId'=>session('userId'),'type'=>'1020004','iqbtId'=>session('iqbtId'),'status'=>0),"count(id) as c",'0');
        $data['notice_work']['num'] = $res_num;
        //其他消息  全部的减去上面两个
        $total =  getField('SysMsg',array('touserId'=>session('userId'),'iqbtId'=>session('iqbtId'),'status'=>0),"count(id) as c",'0');
        $data['notice_other']['num'] = $total - $res_num - $room_num;
        return view('',array('data'=>$data));
    }
    function initfiles()
    {
        $postData=input("request.");
        if(!empty($postData)){
            $ids=array();
            foreach ($postData as $k => $v) {
                $ids[]=$v;
            }
            $msg=getDataList("sysFile",array("id"=>array("in",$ids)),"id,savePath,fileName");
            if(!empty($msg["data"])){
                $files=$msg["data"];
            }

            if(!empty($files)){
                foreach ($files as $file) {
                    foreach ($postData as $f=>$v) {
                        if($v==$file["id"]){
                            $data[$f]=$file["savePath"];
                        }
                    }
                }
                return $data;
            }
        }
        return array();
    }

    /**
     * 检查数据表中字段数据是否唯一
     */
    function checkUniqe(){
        $table=input("table");
        $field=input("field");
        $val=input("val");
        $id=input("id");
        if(!empty($id)){//修改
            $con["id"]=array("neq",$id);
        }
        $con["isDelete"]=0;
        $con[$field]=$val;
        return $this->chkUniqe($table,$con);
    }
    function checkiqbtUniqe(){
        $table=input("table");
        $field=input("field");
        $val=input("val");
        $id=input("id");
        if(!empty($id)){//修改
            $con["id"]=array("neq",$id);
        }
        $con["isDelete"]=0;
        $con[$field]=$val;
        $iqbtId=session("iqbtId");
        if(!empty($iqbtId)){
            $con["iqbtId"]=$iqbtId;
        }
        return $this->chkUniqe($table,$con);
    }

    //设置企业
    function setEtprsId()
    {
        $etprsId=input("etprsId");
        if(empty($etprsId)){
            return "0";
        }else{
            session("etprsId",$etprsId);
            $etprsname=getField("enterprise",array("id"=>$etprsId),"name");
            session("etprsName",$etprsname);
            return "1";
        }
    }
//-------------TEMPLATE----------------------------------------TEMPLATE-----------------------------------------TEMPLATE-----------------------------------------TEMPLATE----------------------------
    //主页
    /*function main(){
        $actmsg=getDataList("activity",array("endtime"=>array("gt",time()),'close'=>0,'iqbtId'=>session("user.iqbtId")),"id,name");
        if($actmsg["code"]==='1'){
            $acts=$actmsg["data"];
        }else{
            $acts=array();
        }
        $newsmsg=getDataList("SysNotice",array('type'=>0,'iqbtId'=>session("user.iqbtId")),"id,title","addtime desc");
        if($newsmsg["code"]==='1'){
            $news=$newsmsg["data"];
        }else{
            $news=array();
        }
        $noticemsg=getDataList("SysNotice",array('type'=>1,'iqbtId'=>session("user.iqbtId")),"id,title","addtime desc");
        if($noticemsg["code"]==='1'){
            $notices=$noticemsg["data"];
        }else{
            $notices=array();
        }
        return view("main",array("acts"=>$acts,'news'=>$news,'notices'=>$notices));
    }*/

    //添加日历时间
    function addDayEvent()
    {
        return view();
    }
    //form表单
    function  formbase(){
        $this->assign("data",array());
        return view();
    }
    //form表单
    function  tablebase(){
        return view();
    }

    function tablejson()
    {
        $json= '[{"Tid":"1","First":"奔波儿灞","sex":"男","Score":"50"},{"Tid":"2","First":"灞波儿奔","sex":"男","Score":"94"},{"Tid":"3","First":"作家崔成浩","sex":"男","Score":"80"},{"Tid":"4","First":"韩寒","sex":"男","Score":"67"},{"Tid":"5","First":"郭敬明","sex":"男","Score":"100"},{"Tid":"6","First":"马云","sex":"男","Score":"77"},{"Tid":"7","First":"范爷","sex":"女","Score":"87"},{"Tid":"3","First":"作家崔成浩","sex":"男","Score":"80"},{"Tid":"4","First":"韩寒","sex":"男","Score":"67"},{"Tid":"5","First":"郭敬明","sex":"男","Score":"100"},{"Tid":"6","First":"马云","sex":"男","Score":"77"},{"Tid":"7","First":"范爷","sex":"女","Score":"87"}]';
        return json_decode($json,true);
    }

    //孵化器管理
    function getIqbt()
    {
        $etprsIqbtId=session("etprsIqbtId");
        $msg=getDataList("incubator",array('etprsIqbtId'=>$etprsIqbtId),"a.*","a.createtime desc");
        if($msg["code"]==="1"){
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addIqbt($etprsIqbtId='0')
    {
        $id=input("id");
        if(empty($etprsIqbtId)){
            $etprsIqbtId=session("etprsIqbtId");
        }
        $c=array();
        if(!empty($id)){
            $msg=findByid("incubator",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c,'etprsIqbtId'=>$etprsIqbtId));
    }
    function saveIqbt(){
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="incubator";
        return saveData($table,$postData,"添加/修改孵化器");
    }
    function deltIqbt(){
        $id=input("id");
        $msg= deleteData("incubator",$id,"删除孵化器");
        if($msg["code"]==="1"){
            saveDataByCon("User",array("isDelete"=>1),array('iqbtId'=>$id));
        }
        return $msg;
    }

    function checkIqbt($id)
    {
        $user=session("user");
        $user["iqbtId"]=$id;
        session("user",$user);
        session('iqbtId', $id);
        $this->redirect(url('/Index/index'));
    }
    function getScheduleCount()
    {
        /*$userId=session("userId");
        $msg=findById("userSchedule",array('adduserId'=>$userId,'endTime'=>array('gt',time())),"count(id) as c");
        if(!empty($msg["data"])){
             return $msg["data"]["c"];
        }*/
        //当前日程统计
        $start=strtotime(date("Y-m-d",time()));
        $end=strtotime(date("Y-m-d",strtotime("+1 day")))-1;
        $sch=getField("userSchedule",array("addUserId"=>session("userId"),'iqbtId'=>session("iqbtId"),'startTime'=>array('BETWEEN',[$start,$end])),"count(id) as c",'0');
        return $sch;
    }
    function getMsgCount()
    {
        $userId=session("userId");
        $msg=findById("SysMsg",array('toUserId'=>$userId,'status'=>0),"count(id) as c");
        if(!empty($msg["data"])){
            return $msg["data"]["c"];
        }
    }
    function getNoticeCount()
    {
        $userId=session("userId");
        $msg=findById("Notes",array('adduserId'=>$userId),"count(id) as c");
        if(!empty($msg["data"])){
            return $msg["data"]["c"];
        }else{
            return 0;
        }
    }


    //消息
    function msg($stat='',$type='',$recycle='')
    {
        $flags=array('navy','danger','primary','info','warning','muted');
        $con=array("level"=>2,'code'=>array('like',"1020%"));
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=getDataList("SysDict",$con,"code,name,id");
        $category=$msg["data"];
        for ($i = 0; $i < count($category); $i++) {
            if($i<count($flags)){
                $category[$i]["flag"]=$flags[$i];
            }else{
                $index=$i%count($flags);
                $category[$i]["flag"]=$flags[$index];
            }
        }

        $all=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0'),"count(id) as c",'0');
        $read=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0','status'=>array("LIKE","%0%")),"count(id) as c",'0');//未读
        $impt=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0','status'=>array("LIKE","%2%")),"count(id) as c",'0');//重要
        $del=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'1'),"count(id) as c",'0');
        return view("",array("category"=>$category,'data'=>array('all'=>$all,'read'=>$read,'impt'=>$impt,'del'=>$del),'stat'=>$stat,'type'=>$type,'recycle'=>$recycle));
    }
    function getMsgs($type="",$page="1",$search="",$pageSize='12',$stat='',$recycle='0')
    {
        $table="SysMsg";
        $sequence="addtime desc";//排序
        $con=array("toUserId"=>session("userId"));
        if(!empty($search)){
            $con["title|content"]=array("like","%".$search."%");
        }
        if($type!==""){
            $con["type"]=array("like","%".$type."%");
        }
        if($stat!==""){
            $con["status"]=array("like","%".$stat."%");
        }
        if($recycle===""||$recycle==="0"){
            $con["recycle"]=0;
        }else{
            $con["recycle"]=1;
        }
        $msg=getPageDataList($table,$con,"*",$page,$pageSize,$sequence);
        $msgs=$msg["data"];
        $tmplist=self::getDictStr("*",$table);
        $msgs=$this->setListIdText($msgs,$tmplist);
        for ($i = 0; $i < count($msgs); $i++) {
            $msgs[$i]["addtime"]=date("Y-m-d H:i",$msgs[$i]["addtime"]);
        }
        $msg["data"]=$msgs;
        $msg["pageSize"]=$pageSize;
        $msg["page"]=$page;
        return $msg;
    }



    function setMsgStat($ids,$stat,$recycle)
    {
        if($stat!==""){
            $data["status"]=$stat;
        }
        if($recycle!==""){
            $data["recycle"]=$recycle;
        }
        return saveDataByCon("SysMsg",$data,array("id"=>array("in",$ids)));
    }

    function msgDetail($id)
    {
        $flags=array('navy','danger','primary','info','warning','muted');
        $con=array("level"=>2,'code'=>array('like',"1020%"));
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=getDataList("SysDict",$con,"code,name,id");
        $category=$msg["data"];
        for ($i = 0; $i < count($category); $i++) {
            if($i<count($flags)){
                $category[$i]["flag"]=$flags[$i];
            }else{
                $index=$i%count($flags);
                $category[$i]["flag"]=$flags[$index];
            }
        }

        $all=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0'),"count(id) as c",'0');
        $read=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0','status'=>array("LIKE","%0%")),"count(id) as c",'0');//未读
        $impt=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0','status'=>array("LIKE","%2%")),"count(id) as c",'0');//重要
        $del=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'1'),"count(id) as c",'0');

        if(!empty($id)){
            $msg=findById("SysMsg",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $detail=$msg["data"];
                $stat=$detail["status"];
                if(strpos($stat,"0")!==false){
                    $stat=str_replace($stat,"0","1");
                    //未读，设置为已读
                    saveDataByCon("SysMsg",array('status'=>$stat),array("id"=>$id));
                }
                $weekarray = array("日","一", "二", "三", "四", "五", "六");
                $key=date("w",$detail["addtime"]);
                $detail["addtime"]=date("Y年m月d日 H:i",$detail["addtime"])."（星期".$weekarray[$key]."）";
                $fromUserId=$detail["fromUserId"];
                if(empty($fromUserId)){
                    $detail["fromUser"]="系统消息";
                }else{
                    $detail["fromUser"]=getField("User",array("id"=>$fromUserId),"realname");
                }
                return view("",array("detail"=>$detail,"category"=>$category,'data'=>array('all'=>$all,'read'=>$read,'impt'=>$impt,'del'=>$del)));
            }else{
                return view("",array("detail"=>array()));
            }
        }else{
            return view("",array("detail"=>array()));
        }
    }

    //获取管理员、企业、导师
    function getManage($type='manage'){

        if($type=="manage"){
            //如果是多园区管理员，查询的是他管理的多孵化器下的管理员
            if(session('user.roleIds')=='1'){
                $map = array(
                    'etprsIqbtId'=>session('user.etprsIqbtId'),
                    'userCate'=>'1011001',
                    'status'=>'1012001',
                    'roleIds'=>array('neq',1),
                );
            }else{
                $map = array(
                    'iqbtId'=>session('iqbtId'),
                    'userCate'=>'1011001',
                    'status'=>'1012001',
                );
            }

            $msg = getDataList('User',$map,'id,name,realname');
        }elseif($type=="etprs"){
            $map = array(
                'a.iqbtId'=>session('iqbtId'),
                'a.userCate'=>'1011002',
                'a.status'=>'1012001',
                'b.status'=>'1001016'
            );
            $join = [['enterprise b','a.etprsId=b.id',"left"]];
            $msg = getDataList('User',$map,'a.id,realname,b.name','',$join);
        }elseif($type =="tutor"){
            $map = array(
                'iqbtId'=>session('iqbtId'),
                'userCate'=>'1011005',
                'status'=>'1012001',
            );
            $msg = getDataList('User',$map,'id,name,realname');
        }
        //  print_r($msg);exit();
        return $msg;

    }
    function getSearchMange(){
        $name = trim(input('name'));
        if(empty($name)){
            return array('code'=>0,'msg'=>'联系人名称不能为空');
        }
        if(session('user.roleIds')=='1'){
            $map = array(
                'etprsIqbtId'=>session('user.etprsIqbtId'),
                'userCate'=>'1011001',
                'status'=>'1012001',
                'roleIds'=>array('neq',1),
                'name'=>array('like','%'.$name.'%')
            );
        }else{
            $map = array(
                'iqbtId'=>session('iqbtId'),
                'userCate'=>'1011001',
                'status'=>'1012001',
                'name'=>array('like','%'.$name.'%')
            );
        }

        $manageMsg = getDataList('User',$map,'id,name,realname');
        if($manageMsg['code']==1 && !empty($manageMsg['data'])){
            $manageInfo = $manageMsg['data'];
        }else{
            $manageInfo = array();
        }
        //如果是多园区管理员，直接返回管理员数据，不需要企业数据
        if(session('user.roleIds')=='1'){
            return array('code'=>1,'data'=>$manageInfo);
        }
        $map2 = array(
            'a.iqbtId'=>session('iqbtId'),
            'a.userCate'=>'1011002',
            'a.status'=>'1012001',
            'b.status'=>'1001006',
            'b.name'=>array('like','%'.$name.'%')
        );
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg = getDataList('User',$map2,'a.id,a.realname,b.name','',$join);
        if($msg['code']==1 && !empty($msg['data'])){
            $etprsInfo = $msg['data'];
        }else{
            $etprsInfo = array();
        }
        $finalInfo = array_merge($manageInfo,$etprsInfo);
        return array('code'=>1,'data'=>$finalInfo);
    }
    //写信
    function addMsg($id=0){
        //收件箱全部条数
        $in_all=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0'),"count(id) as c",'0');
        //发件箱已发送
        $out_send = getField('SysOutbox',array('userId'=>session('userId'),'status'=>'1','iqbtId'=>session('iqbtId')),"count(id) as c",'0');
        //发件箱草稿箱
        $out_draft = getField('SysOutbox',array('userId'=>session('userId'),'status'=>'0','iqbtId'=>session('iqbtId')),"count(id) as c",'0');
        $data = array(
            'in_all'=>$in_all,
            'out_draft'=>$out_draft,
            'out_send'=>$out_send
        );
        $info = array();
        if(!empty($id)){
            $infoMsg = findById('SysOutbox',array('id'=>$id),"*");
            if($infoMsg["code"]=='1'){
                $info=$infoMsg["data"];

            }

        }
        $data['info'] = $info;
        return view("",$data);
    }


    //发信
    //$tousers  要发给的人 以逗号隔开的用户id字符串，如 2,4,5
    function saveMsg(){
        $postData=input("request.");

        if($postData['status'] ==1){
            if(empty($postData['toUserId'])){
                return array('code'=>0,'msg'=>'请选择要发送的用户','data'=>'');
            }
        }
        if(empty($postData['title'])){
            return array('code'=>0,'msg'=>'请输入要发送的主题');
        }
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $postData['userId'] = session("userId");
        $sms = $postData['sms'];
        unset($postData['sms']);
        //$postData['status'] = 1 ; //如果是草稿，status=0
        $res = saveData('SysOutbox',$postData,'发送站内信');
        if($res['code']==1){
            if($postData['status'] ==1){
                $emailData = array(
                    'fromUserId'=>session('userId'),
                    'type'=>'1020008',
                    'title'=>$postData['title'],
                    'content'=>$postData['content'],
                );
                //发送短信
                $smsData = array();
                if($sms==1) {
                    $tpl_id = config('sms_tpl_id.message');
                    $data = array(
                        'title' => $postData['title'],
                        'content' => $postData['content'],
                    );
                    $smsData = array(
                        'tpl'=>$tpl_id,
                        'data'=>$data,
                    );
                }
                //说明发件箱保存成功，然后分别把信息保存到收件箱
                $userArr = explode(",",$postData['toUserId']);
                $this->sendAllMsg($userArr,$emailData,array(),$smsData);
            }

            return array('code'=>'1','msg'=>'发送成功','data'=>'发送成功');
        }else{
            return $res;
        }
    }

    function delOutMsg($ids){
        $data = array(
            'isDelete'=>1
        );
        return saveDataByCon("SysOutbox",$data,array("id"=>array("in",$ids)));
    }
    //发件箱和草稿箱
    function outMsg($status=1){
        //收件箱全部条数
        $in_all=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0'),"count(id) as c",'0');
        //发件箱已发送
        $out_send = getField('SysOutbox',array('userId'=>session('userId'),'status'=>'1','iqbtId'=>session('iqbtId')),"count(id) as c",'0');
        //发件箱草稿箱
        $out_draft = getField('SysOutbox',array('userId'=>session('userId'),'status'=>'0','iqbtId'=>session('iqbtId')),"count(id) as c",'0');
        $data = array(
            'in_all'=>$in_all,
            'out_draft'=>$out_draft,
            'out_send'=>$out_send,
            'status'=>$status
        );
        return view("outMsg",$data);
    }
    //发件箱和草稿箱详情
    function outMsgDetail($id){
        //收件箱全部条数
        $in_all=getField("SysMsg",array("toUserId"=>session("userId"),'recycle'=>'0'),"count(id) as c",'0');
        //发件箱已发送
        $out_send = getField('SysOutbox',array('userId'=>session('userId'),'status'=>'1','iqbtId'=>session('iqbtId')),"count(id) as c",'0');
        //发件箱草稿箱
        $out_draft = getField('SysOutbox',array('userId'=>session('userId'),'status'=>'0','iqbtId'=>session('iqbtId')),"count(id) as c",'0');
        $data = array(
            'in_all'=>$in_all,
            'out_draft'=>$out_draft,
            'out_send'=>$out_send,
        );
        if(!empty($id)){
            $msg=findById("SysOutbox",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $detail=$msg["data"];
                $weekarray = array("日","一", "二", "三", "四", "五", "六");
                $key=date("w",$detail["addtime"]);
                $detail["addtime"]=date("Y年m月d日 H:i",$detail["addtime"])."（星期".$weekarray[$key]."）";

                return view("",array("detail"=>$detail,"data"=>$data));
            }else{
                return view("",array("detail"=>array(),"data"=>$data));
            }
        }else{
            return view("",array("detail"=>array(),"data"=>$data));
        }
    }

    function getOutMsg($search="",$page="1",$status="1",$pageSize='12')
    {
        $table="SysOutbox";
        $sequence="addtime desc";//排序
        $con=array("userId"=>session("userId"),'iqbtId'=>session('iqbtId'));
        if(!empty($search)){
            $con["title|content"]=array("like","%".$search."%");
        }
        if($status!==""){
            $con["status"]=$status;
        }
        $msg=getPageDataList($table,$con,"*",$page,$pageSize,$sequence);
        $msgs=$msg["data"];
        for ($i = 0; $i < count($msgs); $i++) {
            $msgs[$i]["addtime"]=date("Y-m-d H:i",$msgs[$i]["addtime"]);
        }
        $msg["data"]=$msgs;
        $msg["pageSize"]=$pageSize;
        $msg["page"]=$page;
        return $msg;
    }



    function noticelist($type='',$table='')
    {
        return view("",array("type"=>$type,'table'=>$table));
    }
    function noticepagelist($type="0",$page="1",$pageSize='10')
    {
        $table="SysNotice";
        $sequence="addtime desc,id asc";//排序
        $con="iqbtId =".session("user.iqbtId")." and type =".$type." and (endtime >".time()." or endtime=0)";
        $msg=getPageDataList($table,$con,"*",$page,$pageSize,$sequence);
        $msg["pageSize"]=$pageSize;
        return $msg;
    }
    function actpagelist($type='',$page="1",$pageSize='10')
    {
        $table="activity";
        $sequence="addtime desc,id asc";//排序
        $con="iqbtId =".session("user.iqbtId")." and close =0 and (endtime >".time()." or endtime=0)";
        $msg=getPageDataList($table,$con,"id,name as title,addtime",$page,$pageSize,$sequence);
        $msg["pageSize"]=$pageSize;
        return $msg;
    }

    //便签
    function getNotes()
    {
        $msg=getDataList("notes",array('adduserId'=>session('userId')),"a.*","a.addtime desc");
        if($msg["code"]==="1"&&!empty($msg["data"])){
            $weekarray = array("日","一", "二", "三", "四", "五", "六");
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $key=date("w",$msg["data"][$i]["addtime"]);
                $msg["data"][$i]["addtime"]=date("Y年m月d日 H:i",$msg["data"][$i]["addtime"])."（周".$weekarray[$key]."）";
            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addnotes()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("Notes",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveNotes(){
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="Notes";
        return saveData($table,$postData,"添加/修改便签");
    }
    function delNotes(){
        $id=input("id");
        return deleteData("Notes",$id,"删除便签");
    }

    function initIqbt($etprsIqbtId)
    {
        $msg=getDataList("incubator",array("etprsIqbtId"=>$etprsIqbtId),"id,name");
        if(!empty($msg["data"])){
             return $msg["data"];
        }else{
            return array();
        }
    }

    function etprsIqbtIndex()
    {
        $data=array();
        if(strpos(','.session("user.roleIds").',',',1,')!==false||session("user.userCate")=='1011004'){
            //超级管理员
            if(session("user.userCate")=='1011001') {
                $etprsIqbtIds=getFieldArrry("incubator",array("etprsIqbtId"=>session("etprsIqbtId")),"id");
            }else if(session("user.userCate")=='1011004'){
                $etprsIqbtIds=getFieldArrry("incubator",array("districtId"=>array('like','%'.session("user.districtId").'%')),"id");
            }
            //导师数量
            $tutornum = getField('tutor',array('iqbtId'=>array('in',$etprsIqbtIds),'isDelete'=>0),'count(id) as tutornum');
            //孵化器个数和不同级别（国家级、省级、市级)
            $iqbtMsg = getDataList('incubator',array('id'=>array('in',$etprsIqbtIds),'isDelete'=>0,'status'=>1),'id,level,name');
            $iqbtnum = 0;//孵化器全部个数
            $iqbtName = '';//全部孵化器的名称
            $ctyiqbtnum = 0;//国家级
            $ctyiqbtName = '';//国家级孵化器名称
            $prviqbtnum = 0;//省级
            $prviqbtName = '';//省级孵化器名称
            $cityiqbtnum = 0;//市级
            $cityiqbtName = '';//市级孵化器名称
            if($iqbtMsg['code']==1 && !empty($iqbtMsg['data'])){
                foreach($iqbtMsg['data'] as $value){
                    $iqbtnum += 1;
                    $iqbtName .=$value['name'].'，';
                    if($value['level']=='1031001'){
                        $ctyiqbtnum +=1;
                        $ctyiqbtName .=$value['name'].'，';
                    }elseif($value['level']=='1031002'){
                        $prviqbtnum +=1;
                        $prviqbtName .= $value['name'].'，';
                    }elseif($value['level']=='1031003'){
                        $cityiqbtnum +=1;
                        $cityiqbtName .= $value['name'].'，';
                    }
                }
            }
            //运营管理人员数量
            $optnum = getField('user',array('iqbtId'=>array('in',$etprsIqbtIds),'status'=>'1012001','userCate'=>'1011001'),'count(id) as optnum');
            $nowmonth=strtotime(date("Y-m",time()));
            //在孵企业
            $ingnum=getField("enterprise",array("iqbtId"=>array("in",$etprsIqbtIds),"status"=>'1001016'),"count(id) as ingnum");
            $monthingnum=getField("enterprise",array("iqbtId"=>array("in",$etprsIqbtIds),"status"=>'1001016',"entertime"=>array("gt",$nowmonth)),"count(id) as ingnum");
            //申请企业
            $aplnum=getField("enterprise",array("iqbtId"=>array("in",$etprsIqbtIds),"status"=>array("in",array('1001011','1001012','1001013','1001014','1001015'))),"count(id) as aplnum");
            $monthaplnum=getField("enterprise",array("iqbtId"=>array("in",$etprsIqbtIds),"addtime"=>array("gt",$nowmonth),"status"=>array("in",array('1001011','1001012','1001013','1001014','1001015'))),"count(id) as aplnum");
            //完成孵化企业
            $gradtnum=getField("enterprise",array("iqbtId"=>array("in",$etprsIqbtIds),"status"=>array('in','1001017,1001020,1001021')),"count(id) as gradtnum");
            $monthgradtnum=getField("enterprise",array("iqbtId"=>array("in",$etprsIqbtIds),"status"=>array('in','1001017,1001020,1001021'),"quittime"=>array("gt",$nowmonth)),"count(id) as gradtnum");
            //最近一个月新三板，蓝海上市-------------------------
            $marketnum = getField('etprsInfo',array('iqbtId'=>array('in',$etprsIqbtIds),'isMarket'=>'1'),"count(id) as marketnum");
            //已入驻房间占比
            $roomnum=getField("EstateRoom",array("iqbtId"=>array("in",$etprsIqbtIds),"type"=>'1'),"count(id) as num");
            $etroomnum=getField("EstateRoom",array("iqbtId"=>array("in",$etprsIqbtIds),"type"=>'1','status'=>array("in",[1,2])),"count(id) as num");//已分配和正常使用的房间均属于已入驻房间
            if($roomnum>0){
                $roomNumRate= round(100*$etroomnum/$roomnum,2);
            }else{
                $roomNumRate=0;
            }


            //已入驻面积占比
            $roomarea=getField("EstateRoom",array("iqbtId"=>array("in",$etprsIqbtIds),"type"=>'1'),"sum(totalarea) as area");
            $etroomarea=getField("EstateRoom",array("iqbtId"=>array("in",$etprsIqbtIds),"type"=>'1','status'=>array("in",[1,2])),"sum(totalarea) as area");

            //$roomAreaRate= round(100*$etroomarea/$roomarea,2);

            //已入驻工位数占比
            $unitnum=getField("EstateRoom",array("iqbtId"=>array("in",$etprsIqbtIds),"type"=>'0'),"count(id) as num");
            $etunitnum=getField("EstateRoom",array("iqbtId"=>array("in",$etprsIqbtIds),"type"=>'0','status'=>array("in",[1,2])),"count(id) as num");//已分配和正常使用的房间均属于已入驻房间
            //$unitNumRate= round(100*$etunitnum/$unitnum,2);
            if($ingnum+$gradtnum>0){
                $ingetprsrate=round(100*$ingnum/($ingnum+$gradtnum),2);
            }else{
                $ingetprsrate=0;
            }

            $data=array('iqbtIds'=>join(",",$etprsIqbtIds),'iqbtnum'=>$iqbtnum,
                'ctyiqbtnum'=>$ctyiqbtnum,'prviqbtnum'=>$prviqbtnum,'cityiqbtnum'=>$cityiqbtnum,'optnum'=>$optnum,'tutornum'=>$tutornum,
                'ingnum'=>$ingnum,'aplnum'=>$aplnum,'gradtnum'=>$gradtnum,'ingetprsrate'=>$ingetprsrate,
                'roomNumRate'=>$roomNumRate,'monthingnum'=>$monthingnum,'monthaplnum'=>$monthaplnum,'marketnum'=>$marketnum,
                'monthgradtnum'=>$monthgradtnum,'roomnum'=>$roomnum,'etroomnum'=>$etroomnum,'roomarea'=>$roomarea,
                'etroomarea'=>$etroomarea,'unitnum'=>$unitnum,'etunitnum'=>$etunitnum,
                'iqbtName'=>$iqbtName,'ctyiqbtName'=>$ctyiqbtName,'prviqbtName'=>$prviqbtName,'cityiqbtName'=>$cityiqbtName);
        }
        return view("",array("data"=>$data));

    }


}
