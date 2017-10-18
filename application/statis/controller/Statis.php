<?php
namespace app\statis\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;
use think\Log;

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
        $data=array("apl"=>0,"ing"=>0,"gradt"=>0);
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
                       // if($status=="1001021"||$status=="1001020"){
                      //      $data["quid"]=$data["quid"]+1;
                      //  }
                        if($status=="1001017"){
                            $data["gradt"]=$data["gradt"]+1;
                        }
                    }
                }
            }
        }

        $dict=array("apl"=>'申请中','ing'=>'已入驻','gradt'=>'已毕业');
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
        $data=array("apl"=>0,"ing"=>0,"gradt"=>0);
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
                    if(!empty($apltime)&&($apltime>=$starttime&&$apltime<$endtime)){
                        $aplArr = array('1001011','1001012','1001013','1001014','1001015');
                        if(in_array($status,$aplArr)){
                            $data["apl"]=$data["apl"]+1;
                        }
                    }

                    if(!empty($apltime)&&($entertime>=$starttime&&$entertime<$endtime)){
                        if($status=="1001016"){
                            $data["ing"]=$data["ing"]+1;
                        }
                    }

                    if($quittime>=$starttime&&$quittime<$endtime){
                      //  if($status=="1001020"||$status=="1001021"){
                       //     $data["quid"]=$data["quid"]+1;
                      //  }
                        if($status=="1001017"){
                            $data["gradt"]=$data["gradt"]+1;
                        }
                    }

                }
            }
        }
        $dict=array("apl"=>'申请中','ing'=>'已入驻','gradt'=>'已毕业');
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
        $etprsIqbtIds = input('iqbtIds','');
        if(empty($etprsIqbtIds)){
            $etprsIqbtIds=getFieldArrry("incubator",array("etprsIqbtId"=>session("etprsIqbtId")),"id");
        }
        $con=array('a.iqbtId'=>array("in",$etprsIqbtIds),'b.status'=>'1001016');
        $join = [['enterprise b','a.etprsId= b.id']];
        $msg = getDataList('etprsInfo',$con,'a.thousand,a.overseas,a.doctor,a.student,a.worktype','',$join);
        $datanum = array('thousand'=>0,'overseas'=>0,'doctor'=>0,'student'=>0);//企业员工人员结构分类统计
       // $orgnum = array('stu'=>0,'abroad'=>0,'highlevel'=>0,'college'=>0,'company'=>0,'office'=>0,'other'=>0);
        $orgnum = array('1025001'=>0,'1025002'=>0,'1025003'=>0,'1025004'=>0,'1025005'=>0,'1025006'=>0,'1025007'=>0);

        if($msg['code']==1 && !empty($msg['data'])){
            foreach($msg['data'] as $value){
                $datanum['thousand'] += $value['thousand'];
                $datanum['overseas'] +=$value['overseas'];
                $datanum['doctor'] += $value['doctor'];
                $datanum['student'] += $value['student'];
                if(!empty($value['worktype'])){
                    $workArr =explode(",",$value['worktype']);
                    foreach($orgnum as $key=>$value){
                        if(in_array($key,$workArr)){
                            $orgnum[$key] +=1;
                        }
                    }
                }
            }
        }
        /*$data=[['name'=>'千人计划','value'=>'0'],['name'=>'博士','value'=>'0'],['name'=>'留学人员','value'=>'0'],['name'=>'应届大学生','value'=>'0']];
        $area=[['name'=>'大学生创业','value'=>'0'],['name'=>'留学归国人员','value'=>'0'],['name'=>'高层次人才','value'=>'0'],['name'=>'高校科研院所人员','value'=>'0'],['name'=>'大企业离职人员','value'=>'0'],['name'=>'机关事业单位人员','value'=>'0'],['name'=>'其他','value'=>'0']];*/
        $data=[['name'=>'千人计划','value'=>$datanum["thousand"]],['name'=>'博士','value'=>$datanum["doctor"]],['name'=>'留学人员','value'=>$datanum["overseas"]],['name'=>'应届大学生','value'=>$datanum["student"]]];
        $area=[['name'=>'大学生创业','value'=>$orgnum['1025001']],['name'=>'留学归国人员','value'=>$orgnum['1025002']],['name'=>'高层次人才','value'=>$orgnum['1025003']],['name'=>'高校科研院所人员','value'=>$orgnum['1025004']],['name'=>'大企业离职人员','value'=>$orgnum['1025005']],['name'=>'机关事业单位人员','value'=>$orgnum['1025006']],['name'=>'其他','value'=>$orgnum['1025007']]];

        // $msg=findById("etprsInfo",$con,"sum(thousand) as thousand,sum(overseas) as overseas,sum(doctor) as doctor,sum(junior) as junior,sum(student) as student");

       /* if(!empty($msg["data"])){
            $data=[['name'=>'千人计划','value'=>$msg["data"]["thousand"]],['name'=>'留学人员','value'=>$msg["data"]["overseas"]],['name'=>'博士','value'=>$msg["data"]["doctor"]],['name'=>'大专以上','value'=>$msg["data"]["junior"]],['name'=>'应届大学生','value'=>$msg["data"]["student"]]];
            $area=[['name'=>'其它','value'=>$msg["data"]["thousand"]+$msg["data"]["overseas"]+$msg["data"]["doctor"]+$msg["data"]["junior"]],['name'=>'应届大学生','value'=>$msg["data"]["student"]]];
        }*/
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
                //$districtId=session("user.districtId");
                $districtId=$param["districtId"];
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
                $title.="—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—".date("Y-m-d",time());
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
                    if(isset($param['from']) && $param['from']=='home'){
                        //首页只显示3个月的数据
                        $startdate=date("Y-m-d",strtotime("-2 months",strtotime($enddate)));
                    }else{
                        $startdate=date("Y-m-d",strtotime("-5 months",strtotime($enddate)));
                    }

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
            $statuscode[]= '1001017';
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
            //$max=0;
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
                                    $staArr = array('1001011','1001012','1001013','1001014','1001015','1001016','1001017');
                                    if(($addtime>= $min) && ($addtime<$max) &&(in_array($etprs["status"],$staArr))){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                                if($status=='ing'){
                                    if($entertime<$max&&$entertime>=$min && in_array($etprs["status"],array('1001016','1001017'))){
                                        $data[$date[$i]][array_keys($keys,$status)[0]]=$data[$date[$i]][array_keys($keys,$status)[0]]+1;
                                    }
                                }
                                if($status=='gradt'){
                                    if($quittime>=$min&& $quittime <$max &&$etprs["status"]=='1001017'){
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
                                            $staArr = array('1001011','1001012','1001013','1001014','1001015','1001016','1001017');
                                            if(($addtime>= $min) && ($addtime<$max) &&(in_array($etprs["status"],$staArr))){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                        if($status=='ing'){
                                            if($entertime<$max&&$entertime>=$min && in_array($etprs["status"],array('1001016','1001017'))){
                                                $data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,$status.'-'.$param["iqbts"][$j])[0]]+1;
                                            }
                                        }
                                        if($status=='gradt'){
                                            if($quittime>=$min&& $quittime <$max &&$etprs["status"]=='1001017'){
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
        $statuses=array('apl'=>'申请','ing'=>'入驻','gradt'=>'毕业');
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
                //$districtId=session("user.districtId");
                $districtId=$param["districtId"];
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
                $title.="—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—".date("Y-m-d",time());
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
                    if(isset($param['from']) && $param['from']=='home'){
                        $startdate=date("Y-m-d",strtotime("-2 months",strtotime($enddate)));
                    }else{
                        $startdate=date("Y-m-d",strtotime("-5 months",strtotime($enddate)));
                    }

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
                //$districtId=session("user.districtId");
                $districtId=$param["districtId"];
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
        if(isset($param['statistype']) && $param["statistype"]=='total'){
            $title="";
            if(empty($param["starttime"])){
                $title.="—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—".date("Y-m-d",time());
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
                    $startdate=date("Y-m-d",strtotime("-5 months",strtotime($enddate)));
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
        $paydata = array();
        $con=array("a.iqbtId"=>array("in",$param["iqbts"]),'a.status'=>1);
        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $msg=getDataList("feeRcd",$con,"a.iqbtId,a.etprsId,a.itemId,a.total,a.settletime,b.types","a.id",$join);
        if($msg['code']==1 && !empty($msg['data'])){
            $paydata = $msg['data'];
        }
        //查询退费的记录，然后合并到总记录里
        $qcon = array('a.iqbtId'=>array('in',$param['iqbts']),'a.status'=>2);
        $qmsg = getDataList('feeQuitRcd',$qcon,'a.iqbtId,a.etprsId,a.itemId,a.total,a.quittime','a.id');

        $quitdata = array();

        if(!empty($qmsg['data'])){
            $quitdata = $qmsg['data'];

            foreach($quitdata as $key1=>$value1){
                $quitdata[$key1]['settletime'] = $value1['quittime'];
                $quitdata[$key1]['types'] = 1;
                unset($quitdata[$key1]['quittime']);
            }
        }
        $msg['data'] = array_merge($paydata,$quitdata);
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
       // var_dump($keys);
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

                            if($rcd["types"]=='0'&&array_key_exists('0',$keys)){
                                $data[$date[$i]][array_keys($keys,'0')[0]]=$data[$date[$i]][array_keys($keys,'0')[0]]+$rcd["total"];
                            }
                            if($rcd["types"]=='1'&&array_key_exists('1',$keys)){
                                $data[$date[$i]][array_keys($keys,'1')[0]]=$data[$date[$i]][array_keys($keys,'1')[0]]+$rcd["total"];
                            }
                        }
                    }else{
                        for ($j = 0; $j < count($param["iqbts"]); $j++) {
                            if($rcd["iqbtId"]==$param["iqbts"][$j]&&$addtime>=$min&&$addtime<$max){

                                if($rcd["types"]=='0'&&in_array('0-'.$param["iqbts"][$j],$keys)){

                                    $data[$date[$i]][array_keys($keys,'0-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,'0-'.$param["iqbts"][$j])[0]]+$rcd["total"];
                                }
                                if($rcd["types"]=='1'&&in_array('1-'.$param["iqbts"][$j],$keys)){
                                    $data[$date[$i]][array_keys($keys,'1-'.$param["iqbts"][$j])[0]]=$data[$date[$i]][array_keys($keys,'1-'.$param["iqbts"][$j])[0]]+$rcd["total"];
                                }
                            }
                        }
                    }


                    /*}*/
                }
            }

        }
       // var_dump($data);exit();
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
                //$districtId=session("user.districtId");
                $districtId=$param["districtId"];
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
                $title.="—";
            }else{
                $title.=$param["starttime"]."—";
            }
            if(empty($param["endtime"])){
                $title.="—".date("Y-m-d",time());
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
                    $startdate=date("Y-m-d",strtotime("-5 months",strtotime($enddate)));
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
        $join = [['estate_room_etprs b','b.roomId=a.id',"left"]];
        $msg=getDataList("EstateRoom",$con,"b.*,a.addtime as roomtime,a.totalarea,a.etprsId as roometprs,a.type","b.id",$join);

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

            //Log::notice($msg["data"]);
            if(!empty($msg["data"])){
                foreach ($msg["data"] as $room) {
                    $rmtime=$room["roomtime"];//房间的添加时间
                    if($param["statistype"]=='total'||($param["statistype"]!='total'&&$rmtime<$max)){
                        //如果统计方式是总数 或者统计方式不是总数且房间的添加时间比最早时间小
                        $rmstart=$room["startTime"];
                        $rmend=$room["endTime"];

                        if($param["showcate"]=="totaldata") {
                            if ($room["type"] == '1') {
                                if (in_array("roomnum", $param["status"])) {
                                    $data[$date[$i]][array_keys($keys, 'num')[0]] = $data[$date[$i]][array_keys($keys, 'num')[0]] + 1;
                                }
                                if (in_array("roomarea", $param["status"])) {
                                    $data[$date[$i]][array_keys($keys, 'area')[0]] = $data[$date[$i]][array_keys($keys, 'area')[0]] + $room["totalarea"];
                                }
                            } else {
                                if (in_array("roomunit", $param["status"])) {
                                    $data[$date[$i]][array_keys($keys, 'unit')[0]] = $data[$date[$i]][array_keys($keys, 'unit')[0]] + 1;
                                }
                            }
                        }else{
                            for ($j = 0; $j < count($param["iqbts"]); $j++) {
                                if ($room["iqbtId"] == $param["iqbts"][$j]) {


                                    if ($room["type"] == '1') {
                                        if (in_array("roomnum", $param["status"])) {
                                            $data[$date[$i]][array_keys($keys, 'num-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'num-' . $param["iqbts"][$j])[0]] + 1;
                                        }
                                        if (in_array("roomarea", $param["status"])) {
                                            $data[$date[$i]][array_keys($keys, 'area-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'area-' . $param["iqbts"][$j])[0]] + $room["totalarea"];
                                        }
                                    } else {
                                        if (in_array("roomunit", $param["status"])) {
                                            $data[$date[$i]][array_keys($keys, 'unit-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'unit-' . $param["iqbts"][$j])[0]] + 1;
                                        }
                                    }
                                }
                            }
                        }

                        foreach ($param["status"] as $status) {
                            if($param["showcate"]=="totaldata"){
                                /*if($room["type"]=='1'){
                                    if(in_array("roomnum",$param["status"])) {
                                        $data[$date[$i]][array_keys($keys, 'num')[0]] = $data[$date[$i]][array_keys($keys, 'num')[0]] + 1;
                                    }
                                    if(in_array("roomarea",$param["status"])) {
                                        Log::notice("111111111110");
                                        $data[$date[$i]][array_keys($keys, 'area')[0]] = $data[$date[$i]][array_keys($keys, 'area')[0]] + $room["totalarea"];
                                    }
                                }else{
                                    if(in_array("roomunit",$param["status"])) {
                                        $data[$date[$i]][array_keys($keys, 'unit')[0]] = $data[$date[$i]][array_keys($keys, 'unit')[0]] + 1;
                                    }
                                }*/


                                //如果总数，则计算分配给企业的房间.(统计当前房屋状态，而不是统计所有)
                                //Log::notice($param);
                                if($param["statistype"]=='total'){
                                    //只计算当前的房间，status=2说明是历史记录
                                    if($room['status']!=2){
                                        if($room["type"]=='1') {
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
                                    }

                                }else{
                                    if($rmstart<$max&&$rmend>=$min){
                                        if($room["type"]=='1') {
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


                                        /*if($room["type"]=='1') {
                                            if(in_array("roomnum",$param["status"])) {
                                                $data[$date[$i]][array_keys($keys, 'num-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'num-' . $param["iqbts"][$j])[0]] + 1;
                                            }
                                            if(in_array("roomarea",$param["status"])) {
                                                Log::notice("111111111113");
                                                $data[$date[$i]][array_keys($keys, 'area-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'area-' . $param["iqbts"][$j])[0]] + $room["totalarea"];
                                            }
                                        }else{
                                            if(in_array("roomunit",$param["status"])) {
                                                $data[$date[$i]][array_keys($keys, 'unit-' . $param["iqbts"][$j])[0]] = $data[$date[$i]][array_keys($keys, 'unit-' . $param["iqbts"][$j])[0]] + 1;
                                            }
                                        }*/

                                        //如果总数，则计算分配给企业的房间.(统计当前房屋状态，而不是统计所有)
                                        if($param["statistype"]=='total'&&!empty($room["roometprs"])){
                                            if($room["type"]=='1') {
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
                                                if($room["type"]=='1') {
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
        //Log::notice($data);
        $result=self::initChartData(array(),$data,$yaxis);
        //Log::notice($result);
        //var_dump($result);
        $iqbts=array();
        $statuses=array('num'=>'房间使用率','area'=>'面积利用率','roomnum'=>'房间数量','roomarea'=>'房间面积','roomunit'=>'工位数','unit'=>'工位使用率');
        $iqbtsmsg=gethashmap("incubator",array("id"=>array('in',$param["iqbts"])),"id,name");
        if(!empty($iqbtsmsg["data"])){
            $iqbts=$iqbtsmsg["data"];
        }
        for ($i = 0; $i < count($result["legend"]); $i++) {
            $result["legend"][$i]=self::legendFmt($statuses,$result["legend"][$i],$iqbts);
        }
        $tmpdata=$result["yaxis"];
        //Log::notice($tmpdata);
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
                                //Log::notice($tmp["data"][$j]."---".$obj["data"][$j]);
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

    function areaDataStatis()
    {
        $id=session("user.districtId");
        $msg=findById("region",array("id"=>$id));
        if(!empty($msg["data"])){
             return view("",array("data"=>$msg["data"]));
        }else{
            return view();
        }
    }
    function areaEtprsStatis()
    {
        $id=session("user.districtId");
        $msg=findById("region",array("id"=>$id));
        if(!empty($msg["data"])){
            return view("",array("data"=>$msg["data"]));
        }else{
            return view();
        }
    }
    function areaRoomStatis()
    {
        $id=session("user.districtId");
        $msg=findById("region",array("id"=>$id));
        if(!empty($msg["data"])){
            return view("",array("data"=>$msg["data"]));
        }else{
            return view();
        }
    }
    function areaFeeStatis()
    {
        $id=session("user.districtId");
        $msg=findById("region",array("id"=>$id));
        if(!empty($msg["data"])){
            return view("",array("data"=>$msg["data"]));
        }else{
            return view();
        }
    }
    
    
    //管理员统计孵化器数据
    function iqbtStatis()
    {
        return view("");
    }
    function districtIqbtStatis()
    {
        $districtId=session("user.districtId");
        $msg=findById("region",array("id"=>$districtId));
        return view("",array("data"=>$msg["data"]));
    }
    function getAllIqbtStatis($districtId='')
    {
        $con=array();
        if(!empty($districtId)){
             $con["districtId"]=array('like',$districtId."%");
        }
        $data=array('num'=>0,'ctyiqbtnum'=>0,'prviqbtnum'=>0,'cityiqbtnum'=>0,'room'=>0,"station"=>0,"etprsimg"=>0,"etprsedu"=>0,"total"=>0,"junior"=>0,"thousand"=>0);
        $list=array();
        $msg=getDataList("incubator",$con,"id,name,addtime,level");
        if(!empty($msg['data'])){
            foreach ($msg["data"] as $iqbt){
                $tmp=$iqbt;
                $tmp["room"]=self::getIqbtArea($iqbt["id"]);
                $tmp["station"]=self::getIqbtStation($iqbt["id"]);
                $tmp["etprsimg"]=self::getIqbtEtprs($iqbt["id"],'1001016');//在孵企业
                $tmp["etprsedu"]=self::getIqbtEtprs($iqbt["id"],'1001017');//在孵企业
                $etprsEmploy=self::getIqbtEmploy($iqbt["id"]);//创业岗位
                $tmp["total"]=empty($etprsEmploy['total'])?0:$etprsEmploy['total'];//创业岗位
                $tmp["junior"]=empty($etprsEmploy['junior'])?0:$etprsEmploy['junior'];//大专以上
                $tmp["thousand"]=empty($etprsEmploy['thousand'])?0:$etprsEmploy['thousand'];//大专以上
                $data["room"]= $data["room"]+$tmp["room"];
                $data["station"]= $data["station"]+$tmp["station"];
                $data["etprsimg"]= $data["etprsimg"]+$tmp["etprsimg"];
                $data["etprsedu"]= $data["etprsedu"]+$tmp["etprsedu"];
                $data["total"]= $data["total"]+$tmp["total"];
                $data["junior"]= $data["junior"]+$tmp["junior"];
                $data["thousand"]= $data["thousand"]+$tmp["thousand"];
                $list[]=$tmp;


                if($iqbt["level"]=="1031001"){
                    $data['ctyiqbtnum']=$data['ctyiqbtnum']+1;
                }
                if($iqbt["level"]=="1031002"){
                    $data['prviqbtnum']=$data['prviqbtnum']+1;
                }
                if($iqbt["level"]=="1031003"){
                    $data['cityiqbtnum']=$data['cityiqbtnum']+1;
                }
            }


        }
        $data['num']=count($msg["data"]);
        return array('data'=>$data,"list"=>$list);
    }

    //获取孵化器孵化面积
    function getIqbtArea($iqbtId=0)
    {
        if(empty($iqbtId)){
            return 0;
        }
        $msg=findById("EstateRoom",array("type"=>0,"iqbtId"=>$iqbtId),"sum(totalarea) as area");
        if(!empty($msg['data'])){
             return $msg["data"]["area"];
        }else{
            return 0;
        }
    }
    //获取孵化器工位数
    function getIqbtStation($iqbtId=0)
    {
        if(empty($iqbtId)){
            return 0;
        }
        $msg=findById("EstateRoom",array("type"=>1,"iqbtId"=>$iqbtId),"count(id) as num");
        if(!empty($msg['data'])){
            return $msg["data"]["num"];
        }else{
            return 0;
        }
    }
    
    //在孵企业数量&毕业企业数量
    function getIqbtEtprs($iqbtId=0,$status='')
    {
        if(empty($iqbtId)||empty($status)){
            return 0;
        }
        $msg=findById("enterprise",array("iqbtId"=>$iqbtId,"status"=>$status),"count(id) as num");
        if(!empty($msg['data'])){
            return $msg["data"]["num"];
        }else{
            return 0;
        }
    }

    //创业岗位
    function getIqbtEmploy($iqbtId=0)
    {
        if(empty($iqbtId)){
            return 0;
        }
        $msg=findById("EtprsInfo",array("iqbtId"=>$iqbtId),"sum(total) as total,sum(junior) as junior,sum(thousand) as thousand");
        if(!empty($msg['data'])){
            return $msg["data"];
        }else{
            return [];
        }
    }

    /***
     *导出excel
     */
    function exportdata($data=array(),$filename='')
    {
        $data=json_decode($data);
        $header = array();
        $excel=[];
        if(!empty($data)){
            $header=$data[0];
            for ($i = 1; $i < count($data); $i++) {
                $excel[]=$data[$i];
            }
        }
        vendor("PHPExcel");
        vendor("PHPExcel.Writer.Excel5");
        vendor("PHPExcel.IOFactory");

        getExcel($filename,$header,$excel);
    }




    
}