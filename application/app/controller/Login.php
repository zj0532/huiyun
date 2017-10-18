<?php
namespace app\app\controller;
use app\app\controller\Appcommon;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;
use JPush\Client as JPush;

class Login extends Controller{
    /***
     *
     */
    function golog($code='',$msg='')
    {
        /*try {
            require VENDOR_PATH.'/jpush/autoload.php';
            $client = new JPush("90d15043d57250726d697b14", "840aa67593df56e2f0d09c56");
            $client->push()
                ->setPlatform('all')
                ->addAllAudience()
                ->setNotificationAlert('Hello, JPush')
                ->message('message content', array(
                    'title' => 'hello jpush',
                    // 'content_type' => 'text',
                    'extras' => array(
                        'key' => 'value',
                        'jiguang'
                    ),
                ))
                ->send();
        } catch (\Exception $e) {
            Log::notice(json_encode($e));
        }*/
        switch($code){
            case "o":
                return json(array('code'=>'0','msg'=>'验证失败，是否在其它设备上登录！','login'=>'1','data'=>[]));
                break;
            case "s":
                //用户状态不等于1004001 、1004002
                return json(array('code'=>'0','msg'=>'请先登录','login'=>'1','data'=>[]));
                break;
            case "e":
                //其它异常信息
                return json(array('code'=>'0','msg'=>$msg,'login'=>'1','data'=>[]));
                break;
            default:
                return json(array('code'=>'0','msg'=>'未知错误，请重新登录','login'=>'1','data'=>[]));
                break;
        }
    }
    /***
     *
     */
    function login()
    {

        $data=input("request.");
        try {
            $map = array('a.name'=>$data["name"]);
            $join = [['incubator b','a.iqbtId=b.id AND b.isDelete = 0','left']];
            $msg = findById("user",$map,"a.*,b.id as iqbtabId,b.logo,b.exptime",$join);
            if(!empty($msg['data'])){
                $user=$msg["data"];
               /* //只有超级管理员和系统维护员没有孵化器对应关系。
                $iqbtId=$msg["data"]["iqbtId"];
                if($iqbtId !='0'){
                    if(!in_array($user['roleIds'],['1','3'])){
                        if($user['iqbtId'] !=$iqbtId){
                            return json(array('code'=>'0','msg'=>'用户不存在','data'=>[]));
                        }
                    }
                }*/

                $userState=$user["status"];
                if($userState=='1012002'){
                    return json(array('code'=>'0','msg'=>'用户状态异常','data'=>[]));
                }
                if($userState=='1012005'){
                    return json(array('code'=>'0','msg'=>'当前账户已被冻结，请联系管理员','data'=>[]));
                }
                if(!empty($user['exptime'])){
                    if($user['exptime']<=time()){
                        return json(array('code'=>'0','msg'=>'当期孵化器使用期限已到期，无法正常使用','data'=>[]));
                    }
                }
                //如果企业的状态是退出，则查询是否已经退费，如果已经退费，禁止登陆
                if($user["userCate"]=="1011002" && $userState=='1012001'){
                    if(!empty($user['etprsId'])){
                        $etprsState = getField('enterprise',array('id'=>$user['etprsId']),'status');
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
                                return json(array('code'=>'0','msg'=>'您已经完全退出，没有登陆权限','data'=>[]));
                            }
                        }
                    }

                }
                $loginResult=0; //登录结果： 0：登录成功 1：登录失败
                if($user["password"]!==md5($data["password"])){
                    $loginResult=1;
                    $errlogtime=$user["loginErrTime"];
                    $errlogin["loginErrTime"]=$user["loginErrTime"]+1;
                    $errlogin["id"]=$user["id"];
                     if($errlogtime>=5){
                         $errlogin["status"]="1012002";
                     }
                    saveData("user",$errlogin);
                    //登录日志
                    self::loginlog($user,$loginResult);

                    if($errlogtime>=5){
                        return json(array('code'=>'0','msg'=>"密码错误！您密码错误5次，账号已被锁定，请联系管理员重置密码！",'data'=>[]));
                    }else{
                        return json(array('code'=>'0','msg'=>"密码错误！您已经失败".($errlogtime+1)."次，共5次机会",'data'=>[]));
                    }

                }else{
                    //修改密码错误次数
                    $suclogin["loginErrTime"]=0;
                    $suclogin["id"]=$user["id"];
                    saveData("user",$suclogin);

                    self::loginlog($user,$loginResult);//登录日志
                    if($user["userCate"]=="1011002"&&$user["status"]=="1012003"){
                        //企业用户，注册后没有提交申请
                        return json(array('code'=>'0','msg'=>"请提交申请信息！",'data'=>[]));
                    }
                    if($user["userCate"]=="1011002"&&$user["status"]=="1012004"){
                        //企业用户，没有完善申请信息
                        $etprsId=$user["etprsId"];
                        $aplMsg=findById("etprsApl",array("etprsId"=>$etprsId),"apltype");
                        if(!empty($aplMsg["data"])){
                            $aplType=$aplMsg["data"]["apltype"];
                            if ($aplType==1) {
                                return json(array('code'=>'0','msg'=>"请提交企业申请信息！",'data'=>[]));
                            } else {
                                return json(array('code'=>'0','msg'=>"请提交团队申请信息！",'data'=>[]));
                            }
                        }else{
                            return json(array('code'=>'0','msg'=>"请提交申请信息！",'data'=>[]));
                        }
                    }

                    if(strpos(','.$user["roleIds"].',',',1,')!==false||$user["userCate"]=='1011004'){
                        //超级管理员
                        return json(array('code'=>'0','msg'=>"暂不支持超级管理员登录！",'data'=>[]));
                    }
                    if($user["userCate"] == 1011002){
                        return json(array('code'=>'0','msg'=>"暂不支持企业用户登录！",'data'=>[]));
                    }elseif($user["userCate"] ==1011005){
                        return json(array('code'=>'0','msg'=>"暂不支持导师用户登录！",'data'=>[]));
                    }else if($user["userCate"] ==1011003){
                        //Log::notice(json_encode($user));
                        return json(array('code'=>'0','msg'=>"暂不支持admin登录！",'data'=>[]));
                    }else{
                        //return json(array('code'=>'1','msg'=>'登录成功','data'=>[]));
                        $ret=array(
                            "userId"=>$msg["data"]["id"],
                            "roleIds"=>$msg["data"]["roleIds"],
                            "userCate"=>$msg["data"]["userCate"],
                            "iqbtId"=>$msg["data"]["iqbtId"],
                            "jpush_rgst_id"=>$msg["data"]["jpush_rgst_id"],
                            "token"=>$msg["data"]["token"]
                        );
//

                        //登录成功，生成token
                        $token=md5($msg["data"]["iqbtId"]."_".$msg["data"]["id"]."_".time());
                        $tkmsg=saveDataByCon("user",array("token"=>$token),array("id"=>$msg["data"]["id"]));
                        if($tkmsg["code"]==='1'){
                            $ret["token"]=$token;
                        }

                        return json(array('code'=>'1','msg'=>'','data'=>$ret));
                    }
                }
                return json(array('code'=>'0','msg'=>"其它错误",'data'=>[]));


            }else{
                return self::golog("e","用户不存在");
            }
        } catch (\Exception $e) {
            //throw new \think\Exception($e->getMessage());
            return json(array('code'=>'0','msg'=>$e->getMessage(),'data'=>[]));
        }

    }
    function loginlog($user,$loginResult)
    {
        //记录登录数据
        $loginlog["userId"]=$user["id"];
        $loginlog["loginTime"]=date("Y-m-d H:i",time());
        $loginlog["loginResult"]=$loginResult;
        $loginlog["loginIp"]=request()->ip();
        $loginlog["iqbtId"]=$user["iqbtId"];
        $loginlog['addtime'] = time();
        saveData("recordLogin",$loginlog,"登录日志");
    }

    /***
     *
     */
    function saveaplfile()
    {
        $dir=input("dir");
        $dir=empty($dir)?"/default":"/".$dir;
        $rlt= upload($dir);
        //Log::notice(json_encode($files));
        //Log::notice($name);
//        if($rlt["code"]==='1'){
//            //Log::notice(json_encode($rlt));
//        }
        return json($rlt);
    }

    /***
     *
     */
    function savefile()
    {
        $dir=input("dir");
        $dir=empty($dir)?"/default":"/".$dir;
        $rlt= upload($dir,array('jpg','JPG', 'gif','GIF', 'png','PNG','jpeg','GPEG'),15,'',"idandpath");

        return json($rlt);
    }

    /***
     * 检查版本更新
     */
    function checkVersion($version='0')
    {
        $msg=findById("appVersion",array("version"=>[">",$version]),"*",[],0,"a.id desc");
        Log::notice(json_encode($msg["data"]));
        if(!empty($msg["data"])){
            $fileId=$msg["data"]["fileId"];
            $filepath=getField("sysFile",array("id"=>$fileId),"savePath");
            if(!empty($filepath)){
                $msg["data"]["savepath"]=$filepath;
            }
            return json($msg["data"]);
        }else{
            return json(array());
        }
    }

    function wxLogin($openId=''){
        if(empty($openId)){
            return json(array('code'=>'0','msg'=>'openId不能为空','data'=>array()));
        }

        try {
            $map = array(
                'a.unionId'=>$openId,
            );
            $join = [['incubator b','a.iqbtId=b.id AND b.isDelete = 0','left']];
            $msg = findById("user",$map,"a.*,b.id as iqbtabId,b.exptime",$join);
            if(!empty($msg['data'])){
                $user=$msg["data"];
                $userState=$user["status"];
                if($userState=='1012002'){
                    return json(array('code'=>'0','msg'=>'用户状态异常','data'=>[]));
                }
                if($userState=='1012005'){
                    return json(array('code'=>'0','msg'=>'当前账户已被冻结，请联系管理员','data'=>[]));
                }
                if(!empty($user['exptime'])){
                    if($user['exptime']<=time()){
                        return json(array('code'=>'0','msg'=>'当期孵化器使用期限已到期，无法正常使用','data'=>[]));
                    }
                }
                //如果企业的状态是退出，则查询是否已经退费，如果已经退费，禁止登陆
                if($user["userCate"]=="1011002" && $userState=='1012001'){
                    if(!empty($user['etprsId'])){
                        $etprsState = getField('enterprise',array('id'=>$user['etprsId']),'status');
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
                                return json(array('code'=>'0','msg'=>'您已经完全退出，没有登陆权限','data'=>[]));
                            }
                        }
                    }

                }
                $loginResult=0; //登录结果： 0：登录成功 1：登录失败
                //修改密码错误次数
                $suclogin["loginErrTime"]=0;
                $suclogin["id"]=$user["id"];
                saveData("user",$suclogin);

                self::loginlog($user,$loginResult);//登录日志
                if($user["userCate"]=="1011002"&&$user["status"]=="1012003"){
                    //企业用户，注册后没有提交申请
                    return json(array('code'=>'0','msg'=>"请提交申请信息！",'data'=>[]));
                }
                if($user["userCate"]=="1011002"&&$user["status"]=="1012004"){
                    //企业用户，没有完善申请信息
                    $etprsId=$user["etprsId"];
                    $aplMsg=findById("etprsApl",array("etprsId"=>$etprsId),"apltype");
                    if(!empty($aplMsg["data"])){
                        $aplType=$aplMsg["data"]["apltype"];
                        if ($aplType==1) {
                            return json(array('code'=>'0','msg'=>"请提交企业申请信息！",'data'=>[]));
                        } else {
                            return json(array('code'=>'0','msg'=>"请提交团队申请信息！",'data'=>[]));
                        }
                    }else{
                        return json(array('code'=>'0','msg'=>"请提交申请信息！",'data'=>[]));
                    }
                }
                if(strpos(','.$user["roleIds"].',',',1,')!==false||$user["userCate"]=='1011004'){
                    //超级管理员
                    return json(array('code'=>'0','msg'=>"暂不支持超级管理员登录！",'data'=>[]));
                }
                if($user["userCate"] == 1011002){
                    return json(array('code'=>'0','msg'=>"暂不支持企业用户登录！",'data'=>[]));
                }elseif($user["userCate"] ==1011005){
                    return json(array('code'=>'0','msg'=>"暂不支持导师用户登录！",'data'=>[]));
                }else if($user["userCate"] ==1011003){
                    //Log::notice(json_encode($user));
                    return json(array('code'=>'0','msg'=>"暂不支持admin登录！",'data'=>[]));
                }else{
                    //return json(array('code'=>'1','msg'=>'登录成功','data'=>[]));
                    $ret=array(
                        "userId"=>$msg["data"]["id"],
                        "roleIds"=>$msg["data"]["roleIds"],
                        "userCate"=>$msg["data"]["userCate"],
                        "iqbtId"=>$msg["data"]["iqbtId"],
                        "jpush_rgst_id"=>$msg["data"]["jpush_rgst_id"],
                        "token"=>$msg["data"]["token"]
                    );

                    //登录成功，生成token
                    $token=md5($msg["data"]["iqbtId"]."_".$msg["data"]["id"]."_".time());
                    $tkmsg=saveDataByCon("user",array("token"=>$token),array("id"=>$msg["data"]["id"]));
                    if($tkmsg["code"]==='1'){
                        $ret["token"]=$token;
                    }
                    return json(array('code'=>'1','msg'=>'','data'=>$ret));
                }


            }else{
                return self::golog("e","用户不存在");
            }
        } catch (\Exception $e) {
            //throw new \think\Exception($e->getMessage());
            return json(array('code'=>'0','msg'=>$e->getMessage(),'data'=>[]));
        }
    }

}