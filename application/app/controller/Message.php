<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/21
 * Time: 11:28
 */

namespace app\app\controller;
//use app\index\controller\Common;
use app\common\controller\Base;

class Message extends Appcommon
{
    //消息接口，消息主页面
    function messMain(){
        $userId = session('userId');
        $iqbtId = session('iqbtId');
        //从数据库查找消息分类
        $con=array("level"=>2,'code'=>array('like',"1020%"));
        $msg=getDataList("SysDict",$con,"code,name,id");
        if(!empty($msg['data'])){
            foreach($msg['data'] as $value){
                $key = $value['name'];
                $menu[$key] = $value['code'];
            }
        }else{
            $menu = array(
                '日程消息'=>'1020001',
                '活动通知'=>'1020005',
                '孵化管理'=>'1020002',
                '系统消息'=>'1020008',
                '园企互动消息'=>'1020004',
                '会议室申请'=>'1020003',
                '通知公告'=>'1020009',
            );
        }
        $msgList = array();
        foreach($menu as $key=> $value){
            $msgList[$value]['num'] = 0;//每一种消息分类的未读数量，默认为零
            $msgList[$value]['type'] = $value;
            //查找每一种消息最新的一条数据
            $newData = findById('sysMsg',array('type'=>$value,'toUserId'=>$userId,'iqbtId'=>$iqbtId),'title,addtime',array(),'0','id desc');
            if($newData['code']==1 && !empty($newData['data'])){
                $msgList[$value]['title'] = $newData['data']['title'];
                $msgList[$value]['time'] = date("m月d日 H:i",$newData['data']['addtime']);
            }else{
                $msgList[$value]['title'] = '暂无通知';
                $msgList[$value]['time'] = '';
            }
        }
        //查找计算每种消息未读的数量
        $total = getDataList('sysMsg',array('toUserId'=>$userId,'status'=>0,'iqbtId'=>$iqbtId),'id,type,addtime');
        if($total['code']==1 && !empty($total['data'])){
            foreach($total['data'] as $val){
                foreach($menu as $val2){
                    if($val['type'] == $val2){
                        $msgList[$val2]['num'] +=1;
                        break;
                    }
                }
            }
        }
       return json(array('code'=>'1','msg'=>'','data'=>$msgList));
    }

    //获取全部未读的消息数量
    function getUnread(){
        $unread = 0;  //全部未读的消息数量
        $userId = session('userId');
        $iqbtId = session('iqbtId');
        //查找计算每种消息未读的数量
        $total = getDataList('sysMsg',array('toUserId'=>$userId,'status'=>0,'iqbtId'=>$iqbtId),'id,type');
        if($total['code']==1 && !empty($total['data'])){
            $unread = count($total['data']);
        }
        return json(array('code'=>'1','msg'=>'','data'=>$unread));
    }




    //消息接口，日程消息列表页接口
    function messSchList(){
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020001',
        );
        $join = [['userSchedule b','a.relId=b.id','left']];

