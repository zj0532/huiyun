<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/29
 * Time: 9:14
 */
namespace app\cron\controller;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Config;

class Cron extends Command
{

    //配置Cron到命令行
    protected function configure()
    {
        $this->setName('Cron')->setDescription('Command Cron');
    }

    //把要执行的函数写到这个方法里
    protected function execute(Input $input, Output $output)
    {
      // $this->index();
         $this->createRcd();
    }


      function index(){

       /* $data = array(
            'etprsId'=>1,
            'filesId'=>2,
            'desc'=>'测试定时任务'
        );
        saveData('pact',$data,'ceshi定时任务');*/
    }



    function getFeeNum($iqbtId,$numration="",$etprsId=0,$start=0,$end=0,$opt=array())
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
            $tmpcon=array("etprsId"=>$etprsId,'status'=>2,'type'=>1,'iqbtId'=>$iqbtId);
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
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'status'=>2,'type'=>0,'iqbtId'=>$iqbtId),"id,totalarea");
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
    function createRcd()
    {
        Config::load(APP_PATH.'customConfig.php');
        $fee = 0;
        $tmpnum = 0;
        $startday = '';
        $endday = '';
        //定时任务，先从企业表里查询需要生成缴费记录的孵化器
        $iqbtIdList = getFieldArrry('enterprise',array('status'=>'1001016'),'iqbtId');
        if(!empty($iqbtIdList)){
            $iqbtIdList = array_unique($iqbtIdList);
        }else{
            return ;
        }
        //针对每一个孵化器生成缴费记录
        foreach($iqbtIdList as $iqbtId){
            $aplOptlist=array();  //入驻缴费配置里与房间工位无关联的项目
            $renewOptlist = array();  //续约缴费里与房间工位无关联的项目
            $etprslist=array();  //公司列表
            $aplcon=array("a.iqbtId"=>$iqbtId,'a.feetype'=>'1030001','b.about'=>'0');
            $renewcon=array("a.iqbtId"=>$iqbtId,'a.feetype'=>'1030002','b.about'=>'0');
            $join = [['fee_item b','a.itemId=b.id','left'],['fee_item_opt c','a.optId=c.id','left']];
            $aplOptmsg=getDataList("feeItemCfg",$aplcon,"c.*,b.about",'a.id',$join);
            if(!empty($aplOptmsg["data"])){
                $aplOptlist=$aplOptmsg["data"];
            }
            $renewOptMsg = getDataList("feeItemCfg",$renewcon,"c.*",'a.id',$join);
            if(!empty($renewOptMsg['data'])){
                $renewOptlist = $renewOptMsg['data'];
            }
            $econ=array("a.iqbtId"=>$iqbtId,'status'=>'1001016');

            $emsg=getDataList("enterprise",$econ,"id,entertime,name,renewStatus");
            if(!empty($emsg["data"])){
                $etprslist=$emsg["data"];
            }
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
                        self::createRoomRcd($iqbtId,$etprs,$room);
                    }
                }

                //计算与房间无关联的费用
                if(empty($etprs['renewStatus'])){

                    //状态为0代表为入驻状态，为其他代表为续约状态
                    foreach($aplOptlist as $aplopt){

                        if(!empty($aplopt['cycle'])){
                            self::createCycle($iqbtId,$aplopt,$etprs,$fee,$tmpnum);
                        }else{
                            self::saveRcd($aplopt,$etprs['id'],$fee,$tmpnum,$startday,$endday);
                        }
                    }
                }else{
                    foreach($renewOptlist as $renewopt){
                        if(!empty($renewopt['cycle'])){
                            self::createCycle($iqbtId,$renewopt,$etprs,$fee,$tmpnum);
                        }else{
                            self::saveRcd($renewopt,$etprs['id'],$fee,$tmpnum,$startday,$endday);
                        }
                    }
                }

            }

        }
    }

    //计算每个房间应该缴纳的费用
    function createRoomRcd($iqbtId,$etprs=array(),$room=array()){
        $optids = $room['feeOptIds'];
        if(!empty($optids)){
            $optArr = explode(",",$optids);
        }else{
            return ;
        }
        //针对每一个收费标准进行收费
        foreach($optArr as $key=>$value){

            $optmsg = findById('FeeItemOpt',array('id'=>$value),'*');
            if($optmsg['code']==1 && !empty($optmsg['data'])){
                $optinfo = $optmsg['data'];
                //如果是周期性的收费，就周期性计算，如果不是，就一次性计算
                if(!empty($optinfo['cycle'])){
                    self::CreateRoomCycle($iqbtId,$etprs,$room,$optinfo);
                }else{
                    self::saveRoomRcd($etprs,$room,$optinfo);
                }
            }
        }
        return ;
    }


    //计算与房间相关的周期性收费
    function createRoomCycle($iqbtId,$etprs,$room,$opt){
        $rcdnum=0;
        $itemId=$opt["itemId"];
        $etprsId=$etprs["id"];
        $starttime = $room['startTime'];
        $quittime = $room['endTime'];
        $con = array("itemId"=>$itemId,"etprsId"=>$etprsId,"iqbtId"=>$iqbtId,'roomId'=>$room['roomId']);
        $lastOptMsg=findById("feeRcd",$con,"startTime,endTime,id",array(),'0',"endTime desc");

        if(empty($lastOptMsg["data"])||empty($lastOptMsg["data"]["endTime"])){
            //没有生成过缴费记录
            $cfgmsg=findById("FeeCfg",array("iqbtId"=>$iqbtId),"cycletype");
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
    function createCycle($iqbtId,$opt,$etprs,$fee=0,$tmpnum=0){
        $rcdnum=0;
        $itemId=$opt["itemId"];
        $etprsId=$etprs["id"];
        //与房间无关的周期性收费统一按照入驻时间
        $etprsmsg=findById("enterprise",array("id"=>$etprsId),"entertime,quittime,pactquittime");
        if(!empty($etprsmsg["data"])&&isset($etprsmsg["data"]["entertime"])&&!empty($etprsmsg["data"]["entertime"])){
            $starttime = $etprsmsg["data"]["entertime"];
            $quittime = $etprsmsg["data"]["pactquittime"];
        }

        $lastOptMsg=findById("feeRcd",array("itemId"=>$itemId,"etprsId"=>$etprsId,"iqbtId"=>$iqbtId),"startTime,endTime,id",array(),'0',"endTime desc");
        if(empty($lastOptMsg["data"])||empty($lastOptMsg["data"]["endTime"])){
            //没有生成过缴费记录

            $cfgmsg=findById("FeeCfg",array("iqbtId"=>$iqbtId),"cycletype");

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
            $rcd["adduserId"]=21;
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
                    $num=self::getFeeNum($opt["iqbtId"],$numration,$etprsId,$startday,$endday,$opt);
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
            $rcd["adduserId"]=21;
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
        $rcd["adduserId"]=21;
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
                $num=self::getFeeNum($opt["iqbtId"],$numration,$etprsId,$starttime,$endTime,$opt);
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
        $rcd["adduserId"]=21;
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
            $rcd["adduserId"]=21;
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
                    $num=self::getFeeNum($opt["iqbtId"],$numration,$etprsId,$entertime,$timearea[1],$opt);
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
            $rcd["adduserId"]=21;
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
            $rcd["adduserId"]=21;
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
                    $num=self::getFeeNum($opt["iqbtId"],$numration,$etprsId,$timearea[0],$quittime,$opt);
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
            $rcd["adduserId"]=21;
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

}