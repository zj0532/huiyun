<?php
namespace app\tutor\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;
use app\user\library\Pinyin;

class Tutor extends Common{
    //获取所有缴费项目
    function getTutorlist($key="")
    {
        $con=array("a.iqbtId"=>session("iqbtId"));

        if(!empty($key)){
            $con["a.name|a.field"]=array("like","%".$key."%");
        }
        $join = [['user b','a.userId=b.id',"left"]];
        $msg=getDataList("tutor",$con,"a.*,b.name as logname","a.id",$join);
        $list=array();
        if(!empty($msg["data"])){
            $list=$msg["data"];
            $tmplist=self::getDictStr("*","Tutor");
            $list=$this->setListIdText($list,$tmplist);
        }

        for ($i = 0; $i < count($list); $i++) {
            $tutorId=$list[$i]["id"];
            $etprscon=array("iqbtId"=>session("iqbtId"),'status'=>'1001016',"concat(',',tutorIds,',')"=>array('like','%'.$tutorId.'%'));
            $emsg=getDataList("enterprise",$etprscon,"id,name,lealPerson,contact,mobile");
            if(!empty($emsg["data"])){
                $list[$i]["etprs"]=$emsg["data"];
            }else{
                $list[$i]["etprs"]=array();
            }

            $gradetprscon=array("iqbtId"=>session("iqbtId"),'status'=>'1001017',"concat(',',tutorIds,',')"=>array('like','%'.$tutorId.'%'));
            $gemsg=getDataList("enterprise",$gradetprscon,"id,name,lealPerson,contact,mobile");
            if(!empty($gemsg["data"])){
                $list[$i]["gradetprs"]=$gemsg["data"];
            }else{
                $list[$i]["gradetprs"]=array();
            }

           /* $quitetprscon=array("iqbtId"=>session("iqbtId"),'status'=>array('in',['1001020','1001021']),"concat(',',tutorIds,',')"=>array('like','%'.$tutorId.'%'));
            $qemsg=getDataList("enterprise",$quitetprscon,"id,name,lealPerson,contact,mobile");
            if(!empty($qemsg["data"])){
                $list[$i]["quitetprs"]=$qemsg["data"];
            }else{
                $list[$i]["quitetprs"]=array();
            } */
        }
        return $list;
    }

    //导出导师信息
    function exportTutor(){
        $con=array("a.iqbtId"=>session("iqbtId"));

        $join = [['user b','a.userId=b.id',"left"]];
        $msg=getDataList("tutor",$con,"a.*,b.name as logname","a.id",$join);
        $list=array();
        if(!empty($msg["data"])){
            $list=$msg["data"];
            $tmplist=self::getDictStr("*","Tutor");
            $list=$this->setListIdText($list,$tmplist);
        }
        for ($i = 0; $i < count($list); $i++) {
            $tutorId=$list[$i]["id"];
            $list[$i]['company'] ='';
            $etprscon=array("iqbtId"=>session("iqbtId"),'status'=>'1001016',"concat(',',tutorIds,',')"=>array('like','%'.$tutorId.'%'));
            $emsg=getDataList("enterprise",$etprscon,"id,name,lealPerson,contact,mobile");
            if(!empty($emsg["data"])){
                $etprsinfo=$emsg["data"];
                foreach($etprsinfo as $value){
                    $list[$i]['company'] .= $value['name'].',';
                }

            }else{
                $list[$i]['company'] = '暂无辅导企业';
            }
        }
        $data = array();
        foreach($list as $key=>$value){
            $data[$key]['name'] = $value['name'];
            $data[$key]['logname'] = $value['logname'];
            $data[$key]['sex'] = $value['sexText'];
            $data[$key]['mobile'] = $value['mobile'];
            $data[$key]['email'] = $value['email'];
            $data[$key]['field'] = $value['field'];
            $data[$key]['etprs'] = $value['company'];
        }
        $filename = "导师信息表";
        $header = array('姓名','登录名','性别','电话','邮箱','擅长领域','负责企业');
        vendor("PHPExcel");
        vendor("PHPExcel.Writer.Excel5");
        vendor("PHPExcel.IOFactory");
        getExcel($filename,$header,$data);

    }