        $msg = getDataList('sysMsg',$map,'a.id,a.status,b.id as scheId,b.startTime,b.endTime,b.title,b.address,b.addtime','b.startTime desc',$join);
        $data = array();
        if($msg['code']==1 ){
            $unread = array();
            foreach($msg['data'] as $value){
                if($value['status'] == 0){
                    $unread[] = $value['id'];
                }
                //把消息按照时间分类
                $day = date("Y-m-d",$value['startTime']);
                $value['addtime'] = $day;
                $value['startTime'] = date("Y年m月d日 H:i",$value['startTime']).' '.date("A",$value['startTime']);
                $value['endTime'] = date("Y年m月d日 H:i",$value['endTime']).' '.date("A",$value['endTime']);
                $data[$day][] = $value;
            }
            //要把消息的状态改为已读状态
            if(!empty($unread)){
                saveDataByCon('sysMsg',array('status'=>1),array('id'=>array('in',$unread)),'阅读了日程消息');
            }

            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$data));
        }

    }

    //消息接口 日程消息详情页
    /**
     * @param string $msgId 消息ID，必须，需要根据消息ID把消息状态改为已读状态
     * @param string $scheId 日程ID  必须, 需要根据日程ID读取日程详情
     * @return \think\response\Json
     */
    function messSchInfo($messId='',$schId=''){
        if(empty($schId)){
            return json(array('code'=>'0','msg'=>'该条日程消息不存在','data'=>array()));
        }
        if(empty($messId)){
          //  return json(array('code'=>'0','msg'=>'消息参数不能为空','data'=>array()));
        }

        $schMsg = findById('userSchedule',array('id'=>$schId));
        $info = array();
        if($schMsg['code']==1){
            $info = $schMsg['data'];
            if(!empty($info)){
                $info['startTime'] =  date("Y年m月d日 H:i",$info['startTime']).' '.date("A",$info['startTime']);
                $info['endTime'] =  date("Y年m月d日 H:i",$info['endTime']).' '.date("A",$info['endTime']);
                $info['addtime'] = date("Y-m-d",$info['addtime']);
            }
            return json(array('code'=>'1','msg'=>'','data'=>$info));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$info));
        }

    }

    //消息接口，活动通知列表页
    function messActList(){
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020005',
        );
        $join = [['activityApply b','a.relId=b.id','left'],['activity c','b.activityId=c.id','left']];
        $msg = getDataList('sysMsg',$map,'a.id,a.status,a.addtime,a.content,b.id as applyId,b.status as optStatus,c.name','a.id desc',$join); //optStatus 活动状态：0：未申请 1：审核中 2：通过 3：拒绝
        $data = array();
        if($msg['code']==1){
            $unread = array();
            $data = $msg['data'];
            foreach($data as $key=>$value){
                if($value['status']==0){
                    $unread[] = $value['id'];
                }
                $data[$key]['addtime'] = date("Y-m-d",$value['addtime']);
            }
            //要把消息的状态改为已读状态
            if(!empty($unread)){
                saveDataByCon('sysMsg',array('status'=>1),array('id'=>array('in',$unread)),'阅读了活动通知消息');
            }

            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$data));
        }

    }

    //消息接口，活动通知详情页
    /**
     * @param string $messId 消息ID，要把该条消息改为已读状态
     * @param string $actId  活动通知申请ID，需要根据该ID查找数据
     * @return \think\response\Json
     */
    function messActInfo($messId='',$actId=''){
        if(empty($actId)){
            return json(array('code'=>'0','msg'=>'该条活动申请不存在','data'=>array()));
        }
        if(empty($messId)){
           // return json(array('code'=>'0','msg'=>'消息参数不能为空','data'=>array()));
        }

        //查询活动申请的详细信息
        $join = [['enterprise b','a.etprsId=b.id','left']];
        $actMsg = findById('activityApply',array('a.id'=>$actId),'a.*,b.name as etprsName',$join);
        $info = array();
        if($actMsg['code']==1){
            $info = $actMsg['data'];
            if(!empty($info)){
                $info['addtime'] = date("Y-m-d",$info['addtime']);
            }
            return json(array('code'=>'1','msg'=>'','data'=>$info));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$info));
        }
    }

    //园企互动消息接口，列表页
    function messSugList(){
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020004',
        );
        $join = [['etprsSuggest b','a.relId=b.id','left'],['enterprise c','b.etprsId=c.id','left']];
        $msg = getDataList('sysMsg',$map,'a.id,a.status,a.addtime,a.title,a.content,b.id as suggestId,b.status as replyStatus,c.name as etprsName','a.id desc',$join);//replyStatus 状态 0-已提交  1-已回复 2、已添加拜访 3、企业已反馈
        $data = array();
        if($msg['code']==1){
            $unread = array();
            $data = $msg['data'];
            foreach($data as $key=>$value){
                if($value['status']==0){
                    $unread[] = $value['id'];
                }
                $data[$key]['addtime'] = date("Y-m-d",$value['addtime']);
            }
            //要把消息的状态改为已读状态
            if(!empty($unread)){
                saveDataByCon('sysMsg',array('status'=>1),array('id'=>array('in',$unread)),'阅读了园企互动消息');
            }
            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$data));
        }
    }

    //园企互动消息接口，详情页
    function messSugInfo($messId='',$sugId='20'){
        if(empty($sugId)){
            return json(array('code'=>'0','msg'=>'该条互动消息不存在','data'=>array()));
        }
        if(empty($messId)){
          //  return json(array('code'=>'0','msg'=>'消息参数不能为空','data'=>array()));
        }
        //查询园企互动的详细信息
        $join = [['enterprise b','a.etprsId=b.id','left']];
        $sugMsg = findById('etprsSuggest',array('a.id'=>$sugId),'a.*,b.name as etprsName',$join);
        $info = array();
        if($sugMsg['code']==1){
            $info = $sugMsg['data'];
            if(!empty($info)){
                $info['addtime'] = date("Y-m-d",$info['addtime']);
                $base = new Base();
                $tmplist=$base->getDictStr("*","EtprsSuggest");
                $info = $base->setObjIdText($info,$tmplist);
            }


            return json(array('code'=>'1','msg'=>'','data'=>$info));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$info));
        }
    }

    //会议室申请消息接口  列表页
    function messRoomList(){
        $now = time();
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020003',
        );
        $join = [['oaMeetroomApl b','a.relId=b.id','left'],['oaMeetroom c','b.roomId=c.id','left'],['enterprise d','b.etprsId=d.id','left']];
        $msg = getDataList('sysMsg',$map,'a.id,a.status,a.addtime,b.id as roomaplId,b.startTime,b.endTime,b.status as optStatus, c.name as roomName,d.name as etprsName','a.id desc',$join);
        $data = array();
        if($msg['code']==1){
            $unread = array();
            foreach($msg['data'] as $value){
                //记录未读的消息，统一设置为已读
                if($value['status'] ==0){
                    $unread[] = $value['id'];
                }
                //过滤掉过期的申请
                if($value['startTime']<time()){
                    continue;
                }
                //把消息按照时间分类
                $day = date("Y-m-d",$value['addtime']);
                $value['addtime'] = $day;
                $value['startTime'] = date("H:i",$value['startTime']);
                $value['endTime'] = date("H:i",$value['endTime']);
                $data[$day][] = $value;
            }

            if(!empty($unread)){
                //要把消息的状态改为已读状态
                saveDataByCon('sysMsg',array('status'=>1),array('id'=>array('in',$unread)),'阅读了会议室申请消息');
            }

            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$data));
        }
    }

    //会议室申请接口，详情页
    function messRoomInfo($messId='',$aplId=''){
        if(empty($aplId)){
            return json(array('code'=>'0','msg'=>'该条会议室申请信息不存在','data'=>array()));
        }
        if(empty($messId)){

          //  return json(array('code'=>0,'msg'=>'消息参数不能为空','data'=>array()));
        }

        //查询会议室申请的详细信息
        $join = [['enterprise b','a.etprsId=b.id','left'],['oaMeetroom c','a.roomId=c.id','left']];
        $aplMsg = findById('oaMeetroomApl',array('a.id'=>$aplId),'a.*,b.name as etprsName,c.name as roomName,c.fee',$join);
        $info = array();
        if($aplMsg['code']==1){
            $info = $aplMsg['data'];
            if(!empty($info)){
                $hour = floor(($info['endTime']-$info['startTime'])/3600); //使用共计多少个小时
                $min = (($info['endTime']-$info['startTime'])%3600)/60;
                $time = '';
                if($hour==0){
                    $time = $min.'分钟';
                }else{
                    if($min !=0){
                        $time = $hour.'小时'.$min.'分钟';
                    }else{
                        $time = $hour.'小时';
                    }
                }
                $info['hours'] = $time;
                $info['addtime'] = date("Y-m-d",$info['addtime']);
                $info['startTime'] = date("H:i",$info['startTime']);
                $info['endTime'] = date("H:i",$info['endTime']);
            }

            return json(array('code'=>'1','msg'=>'','data'=>$info));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$info));
        }
    }


    //系统消息接口 只有一个列表页
    //目前，只有写信发的消息放到了这一类
    function messSysList(){
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020008',
        );
        $msg = getDataList('sysMsg',$map,'id,title,status,content,addtime');
        $data = array();
        if($msg['code']==1 ){
            $unread = array();
            $data = $msg['data'];
            foreach($data as $key=>$value){
                $data[$key]['addtime'] = date("Y-m-d",$value['addtime']);
                if($value['status']==0){
                    $unread[] = $value['id'];
                }
            }
            //把未读的消息改为已经读了
            if(!empty($unread)){
                saveDataByCon('sysMsg',array('status'=>1),array('id'=>array('in',$unread)),'阅读了系统消息');
            }
            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$data));
        }
    }


    //通知公告消息接口，列表页
    function messNotList(){
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020009',
        );
        $join = [['sysNotice b','a.relId=b.id','left']];
        $msg = getDataList('sysMsg',$map,'a.id,a.status,a.addtime,b.id as noticeId,b.type,b.title,b.content','a.id desc',$join);
        $data = array();
        if($msg['code']==1 && !empty($msg['data'])){

            foreach($msg['data'] as $key=> $value){
                $data[$key]['addtime'] = date("Y-m-d",$value['addtime']);
                //把未读的消息改为已经读了
                if($value['status']==0){
                    saveDataByCon('sysMsg',array('status'=>1),array('id'=>$value['id']),'阅读了系统消息');
                }
            }

            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>$data));
        }
    }

    //孵化管理列表页
    function messIqbtList(){
        //定义消息的二级分类类型
        $stype = array(
            '1'=>'材料初审',
            '2'=>'复审通知',
            '3'=>'导师评审',
            '4'=>'同意入驻',
            '5'=>'房间分配',
            '6'=>'签约入驻',
            '7'=>'续约管理',
            '8'=>'退出管理',
        );
        $map = array(
            'a.toUserId'=>session('userId'),
            'a.iqbtId'=>session('iqbtId'),
            'a.type'=>'1020002',
        );
        $msg = getDataList('sysMsg',$map,'a.id,a.status,a.relTable,a.relId,a.stype','a.id desc');
        $return = array();
        for($i=1;$i<=8;$i++){
            $return[$i]['title']=$stype[$i];
            $return[$i]['data'] = array();
        }
        if($msg['code']==1){
            $msgData = $msg['data'];
            $unread = array();
            foreach($msgData as $key=>$value){
                if($value['status']==0){
                    $unread[] = $value['id'];
                }
                $res = $this->getDataInfo($value['stype'],$value['relTable'],$value['relId']);
                if(!empty($res)){
                    $return[$res['stype']]['data'][] = $res;
                }
            }

            //要把消息的状态改为已读状态
            if(!empty($unread)){
                saveDataByCon('sysMsg',array('status'=>1),array('id'=>array('in',$unread)),'阅读了孵化管理消息');
            }
        }
        return json(array('code'=>'1','msg'=>'','data'=>$return));


    }

    function getDataInfo($stype='',$table='',$id=''){
        switch($stype){
            //材料初审
            case 1:
            {
                //材料初审：企业初始入驻申请
                if($table=="enterprise"){
                    $res = $this->getOneInfo($id,'1001011','1');
                    return $res;
                }elseif($table =='etprsAplRoom'){
                    //申请加租房间
                    $join2 = [['enterprise b','a.etprsId=b.id',"left"],["etprs_apl c",'a.etprsId=c.etprsId',"left"]];
                    $info = findById('etprsAplRoom',array('a.id'=>$id),"a.id,a.status,b.`name` as etprsName,'roomapl' as apltype,a.addtime,b.contact,a.mobile,c.industry,'seated' as type,a.etprsId",$join2);
                    if($info['code']==1 && !empty($info['data'])){
                        //如果企业的状态已经初审过了，则不显示这条消息了
                        if($info['data']['status']=='1027001'){
                            $tmplist=$this->getDictStr("industry","EtprsApl");
                            $data=$this->setObjIdText($info['data'],$tmplist);
                            if($data['apltype']==0){
                                $data['apltypeText'] = '企业入驻';
                            }elseif($data['apltype']==1){
                                $data['apltypeText'] = '团队入驻';
                            }elseif($data['apltype']=='roomapl'){
                                $data['apltypeText'] = '加租房间';
                            }
                            $data['addtime'] = date("Y-m-d",$data['addtime']);
                            $data['stype'] = 1;
                          //  $data['stypeText'] = '材料初审';
                            return $data;
                        }else{
                            return array();
                        }
                    }else{
                        return array();
                    }
                }else{
                    return array();
                }
            } break;
            case 2:
            {
                //复审通知
                $res = $this->getOneInfo($id,'1001012','2');
                return $res;
            }break;
          //  case 3:没有导师评审的消息，暂且留着这个状态
            case 4:
            {
                //同意入驻
                $res = $this->getOneInfo($id,'1001013','4');
                return $res;
            }break;
            case 5:
            {
                //分配房间
                //材料初审：企业初始入驻申请
                if($table=="enterprise"){
                    $res = $this->getOneInfo($id,'1001014','5');
                    return $res;
                }elseif($table =='etprsAplRoom'){
                    //申请加租房间
                    $join2 = [['enterprise b','a.etprsId=b.id',"left"],["etprs_apl c",'a.etprsId=c.etprsId',"left"]];
                    $info = findById('etprsAplRoom',array('a.id'=>$id),"a.id,a.status,b.`name` as etprsName,'roomapl' as apltype,a.addtime,b.contact,a.mobile,c.industry,'seated' as type,a.etprsId",$join2);
                    if($info['code']==1 && !empty($info['data'])){
                        //如果企业的状态已经被操作过了，则不显示这条消息了
                        if($info['data']['status']=='1027002'){
                            $tmplist=$this->getDictStr("industry","EtprsApl");
                            $data=$this->setObjIdText($info['data'],$tmplist);
                            if($data['apltype']==0){
                                $data['apltypeText'] = '企业入驻';
                            }elseif($data['apltype']==1){
                                $data['apltypeText'] = '团队入驻';
                            }elseif($data['apltype']=='roomapl'){
                                $data['apltypeText'] = '加租房间';
                            }
                            $data['addtime'] = date("Y-m-d",$data['addtime']);
                            $data['stype'] = 5;
                          //  $data['stypeText'] = '房间分配';
                            return $data;
                        }else{
                            return array();
                        }
                    }else{
                        return array();
                    }
                }else{
                    return array();
                }
            }break;
            case 6:
            {
                $res = $this->getOneInfo($id,'1001015','6');
                return $res;
            }break;
            case 7:
            {
                //续约管理
                $join2 = [['enterprise b','a.etprsId=b.id',"left"],["etprs_apl c",'a.etprsId=c.etprsId',"left"]];
                $info = findById('etprsAplRenew',array('a.id'=>$id),"a.id,a.status,b.`name` as etprsName,'roomapl' as apltype,a.addtime,b.contact,a.mobile,c.industry,'seated' as type,a.etprsId",$join2);
                if($info['code']==1 && !empty($info['data'])){
                    //如果企业的状态已经被操作过了，则不显示这条消息了
                    if($info['data']['status']=='1027001'){
                        $tmplist=$this->getDictStr("industry","EtprsApl");
                        $data=$this->setObjIdText($info['data'],$tmplist);
                        if($data['apltype']==0){
                            $data['apltypeText'] = '企业入驻';
                        }elseif($data['apltype']==1){
                            $data['apltypeText'] = '团队入驻';
                        }elseif($data['apltype']=='roomapl'){
                            $data['apltypeText'] = '加租房间';
                        }
                        $data['addtime'] = date("Y-m-d",$data['addtime']);
                        $data['stype'] = 7;
                       // $data['stypeText'] = '续约管理';
                        return $data;
                    }else{
                        return array();
                    }
                }else{
                    return array();
                }
            }
            case 8:
            {
                //退出管理
                $join2 = [['enterprise b','a.etprsId=b.id',"left"],["etprs_apl c",'a.etprsId=c.etprsId',"left"]];
                $info = findById('etprsAplQuit',array('a.id'=>$id),"a.id,a.status,b.`name` as etprsName,'roomapl' as apltype,a.addtime,b.contact,a.mobile,c.industry,'seated' as type,a.etprsId",$join2);
                if($info['code']==1 && !empty($info['data'])){
                    //如果企业的状态已经被操作过了，则不显示这条消息了
                    if($info['data']['status']=='1028001'){
                        $tmplist=$this->getDictStr("industry","EtprsApl");
                        $data=$this->setObjIdText($info['data'],$tmplist);
                        if($data['apltype']==0){
                            $data['apltypeText'] = '企业入驻';
                        }elseif($data['apltype']==1){
                            $data['apltypeText'] = '团队入驻';
                        }elseif($data['apltype']=='roomapl'){
                            $data['apltypeText'] = '加租房间';
                        }
                        $data['addtime'] = date("Y-m-d",$data['addtime']);
                        $data['stype'] = 8;
                      //  $data['stypeText'] = '退出管理';
                        return $data;
                    }else{
                        return array();
                    }
                }else{
                    return array();
                }
            }

            default:
                return array();
        }
    }

    function getOneInfo($id,$status,$stype){
        //定义消息的二级分类类型
        $stypeArr = array(
            '1'=>'材料初审',
            '2'=>'复审通知',
            '3'=>'导师评审',
            '4'=>'同意入驻',
            '5'=>'房间分配',
            '6'=>'签约入驻',
            '7'=>'续约管理',
            '8'=>'退出管理',
        );
        $join1 = [['etprs_apl b','a.id=b.etprsId',"left"]];
        $info = findById('enterprise',array('a.id'=>$id),"a.status,b.id,a.`name` as etprsName,b.apltype,b.addtime,b.type,a.contact,a.mobile,b.industry,b.etprsId,b.total",$join1);
        if($info['code']==1 && !empty($info['data'])){
            //如果企业的状态已经初审过了，则不显示这条消息了
            if($info['data']['status']==$status){
                $tmplist=$this->getDictStr("industry","EtprsApl");
                $data=$this->setObjIdText($info['data'],$tmplist);
                if($data['apltype']==0){
                    $data['apltypeText'] = '企业入驻';
                }elseif($data['apltype']==1){
                    $data['apltypeText'] = '团队入驻';
                }elseif($data['apltype']=='roomapl'){
                    $data['apltypeText'] = '加租房间';
                }
                $data['addtime'] = date("Y-m-d",$data['addtime']);
                $data['stype'] = $stype;
              //  $data['stypeText'] = $stypeArr[$stype];
                return $data;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    //获取工作台的菜单，
    function getMenus()
    {
        $manage = array('33','36');
        $service = array('28','30','358','32','29'); //APP孵化服务全部的菜单ID
        $userId = session('userId');
        $iqbtId=session("iqbtId");
        $stepArr = array(
            'apllist'=>'8',
            'batchapl'=>'9',
            'retrialapl'=>'10',
            'enterapl'=>'11',
            'roomdstb'=>'12',
            'enteriqbt'=>'13',
        );
        $stepmsg=findById("enterStep",array("iqbtId"=>$iqbtId),"apllist,batchapl,retrialapl,enterapl,roomdstb,enteriqbt");
        $newsubs = array();
        if(!empty($stepmsg['data'])){
            //如果是孵化入驻下的菜单
            $steps=$stepmsg['data'];
            foreach ($steps as $k=>$v) {
                if($v==1){
                    $newsubs[]=$stepArr[$k];
                }
            }
        }else{
            $newsubs = array('8','9','10','11','12','13');
        }
        $manage = array_merge($newsubs,$manage); //APP孵化管理全部的菜单ID

        $roleId = getField('user',array('id'=>$userId),'roleIds');
        $rolesMsg=getDataList("UserRole",array("id"=>array("in",$roleId)),"menuIds,rolename,parentId");
        $menuIds="";
        if($rolesMsg["code"]==='1'){
            $roles=$rolesMsg["data"];
            foreach ($roles as $role) {
                if(!empty($role["menuIds"])){
                    $menuIds.=",".$role["menuIds"];
                }
            }
        }
        $menuIdarr=array_unique(explode(",",$menuIds));
        if(strpos(','.$roleId.',',',1,')===false&&strpos(','.$roleId.',',',3,')===false&&strpos(','.$roleId.',',',6,')===false){
            //非系统维护员&&非区域用户
            $iqbtMenuIds='';
            $join = [['user_packages b','a.packageId=b.id',"left"]];
            $iqbtMenumsg=findById("incubator",array("a.id"=>$iqbtId),"b.menuIds",$join);
            if(!empty($iqbtMenumsg["data"])){
                $iqbtMenuIds=$iqbtMenumsg["data"]["menuIds"];
            }
            if(!empty($iqbtMenuIds))
                $iqbtMenuIdsArr = explode(",",$iqbtMenuIds);
            //取两个数组的交集
            $menuIdarr = array_intersect($menuIdarr,$iqbtMenuIdsArr);
        }else{
           $menuIdarr = array();
        }
        $manage = array_intersect($manage,$menuIdarr);  //根据电脑端分配的权限，取交集
        $service = array_intersect($service,$menuIdarr);  //根据电脑端分配的权限，取交集
        //查询
        $queryArr = array_merge($manage,$service);
        $menuMsg = getDataList('userMenu',array('id'=>array('in',$queryArr)),'id,name');
        if($menuMsg['code']==1){
            $menuData = $menuMsg['data'];
        }else{
            $menuData = array();
        }
        return json($menuData);
        $notice = $this->messageRemind(); //借用电脑端的未读数量数组，然后转变形式，为之所用
       // halt($notice);
        $remindArr = array(); //消息未读的数量数组
        foreach($notice as $key=>$val){
            if($key =='rootmenu'){
                continue;
            }
            $remindArr = $remindArr + $val;
        }
        $returnData = array();
        foreach($menuData as $key1=>$val1){
            if(isset($remindArr[$val1['id']])){
                $val1['unread'] = $remindArr[$val1['id']];
                $returnData[$val1['id']] = $val1;
            }else{
                $val1['unread'] = 0;
                $returnData[$val1['id']] = $val1;
            }
        }
        return json(array('code'=>'1','msg'=>'','data'=>$returnData));
    }


}