<?php
namespace app\fice\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;
use org\weixin\WechatPush;

class Fice extends Common{

    //获取所有缴费项目
    function getItemlist($name="",$cate="")
    {
        $con=array('iqbtId'=>session("iqbtId"));
        if(!empty($name)){
            $con["name"]=array("like","%".$name."%");
        }
        if(!empty($cate)){
            $con["cate"]=$cate;
        }
        $msg=getDataList("FeeItem",$con,"id,name,cate,types,about");
        $list=array();
        if(!empty($msg["data"])){
            $list=$msg["data"];
            $tmplist=self::getDictStr("*","FeeItem");
            $list=$this->setListIdText($list,$tmplist);
        }
        if(!empty($list)){
            for ($i = 0; $i < count($list); $i++) {
                $itemId=$list[$i]["id"];
                $list[$i]["opts"]=array();
                $omsg=getDataList("FeeItemOpt",array("itemId"=>$itemId),"*");
                if(!empty($omsg["data"])){
                    $list[$i]["opts"]=$omsg["data"];
                }
            }
        }
        return $list;
    }

    function dftItemOpt($id=0,$dft=0,$itemId=0)
    {
        if($dft=='1'){
            //设为默认，先把默认的设为非默认
            saveDataByCon("feeItemOpt",array("isdft"=>0),array("itemId"=>$itemId,'isdft'=>1));
        }else{
            //如果取消默认，则把第一个设为默认
            $msg=getDataList("feeItemOpt",array("itemId"=>$itemId),"id");
            if(count($msg["data"])==1){
                return array("code"=>0,'msg'=>"只有一条记录");
            }
            $optId=0;
            if(!empty($msg["data"])){
                $optId=$msg["data"]["id"];
            }
            saveDataByCon("feeItemOpt",array("isdft"=>1),array("id"=>$optId));
        }
        return saveData("feeItemOpt",array("id"=>$id,"isdft"=>$dft));
    }

