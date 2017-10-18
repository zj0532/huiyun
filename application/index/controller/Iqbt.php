<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Exception;
class Iqbt extends Common{
    function iqbtAplList()
    {
        return view();
    }

    function getIqbtApls($status='0')
    {
        $con=array('b.status'=>$status);
        $data=array();
        $join = [['incubator b','a.iqbtId=b.id'],['region c','a.districtId=c.id']];
        $msg=getDataList("IqbtApl",$con,"a.*,b.status,c.name as districtName",'a.addtime',$join);
        if(!empty($msg["data"])){
            $data=$msg["data"];
        }
        $tmplist=self::getDictStr("*","IqbtApl");
        $data=$this->setListIdText($data,$tmplist);
        return $data;
    }

    function setAplStatus($id=0,$status=1)
    {
        $data=array("status"=>$status);
        if(!empty($id)){
            if($status==1){
                $aplmsg=findById("IqbtApl",array('iqbtId'=>$id),"contact as leader,mobile,email,services,facility,type,level");
                if(!empty($aplmsg["data"])){
                    $data=array_merge($data,$aplmsg["data"]);
                }
                //设置当前孵化器权限继承孵化器企业
            }
            $msg= saveDataByCon("incubator",$data,array("id"=>$id));
            return $msg;
        }else{
            return array("code"=>0,'msg'=>'没有记录需要修改');
        }
    }

    //维护认领的孵化器信息
    function updateIqbtInfo(){
        $iqbtId = session('iqbtId');
        $msg=findById("incubator",array("id"=>$iqbtId));
        if(!empty($msg["data"])){
            $data=$msg["data"];
        }
        $data["iqbtId"]=$iqbtId;
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
        return view("",array('data'=>$data));
    }

    function saveIqbtInfo()
    {
        $postData=input("request.");
        $iqbtId=$postData["id"];
        if(empty($iqbtId)){
            return array("code"=>0,'msg'=>'孵化器错误');
        }
        if(!empty($postData["services"])&&is_array($postData["services"])){
            $postData["services"]=join(",",$postData["services"]);
        }
        if(!empty($postData["facility"])&&is_array($postData["facility"])){
            $postData["facility"]=join(",",$postData["facility"]);
        }
        $msg=saveData("incubator",$postData);
        return $msg;
    }

}
