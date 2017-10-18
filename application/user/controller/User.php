<?php
namespace app\user\controller;
use think\Controller;
use app\index\controller\Common;
use think\Db;
use think\Exception;
use app\user\library\Pinyin;
use org\weixin\Jssdk;

class User extends Common{
    function user()
    {
        $usercate=session("user.userCate");
        return view("",array("usercate"=>$usercate));
    }
    //获取用户列表
    function getUsers($cate="1011001",$roleIds=''){
        $table="user";
        $sequence="userCate asc,status asc";//排序
        $usercate=session('user.userCate');
        $con=array('userCate'=>$cate);
        $iqbtIds=array();
        if($usercate=='1011001'){
            if(session('user.roleIds')==1){
                $imsg=getDataList("incubator",array("etprsIqbtId"=>session("etprsIqbtId")),"id");
                if(!empty($imsg["data"])){
                    foreach ($imsg["data"] as $iqbt) {
                        $iqbtIds[]=$iqbt["id"];
                    }
                }
            }else{
                $iqbtIds[0]=session("iqbtId");
            }
            if(!empty($iqbtIds)){
                $con['iqbtId']=array("in",$iqbtIds);
            }
        }

        if(!empty($roleIds)){
            $con["CONCAT(',',a.roleIds,',')"] = array('like',"%,".$roleIds.",%");
        }

        /*$userCate=input("userCate");
        $userCate2=input("userCate2");
        $userCate=!isset($userCate2)?$userCate:$userCate2;//下拉搜索，应该可以优化*/
        /*if(!empty($userCate)){
            $con["userCate"]=array("like","%".$userCate."%");
        }*/
        $join = [['incubator b','a.iqbtId=b.id',"left"]];
        $msg=getDataList($table,$con,"a.*,b.name as iqbtName",$sequence,$join);


        if($msg["code"]==='0'){
            return $msg;
        }
        $tmplist=self::getDictStr("*",$table);
        $msg['data']=self::setListIdText($msg['data'],$tmplist);
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'etprsId','fieldname'=>'etprsText'),"enterprise","id,name",array())));
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'roleIds','fieldname'=>'roleText'),"UserRole","id,rolename",array())));
        //企业用户 显示对应的企业
        foreach($msg['data'] as $key=>$value){
            if($value['userCate'] =='1011002' && !empty($value['etprsId'])){
                $msg['data'][$key]['etprsName'] = getField('enterprise',array('id'=>$value['etprsId']),'name');

            }else{
                $msg['data'][$key]['etprsName'] = '';
            }
            if($value['roleIds']=='1' &&!empty($value['etprsIqbtId'])){
                $msg['data'][$key]['etprsIqbtName'] =getField('etprsIqbt',array('id'=>$value['etprsIqbtId']),'name');
            }else{
                $msg['data'][$key]['etprsIqbtName'] = '';
            }
        }

        for ($i = 0; $i < count($msg["data"]); $i++) {
            if(!empty($msg["data"][$i]["districtId"])){
                $msg["data"][$i]["district"]=getField("region",array("id"=>$msg["data"][$i]["districtId"]),"name");
            }
        }
        return $msg["data"];
    }

    function freezeUser($id=0)
    {
        if(!empty($id)){
            $con=array("id"=>array('in',$id));
            return saveDataByCon("user",array('status'=>'1012005'),$con);
        }else{
            return array("code"=>'0','msg'=>'参数错误');
        }
    }

    function nofreezeUser($id=0)
    {
        if(!empty($id)){
            $con=array("id"=>array('in',$id));
            return saveDataByCon("user",array('status'=>'1012001'),$con);
        }else{
            return array("code"=>'0','msg'=>'参数错误');
        }
    }
    function addUser($cate="",$tab=''){
        if(empty($cate)){
            $cate=session("user.userCate");
        }
        $admintype=0;//区分当前用户是超级管理员还是普通管理员  0-什么都不是，1-超级管理员  2-普通管理员
        $addusercate=session("user.userCate");
        $id=input("id");
        $c=array('userCate'=>$cate,'iqbtId'=>session("iqbtId"),'etprsIqbtId'=> session("etprsIqbtId"));
        if(!empty($id)){
            $msg=findByid("user",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        //角色
        $roots=array();

        $con=array("a.iqbtId"=>array('in','0,'.session("iqbtId")));
        $userRole=session("user.roleIds");
        $userCate=session("user.userCate");
        //系统维护员和管理员，管理员角色中没有企业用户
        //普通管理员，不能添加系统维护员
        if($userCate=="1011003"){
            //系统维护员，只能添加系统维护员和超级管理员,地区用户
            //$con="id in (1,3,4,6) or parentId =6";

            if($cate=="1011003"){
                $con="a.id =3";
            }else if($cate=="1011001"){
                $con="a.id in (1,4)";
            }else if($cate=="1011004"){
                $con="a.id =6 or a.parentId =6";
            }
        }else{
            if(strpos(",".$userRole.",",",1,")!==false){
                $admintype=1;
                //超级管理员--只能添加管理员
                //需要获取所辖孵化器的id
                $etprsiqbtArr = getFieldArrry('incubator',array('etprsIqbtId'=>session('etprsIqbtId')),'id');
                $etprsIqbtStr = implode(",",$etprsiqbtArr);
                $con['a.iqbtId'] = array('in','0,'.$etprsIqbtStr);
                $con["a.parentId|a.id"]=4;
            }else{
                $admintype=2;
                //普通管理员只可以添加除了超级管理员以外的管理员用户
                $con["a.id"]=array("not in","1");
                $con["a.parentId|a.id"]=4;
            }
        }
        $msg=getDataList("UserRole",$con,"a.id,a.rolename,a.isRole,a.parentId,a.level,b.name as iqbtname","a.iqbtId desc",[['incubator b', 'b.id=a.iqbtId','left']]);
        if($msg["code"]==='1'){
            $roles=$msg["data"];
            foreach($roles as $role){
                $role["rolename"]=empty($role["iqbtname"])?$role["rolename"]:$role["iqbtname"]."--".$role["rolename"];
                if($role["parentId"]==0){
                    $roots[]=$role;
                }
            }
            for ($i = 0; $i < count($roots); $i++) {
                $roots[$i]["sub"]=array();
                foreach($roles as $orgrole){
                    $orgrole["rolename"]=empty($orgrole["iqbtname"])?$orgrole["rolename"]:$orgrole["iqbtname"]."--".$orgrole["rolename"];
                    if($orgrole["parentId"]==$roots[$i]["id"]){
                        $roots[$i]["sub"][]=$orgrole;
                    }
                }
            }
        }
        $catecon=array();
        $chkcate="1011001";
        if($userCate=="1011003"){
            //系统维护员，只能添加系统维护员和超级管理员
            $catecon["code"]=array("in",['1011001','1011003']);
            $chkcate="1011003";
        }else{
            $catecon["code"]=array("in",['1011001']);
        }
        $ucate=array();
        if(!empty(session("iqbtId"))){
            $catecon["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $cmsg=getDataList("SysDict",$catecon,"id,name,code");
        if(!empty($cmsg["data"])){
            $ucate=$cmsg["data"];
        }
        if(!empty($c["districtId"])){
            $dmsg=findById("region",array("id"=>$c["districtId"]),"id,level,provinceid,cityid");
            if(!empty($dmsg["data"])){
                if($dmsg["data"]["level"]==1){
                    $c["province"]=$dmsg["data"]["id"];
                    $c["city"]="";
                    $c["district"]="";
                }else if($dmsg["data"]["level"]==2){
                    $c["province"]=$dmsg["data"]["provinceid"];
                    $c["city"]=$dmsg["data"]["id"];
                    $c["district"]="";
                }else if($dmsg["data"]["level"]==3){
                    $c["province"]=$dmsg["data"]["provinceid"];
                    $c["city"]=$dmsg["data"]["cityid"];
                    $c["district"]=$dmsg["data"]["id"];
                }
            }
        }
        //管理员关联孵化器企业
        $iqbtId=$c["iqbtId"];
        if(!empty($iqbtId)){
            $eimsg=findById("incubator",array("id"=>$iqbtId),"etprsIqbtId");
            if(!empty($eimsg["data"])){
                $c["etprsIqbtId"]=$eimsg["data"]["etprsIqbtId"];
            }
        }
        if(isset($c['etprsIqbtId'])&&!empty($c["etprsIqbtId"])){
            //超级管理员
            $uid=session("userId");
            $umsg=findById("user",array("id"=>$uid),"etprsIqbtId");
            if(!empty($umsg["data"]["etprsIqbtId"])){
                $c["etprsIqbtId"]=$umsg["data"]["etprsIqbtId"];
            }

        }
        return view("",array("data"=>$c,'roles'=>$roots,'cates'=>$ucate,'chkcate'=>$chkcate,'addusercate'=>$addusercate,'tab'=>$tab,'admintype'=>$admintype));
    }
    function impvInfo(){
        $id=session("userId");
        $c=array();
        if(!empty($id)){
            $join = [['sys_file b','a.userheader=b.id',"left"]];
            $msg=findByid("user",array("a.id"=>$id),"a.*,b.savePath",$join);
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    //手机端二维码
    function code(){
        $id = input('uid','0');
        $c = array();
        if(!empty($id)){
            $msg = findById('user',array('a.id'=>$id),'a.*');
            if($msg['code']=='1'){
                $c = $msg['data'];
            }
        }
        return view('',array('data'=>$c));
    }
    //手机端修改其他信息
    function change(){
        $id = input('uid','0');
        $c = array();
        if(!empty($id)){
            $msg = findById('user',array('a.id'=>$id),'a.*');
            if($msg['code']=='1'){
                $c = $msg['data'];
            }
        }
        return view('',array('data'=>$c));
    }
    //手机端修改性别
    function changeSex(){
        $id = input('id','0');
        $sex = input('sex','2001001');
        return saveDataByCon('user',array('sex'=>$sex),array('id'=>$id),'修改性别');
    }

    /**
     * @return array
     */
    function saveUser(){
        $py=new Pinyin();
        $postData=input("request.");
//        halt($postData);
        if(!isset($postData['iqbtId'])||empty($postData['iqbtId'])){
            $postData["iqbtId"]=session("iqbtId");
        }
        $table="user";
        if(empty($postData["roleIds"])){
            return array('code'=>"0",'msg'=>'保存失败！请选择用户角色');
        }
        $roles=explode(",",$postData["roleIds"]);
        $postData['roleIds'] = implode(",",array_unique($roles));
        if(in_array(3,$roles)&&count($roles)>1){
            return array('code'=>"0",'msg'=>'保存失败！系统维护员和管理员不能同时选择');
        }

        if($postData["roleIds"]=='3'){
            $postData["userCate"]="1011003";
        }else{
            $chkRoles=getFieldArrry("UserRole",array('id'=>array("in",$roles)),"parentId");
            if(empty($chkRoles)){
                return array('code'=>"0",'msg'=>'保存失败！用户分类错误');
            }else if(count($chkRoles)>1){
                return array('code'=>"0",'msg'=>'保存失败！只能选择一类角色');
            }else{
                if($chkRoles[0]=='4'){
                    $postData["userCate"]="1011001";
                    if(empty($postData["etprsIqbtId"])){
                        return array('code'=>"0",'msg'=>'保存失败！超级管理员必须对应孵化器');
                    }
                }
                if($chkRoles[0]=='6'){
                    $postData["userCate"]="1011004";
                    if(empty($postData["districtId"])){
                        return array('code'=>"0",'msg'=>'保存失败！区域用户必须对应某个区域');
                    }
                }
            }
        }
        if(empty($postData["id"])){

            //$postData["password"]=md5($postData["name"]);
            $postData["password"]=md5("888888");

            if(!empty($postData["userCate"])&&$postData["userCate"]=="1011001"){
                //如果是管理员，默认分配的拜访企业是当前全部的入驻企业
                $map = array('iqbtId'=>$postData['iqbtId'],'status'=>'1001016');
                $etprsArr = getFieldArrry('enterprise',$map,'id');
                if(!empty($etprsArr)){
                    $postData['etprsId'] = implode(",",$etprsArr);
                }else{
                    $postData['etprsId'] = '';
                }
            }
        }


        /*if(!empty($postData["userCate"])&&$postData["userCate"]=="1011002"){
            //如果是企业用户，设置为企业用户
            $postData["roleIds"]="2";
        }
        if(!empty($postData["userCate"])&&$postData["userCate"]=="1011005"){
            //如果是企业用户，设置为企业用户
            $postData["roleIds"]="5";
        }*/
        $msg= saveData($table,$postData,"添加/修改");
        $userId=$msg["data"];
        //添加新用户是生成带参数的二维码，扫描二维码，绑定微信号
        if(empty($postData['id'])){
            if(config('open_wechat')){
                $jssdk = new Jssdk();
                $jssdk->makeqr($userId);
            }

        }
        if($msg["code"]==='1'){
            if(!empty($postData["userCate"])&&$postData["userCate"]=="1011005"){
                //如果是导师
                $tmsg=findById("tutor",array("userId"=>$userId),"id");
                if(!empty($tmsg["data"])){
                    $tutor["id"]=$tmsg["data"]["id"];
                }
                $tutor["mobile"]=$postData["mobile"];
                $tutor["email"]=$postData["email"];
                $tutor["sex"]=$postData["sex"];
                $tutor["name"]=$postData["name"];
                $tutor["userId"]=$userId;
                $tutor["adduserId"]=session("userId");
                $tutor["addtime"]=time();
                $tutor["iqbtId"]=$postData["iqbtId"];
                saveData("tutor",$tutor);
            }
        }

        return $msg;
    }
    
    //完善个人资料时保存信息
    function saveUserInfo(){
        $postData=input("request.");
        $table="user";
        $msg= saveData($table,$postData,"添加/修改");
        return $msg;
    }


    function deleteUser(){
        $id=input("id");
        return deleteData("user",$id,"删除用户");
    }

    //设置理员对应企业
    function setUserEtprs($userId)
    {
        $user=array();
        $userCon=array("id"=>$userId,"iqbtId"=>session("iqbtId"));
        $usermsg=findById("user",$userCon,"id,etprsId");
        if($usermsg["code"]==='1'&&!empty($usermsg["data"])){
            $user=$usermsg["data"];
        }
        $etprs=array();
        if(!empty($user)){
            $etprsIds=$user["etprsId"];
          //  $con=array("iqbtId"=>session("iqbtId"),'status'=>array("between","1001011,1001017"));
            $con=array("iqbtId"=>session("iqbtId"),'status'=>'1001016');
            $msg=getDataList("enterprise",$con,"id,name");
            if($msg["code"]==='1'){
                $etprs=$msg["data"];
                for ($i = 0; $i < count($etprs); $i++) {
                    $etprs[$i]["chk"]=0;
                    if(in_array($etprs[$i]["id"],explode(",",$etprsIds))){
                        $etprs[$i]["chk"]=1;
                    }
                }
            }
        }
        return view("",array("etprs"=>$etprs,"id"=>$userId));
    }
    function saveUserEtprs()
    {
        $id=input("id");
        $etprsId=input("etprsId");
        return saveDataByCon("user",array("etprsId"=>$etprsId),array("id"=>$id),"添加/修改");
    }
    //设置首页模块
    function setHomepage(){
        $id = input('id');
        if(empty($id)){
            return array('code'=>'0','msg'=>'参数错误');
        }
        $roleMsg = findByid('UserRole',array('id'=>$id),'*');
        if($roleMsg['code']==1 && !(empty($roleMsg['data']))){
            $homepageIds = $roleMsg['data']['homepageIds'];
        }
        if(!empty($homepageIds)){
            $roleArr = explode(",",$homepageIds);
        }else{
            $roleArr = array();
        }
        $homepageMsg = getDataList('UserHomepage',array('isDelete'=>0),'*');
        if($homepageMsg['code']==1 && !empty($homepageMsg['data'])){
            $homepageInfo = $homepageMsg['data'];
            foreach($homepageInfo as $key=>$value){
                $homepageInfo[$key]['chk'] = 0;
                if(in_array($value['id'],$roleArr)){
                    $homepageInfo[$key]['chk'] = 1;
                }
            }
            return view('',array('data'=>$homepageInfo,'id'=>$id));
        }
    }
    function saveHomepage(){
        $id = input('id');
        $homepageIds = input('homepageIds');
        return saveDataByCon('UserRole',array('homepageIds'=>$homepageIds),array('id'=>$id),'添加修改');
    }
    function resetpwd()
    {
        $py=new Pinyin();
        $id=input("id");
        $name=input("name");
        if(empty($id)){
            return returnResult("db_info","db_noid_info");
        }
        //重置密码，用户状态设置为正常
        return saveDataByCon("user",array("password"=>md5("888888"),'status'=>"1012001"),array("id"=>$id));
    }
    function savepwd(){
        $py=new Pinyin();
        $id=session("userId");
        $postData=input("request.");
        $password=$postData["password"];
        $newpassword=$postData["newpassword"];
        $confirmpassword=$postData["confirmpassword"];
        if($newpassword!=$confirmpassword){
            return returnResult("login_info","login_password2_error");
        }
        $dbpassword=getField("user",array("id"=>$id),"password");
        if($dbpassword!=md5($password)){
            return returnResult("login_info","login_password_error");
        }
        return saveDataByCon("user",array("password"=>md5($newpassword)),array("id"=>$id));
    }


    /***
     *  超级管理员维护的角色管理
     */
    function iqbtrole()
    {
        $etprsIqbtId=session("etprsIqbtId");
        $data=array();
        if(!empty($etprsIqbtId)){
            $msg=getDataList("incubator",array("etprsIqbtId"=>$etprsIqbtId),"id,name");
            if(!empty($msg['data'])){
                 $data=$msg["data"];
            }
        }
        return view("",array("iqbts"=>$data));
    }

    //获取角色列表
    function getRoles($iqbtId=0){
        $parentId=input("parentId");
        $roleId=session("user.roleIds");
        if(strpos(','.$roleId.',',',3,')!==false){
            //系统维护员只能维护 企业，超级管理员，管理员，系统维护员，导师,区域用户权限权限
            $con="id in (1,2,3,4,5,6) or parentId =6";
        }else{
            $uid=session("userId");
            $umsg=findById("user",array('id'=>$uid),"iqbtId,etprsIqbtId");
            $con=array();
            if(!empty($umsg["data"])){
                if(!empty($umsg["data"]["iqbtId"])){
                    $con2["id"]=$umsg["data"]["iqbtId"];
                }
                if(!empty($umsg["data"]["etprsIqbtId"])){
                    $con2["etprsIqbtId"]=$umsg["data"]["etprsIqbtId"];
                }
            }
            if(!empty($con2)){
                $ids=getFieldArrry("incubator",$con2,"id");
                $ids[]=0;
                $con["iqbtId"]=array("in",$ids);
            }
            //孵化器管理员不能维护 企业，系统维护员权限
            $con["id"]=array("not in",'2,3,5,6');
            $con["parentId"]=array("not in",'6');
            //$con["iqbtId"]=array("in",'0,'.session("iqbtId"));
            if(!empty($parentId)){
                //显示下级所有角色
                $con["parentId"]=$parentId;
            }
        }
        if(!empty($iqbtId)){
            $con["iqbtId"]=$iqbtId;
        }
        $table="UserRole";
        $sequence="sort desc,level asc";//排序
        $msg=getDataList($table,$con,"*",$sequence);
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'parentId','fieldname'=>'parentText'),"UserRole","id,rolename",array("level"=>1))));
        return $msg["data"];
    }
    function addRole($iqbtId=0){
        $id=input("id");
        $c=array();
        $iqbts=array();
        if(!empty($id)){
            $msg=findByid("UserRole",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        $uid=session("userId");
        $umsg=findById("user",array('id'=>$uid),"iqbtId,etprsIqbtId");
        $con=array();
        if(!empty($umsg["data"])){
            if(!empty($umsg["data"]["iqbtId"])){
                $con["id"]=$umsg["data"]["iqbtId"];
            }
            if(!empty($umsg["data"]["etprsIqbtId"])){
                $con["etprsIqbtId"]=$umsg["data"]["etprsIqbtId"];
            }
        }
        if(!empty($iqbtId)){
            $con["id"]=$iqbtId;
        }
        if(!empty($con)){
             $imsg=getDataList("incubator",$con,"id,name");
            if(!empty($imsg["data"])){
                $iqbts=$imsg["data"];
            }
        }

        return view("",array("data"=>$c,'iqbts'=>$iqbts));
    }
    function saveRole(){
        $postData=input("request.");
        $id=$postData["id"];
        /*if(empty($id)||$id>4){
            $postData["iqbtId"]=session("iqbtId");
        }*/
        $table="UserRole";
        if(!empty($postData["id"])&&$postData["level"]>1){
            $chkmsg=getDataList($table,array('parentId'=>array('in',$postData["id"])),"id");
            if(!empty($chkmsg["data"])){
                return array('code'=>"0",'msg'=>'保存失败！存在下级角色',array());
            }
        }
        $con=array("rolename"=>$postData['rolename'],'iqbtId'=>$postData["iqbtId"]);
        if(!empty($id)){
            $con['id']=array("!=",$id);
        }
        $cmsg=findById($table,$con,"id");
        if(!empty($cmsg['data'])){
            return array('code'=>"0",'msg'=>'当前孵化器已经存在该角色名',array());
        }
        if(empty($postData["isRole"])){
            $postData["isRole"]=0;
        }else{
            $postData["isRole"]=1;
        }
        $msg= saveData($table,$postData,"添加/修改");
        return $msg;
    }
    function deleteRole(){
        $id=input("id");
        $msg=getDataList("UserRole",array('parentId'=>array('in',$id)),"id");
        if(!empty($msg["data"])){
            return array('code'=>"0",'msg'=>'删除失败存在下级角色',array());
        }
        if($id<=5){
            return array('code'=>"0",'msg'=>'当前角色不能删除',array());
        }
        return deleteData("UserRole",$id,"删除角色");
    }

    function setRoleMenu() {
        $id=input("id");//角色ID
        $parentId=input("parentId");
        $roots=array();
        $con="1=1 ";
        $iqbtId=0;
        if(empty($id)){
            return view("",array("menus"=>$roots,"id"=>$id));
        }
        //获取当前角色上一级被分配的权限。当前角色不能拥有超过上级的权限。
        if(!empty($parentId)){
            $parentMenuIds=0;
            $rolemsg=findById("UserRole",array("id"=>$parentId),"id,rolename,menuIds");
            //判断用户角色上级角色 是否分配权限
            if(!empty($rolemsg["data"])){
                //$con["id"]=array("in",$parentMenuIds);
                $parentMenuIds=$rolemsg["data"]["menuIds"];
                $con.=" and id in(".$parentMenuIds.")";
            }else{
                return view("",array("menus"=>$roots,"id"=>$id));
            }
        }

        if(!empty($id)){
            $rolemsg2=findById("UserRole",array("id"=>$id),"iqbtId");
            if(!empty($rolemsg2["data"])){
                $iqbtId=$rolemsg2["data"]["iqbtId"];
            }
        }
        //获取当前孵化器被分配的权限
        $roleId=session("user.roleIds");
        if(strpos(','.$roleId.',',',3,')===false&&!empty($iqbtId)){
            //非系统维护员
            /*$etprsIqbtId=session("etprsIqbtId");
            $iqbtMenuIds=getField("EtprsIqbt",array("id"=>$etprsIqbtId),"menuIds");*/
            /*$iqbtId=session("iqbtId");
            $iqbtMenuIds=getField("incubator",array("id"=>$iqbtId),"menuIds");*/
            $iqbtMenuIds='';
            //$iqbtId=session("iqbtId");//因为如果是超级管理员，不存在iqbt，只有etprsIqbtId
            $join = [['user_packages b','a.packageId=b.id',"left"]];
            $iqbtMenumsg=findById("incubator",array("a.id"=>$iqbtId),"b.menuIds",$join);
            if(!empty($iqbtMenumsg["data"])){
                $iqbtMenuIds=$iqbtMenumsg["data"]["menuIds"];
            }
            if(!empty($iqbtMenuIds)){
                //孵化器用户需要使用系统维护员分配的功能
                $con=$con." and id in(".$iqbtMenuIds.")";
                //$con["id"]=array("in",$iqbtMenuIds);
            }else{
                //当前孵化器没有分配功能菜单
                return view("",array("menus"=>$roots,"id"=>$id));
            }
        }
        $menuMsg=getDataList("UserMenu",$con,"id,name,parentId,desc,level","sort desc");
        if($menuMsg["code"]==="1"){
            $menus=$menuMsg["data"];
            $roleMenuText="";
            $roleMenuMsg=findById("UserRole",array("id"=>$id),"menuIds");
            if($roleMenuMsg["code"]==='1'&&!empty($roleMenuMsg["data"])){
                $roleMenuText=$roleMenuMsg["data"]["menuIds"];
            }
            $roleMenuIds=explode(",",$roleMenuText);
            //print_r($menus);die();

            for ($i = 0; $i < count($menus); $i++) {
                $menu=$menus[$i];
                $menu["chk"]=false;
                $menu["enable"]=true;
                $menuId=$menu["id"];
                if(in_array($menuId,$roleMenuIds)){
                    $menu["chk"]=true;
                }

                if(empty($iqbtId)&&strpos(','.$roleId.',',',3,')===false){

                    $menu["enable"]=false;
                }
                if(!empty($iqbtId) && $roleId!=1){
                    if($menu['id']==94){
                        $menu["enable"]=false;
                    }
                }

                switch($menu["level"]){
                    case 1:
                        //$menu["sub"]=array();
                        $roots[]=$menu;
                        break;
                    case 2:
                        //$menu["third"]=array();
                        $roots[count($roots)-1]["sub"][]=$menu;
                        break;
                    case 3:
                        if(!empty($roots[count($roots)-1]["sub"])){
                            $roots[count($roots)-1]["sub"][count($roots[count($roots)-1]["sub"])-1]["third"][]=$menu;
                        }
                        break;
                    default;
                        break;
                }
            }
        }
        return view("",array("menus"=>$roots,"id"=>$id));
    }

    function saveRoleMenu()
    {
        $id=input("id");
        $menuIds=input("menuIds");
        $roleId=session("user.roleIds");
        if(strpos(','.$roleId.',',',3,')===false&&strpos(',1,2,3,4,',','.$id.',')!==false){
            //非系统维护员不能设置，企业，超级管理员，管理员，系统维护员权限。
            return array('code' => '0', 'msg' => "非系统维护员不能设置，企业，超级管理员，管理员，系统维护员权限。", 'data' => array());
        }
        return saveDataByCon("UserRole",array("menuIds"=>$menuIds),array("id"=>$id));
    }
    //获取菜单列表
    /*function menu()
    {

        return view("",array("tag"=>'ssss'));
    }*/
    function getMenus(){
        $con=array('level'=>array("lt",3));
        $table="UserMenu";
        $sequence="sort desc";//排序
        $msg=getDataList($table,$con,"*",$sequence);
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'parentId','fieldname'=>'parentText'),"UserMenu","id,name",array())));

        return $msg["data"];
    }
    function getCldMenus($rootId){
        $con=array('iqbtId'=>session("user.iqbtId"));
        if(!empty($rootId)){
            $con["rootId"]=$rootId;
        }
        $table="UserMenu";
        $sequence="sort desc";//排序
        $msg=getDataList($table,$con,"*",$sequence);
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'parentId','fieldname'=>'parentText'),"UserMenu","id,name",array('iqbtId'=>session("user.iqbtId")))));
        return $msg["data"];
    }
    function addMenu(){
        $id=input("id");
        $c=array("level"=>1);
        if(!empty($id)){

            $msg=findByid("UserMenu",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveMenu(){
        $postData=input("request.");
        $table="UserMenu";
        //$postData["iqbtId"]=session("user.iqbtId");
        /*if(!empty($postData["id"])&&$postData["level"]>1){
            $chkmsg=getDataList($table,array('parentId'=>array('in',$postData["id"])),"id");
            if(!empty($chkmsg["data"])){
                return array('code'=>"0",'msg'=>'保存失败！存在下级菜单',array());
            }
        }*/
        $url=$postData["url"];

        $msg= saveData($table,$postData,"添加/修改");
        return $msg;
    }
    function deleteMenu(){
        $id=input("id");
        $msg=getDataList("UserMenu",array('parentId'=>array('in',$id)),"id");
        if(!empty($msg["data"])){
            return array('code'=>"0",'msg'=>'删除失败！存在下级菜单',array());
        }
        return deleteData("UserMenu",$id,"删除菜单");
    }
    function initMenu($parentId)
    {
        $msg=gethashmap("UserMenu",array("parentId"=>$parentId),"id,name");
        if($msg["code"]==='1'){
            return $msg["data"];
        }else{
            return array();
        }
    }

    //孵化器用户
    function getEtprsIqbt()
    {
        $etprsiqbts=array();
        $iiqbts=array();
        $msg=getDataList("EtprsIqbt",array(),"a.*,'0' as iqbtcate","a.addtime desc");
        $msg2=getDataList("incubator",array("etprsIqbtId"=>'0','status'=>'1'),"*,'1' as iqbtcate");

        if($msg["code"]==="1"){
            $etprsiqbts=$msg["data"];
            for ($i = 0; $i < count($etprsiqbts); $i++) {
                $eiId=$etprsiqbts[$i]["id"];
                $iqbtmsg=getDataList("incubator",array("etprsIqbtId"=>$eiId,'status'=>'1'),"a.*","a.createtime desc");
                if($iqbtmsg["code"]==="1"){
                    $iqbts=$iqbtmsg["data"];
                }
                $etprsiqbts[$i]["iqbts"]=$iqbts;
            }

        }else{
            $etprsiqbts= array();
        }
        if(!empty($msg2["data"])){
            $iiqbts=$msg2["data"];
        }
        return array_merge($etprsiqbts,$iiqbts);
    }

    function iqbtlist($id=0)
    {
        return view("",array("id"=>$id));
    }

    function areaiqbtlist()
    {
        $districtId=session("user.districtId");
        return view("",array("districtId"=>$districtId));
    }

    //导出孵化器用户的数据统计
    function exportIqbtStat($ids=''){
        if(!empty($ids)){
            $arrId = explode(",",$ids);
            $iqbtMsg = getDataList('incubator',array('id'=>array('in',$arrId)),'id,name');

            if($iqbtMsg['code']==1 && !empty($iqbtMsg['data'])){
                $data = array();
                foreach($iqbtMsg['data'] as $key=>$value){
                    $data[$key] = self::iqbtStatisData([$value['id']]);
                    $data[$key]['iqbtName'] = $value['name'];
                }
                $filename = '孵化器统计数据表';

                $header = array('孵化器名称','在孵企业（个）','申请中企业（个）','完成孵化企业（个）','房间总数（个）','已入驻房间数（个）','工位总数（个）','已使用工位数（个）',
                    '累计房间面积（㎡）','使用房间面积（㎡）','孵化器数量（个）','高新企业数（个）','带动就业数（人）','应届大学生（人）','千人计划（人）','博士（人）',
                    '留学生数（人）','申请专利数（件）','累计投入（万元）','营业额（万元）','缴税额（万元）','技术成交额（万元）','获天使投资（万元）');
                $data1 = array();//重新调整一下导出的顺序，和header对应
                foreach($data as $key=>$val){
                    $data1[$key]['iqbtName'] = $val['iqbtName'];
                    $data1[$key]['ingnum'] = $val['ingnum'];
                    $data1[$key]['aplnum'] = $val['aplnum'];
                    $data1[$key]['gradtnum'] = $val['gradtnum'];
                    $data1[$key]['roomnum'] = $val['roomnum'];
                    $data1[$key]['etroomnum'] = $val['etroomnum'];
                    $data1[$key]['unitnum'] = $val['unitnum'];
                    $data1[$key]['etunitnum'] = $val['etunitnum'];
                    $data1[$key]['roomarea'] = $val['roomarea'];
                    $data1[$key]['etroomarea'] = $val['etroomarea'];
                    $data1[$key]['iqbtnum'] = 1;
                    $data1[$key]['highetprs'] = $val['highetprs'];
                    $data1[$key]['staffnum'] = $val['staffnum'];
                    $data1[$key]['student'] = $val['student'];
                    $data1[$key]['thousand'] = $val['thousand'];
                    $data1[$key]['doctor'] = $val['doctor'];
                    $data1[$key]['overseas'] = $val['overseas'];
                    $data1[$key]['invent'] = $val['invent'];
                    $data1[$key]['rdinput'] = $val['rdinput'];
                    $data1[$key]['income'] = $val['income'];
                    $data1[$key]['tax'] = $val['tax'];
                    $data1[$key]['tct'] = $val['tct'];
                    $data1[$key]['investment'] = $val['investment'];
                }
                vendor("PHPExcel");
                vendor("PHPExcel.Writer.Excel5");
                vendor("PHPExcel.IOFactory");
                getExcel($filename,$header,$data1);
            }



        }
    }
    //孵化器用户
    function getIqbtlist($id=0,$districtId=0)
    {
        if(empty($id)){
             $id=session("etprsIqbtId");
        }

        if(!empty($districtId)){
            $con=array("districtId"=>array('like',$districtId."%"));
        }else{
            $con=array("etprsIqbtId"=>$id);
        }
        $msg=getDataList("incubator",$con,"a.*","a.addtime desc");
        if($msg["code"]==="1"){
            $iqbts=$msg["data"];
            $tmplist=self::getDictStr("level","incubator");
            $iqbts=$this->setListIdText($iqbts,$tmplist);
            for ($i = 0; $i < count($iqbts); $i++) {
                $iqbtId=$iqbts[$i]["id"];
                //管理员列表
                $join = [['user_role b','a.roleIds=b.id',"left"]];
                $users=array();
                $usermsg=getDataList("user",array("a.iqbtId"=>$iqbtId,"a.status"=>'1012001',"a.userCate"=>"1011001"),"a.*,b.rolename","a.id desc",$join);
                if($usermsg["code"]==="1"){
                    $users=$usermsg["data"];
                }
                $iqbts[$i]["users"]=$users;

                //导师列表
                $tutors=array();
                $tutormsg=getDataList("tutor",array("a.iqbtId"=>$iqbtId),"a.*","a.addtime desc");
                if($tutormsg["code"]==="1"){
                    $tutors=$tutormsg["data"];
                }
                $iqbts[$i]["tutors"]=$tutors;
                //在孵企业列表
                $etprss=array();
                $etprsmsg=getDataList("enterprise",array("a.iqbtId"=>$iqbtId,'status'=>'1001016'),"a.*","a.addtime desc");
                if($etprsmsg["code"]==="1"){
                    $etprss=$etprsmsg["data"];
                }
                foreach($etprss as $key=>$value){
                    $etprss[$key]['entertime'] = date('Y-m-d',$value['entertime']);
                    $etprss[$key]['pactquittime'] = date('Y-m-d',$value['pactquittime']);
                }
                $iqbts[$i]["etprss"]=$etprss;
                $iqbts[$i]["statis"]=self::iqbtStatisData([$iqbtId]);
            }
            return $iqbts;
        }else{
            return array();
        }
    }
    function addEtprsIqbt()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("EtprsIqbt",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveEtprsIqbt(){
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="EtprsIqbt";
        return saveData($table,$postData,"添加/修改企业孵化器");
    }
    function deltEtprsIqbt(){
        $id=input("id");
        $eimsg= deleteData("EtprsIqbt",$id,"删除企业孵化器");
        if($eimsg["code"]==="1") {
            $msg=getDataList("incubator",array('etprsIqbtId' => $id));
            if ($msg["code"] === "1") {
                $iqbts=$msg["data"];
                $ids="";
                if(!empty($iqbts)){
                    foreach ($iqbts as $iqbt) {
                        $ids.=$iqbt["id"].",";
                    }
                }

                $umsg=saveDataByCon("User", array("isDelete" => 1), array('iqbtId' => array("in",trim($ids,","))));
            }
            saveDataByCon("incubator", array("isDelete" => 1), array('etprsIqbtId' => $id));
            saveDataByCon("User", array("isDelete" => 1), array('etprsIqbtId' => $id));//超级管理员
        }
        return $msg;
    }


    function logout()
    {

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

    function addIqbt($etprsIqbtId='0',$id=0)
    {
        $data=array();
        if(empty($etprsIqbtId)){
            $etprsIqbtId=session("etprsIqbtId");
        }
        if(!empty($etprsIqbtId)){
            $data["etprsIqbtId"]=$etprsIqbtId;
        }
        if(!empty($id)){

            $join = [['sys_file b','a.bgimg=b.id',"left"]];
            $msg=findByid("incubator",array("a.id"=>$id),"a.*,b.savePath",$join);

            if($msg["code"]==='1'){
                $data=$msg["data"];

                //读取logo图片的地址
                $logoPath = '';
                if(!empty($data['logo'])){
                    $logoPath = getField('sys_file',array('id'=>$data['logo']),'savePath');
                }
                $data['logoPath'] = $logoPath;
            }
        }
        if(!empty($data["districtId"])){
            $dmsg=findById("region",array("id"=>$data["districtId"]),"id,level,provinceid,cityid");
            if(!empty($dmsg["data"])){
                if($dmsg["data"]["level"]==1){
                    $data["province"]=$dmsg["data"]["id"];
                    $data["city"]="";
                    $data["district"]="";
                }else if($dmsg["data"]["level"]==2){
                    $data["province"]=$dmsg["data"]["provinceid"];
                    $data["city"]=$dmsg["data"]["id"];
                    $data["district"]="";
                }else if($dmsg["data"]["level"]==3){
                    $data["province"]=$dmsg["data"]["provinceid"];
                    $data["city"]=$dmsg["data"]["cityid"];
                    $data["district"]=$dmsg["data"]["id"];
                }
            }
        }
        return view("",array("data"=>$data,'etprsIqbtId'=>$etprsIqbtId));
    }

    function saveIqbt(){
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        if(!empty($postData['exptime'])){
            $postData['exptime'] = strtotime($postData['exptime']);
            if($postData['exptime']<=time()){
                return array('code'=>0,'msg'=>'孵化器到期时间不能小于当期时间');
            }

        }
        $postData["addtime"]=time();
        $table="incubator";
        if(!empty($postData["services"])&&is_array($postData["services"])){
            $postData["services"]=join(",",$postData["services"]);
        }
        if(!empty($postData["facility"])&&is_array($postData["facility"])){
            $postData["facility"]=join(",",$postData["facility"]);
        }
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

    function initRegion($id)
    {
        $data=array();
        if(empty($id)){
             return $data;
        }
        $msg=getDataList("region",array("parentId"=>$id),"id,name");
        if(!empty($msg["data"])){
             return $msg["data"];
        }else{
            return array();
        }
    }

    function iqbtStatisData($iqbtIds=array())
    {
        $data=array('ingnum'=>0,'aplnum'=>0,'gradtnum'=>0,'roomnum'=>0,'etroomnum'=>0,'unitnum'=>0,'etunitnum'=>0,'roomarea'=>0,'etroomarea'=>0,'iqbtnum'=>count($iqbtIds),
            'staffnum'=>0,'overseas'=>0,'doctor'=>0,'thousand'=>0,'student'=>0,'highetprs'=>0,'rdinput'=>0,'income'=>0,'tax'=>0,'tct'=>0,'invent'=>0,'investment'=>0);
        if(empty($iqbtIds)){
             return $data;
        }
        //在孵企业
        $data["ingnum"]=getField("enterprise",array("iqbtId"=>array("in",$iqbtIds),"status"=>'1001016'),"count(id) as ingnum");
        //申请企业
        $data["aplnum"]=getField("enterprise",array("iqbtId"=>array("in",$iqbtIds),"status"=>array("in",array('1001011','1001012','1001013','1001014','1001015'))),"count(id) as aplnum");
        //完成孵化企业
        $data["gradtnum"]=getField("enterprise",array("iqbtId"=>array("in",$iqbtIds),"status"=>'1001017'),"count(id) as gradtnum");

        //房间总数
        $data["roomnum"]=getField("EstateRoom",array("iqbtId"=>array("in",$iqbtIds),"type"=>'1'),"count(id) as num");
        //已入驻房间数
        $data["etroomnum"]=getField("EstateRoom",array("iqbtId"=>array("in",$iqbtIds),"type"=>'1','status'=>array("in",[1,2])),"count(id) as num");//已分配和正常使用的房间均属于已入驻房间

        //工位数
        $data["unitnum"]=getField("EstateRoom",array("iqbtId"=>array("in",$iqbtIds),"type"=>'0'),"count(id) as num");
        //已使用工位数
        $data["etunitnum"]=getField("EstateRoom",array("iqbtId"=>array("in",$iqbtIds),"type"=>'0','status'=>array("in",[1,2])),"count(id) as num");

        //累积房间面积
        $data["roomarea"]=getField("EstateRoom",array("iqbtId"=>array("in",$iqbtIds),"type"=>'1'),"sum(totalarea) as area");
        //使用房间面积
        $data["etroomarea"]=getField("EstateRoom",array("iqbtId"=>array("in",$iqbtIds),"type"=>'1','status'=>array("in",[1,2])),"sum(totalarea) as area");

        //带动就业数，留学生数，博士，千人计划，应届大学生，高新企业数
        $sql="SELECT IFNULL(SUM(total),0) as usertotal,IFNULL(SUM(overseas),0) as overseas,IFNULL(SUM(doctor),0) as doctor,IFNULL(SUM(thousand),0) as thousand,IFNULL(SUM(student),0) as student,IFNULL(SUM(highetprs),0) as highetprs   from (SELECT  id,etprsId,iqbtId,`month`,overseas,doctor,thousand,student,total,highetprs FROM (SELECT id,etprsId,iqbtId,`month`,overseas,doctor,thousand,student,total,highetprs from ibt_statement where iqbtId in (".join(',',$iqbtIds).") ORDER BY `month` desc) as tmptab GROUP BY etprsId) as tmptab2 group BY iqbtId;";
        $userdata=Db::query($sql);
        if(!empty($userdata)){
            $data["staffnum"]=$userdata[0]["usertotal"];
            $data["overseas"]=$userdata[0]["overseas"];
            $data["doctor"]=$userdata[0]["doctor"];
            $data["thousand"]=$userdata[0]["thousand"];
            $data["student"]=$userdata[0]["student"];
            $data["highetprs"]=$userdata[0]["highetprs"];
        }
        //累积投入，营业额，缴税额，技术成交额,申请专利数，获天使投资
        $con=array("iqbtId"=>array("in",$iqbtIds));
        $field="IFNULL(SUM(rdinput),0) as rdinput,IFNULL(SUM(income),0) as income,IFNULL(SUM(tax),0) as tax,IFNULL(SUM(tct),0) as tct,IFNULL(SUM(invent),0) as invent,IFNULL(SUM(investment),0) as investment";
        $msg=findById("statement",$con,$field);
        if(!empty($msg["data"])){
            $data["rdinput"]=$msg["data"]["rdinput"];
            $data["income"]=$msg["data"]["income"];
            $data["tax"]=$msg["data"]["tax"];
            $data["tct"]=$msg["data"]["tct"];
            $data["invent"]=$msg["data"]["invent"];
            $data["investment"]=$msg["data"]["investment"];
        }
        return $data;
    }

    //套餐
    function getpackages(){

        $table="UserPackages";
        $msg=getDataList($table,array(),"*");
        return $msg["data"];
    }
    function addpackage(){
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("UserPackages",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function savepackage(){
        $postData=input("request.");

        $msg= saveData("UserPackages",$postData,"添加/修改套餐");
        return $msg;
    }
    function deletepackage(){
        $id=input("id");
        return deleteData("UserPackages",$id,"删除套餐");
    }
    function setPackageMenuIds()
    {
        $id=input("id");
        $flag=input("flag");
        $roots=array();
        //筛选非系统维护员的权限菜单
        $con=array('isadmin'=>'0');
        if(empty($id)){
            return view("",array("menus"=>$roots,"id"=>$id));
        }

        $menuMsg=getDataList("UserMenu",$con,"id,name,parentId,desc,level","sort desc");
        //print_r(json_encode($menuMsg));die();
        if($menuMsg["code"]==="1"){
            $menus=$menuMsg["data"];
            $iqbtMenuText="";
            $iqbtMenuMsg=findById("UserPackages",array("id"=>$id),"menuIds");
            if($iqbtMenuMsg["code"]==='1'&&!empty($iqbtMenuMsg["data"])){
                $iqbtMenuText=$iqbtMenuMsg["data"]["menuIds"];
            }
            $roleMenuIds=explode(",",$iqbtMenuText);

            for ($i = 0; $i < count($menus); $i++) {
                $menu = $menus[$i];
                $menu["chk"] = false;
                $menuId = $menu["id"];
                if (in_array($menuId, $roleMenuIds)) {
                    $menu["chk"] = true;
                }
                switch ($menu["level"]) {
                    case 1:
                        $roots[] = $menu;
                        break;
                    case 2:
                        $roots[count($roots) - 1]["sub"][] = $menu;
                        break;
                    case 3:
                        $roots[count($roots) - 1]["sub"][count($roots[count($roots) - 1]["sub"]) - 1]["third"][] = $menu;
                        break;
                    default;
                        break;
                }
            }



            /*for ($i = 0; $i < count($menus); $i++) {
                $menus[$i]["chk"]=false;
                $menuId=$menus[$i]["id"];
                if(in_array($menuId,$roleMenuIds)){
                    $menus[$i]["chk"]=true;
                }
                if($menus[$i]["parentId"]==0){
                    $roots[]=$menus[$i];
                }
            }
            for ($i = 0; $i < count($roots); $i++) {
                foreach($menus as $menu){
                    if($menu["parentId"]==$roots[$i]["id"]){
                        $roots[$i]["sub"][]=$menu;
                    }
                }
            }*/
        }
        //print_r(json_encode($roots));die();
        return view("",array("menus"=>$roots,"id"=>$id,'flag'=>$flag));
    }
    function savePackageMenuIds()
    {
        $id=input("id");
        $menuIds=input("menuIds");
        $flag=input("flag");
        $roleId=session("user.roleIds");
        if(strpos(','.$roleId.',',',3,')===false){
            //非系统维护员不能设置，企业，超级管理员，管理员，系统维护员权限。
            return array('code' => '0', 'msg' => "非系统维护员不能设置孵化器管理权限。", 'data' => array());
        }
        /*$con=array();
        if(empty($flag)){
            $con=array('etprsIqbtId'=>$id);
            saveDataByCon("etprsIqbt",array("menuIds"=>$menuIds),array('id'=>$id));
        }else{
            $con=array('id'=>$id);
        }*/
        return saveDataByCon("UserPackages",array("menuIds"=>$menuIds),array('id'=>$id));
    }

    //登录日志
    function getuserLoginLog(){

        $join = [['user b','a.userId=b.id']];
        $con = array(
            'b.userCate'=>array('neq','1011002')
        );
        $param = input('request.');
        if(!empty($param['name'])){
            $con['b.name'] = array('like','%'.$param['name'].'%');
        }
        if(!empty($param['start'])&&!empty($param['end'])){
            $start = strtotime($param['start']);
            $end = strtotime($param['end']);
            $arr = [$start,$end];
            $con['a.addtime'] = array('between',$arr);
        }
        $msg = getDataList('recordLogin',$con,'a.loginTime,a.loginResult,a.loginIp,b.name,b.userCate,b.iqbtId,b.etprsIqbtId','a.id desc',$join);
        if($msg['code']==1 && !empty($msg['data'])){
           foreach($msg['data'] as $key =>$value){
                if(!empty($value['iqbtId'])){
                    $msg['data'][$key]['iqbtName'] = getField('incubator',array('id'=>$value['iqbtId']),'name');
                }elseif(!empty($value['etprsIqbtId'])){
                    $msg['data'][$key]['iqbtName'] = getField('etprsIqbt',array('id'=>$value['etprsIqbtId']),'name');
                }else{
                    $msg['data'][$key]['iqbtName'] = "中联慧云孵化器管理系统";
                }
           }
            return $msg['data'];
        }else{
            return array();
        }

    }


    //管理员添加毕业企业
    function addQuitEtprs(){
        return view();
    }
    //保存信息
    function saveQuitEtprs(){
        $postData=input("request.");
        $etprsData = array(
            'name'=>$postData['name'],
            'lealPerson'=>$postData['lealPerson'],
            'status'=>'1001017',
            'contact'=>$postData['leader'],
            'rgsttime'=>$postData['rgsttime'],
            'mobile'=>$postData['mobile'],
            'iqbtId'=>session('iqbtId'),
            'addtime'=>time(),
            'entertime'=>strtotime($postData['entertime']),
            'quittime'=>strtotime($postData['quittime']),
            'pactquittime'=>strtotime($postData['quittime']),
            'total'=>$postData['total'],
            'apltype'=>0

        );
        $m1=saveData("enterprise",$etprsData,"添加毕业企业");
        if($m1['code']==1){
            $etprsId = $m1['data'];
        }else{
            return $m1;
        }
        if(isset($postData['worktype']) &&is_array($postData['worktype'])){
            $postData['worktype'] = implode(",",$postData['worktype']);
        }else{
            $postData['worktype'] ='1025007';
        }
        $postData["etprsId"]=$etprsId;
        $postData['planintime'] = $postData['entertime'];
        unset($postData['entertime']);
        unset($postData['quittime']);
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $postData["type"]="etprs";
        $msg= saveData("etprsApl",$postData,"添加毕业企业");
        if($msg["code"]==='1'){
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
            $data["addtime"]=time();
            saveData('etprsInfo',$data,'企业维护信息初始值');
        }
        return $msg;
    }

}