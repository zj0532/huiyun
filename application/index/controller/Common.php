<?php
namespace app\index\controller;
use app\common\controller\Base;
use think;
use think\Config;
class Common extends Base{
    /**
     * 架构函数
     * @param Request    $request     Request对象
     * @access public
     */
    public function __construct()
    {
        if(request()->isMobile()){
          //  echo 'aaa';exit();
            config('template.view_base',APP_PATH.'mobile/');
        }
        parent::__construct();
    }
    public function _initialize()
    {
        $controller=request()->controller();
        $action=request()->action();
        $module= request()->module();
        $userId=session('userId');

        if(empty($userId)){
            $this->redirect(url('/user/Login/login'));
        }
        //self::checkRoleMenu("/".$controller."/".$action);

        Config::load(APP_PATH.'customConfig.php');

        self::initMenus("/".$module."/".$controller."/".$action);
        $this->assign("url","/".$module."/".$controller."/".$action);
        //企业端显示部门介绍
        $userCate = session('user.userCate');
        $deptData = array();

        $con=array('iqbtId'=>session("iqbtId"));
        $msg=getDataList("deptIntro",$con,"*"," sort desc");
        if($msg["code"]==="1") {
            $deptData = $msg["data"];
        }

        $this->assign('deptData',$deptData);
        $this->assign("tags","edituser");
    }

    function initMenus($url)
    {
        $roleId=session("user.roleIds");
        $rolesMsg=getDataList("UserRole",array("id"=>array("in",$roleId)),"menuIds,rolename,parentId");
        $roleName="";
        $menuIds="";
        $menuarr=array();
        $proles=array();
        $mobilechecks=[];
        //halt($rolesMsg);
        if($rolesMsg["code"]==='1'){
            $roles=$rolesMsg["data"];

            foreach ($roles as $role) {
                if(!in_array($role["parentId"],$proles)){
                    $proles[]=$role["parentId"];
                }
                $roleName.=",".$role["rolename"];
                if(!empty($role["menuIds"])){
                    $menuIds.=",".$role["menuIds"];
                }
            }
        }
        $roleName=trim($roleName,",");
        //$menuIdarr=array_unique(explode(",",$menuIds));
        $con="level <=2 and id in(".trim($menuIds,",").")";
        if(strpos(','.$roleId.',',',1,')===false&&strpos(','.$roleId.',',',3,')===false&&strpos(','.$roleId.',',',6,')===false&&!in_array(6,$proles)){
            //非系统维护员&&非区域用户
            $iqbtMenuIds='';
            $iqbtId=session("iqbtId");
            $join = [['user_packages b','a.packageId=b.id',"left"]];
            $iqbtMenumsg=findById("incubator",array("a.id"=>$iqbtId),"b.menuIds",$join);
            //halt($iqbtMenumsg);
            if(!empty($iqbtMenumsg["data"])){
                $iqbtMenuIds=$iqbtMenumsg["data"]["menuIds"];
            }
            if(!empty($iqbtMenuIds)){
                //孵化器用户需要使用系统维护员分配的套餐功能
                if(!empty($con)){
                    $con=$con." and id in(".$iqbtMenuIds.")";
                }else{
                    $con=" id in(".$iqbtMenuIds.")";
                }
            }else{
                //当前孵化器没有分配功能菜单
                //$con["id"]=array("in","");
                if(!empty($con)){
                    $con=$con." and id in('')";
                }else{
                    $con=" id in('')";
                }
            }
        }
        if(session('user.userCate')=="1011002"){
            $status=getField("enterprise",array("id"=>session("user.etprsId")),"status");
            if($status<1001016){
                $con=$con." and (id=7 or id=14)";
            }
        }

        $menusMsg=getDataList("UserMenu",$con,"id,name,parentId,level,url,icon,ourl","sort desc");
        if($menusMsg["code"]=="1"){
            $menuarr=$menusMsg["data"];
        }
        session('menuarr',$menuarr);
        $roots=array();
        //halt($menuarr);
        for ($i = 0; $i < count($menuarr); $i++) {
            if($menuarr[$i]["parentId"]==0){
                $roots[]=$menuarr[$i];
            }else{
                $mobilechecks[]=$menuarr[$i]["id"];
            }
        }
        // halt($menuarr);
        $tmpurl=self::get_url();
        list($turl,$pf)=explode(".",$tmpurl."a.a");
        //添加消息提醒的内容
        $noticeRemind = self::messageRemind();
        for ($i = 0; $i < count($roots); $i++) {
            if(in_array($roots[$i]['id'],$noticeRemind['rootmenu'])){
                $roots[$i]["act"]=0;
                $roots[$i]['noticenum'] = 0;
                foreach($menuarr as $menu){
                    $menu["act"]=0;
                    if($menu["parentId"]==$roots[$i]["id"]){
                        $tmpstr="";
                        if(strpos($turl,"iqbt/")){
                            $tmpstr="/iqbt";
                        }
                        if($tmpstr.$menu["url"]==$turl||in_array($url,explode(",",$menu["ourl"]))){
                            $menu["act"]=1;
                            $roots[$i]["act"]=1;
                        }
                        //该二级菜单如果有要提醒的消息
                        $i_str = (string)$roots[$i]['id'];
                        $id_str = (string)($menu['id']);
                        if(isset($noticeRemind[$i_str][$id_str])){
                            $menu['noticenum'] = $noticeRemind[$i_str][$id_str];
                            $roots[$i]['noticenum'] += $menu['noticenum'];
                        }else{
                            $menu['noticenum'] = 0;
                        }
                        $roots[$i]["sub"][]=$menu;

                    }
                }
            }else{
                $roots[$i]["act"]=0;
                $roots[$i]['noticenum'] = 0;
                foreach($menuarr as $menu){
                    $menu["act"]=0;
                    if($menu["parentId"]==$roots[$i]["id"]){
                        $tmpstr="";
                        if(strpos($turl,"iqbt/")){
                            $tmpstr="/iqbt";
                        }
                        if($tmpstr.$menu["url"]==$turl||in_array($url,explode(",",$menu["ourl"]))){
                            $menu["act"]=1;
                            $roots[$i]["act"]=1;
                        }
                        $menu['noticenum'] = 0;
                        $roots[$i]["sub"][]=$menu;
                    }

                }
            }
        }

        //针对孵化器配置的流程(roleId 等于2 ，说明是企业端登录，不需要配置,否则会导致企业端主页面菜单读不出来）
        if($roleId !='2'){

            if(!empty(session("iqbtId"))){
                $stepmsg=findById("enterStep",array("iqbtId"=>session("iqbtId")),"apllist,batchapl,retrialapl,enterapl,roomdstb,enteriqbt");
                if(!empty($stepmsg['data'])){
                    for ($i = 0; $i < count($roots); $i++) {
                        if($roots[$i]["id"]==7){
                            //如果是孵化入驻下的菜单
                            $subs=$roots[$i]["sub"];
                            $steps=$stepmsg['data'];
                            $newsubs=[];
                            foreach ($subs as $tmp) {
                                $strfrom=$tmp["url"];
                                //think\Log::notice($strfrom);
                                foreach ($steps as $k=>$v) {
                                    $strfor="/".$k;
                                    if($v==1&&strrchr($strfrom,$strfor)==$strfor ){
                                        $newsubs[]=$tmp;
                                    }
                                }
                            }
                            $roots[$i]["sub"]=$newsubs;
                            break;
                        }
                    }
                }
            }
        }

        $this->assign("mobileroles",$mobilechecks);
        $this->assign("menus",$roots);
    }

