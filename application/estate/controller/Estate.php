<?php
namespace app\estate\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;

class Estate extends Common{
    //资料管理
    function getDatas() {
        $msg=getDataList("EstateDatabank",array('iqbtId'=>session("user.iqbtId")),"*","addtime desc");
        if($msg["code"]==='1'){
            $datas=$msg["data"];
            $tmplist=self::getDictStr("*","EstateDatabank");
            //print_r($tmplist);
            $datas=$this->setListIdText($datas,$tmplist);
            for ($i = 0; $i < count($datas); $i++) {
                $datas[$i]["addtime"]=empty($datas[$i]["addtime"])?"":date("Y-m-d H:i",$datas[$i]["addtime"]);
                $dataId=$datas[$i]["id"];
                $rcdMsg=getDataList("EstateDatabankRcd",array("dataId"=>$dataId),"*","a.addtime desc");
                if(empty($rcdMsg["data"])){
                    $datas[$i]["rcd"]=array();
                }else{
                    $datas[$i]["rcd"]=$rcdMsg["data"];
                }
            }
            return $datas;
        }else{
            return array();
        }
    }


    function getThirdType($code){
        $con=array("code"=>array("like",$code."%"),"level"=>3);
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        return getDataList("SysDict",$con,"code,name");
    }

    function addDatas()
    {
        $id=input("id");
        $c=array();
        $secondType="";
        if(!empty($id)){
            $msg=findByid("EstateDatabank",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        if(!empty($c)){
            $type=$c["type"];
            $secondType=substr($type,0,7);
        }
        return view("",array("data"=>$c,'scdType'=>$secondType));
    }
    function saveDatas()
    {
        $postData=input("request.");

        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $table="EstateDatabank";
        $msg= saveData($table,$postData,"保存资料");
        return $msg;
    }
    function deleteDatas(){
        $id=input("id");
        return deleteData("EstateDatabank",$id,"删除活动");
    }

    function addDataRcd($id)
    {
        return view("",array("dataId"=>$id));
    }

    function editDataRcd($id)
    {
        $c=array();
        $dataId="";
        if(!empty($id)){
            $msg=findByid("EstateDatabankRcd",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        if(!empty($c)){
            $dataId=$c["dataId"];
        }
        return view("addDataRcd",array("data"=>$c,"dataId"=>$dataId));
    }
    function saveDatasRcd()
    {
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $table="EstateDatabankRcd";
        $msg= saveData($table,$postData,"维护资料信息");
        if($msg["code"]==='1'){
            saveData("EstateDatabank",array("id"=>$postData["dataId"],'status'=>$postData["status"]));
        }
        return $msg;
    }
    function deleteDatasRcd(){
        $id=input("id");
        return deleteData("EstateDatabankRcd",$id,"删除活动");
    }
    //缴费管理
    function getFee()
    {
        $con=array('a.iqbtId'=>session('user.iqbtId'));
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=getDataList("EstateFee",$con,"a.*,b.name","a.addtime desc",$join);
        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $msg["data"][$i]["addtime"]=date("Y-m-d H:i",$msg["data"][$i]["addtime"]);
            }
            $tmplist=self::getDictStr("*","EstateFee");
            $msg['data']=$this->setListIdText($msg['data'],$tmplist);
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addFee()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("EstateFee",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveFee(){
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="EstateFee";
        return saveData($table,$postData,"添加/修改缴费信息");
    }
    function deleteFee(){
        $id=input("id");
        return deleteData("EstateFee",$id,"删除缴费信息");
    }

    function getBuilding()
    {
        $con=array('iqbtId'=>session('iqbtId'));
        $msg=getDataList("EstateBuilding",$con,"*","name desc");
        if($msg["code"]==="1"){
            return $msg["data"];
        }else{
            return array();
        }
    }
    function addBuilding()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("EstateBuilding",array("id"=>$id,'iqbtId'=>session('iqbtId')),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveBuilding(){
        $postData=input("request.");
        //编辑的时候，判断一下楼层，新修改的楼层数不可小于已经有使用楼层的数
        if(!empty($postData['id'])){
            $map = array(
                'buildId'=>$postData['id'],
                'status'=>array('gt',0),
            );
            $floors = getFieldArrry('estateRoom',$map,'floor');
            if(!empty($floors)){
                $max = max($floors);
                if($postData['floor'] <$max){
                    return array('code'=>0,'msg'=>'楼层数不能小于当前已分配房间的最高楼层:'.$max.'层');
                }
            }
        }


        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="EstateBuilding";
        return saveData($table,$postData,"添加/修改写字楼信息");
    }
    function deleteBuilding(){
        $id=input("id");
        //删除之前，判断该楼是否已经有使用的房间了，如果有，不可删除
        $map = array(
            'buildId'=>$id,
            'status'=>array('gt',0)
        );
        $rooms = getDataList('estateRoom',$map,'id');
        if($rooms['code']==1 && !empty($rooms['data'])){
            return array('code'=>0,'msg'=>'该楼宇已经有使用的房间，不可删除。');
        }
        return deleteData("EstateBuilding",$id,"删除写字楼信息");
    }

    function getFloor($id)
    {
        if(empty($id)){
             return 0;
        }else{
            $floor=getField("EstateBuilding",array("id"=>$id),"floor");
            return $floor;
        }
    }

    function getFloorRoom($buildId,$floor)
    {
        $data=array();
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=getDataList("EstateRoom",array("a.buildId"=>$buildId,"a.floor"=>$floor,'a.iqbtId'=>session('user.iqbtId')),"a.id,a.roomNo,a.etprsId,a.buildId,a.floor,a.totalarea,a.status,a.type,a.roomIndex,b.name","a.id",$join);
        if($msg["code"]==='1'){
            $rooms=$msg["data"];
            foreach ($rooms as $room) {
                $data[$room["roomIndex"]]=$room;
            }
            return $data;
        }else{
            return array();
        }
    }

    function saveRoom()
    {
        $postData=input("request.");
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="EstateRoom";
        if($postData["type"]!="0"){
            //如果不是房间，状态设为空闲
            $postData["status"]="2";
        }
        if($postData["status"]=="2"){
            $postData["etprsId"]=0;
        }
        return saveData($table,$postData,"添加/修改房间信息");
    }

    function saveEtprsRoom()
    {
        $roomIds=input("roomIds");
        $etprsId=input("etprsId");
        if(empty($etprsId)){
             return array("code"=>0,"msg"=>"请选择企业");
        }
        $arr=explode(",",$roomIds);
        try {
            Db::startTrans();
            foreach ($arr as $roomId) {
                $data["id"]=$roomId;
                $data["status"]=0;
                $data["etprsId"]=$etprsId;
                saveData("EstateRoom",$data);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return array("code"=>0,"msg"=>$e->getMessage());
        }
        return array("code"=>1,"msg"=>"操作成功");
    }

    //缴费管理
    function getEtprsRooms()
    {
        $con=array('a.etprsId'=>session('user.etprsId'));//已分配，正常使用，未及时缴费,'status'=>array('in','0,1,3')
        $join = [['estate_building b','a.buildId=b.id'],['enterprise c','a.etprsId=c.id',"left"]];
        $msg=getDataList("EstateRoom",$con,"a.*,b.name,c.name as etprsname","a.addtime desc",$join);
        if($msg["code"]==="1"){
            /*$tmplist=self::getDictStr("*","EstateFee");
            $msg['data']=$this->setListIdText($msg['data'],$tmplist);*/
            return $msg["data"];
        }else{
            return array();
        }
    }

    function room($buildid)
    {
        return view("",array("id"=>$buildid,'iqbtId'=>session('iqbtId')));
    }

    function addRoom($id=0,$buildid=0,$floor=0)
    {
        $c=array();
        if(!empty($id)){
            $msg=findByid("estateRoom",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
                $floor=$c["floor"];
            }
        }
        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $imsg=getDataList("feeCfg",array("a.feetype"=>"1030001",'a.iqbtId'=>session("iqbtId")),"a.id,a.itemId,b.name as itemName","",$join);
        $items=array();
        if(!empty($imsg["data"])){
            $items=$imsg["data"];
        }
        return view("",array("data"=>$c,"buildId"=>$buildid,"floor"=>$floor,'items'=>$items));
    }

    function saveRooms()
    {
        $postData=input("request.");
        if(empty($postData['roomNo'])){
            return array('code'=>'0','msg'=>'房间编号必填');
        }
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $table="EstateRoom";
        $postData["status"]="0";
        $postData['roomNo'] = trim($postData['roomNo']);
        //首次添加的时候判断，编辑的时候不需要了
        if(!isset($postData['id']) || empty($postData['id'])){
            //判断房间号是否有了，去重
            if(!empty($postData['roomNo'])){
                $info = findById('EstateRoom',array('roomNo'=>$postData['roomNo'],'iqbtId'=>session('iqbtId'),'buildId'=>$postData["buildId"],'floor'=>$postData["floor"]),'*');
                if($info['code']==1 &&!empty($info['data'])){
                    return array('code'=>0,'msg'=>'房间编号重复,请重新命名');
                }
            }
        }else{
            //编辑的时候，如果重新编辑房间名的话，得判断是否有重复
            $map = array(
                'iqbtId'=>session('iqbtId'),
                'roomNo'=>$postData['roomNo'],
                'id'=>array('neq',$postData['id'])
            );
            $info = findById('EstateRoom',$map,'id');
            if($info['code']==1 && !empty($info['data'])){
                return array('code'=>0,'msg'=>'房间编号重复,请重新命名');
            }
        }
        $msg= saveData($table,$postData,"添加/修改房间信息");

        return $msg;
    }
    
    //缴费记录
    function getEtprsFee()
    {
        $con=array('a.etprsId'=>session('user.etprsId'));
        $join = [['estate_room b','a.roomId=b.id',"left"],['enterprise c','a.etprsId=c.id',"left"]];
        $msg=getDataList("EstateFee",$con,"a.*,b.roomNo,c.name as etprsname","a.addtime desc",$join);
        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                $msg["data"][$i]["addtime"]=date("Y-m-d H:i",$msg["data"][$i]["addtime"]);
            }
            $tmplist=self::getDictStr("*","EstateFee");
            $msg['data']=$this->setListIdText($msg['data'],$tmplist);
            return $msg["data"];
        }else{
            return array();
        }
    }

    //停车位
    function getParklot()
    {
        $join = [['estate_parklot_record b','a.id=b.lotId and b.startTime<='.time().' and b.endTime>='.time()." and b.isDelete=0","left"]];
        /*$con["b.startTime"]=array("ELT",time());
        $con["b.endTime"]=array("EGT",time());
        $con["b.isDelete"]=0;*/
        $msg=getDataList("EstateParklot",array('a.iqbtId'=>session("user.iqbtId")),"a.*,b.userName,b.mobile,b.startTime,b.endTime,b.plateNo","a.parkNo asc,b.startTime desc",$join);
        if($msg["code"]==='1'){
            $lots=$msg["data"];
            $tmplist=self::getDictStr("*","EstateParklot");
            $lots=$this->setListIdText($lots,$tmplist);
            for ($i = 0; $i < count($lots); $i++) {
                $lots[$i]["startTime"]=empty($lots[$i]["startTime"])?"":date("Y-m-d",$lots[$i]["startTime"]);
                $lots[$i]["endTime"]=empty($lots[$i]["endTime"])?"":date("Y-m-d",$lots[$i]["endTime"]);
                $lotId=$lots[$i]["id"];
                $join2 = [['enterprise b','a.etprsId=b.id',"left"]];
                $rcdMsg=getDataList("EstateParklotRecord",array('a.iqbtId'=>session("user.iqbtId"),"a.lotId"=>$lotId),"a.*,b.name","a.startTime desc",$join2);
                if(empty($rcdMsg["data"])){
                    $lots[$i]["records"]=array();
                }else{
                    for ($j = 0; $j < count($rcdMsg["data"]); $j++) {
                        $rcdMsg["data"][$j]["startTime"] = empty($rcdMsg["data"][$j]["startTime"]) ? "" : date("Y-m-d", $rcdMsg["data"][$j]["startTime"]);
                        $rcdMsg["data"][$j]["endTime"] = empty($rcdMsg["data"][$j]["endTime"]) ? "" : date("Y-m-d H:i", $rcdMsg["data"][$j]["endTime"]);
                    }
                    $lots[$i]["records"]=$rcdMsg["data"];
                }
            }
            return $lots;
        }else{
            return array();
        }
    }
    function getParklotRcd()
    {
        $join = [['estate_parklot_record b','a.id=b.lotId']];
        $con["b.startTime"]=array("ELT",time());
        $con["b.endTime"]=array("EGT",time());
        $con["b.isDelete"]=0;
        $con["b.etprsId"]=session("user.etprsId");
        $con["a.iqbtId"]=session("user.iqbtId");
        $msg=getDataList("EstateParklot",$con,"a.*,b.userName,b.mobile,b.startTime,b.endTime,b.plateNo","a.parkNo asc,b.startTime desc",$join);
        if($msg["code"]==='1'){
            $lots=$msg["data"];
            $tmplist=self::getDictStr("*","EstateParklot");
            $lots=$this->setListIdText($lots,$tmplist);
            for ($i = 0; $i < count($lots); $i++) {
                $lots[$i]["startTime"]=empty($lots[$i]["startTime"])?"":date("Y-m-d",$lots[$i]["startTime"]);
                $lots[$i]["endTime"]=empty($lots[$i]["endTime"])?"":date("Y-m-d",$lots[$i]["endTime"]);
            }
            return $lots;
        }else{
            return array();
        }
    }
    function addParklot()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("EstateParklot",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveParklot()
    {
        $data=input("request.");
        $data["iqbtId"]=session("user.iqbtId");
        $data["adduserId"]=session("userId");
        $data["addtime"]=time();

        $table="EstateParklot";
        $id=$data["id"];

        try {
            Db::startTrans();
            if(!empty($id)){
                //判断区域内是否存在同名的
                $chklot=findById($table,array("areaNo"=>$data["areaNo"],'id'=>array("NEQ",$id),'parkNo'=>$data["parkNo"]));
                if(!empty($chklot["data"])){
                    throw new \think\Exception("区域内有相同车位编号：".$data["parkNo"]);
                }
                $msg =saveData($table,$data,"添加/修改会议室信息");
                if ($msg["code"] !== '1') {//出现错误
                    throw new \think\Exception("保存失败：".$data["parkNo"]);
                }
            }else{
                $parks=explode("\n",$data["parkNo"]);
                foreach ($parks as $parkNo) {
                    $parkNo=trim($parkNo);
                    if(!empty($parkNo)){
                        $lot=$data;
                        $lot["parkNo"]=$parkNo;
                        /*echo $data["areaNo"]."---".$parkNo;
                        halt($parks);*/
                        $chklot2=findById($table,array("areaNo"=>$data["areaNo"],'parkNo'=>$parkNo));

                        if(!empty($chklot2["data"])){
                            throw new \think\Exception("区域内有相同车位编号：".$parkNo);
                        }
                        $msg = saveData($table, $lot);
                        if ($msg["code"] !== '1') {//出现错误
                            throw new \think\Exception("保存失败：".$parkNo);
                        }
                    }
                }
                Db::commit();
            }
        } catch (\Exception $e) {
            Db::rollback();
            $msg['code'] = '0';
            $msg["msg"] = $e->getMessage();
            return $msg;
        }
        return $msg;
    }

    function deleteParklot(){
        $id=input("id");
        return deleteData("EstateParklot",$id,"删除车位信息");
    }

    function addLotRecord($lotId)
    {
        return view("",array("lotId"=>$lotId));
    }
    function editLotRecord($id)
    {
        $c=array();
        if(!empty($id)){
            $msg=findByid("EstateParklotRecord",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("addLotRecord",array("data"=>$c,"lotId"=>$c["lotId"]));
    }

    function saveLotRecord()
    {
        $table="EstateParklotRecord";
        $postData=input("request.");
        $postData["startTime"]=strtotime($postData["startTime"]);
        $postData["endTime"]=strtotime($postData["endTime"])+86399;//到当天11：59：59
        if($postData["endTime"]<=$postData["startTime"]){
            /*echo $postData["endTime"]."-".$postData["startTime"]."=";
            echo $postData["endTime"]-$postData["startTime"];*/
            return returnResult("global_info","global_endtime_error");
        }
        $mtrCon="";
        if(!empty($postData["id"])){
            $mtrCon="id !=".$postData["id"]." and ";
        }
        $mtrCon=$mtrCon."lotId='".$postData["lotId"]."' and ((startTime>='".$postData["startTime"]."' and startTime<'".$postData["endTime"]."') or (endTime>'".$postData["startTime"]."' and endTime<='".$postData["endTime"]."') or (startTime<='".$postData["startTime"]."' and endTime>='".$postData["endTime"]."'))";

        $chkmsg=findById($table,$mtrCon,"id,userName");
        if(!empty($chkmsg["data"])){
            return array("code"=>0,'msg'=>"与业主 ".$chkmsg["data"]["userName"]." 时间冲突");
        }
        $postData["adduserId"]=session("userId");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $msg= saveData($table,$postData,"保存活动");
        return $msg;
    }
    function deleteLotRecord(){
        $id=input("id");
        return deleteData("EstateParklotRecord",$id,"删除车位使用者信息");
    }

    function roomForm($buildid=0){
        if(!empty($buildid)){
            $msg=findById("EstateBuilding",array("id"=>$buildid),"id,floor,name");
            $floorNum=$msg['data']['floor']+1;
            $name = $msg['data']['name'];
            return view("",array('buildid'=>$buildid,'floorNum'=>$floorNum,'name'=>$name));
        }

    }

    //房间列表形式的查看
    function getRoomForm($id=0){
        $map = array('buildid'=>$id);
        $name = input("name");
        $floor = input("floor");
        $type = input("type");
        $status1 = input("status");
        if(!empty($floor)){
            $map['a.floor'] = $floor;
        }
        if(!empty($name)){
            $map['a.roomNo'] = $name;
        }
        if($type!=""){
            $map['a.type'] = $type;
        }
        if($status1!="" ){
            $map['a.status'] = $status1;
        }

        $join1 = [['enterprise b','a.etprsId=b.id',"left"],['estateRoomEtprs c','c.roomId=a.id and c.status<>2',"left"]];
        $msg=getDataList("EstateRoom",$map,"a.id,a.floor,a.roomNo,a.totalarea,a.type,b.name as etprsName,a.etprsId,a.status,c.startTime,c.endTime","a.roomNo desc",$join1);
        $data=$msg["data"];
        for ($i = 0; $i < count($data); $i++) {
            $status=$data[$i]["status"];
            if($status=="2"){
                $endtime=$data[$i]["endTime"];
                $now=time();
                //还有三十天结束的，状态为待续费
                if(($endtime-$now)/(86400*30)<1){
                    $data[$i]["status"]=3;
                }
            }

        }

        $msg["data"]=$data;
        return $msg;
    }

    //删除空房间
    function delRoom($id=""){
        if(!empty($id)){
            return deleteData("EstateRoom",$id,"删除空房间");
        }
    }
}