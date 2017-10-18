<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/10 0010
 * Time: ���� 1:58
 */

namespace app\wechat\controller;
use org\auth\WeAuth ;
use think\Controller;
use think;

class Auth extends Controller
{
    protected $userId;
    public function index()
    {
        $userId = session("userId");
        if (!$userId) {
            $auth = new WeAuth();
            $wechatInfo = $auth->doAuth();
           // print_r($wechatInfo);exit();
            if($wechatInfo && isset($wechatInfo['unionid']) && !empty($wechatInfo['unionid']))
            {
                $unionId = $wechatInfo['unionid'];
                $join = [['incubator b','a.iqbtId=b.id AND b.isDelete = 0','left']];
                $msg = findById("user",array('a.unionId'=>$unionId),"a.*,b.id as iqbtabId,b.logo,b.exptime",$join);
                if(empty($msg["data"])||$msg['code']==0){
                    $this->redirect(url('/user/Login/login',['ret' => '该微信号未与系统账号绑定，请先用用户名登录系统，扫描右上角的二维码，绑定微信']));
                    //echo "该微信号未与系统账号绑定，请先用用户名登录系统，扫描右上角的二维码，绑定微信。";return;
                }else {
                    $user = $msg["data"];
                    $userState = $user["status"];
                    if ($userState == '1012002'){
                        //echo "用户状态异常."; return;
                        $this->redirect(url('/user/Login/login',['ret' => '微信登录失败，用户状态异常']));
                    }
                    if ($userState == '1012005') {
                        //echo "当前账户已被冻结，请联系管理员"; return;
                        $this->redirect(url('/user/Login/login',['ret' => '当前账户已被冻结，请联系管理员']));
                    }
                    if (!empty($user['exptime'])) {
                        if ($user['exptime'] <= time()) {
                            //echo "当前孵化器使用期限已到期，无法正常使用";return;
                            $this->redirect(url('/user/Login/login',['ret' => '当前孵化器使用期限已到期，无法正常使用']));
                        }
                    }
                    //如果企业的状态是退出，则查询是否已经退费，如果已经退费，禁止登陆
                    if ($user["userCate"] == "1011002" && $userState == '1012001') {
                        if (!empty($user['etprsId'])) {
                            $etprsState = getField('enterprise', array('id' => $user['etprsId']), 'status');
                            if ($etprsState == '1001017') {
                                //再查询是否有退费
                                $feemap = array(
                                    'etprsId' => $user['etprsId'],
                                    'status' => array('in', '0,1'),
                                    'iqbtId' => $user['iqbtId']
                                );
                                $feeMsg = getDataList('feeQuitRcd', $feemap, 'id');
                                if ($feeMsg['code'] == 1 && empty($feeMsg['data'])) {
                                    //如果企业状态是退出状态并且没有待退费记录，则禁止登陆
                                    //echo "您已经完全退出，没有登录权限";return;
                                    $this->redirect(url('/user/Login/login',['ret' => '您已经完全退出，没有登录权限']));
                                }
                            }
                        }
                    }
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
                    session('userId', $user["id"]);
                    if($user["userCate"]=="1011002"){
                        session('etprsId', $user["etprsId"]);
                    }
                    session('iqbtId', $user["iqbtId"]);
                    session("etprsIqbtId",$user["etprsIqbtId"]);
                    $this->redirect(url('/index/Index/index'));
                  //  return array('code'=>1,'msg'=>'登录成功');
                }
            }else{
                //echo "该微信号未与系统账户绑定。";return;
                $this->redirect(url('/user/Login/login',['ret' => '该微信号未与系统账户绑定']));
            }
        } else {
            $this->redirect(url('/index/Index/index'));
        }

    }
}