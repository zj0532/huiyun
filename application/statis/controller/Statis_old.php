<?php
namespace app\statis\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;

class Statis extends Common{
    function index()
    {
        return view();
    }

    function initChartData($dict,$data,$yaxis)
    {
        $colors=['#1ab394','#EF5352','#1c84c6','#23c6c8','#F8AC59','#F81C59','#18AC59','#F8A159','#F8ACF9','#F1AC59','#2767c6','#17c3e5'];
        $rltcolor=array();
        $rltxaxis=array();
        $legend=array();
        for ($i = 0; $i < count($yaxis); $i++) {
            $legend[]=$yaxis[$i]["name"];
            $rltcolor[$i]=$colors[$i%count($colors)];
            $yaxis[$i]["data"]=array();
        }
        foreach ($data as $k => $v) {
            if(empty($dict)){
                $rltxaxis[]=$k;
            }else{
                $rltxaxis[]=$dict[$k];
            }
            if(is_array($v)){
                for ($i = 0; $i < count($yaxis); $i++) {
                    $yaxis[$i]["data"][]=$v[$i];
                }
            }else{
                $yaxis[0]["data"][]=$v;
            }
        }
        return array('color'=>$rltcolor,'legend'=>$legend,'xaxis'=>$rltxaxis,'yaxis'=>$yaxis);
    }
    function etprsb()
    {
        $time=time();
        return view("",array("time"=>$time));
    }
    function etprsStatis($time="")
    {
        if(empty($time)){
             $time=date("Y-m-d",time()+86400);
        }else{
            $time=date("Y-m-d",strtotime($time)+86400);
        }
        $iqbtId=session("iqbtId");
        $data=array("apl"=>0,"ing"=>0,"gradt"=>0,"quid"=>0);
        if(!empty($time)){
            $time=strtotime($time);
            $msg=getDataList("enterprise",array("iqbtId"=>$iqbtId,'status'=>array("in",['1001020','1001021','1001011','1001012','1001013','1001014','1001015','1001016','1001017'])),"status,id,entertime,addtime,quittime");
            //var_dump($msg["data"]);
            if(!empty($msg["data"])){
                foreach ($msg["data"] as $etprs) {
                    $entertime=$etprs["entertime"];
                    $apltime=$etprs["addtime"];
                    $quittime=$etprs["quittime"];
                    $status=$etprs["status"];
                    //已申请 && （申请时间 < 选择时间 &&(企业在选择时间未入驻 || 企业到目前仍未入驻) ）
                    if(!empty($apltime)&&($apltime<$time&&($time<$entertime||(empty($entertime)&&($status=='1001011'||$status=='1001012'||$status=='1001013'||$status=='1001014'||$status=='1001015'))))){
                        $data["apl"]=$data["apl"]+1;
                    }
                    if(!empty($entertime)&&($entertime<$time&&($time<$quittime||(empty($quittime)&&$status=='1001016')))){
                        $data["ing"]=$data["ing"]+1;
                    }
                    if(!empty($quittime)&&$quittime<$time){
                        if($status=="1001021"||$status=="1001020"){
                            $data["quid"]=$data["quid"]+1;
                        }
                        if($status=="1001017"){
                            $data["gradt"]=$data["gradt"]+1;
                        }
                    }
                }
            }
        }

        $dict=array("apl"=>'申请中','ing'=>'已入驻','gradt'=>'已毕业','quid'=>'已退出');
        $yaxis=array(['name'=>'数量','type'=>'bar']);
        $result=self::initChartData($dict,$data,$yaxis);
        return $result;
    }

    function etprs()
    {
        $start=strtotime("-1 month",time());
        $end=time();
        return view("",array("start"=>$start,"end"=>$end));
    }
    function enterStatis($start="",$end="")
    {
        $iqbtId=session("iqbtId");
        $data=array("apl"=>0,"ing"=>0,"gradt"=>0,"quid"=>0);
        if(!empty($start)||!empty($end)){
            $starttime=strtotime($start);
            $endtime=strtotime($end)+86400;
            $msg=getDataList("enterprise",array("iqbtId"=>$iqbtId,'status'=>array("in",['1001020','1001021','1001011','1001012','1001013','1001014','1001015','1001016','1001017'])),"status,id,entertime,addtime,quittime");
            if(!empty($msg["data"])){
                foreach ($msg["data"] as $etprs) {
                    $entertime=$etprs["entertime"];
                    $apltime=$etprs["addtime"];
                    $quittime=$etprs["quittime"];
                    $status=$etprs["status"];
                    if(!empty($apltime)&&($apltime>$starttime&&$apltime<$endtime)){
                        $aplArr = array('1001011','1001012','1001013','1001014','1001015');
                        if(in_array($status,$aplArr)){
                            $data["apl"]=$data["apl"]+1;
                        }
                    }
                    if(!empty($apltime)&&($entertime>$starttime&&$entertime<$endtime)){
                        if($status=="1001016"){
                            $data["ing"]=$data["ing"]+1;

                        }
                    }
                    if($quittime>$starttime&&$quittime<$endtime){
                        if($status=="1001020"||$status=="1001021"){
                            $data["quid"]=$data["quid"]+1;
                        }
                        if($status=="1001017"){
                            $data["gradt"]=$data["gradt"]+1;
                        }
                    }
                }
            }
        }
        $dict=array("apl"=>'申请中','ing'=>'已入驻','gradt'=>'已毕业','quid'=>'已退出');
        $yaxis=array(['name'=>'数量','type'=>'bar']);
        $result=self::initChartData($dict,$data,$yaxis);
        return $result;
    }

