<?php
namespace app\service\controller;
use think\Controller;
use app\index\controller\Common;
use think\Db;
use think\Exception;

class Resos extends Common{

    function myResos()
    {
        //$type=input("type");
        return view("");
    }

    function resos()
    {
        $type=input("type");
        return view("",array("type"=>$type));
    }

    //管理资源/需求。
    function getMyResos()  {
      //  $type=input("type");
        $con=array('iqbtId'=>session("iqbtId"),'etprsId'=>session("user.etprsId"));
        $table="ResosResource";
        $sequence="addtime desc";//排序
        $msg=getDataList($table,$con,"*",$sequence);
        $tmplist=self::getDictStr("*",$table);
        $msg['data']=$this->setListIdText($msg['data'],$tmplist);
        $msg["data"]=self::initResos($msg["data"]);
        $msg["data"]=self::getResosCoperate($msg["data"]);
        return $msg["data"];
    }

    function getResos(){
        $type=input("type");
        //$con=array('iqbtId'=>session("user.iqbtId"),'type'=>$type,'deadline'=>array('gt',time()),'status'=>array('in','1,2,4'));//已申请，通过，完成/结束
        $con="iqbtId =".session("user.iqbtId")." and type =".$type." and (deadline >".time()." or deadline=0) and status in(1,2,4)";
        $table="ResosResource";
        $sequence="status asc";//排序
        $msg=getDataList($table,$con,"*",$sequence);
        $tmplist=self::getDictStr("*",$table);
        $msg['data']=$this->setListIdText($msg['data'],$tmplist);

        $msg["data"]=self::initResos($msg["data"]);
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'etprsId','fieldname'=>'etprsText'),"enterprise","id,name",array('iqbtId'=>session("user.iqbtId")))));
        $msg["data"]=self::getResosCoperate($msg["data"]);
        return $msg["data"];
    }

    function getResosCoperate($resoslist)
    {
        for ($i = 0; $i < count($resoslist); $i++) {
            $resosId=$resoslist[$i]["id"];
            $msg=getDataList("ResosCoperate",array("resosId"=>$resosId));
            if($msg["code"]==="1"){
                $coperatelist=$msg["data"];
                //状态
                $tmplist=self::getDictStr("*","ResosCoperate");
                $coperatelist=$this->setListIdText($coperatelist,$tmplist);
                //企业名称
                $coperatelist=self::setListIdText($coperatelist,array(array(array('fieldkey'=>'resosEtprsId','fieldname'=>'resosEtprsText'),"enterprise","id,name",array())));
                $coperatelist=self::setListIdText($coperatelist,array(array(array('fieldkey'=>'requireEtprsId','fieldname'=>'requireEtprsText'),"enterprise","id,name",array())));
                $resoslist[$i]["coperates"]=json_encode($coperatelist);
            }
        }
        return $resoslist;
    }
    function addResos()
    {
        $id=input("id");
      //  $type=input("type");
        $c=array();
        if(!empty($id)){
            $msg=findByid("ResosResource",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
                $c['deadline'] = date("Y-m-d",$c['deadline']);

            }
        }
        return view("",array("data"=>$c));
    }
    public function initResos($list)
    {
        $status=array('0'=>"待申请发布",'1'=>'已申请发布','2'=>"已发布",'3'=>'退回','4'=>"结束");
        for ($i = 0; $i < count($list); $i++) {
            if(empty($list[$i]["deadline"])){
                $list[$i]["deadlineTime"]="永久";
            }else{
                $list[$i]["deadlineTime"]=date("Y-m-d",$list[$i]["deadline"]);
            }
            $list[$i]["statusText"]=$status[$list[$i]["status"]];
        }
        return $list;
    }
    function saveResos()
    {
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["etprsId"]=session("user.etprsId");
        $postData["addUid"]=session("userId");
        $postData["deadline"]=strtotime($postData["deadline"]);
        $postData["addtime"]=time();
        $table="ResosResource";
        $msg= saveData($table,$postData,"添加/修改");
        return $msg;
    }
    function deleteResos(){
        $id=input("id");
        return deleteData("ResosResource",$id,"删除用户");
    }

    function resosStatus()
    {
        $id=input("id");
        $status=input("status");
        $data=array("id"=>$id,'status'=>$status);
        $remark=input("remark");
        if(!empty($remark)){
            $data["remark"]=$remark;
        }
        if(!empty($id)){
            $res = saveData("ResosResource",$data);
            if($res['code']==1){
                if($status=="2"||$status=="3"){
                    $resosmsg=findById("ResosResource",array("id"=>$id),"etprsId,name,addUid");
                    if($resosmsg["code"]==='1'&&!empty($resosmsg["data"])){
                        $resosname=$resosmsg['data']["name"];
                        $addUserId=$resosmsg['data']["addUid"];
                        $title="资源/需求:".$resosname."审核".($status=="2"?"通过":"被退回。");
                        $content="资源/需求 ".$resosname."审核".($status=="2"?"通过":"被退回，请确认。");
                        $emailData = array(
                            'type'=>'1020008',
                            'title'=>$title,
                            'content'=>$content
                        );
                        $this->sendAllMsg($addUserId,$emailData);
                        $etprsId = $resosmsg['data']['etprsId'];
                        //工作日志：
                        $logData = array(
                            'etprsId'=>$etprsId,
                            'fmenuId'=>27,
                            'smenuId'=>81,
                            'objId'=>$id,
                            'content'=>$title,
                        );
                        saveOaLog($logData);
                    }
                }
            }
            return $res;
        }else{
            return returnResult("db_info", "db_noid_info");
        }
    }


    function resosplat($type)
    {
        return view("",array("type"=>$type));
    }

    /*function getPlatResos($type="0",$page="1",$search="",$pageSize='10')
    {
        $table="ResosResource";
        $sequence="addtime desc";//排序
        //$con=array('iqbtId'=>session("user.iqbtId"),'type'=>$type,'deadline'=>array('gt',time()),'status'=>2);//审核通过
        $con="iqbtId =".session("user.iqbtId")." and type =".$type." and (deadline >".time()." or deadline=0) and status=2";
        if(!empty($search)){
            //$con["name|desc"]=array("like","%".$search."%");
            $con.=" and (`name` like '%".$search."%' or `desc` like '%".$search."%')";
        }
        //echo $con;
        $msg=getPageDataList($table,$con,"*",$page,$pageSize,$sequence);
        //**********

        $tmplist=self::getDictStr("*",$table);
        $msg['data']=$this->setListIdText($msg['data'],$tmplist);
        $msg["data"]=self::initResos($msg["data"]);
        $msg["data"]=self::setListIdText($msg["data"],array(array(array('fieldkey'=>'etprsId','fieldname'=>'etprsText'),"enterprise","id,name",array('iqbtId'=>session("user.iqbtId")))));
        $msg["pageSize"]=$pageSize;
        return $msg;
    }*/
    function getPlatResos($search="")
    {
        $table = "ResosResource";
        $sequence = "addtime desc";//排序
        //$con=array('iqbtId'=>session("user.iqbtId"),'type'=>$type,'deadline'=>array('gt',time()),'status'=>2);//审核通过
        $con = "iqbtId =" . session("user.iqbtId") . " and (deadline >" . time() . " or deadline=0) and status=2";
        if (!empty($search)) {
            //$con["name|desc"]=array("like","%".$search."%");
            $con .= " and (`name` like '%" . $search . "%' or `desc` like '%" . $search . "%')";
        }

        //echo $con;
        $msg = getDataList($table, $con,"*",$sequence);
        //**********

        $tmplist = self::getDictStr("*", $table);
        $msg['data'] = $this->setListIdText($msg['data'], $tmplist);
        $msg["data"] = self::initResos($msg["data"]);
        $msg["data"] = self::setListIdText($msg["data"], array(array(array('fieldkey' => 'etprsId', 'fieldname' => 'etprsText'), "enterprise", "id,name", array('iqbtId' => session("user.iqbtId")))));
      //  header("Content-Type: text/html;charset=utf-8");
      //  print_r($msg['data']);exit();
        return $msg;
    }

    function getContact($resosId)
    {
        if(empty($resosId)){
            return returnResult("db_info", "db_noid_info");
        }
        $table="ResosResource";
        $msg=findById($table,array("id"=>$resosId));
        if($msg["code"]=="1"&&!empty($msg["data"])){
            $resos=$msg["data"];
            $etprsId=$resos["etprsId"];
            /*$etprsMsg=findById("enterprise",array("id"=>$etprsId));
            if($etprsMsg["code"]==="1"){
                $etprs=$etprsMsg["data"];

            }else{
                return array('code' => '0', 'msg' =>"企业错误", 'data' => array());
            }*/
            //资源是否属于当前企业
            $nowEptrsId=session("user.etprsId");
            if(empty($nowEptrsId)){
                return array('code' => '0', 'msg' =>"当前用户错误", 'data' => array());
            }
            //暂时注释，方便测试
            if($nowEptrsId==$etprsId){
                return array('code' => '1', 'msg' =>"", 'data' => $resos);
            }
            //是否查看过
            $chkCon["resosId"]=$resosId;
            if($resos['type']=="0"){
                $chkCon["resosEtprsId"]=$etprsId;
                $chkCon["requireEtprsId"]=$nowEptrsId;
            }else{
                $chkCon["requireEtprsId"]=$etprsId;
                $chkCon["resosEtprsId"]=$nowEptrsId;
            }
            $chkCpt=findById("resosCoperate",$chkCon,"id");
            if($chkCpt["code"]=="1"&&!empty($chkCpt["data"])){
                //查看过，合作中
                return array('code' => '1', 'msg' =>"", 'data' => $resos);
            }else{
                //提示是否合作
                return array('code' => '2', 'msg' =>"", 'data' => $resos);
            }

        }else{
            return array('code' => '0', 'msg' =>"找不到资源", 'data' => array());
        }

    }
    function getExterContact($resosId)
    {
        if(empty($resosId)){
            return returnResult("db_info", "db_noid_info");
        }
        $table="ResosExternal";
        $msg=findById($table,array("id"=>$resosId));
        if($msg["code"]=="1"&&!empty($msg["data"])){
            $resos=$msg["data"];
            return array('code' => '1', 'msg' =>"", 'data' => $resos);

        }else{
            return array('code' => '0', 'msg' =>"找不到资源", 'data' => array());
        }

    }

    function coperate($resosId,$etprsId)
    {
        $nowEptrsId=session("user.etprsId");
        $resosmsg=findById("ResosResource",array("id"=>$resosId),"name,type");
        if($resosmsg['data']['type']=="0"){
            $data["resosEtprsId"]=$etprsId;
            $data["requireEtprsId"]=$nowEptrsId;
        }else{
            $data["requireEtprsId"]=$etprsId;
            $data["resosEtprsId"]=$nowEptrsId;
        }
        $data["resosId"]=$resosId;
        $data["status"]="1007001";
        $data["iqbtId"]=session("user.iqbtId");
        //产生提醒


        if($resosmsg["code"]==='1'&&!empty($resosmsg["data"])){
            $resosname=$resosmsg['data']["name"];
            $usermsg=findById("user",array('etprsId'=>$etprsId),"id");
            if($usermsg["code"]==='1'&&!empty($usermsg["data"])){
                $etprsUid=$usermsg["data"]["id"];
                $emailData["type"]="1020008";
                $emailData["title"]="资源/需求 ".$resosname."产生合作记录";
                $emailData["content"]="资源/需求 ".$resosname."产生合作记录。";
                $emailData["fromUserId"]=session("userId");
                $this->sendAllMsg($etprsUid,$emailData);
            }
        }

        return saveData("resosCoperate",$data);
    }

    function coperateScore($id,$resosId)
    {
        if(empty($id)){
            return view();
        }
        $join = [['resos_resource b','a.resosId=b.id']];
        $msg=findById("ResosCoperate",array("a.id"=>$id),"a.*,b.name as resosname",$join);
        if($msg["code"]==="1"){
            $coperate=$msg["data"];
            $coperate['resosEtprs']=getField("enterprise",array("id"=>$coperate['resosEtprsId']),"name");
            $coperate['requireEtprs']=getField("enterprise",array("id"=>$coperate['requireEtprsId']),"name");
            $dictcon=array("code"=>$coperate['status']);
            if(!empty(session("iqbtId"))){
                $dictcon["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
            }
            $coperate['statusText']=getField("sysDict",$dictcon,"name");//状态

            //资源企业评分情况
            $resosScoreMsg=findById("resosCoperateScore",array('type'=>0,'etprsId'=>$coperate['resosEtprsId'],'resosId'=>$resosId,'cprtId'=>$id),"id,cprtScore,qualityScore,desc");
            if($resosScoreMsg["code"]==="1"&&!empty($resosScoreMsg['data'])){
                $resosScore=$resosScoreMsg['data'];
                $coperate['resosqualityScore']=$resosScore["qualityScore"];
                $coperate['resoscprtScore']=$resosScore["cprtScore"];
                $coperate['resosEtprsDesc']=$resosScore["desc"];
                $coperate['resosCprtid']=$resosScore["id"];
            }
            //需求企业评分情况
            $requireScoreMsg=findById("resosCoperateScore",array('type'=>1,'etprsId'=>$coperate['requireEtprsId'],'resosId'=>$resosId,'cprtId'=>$id),"id,cprtScore,qualityScore,desc");
            if($requireScoreMsg["code"]==="1"&&!empty($requireScoreMsg['data'])){
                $requireScore=$requireScoreMsg['data'];
                $coperate['requirequalityScore']=$requireScore["qualityScore"];
                $coperate['requirecprtScore']=$requireScore["cprtScore"];
                $coperate['requireEtprsDesc']=$requireScore["desc"];
                $coperate['requireCprtid']=$requireScore["id"];
            }

            return view("",array("data"=>$coperate));
        }else{
            return view();
        }
    }
    //保存合作评分
    function saveCoperateScore()
    {
        $postData=input("request.");
        try {
            Db::startTrans();
            $cprtReason=$postData["cprtReason"];
            $cprtId=$postData["id"];
            //保存合作/不合作 原因
            $cprtMsg=saveData("ResosCoperate",array("id"=>$cprtId,"cprtReason"=>$cprtReason));
            if($cprtMsg["code"]!=="1"){
                throw new \think\Exception($cprtMsg['msg']);
            }
            //保存资源方评分
            $resos["id"]=$postData["resosCprtid"];
            $resos["resosId"]=$postData["resosId"];
            $resos["etprsId"]=$postData["resosEtprsId"];
            $resos["cprtId"]=$cprtId;
            $resos["cprtScore"]=$postData["resoscprtScore"];
            $resos["qualityScore"]=$postData["resosqualityScore"];
            $resos["desc"]=$postData["resosEtprsDesc"];
            $resos["type"]=0;
            $resos["addtime"]=time();
            $resos["addUserId"]=session("userId");
            $resos["iqbtId"]=session("user.iqbtId");
            $resosMsg=saveData("resosCoperateScore",$resos);
            if($resosMsg["code"]!=="1"){
                throw new \think\Exception($resosMsg['msg']);
            }
            $require["id"]=$postData["requireCprtid"];
            $require["resosId"]=$postData["resosId"];
            $require["etprsId"]=$postData["requireEtprsId"];
            $require["cprtId"]=$cprtId;
            $require["cprtScore"]=$postData["requirecprtScore"];
            $require["qualityScore"]=$postData["requirequalityScore"];
            $require["desc"]=$postData["requireEtprsDesc"];
            $require["type"]=1;
            $require["addtime"]=time();
            $require["addUserId"]=session("userId");
            $require["iqbtId"]=session("user.iqbtId");
            $requireMsg=saveData("resosCoperateScore",$require);
            if($requireMsg["code"]!=="1"){
                throw new \think\Exception($requireMsg['msg']);
            }
            return returnResult("global_info", "global_operate_suc", array());
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return array("code"=>0,"msg"=>$e->getMessage(),array());
        }
    }

    function coprtStatus($status,$id)
    {
        return saveData("ResosCoperate",array("id"=>$id,'status'=>$status));
    }

    function resosCoperate()
    {
        $etprsId=session("user.etprsId");
        return view("",array("etprsId"=>$etprsId));
    }

    //所有合作记录
    function getAllCoperate()
    {
        $etprsId=session("user.etprsId");
        $join = [['resos_resource b','a.resosId=b.id']];
        $con["a.resosEtprsId|a.requireEtprsId"]=$etprsId;
        $msg=getDataList("ResosCoperate",$con,"a.*,b.name as resosname,b.desc as resosdesc,b.contact,b.detail,b.category","a.addtime",$join);
        if($msg["code"]==="1"&&!empty($msg["data"])){
            $coperate=$msg["data"];

            $resostmplist=self::getDictStr("*","ResosResource");
            $coperate=$this->setListIdText($coperate,$resostmplist);
            $tmplist=self::getDictStr("*","ResosCoperate");
            $coperate=$this->setListIdText($coperate,$tmplist);


            $coperate=self::setListIdText($coperate,array(array(array('fieldkey'=>'resosEtprsId','fieldname'=>'resosEtprs'),"enterprise","id,name",array())));
            $coperate=self::setListIdText($coperate,array(array(array('fieldkey'=>'requireEtprsId','fieldname'=>'requireEtprs'),"enterprise","id,name",array())));
            return $coperate;
        }else{
            return array();
        }
    }

    function detail($id)
    {
        if(!empty($id)){
            $msg=findById("ResosResource",array("id"=>$id));
            if($msg["code"]==="1"){

                $msg["data"]["category"]=getField("sysDict",array("code"=>$msg["data"]["category"]),"name");
                return view("",array("data"=>$msg["data"]));
            }
        }
    }



}