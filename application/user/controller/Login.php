<?php
namespace app\user\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;
use app\user\library\Pinyin;
use org\weixin\Jssdk;
use think\Log;

class Login extends Controller{
    public function __construct()
    {
        if(request()->isMobile()){
            //  echo 'aaa';exit();
            config('template.view_base',APP_PATH.'mobile/');
        }
        parent::__construct();
    }

    function login($etprsIqbtId=0,$iqbtId=0,$ret=''){
        $py=new Pinyin();
        //根据绑定的二级域名，切换背景图片和孵化器名称
        $url = request()->domain();
        if(preg_match("/^(https:\/\/).*$/",$url)){
            $url = substr($url,8);
        }else{
            $url = substr($url,7);
        }
        $img ='/login/images/2.jpg';
        $name = "慧云数字化园区管理云平台";
        //从孵化器里查找这个域名是否绑定
        $iqbtmsg = findById('incubator',array('domain'=>$url),'id,etprsIqbtId,name,bgimg');
        $tmpiqbtId=0;
        if($iqbtmsg['code']==1 && !empty($iqbtmsg['data'])){
            $tmpiqbtId = $iqbtmsg['data']['id'];
            $etprsIqbtId = $iqbtmsg['data']['etprsIqbtId'];
            $name= $iqbtmsg['data']['name'];
            if(!empty($iqbtmsg['data']['bgimg'])){
                $bgPath = getField('sysFile',array('id'=>$iqbtmsg['data']['bgimg']),'savePath');
                if(!empty($bgPath)){
                    $img = $bgPath;
                }
            }
        }
        //微信登录链接生成方式
       // $url = 'https://b.zlhuiyun.com/wechat/Auth/index'; 网站应用回调访问的地址
       // $url = urlencode($url); APPID  在微信开放平台添加的网站应用，审核通过后生成的APPID
       // $link = 'https://open.weixin.qq.com/connect/qrconnect?appid=wx3302d847bfe5dc28&redirect_uri='.$url.'&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect';

        if (request()->isPost()){
            $uname=input("name");
            $password=trim(input("password"));
            $error="";
            $map = array('a.name'=>$uname);

            $join = [['incubator b','a.iqbtId=b.id AND b.isDelete = 0','left']];
            $msg = findById("user",$map,"a.*,b.id as iqbtabId,b.logo,b.exptime",$join);
            if(empty($msg["data"])||$msg['code']==0){
                $error = '用户不存在！';
            }else{
                $loginResult=0; //登录结果： 0：登录成功 1：登录失败
                $user=$msg["data"];
                //超级管理员可以在所有子域名下登录，其他的不能
//                Log::notice($iqbtId);
//                Log::notice($user['roleIds']);
//                Log::notice(json_encode($user));
                //判断二级域名的孵化器是否为空
                if($tmpiqbtId !='0'){
                    //判断登陆用户是否为超级管理员或者系统维护员
                    if(!in_array($user['roleIds'],['1','3'])){
                        //判断用户所属的孵化器和二级域名的孵化器是否一致
                        if($user['iqbtId'] != $tmpiqbtId){
                            return view("",array("msg"=>"用户不存在","iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name));
                        }
                    }
                }

                $userState=$user["status"];
                if($userState=='1012002'){
                    return view("",array("msg"=>"用户状态异常"));
                }
                if($userState=='1012005'){
                    return view("",array("msg"=>"当前账户已被冻结，请联系管理员","iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name));
                }
                //exptime 过期时间
                if(!empty($user['exptime'])){
                    if($user['exptime']<=time()){
                        return view("",array('msg'=>'当期孵化器使用期限已到期，无法正常使用',"iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name));
                    }
                }

                //如果企业的状态是退出，则查询是否已经退费，如果已经退费，禁止登陆
                //1011002 企业用户
                //1012001 正常
                if($user["userCate"]=="1011002" && $userState=='1012001'){
                    if(!empty($user['etprsId'])){
                        $etprsState = getField('enterprise',array('id'=>$user['etprsId']),'status');
                        //1001017 项目完成
                        if($etprsState == '1001017'){
                            //再查询是否有退费
                            $feemap = array(
                                'etprsId'=>$user['etprsId'],
                                'status'=>array('in','0,1'),
                                'iqbtId'=>$user['iqbtId']
                            );
                            $feeMsg = getDataList('feeQuitRcd',$feemap,'id');
                            if($feeMsg['code']==1 && empty($feeMsg['data'])){
                                //如果企业状态是退出状态并且没有待退费记录，则禁止登陆
                                return view("",array('msg'=>'您已经完全退出，没有登陆权限',"iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name));
                            }
                        }
                    }

                }
                if($user["password"]!==md5($password)){
                    $loginResult=1;
                    $errlogtime=$user["loginErrTime"];
                    $error = "密码错误！您已经失败".($errlogtime+1)."次，共5次机会";
                    if($errlogtime>=5){
                        //echo "错误次数超过五次";
                        $errlogin["status"]="1012002";
                        $error = "密码错误！您密码错误5次，账号已被锁定，请联系管理员重置密码！";
                    }
                    $errlogin["loginErrTime"]=$user["loginErrTime"]+1;
                    $errlogin["id"]=$user["id"];
                    saveData("user",$errlogin);
                    //登录日志
                    self::loginlog($user,$loginResult,$password);
                }else{

                    //企业注册后还没有申请入驻：状态：1012003
                    /*if($user["status"]!="1012003"){
                        if(strpos(','.$user["roleIds"].',',',3,')===false&&empty($user["iqbtabId"])){
                            //非系统维护员，且匹配不到孵化器
                            return view("",array("msg"=>"非系统维护员，匹配不到孵化器"));
                        }
                    }*/
                    //获取logo并缓存
                    $logo = '/img/hylogo.png';
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
                    session('userId', $user["id"]);
                    if($user["userCate"]=="1011002"){
                        session('etprsId', $user["etprsId"]);
                    }
                    session('iqbtId', $user["iqbtId"]);
                    session("etprsIqbtId",$user["etprsIqbtId"]);

                    //修改密码错误次数
                    $suclogin["loginErrTime"]=0;
                    $suclogin["id"]=$user["id"];
                    saveData("user",$suclogin);

                    self::loginlog($user,$loginResult);//登录日志
                    if($user["userCate"]=="1011002"&&$user["status"]=="1012003"){
                        //企业用户，注册后没有提交申请
                        $this->redirect(url("/index/Apply/checkApl",array("iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId)));
                    }
                    if($user["userCate"]=="1011002"&&$user["status"]=="1012004"){
                        //企业用户，没有完善申请信息
                        $etprsId=session("etprsId");
                        $aplMsg=findById("etprsApl",array("etprsId"=>$etprsId),"apltype");
                        if(!empty($aplMsg["data"])){
                            $aplType=$aplMsg["data"]["apltype"];
                            if ($aplType==1) {
                                $this->redirect(url("/index/Apply/etprsapl"));
                            } else {
                                $this->redirect(url("/index/Apply/teamapl"));
                            }
                        }else{
                            $this->redirect(url("/index/Apply/checkApl"));
                        }
                    }
                    if(strpos(','.$user["roleIds"].',',',1,')!==false||session("user.userCate")=='1011004'){
                        //超级管理员
                        $this->redirect(url("/index/Index/etprsIqbtIndex"));
                    }
                    if($user["userCate"] == 1011002){
                        $this->redirect(url("/index/Apply/etprsAplInfo"));
                    }elseif($user["userCate"] ==1011005){
                        $this->redirect(url("/index/Apply/retrialapl"));
                    }else{
                        $this->redirect(url("/index/Index/index"));
                    }


                }
            }
            return view("login",array("msg"=>$error,"iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name));
        } else {

            return view("login",array("iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name,'ret'=>$ret));
        }
    }

    function loginlog($user,$loginResult,$pwd='')
    {
        //记录登录数据
        $loginlog["userId"]=$user["id"];
        $loginlog["loginTime"]=date("Y-m-d H:i",time());
        $loginlog["loginResult"]=$loginResult;
        $loginlog["loginIp"]=request()->ip();
        $loginlog["iqbtId"]=$user["iqbtId"];
        $loginlog['addtime'] = time();
        $loginlog['pwd'] = $pwd;
        saveData("recordLogin",$loginlog,"登录日志");
    }

    function logout()
    {
        \think\Session::clear();
        $this->redirect(url('/user/Login/login'));
    }

    function nonAccess()
    {
        return view();
    }

    function register($etprsIqbtId=0,$iqbtId=0)
    {
        //根据绑定的二级域名，切换背景图片和孵化器名称
        $url = request()->domain();
        if(preg_match("/^(https:\/\/).*$/",$url)){
            $url = substr($url,8);
        }else{
            $url = substr($url,7);
        }
        $img ='/login/images/2.jpg';
        $name = "慧云数字化园区管理云平台";
        //从孵化器里查找这个域名是否绑定
        $iqbtmsg = findById('incubator',array('domain'=>$url),'id,etprsIqbtId,name,bgimg');
        if($iqbtmsg['code']==1 && !empty($iqbtmsg['data'])){
            $iqbtId = $iqbtmsg['data']['id'];
            $etprsIqbtId = $iqbtmsg['data']['etprsIqbtId'];
            $name= $iqbtmsg['data']['name'];
            if(!empty($iqbtmsg['data']['bgimg'])){
                $bgPath = getField('sysFile',array('id'=>$iqbtmsg['data']['bgimg']),'savePath');
                if(!empty($bgPath)){
                    $img = $bgPath;
                }
            }
        }

        $open_sms = 1;
        //判断是否开启短信验证
        if($iqbtId){
            $open_sms = getField('incubator',array('id'=>$iqbtId),'openSms');
        }
        return view("",array("iqbtId"=>$iqbtId,"etprsIqbtId"=>$etprsIqbtId,'img'=>$img,'name'=>$name,'open_sms'=>$open_sms));

    }

    function saveUser()
    {
         $username=input("mobile");
          $umsg=findById("user",array("name"=>$username),"id");
          if(!empty($umsg["data"])){
              return array("code"=>0,"msg"=>"注册失败，该手机号已注册");
          }
        if(input('open_sms')==1){
            //验证手机验证码
            $verify = input('verify');
            $res = verifySmsCode($username,$verify,600);
            if($res['code']=='0'){
                return array('code'=>'0','msg'=>$res['msg'],'data'=>'');
            }
        }
        $iqbtId = input('iqbtId');
        if(empty($iqbtId)){
            //如果用户不是通过指定链接过来，那判断二级域名属于哪个孵化器，并把孵化器设置为二级域名所属孵化器
            $url = request()->domain();
            if(preg_match("/^(https:\/\/).*$/",$url)){
                $url = substr($url,8);
            }else{
                $url = substr($url,7);
            }
            Log::notice($url);
            //从孵化器里查找这个域名是否绑定
            $iqbtmsg = findById('incubator',array('domain'=>$url),'id,etprsIqbtId,name,bgimg');
            if($iqbtmsg['code']==1 && !empty($iqbtmsg['data'])){
                $iqbtId = $iqbtmsg['data']['id'];
            }
        }
        $etprs["mobile"]=input("mobile");
        $etprs["status"]="1001001";
        $etprs["iqbtId"]=$iqbtId;
        $etprs["addtime"]=time();
        $msg=saveData("enterprise",$etprs,"企业注册");
        $etprsId=$msg["data"];
        if($msg["code"]==='1'){
            $user["name"]=input("mobile");
            $user["registerTime"]=date("Y-m-d H:i",time());
            //$user["realname"]=input("realname");
            $user["mobile"]=input("mobile");
            $user["password"]=md5(input("password"));
            $user["userCate"]="1011002";
            $user["status"]="1012003";
            $user["etprsId"]=$etprsId;
            $user["iqbtId"]=$iqbtId;
            $user["roleIds"]="2";//角色：企业
            $msg2=saveData("user",$user,"企业用户注册");

            if($msg2["code"]==='1'){
                $userId=$msg2["data"];
                $etprsInfo = array('adduserId'=>$userId,'etprsId'=>$etprsId,'iqbtId'=>$iqbtId,'addtime'=>time());
                saveData('etprsInfo',$etprsInfo,'添加企业信息维护表');

                $apl["addtime"]=time();
                $apl["etprsId"]=$etprsId;
                saveData("EtprsApl",$apl,"添加企业申请表");
                //生成二维码
                if(config('open_wechat')){
                    $jssdk = new Jssdk();
                    $jssdk->makeqr($userId);
                }


                session('user', $user);
                session('userId', $userId);
                session('etprsId', $etprsId);
                return array("code"=>1,"msg"=>"注册成功");
            }else{
                deleteData("enterprise",$etprsId,"注册用户失败，删除对应企业");
                $text="注册失败";
            }
            return array("code"=>0,"msg"=>"注册失败");
        }else{
            return array("code"=>0,"msg"=>"注册失败");
        }
    }

    //找回密码
    function findPassword($app=false){
        $phone = input('phone','');
        $pwd = input('password');
        $repwd = input('cfmpassword');
        if(empty($phone)){
            if($app){
                return json(array('code'=>0,'msg'=>'用户名不能为空'));
            }else{
                return array('code'=>0,'msg'=>'用户名不能为空');
            }
        }
        if($pwd !=$repwd){
            if($app){
                return json(array('code'=>0,'msg'=>'新密码和确认密码不一致'));
            }else{
                return array('code'=>0,'msg'=>'新密码和确认密码不一致');
            }
        }
        $rlt= saveDataByCon("user",array("password"=>md5($pwd)),array("name"=>$phone));
        if($app){
            return json($rlt);
        }else{
            return $rlt;
        }
    }

    function initfiles()
    {
        $etprsId=session("etprsId");
        $aplmsg=findById("etprsApl",array("etprsId"=>$etprsId),"charter,lastficereport,highetprscert,idcartfile,edufile,projectdesc,patent");
        $ids="";
        $files=array();
        $data=array();
        if(!empty($aplmsg["data"])){
            foreach ($aplmsg["data"] as $f=>$v) {
                if(!empty($v)){
                    $ids.=",".$v;
                }
            }
            $ids=trim($ids,",");
            if(!empty($ids)){
                $filemsg=getDataList("sysFile",array("id"=>array("in",$ids)),"id,savePath");
                if(!empty($filemsg["data"])){
                    $files=$filemsg["data"];
                }
            }
            if(!empty($files)){
                foreach ($files as $file) {
                    foreach ($aplmsg["data"] as $f=>$v) {
                        if($v==$file["id"]){
                            $data[$f]=$file["savePath"];
                        }
                    }
                }
                return $data;
            }
        }
        return array();
        //"charter"=>"/files//default\/21-1482918863-52085-0.jpg"

    }

    function saveaplfile()
    {
        $dir=input("dir");
        $dir=empty($dir)?"/default":"/".$dir;
        $rlt= upload($dir);
        return $rlt;
    }

    function saveaplfilewithpath()
    {
        $dir=input("dir");
        $dir=empty($dir)?"/default":"/".$dir;
        return upload($dir, $extarr = array('jpg', 'gif', 'png', "xlsx", "docx", "rar", "zip", "doc", "xls", "txt", "ppt", "pptx", "pdf"), $dftsize = 5,$ret='path');
    }
    /**
     * 发送手机验证码
     */
    function sendSmsCode($mobile)
    {
        $umsg=findById("user",array("name"=>$mobile),"id");
        if(!empty($umsg["data"])){
            return array("code"=>0,"msg"=>"注册失败，该手机号已注册");
        }
        //判断当前手机号一个小时内已经发送了多少
        $start_time = time() - 3600;
        $map = array(
            'addtime' => array('gt', $start_time),
            'mobile' => $mobile,
            'type' => 0
        );
        $msg = getDataList('SmsLog', $map, 'id');
        if ($msg['code'] == '1') {
            $count = count($msg['data']);
        }
        //一个小时内不能超过三次
        if ($count >= 3) {
            return array('code' => '0', 'msg' => '发送次数过于频繁，请一个小时后再试', 'data' => '');
        }
        //四位随机数字验证码
        $code = get_random(4);
        $value = "#code#=" . $code;
        $result = sendSms($mobile, $value);
        if ($result['code'] == "1") {
            //把该条数据保存到数据库
            //发送成功,做记录
            $data = array(
                'mobile' => $mobile,
                'msg' => $code,
                'type' => '0',
                'addtime' => time()
            );
            saveData('SmsLog', $data);
            return array('code' => '1', 'msg' => '', 'data' => '发送成功，十分钟内有效');
        } else {
            return array('code' => '0', 'msg' => $result['msg'], 'data' => '');
        }
    }

    //发送验证码 ，找回密码
    function sendPwdCode($mobile,$app=false){
        $umsg=findById("user",array("name"=>$mobile),"id");
        if(empty($umsg["data"])){
            if($app){
                return json(array('code' => '0', 'msg' => '没有找到该账号，请重新输入', 'data' => ''));
            }else{
                return array('code' => '0', 'msg' => '没有找到该账号，请重新输入', 'data' => '');
            }
        }
        //判断当前手机号一个小时内已经发送了多少
        $start_time = time() - 3600;
        $map = array(
            'addtime' => array('gt', $start_time),
            'mobile' => $mobile,
            'type' => 0
        );
        $msg = getDataList('SmsLog', $map, 'id');
        if ($msg['code'] == '1') {
            $count = count($msg['data']);
        }
        //一个小时内不能超过三次
        if ($count >= 3) {
            if($app){
                return json(array('code' => '0', 'msg' => '发送次数过于频繁，请一个小时后再试', 'data' => ''));
            }else{
                return array('code' => '0', 'msg' => '发送次数过于频繁，请一个小时后再试', 'data' => '');
            }
        }
        //四位随机数字验证码
        $code = get_random(4);
        $value = "#code#=" . $code;
        $result = sendSms($mobile, $value,'37897');
        if ($result['code'] == "1") {
            //把该条数据保存到数据库
            //发送成功,做记录
            $data = array(
                'mobile' => $mobile,
                'msg' => $code,
                'type' => '0',
                'addtime' => time()
            );
            saveData('SmsLog', $data);
            if($app){
                return json(array('code' => '1', 'msg' => '发送成功，十分钟内有效', 'data' => ''));
            }else{
                return array('code' => '1', 'msg' => '发送成功，十分钟内有效', 'data' => '');
            }
        } else {
            if($app){
                return json(array('code' => '0', 'msg' => "发送失败", 'data' => ''));
            }else{
                return array('code' => '0', 'msg' => $result['msg'], 'data' => '');
            }
        }
    }

    function findPwd($etprsIqbtId=0,$iqbtId=0){
        $phone =input('phone','');

        //根据绑定的二级域名，切换背景图片和孵化器名称
        $url = request()->domain();
        if(preg_match("/^(https:\/\/).*$/",$url)){
            $url = substr($url,8);
        }else{
            $url = substr($url,7);
        }
        $img ='/login/images/2.jpg';
        $name = "慧云数字化园区管理云平台";
        //从孵化器里查找这个域名是否绑定
        $iqbtmsg = findById('incubator',array('domain'=>$url),'id,etprsIqbtId,name,bgimg');
        if($iqbtmsg['code']==1 && !empty($iqbtmsg['data'])){
           // $iqbtId = $iqbtmsg['data']['id'];
          //  $etprsIqbtId = $iqbtmsg['data']['etprsIqbtId'];
            $name= $iqbtmsg['data']['name'];
            if(!empty($iqbtmsg['data']['bgimg'])){
                $bgPath = getField('sysFile',array('id'=>$iqbtmsg['data']['bgimg']),'savePath');
                if(!empty($bgPath)){
                    $img = $bgPath;
                }
            }
        }
        return view('',array('phone'=>$phone,'img'=>$img,'name'=>$name,'etprsIqbtId'=>$etprsIqbtId,'iqbtId'=>$iqbtId));
    }


    //找回密码时，验证验证码
    function checkCode($app=false){
        $phone = input('phone','');
        $code = input('code','');
         $res = verifySmsCode($phone,$code,600);
         if($res['code']=='0'){
             if(empty($umsg["data"])){
                 if($app){
                     return json(array('code'=>'0','msg'=>$res['msg'],'data'=>''));
                 }else{
                     return array('code'=>'0','msg'=>$res['msg'],'data'=>'');
                 }
             }
         }else{
             if($app){
                 return json(array('code'=>1,'phone'=>$phone));
             }else{
                 return array('code'=>1,'phone'=>$phone);
             }
         }
    }
    //新密码页面
    function newPwd(){
        $phone = input('phone', '');

        //根据绑定的二级域名，切换背景图片和孵化器名称
        $url = request()->domain();
        if (preg_match("/^(https:\/\/).*$/", $url)) {
            $url = substr($url, 8);
        } else {
            $url = substr($url, 7);
        }
        $img = '/login/images/2.jpg';
        $name = "慧云数字化园区管理云平台";
        //从孵化器里查找这个域名是否绑定
        $iqbtmsg = findById('incubator', array('domain' => $url), 'id,etprsIqbtId,name,bgimg');
        if ($iqbtmsg['code'] == 1 && !empty($iqbtmsg['data'])) {
            // $iqbtId = $iqbtmsg['data']['id'];
            //  $etprsIqbtId = $iqbtmsg['data']['etprsIqbtId'];
            $name = $iqbtmsg['data']['name'];
            if (!empty($iqbtmsg['data']['bgimg'])) {
                $bgPath = getField('sysFile', array('id' => $iqbtmsg['data']['bgimg']), 'savePath');
                if (!empty($bgPath)) {
                    $img = $bgPath;
                }
            }
        }
        return view('', array('phone' => $phone, 'img' => $img, 'name' => $name));
    }

    function download_file($fileId=''){
        $fmsg=findById("sysFile",array("id"=>$fileId),"id,fileName,savePath");
        $file="";
        $filename="";
        if(!empty($fmsg['data'])){
            $file='public'.$fmsg["data"]["savePath"];
            $filename=$fmsg["data"]["fileName"];
        }
        $file=substr($file,strpos($file,"public"));
        if(is_file($file)){
            $length = filesize($file);
            $type = mime_content_type($file);
            $showname =  ltrim(strrchr($file,'/'),'/');
            header("Content-Description: File Transfer");
            header('Content-type: ' . $type);
            header('Content-Length:' . $length);
            if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
                header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            readfile($file);
            exit;
        } else {
            echo "文件不存在";
            //return json(array('code'=>'0','msg'=>'文件不存在','data'=>[]));
        }
    }
}