    function checkRoleMenu($url="")
    {
        $roleId=session("user.roleIds");
        $rolesMsg=getDataList("UserRole",array("id"=>array("in",$roleId)),"menuIds,rolename");
        $roleName="";
        $menuIds="";
        $iqbtMenuIdarr=array();
        if($rolesMsg["code"]==='1'){
            $roles=$rolesMsg["data"];
            foreach ($roles as $role) {
                $roleName.=",".$role["rolename"];
                if(!empty($role["menuIds"])){
                    $menuIds.=",".$role["menuIds"];
                }
            }
        }
        $menuIdarr=array_unique(explode(",",$menuIds));

        if(strpos(','.$roleId.',',',3,')===false){
            //非系统维护员
            /*$etprsIqbtId=session("etprsIqbtId");
            $iqbtMenuIds=getField("EtprsIqbt",array("id"=>$etprsIqbtId),"menuIds");*/
            $iqbtMenuIds='';
            $iqbtId=session("iqbtId");
            $join = [['user_packages b','a.packageId=b.id',"left"]];
            $iqbtMenumsg=findById("incubator",array("a.id"=>$iqbtId),"b.menuIds",$join);
            if(!empty($iqbtMenumsg["data"])){
                $iqbtMenuIds=$iqbtMenumsg["data"]["menuIds"];
            }
            if(!empty($iqbtMenuIds)){
                //孵化器用户需要使用系统维护员分配的功能
                $iqbtMenuIdarr=explode(",",$iqbtMenuIds);
            }else{
                //当前孵化器没有分配功能菜单
                $iqbtMenuIdarr=array();
            }

            if(!empty($url)){
                $menuId=getField("UserMenu",array("concat(',',url,',')"=>array('like','%,'.$url.',%'),'level'=>array('elt',2)),"id");

                if(!empty($menuId)&&!(in_array($menuId,$menuIdarr)&&in_array($menuId,$iqbtMenuIdarr))){
                    $this->redirect(url('/Login/nonAccess'));
                }
            }
        }else{
            //系统维护员拥有所划分的全部权限
            if(!empty($url)){
                $menuId=getField("UserMenu",array("concat(',',url,',')"=>array('like','%,'.$url.',%')),"id");

                if(!empty($menuId)&&!in_array($menuId,$menuIdarr)){
                    $this->redirect(url('/Login/nonAccess'));
                }
            }
        }

    }

