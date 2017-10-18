<?php
namespace app\wechat\controller;
use think\Controller;
use org\weixin\MyWechat;
use org\weixin\Jssdk;
use org\weixin\WechatPush;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/28 0028
 * Time: 下午 4:42
 */
class Weixin  extends Controller
{
    //公众号接口
    public function index()
    {
        if(config('weixin.token')){
            $token = config('weixin.token');
        }else{
            $token = 'zlhuiyunfuhuaqi';
        }
        $wechat = new MyWechat($token, true);
        $wechat->run();
    }
//创建菜单
    public function makeMenu(){
        $arr = array(
            'button' => array(
                array(
                    'name'=>"软件介绍",
                    'type'=>'view',
                    'url'=>'http://mp.weixin.qq.com/s/LGjbsJDeYId12iDWDw5yhg',
                    'sub_button' => array()

                ),
                array(
                    'name' => "联系我们",
                    'type'=>'view',
                    'url'=>'http://mp.weixin.qq.com/s/YcOpFEEfQoD_1k1NL3ntpw',
                    'sub_button' => array()
                ),
                array(
                    'name' => "申请试用",
                    'type'=>'view',
                    'url'=>'https://www.zlhuiyun.com/shenqingshiyong/',
                    'sub_button' => array()
                )

            ));

        $jssdk = new Jssdk();
        $res = $jssdk->createMenu($arr);
        print_r($res);
    }

    //菜单查询接口
    public function getMenu(){
        $jssdk = new Jssdk();
        $res = $jssdk->getMenu();
        print_r($res);
    }

    public function makeImg(){
      //  $uids = getFieldArrry('user',array('userCate'=>'1011001','status'=>'1012001'),'id');
        $userinfomsg = getDataList('user',array('userCate'=>'1011001'),'id,qr_img');
        if($userinfomsg['code']==1 &(!empty($userinfomsg['data']))){
            $userinfo = $userinfomsg['data'];
        }
      //  print_r($userinfo);exit();
        $jssdk = new Jssdk();
        $str ='未获取图片id:';
        foreach($userinfo as $value){
            if(empty($value['qr_img'])){
                $img = $jssdk->makeqr($value['id']);
                if(!$img){
                    $str .=$value.',';
                }
            }
           
        }
        echo $str;
        
    }

    //给管理员发送新的申请的通知
    public function sendNotice(){
        $userIds = $this->getAdminIds('7');
        $etprsName = "青岛因特科技有限公司";
        $push = new WechatPush();
        $push->newApply($userIds,$etprsName);
        echo "发送成功";

    }

    //给企业发送状态变更的通知
    public function sendChange(){
        $uid = 32;
        $link = '续约审核';
        $status ='审核通过';
        $push = new wechatPush();
        $push->changeStatus($uid,$link,$status);
        echo "发送成功1";
    }

    //获取有当前权限的所有管理员ID
    public function getAdminIds($role_id='7'){
        $rolemap = array(
            'iqbtId'=>session('iqbtId'),
            'isDelete'=>0,
        );
        $roleMsg = getDataList('UserRole',$rolemap,'id,rolename,menuIds');
        $roleIds = array();
        if($roleMsg['code']==1 &&(!empty($roleMsg['data']))){
            foreach($roleMsg['data'] as $value1){
                if(!empty($value1['menuIds'])){
                    $arr = explode(",",$value1['menuIds']);
                    if(in_array($role_id,$arr)){
                        $roleIds[] = $value1['id'];
                    }
                }
            }
        }
        if(empty($roleIds)){
            return array();
        }
        $map = array(
            'iqbtId'=>session('iqbtId'),
            'userCate'=>'1011001',
            'is_gz'=>'1',
            'status'=>'1012001',
        );
        $userIds = array();
        $userMsg = getDataList('user',$map,'id,name,roleIds');
        if($userMsg['code'] ==1 &&(!empty($userMsg['data']))){
            foreach($userMsg['data'] as $key=>$value){
                if(in_array($value['roleIds'],$roleIds)){
                    $userIds = $value['id'];
                }
            }
        }
        return $userIds;
    }
}