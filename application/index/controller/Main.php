<?php
namespace app\index\controller;
use think\Controller;

use think\Db;
use think\Exception;
use app\user\library\Pinyin;

class Main extends Controller{
    function index()
    {
        return view();
    }

    function initIqbts($status='1',$key='',$districtId=0)
    {
        $con=array();
        if(!empty($status)){
             $con["status"]=$status;
        }
        if(!empty($key)){
            $con["name"]=array('like',"%".$key."%");
        }
        if(!empty($districtId)){
            $con["districtId"]=$districtId;
        }
        $msg=getDataList("incubator",$con,"id,name,status,etprsIqbtId");
        if(!empty($msg["data"])){
             return $msg["data"];
        }else{
            return array();
        }
    }

    function getIqbt($iqbtId=0,$id=0)
    {
        $data=array();
        $con=array();
        if(!empty($id)){
            $con["id"]=$id;
        }
        $iqbtdata=array();
        if(!empty($iqbtId)){


            $con["iqbtId"]=$iqbtId;
        }
        if(!empty($con)){
            $msg=findById("IqbtApl",$con);
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        if(!empty($iqbtId)&&empty($data)){
            $msg=findById("incubator",array("id"=>$iqbtId));
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
            $data["iqbtId"]=$iqbtId;
            $data["id"]=0;
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
        return view("",array('data'=>$data));
    }

    function saveIqbtapl()
    {
        $postData=input("request.");
        /*$iqbt["level"]=$postData["level"];
        $iqbt["type"]=$postData["type"];
        $iqbt["facility"]=join(",",$postData["facility"]);
        $iqbt["services"]=join(",",$postData["services"]);
        $iqbt["name"]=$postData["name"];
        $iqbt["mobile"]=$postData["mobile"];
        $iqbt["leader"]=$postData["contact"];
        $iqbt["email"]=$postData["email"];
        $iqbt["address"]=$postData["address"];*/
        $postData["addtime"]=time();
        $iqbtId=$postData["iqbtId"];
        if(empty($iqbtId)){
             return array("code"=>0,'msg'=>'孵化器错误');
        }
        $chkmsg1=findById("incubator",array("id"=>$iqbtId),"*");
        if(!empty($chkmsg1["data"])){
            $iqbtdata=$chkmsg1["data"];
        }
        if(!empty($iqbtdata)){
            if ($iqbtdata["status"]!='0') {
                return array("code"=>0,'msg'=>'当前孵化器已被认领或认领审核中');
            }
        }else{
            return array("code"=>0,'msg'=>'找不到对应孵化器');
        }
        $chkmsg=findById("IqbtApl",array("iqbtId"=>$iqbtId),"id");
        if($chkmsg["code"]==='1'){
            $postData["id"]=$chkmsg["data"]["id"];
        }
        if(!empty($postData["services"])&&is_array($postData["services"])){
            $postData["services"]=join(",",$postData["services"]);
        }
        if(!empty($postData["facility"])&&is_array($postData["facility"])){
            $postData["facility"]=join(",",$postData["facility"]);
        }
        $msg=saveData("iqbtApl",$postData);
        if($msg["code"]==='1'){
            saveDataByCon("incubator",array("status"=>2),array("id"=>$iqbtId));
        }
        return $msg;
    }


    function initShowRegion($id)
    {
        $con=array("parentId"=>0);
        if(!empty($id)){
            $con=array("parentId"=>$id);
        }
        $msg=getDataList("region",$con,"id,name,level");
        if(!empty($msg["data"])){
            return $msg["data"];
        }else{
            return array();
        }
    }

    function initRegionIqbts($id=0,$level=3,$key="")
    {
        if(empty($id)){
            return array();
        }
        $con=array();
        $con2=array('1'=>2);
        if($level==3){
            $con=array("districtId"=>$id);
        }else if($level==2){
            $con2=array("cityId"=>$id);
        }else if($level==1){
            $con2=array("provinceId"=>$id);
        }
        $rs=getFieldArrry("region",$con2,"id");
        if(empty($con)){
             $con=array("districtId"=>array("in",$rs));
        }
        if(!empty($key)){
            $con["name"]=array("like","%".$key."%");
        }
        $msg=getDataList("incubator",$con,"*");
        if(!empty($msg["data"])){
            $con=array("code"=>array("like","1032%"));
            if(!empty(session("iqbtId"))){
                $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
            }
            $smsg=getDataList("SysDict",$con,"code,name");
            if(!empty($smsg["data"])){
                $svss=$smsg["data"];
                for ($i = 0; $i < count($msg["data"]); $i++) {
                    $msg["data"][$i]["serviceText"]="";
                    foreach($svss as $svs){
                        if(in_array($svs["code"],explode(",",$msg["data"][$i]["services"]))){
                            $msg["data"][$i]["serviceText"].=",".$svs["name"];
                        }
                    }
                    $msg["data"][$i]["serviceText"]=trim($msg["data"][$i]["serviceText"],",");
                }
            }
            $dictcon=array("code"=>array("like","1033%"));
            if(!empty(session("iqbtId"))){
                $dictcon["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
            }
            $fmsg=getDataList("SysDict",$dictcon,"code,name");
            if(!empty($msg["data"])){
                $fvss=$fmsg["data"];
                for ($i = 0; $i < count($msg["data"]); $i++) {
                    $msg["data"][$i]["facilityText"]="";
                    foreach($fvss as $svs){
                        if(in_array($svs["code"],explode(",",$msg["data"][$i]["facility"]))){
                            $msg["data"][$i]["facilityText"].=",".$svs["name"];
                        }
                    }
                    $msg["data"][$i]["facilityText"]=trim($msg["data"][$i]["facilityText"],",");
                }
            }
            $rmsg=getDataList("region",array(),"id,name");
            if(!empty($msg["data"])){
                $rvss=$rmsg["data"];
                for ($i = 0; $i < count($msg["data"]); $i++) {
                    $msg["data"][$i]["districtText"]="";
                    foreach($rvss as $r){
                        if(in_array($r["id"],explode(",",$msg["data"][$i]["districtId"]))){
                            $msg["data"][$i]["districtText"].=",".$r["name"];
                            break;
                        }
                    }
                    $msg["data"][$i]["districtText"]=trim($msg["data"][$i]["districtText"],",");
                }
            }
            return $msg["data"];
        }else{
            return array();
        }
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

}