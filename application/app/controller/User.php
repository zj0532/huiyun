<?php
namespace app\app\controller;
//use app\index\controller\Common;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;
use JPush\Client as JPush;

class User extends Appcommon{
    /***
     *
     */
    function getUserInfo($userId=0)
    {
        $map = array('a.id'=>$userId);
        $join = [['sys_file b','a.userheader=b.id','left'],['incubator c','a.iqbtId=c.id','left']];
        $msg = findById("user",$map,"a.id,a.realname,a.mobile,a.email,a.sex,a.roleIds,a.userCate,a.iqbtId,a.jpush_rgst_id,a.token,b.savePath as pic,c.name as iqbtname",$join);
        if($msg["code"]==='1'){
            $msg["data"]["pic"]=str_replace("//","/",$msg["data"]["pic"]);
            $msg["data"]["userId"]=$msg["data"]["id"];
            return json($msg);
        }else{
            return json(array());
        }
    }

    /***
     *
     */
    function saveUserInfo($field="",$val="",$userId=0)
    {
        if($field=="mobile"){
            $con=array("id"=>["<>",$userId],"mobile"=>$val);
            $msg=findById("user",$con,"id");
            if(!empty($msg["data"])){
                return json(array('code'=>'0','msg'=>'已经存在该手机号','data'=>[]));
            }
        }
        $rlt=saveDataByCon("user",array($field=>$val),array("id"=>$userId));

        if($field=="userheader"){
            $rlt["data"]["pic"]="";
            if($rlt["code"]==='1'){
                $result=findById("SysFile",array("id"=>$val),"savePath");
                if(!empty($result['data'])){
                    $rlt["data"]["pic"]=str_replace("\\","/",$result['data']["savePath"]);
                }
            }
        }
        return json($rlt);
    }

    /***
     *
     */
    function getiqbtinfo($iqbtId=0)
    {
        $msg=findById("incubator",array("id"=>$iqbtId),"name,address,mobile,facility,level");
        // Log::notice(json_encode($msg));
        if(!empty($msg['data'])){
            $data=$msg["data"];
            $facilitycode=$data["facility"];
            $level=$data["level"];
            if(!empty($facilitycode)){
                $fclt=getFieldArrry("SysDict",array("code"=>['in',$facilitycode]),"name");

                if(!empty($fclt)){
                    $data["facilityText"]=$fclt;
                }else{
                    $data["facilityText"]=array();
                }
            }else{
                $data["facilityText"]=array();
            }
            if(!empty($level)){
                $levl=getField("SysDict",array("code"=>$level),"name");

                if(!empty($levl)){
                    $data["levelText"]=$levl;
                }else{
                    $data["levelText"]="";
                }
            }else{
                $data["levelText"]="";
            }
            return json($data);
        }else{
            return json(array());
        }
    }


    //我的——我的客服，常见问题列表
    function getFaqList(){
        $map = array(
            'a.iqbtId'=>session('iqbtId'),
        );
        $msg = getDataList('faq',$map,'id,title,content','sort desc');
        $list = array();
        if($msg['code']==1){
            $list = $msg['data'];
            foreach($list as $key=>$value){
                $logmsg = findById('faqLog',array('faqId'=>$value['id'],'adduserId'=>session('userId')),'id,status');
                if(!empty($logmsg['data'])){
                    $list[$key]['helpId'] = $logmsg['data']['id'];
                    $list[$key]['status'] = $logmsg['data']['status'];
                }else{
                    $list[$key]['helpId'] = 0;
                    $list[$key]['status'] = 0;
                }
            }
        }
        return json(array('code'=>'1','msg'=>'','data'=>$list));
    }

    /**
     * 点击“有帮助”惭怍
     * @param string $faqId  常见的问题ID
     * @param string $helpId 有帮助的记录ID，
     * @return \think\response\Json
     */
    function doHelp($faqId='',$helpId=''){
        if(empty($faqId)){
            return json(array('code'=>'0','msg'=>'问题ID不能为空','data'=>array()));
        }
        if(empty($helpId)){
            //则保存一条记录
            $data = array(
                'iqbtId'=>session('iqbtId'),
                'adduserId'=>session('userId'),
                'faqId'=>$faqId,
                'addtime'=>date("Y-m-d",time()),
            );
            $res = saveData('faqLog',$data,'点赞操作');
            return json($res);
        }else{
            //修改状态：
            $old = getfield('faqLog',array('id'=>$helpId),'status','0');
            $new = 1-$old;
            $res = saveDataByCon('faqLog',array('status'=>$new),array('id'=>$helpId));
            return json($res);

        }
    }

    //故障提交
    function saveTrouble(){
        $postdata = input("request.");

        $data = array(
            'iqbtId'=>session('iqbtId'),
            'adduserId'=>session('userId'),
            'addtime'=>date("Y-m-d",time()),
        );
        if(isset($postdata['desc'])){
            $data['desc'] = $postdata['desc'];
        }
        if(isset($postdata['fileId'])){
            $data['fileId'] = $postdata['fileId'];
        }
        $res = saveData('trouble',$data,'提交故障问题');
        return json($res);
    }

}