    //添加企业日程计划
    function addEtprsSchedule($etprsId=0,$startDate="",$id=0)
    {
        if(!empty($id)){
            $msg=findById("UserSchedule",array("id"=>$id),"id,title as name,startTime,endTime,holeday,aim,remark,address,color,isend,timemark");
            if($msg["code"]==='1'){
                $schedule=$msg["data"];
                if(!empty($schedule["startTime"])){
                    $schedule["startDate"]=$schedule["startTime"];
                    $schedule["starttime"]=$schedule["startTime"];
                }

                if(!empty($schedule["endTime"])) {
                    $schedule["endDate"] = $schedule["endTime"];
                    $schedule["endtime"] =$schedule["endTime"];
                }
                //halt($schedule);
                return view("",array("data"=>$schedule));
            }
        }
        if(!empty($etprsId)){
            $msg=findById("Enterprise",array("id"=>$etprsId),"name,lealPerson,status,mobile,address,contact");
            if($msg["code"]==="1"){
                $etprs=$msg["data"];
            }
            $etprs["name"]="拜访企业【".$etprs["name"]."】";
            $etprs["remark"]="<p>企业名称：".$etprs["name"]."</p><p>联 系 人：".$etprs["contact"]."</p><p>联系电话：".$etprs["mobile"]."</p><p>企业地址：".$etprs["address"]."</p>";
            return view("",array("data"=>$etprs,"startDate"=>time()));
        }
        if(!empty($startDate)){
            return view("",array("startDate"=>$startDate));
        }
        return view();
    }
    //添加日程计划
    function saveEtprsSchedule()
    {
        $postData = input("request.");

        $postData["addtime"]=time();
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("user.iqbtId");
        if(!isset($postData["holeday"])||empty($postData["holeday"])){
            //非全天
            $postData["holeday"]=0;
            if(!empty($postData["isend"])){
                //有结束时间
                if(!empty($postData["timemark"])){
                    //有开始日期 结束日期 开始时间，结束时间
                    $postData["startTime"]=strtotime(rtrim($postData["startDate"]." ".$postData["starttime"]," "));
                    $postData["endTime"]=strtotime(rtrim($postData["endDate"]." ".$postData["endtime"]," "));
                }else{
                    //只有开始日期 结束日期，不选择开始时间和结束时间
                    $postData["startTime"]=strtotime(rtrim($postData["startDate"]," "));
                    $postData["endTime"]=strtotime(rtrim($postData["endDate"]," "))+86399;
                }

            }else{
                if(!empty($postData["timemark"])){
                    //有开始日期 开始时间  无结束日期 结束时间
                    $postData["isend"]=0;
                    $postData["startTime"]=strtotime(rtrim($postData["startDate"]." ".$postData["starttime"]," "));
                    $postData["endTime"]=strtotime(rtrim($postData["startDate"]," "))+86399;
                }else{
                    //只有开始日期  全天
                    $postData["isend"]=0;
                    $postData["startTime"]=strtotime(rtrim($postData["startDate"]," "));
                    $postData["endTime"]=0;
                    $postData["timemark"]=0;
                    $postData["holeday"]=1;
                }
            }
        }else{
            //全天
            $postData["isend"]=0;
            $postData["startTime"]=strtotime(rtrim($postData["startDate"]," "));
            $postData["endTime"]=strtotime(rtrim($postData["startDate"]," "))+86399;
            $postData["timemark"]=0;
        }

        unset($postData["endDate"]);
        if(isset($postData['endtime'])){
            unset($postData["endtime"]);
        }
        unset($postData["startDate"]);
        if(isset($postData['starttime'])){
            unset($postData["starttime"]);
        }
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
        return $res;
    }
    //初始化日程表
    function initCalendar()
    {
        $userId=session("userId");
        $msg=getDataList("UserSchedule",array("adduserId"=>$userId),"id,startTime,endTime,aim,title,addtime,holeday,isend,remark,color");
        if($msg["code"]==="1"){
            $schedule=array();
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $tmp["id"]=$msg["data"][$i]["id"];
                $tmp["title"]=$msg["data"][$i]["title"];
                $tmp["start"]=date("Y-m-d H:i",$msg["data"][$i]["startTime"]);
                $tmp["color"]=$msg["data"][$i]["color"];
                if($msg["data"][$i]["holeday"]=="0"){
                    $tmp["allDay"]=false;
                    $tmp["end"]=date("Y-m-d H:i",$msg["data"][$i]["endTime"]);
                }else{
                    $tmp["allDay"]=true;
                    //$tmp["end"]=date("Y-m-d H:i",$msg["data"][$i]["endTime"]);
                }
                $schedule[]=$tmp;
            }
            return $schedule;
        }else{
            return array();
        }
    }

    /**
     * 管理员用户权限判断
     * 判断是否选择企业
     * @return int
     */
    function checkEtprsId()
    {
        $userCate=session("user.userCate");
        if($userCate!="1011001"){
            //非管理人员不能选择企业
            return "1013002";
        }
        $etprsId=session("etprsId");
        if(empty($etprsId)){
            return "1013004";
        }
        $status=getField("enterprise",array("id"=>$etprsId),"status");
        if($status!="1001015"){
            return "1013008";
        }
        return "1013005";
    }
    //企业用户权限判断
    function checkEtprs()
    {
        $userCate=session("user.userCate");
        if($userCate!="1011002"){
            //非企业人员
            return "1013003";
        }
        $etprsId=session("etprsId");
        $status=getField("enterprise",array("id"=>$etprsId),"status");
        if($status!="1001015"){
            return "1013008";
        }
        return "1013005";
    }

    function selectEtprs($status,$close)
    {
        return view("",array("status"=>$status,"close"=>$close));
    }
    function checkEtprsAccess($stat)
    {
        $userCate=session("user.userCate");
        if($userCate!="1011001"&&$userCate!="1011002"){
            return "1013006";//用户类型错误
        }
        $etprsId=session("etprsId");
        if(empty($etprsId)){
            return "1013004";//管理员未选择企业
        }
        $msg=findById("enterprise",array("id"=>$etprsId),"status");
        if($msg["code"]==='1'){
            $etprs=$msg["data"];
            $etprsStat=$etprs["status"];
            if($etprsStat<$stat){
                //企业状态错误
                return "1013008";
            }
        }else{
            return "1013007";//未找到相关企业
        }
        return "1013005";
    }




    public function uploadFile()
    {
        $dir=input("dir");
        $dir=empty($dir)?"/default":"/".$dir;
        return upload($dir);
    }

    function smntUpload()
    {
        $dir=input("dir");
        $dir=empty($dir)?"/default":"/".$dir;
        $result= upload($dir);
        $file=findById("SysFile",array("id"=>$result['data']),"*");
        return $file["data"]["savePath"];
    }



    function downfile(){
        $id=input("id");
        $fileinfoMsg=self::findById("sysFile",array("id"=>$id),"id,savePath,saveName","1");
        if($fileinfoMsg["code"]!=="1"){
            return $fileinfoMsg;
        }
        $fileinfo=$fileinfoMsg["data"];
        $file=$fileinfo["savePath"];

        if(is_file($file)){
            $length = filesize($file);
            /*$type = Fileinfo($file);
            //$showname =  ltrim(strrchr($file,'/'),'/');*/
            header("Content-Description: File Transfer");
            header('Content-Type: application/force-download');
            header('Content-Length:' . $length);
            if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
                header('Content-Disposition: attachment; filename="' . rawurlencode($fileinfo['saveName']) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $fileinfo['saveName'] . '"');
            }
            readfile($file);
            exit;
        } else {
            exit('文件已被删除！');
        }
    }


    function getDingToken()
    {
        $iqbtId=session("iqbtId");
        if(!empty($iqbtId)){
            $msg=findByid("dingCfg",array("iqbtId"=>$iqbtId),"*");
            if($msg["code"]==='1'){
                $corpid=$msg["data"]["corpid"];
                $corpsecret=$msg["data"]["corpsecret"];
                if(!empty($corpid)&&!empty($corpsecret)){
                    $url="https://oapi.dingtalk.com/gettoken?corpid=".$corpid."&corpsecret=".$corpsecret;
                    $ret=file_get_contents($url);
                    $return=json_decode($ret,true);
                    if(isset($return["access_token"])&&!empty($return["access_token"])){
                        return $return["access_token"];
                    }
                }
            }
        }
        return "";
    }

























}