    function feeStatis()
    {
        $iqbtId=session("iqbtId");
        $months=array();
        $data=array();
        $nowmonth=strtotime("+1 month",strtotime(date("Y-m",time())));
        $first=date("Y-m",strtotime("-1 year",$nowmonth));
        for ($i = 0; $i <12; $i++) {
            $months[]=date("Y-m",strtotime("+".$i." month",strtotime($first)));
            $data[date("Y-m",strtotime("+".$i." month",strtotime($first)))]=[0,0];
        }

        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $msg=getDataList("feeRcd",array("a.iqbtId"=>$iqbtId,"a.settletime"=>array("between",[strtotime($first),strtotime(date("Y-m-d",time()))]),'a.status'=>1),"a.settletime,a.total,b.types","a.settletime",$join);

        if(!empty($msg["data"])){
            foreach ($msg["data"] as $rcd) {
                $month=date("Y-m",$rcd["settletime"]);
                if(!isset($data[$month][$rcd["types"]])){
                    $data[$month][$rcd["types"]]=0;
                }
                $data[$month][$rcd["types"]]+=$rcd["total"];
            }
        }
        //退费，从fee_quit_rcd表里查询数据
        $quitMsg = getDataList('feeQuitRcd',array('a.iqbtId'=>$iqbtId,'a.status'=>2,'a.quittime'=>array('between',[strtotime($first),strtotime(date("Y-m-d",time()))])),'a.quittime,a.total','a.quittime');
        if(!empty($quitMsg['data'])){
            foreach($quitMsg['data'] as $quitRcd){
                $month = date("Y-m",$quitRcd['quittime']);
                $data[$month]['1'] += $quitRcd['total'];
            }
        }
        $yaxis=array(['name'=>'缴费','type'=>'line'],['name'=>'退费','type'=>'line']);
        $result=self::initChartData(array(),$data,$yaxis);
        return $result;
    }

    function roomStatis()
    {
        $iqbtId=session("iqbtId");
        $con=array('iqbtId'=>$iqbtId,'type'=>1);
        $msg=getDataList("estateRoom",$con,"id,totalarea,status,type");
        $data=[['name'=>'空置房','value'=>'0'],['name'=>'在用房','value'=>'0']];
        $area=[['name'=>'空闲面积','value'=>'0'],['name'=>'已用面积','value'=>'0']];
        if(!empty($msg["data"])){
            foreach ($msg["data"] as $room){
                if($room["status"]=='0'){
                    $data[0]["value"]+=1;
                    $area[0]["value"]+=$room["totalarea"];
                }else{
                    $data[1]["value"]+=1;
                    $area[1]["value"]+=$room["totalarea"];
                }
            }
        }
        return array('room'=>$data,'area'=>$area);
    }

    //工位统计
    function deskStatis(){
        $iqbtId=session("iqbtId");
        $con=array('iqbtId'=>$iqbtId,'type'=>0);
        $msg=getDataList("estateRoom",$con,"id,totalarea,status,type");
        $data=[['name'=>'空置工位','value'=>'0'],['name'=>'已用工位','value'=>'0']];
        if(!empty($msg["data"])){
            foreach ($msg["data"] as $room){
                if($room["status"]=='0'){
                    $data[0]["value"]+=1;
                }else{
                    $data[1]["value"]+=1;
                }
            }
        }
        return array('desk'=>$data);
    }

    function getIqbtUsersStatis()
    {
        $etprsIqbtIds=getFieldArrry("incubator",array("etprsIqbtId"=>session("etprsIqbtId")),"id");
        $con=array('iqbtId'=>array("in",$etprsIqbtIds),'status'=>'1001016');
        $msg=findById("enterprise",$con,"sum(thousand) as thousand,sum(overseas) as overseas,sum(doctor) as doctor,sum(junior) as junior,sum(student) as student");
        $data=[['name'=>'千人计划','value'=>'0'],['name'=>'留学人员','value'=>'0'],['name'=>'博士','value'=>'0'],['name'=>'大专以上','value'=>'0'],['name'=>'应届大学生','value'=>'0']];
        $area=[['name'=>'其它','value'=>'0'],['name'=>'应届大学生','value'=>'0']];
        if(!empty($msg["data"])){
            $data=[['name'=>'千人计划','value'=>$msg["data"]["thousand"]],['name'=>'留学人员','value'=>$msg["data"]["overseas"]],['name'=>'博士','value'=>$msg["data"]["doctor"]],['name'=>'大专以上','value'=>$msg["data"]["junior"]],['name'=>'应届大学生','value'=>$msg["data"]["student"]]];
            $area=[['name'=>'其它','value'=>$msg["data"]["thousand"]+$msg["data"]["overseas"]+$msg["data"]["doctor"]+$msg["data"]["junior"]],['name'=>'应届大学生','value'=>$msg["data"]["student"]]];
        }
        return array('userdata'=>$data,'studata'=>$area);
    }

    function etprsiqbtStatis($etprsId=0)
    {
        $iqbtId=session("iqbtId");
        $data=array();
        $con=array("iqbtId"=>$iqbtId);
        if(!empty($etprsId)){
            $con["etprsId"]=$etprsId;
        }
        $msg=getDataList("statement",$con,"rdinput,month,income,tax","years asc,months asc");
        if(!empty($msg["data"])){
            foreach ($msg["data"] as $rpt){
                $strmonth=$rpt["month"];
                if(!isset($data[$strmonth])){
                    $data[$strmonth]=[0,0,0];
                }
                $data[$strmonth][0]=$data[$strmonth][0]+$rpt["rdinput"];
                $data[$strmonth][1]=$data[$strmonth][1]+$rpt["income"];
                $data[$strmonth][2]=$data[$strmonth][2]+$rpt["tax"];
            }
        }
        $yaxis=array(['name'=>'研发投入','type'=>'line'],['name'=>'收入','type'=>'line'],['name'=>'缴税','type'=>'line']);
        $result=self::initChartData(array(),$data,$yaxis);
        return $result;
    }