    function addFeeItem($id=0)
    {
        $data=array();
        if(!empty($id)){
            $msg=findById("FeeItem",array("id"=>$id),"*");
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        return view("",array("data"=>$data));
    }
    function saveFeeItem(){
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $msg=saveData("FeeItem",$postData,"添加缴费项目");
        return $msg;
    }
    function addItemOpt($id=0,$itemId=0)
    {
        $data=array();
        if(!empty($id)){
            $join = [['fee_item b','a.itemId=b.id',"left"]];
            $msg=findById("FeeItemOpt",array("a.id"=>$id),"a.*,b.name as itemName,b.cate",$join);
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        if(!empty($itemId)){
            $msg=findById("FeeItem",array("id"=>$itemId),"name as itemName,id as itemId,cate");
            if(!empty($msg["data"])){
                $data=$msg["data"];
            }
        }
        return view("",array("data"=>$data));
    }
    function saveItemOpt(){
        $postData=input("request.");
        if(!empty($postData['itemId'])){
            $cate = getField('feeItem',array('id'=>$postData['itemId']),'cate');
            if($cate =='1029002'){
                //如果是周期性缴费，则必须选择周期
                if(empty($postData['cycle'])){
                    return array('code'=>0,'msg'=>'请选择缴费周期');
                }
            }
        }
        if($postData['feestyle']=='numration'){
            if(empty($postData['fee'])){
                return array('code'=>0,'msg'=>'请输入指定金额');
            }
        }
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $cmsg=findById("feeItemOpt",array("itemId"=>$postData["itemId"]),"id");
        if(empty($cmsg["data"])){
            $postData["isdft"]=1;
        }
        $msg=saveData("FeeItemOpt",$postData,"添加缴费项目-标准");
        return $msg;
    }

    function deltFeeItem($id)
    {
        //删除前先判断该项目是否有子标准，如果有，不允许删除
        $map = array(
            'itemId'=>$id,
            'iqbtId'=>session('iqbtId'),
        );
        $optMsg = getDataList('FeeItemOpt',$map,'id');
        if($optMsg['code']==1 && !empty($optMsg['data'])){
            return array('code'=>0,'msg'=>'该项目下有缴费标准，不可删除');
        }
        $msg=saveDataByCon("FeeItemOpt",array("isDelete"=>1),array("itemId"=>$id),"删除缴费项目-标准");
        if($msg["code"]==='1'){
            return deleteData("FeeItem",$id,"删除缴费项目");
        }else{
            return $msg;
        }
    }
    function deltFeeItemOpt($id)
    {
        //删除之前，判断在缴费配置里是否有配置，如果配置了，不可删除。
        $map = array(
            'iqbtId'=>session('iqbtId'),
            'optId'=>$id,
            'isDelete'=>0,
        );
        $cfgMsg = findById('feeItemCfg',$map,'id');
        if($cfgMsg['code']==1 && !empty($cfgMsg['data'])){
            return array('code'=>0,'msg'=>"缴费配置里已使用该标准，不可删除");
        }
        return deleteData("FeeItemOpt",$id,"删除缴费项目-标准");
    }

    //企业待缴费记录列表
    function getEtprsRcdlist($etprsId=0)
    {
        if(empty($etprsId)){
            return array();
        }
        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"]];
        $msg=getDataList("FeeRcd",array("a.status"=>0,"a.etprsId"=>$etprsId),"a.*,b.name as itemName,c.name as optName,c.feestyle,c.numration,c.price","a.id",$join);
        $data=array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            for ($i = 0; $i < count($data); $i++) {
                if(!empty($data[$i]["startTime"])){
                    $data[$i]["startTime"]=date("Y-m-d",$data[$i]["startTime"]);
                }else{
                    $data[$i]["startTime"]="";
                }
                if(!empty($data[$i]["endTime"])){
                    $data[$i]["endTime"]=date("Y-m-d",$data[$i]["endTime"]);
                }else{
                    $data[$i]["endTime"]="";
                }

                $data[$i]["numration"]=self::numratFmt($data[$i]["numration"]);
            }
        }
        return $data;
    }

    function setpayfee($ids="",$types)
    {
        if(empty($ids)){
            return array("code"=>0,"msg"=>"至少选择一条记录");
        }
        $data = array(
            'status'=>1,
            'settleuserId'=>session('userId'),
            'settletime'=>time(),
            'settletype'=>$types
        );
        $msg=saveDataByCon("FeeRcd",$data,array("id"=>array("in",$ids)));
        //工作日志#todo

        return $msg;
    }

    function addPayfee($etprsId=0)
    {
        return view("",array("etprsId"=>$etprsId));
    }

    function cmpltPayfee($optId=0,$ids='') {
        $msg=findById("feeItemOpt",array("id"=>$optId),"*");
        $opt=array();
        if(!empty($msg["data"])){
            $opt=$msg["data"];
        }
        $opt["feetext"]=self::feeFmt($opt["feestyle"]);
        $opt["numrationtext"]=self::numratFmt($opt["numration"]);
        $imsg=findById("feeItem",array("id"=>$opt["itemId"]),"name");
        if(!empty($imsg["data"])){
            $opt["item"]=$imsg["data"]["name"];
        }
        return view("",array("opt"=>$opt,"ids"=>$ids));
    }

    function getFeeItems($feestyle)
    {
        if(empty($feestyle)){
            return array();
        }
        $msg=getDataList("FeeItem",array("cate"=>$feestyle,'iqbtId'=>session("iqbtId")),"id,name");
        if($msg["code"]==='1'){
            return $msg["data"];
        }else{
            return array();
        }
    }
    function getFeeItemsOpt($item)
    {
        if(empty($item)){
            return array();
        }
        $msg=getDataList("FeeItemOpt",array("itemId"=>$item),"id,name");
        if($msg["code"]==='1'){
            return $msg["data"];
        }else{
            return array();
        }
    }

    function getItemsOptDetail($optId=0)
    {
        $msg = findById("FeeItemOpt", array("id" => $optId), "*");
        $data = array();
        if (!empty($msg["data"])) {
            $data = $msg["data"];
        }
        if (!empty($data)) {
            $data["feestyleText"]=self::feeFmt($data["feestyle"]);
            $data["numrationText"]=self::numratFmt($data["numration"]);
        }
        return $data;
    }

    function saveFeeRcd()
    {
        $postData=input("request.");
        if(empty($postData['etprsId'])){
            return array('code'=>0,'msg'=>'请选择企业');
        }
        if(empty($postData['itemId'])){
            return array('code'=>0,'msg'=>'请选择收费项目');
        }
        if(empty($postData['optId'])){
            return array('code'=>0,'msg'=>'请选择收费标准');
        }
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $postData["startTime"]=0;
      //  $postData["startTime"]=strtotime($postData["startTime"]);
        $postData["endTime"]=0;
      //  $postData["endTime"]=strtotime($postData["endTime"]);
        $msg=saveData("FeeRcd",$postData,"添加缴费");
        return $msg;
    }

    function deltFeeRcd($id=0)
    {
        return deleteData("feeRcd",$id,"删除手动添加缴费记录");
    }

    function cmpltFeeRcd()
    {
        $postData=input("request.");
        $optId=$postData["optId"];
        if(!empty($optId)){
            $msg=findById("feeItemOpt",array("id"=>$optId),"*");
            if(!empty($msg["data"])){
                $opt=$msg["data"];
                $data=array();
                if($opt["feestyle"]=="input"||$opt["feestyle"]=="numration"){
                    $fee=$postData["fee"];
                    if(empty($fee)){
                        return array("code"=>0,"msg"=>"金额不能为空");
                    }
                    $data["fee"]=$fee;
                }
                if($opt["numration"]=="data"){
                    $num=$postData["num"];
                    if(empty($num)){
                        return array("code"=>0,"msg"=>"数量不能为空");
                    }
                    $data["num"]=$num;
                }
                $ids=$postData["ids"];
                if(!empty($ids)){
                    $data["cate"]=1;
                    $rmsg=saveDataByCon("FeeRcd",$data,array("id"=>array("in",$ids)));
                    if($rmsg["code"]==1){
                        $rcdmsg=getDataList("FeeRcd",array("id"=>array("in",$ids)),"*");
                        if(!empty($rcdmsg["data"])){
                            $rcdlist=$rcdmsg["data"];
                            try {
                                foreach ($rcdlist as $rcd) {
                                    $totaldata=self::getFeeTotal($rcd["startTime"],$rcd["endTime"],$rcd["optId"],$rcd["etprsId"],$rcd["fee"],$rcd["num"]);
                                    if(!empty($totaldata["total"])){
                                        saveDataByCon("FeeRcd",array("total"=>$totaldata["total"]),array("id"=>$rcd["id"]));
                                    }
                                }
                                return array("code"=>1,"msg"=>"操作成功");
                            }catch (\Exception $e) {
                                return array("code"=>0,"msg"=>$e->getMessage());
                            }

                        }else{
                            return array("code"=>0,"msg"=>"没有待修改记录");
                        }
                    }else{
                        return array("code"=>0,"msg"=>"操作失败");
                    }
                }else{
                    return array("code"=>0,"msg"=>"没有记录需要修改");
                }
            }
        }
        return array("code"=>0,"msg"=>"参数错误");
    }
    function removePayfee($ids='',$status=2)
    {
        if(!empty($ids)){
             return saveDataByCon("feeRcd",array("status"=>$status),array("id"=>array("in",$ids)));
        }else{
            return array("code"=>0,"msg"=>"没有需要修改的记录");
        }
    }
    function feeFmt($value) {
        if ($value == 'num_price') {
            return "单价×数量";
        } else if ($value == "input") {
            return "每户单独输入";
        } else if ($value == "numration") {
            return "指定金额";
        }else{
            return "";
        }
    }
    function numratFmt($value) {
        if ($value == 'etprs') {
            return "按企业收取";
        } else if ($value == "people") {
            return "人数";
        } else if ($value == "area") {
            return "建筑面积";
        } else if ($value == "day") {
            return "按天";
        } else if ($value == "room") {
            return "房间数";
        } else if ($value == "smlroom") {
            return "工位数";
        } else if ($value == "data") {
            return "录入数据";
        } else{
            return "";
        }
    }

    function getFeeNum($numration="",$etprsId=0,$start=0,$end=0,$opt=array())
    {
        if($numration=="etprs"){
            return 1;
        }elseif($numration=="people"){
            $msg=findById("enterprise",array("id"=>$etprsId),"id,total");
            if(!empty($msg["data"])){
                return $msg["data"]["total"];
            }else{
                return 0;
            }
        }elseif($numration=="area"||$numration=="room"){
            $tmpcon=array("etprsId"=>$etprsId,'status'=>2,'type'=>1,'iqbtId'=>session("iqbtId"));
            if($numration=="area"&&isset($opt["objId"])){
                $tmpcon["id"]=$opt["objId"];
            }
            $msg=getDataList("EstateRoom",$tmpcon,"id,totalarea");
            if(!empty($msg["data"])){
                if($numration=="area"){
                    $total=0;
                    foreach ($msg["data"] as $r) {
                        $total=$total+$r["totalarea"];
                    }
                    return $total;
                }else{
                    return count($msg["data"]);
                }

            }else{
                return 0;
            }
        }elseif($numration=="smlroom"){
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'status'=>2,'type'=>0,'iqbtId'=>session("iqbtId")),"id,totalarea");
            if(!empty($msg["data"])){
                return count($msg["data"]);
            }else{
                return 0;
            }
        }elseif($numration=="day"){
            $startTime=strtotime($start);
            $endTime=strtotime($end)+86400;
            return intval(($endTime-$startTime)/86400);
        }
        return 0;
    }

    //获取企业缴费信息
    function getEtprsRcd($status=1,$etprsId='')
    {
        if(empty($etprsId)){
            $etprsId = session('etprsId');
        }

        $con=array("a.etprsId"=>$etprsId,'a.iqbtId'=>session("iqbtId"),'a.status'=>$status);

        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"]];
        $msg=getDataList("FeeRcd",$con,"a.*,b.name as itemName,c.name as optName,c.feestyle,c.id as optId","a.etprsId desc",$join);
        $data=array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            for ($i = 0; $i < count($data); $i++) {
                if(!empty($data[$i]["startTime"])){
                    $data[$i]["startTime"]=date("Y-m-d",$data[$i]["startTime"]);
                }else{
                    $data[$i]["startTime"]="";
                }
                if(!empty($data[$i]["endTime"])){
                    $data[$i]["endTime"]=date("Y-m-d",$data[$i]["endTime"]);
                }else{
                    $data[$i]["endTime"]="";
                }
                if($data[$i]['feestyle']=="num_price"){
                    $data[$i]['fee'] = "";
                }elseif($data[$i]['feestyle']=="input"){
                    $data[$i]['fee'] = "";
                    $data[$i]['num'] = "";
                    $data[$i]['price'] = "";
                }elseif($data[$i]['feestyle']=='numration'){
                    $data[$i]['num'] = "";
                    $data[$i]['price'] = "";
                }
                if(!empty($data[$i]['settletime'])){
                    $data[$i]['settletime'] = date("Y-m-d H:i",$data[$i]['settletime']);
                }else{
                    $data[$i]['settletime'] = '';
                }
                if(!empty($data[$i]['settleuserId'])){
                    $data[$i]['settleuserId'] = getField('user',array('id'=>$data[$i]['settleuserId']),'realname');
                }else{
                    $data[$i]['settleuserId'] ='';
                }
                if(!empty($data[$i]['roomId'])){
                    $data[$i]['roomId'] = getField('estateRoom',array('id'=>$data[$i]['roomId']),'roomNo');
                }else{
                    $data[$i]['roomId'] ='';
                }
            }
        }

        return $data;
    }


    function getRcdlist($status=1,$name='',$feestyle='',$settletype=0,$time_start='',$time_end='')
    {
        $con=array("a.status"=>$status,'a.iqbtId'=>session("iqbtId"));

        if(!empty($name)){
            $con["b.name|d.name|c.name"]=array("like","%".$name."%");
        }

        if(!empty($feestyle)){
            $con["c.feestyle"]=$feestyle;
        }
       // $settletype = input('settletype',0);
      //  $time_start = input('time_start','');
      //  $time_end = input('time_end','');
        if(!empty($settletype)){
            $con['a.settletype'] = $settletype;
        }

        if(!empty($time_start) && !empty($time_end)){
            $con['a.settletime'][] = array('gt',strtotime($time_start));
            $con['a.settletime'][] = array('lt',strtotime($time_end));
        }else{
            if(!empty($time_start)){
                $con['a.settletime'] = array('gt',strtotime($time_start));
            }
            if(!empty($time_end)){
                $con['a.settletime'] = array('lt',strtotime($time_end));
            }
        }
        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"],['enterprise d','a.etprsId=d.id',"left"]];
        $msg=getDataList("FeeRcd",$con,"a.*,b.name as itemName,c.name as optName,c.feestyle,d.name as etprsname,c.id as optId","a.etprsId desc ,a.id desc",$join);
        $data=array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            for ($i = 0; $i < count($data); $i++) {
                if(!empty($data[$i]["startTime"])){
                    $data[$i]["startTime"]=date("Y-m-d",$data[$i]["startTime"]);
                }else{
                    $data[$i]["startTime"]="";
                }
                if(!empty($data[$i]["endTime"])){
                    $data[$i]["endTime"]=date("Y-m-d",$data[$i]["endTime"]);
                }else{
                    $data[$i]["endTime"]="";
                }
                if($data[$i]['feestyle']=="num_price"){
                    $data[$i]['fee'] = "";
                }elseif($data[$i]['feestyle']=="input"){
                    $data[$i]['fee'] = "";
                    $data[$i]['num'] = "";
                    $data[$i]['price'] = "";
                }elseif($data[$i]['feestyle']=='numration'){
                    $data[$i]['num'] = "";
                    $data[$i]['price'] = "";
                }
                if(!empty($data[$i]['settletime'])){
                    $data[$i]['settletime'] = date("Y-m-d H:i",$data[$i]['settletime']);
                }else{
                    $data[$i]['settletime'] = '';
                }
                if(!empty($data[$i]['settleuserId'])){
                    $data[$i]['settleuserId'] = getField('user',array('id'=>$data[$i]['settleuserId']),'realname');
                }else{
                    $data[$i]['settleuserId'] ='';
                }
                if(!empty($data[$i]['roomId'])){
                    $data[$i]['roomId'] = getField('estateRoom',array('id'=>$data[$i]['roomId']),'roomNo');
                }else{
                    $data[$i]['roomId'] ='';
                }
            }
        }
        return $data;
    }

    function export(){
        $ids = input('id');
        $ids = explode(",",$ids);
        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"],['enterprise d','a.etprsId=d.id',"left"]];
        $msg=getDataList("FeeRcd",array('a.id'=>array('in',$ids)),"a.*,b.name as itemName,c.name as optName,c.feestyle,d.name as etprsname,c.id as optId","a.etprsId desc",$join);
        $data=array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            $expData = array();
            for ($i = 0; $i < count($data); $i++) {
                $expData[$i] = array(
                    'itemName'=>$data[$i]['itemName'],
                    'etprsname'=>$data[$i]['etprsname'],
                    'optName'=>$data[$i]['optName'],
                );
                //交费方式
                if ($data[$i]['feestyle'] == 'num_price') {
                    $expData[$i]['feestyle'] = "单价×数量";
                } else if ($data[$i]['feestyle'] == "input") {
                    $expData[$i]['feestyle'] = "每户单独输入";
                } else if ($data[$i]['feestyle'] == "numration") {
                    $expData[$i]['feestyle'] = "指定金额";
                }else{
                    $expData[$i]['feestyle'] = "未知方式";
                }
                //房间号
                if(!empty($data[$i]['roomId'])){
                    $expData[$i]['roomId'] = getField('estateRoom',array('id'=>$data[$i]['roomId']),'roomNo');
                }else{
                    $expData[$i]['roomId'] ='';
                }

                if(!empty($data[$i]["startTime"])){
                    $expData[$i]["startTime"]=date("Y-m-d",$data[$i]["startTime"]);
                }else{
                    $expData[$i]["startTime"]="";
                }
                if(!empty($data[$i]["endTime"])){
                    $expData[$i]["endTime"]=date("Y-m-d",$data[$i]["endTime"]);
                }else{
                    $expData[$i]["endTime"]="";
                }
                $expData[$i]["fee"]=$data[$i]['fee'];
                $expData[$i]["price"]=$data[$i]['price'];
                $expData[$i]["num"]=$data[$i]['num'];
                $expData[$i]["total"]=$data[$i]['total'];
                if($data[$i]['feestyle']=="num_price"){
                    $expData[$i]['fee'] = "";
                }elseif($data[$i]['feestyle']=="input"){
                    $expData[$i]['fee'] = "";
                    $expData[$i]['num'] = "";
                    $expData[$i]['price'] = "";
                }elseif($data[$i]['feestyle']=='numration'){
                    $expData[$i]['num'] = "";
                    $expData[$i]['price'] = "";
                }
                if(!empty($data[$i]['settletype'])){
                    if ($data[$i]['settletype'] == '1') {
                        $expData[$i]['settletype'] =  "现金";
                    } else if ($data[$i]['settletype'] == "2") {
                        $expData[$i]['settletype'] = "刷卡";
                    } else if ($data[$i]['settletype'] == "3") {
                        $expData[$i]['settletype'] = "网络转账";
                    }else{
                        $expData[$i]['settletype']= "未知方式";
                    }
                }else{
                    $expData[$i]['settletype'] = '';
                }

                if(!empty($data[$i]['settletime'])){
                    $expData[$i]['settletime'] = date("Y-m-d H:i",$data[$i]['settletime']);
                }else{
                    $expData[$i]['settletime'] = '';
                }
                if(!empty($data[$i]['settleuserId'])){
                    $expData[$i]['settleuserId'] = getField('user',array('id'=>$data[$i]['settleuserId']),'realname');
                }else{
                    $expData[$i]['settleuserId'] ='';
                }

            }
        }
        $header = array('项目名称','缴费企业','缴费标准','收费方式','房间号','开始时间','结束时间','固定金额','单价','数量','缴费金额','缴费方式','缴费时间','操作人');
      //  header("Content-Type: text/html;charset=utf-8");
     //   print_r($expData['0']);
        vendor("PHPExcel");
        vendor("PHPExcel.Writer.Excel5");
        vendor("PHPExcel.IOFactory");
        $type = input('type','1');
        if($type ==1){
            $filename = '未缴费记录';
        }else if($type ==2){
            $filename = '已缴费记录';
        }else{
            $filename = '移除缴费记录';
        }
        getExcel($filename,$header,$expData);

    }

    //获取缴费周期 数
    function getCycleNum($start,$end,$cycle)
    {
        $startTime=strtotime($start);
        $endTime=strtotime($end)+86400;

        $startData=date("Y-m-d",$startTime);
        $endData=date("Y-m-d",$endTime);

        list($y1,$m1,$d1)=explode("-",$startData);
        list($y2,$m2,$d2)=explode("-",$endData);
        $ylen=$y2-$y1;
        $mlen=$m2-$m1;
        $len=$ylen*12+$mlen;
        $d=$d2-$d1;
        if($mlen>0){
            if ($d < 0)
            {
                $len -= 1;
            }
        }
        return intval($len/$cycle);
    }

    function getFeeTotal($start='',$end='',$optId=0,$etprsId=0,$fee=0,$tmpnum=0)
    {
        $msg=findById("feeItemOpt",array("id"=>$optId),"id,numration,cycle,price,fee,feestyle");
        if(!empty($msg["data"])){
            $numration=$msg["data"]["numration"];
            $cycle=$msg["data"]["cycle"];
            $price=$msg["data"]["price"];
            $style=$msg["data"]["feestyle"];

            $total=0;
            $cycleNum=0;
            $num=0;
            if(!empty($cycle)&&!empty($start)&&!empty($end)){
                $cycleNum=self::getCycleNum($start,$end,$cycle);
            }

            if(!empty($numration)){
                if($numration=="data"){
                    $num=$tmpnum;
                }else{
                    $num=self::getFeeNum($numration,$etprsId,$start,$end);
                }
            }
            if($style=="num_price"){
                $total=$price*$num;
                //按周期
                if(!empty($cycle)){
                    $total=$price*$cycleNum;
                }
            }else if($style=="numration"){
                //指定金额
                $fee=$msg["data"]["fee"];
                //数量
                if(!empty($numration)){
                    $total=$fee*$num;
                }else{
                    $total=$fee;
                }
                //按周期
                if(!empty($cycle)){
                    $total=$total*$cycleNum;
                }
            }elseif($style=="input"){
                //每户单独输入
                //echo $numration."---".$num."-----".$fee;
                //数量
                if(!empty($numration)){
                    $total=$fee*$num;
                }else{
                    $total=$fee;
                }
                //按周期
                if(!empty($cycle)){
                    $total=$total*$cycleNum;
                }
            }
            $data=array("total"=>$total,"num"=>$num,"cycle"=>$cycleNum);
            return $data;
        }else{
            return 0;
        }
    }

    //修改缴费记录的最终价格 $id 缴费记录ID
    function editFeeRcd($id){
        if(!empty($id)){
            $feeMsg = findById('feeRcd',array('id'=>$id),'*');
            if($feeMsg['code']==1 && !empty($feeMsg['data'])){
                $feeInfo = $feeMsg['data'];
            }else{
                $feeInfo = array();
            }
        }else{
            return array('code'=>0,'msg'=>'参数错误');
        }
        return view('',array('data'=>$feeInfo));
    }

    //保存修改的改价记录
    function saveFeeRcdLog(){
        $postData = input('request.');
        if(floatval($postData['fee_final'])<=0){
            return array('code'=>0,'msg'=>'请输入正确的金额');
        }
        if(empty($postData['desc'])){
            return array('code'=>0,'msg'=>'修改理由必填');
        }
        if(!empty($postData['id'])){
            //把修改后的价格保存到缴费记录表里
            $msg = saveDataByCon('feeRcd',array('total'=>$postData['fee_final']),array('id'=>$postData['id']),'修改缴费最终价格');
            if($msg['code']==1){
                //添加修改记录
                $data = array(
                    'fee_rcd_log'=>$postData['id'],
                    'iqbtId'=>$postData['iqbtId'],
                    'etprsId'=>$postData['etprsId'],
                    'optId'=>  $postData['optId'],
                    'itemId'=>$postData['itemId'],
                    'roomId'=>$postData['roomId'],
                    'fee_original'=>$postData['fee_original'],
                    'fee_final' =>$postData['fee_final'],
                    'desc' =>$postData['desc'],
                    'addtime'=>time(),
                    'adduserId'=>session('userId')
                );
                saveData('feeRcdLog',$data,'添加修改记录');
                return array('code'=>1,'msg'=>'修改成功');
            }
        }else{
            return array('code'=>0,'msg'=>'参数错误');
        }
    }


    //发送缴费消息通知
    function addMsg(){
        $ids = input("ids",'');

        if(!empty($ids)){
            return view("",array('ids'=>$ids));
        }
    }
    //把要发送消息的缴费记录按照企业进行分类列出
    function getMsgList($ids=''){
       // $ids = '219,220,221,222,223';
        if(!empty($ids)){
            $idArr = explode(",",$ids);
            $map = array(
                'a.iqbtId'=>session('iqbtId'),
                'a.id'=>array('in',$idArr)
            );
            $join = [['enterprise b','a.etprsId=b.id','left'],['fee_item c','a.itemId=c.id','left']];
            $msg = getDataList('feeRcd',$map,'a.etprsId,a.optId,a.itemId,a.total,b.name as etprsName,c.name as itemName','',$join);
            if($msg['code']==1 && !empty($msg['data'])){
                $data = array();
                $etprsArr = array('0');
                foreach($msg['data'] as $value){
                    $etprsArr[] = $value['etprsId'];
                    if(in_array($value['etprsId'],$etprsArr)){
                        $data[$value['etprsId']] = array('sum'=>0,'etprsName'=>$value['etprsName'],'sub'=>array());
                    }
                }
                foreach($msg['data'] as $value){
                    $data[$value['etprsId']]['sum']+= $value['total'];
                    $data[$value['etprsId']]['sub'][] = $value;
                }
                $data1 = array_values($data);
              //  print_r($data1);exit();
                return $data1;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    //发送消息通知
    function saveMsg($ids=''){
        if(!empty($ids)){
            $sms = input('sms',0);
            $idArr = explode(",",$ids);
            $map = array(
                'a.iqbtId'=>session('iqbtId'),
                'a.id'=>array('in',$idArr)
            );
            $join = [['enterprise b','a.etprsId=b.id','left'],['fee_item c','a.itemId=c.id','left']];
            $msg = getDataList('feeRcd',$map,'a.etprsId,a.optId,a.itemId,a.total,b.name as etprsName,c.name as itemName','',$join);
            if($msg['code']==1 && !empty($msg['data'])){
                $data = array();
                $etprsArr = array('0');
                foreach($msg['data'] as $value){
                    $etprsArr[] = $value['etprsId'];
                    if(in_array($value['etprsId'],$etprsArr)){
                        $data[$value['etprsId']] = array('sum'=>0,'etprsName'=>$value['etprsName'],'sub'=>array());
                    }
                }

                foreach($msg['data'] as $value){
                    $data[$value['etprsId']]['uid']  = getField('user',array('etprsId'=>$value['etprsId'],'userCate'=>'1011002'),'id');
                    $data[$value['etprsId']]['mobile']  = getField('user',array('etprsId'=>$value['etprsId'],'userCate'=>'1011002'),'mobile');
                    $data[$value['etprsId']]['sum']+= $value['total'];
                    $data[$value['etprsId']]['sub'][] = $value;
                }
                //针对每个企业发邮件
                $userIdStr = '';
                $etprsStr = '';
                $content = '';
                foreach($data as $value1){
                    $userIdStr .= $value1['uid'].",";
                    $etprsStr .= $value1['etprsName'].",";
                    $content .= $value1['etprsName']."需要缴费:".$value1['sum']."元,";
                }
                //发邮件
                $outData = array(
                    "iqbtId"=>session("iqbtId"),
                    "addtime"=>time(),
                    'userId' => session("userId"),
                    'toUserId'=>$userIdStr,
                    'toUserName'=>$etprsStr,
                    'title'=>'财务缴费通知',
                    'content'=>$content,
                    'status'=>1
                );
                $res = saveData('SysOutbox',$outData,'发送站内信');
                if($res['code']==1) {
                    //说明发件箱保存成功，然后分别把信息保存到收件箱
                    foreach ($data as $value2) {
                        if (empty($value2['uid'])) {
                            continue;
                        }
                        $etprsContent = '尊敬的' . $value2['etprsName'] . ",您本次应该缴费金额为" . $value2['sum'] . "元，缴费项目如下：";
                        $mobileContent = $value2['sum'].'元，缴费项目如下：';
                        foreach ($value2['sub'] as $val) {
                            $etprsContent .= $val['itemName'] . "，缴费金额为" . $val['total'] . "元;";
                            $mobileContent .= $val['itemName'] . "，缴费金额为" . $val['total'] . "元;";
                        }
                        $emailData = array(
                            'type' => '1020008',
                            'title' => '财务缴费通知',
                            'content' => $etprsContent,
                        );
                        //如果需要发短信
                        $smsData = array();
                        if($sms==1){
                             $tpl_id = config('sms_tpl_id.fee');
                            $data = array(
                                'info'=>$mobileContent,
                            );
                            $smsData = array(
                                'tpl'=>$tpl_id,
                                'data'=>$data,
                            );
                        }
                        $this->sendAllMsg($value2['uid'],$emailData,array(),$smsData);
                    }

                }
            return array('code'=>1,'msg'=>'成功');

            }else{
                return array('code'=>0,'msg'=>'没有数据');
            }
        }else{
            return array('code'=>0,'msg'=>'参数为空');
        }
    }


    //自动生成缴费记录页面
    function createFee()
    {
        return view();
    }

    /**
     * 生成缴费记录
     * @param string $feestyle  缴费类型  退费、周期性 临时性  押金
     * @param string $item  缴费项目  房租、押金、物业费、工本费...
     * @param string $opt   收费标准
     * @param int $etprsId   企业ID
     * @param int $fee
     * @param int $tmpnum
     * @param string $startday
     * @param string $endday
     * @return int
     */
    function createRcd($etprsId,$fee=0,$tmpnum=0,$startday='',$endday='')
    {

        $aplOptlist=array();  //入驻缴费配置里与房间工位无关联的项目
        $renewOptlist = array();  //续约缴费里与房间工位无关联的项目
        $etprslist=array();  //公司列表
        $aplcon=array("a.iqbtId"=>session("iqbtId"),'a.feetype'=>'1030001','b.about'=>'0');
        $renewcon=array("a.iqbtId"=>session("iqbtId"),'a.feetype'=>'1030002','b.about'=>'0');
        $join = [['fee_item b','a.itemId=b.id','left'],['fee_item_opt c','a.optId=c.id','left']];
        $aplOptmsg=getDataList("feeItemCfg",$aplcon,"c.*,b.about",'a.id',$join);
        if(!empty($aplOptmsg["data"])){
            $aplOptlist=$aplOptmsg["data"];
        }
        $renewOptMsg = getDataList("feeItemCfg",$renewcon,"c.*",'a.id',$join);
        if(!empty($renewOptMsg['data'])){
            $renewOptlist = $renewOptMsg['data'];
        }
        $econ=array("a.iqbtId"=>session("iqbtId"),'status'=>'1001016');
        if(!empty($etprsId)){
            $econ["id"]=$etprsId;
        }
        $emsg=getDataList("enterprise",$econ,"id,entertime,name,renewStatus");
        if(!empty($emsg["data"])){
            $etprslist=$emsg["data"];
        }else{
            return 0;
        }

        $rcd_num=0;
        //计算每一个企业的应缴费用
        foreach ($etprslist as $etprs) {
            //计算与房间相关的费用
               $etprsId=$etprs["id"];
              $join = [['estate_room b','a.roomId=b.id',"left"]];
              $roommsg=getDataList("estateRoomEtprs",array("a.etprsId"=>$etprsId,"a.status"=>array("in",[0,1,3]),'b.status'=>'2','b.type'=>'1'),"a.roomId,a.startTime,a.endTime,b.feeOptIds,b.roomNo,b.totalarea","a.id",$join);
             if(!empty($roommsg["data"])){
                  $roomslist = $roommsg['data'];
                  //计算每个房间该缴的费用
                  foreach($roomslist as $room){
                      $rcd_num += self::createRoomRcd($etprs,$room);
                  }
              }

            //计算与房间无关联的费用
            if(empty($etprs['renewStatus'])){
            //    header("Content-Type: text/html;charset=utf-8");
            //    print_r($aplOptlist);exit();
                //状态为0代表为入驻状态，为其他代表为续约状态
                foreach($aplOptlist as $aplopt){

                    if(!empty($aplopt['cycle'])){
                        $rcd_num += self::createCycle($aplopt,$etprs,$fee,$tmpnum);
                    }else{
                        $rcd_num += self::saveRcd($aplopt,$etprs['id'],$fee,$tmpnum,$startday,$endday);
                    }
                }
            }else{
                foreach($renewOptlist as $renewopt){
                    #todo 计算费用
                    if(!empty($renewopt['cycle'])){
                        $rcd_num += self::createCycle($renewopt,$etprs,$fee,$tmpnum);
                    }else{
                        $rcd_num += self::saveRcd($renewopt,$etprs['id'],$fee,$tmpnum,$startday,$endday);
                    }
                }
            }



        }
        return $rcd_num;
    }

    //计算每个房间应该缴纳的费用
    function createRoomRcd($etprs=array(),$room=array()){
        $optids = $room['feeOptIds'];
        if(!empty($optids)){
            $optArr = explode(",",$optids);
        }else{
            return 0;
        }
        $rcd_room_num = 0;
        //针对每一个收费标准进行收费
        foreach($optArr as $key=>$value){
            $optmsg = findById('FeeItemOpt',array('id'=>$value),'*');
            if($optmsg['code']==1 && !empty($optmsg['data'])){
                $optinfo = $optmsg['data'];
                //如果是周期性的收费，就周期性计算，如果不是，就一次性计算
                if(!empty($optinfo['cycle'])){
                    $rcd_room_num += self::CreateRoomCycle($etprs,$room,$optinfo);
                }else{
                    $rcd_room_num += self::saveRoomRcd($etprs,$room,$optinfo);
                }
            }
        }
        return $rcd_room_num;
    }


    //计算与房间相关的周期性收费
    function createRoomCycle($etprs,$room,$opt){
        $rcdnum=0;
        $itemId=$opt["itemId"];
        $etprsId=$etprs["id"];
        $starttime = $room['startTime'];
        $quittime = $room['endTime'];
        $con = array("itemId"=>$itemId,"etprsId"=>$etprsId,"iqbtId"=>session("iqbtId"),'roomId'=>$room['roomId']);
        $lastOptMsg=findById("feeRcd",$con,"startTime,endTime,id",array(),'0',"endTime desc");

        if(empty($lastOptMsg["data"])||empty($lastOptMsg["data"]["endTime"])){
            //没有生成过缴费记录
            $cfgmsg=findById("FeeCfg",array("iqbtId"=>session("iqbtId")),"cycletype");
            if(isset($cfgmsg["data"]["cycletype"])&&$cfgmsg["data"]["cycletype"]=='1'){
                //指定月月初作为初始时间
                $starttime=self::halfRoomRcd($etprs,$room,$opt);//生成入驻时间到周期初时间段的缴费信息
                if(!empty($starttime)){
                    $starttime=$starttime+86400;
                    $rcdnum=1;
                }else{
                    return 0 ;
                }
            }else{
                //签订合同时间作为初始时间
                //开始时间计算在上面about的判断中，这里不需要再判断
            }
        }else{
            $starttime=$lastOptMsg["data"]["endTime"]+86400;
        }
        //开始循环遍历
        if(!empty($starttime)&&!empty($quittime)){
            $starttime=date("Y-m-d",$starttime);

            $now=time();//当前时间
            $totime=strtotime(date("Y-m-d",time())); //当前时间去掉时分秒
            $cycle=$opt["cycle"];//缴费周期
            $endTime=date("Y-m-d",strtotime("+".$cycle." months",strtotime($starttime))-86400);
            $tmpendtime=strtotime($endTime);

            $totime=strtotime("+".$cycle." months",$totime);


            if($totime<=$quittime){
                while($tmpendtime<$totime){
                    $msg=self::saveCycleRoomRcd($etprs,$room,$opt,$starttime,$endTime);
                    if($msg["code"]==='1'){
                        $rcdnum++;
                    }

                    $starttime=date("Y-m-d",strtotime($endTime)+86400);
                    $tmpendtime=strtotime("+".$cycle." months",strtotime($starttime));
                    $endTime=date("Y-m-d",$tmpendtime-86400);
                }
            }else if(strtotime($starttime)<$quittime){
                //到了最后一个周期，计算最后周期天数的缴费记录
                //如果离结束时间还有15天，提醒缴费
                if(intval(($quittime-$now)/86400)==15){
                    //发送通知，及时缴费
                 //   $etprsId=$etprs["id"];
               //     $etprsUserId=0;
                //    $umsg=findById("user",array("etprsId"=>$etprsId),"mobile,id");
                 //   if(!empty($umsg["data"])){
                        // $mobile=$umsg["data"]["mobile"];
                        //  $etprsUserId=$umsg["data"]["id"];
                        //  sendSms($mobile,"","31902");
                 //   }
                    // $push = new wechatPush();
                    //  $push->newApply($etprsUserId,$etprs['name']);
                }
                self::lasthalfRoomRcd($etprs,$room,$opt,$starttime,$tmpendtime);
                $rcdnum++;
            }

        }
        return $rcdnum;

    }



    //与房间无关的周期性的  比如 按照企业1个月收取一次 垃圾清理费
    function createCycle($opt,$etprs,$fee=0,$tmpnum=0){
        $rcdnum=0;
        $itemId=$opt["itemId"];
        $etprsId=$etprs["id"];
        //与房间无关的周期性收费统一按照入驻时间
        $etprsmsg=findById("enterprise",array("id"=>$etprsId),"entertime,quittime,pactquittime");
        if(!empty($etprsmsg["data"])&&isset($etprsmsg["data"]["entertime"])&&!empty($etprsmsg["data"]["entertime"])){
            $starttime = $etprsmsg["data"]["entertime"];
            $quittime = $etprsmsg["data"]["pactquittime"];
        }

        $lastOptMsg=findById("feeRcd",array("itemId"=>$itemId,"etprsId"=>$etprsId,"iqbtId"=>session("iqbtId")),"startTime,endTime,id",array(),'0',"endTime desc");
        if(empty($lastOptMsg["data"])||empty($lastOptMsg["data"]["endTime"])){
            //没有生成过缴费记录

            $cfgmsg=findById("FeeCfg",array("iqbtId"=>session("iqbtId")),"cycletype");

            if(isset($cfgmsg["data"]["cycletype"])&&$cfgmsg["data"]["cycletype"]=='1'){

                //指定月月初作为初始时间

                $starttime=self::halfRcd($etprs,$opt,$fee,$tmpnum);//生成入驻时间到周期初时间段的缴费信息
                if(!empty($starttime)){
                    $starttime=$starttime+86400;
                    $rcdnum=1;
                }else{
                    //如果返回的为空，说明这条收费记录没有，不需要以下的循环
                    return 0;
                }
            }else{
                //签订合同时间作为初始时间
                //开始时间计算在上面about的判断中，这里不需要再判断
            }
        }else{
            $starttime=$lastOptMsg["data"]["endTime"]+86400;
        }

        //开始循环遍历
        if(!empty($starttime)&&!empty($quittime)){
            $starttime=date("Y-m-d",$starttime);
            $now=time();
            $totime=strtotime(date("Y-m-d",time()));
            $cycle=$opt["cycle"];//缴费周期
            $endTime=date("Y-m-d",strtotime("+".$cycle." months",strtotime($starttime))-86400);
            $tmpendtime=strtotime($endTime);
            $totime=strtotime("+".$cycle." months",$totime);

            if($totime<=$quittime){
                while($tmpendtime<$totime){
                    if(intval((strtotime($starttime)-time())/86400)<=15) {
                        $msg = self::saveCycleRcd($opt, $starttime, $endTime, $etprsId, $fee, $tmpnum);
                        if ($msg["code"] === '1') {
                            $rcdnum++;
                            //发送通知,缴费通知
                            //todo

                        }

                        $starttime = date("Y-m-d", strtotime($endTime) + 86400);
                        $tmpendtime = strtotime("+" . $cycle . " months", strtotime($starttime));
                        $endTime = date("Y-m-d", $tmpendtime - 86400);
                    }else{
                        break;
                    }
                }
            }else if(strtotime($starttime)<$quittime){

                //到了最后一个周期，计算最后周期天数的缴费记录
                //如果离结束时间还有15天，提醒续费或者准备退出
                if(intval(($quittime-$now)/86400)<=15){
                    //发送通知，入驻时间将要到期，及时续费
                    $etprsId=$etprs["id"];
                    $etprsUserId=0;
                    $umsg=findById("user",array("etprsId"=>$etprsId),"mobile,id");
                    if(!empty($umsg["data"])){
                       // $mobile=$umsg["data"]["mobile"];
                      //  $etprsUserId=$umsg["data"]["id"];
                      //  sendSms($mobile,"","31902");
                    }
                   // $push = new wechatPush();
                  //  $push->newApply($etprsUserId,$etprs['name']);

                }
                if(intval((strtotime($starttime)-time())/86400)<=15) {
                    self::lasthalfRcd($etprs, $opt, $fee, $tmpnum, strtotime($starttime), $quittime);
                    $rcdnum++;
                    //发送通知,缴费通知
                    //todo
                }

            }

        }
        return $rcdnum;
    }

    //房间的非周期缴费
    function saveRoomRcd($etprs,$room,$opt)
    {
        $startday = 0;
        $endday = 0;
        $total = 0;
        $etprsId = $etprs['id'];
        $con=array("etprsId"=>$etprsId,"itemId"=>$opt["itemId"],'iqbtId'=>$opt["iqbtId"],'roomId'=>$room['roomId']);
        $msg=findById("FeeRcd",$con,"id");
        $num=0;
        $rcd["cate"]="1";
        if(empty($msg["data"])){
            //没有缴费记录
            $numration=$opt["numration"];
            if(!empty($numration)){
                if($numration=="data"){
                    $rcd["cate"]="2";
                    $num=0;
                }elseif($numration =="area"){
                    $num = $room['totalarea'];
                }else{
                    $num = 1;
                }
            }

            $price=$opt["price"];
            $style=$opt["feestyle"];

            if($style=="num_price"){
                $total=$price*$num;
            }else if($style=="numration"){
                //指定金额
                $fee=$opt["fee"];
                if(empty($fee)){
                    //没有录入指定金额
                    $rcd["cate"]=2;
                    $total = 0;
                }else{
                    $total=$fee;
                }

            }elseif($style=="input"){
                //每户单独输入
                //如果没有指定金额或者指定数量，则status=2
                $rcd["cate"]="2";
                $total = 0;
            }
            //如果cate为1 并且total 为0 ，就不生成记录
            if($rcd['cate'] ==1 && $total ==0){
                return 0;
            }
            $rcd["itemId"]=$opt["itemId"];
            $rcd["etprsId"]=$etprsId;
            $rcd["optId"]=$opt["id"];
            $rcd["num"]=$num;
            $rcd["total"]=$total;
            $rcd["price"]=$opt["price"];
            $rcd["fee"]=$opt['fee'];
            $rcd["adduserId"]=session("userId");
            $rcd["iqbtId"]=$opt["iqbtId"];
            $rcd["addtime"]=time();
            $rcd["startTime"]=strtotime($startday);
            $rcd["endTime"]=strtotime($endday);
            $rcd['roomId'] = $room['roomId'];
            $msg=saveData("FeeRcd",$rcd,"添加非周期性缴费");
            if($msg["code"]==='1'){
                return 1;
            }else{
                return 0;
            }
        }else{
            //已经有缴费记录
            return 0;
        }

    }
    //添加非周期性记录
    function saveRcd($opt,$etprsId,$fee=0,$tmpnum=0,$startday='',$endday='')
    {
        //$con=array("etprsId"=>$etprsId,"optId"=>$opt["id"],'iqbtId'=>$opt["iqbtId"]);
        $con=array("etprsId"=>$etprsId,"itemId"=>$opt["itemId"],'iqbtId'=>$opt["iqbtId"]);
        $msg=findById("FeeRcd",$con,"id");
        $num=0;
        $total = 0;
        $rcd["cate"]="1";
        if(empty($msg["data"])){
            //没有缴费记录
            $numration=$opt["numration"];
            if(!empty($numration)){
                if($numration=="data"){
                    if(empty($tmpnum)){
                        $rcd["cate"]="2";
                    }
                    $num=$tmpnum;
                }else{
                    $num=self::getFeeNum($numration,$etprsId,$startday,$endday,$opt);
                }
            }

            $price=$opt["price"];
            $style=$opt["feestyle"];

            if($style=="num_price"){
                $total=$price*$num;
            }else if($style=="numration"){
                //指定金额
                $fee=$opt["fee"];
                if(empty($fee)){
                    //没有录入指定金额
                    $rcd["cate"]=2;
                    $total = 0;
                }else{
                    $total=$fee;
                }
                /*//数量
                if(!empty($numration)){
                    $total=$fee*$num;
                }else{
                    $total=$fee;
                }*/
            }elseif($style=="input"){
                //每户单独输入
                //如果没有指定金额或者指定数量，则status=2
                $rcd["cate"]="2";
                $total = 0;
             /*   if(empty($fee)){
                    $rcd["cate"]="2";
                }
                //数量
                if(!empty($numration)){
                    $total=$fee*$num;
                }else{
                    $total=$fee;
                }*/
            }
            //如果cate为1 并且total 为0 ，就不生成记录
            if($rcd['cate'] ==1 && $total ==0){
                return 0;
            }
            $rcd["itemId"]=$opt["itemId"];
            $rcd["etprsId"]=$etprsId;
            $rcd["optId"]=$opt["id"];
            $rcd["num"]=$num;
            $rcd["total"]=$total;
            $rcd["price"]=$opt["price"];
            $rcd["fee"]=$opt['fee'];
            $rcd["adduserId"]=session("userId");
            $rcd["iqbtId"]=$opt["iqbtId"];
            $rcd["addtime"]=time();
            $rcd["startTime"]=strtotime($startday);
            $rcd["endTime"]=strtotime($endday);
            $msg=saveData("FeeRcd",$rcd,"添加非周期性缴费");
            if($msg["code"]==='1'){
                return 1;
            }else{
                return 0;
            }
        }else{
            //已经有缴费记录
            return 0;
        }

    }

    //房间的周期性缴费
    function saveCycleRoomRcd($etprs,$room,$opt,$starttime,$endTime){
        $etprsId = $etprs['id'];
        $rcd["cate"]="1";
        $total = 0;
        $numration=$opt["numration"];
        if(!empty($numration)){
            if($numration=="data"){
                if(empty($tmpnum)){
                    $rcd["cate"]="2";
                }
                $num=0;
            }elseif($numration =="area"){
                $num = $room['totalarea'];
            }else{
                $num = 1;
            }
        }
        $price=$opt["price"];
        $style=$opt["feestyle"];

        if($style=="num_price"){
            $total=$price*$num;
        }else if($style=="numration"){
            //指定金额
            $fee=$opt["fee"];
            if(empty($fee)){
                //没有录入指定金额
                $rcd["cate"]=2;
            }
            //数量
            if(!empty($numration)){
                $total=$fee*$num;
            }else{
                $total=$fee;
            }
        }elseif($style=="input"){
            //每户单独输入
            //如果没有指定金额或者指定数量，则status=2
            if(empty($fee)){
                $rcd["cate"]="2";
            }
            //数量
            if(!empty($numration)){
                $total=$fee*$num;
            }else{
                $total=$fee;
            }
        }
        //如果cate为1 并且total 为0 ，就不生成记录
        if($rcd['cate'] ==1 && $total ==0){
            return array('code'=>0,'msg'=>'缴费金额为0');
        }
        $rcd["itemId"]=$opt["itemId"];
        $rcd["etprsId"]=$etprsId;
        $rcd["optId"]=$opt["id"];
        $rcd["iqbtId"]=$opt["iqbtId"];
        $rcd["num"]=$num;
        $rcd["total"]=$total;
        $rcd["price"]=$opt["price"];
        $rcd["fee"]=$opt['fee'];
        $rcd["adduserId"]=session("userId");
        $rcd["addtime"]=time();
        $rcd["startTime"]=strtotime($starttime);
        $rcd["endTime"]=strtotime($endTime);
        $rcd['roomId'] = $room['roomId'];
        $msg=saveData("FeeRcd",$rcd,"添加周期性缴费");
        return $msg;
    }


    //添加周期性缴费
    function saveCycleRcd($opt,$starttime,$endTime,$etprsId,$fee=0,$tmpnum=0)
    {
        $rcd["cate"]="1";
        $total = 0;
        $numration=$opt["numration"];
        if(!empty($numration)){
            if($numration=="data"){
                if(empty($tmpnum)){
                    $rcd["cate"]="2";
                }
                $num=$tmpnum;
            }else{
                $num=self::getFeeNum($numration,$etprsId,$starttime,$endTime,$opt);
            }
        }
        $price=$opt["price"];
        $style=$opt["feestyle"];

        if($style=="num_price"){
            $total=$price*$num;
        }else if($style=="numration"){
            //指定金额
            $fee=$opt["fee"];
            if(empty($fee)){
                //没有录入指定金额
                $rcd["cate"]=2;
            }
            //数量
            if(!empty($numration)){
                $total=$fee*$num;
            }else{
                $total=$fee;
            }
        }elseif($style=="input"){
            //每户单独输入
            //如果没有指定金额或者指定数量，则status=2
            if(empty($fee)){
                $rcd["cate"]="2";
            }
            //数量
            if(!empty($numration)){
                $total=$fee*$num;
            }else{
                $total=$fee;
            }
        }
        //如果cate为1 并且total 为0 ，就不生成记录
        if($rcd['cate'] ==1 && $total ==0){
            return array('code'=>0,'msg'=>'缴费金额为0');
        }
        $rcd["itemId"]=$opt["itemId"];
        $rcd["etprsId"]=$etprsId;
        $rcd["optId"]=$opt["id"];
        $rcd["iqbtId"]=$opt["iqbtId"];
        $rcd["num"]=$num;
        $rcd["total"]=$total;
        $rcd["price"]=$opt["price"];
        $rcd["fee"]=$opt['fee'];
        $rcd["adduserId"]=session("userId");
        $rcd["addtime"]=time();
        $rcd["startTime"]=strtotime($starttime);
        $rcd["endTime"]=strtotime($endTime);
        $msg=saveData("FeeRcd",$rcd,"添加周期性缴费");
        return $msg;
    }

    //房间的非完整周期
    function halfRoomRcd($etprs=array(),$room=array(),$opt=array()){
        $etprsId=$etprs["id"];
        $cycle=$opt["cycle"];
        $total = 0;
        $entertime=date("Y-m-d",$room["startTime"]);
        if(!empty($cycle)){
            $timearea=self::getCycleStartTime($entertime,$cycle);
        }
        if(!empty($timearea)){
            $rcd["cate"]="1";
            $numration=$opt["numration"];
            if(!empty($numration)){
                if($numration=="data"){
                    //数据手动录入
                    $rcd["cate"]=2;
                    $num=0;
                }elseif($numration =="area"){
                    $num = $room['totalarea'];
                }else{
                    $num = 1;
                }
            }
            $price=$opt["price"];
            $style=$opt["feestyle"];

            if($style=="num_price"){
                $total=$price*$num;
            }else if($style=="numration"){
                //指定金额
                $fee=$opt["fee"];
                if(empty($fee)){
                    //没有录入指定金额
                    $rcd["cate"]=2;
                    $toal = 0;
                }else{
                    $total = $fee;
                }

            }elseif($style=="input"){
                //每户单独输入
                //如果没有指定金额或者指定数量，则status=2
                $rcd["cate"]=2;
                $total = 0;

            }

            //根据天数计算应付多少钱
            $totalday=(strtotime($timearea[1])+86400-strtotime($timearea[0]))/86400;
            $feeday=(strtotime($timearea[1])+86400-strtotime($entertime))/86400;
            $total=$total*$feeday/$totalday;  //总额*（入驻天数/周期总天数）
            if($rcd['cate']==1 && $total ==0){
                return '0';
            }
            $rcd["itemId"]=$opt["itemId"];
            $rcd["etprsId"]=$etprsId;
            $rcd["optId"]=$opt["id"];
            $rcd["iqbtId"]=$opt["iqbtId"];
            $rcd["num"]=$num;
            $rcd["total"]=$total;
            $rcd["price"]=$opt["price"];
            $rcd["fee"]=$opt['fee'];
            $rcd["adduserId"]=session("userId");
            $rcd["addtime"]=time();
            $rcd["startTime"]=strtotime($entertime);
            $rcd["endTime"]=strtotime($timearea[1]);
            $rcd['roomId'] = $room['roomId'];
            $msg=saveData("FeeRcd",$rcd,"添加周期性缴费");
            return strtotime($timearea[1]);
        }else{
            return "";
        }
    }


    //非完整周期
    function halfRcd($etprs,$opt,$fee=0,$tmpnum=0)
    {
        $etprsId=$etprs["id"];
        $cycle=$opt["cycle"];
        $total = 0;
        $entertime=date("Y-m-d",$etprs["entertime"]);
        if(!empty($cycle)){
            $timearea=self::getCycleStartTime($entertime,$cycle);
        }

        if(!empty($timearea)){
            //self::saveCycleRcd($opt,$timearea[0],$timearea[1],$etprsId,$fee,$tmpnum);
            $rcd["cate"]="1";
            $numration=$opt["numration"];
            if(!empty($numration)){
                if($numration=="data"){
                    if(empty($tmpnum)){
                        $rcd["cate"]=2;
                    }
                    $num=$tmpnum;
                }else{
                    $num=self::getFeeNum($numration,$etprsId,$entertime,$timearea[1],$opt);
                }
            }
            $price=$opt["price"];
            $style=$opt["feestyle"];

            if($style=="num_price"){
                $total=$price*$num;
            }else if($style=="numration"){
                //指定金额
                $fee=$opt["fee"];
                if(empty($fee)){
                    //没有录入指定金额
                    $rcd["cate"]=2;
                    $toal = 0;
                }else{
                    $total = $fee;
                }
               /* //数量
                if(!empty($numration)){
                    $total=$fee*$num;
                }else{
                    $total=$fee;
                }*/
            }elseif($style=="input"){
                //每户单独输入
                //如果没有指定金额或者指定数量，则status=2
                $rcd["cate"]=2;
                $total = 0;
               /* if(empty($fee)){
                    $rcd["cate"]=2;

                }
                //数量
                if(!empty($numration)){
                    $total=$fee*$num;
                }else{
                    $total=$fee;
                }*/
            }

            //根据天数计算应付多少钱
            $totalday=(strtotime($timearea[1])+86400-strtotime($timearea[0]))/86400;
            $feeday=(strtotime($timearea[1])+86400-strtotime($entertime))/86400;
            $total=$total*$feeday/$totalday;  //总额*（入驻天数/周期总天数）
            if($rcd['cate']==1 && $total ==0){
                return "0";
            }
            $rcd["itemId"]=$opt["itemId"];
            $rcd["etprsId"]=$etprsId;
            $rcd["optId"]=$opt["id"];
            $rcd["iqbtId"]=$opt["iqbtId"];
            $rcd["num"]=$num;
            $rcd["total"]=$total;
            $rcd["price"]=$opt["price"];
            $rcd["fee"]=$opt['fee'];
            $rcd["adduserId"]=session("userId");
            $rcd["addtime"]=time();
            $rcd["startTime"]=strtotime($entertime);
            $rcd["endTime"]=strtotime($timearea[1]);
            $msg=saveData("FeeRcd",$rcd,"添加周期性缴费");
            return strtotime($timearea[1]);
        }else{
            return "";
        }
    }

    //房间的最后非完整周期
    function lasthalfRoomRcd($etprs=array(),$room=array(),$opt=array(),$startTime,$endTime){
        $etprsId=$etprs["id"];
       // $cycle=$opt["cycle"];
        $quittime = $room['endTime'];
        $quittime =date("Y-m-d",$quittime);
        /*  if(!empty($cycle)){
              $timearea=self::getCycleStartTime($quittime,$cycle);
          }*/

      //  $startTime = date("Y-m-d",$startTime);
        $endTime = date("Y-m-d",$endTime);
        $timearea = array($startTime,$endTime);

        if(!empty($timearea)){
            $rcd["cate"]="1";
            $numration=$opt["numration"];
            if(!empty($numration)){
                if($numration=="data"){
                    $rcd["cate"]=2;
                    $num=0;
                }elseif($numration =="area"){
                    $num = $room['totalarea'];
                }else{
                    $num = 1;
                }
            }
            $price=$opt["price"];
            $style=$opt["feestyle"];

            if($style=="num_price"){
                $total=$price*$num;
            }else if($style=="numration"){
                //指定金额
                $fee=$opt["fee"];
                if(empty($fee)){
                    //没有录入指定金额
                    $rcd["cate"]=2;
                    $total = 0;
                }else{
                    $total = $fee;
                }

            }elseif($style=="input"){
                //每户单独输入
                //如果没有指定金额或者指定数量，则status=2
                $rcd["cate"]=2;
                $total = 0;
            }
            //根据天数计算应付多少钱
            $totalday=(strtotime($timearea[1])+86400-strtotime($timearea[0]))/86400;
            $feeday=(strtotime($quittime)+86400-strtotime($timearea[0]))/86400;
            $total=$total*$feeday/$totalday;  //总额*（入驻天数/周期总天数）
            if($rcd['cate']==1 && $total ==0){
                return "0";
            }
            $rcd["itemId"]=$opt["itemId"];
            $rcd["etprsId"]=$etprsId;
            $rcd["optId"]=$opt["id"];
            $rcd["iqbtId"]=$opt["iqbtId"];
            $rcd["num"]=$num;
            $rcd["total"]=$total;
            $rcd["price"]=$opt["price"];
            $rcd["fee"]=$opt['fee'];
            $rcd["adduserId"]=session("userId");
            $rcd["addtime"]=time();
            $rcd["startTime"]=strtotime($timearea[0]);
            $rcd["endTime"]=strtotime($quittime);
            $rcd['roomId'] = $room['roomId'];
            $msg=saveData("FeeRcd",$rcd,"添加周期性缴费");
            return strtotime($timearea[1]);
        }else{
            return "";
        }
    }


    //非完整周期
    function lasthalfRcd($etprs,$opt,$fee=0,$tmpnum=0,$starttime,$quittime)
    {
        $etprsId=$etprs["id"];
        $cycle=$opt["cycle"];
        /*   if(!empty($cycle)){
               $timearea=self::getCycleStartTime($quittime,$cycle);
           }*/
        $starttime = date("Y-m-d",$starttime);
        $quittime = date("Y-m-d",$quittime);
        $endtime=date("Y-m-d",strtotime("+".$cycle." months",strtotime($starttime))-86400);
        $timearea = array($starttime,$endtime);

        if(!empty($timearea)){
            //self::saveCycleRcd($opt,$timearea[0],$timearea[1],$etprsId,$fee,$tmpnum);
            $rcd["cate"]="1";
            $numration=$opt["numration"];
            if(!empty($numration)){
                if($numration=="data"){
                    if(empty($tmpnum)){
                        $rcd["cate"]=2;
                    }
                    $num=$tmpnum;
                }else{
                    $num=self::getFeeNum($numration,$etprsId,$timearea[0],$quittime,$opt);
                }
            }
            $price=$opt["price"];
            $style=$opt["feestyle"];

            if($style=="num_price"){
                $total=$price*$num;
            }else if($style=="numration"){
                //指定金额
                $fee=$opt["fee"];
                if(empty($fee)){
                    //没有录入指定金额
                    $rcd["cate"]=2;
                    $total = 0;
                }else{
                    $total = $fee;
                }

            }elseif($style=="input"){
                //每户单独输入
                //如果没有指定金额或者指定数量，则status=2
                $rcd["cate"]=2;
                $total = 0;

            }

            //根据天数计算应付多少钱
            $totalday=(strtotime($timearea[1])+86400-strtotime($timearea[0]))/86400;
            $feeday=(strtotime($quittime)+86400-strtotime($timearea[0]))/86400;
            $total=$total*$feeday/$totalday;  //总额*（入驻天数/周期总天数）
            if($rcd['cate']==1 && $total ==0){
                return "0";
            }
            $rcd["itemId"]=$opt["itemId"];
            $rcd["etprsId"]=$etprsId;
            $rcd["optId"]=$opt["id"];
            $rcd["iqbtId"]=$opt["iqbtId"];
            $rcd["num"]=$num;
            $rcd["total"]=$total;
            $rcd["price"]=$opt["price"];
            $rcd["fee"]=$opt['fee'];
            $rcd["adduserId"]=session("userId");
            $rcd["addtime"]=time();
            $rcd["startTime"]=strtotime($timearea[0]);
            $rcd["endTime"]=strtotime($quittime);
            $msg=saveData("FeeRcd",$rcd,"添加周期性缴费");
            return strtotime($timearea[1]);
        }else{
            return "";
        }
    }
    //入驻后第一个周期、最后一个周期起止时间
    function getCycleStartTime($starttime='',$cycle='')
    {
        $months=[];
        if(!empty($cycle)){
            switch($cycle){
                case "1";
                    $months=[1,2,3,4,5,6,7,8,9,10,11,12];
                    break;
                case "2":
                    $months=[1,3,5,7,9,11];
                    break;
                case "3":
                    $months=[1,4,7,10];
                    break;
                case "4":
                    $months=[1,5,9];
                    break;
                case "6":
                    $months=[1,7];
                    break;
                case "12":
                    $months=[1];
                    break;
            }
           // var_dump($starttime);exit();

            list($y,$m,$d)=explode("-",$starttime);
            for ($i = 0; $i < count($months); $i++){
                $month=$months[$i];
                if($i==(count($months)-1)){
                    //如果循环遍历缴费周期的每个起点，一直到最后一个，还没遍历到需要的时间，则计算最后一个周期起点。
                    //年份减1  月为初始月
                    $preTime=$y."-".$months[$i]."-1";
                    $cyclem=$months[0];
                    return [$preTime,date("Y-m-d",strtotime("-1 day",strtotime(($y+1)."-".$cyclem."-1")))];

                }else{
                    $nextmonth=$months[$i+1];
                    if($m>=$month&&$m<$nextmonth){
                        if($d==1){
                            //入驻时间为周期开始第一天 不处理
                        }else{
                            //不到一个周期
                            return [$y."-".$month."-1",date("Y-m-d",strtotime("-1 day",strtotime($y."-".$nextmonth."-1")))];
                        }
                    }
                }
            }
        }
        return array();
    }

    function feecfg(){
        $data=array();
        $msg=findById("feeCfg",array("iqbtId"=>session("iqbtId")),"id,cycletype");
        if(!empty($msg["data"])){
            $data=$msg["data"];
        }
      //  print_r($data);exit();
        return view("",array('data'=>$data));
    }

    function getFeecfg()
    {

        $con=array("level"=>2,'code'=>array("like",'1030%'));
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=getDataList("SysDict",$con,"code,name,id");
        $fees=array();
        if(!empty($msg["data"])){
            $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id','left']];
            $cmsg=getDataList("FeeItemCfg",array("a.iqbtId"=>session("iqbtId")),"a.*,b.name as itemName,c.name as optName","a.id",$join);
            $fees=$msg["data"];
            for ($i = 0; $i < count($fees); $i++){
                $fees[$i]["cfgs"]=array();
                if(!empty($cmsg["data"])){
                    $cfgs=$cmsg["data"];
                    foreach ($cfgs as $c){
                        if($c["feetype"]==$fees[$i]["code"]){
                            $fees[$i]["cfgs"][]=$c;
                        }
                    }
                }
            }
        }
        return $fees;
    }

    //设置缴费项目
    function addFeeCfg($feetype='')
    {
        return view("",array("feetype"=>$feetype));
    }


    function saveFeeCfg()
    {
        $postData=input("request.");
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        if(empty($postData['optId'])){
            return array('code'=>0,'msg'=>'缴费标准不能为空');
        }
        if(!empty($postData['itemId'])){
            $itemMsg = findByid('FeeItemCfg',array('iqbtId'=>session('iqbtId'),'feetype'=>$postData['feetype'],'itemid'=>$postData['itemId']),'*');
            if($itemMsg['code']==1 &&!empty($itemMsg['data'])){
                return array('code'=>0,'msg'=>'已经添加了当前项目的标准，不能重复添加');
            }
        }else{
            return array('code'=>0,'msg'=>'缴费项目必须选择');
        }

        $msg=saveData("FeeItemCfg",$postData,"添加缴费项目");
        return $msg;
    }

    function saveBaseFeeCfg()
    {
        $postData=input("request.");
     //   var_dump($postData);exit();
        $postData["adduserId"]=session("userId");
        $postData["iqbtId"]=session("iqbtId");
        $postData["addtime"]=time();
        $msg=saveData("FeeCfg",$postData,"添加缴费项目");
        return $msg;
    }


    function removeFeeCfg($id)
    {
        return deleteByCon("FeeItemCfg",array("id"=>$id),"");
    }

    //以下是与退费相关的函数
    //企业端获取退费记录
    function getEtprsQuit($etprsId=''){
        if(empty($etprsId)){
            $etprsId = session('etprsId');
        }
        $con=array('a.iqbtId'=>session("iqbtId"),'a.etprsId'=>$etprsId);

        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"]];
        $msg=getDataList("FeeQuitRcd",$con,"a.*,b.name as itemName,c.name as optName,c.feestyle,c.id as optId","a.id desc",$join);
        $data=array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            //查询每个项目的最新一期缴费记录
            foreach($data as $key=>$value) {
                if(!empty($value['quituserId'])){
                    $data[$key]['quituserId'] = getField('user',array('id'=>$value['quituserId']),'realname');
                }else{
                    $data[$key]['quituserId'] ='';
                }

                if(!empty($value['quittime'])){
                    $data[$key]['quittime'] = date("Y-m-d",$value["quittime"]);
                }else{
                    $data[$key]['quittime'] ='';
                }
            }
        }

        return $data;

    }


    function getquitRcdlist($status=0,$name='',$feestyle='',$quittype=0,$time_start='',$time_end='')
    {
        $con=array('a.iqbtId'=>session("iqbtId"));
        if($status ==0){
            $con['a.status'] = array('in',array('0','1'));
        }else{
            $con['a.status'] = $status;
        }

        if(!empty($name)){
            $con["b.name|d.name|c.name"]=array("like","%".$name."%");
        }
        if(!empty($feestyle)){
            $con["c.feestyle"]=$feestyle;
        }
        // $settletype = input('settletype',0);
        //  $time_start = input('time_start','');
        //  $time_end = input('time_end','');
        if(!empty($quittype)){
            $con['a.quittype'] = $quittype;
        }

        if(!empty($time_start) && !empty($time_end)){
            $con['a.quittime'][] = array('gt',strtotime($time_start));
            $con['a.quittime'][] = array('lt',strtotime($time_end));
        }else{
            if(!empty($time_start)){
                $con['a.quittime'] = array('gt',strtotime($time_start));
            }
            if(!empty($time_end)){
                $con['a.quittime'] = array('lt',strtotime($time_end));
            }
        }

        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"],['enterprise d','a.etprsId=d.id',"left"]];
        $msg=getDataList("FeeQuitRcd",$con,"a.*,b.name as itemName,c.name as optName,c.feestyle,d.name as etprsname,c.id as optId","a.id desc",$join);
        $data=array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            //查询每个项目的最新一期缴费记录
            foreach($data as $key=>$value) {
                $con = array("itemId" => $value['itemId'], "etprsId" => $value['etprsId'], "iqbtId" => session("iqbtId"),'status'=>1);
                if(!empty($value['roomId'])){
                    $con['roomId'] = $value['roomId'];
                }
                $lastOptMsg = findById("feeRcd", $con, "startTime,endTime,id,total", array(), '0', "endTime desc");
                if (!empty($lastOptMsg["data"])){
                    $datalog = $lastOptMsg['data'];

                    if(!empty($datalog["startTime"])){
                        $data[$key]["startTime"]=date("Y-m-d",$datalog["startTime"]);
                    }else{
                        $data[$key]["startTime"]="";
                    }
                    if(!empty($datalog["endTime"])){
                        $data[$key]["endTime"]=date("Y-m-d",$datalog["endTime"]);
                    }else{
                        $data[$key]["endTime"]="";
                    }
                    $data[$key]['total_fee'] = $datalog['total'];
                }else {
                    //没有缴费记录
                    $data[$key]["startTime"] = '';
                    $data[$key]["endTime"] = '';
                    $data[$key]['total_fee'] = 0.00;
                }
                if(!empty($value['quituserId'])){
                    $data[$key]['quituserId'] = getField('user',array('id'=>$value['quituserId']),'realname');
                }else{
                    $data[$key]['quituserId'] ='';
                }
                if(!empty($value['quittime'])){
                    $data[$key]['quittime'] = date("Y-m-d",$value["quittime"]);
                }else{
                    $data[$key]['quittime'] ='';
                }
            }
        }

        return $data;
    }

    //导出退费数据
    function quitExport(){
        $ids  = input('id');
        $type = input('type','1');//1、待退费  2、已退费
        $ids = explode(",",$ids);//转成数组
        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id',"left"],['enterprise d','a.etprsId=d.id',"left"]];
        $msg=getDataList("FeeQuitRcd",array('a.id'=>array('in',$ids)),"a.*,b.name as itemName,c.name as optName,c.feestyle,d.name as etprsname,c.id as optId","a.id desc",$join);
        $data=array();

        $expData = array();
        if(!empty($msg["data"])){
            $data=$msg["data"];
            //查询每个项目的最新一期缴费记录
            foreach($data as $key=>$value) {
                $expData[$key] = array(
                    'itemName'=>$value['itemName'],
                    'etprsname'=>$value['etprsname'],
                    'optName'=>$value['optName']
                );
                if ($value['feestyle'] == 'num_price') {
                    $expData[$key]['feestyle'] = "单价×数量";
                } else if ($value['feestyle'] == "input") {
                    $expData[$key]['feestyle'] = "每户单独输入";
                } else if ($value['feestyle'] == "numration") {
                    $expData[$key]['feestyle'] = "指定金额";
                }else{
                    $expData[$key]['feestyle'] = "未知方式";
                }
                if($type ==1){
                    $con = array("itemId" => $value['itemId'], "etprsId" => $value['etprsId'], "iqbtId" => session("iqbtId"),'status'=>1);
                    if(!empty($value['roomId'])){
                        $con['roomId'] = $value['roomId'];
                    }
                    $lastOptMsg = findById("feeRcd", $con, "startTime,endTime,id,total", array(), '0', "endTime desc");
                    if (!empty($lastOptMsg["data"])){
                        $datalog = $lastOptMsg['data'];

                        if(!empty($datalog["startTime"])){
                            $expData[$key]["startTime"]=date("Y-m-d",$datalog["startTime"]);
                        }else{
                            $expData[$key]["startTime"]="";
                        }
                        if(!empty($datalog["endTime"])){
                            $expData[$key]["endTime"]=date("Y-m-d",$datalog["endTime"]);
                        }else{
                            $expData[$key]["endTime"]="";
                        }
                        $expData[$key]['total_fee'] = $datalog['total'];
                    }else {
                        //没有缴费记录
                        $expData[$key]["startTime"] = '';
                        $expData[$key]["endTime"] = '';
                        $expData[$key]['total_fee'] = 0.00;
                    }
                }

                //退费金额
                $expData[$key]['total'] = $value['total'];
                if($type ==2){
                    //退费方式
                    if(!empty($value['quittype'])){
                        if ($value['quittype'] == '1') {
                            $expData[$key]['quittype'] = "现金";
                        } else if ($value['quittype'] == "2") {
                            $expData[$key]['quittype'] = "刷卡";
                        } else if ($value['quittype'] == "3") {
                            $expData[$key]['quittype'] = "网络转账";
                        }else{
                            $expData[$key]['quittype'] = "";
                        }
                    }else{
                        $expData[$key]['quittype'] ='';
                    }
                    if(!empty($value['quittime'])){
                        $expData[$key]['quittime'] = date("Y-m-d",$value["quittime"]);
                    }else{
                        $expData[$key]['quittime'] ='';
                    }
                    if(!empty($value['quituserId'])){
                        $expData[$key]['quituserId'] = getField('user',array('id'=>$value['quituserId']),'realname');
                    }else{
                        $expData[$key]['quituserId'] ='';
                    }
                }

            }
        }
      //  header("Content-Type: text/html;charset=utf-8");
       // print_r($expData);exit();
        if($type ==1){
            $filename = "待退费记录";
            $header = array('项目名称','缴费企业','缴费标准','收费方式','收费开始时间','收费结束时间','收费金额','退费金额');
        }else{
            $filename = '已退费记录';
            $header = array('项目名称','缴费企业','缴费标准','收费方式','退费金额','退费方式','退费时间','退费操作人');
        }
        vendor("PHPExcel");
        vendor("PHPExcel.Writer.Excel5");
        vendor("PHPExcel.IOFactory");
        getExcel($filename,$header,$expData);

    }
    //填写退费金额 缴费记录ID
    function editquitFeeRcd($id){
        if(!empty($id)){
            $feeMsg = findById('feeQuitRcd',array('id'=>$id),'*');
            if($feeMsg['code']==1 && !empty($feeMsg['data'])){
                $feeInfo = $feeMsg['data'];
            }else{
                $feeInfo = array();
            }
        }else{
            return array('code'=>0,'msg'=>'参数错误');
        }
        return view('',array('data'=>$feeInfo));
    }

    //填写退费金额和备注，
    function savequitFeeRcdLog(){
        $postData = input('request.');
        if(floatval($postData['fee_final'])<=0){
            return array('code'=>0,'msg'=>'请输入正确的金额');
        }
        if(!empty($postData['id'])){
            //把修改后的价格保存到缴费记录表里
            $msg = saveDataByCon('feeQuitRcd',array('total'=>$postData['fee_final'],'status'=>1),array('id'=>$postData['id']),'退费金额');
            if($msg['code']==1){
                //添加修改记录
                $data = array(
                    'fee_rcd_log'=>$postData['id'],
                    'iqbtId'=>$postData['iqbtId'],
                    'etprsId'=>$postData['etprsId'],
                    'optId'=>  $postData['optId'],
                    'itemId'=>$postData['itemId'],
                    'roomId'=>$postData['roomId'],
                    'fee_final' =>$postData['fee_final'],
                    'desc' =>$postData['desc'],
                    'addtime'=>time(),
                    'adduserId'=>session('userId'),
                    'is_quit'=>1
                );
                saveData('feeRcdLog',$data,'添加修改记录');
                return array('code'=>1,'msg'=>'修改成功');
            }
        }else{
            return array('code'=>0,'msg'=>'参数错误');
        }
    }
    //退费
    function setquitfee($ids="",$types)
    {
        if(empty($ids)){
            return array("code"=>0,"msg"=>"至少选择一条记录");
        }
        $data = array(
            'status'=>2,
            'quituserId'=>session('userId'),
            'quittime'=>time(),
            'quittype'=>$types
        );
        $msg=saveDataByCon("feeQuitRcd",$data,array("id"=>array("in",$ids)));
        return $msg;
    }

}