    function addTutor($id=0)
    {
        $data=array();
        if(!empty($id)){
            $msg=findById("tutor",array("id"=>$id),"*");
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        return view("",array("data"=>$data));
    }
    function saveTutor(){
        $postData=input("request.");
        $py=new Pinyin();
        $user["mobile"]=$postData["mobile"];
        $user["email"]=$postData["email"];
        $user["sex"]=$postData["sex"];
        $user["iqbtId"]=session("iqbtId");
        $user["registerTime"]=date("Y-m-d H:i",time());
        $user["status"]="1012001";
        $user["userCate"]="1011005";
        $user["realname"]=$postData["name"];
        $user["roleIds"]=5;
        if(empty($postData["id"])){
            $user["name"]=$py->Pinyin($postData["name"],'UTF8').time();
            $user["password"]=md5($user["name"]);
            $umsg=saveData("user",$user,"添加导师用户");
        }else{
            $umsg=saveDataByCon("user",$user,array("id"=>$postData["userId"]),"修改导师用户");
            $id=$postData["userId"];
        }
        if($umsg["code"]==='1'){
            if(!isset($id) || empty($id)){
                $id=$umsg["data"];
            }
            if(empty($postData["id"])) {
                saveData("user", array('name' => $py->Pinyin($postData["name"], 'UTF8'). $id, "id" => $id, "password" => md5('888888')));
            }

            $postData["iqbtId"]=session("iqbtId");
            $postData["addtime"]=time();
            $postData["userId"]=$id;
            $postData["adduserId"]=session("userId");
            $msg=saveData("tutor",$postData,"添加/修改导师信息");
        }
        //工作日志：
        if(empty($postData["id"])) {
            $logData = array(
                'etprsId' => 0,
                'fmenuId' => 27,
                'smenuId' => 26,
                'objId' => $id,
                'content' => '新增了一位导师：' . $postData['name'],
            );
            saveOaLog($logData);
        }
        return $umsg;
    }

    function deltTutor($id)
    {
        $msg=findById("tutor",array("id"=>$id),"id,userId");
        $dmsg=array();
        if(!empty($msg["data"])){
            $userId=$msg["data"]["userId"];
            $dmsg=deleteData("user",$userId,"删除导师-用户信息");
        }
        if($dmsg["code"]==='1'){
            return deleteData("tutor",$id,"删除导师信息");
        }else{
            return $dmsg;
        }

    }

    function tutor()
    {
        $userId=session("userId");
        $msg=findById("tutor",array("userId"=>$userId),"*");
        if(!empty($msg["data"])){
            $umsg=findById("user",array("id"=>$userId),"name");
            if(!empty($umsg["data"])){
                $msg["data"]["logname"]=$umsg["data"]["name"];
            }
            return view("",array("data"=>$msg["data"]));
        }
        return view("",array("data"=>array()));
    }

    function saveTutorInfo()
    {
        $postData=input("request.");
        $py=new Pinyin();
        $user["mobile"]=$postData["mobile"];
        $user["email"]=$postData["email"];
        $user["sex"]=$postData["sex"];
        $user["realname"]=$postData["name"];
        $user["name"]=$postData["logname"];

        $umsg=saveDataByCon("user",$user,array("id"=>$postData["userId"]),"修改导师用户");
        if($umsg["code"]==='1'){
            unset($postData["logname"]);
            $msg=saveData("tutor",$postData,"添加/修改导师信息");
        }
        return $umsg;
    }

    function tutorplan($etprsId=0)
    {
        if(empty($etprsId)){
            $etprsId=session("etprsId");
        }

        $msg=getDataList("tutorPlan",array("etprsId"=>$etprsId),"*");
        $data=array();
        if(!empty($msg["data"])){
             $data=$msg["data"];
        }
        if(!empty($data)){
            for ($i = 0; $i < count($data); $i++) {
                $id=$data[$i]["id"];
                $emsg=getDataList("tutorPlanEvent",array("planId"=>$id),"*");
                if(!empty($emsg["data"])){

                    foreach($emsg['data'] as $key=>$value){
                        $tutorName = getFieldArrry('tutor',array('id'=>array('in',explode(",",$value['tutors']))),'name');
                        if(!empty($tutorName)){
                            $emsg['data'][$key]['tutors'] = implode(",",$tutorName);
                        }else{
                            $emsg['data'][$key]['tutors'] = '';
                        }

                    }
                    $data[$i]["evts"]=$emsg["data"];
                }
            }
        }
        $usercate=session("user.userCate");
        return view("",array("lines"=>$data,'etprsId'=>$etprsId,'userCate'=>$usercate));
    }

    function addEvt($etprsId=0)
    {
        $id = input('id',0);
        $planId = input('planId',0);
        $data=array();
        if(!empty($id)){
            $msg=findById("tutorPlanEvent",array("id"=>$id),"*");
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        //从企业表里获取分配的导师
        $tutorIds = getField('enterprise',array('id'=>$etprsId),'tutorIds');
        if(!empty($tutorIds)){
            $tutorIds = explode(',',$tutorIds);
        }else{
            $tutorIds = array('0');
        }
        return view("",array("data"=>$data,"planId"=>$planId,"etprsId"=>$etprsId,'tutorIds'=>$tutorIds));
    }
    function saveEvt()
    {
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
//        $postData["etprsId"]=session("etprsId");
        $postData["addtime"]=time();
        $msg=saveData("tutorPlanEvent",$postData,"添加/修改孵化事件");
        return $msg;
    }

    function addLine($etprsId=0)
    {
        $id = input('id',0);
        $data=array();
        if(!empty($id)){
            $msg=findById("tutorPlan",array("id"=>$id),"*");
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        return view("",array("data"=>$data,"etprsId"=>$etprsId));
    }

    function saveLine()
    {
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
//        $postData["etprsId"]=session("etprsId");
        $postData["addtime"]=time();
        $msg=saveData("tutorPlan",$postData,"添加/修改孵化阶段");
        return $msg;
    }

    function etprslist()
    {
        return view();
    }

    function getTutorEtprs($key="")
    {
        $tutormsg=findById("tutor",array("userId"=>session("userId")),"id");
        $tutorId=0;
        if(!empty($tutormsg["data"])){
            $tutorId=$tutormsg["data"]["id"];
        }
        $con=array("concat(',',tutorIds,',')"=>array("like",'%,'.$tutorId.",%"),"iqbtId"=>session("iqbtId"));
        if(!empty($key)){
            $con["name"]=array('like','%'.$key.'%');
        }
        $msg=getDataList("enterprise",$con,"name,id,contact,mobile,entertime");
        if(!empty($msg["data"])){
            return $msg["data"];
        }else{
            return array();
        }
    }

}