    function getIqbtEtprsStatis()
    {
        $param=input("request.");
        if(!isset($param["showcate"])){
            $param["showcate"]="totaldata";
        }

        if(!isset($param["iqbts"])||empty($param["iqbts"])){
            $usercate=session("user.userCate");
            if($usercate=="1011004"){
                //区域用户
                $districtId=session("user.districtId");
                if(!empty($districtId)){
                    $param["iqbts"]=getFieldArrry("incubator",array("districtId"=>array('like',$districtId."%")));
                }else{
                    return array("code"=>0,"msg"=>"当前区域下没有对应孵化器数据");
                }
            }elseif($usercate=="1011001"){
                //超级管理员
                $param["iqbts"]=[session("iqbtId")];
            }
        }
        if(!empty($param["starttime"])&&!empty($param["endtime"])){
            if(strtotime($param["endtime"])<strtotime($param["starttime"])){
                return array("code"=>0,"msg"=>"开始时间必须小于结束时间");
            }
        }
        //初始化日期------开始
        $date=array();
        if($param["statistype"]=='total'){
            $title="";
            if(empty($param["starttime"])){
                $title.="最早—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—现在";
            }else{
                $title.="—".$param["endtime"];
            }
            $date[]=$title;
        }else{
            $enddate=date("Y-m-d",time());
            if(!empty($param["endtime"])){
                $enddate=$param["endtime"];
            }
            if($param["statistype"]=="months"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-11 months",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime(date("Y-m",strtotime($startdate)));
                $enddatespan=strtotime(date("Y-m",strtotime($enddate)));
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m",$startdatespan);
                    $startdatespan=strtotime("+1 months",$startdatespan);
                }


            }else if($param["statistype"]=="day"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-10 day",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime($startdate);
                $enddatespan=strtotime($enddate);
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m-d",$startdatespan);
                    $startdatespan=strtotime("+1 day",$startdatespan);
                }
            }
        }
        //初始化日期------结束
        //初始化查询条件 ---开始

        $statuscode=array();
        if(in_array('apl',$param["status"])){
            $statuscode=array_merge($statuscode, array('1001011','1001012','1001013','1001014','1001015'));
        }
        if(in_array('ing',$param["status"])){
            $statuscode[]='1001016';
        }
        if(in_array('gradt',$param["status"])){
            $statuscode[]='1001017';
        }
        $con=array("iqbtId"=>array("in",$param["iqbts"]),"status"=>array("in",$statuscode));
        $msg=getDataList("enterprise",$con,"id,addtime,quittime,entertime,status,iqbtId");
        /*$msg=getDataList2("enterprise",$con,"id,addtime,quittime,entertime,status,iqbtId");
        var_dump($msg["data"]);*/

        $yaxis=array();
        $empty=array();
        $keys=array();

        foreach ($param["status"] as $status) {
            if($param["showtype"]=='bar'){
                $stockarr=array('stack'=>$status);
            }else{
                $stockarr=array();
            }

            if($param["showcate"]=="totaldata"){
                $keys[]=$status;
                $yaxis[]=array_merge($stockarr,['name'=>$status,'type'=>$param["showtype"],'stackflag'=>$status,'barWidth'=>20]);
                $empty[]=0;
            }else{
                foreach ($param["iqbts"] as $iqbtId) {
                    $keys[]=$status.'-'.$iqbtId;
                    $yaxis[]=array_merge($stockarr,['name'=>$status.'-'.$iqbtId,'type'=>$param["showtype"],'stackflag'=>$status,'barWidth'=>20]);
                    $empty[]=0;
                }
            }

        }
        $data=array();
        //array_keys()
        for ($i = 0; $i < count($date); $i++) {
            if(!isset($data[$date[$i]])){
                $data[$date[$i]]=$empty;
            }
            $min=0;
            $max=0;
            if($param["statistype"]=='total'){
                if(!empty($param["starttime"])){
                    $min=strtotime($param["starttime"]);
                }
                if(!empty($param["endtime"])){
                    $max=strtotime("+1 day",strtotime($param["endtime"]));
                }else{
                    $max=time();
                }
            }else{
                $min=strtotime($date[$i]);
                $max=strtotime("+1 ".$param["statistype"],$min);
            }
            if(!empty($msg["data"])){
                foreach ($msg["data"] as $etprs) {
                    $addtime=$etprs["addtime"];
                    $quittime=$etprs["quittime"];
                    $entertime=$etprs["entertime"];
                    if($param["showcate"]=="totaldata"){
                        //综合数据展示
                        foreach ($param["status"] as $status) {
                            if($param["statistype"]=='total'){
                                if($status=='apl'){
                                    if($etprs["status"]=='1001011'||$etprs["status"]=='1001012'||$etprs["status"]=='1001013'||$etprs["status"]=='1001014'||$etprs["status"]=='1001015'){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                                if($status=='ing'){
                                    if($etprs["status"]=='1001016'){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                                if($status=='gradt'){
                                    if($etprs["status"]=='1001017'){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                            }else{
                                if($status=='apl'){
                                    if($addtime<$max&&($entertime>=$min||($etprs["status"]=='1001011'||$etprs["status"]=='1001012'||$etprs["status"]=='1001013'||$etprs["status"]=='1001014'||$etprs["status"]=='1001015'))){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                                if($status=='ing'){
                                    if($addtime<$max&&($quittime>=$min||$etprs["status"]=='1001016')){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                                if($status=='gradt'){
                                    if($quittime<$min&&$etprs["status"]=='1001017'){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                            }
                        }
                    }else{
                        //按孵化器展示
                        for ($j = 0; $j < count($param["iqbts"]); $j++) {
                            if($etprs["iqbtId"]==$param["iqbts"][$j]){
                                foreach ($param["status"] as $status) {
                                    if($param["statistype"]=='total'){
                                        if($status=='apl'){
                                            if($etprs["status"]=='1001011'||$etprs["status"]=='1001012'||$etprs["status"]=='1001013'||$etprs["status"]=='1001014'||$etprs["status"]=='1001015'){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                        if($status=='ing'){
                                            if($etprs["status"]=='1001016'){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                        if($status=='gradt'){
                                            if($etprs["status"]=='1001017'){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                    }else{
                                        if($status=='apl'){
                                            if($addtime<$max&&($entertime>=$min||($etprs["status"]=='1001011'||$etprs["status"]=='1001012'||$etprs["status"]=='1001013'||$etprs["status"]=='1001014'||$etprs["status"]=='1001015'))){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                        if($status=='ing'){
                                            if($addtime<$max&&($quittime>=$min||$etprs["status"]=='1001016')){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                        if($status=='gradt'){
                                            if($quittime<$min&&$etprs["status"]=='1001017'){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }

        $result=self::initChartData(array(),$data,$yaxis);

        $iqbts=array();
        $statuses=array('apl'=>'申请中','ing'=>'孵化中','gradt'=>'毕业');
        $iqbtsmsg=gethashmap("incubator",array("id"=>array('in',$param["iqbts"])),"id,name");
        if(!empty($iqbtsmsg["data"])){
            $iqbts=$iqbtsmsg["data"];
        }
        for ($i = 0; $i < count($result["legend"]); $i++) {
            $result["legend"][$i]=self::legendFmt($statuses,$result["legend"][$i],$iqbts);
        }
        for ($i = 0; $i < count($result["yaxis"]); $i++) {
            $result["yaxis"][$i]["name"]=self::legendFmt($statuses,$result["yaxis"][$i]["name"],$iqbts);
        }
        return $result;
    }


    function getIqbtDataStatis()
    {
        $param=input("request.");
        if(!isset($param["showcate"])){
            $param["showcate"]="totaldata";
        }
        if(!isset($param["iqbts"])||empty($param["iqbts"])){
            $usercate=session("user.userCate");
            if($usercate=="1011004"){
                //区域用户
                $districtId=session("user.districtId");
                if(!empty($districtId)){
                    $param["iqbts"]=getFieldArrry("incubator",array("districtId"=>array('like',$districtId."%")));
                }else{
                    return array("code"=>0,"msg"=>"当前区域下没有对应孵化器数据");
                }
            }elseif($usercate=="1011001"){
                //超级管理员
                $param["iqbts"]=[session("iqbtId")];
            }
        }
        if(!empty($param["starttime"])&&!empty($param["endtime"])){
            if(strtotime($param["endtime"])<=strtotime($param["starttime"])){
                return array("code"=>0,"msg"=>"开始时间必须小于结束时间");
            }
        }
        //初始化日期------开始
        $date=array();
        if($param["statistype"]=='total'){
            $title="";
            if(empty($param["starttime"])){
                $title.="最早—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—现在";
            }else{
                $title.="—".$param["endtime"];
            }
            $date[]=$title;
        }else{
            $enddate=date("Y-m-d",time());
            if(!empty($param["endtime"])){
                $enddate=$param["endtime"];
            }
            if($param["statistype"]=="months"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-11 months",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime(date("Y-m",strtotime($startdate)));
                $enddatespan=strtotime(date("Y-m",strtotime($enddate)));
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m",$startdatespan);
                    $startdatespan=strtotime("+1 months",$startdatespan);
                }


            }else if($param["statistype"]=="day"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-10 day",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime($startdate);
                $enddatespan=strtotime($enddate);
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m-d",$startdatespan);
                    $startdatespan=strtotime("+1 day",$startdatespan);
                }
            }
        }
        //初始化日期------结束
        //初始化查询条件 ---开始

        $con=array("a.iqbtId"=>array("in",$param["iqbts"]));
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=getDataList("statement",$con,"a.*","a.month",$join);
        /*$msg=getDataList2("statement",$con,"a.*","a.month",$join);
        var_dump($msg["data"]);*/
        $yaxis=array();
        $empty=array();
        $keys=array();
        foreach ($param["idx"] as $idx) {
            if($param["showtype"]=='bar'){
                $stockarr=array('stack'=>$idx);
            }else{
                $stockarr=array();
            }
            if($idx=="total"){
                $yarr=array('yAxisIndex' => 1);
            }else{
                $yarr=array();
            }
            if($param["showcate"]=="totaldata"){
                $keys[]=$idx;
                $yaxis[]=array_merge($yarr,$stockarr,['name'=>$idx,'type'=>$param["showtype"],'stackflag'=>$idx,'barWidth'=>20]);
                $empty[]=0;
            }else{
                foreach ($param["iqbts"] as $iqbtId) {
                    $keys[]=$idx.'-'.$iqbtId;

                    $yaxis[]=array_merge($yarr, ['name'=>$idx.'-'.$iqbtId,'type'=>$param["showtype"],'stackflag'=>$idx,'barWidth'=>20],$stockarr);
                    $empty[]=0;
                }
            }
        }
        $data=array();
        //array_keys()
        for ($i = 0; $i < count($date); $i++) {
            if(!isset($data[$date[$i]])){
                $data[$date[$i]]=$empty;
            }

            $min=0;
            $max=0;
            if($param["statistype"]=='total'){
                if(!empty($param["starttime"])){
                    $min=strtotime($param["starttime"]);
                }
                if(!empty($param["endtime"])){
                    $max=strtotime("+1 day",strtotime($param["endtime"]));
                }else{
                    $max=time();
                }
            }else{
                $min=strtotime($date[$i]);
                $max=strtotime("+1 ".$param["statistype"],$min);
            }
            if(!empty($msg["data"])){
                foreach ($msg["data"] as $report) {
                    $month=strtotime($report["month"]);
                    if($param["showcate"]=="totaldata"){
                        //综合数据展示
                        if ($month < $max && $month >= $min) {
                            foreach ($param["idx"] as $idx) {
                                //研发投入
                                if ($idx == 'rdinput') {
                                    $data[$date[$i]][array_keys($keys, $idx)[0]] = $data[$date[$i]][array_keys($keys, $idx)[0]] + $report["rdinput"];
                                }
                                //营业额
                                if ($idx == 'income') {
                                    $data[$date[$i]][array_keys($keys, $idx)[0]] = $data[$date[$i]][array_keys($keys, $idx)[0]] + $report["income"];
                                }
                                //税收
                                if ($idx == 'tax') {
                                    $data[$date[$i]][array_keys($keys, $idx)[0]] = $data[$date[$i]][array_keys($keys, $idx)[0]] + $report["tax"];
                                }
                                //技术合同成交额
                                if ($idx == 'tct') {
                                    $data[$date[$i]][array_keys($keys, $idx)[0]] = $data[$date[$i]][array_keys($keys, $idx)[0]] + $report["tct"];
                                }
                                //带动就业
                                if ($idx == 'total') {
                                    $data[$date[$i]][array_keys($keys, $idx)[0]] = $data[$date[$i]][array_keys($keys, $idx)[0]] + $report["total"];
                                }

                            }
                        }
                    }else{
                        for ($j = 0; $j < count($param["iqbts"]); $j++) {
                            if ($report["iqbtId"] == $param["iqbts"][$j] && $month < $max && $month >= $min) {
                                foreach ($param["idx"] as $idx) {
                                    //研发投入
                                    if ($idx == 'rdinput') {
                                        $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] + $report["rdinput"];
                                    }
                                    //营业额
                                    if ($idx == 'income') {
                                        $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] + $report["income"];
                                    }
                                    //税收
                                    if ($idx == 'tax') {
                                        $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] + $report["tax"];
                                    }
                                    //技术合同成交额
                                    if ($idx == 'tct') {
                                        $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] + $report["tct"];
                                    }
                                    //带动就业
                                    if ($idx == 'total') {
                                        $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $idx . '-' . $param["iqbts"][$j])[0]] + $report["total"];
                                    }

                                }
                            }
                        }
                    }

                }
            }
        }

        $result=self::initChartData(array(),$data,$yaxis);

        $iqbts=array();
        $idxs=array('rdinput'=>'研发投入','income'=>'营业额','tax'=>'税收','tct'=>'技术合同成交额','total'=>'带动就业');
        $iqbtsmsg=gethashmap("incubator",array("id"=>array('in',$param["iqbts"])),"id,name");
        if(!empty($iqbtsmsg["data"])){
            $iqbts=$iqbtsmsg["data"];
        }

        for ($i = 0; $i < count($result["legend"]); $i++) {
            $result["legend"][$i]=self::legendFmt($idxs,$result["legend"][$i],$iqbts);
        }
        for ($i = 0; $i < count($result["yaxis"]); $i++) {
            $result["yaxis"][$i]["name"]=self::legendFmt($idxs,$result["yaxis"][$i]["name"],$iqbts);
        }
        return $result;
    }

    function getIqbtFeeStatis()
    {
        $param=input("request.");
     //   var_dump($param);
        if(!isset($param["showcate"])){
            $param["showcate"]="totaldata";
        }
        if(!isset($param["iqbts"])||empty($param["iqbts"])){
            $usercate=session("user.userCate");
            if($usercate=="1011004"){
                //区域用户
                $districtId=session("user.districtId");
                if(!empty($districtId)){
                    $param["iqbts"]=getFieldArrry("incubator",array("districtId"=>array('like',$districtId."%")));
                }else{
                    return array("code"=>0,"msg"=>"当前区域下没有对应孵化器数据");
                }
            }elseif($usercate=="1011001"){
                //超级管理员
                $param["iqbts"]=[session("iqbtId")];
            }
        }
        if(!empty($param["starttime"])&&!empty($param["endtime"])){
            if(strtotime($param["endtime"])<strtotime($param["starttime"])){
                return array("code"=>0,"msg"=>"开始时间必须小于结束时间");
            }
        }
        //初始化日期------开始
        $date=array();
        if($param["statistype"]=='total'){
            $title="";
            if(empty($param["starttime"])){
                $title.="最早—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—现在";
            }else{
                $title.="—".$param["endtime"];
            }
            $date[]=$title;
        }else{
            $enddate=date("Y-m-d",time());
            if(!empty($param["endtime"])){
                $enddate=$param["endtime"];
            }
            if($param["statistype"]=="months"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-11 months",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime(date("Y-m",strtotime($startdate)));
                $enddatespan=strtotime(date("Y-m",strtotime($enddate)));
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m",$startdatespan);
                    $startdatespan=strtotime("+1 months",$startdatespan);
                }


            }else if($param["statistype"]=="day"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-10 day",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime($startdate);
                $enddatespan=strtotime($enddate);
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m-d",$startdatespan);
                    $startdatespan=strtotime("+1 day",$startdatespan);
                }
            }
        }
        //初始化日期------结束
        //初始化查询条件 ---开始

        $con=array("a.iqbtId"=>array("in",$param["iqbts"]),'a.status'=>1);
        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $msg=getDataList("feeRcd",$con,"a.iqbtId,a.etprsId,a.itemId,a.total,a.settletime,b.types","a.id",$join);
        $paydata = $msg['data'];
        //查询退费的记录，然后合并到总记录里
        $qcon = array('a.iqbtId'=>array('in',$param['iqbts']),'a.status'=>2);
        $qmsg = getDataList('feeQuitRcd',$qcon,'a.iqbtId,a.etprsId,a.itemId,a.total,a.quittime','a.id');
        if(!empty($qmsg['data'])){
            $quitdata = $qmsg['data'];

            foreach($quitdata as $key1=>$value1){
                $quitdata[$key1]['types'] = 1;
                $quitdata[$key1]['settletime'] = $value1['quittime'];
                unset($quitdata[$key1]['quittime']);
            }
        }
        $msg['data'] = array_merge($paydata,$quitdata);
      //  var_dump($msg['data']);
      //  var_dump($msg["data"]);
        //$yaxis=array(['name'=>'研发投入','type'=>'line'],['name'=>'收入','type'=>'line'],['name'=>'缴税','type'=>'line']);
        $yaxis=array();
        $empty=array();
        $keys=array();
        foreach ($param["feetype"] as $feetype) {
            if($param["showtype"]=='bar'){
                $stockarr=array('stack'=>$feetype);
            }else{
                $stockarr=array();
            }
            if($param["showcate"]=="totaldata"){
                $keys[]=$feetype;
                $yaxis[]=array_merge($stockarr,['name'=>$feetype,'type'=>$param["showtype"],'stackflag'=>$feetype,'barWidth'=>20]);
                $empty[]=0;
            }else{
                foreach ($param["iqbts"] as $iqbtId) {
                    $keys[]=$feetype.'-'.$iqbtId;
                    $yaxis[]=array_merge($stockarr, ['name'=>$feetype.'-'.$iqbtId,'type'=>$param["showtype"],'stackflag'=>$feetype,'barWidth'=>20]);
                    //$yaxis[]=['name'=>$item.'-'.$iqbtId,'type'=>$param["showtype"],'stack'=>$item];
                    $empty[]=0;
                }
            }
        }

        $data=array();
        /*$feetypes=array();
        $itemmsg=gethashmap("FeeItem",array('iqbtId'=>session("iqbtId")),"id,name");
        if(!empty($itemmsg["data"])){
            $items=$itemmsg["data"];
        }*/
        for ($i = 0; $i < count($date); $i++) {
            if(!isset($data[$date[$i]])){
                $data[$date[$i]]=$empty;
            }
            $min=0;
            $max=0;
            if($param["statistype"]=='total'){
                if(!empty($param["starttime"])){
                    $min=strtotime($param["starttime"]);
                }
                if(!empty($param["endtime"])){
                    $max=strtotime("+1 day",strtotime($param["endtime"]));
                }else{
                    $max=time();
                }
            }else{
                $min=strtotime($date[$i]);
                $max=strtotime("+1 ".$param["statistype"],$min);
            }

            if(!empty($msg["data"])){
                foreach ($msg["data"] as $rcd) {
                    $addtime=$rcd["settletime"];//应添加缴费时间 字段
                    /*foreach ($param["item"] as $item) {*/
                    if($param["showcate"]=="totaldata"){
                        //综合数据展示
                        if($addtime>=$min&&$addtime<$max){
                            if($rcd["types"]=='0'){
                                $data[$date[$i]][array_keys($keys,'0')[0]]=$data[$date[$i]][array_keys($keys,'0')[0]]+$rcd["total"];
                            }
                            if($rcd["types"]=='1'){
                                $data[$date[$i]][array_keys($keys,'1')[0]]=$data[$date[$i]][array_keys($keys,'1')[0]]+$rcd["total"];
                            }
                        }
                    }else{
                        for ($j = 0; $j < count($param["iqbts"]); $j++) {
                            if($rcd["iqbtId"]==$param["iqbts"][$j]&&$addtime>=$min&&$addtime<$max){
                                if($rcd["types"]=='0'){
                                    $data[$date[$i]][array_keys($keys,'0-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,'0-'.$param["iqbts"][$j])[0]]+$rcd["total"];
                                }
                                if($rcd["types"]=='1'){
                                    $data[$date[$i]][array_keys($keys,'1-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,'1-'.$param["iqbts"][$j])[0]]+$rcd["total"];
                                }
                            }
                        }
                    }


                    /*}*/
                }
            }

        }
        $result=self::initChartData(array(),$data,$yaxis);

        $iqbts=array();
        $iqbtsmsg=gethashmap("incubator",array("id"=>array('in',$param["iqbts"])),"id,name");
        if(!empty($iqbtsmsg["data"])){
            $iqbts=$iqbtsmsg["data"];
        }
        $feetypes=array('0'=>'缴费','1'=>'退费');
        for ($i = 0; $i < count($result["legend"]); $i++) {
            $result["legend"][$i]=self::legendFmt($feetypes,$result["legend"][$i],$iqbts);
        }
        for ($i = 0; $i < count($result["yaxis"]); $i++) {
            $result["yaxis"][$i]["name"]=self::legendFmt($feetypes,$result["yaxis"][$i]["name"],$iqbts);
        }
        return $result;
    }

    function getIqbtRoomStatis()
    {
        $param=input("request.");
        if(!isset($param["showcate"])){
            $param["showcate"]="totaldata";
        }
        if(!isset($param["iqbts"])||empty($param["iqbts"])){
            $usercate=session("user.userCate");
            if($usercate=="1011004"){
                //区域用户
                $districtId=session("user.districtId");
                if(!empty($districtId)){
                    $param["iqbts"]=getFieldArrry("incubator",array("districtId"=>array('like',$districtId."%")));
                }else{
                    return array("code"=>0,"msg"=>"当前区域下没有对应孵化器数据");
                }
            }elseif($usercate=="1011001"){
                //超级管理员
                $param["iqbts"]=[session("iqbtId")];
            }
        }
        if(!empty($param["starttime"])&&!empty($param["endtime"])){
            if(strtotime($param["endtime"])<strtotime($param["starttime"])){
                return array("code"=>0,"msg"=>"开始时间必须小于结束时间");
            }
        }
        //初始化日期------开始
        $date=array();
        if($param["statistype"]=='total'){
            $title="";
            if(empty($param["starttime"])){
                $title.="最早—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—现在";
            }else{
                $title.="—".$param["endtime"];
            }
            $date[]=$title;
        }else{
            $enddate=date("Y-m-d",time());
            if(!empty($param["endtime"])){
                $enddate=$param["endtime"];
            }
            if($param["statistype"]=="months"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-11 months",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime(date("Y-m",strtotime($startdate)));
                $enddatespan=strtotime(date("Y-m",strtotime($enddate)));
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m",$startdatespan);
                    $startdatespan=strtotime("+1 months",$startdatespan);
                }


            }else if($param["statistype"]=="day"){
                if(empty($param["starttime"])){
                    $startdate=date("Y-m-d",strtotime("-10 day",strtotime($enddate)));
                }else{
                    $startdate=$param["starttime"];
                }
                $startdatespan= strtotime($startdate);
                $enddatespan=strtotime($enddate);
                while($startdatespan<=$enddatespan){
                    $date[]=date("Y-m-d",$startdatespan);
                    $startdatespan=strtotime("+1 day",$startdatespan);
                }
            }
        }
        //初始化日期 ---结束
        //初始化查询条件 ---开始

        $con=array("a.iqbtId"=>array("in",$param["iqbts"]));
        $join = [['estate_room b','a.roomId=b.id',"left"]];
        $msg=getDataList("EstateRoomEtprs",$con,"a.*,b.addtime as roomtime,b.totalarea,b.etprsId as roometprs,b.type","a.id",$join);

        //$yaxis=array(['name'=>'研发投入','type'=>'line'],['name'=>'收入','type'=>'line'],['name'=>'缴税','type'=>'line']);
        $yaxis=array();
        $empty=array();
        $keys=array();
        if($param["showcate"]=="totaldata"){
            if(in_array("roomnum",$param["status"])){
                $keys[]='num';
                $yaxis[]=['name'=>'num','type'=>'line'];
                $empty[]=0;
            }
            if(in_array("roomarea",$param["status"])) {
                $keys[] = 'area';
                $yaxis[] = ['name' => 'area', 'type' => 'line', 'yAxisIndex' => 1];
                $empty[] = 0;
            }
            if(in_array("roomunit",$param["status"])) {
                $keys[] = 'unit';
                $yaxis[] = ['name' => 'unit', 'type' => 'line', 'yAxisIndex' => 1];
                $empty[] = 0;
            }
        }else{
            foreach ($param["iqbts"] as $iqbtId) {
                if(in_array("roomnum",$param["status"])){
                    $keys[]='num-'.$iqbtId;
                    $yaxis[]=['name'=>'num-'.$iqbtId,'type'=>'line'];
                    $empty[]=0;
                }
                if(in_array("roomarea",$param["status"])) {
                    $keys[] = 'area-' . $iqbtId;
                    $yaxis[] = ['name' => 'area-' . $iqbtId, 'type' => 'line', 'yAxisIndex' => 1];
                    $empty[] = 0;
                }
                if(in_array("roomunit",$param["status"])) {
                    $keys[] = 'unit-' . $iqbtId;
                    $yaxis[] = ['name' => 'unit-' . $iqbtId, 'type' => 'line', 'yAxisIndex' => 1];
                    $empty[] = 0;
                }
            }
        }
        foreach ($param["status"] as $status) {
            if($param["showcate"]=="totaldata"){
                $keys[]=$status;
                $tmp=array();
                if($status=="roomarea"){
                    $tmp=['yAxisIndex'=>1];
                }
                $yaxis[]=array_merge($tmp, ['name'=>$status,'type'=>'bar','stack'=>$status,'stackflag'=>$status,'barWidth'=>20]);
                $empty[]=0;
            }else{
                foreach ($param["iqbts"] as $iqbtId) {
                    $keys[]=$status.'-'.$iqbtId;
                    $tmp=array();
                    if($status=="roomarea"){
                        $tmp=['yAxisIndex'=>1];
                    }
                    $yaxis[]=array_merge($tmp, ['name'=>$status.'-'.$iqbtId,'type'=>'bar','stack'=>$status,'stackflag'=>$status,'barWidth'=>20]);
                    $empty[]=0;
                }
            }
        }
        $data=array();

        for ($i = 0; $i < count($date); $i++) {
            if(!isset($data[$date[$i]])){
                $data[$date[$i]]=$empty;
            }
            $min=0;
            $max=0;
            if($param["statistype"]=='total'){
                if(!empty($param["starttime"])){
                    $min=strtotime($param["starttime"]);
                }
                if(!empty($param["endtime"])){
                    $max=strtotime("+1 day",strtotime($param["endtime"]));
                }else{
                    $max=time();
                }
            }else{
                $min=strtotime($date[$i]);
                $max=strtotime("+1 ".$param["statistype"],$min);
            }
            if(!empty($msg["data"])){
                foreach ($msg["data"] as $room) {
                    $rmtime=$room["roomtime"];
                    if($param["statistype"]=='total'||($param["statistype"]!='total'&&$rmtime<$min)){
                        $rmstart=$room["startTime"];
                        $rmend=$room["endTime"];
                        foreach ($param["status"] as $status) {
                            if($param["showcate"]=="totaldata"){
                                if($room["type"]=='0'){
                                    if(in_array("roomnum",$param["status"])) {
                                        $data[$date[$i]][array_keys($keys, 'num')[0]] = $data[$date[$i]][array_keys($keys, 'num')[0]] + 1;
                                    }
                                    if(in_array("roomarea",$param["status"])) {
                                        $data[$date[$i]][array_keys($keys, 'area')[0]] = $data[$date[$i]][array_keys($keys, 'area')[0]] + $room["totalarea"];
                                    }
                                }else{
                                    if(in_array("roomunit",$param["status"])) {
                                        $data[$date[$i]][array_keys($keys, 'unit')[0]] = $data[$date[$i]][array_keys($keys, 'unit')[0]] + 1;
                                    }
                                }


                                //如果总数，则计算分配给企业的房间.(统计当前房屋状态，而不是统计所有)
                                if($param["statistype"]=='total'&&!empty($room["roometprs"])){
                                    if($room["type"]=='0') {
                                        if ($status == 'roomnum') {
                                            $data[$date[$i]][array_keys($keys, $status)[0]] = $data[$date[$i]][array_keys($keys, $status)[0]] + 1;
                                        }
                                        if ($status == 'roomarea') {
                                            $data[$date[$i]][array_keys($keys, $status)[0]] = $data[$date[$i]][array_keys($keys, $status)[0]] + $room["totalarea"];
                                        }
                                    }else{
                                        if ($status == 'roomunit') {
                                            $data[$date[$i]][array_keys($keys, $status)[0]] = $data[$date[$i]][array_keys($keys, $status)[0]] + 1;
                                        }
                                    }
                                }else{
                                    if($rmstart<$max&&$rmend>=$min){
                                        if($room["type"]=='0') {
                                            if($status=='roomnum'){
                                                $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                            }
                                            if($status=='roomarea'){
                                                $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+$room["totalarea"];
                                            }
                                        }else{
                                            if($status=='roomunit'){
                                                $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                            }
                                        }

                                    }
                                }
                            }else{
                                for ($j = 0; $j < count($param["iqbts"]); $j++) {
                                    if($room["iqbtId"]==$param["iqbts"][$j]){


                                        if($room["type"]=='0') {
                                            if(in_array("roomnum",$param["status"])) {
                                                $data[$date[$i]][array_keys($keys, 'num-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'num-' . $param["iqbts"][$j])[0]] + 1;
                                            }
                                            if(in_array("roomarea",$param["status"])) {
                                                $data[$date[$i]][array_keys($keys, 'area-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'area-' . $param["iqbts"][$j])[0]] + $room["totalarea"];
                                            }
                                        }else{
                                            if(in_array("roomunit",$param["status"])) {
                                                $data[$date[$i]][array_keys($keys, 'unit-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'unit-' . $param["iqbts"][$j])[0]] + 1;
                                            }
                                        }

                                        //如果总数，则计算分配给企业的房间.(统计当前房屋状态，而不是统计所有)
                                        if($param["statistype"]=='total'&&!empty($room["roometprs"])){
                                            if($room["type"]=='0') {
                                                if ($status == 'roomnum') {
                                                    $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] + 1;
                                                }
                                                if ($status == 'roomarea') {
                                                    $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] + $room["totalarea"];
                                                }
                                            }else{
                                                if ($status == 'roomunit') {
                                                    $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] + 1;
                                                }
                                            }
                                        }else{
                                            if ($rmstart < $max && $rmend >= $min) {
                                                if($room["type"]=='0') {
                                                    if ($status == 'roomnum') {
                                                        $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] + 1;
                                                    }
                                                    if ($status == 'roomarea') {
                                                        $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] + $room["totalarea"];
                                                    }
                                                }else{
                                                    if ($status == 'roomunit') {
                                                        $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, $status . '-' . $param["iqbts"][$j])[0]] + 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }


                        }
                    }
                }
            }
        }

        $result=self::initChartData(array(),$data,$yaxis);

        $iqbts=array();
        $statuses=array('num'=>'房间使用率','area'=>'面积利用率','roomnum'=>'数量','roomarea'=>'面积','roomunit'=>'工位数','unit'=>'工位使用率');
        $iqbtsmsg=gethashmap("incubator",array("id"=>array('in',$param["iqbts"])),"id,name");
        if(!empty($iqbtsmsg["data"])){
            $iqbts=$iqbtsmsg["data"];
        }
        for ($i = 0; $i < count($result["legend"]); $i++) {
            $result["legend"][$i]=self::legendFmt($statuses,$result["legend"][$i],$iqbts);
        }
        $tmpdata=$result["yaxis"];
        for ($i = 0; $i < count($tmpdata); $i++) {
            $obj=$tmpdata[$i];
            list($type,$iqbt)=explode("-",$obj["name"]."-");
            if($type=='num'||$type=='area'||$type=='unit'){
                for ($j = 0; $j < count($obj["data"]); $j++) {
                    foreach ($tmpdata as $tmp) {
                        if($tmp["name"]=="room".$obj["name"]){
                            if($obj["data"][$j]==0){
                                $obj["data"][$j]=0;
                            }else{
                                $obj["data"][$j]=round(100*$tmp["data"][$j]/$obj["data"][$j],2);
                            }
                        }
                    }
                }
                $obj["yAxisIndex"]=2;
            }
            $tmpdata[$i]=$obj;
        }
        $result["yaxis"]=$tmpdata;
        for ($i = 0; $i < count($result["yaxis"]); $i++) {
            $result["yaxis"][$i]["name"]=self::legendFmt($statuses,$result["yaxis"][$i]["name"],$iqbts);
            //计算每个周期下，面积或者数量总和
            /*for ($j = 0; $j < count($date); $j++) {
                if(!isset($tmpdata[$result["yaxis"][$i]["stack"]][$j])){
                    $tmpdata[$result["yaxis"][$i]["stack"]][$j]=0;
                }
                $tmpdata[$result["yaxis"][$i]["stack"]][$j]=$tmpdata[$result["yaxis"][$i]["stack"]][$j]+$result["yaxis"][$i]["data"][$j];
            }*/
        }
        return $result;
    }

    function legendFmt($arr=array(),$legend,$iqbts=array())
    {

        list($status,$iqbt)=explode("-",$legend."-");
        if(!empty($iqbt)){
            return $arr[$status].'-'.$iqbts[$iqbt];
        }else{
            return $arr[$status];
        }

    }
}