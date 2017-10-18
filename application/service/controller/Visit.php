<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/14 0014
 * Time: 下午 3:02
 */

namespace app\service\controller;
use think\Controller;
use app\index\controller\Common;
use think\Db;
use think\Exception;
use app\user\library\Pinyin;
use org\weixin\WechatPush;

class Visit extends Common
{
    //导出word 测试

    //下载文件
    function download_file($file){
        if(is_file($file)){
            $length = filesize($file);
            $type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
           // $type = mime_content_type($file);
            $showname =  ltrim(strrchr($file,'/'),'/');
            header("Content-Description: File Transfer");
            header('Content-type: ' . $type);
            header('Content-Length:' . $length);
            if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
                header('Content-Disposition: attachment; filename="' . rawurlencode($showname) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $showname . '"');
            }
            readfile($file);
            unlink($file);
            exit;
        } else {
            exit('文件已被删除！');
        }
    }

    function wordtemplatetest($table,$data,$filename){
        vendor("PHPWord");
        vendor("PHPWord.IOFactory");
        $PHPWord = new \PHPWord();
        $tfile='word/'.$table.'.docx';
        $sfile='word/files/'.iconv("utf-8", "GB2312//IGNORE", $filename).'.docx';
        $document = $PHPWord->loadTemplate($tfile);


        foreach($data as $key=>$value){
            $document->setValue($key,iconv('utf-8', 'GB2312//IGNORE', $value));
        }

        $document->save($sfile);
        self::download_file($sfile);

    }
    function outword(){
        $visitId = input('visitId','');
        if(empty($visitId)){
            return false;
        }
        $join = [['enterprise b','a.etprsId=b.id','left'],['user c','a.adduserId=c.id','left']];
        $msg = findById('visit',array('a.id'=>$visitId),'a.*,b.name as etprsname,c.name as username',$join);
        if($msg['code']==1 && !empty($msg['data'])){
            $info = $msg['data'];
            $tmplist=self::getDictStr("*","visit");
            $info=$this->setObjIdText($info,$tmplist);
        }else{
            return false;
        }
        $table="abc"; //模板文件名字，暂时用的最简单的，
        $data = array(
            'visittype'=>$info['visitTypeText'],
            'visittime'=>date("Y年m月d日 H点i分",$info['visitTime']),
            'etprsname'=>$info['etprsname'],
            'servetype'=>$info['etprsTypeText'],
            'servename'=>$info['servePeople'],
            'servemobile'=>$info['serveMobile'],
            'frequency'=>$info['frequency'],
            'visitGoal'=>$info['visitGoal'],
            'visitMain'=>$info['visitSummary'],
            'info'=>$info['etprsInfo'],
            'need'=>$info['etprsNeed'],
            'message'=>$info['transInfo'],
            'dream'=>$info['servePlan'],
            'name'=>$info['username']
        );
        $py=new Pinyin();
        $filename = $py->Pinyin($info["etprsname"],'UTF8').time();
        self::wordtemplatetest($table,$data,$filename);
    }


    //管理员获取分配的拜访企业

    function getVisitEtprs(){
        $start = input('time_start','');
        $end = input('time_end','');
        $name = input('name','');

        if(!empty($start)&&!empty($end)){
            $arr = array(
                '0'=>strtotime($start),
                '1'=>strtotime($end)
            );
            $map['visitTime'] = array('between',$arr);
        }elseif(!empty($start)){
            $map['visitTime'] = array('gt',strtotime($start));
        }elseif(!empty($end)){
            $map['visitTime'] = array('lt',strtotime($end));
        }else{
            //都为空，默认查询当前一个月的
            $start = strtotime(date("Y-m",time()));
            $end = strtotime("+1 months",$start);
            $map['visitTime'] = array('between',[$start,$end]);
        }
        $etprsId = session('user.etprsId');//获取分配的拜访企业ID
        $etsData = array();
        if(!empty($etprsId)){
            $etprsIdArr = explode(",",$etprsId);
            $con = array(
                'id'=>array('in',$etprsIdArr),
                'status'=>'1001016',
                'iqbtId'=>session('iqbtId')
            );
            if(!empty($name)){
                $con['name'] = array('like','%'.$name.'%');
            }

            $etsMsg = getDataList('enterprise',$con,'id,name,contact,mobile,entertime');
            if($etsMsg['code']==1 && !empty($etsMsg['data'])){
                $etsData = $etsMsg['data'];
                $map['adduserId'] = session('userId');
                foreach($etsData as $key=>$value){
                    $map['etprsId'] = $value['id'];
                    $map['status'] = 1;
                    $visitMsg = getDataList('visit',$map,'id');
                    if($visitMsg['code']==1 &&!empty($visitMsg['data'])){
                        $etsData[$key]['total'] = count($visitMsg['data']);
                    }else{
                        $etsData[$key]['total'] = 0;
                    }
                }
            }
        }
        return $etsData;
    }

    function detail($id=0)
    {
        $msg=findById("enterprise",array("id"=>$id),"*");
        $data=array();
        if(!empty($msg['data'])){
             $data=$msg["data"];
        }
        $data["c"]=0;
        $map['etprsId'] = $id;
        $map['status'] = 1;
        $visitMsg = findById('visit',$map,'count(id) as c');
        if(!empty($visitMsg['data'])){
            $data["vstnum"]=$visitMsg['data']["c"];
        }
        return view("",array("data"=>$data));
    }

    function visitdetail($id=0)
    {
        $data=array();
        if(!empty($id)){
            $msg=findById("visit",array("a.id"=>$id),"a.*,b.name as etprsname",[['enterprise b','a.etprsId=b.id','left']]);
            if(!empty($msg['data'])){
                $data=$msg["data"];
            }
            if(!empty($data["visitTime"])){
                $data["visitTime"]=date("Y-m-d H:i:s",$data["visitTime"]);
            }
            if(!empty($data["visitType"])){
                $con=array("code"=>$data["visitType"]);
                if(!empty(session("iqbtId"))){
                    $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
                }
                $data["visitType"]=getField("sysDict",$con,"name");
            }
        }
        return view("",array("data"=>$data));
    }

    //单个企业的拜访记录
    function etprsVisit($etprsId=0){
        if(empty($etprsId)){
            return array('code'=>0,'msg'=>"参数错误");
        }
        return view('',array('etprsId'=>$etprsId));
    }
    //获取单个企业的全部拜访记录

    function getEtprsLog($etprsId=0){
        if(empty($etprsId)){
           return array();
        }
        //获取企业的名称、联系人、联系电话
        $etprsMsg = findById('enterprise',array('id'=>$etprsId),'name,contact,mobile');
        if($etprsMsg['code']==1 &&!empty($etprsMsg['data'])){
            $etprsData = $etprsMsg['data'];
        }else{
            $etprsData = array('name'=>'','contact'=>'','mobile'=>'');
        }
        $map['etprsId'] = $etprsId;
        $map['adduserId'] = session('userId');
        $map['iqbtId'] = session('iqbtId');
        $start = input('time_start','');
        $end = input('time_end','');
        $visitType = input('visitType','');
        if(!empty($start)&&!empty($end)){
            $arr = array(
                '0'=>strtotime($start),
                '1'=>strtotime($end)
            );
            $map['visitTime'] = array('between',$arr);
        }elseif(!empty($start)){
            $map['visitTime'] = array('gt',strtotime($start));
        }elseif(!empty($end)){
            $map['visitTime'] = array('lt',strtotime($end));
        }
        if(!empty($visitType)){
            $map['visitType'] = $visitType;
        }

        $msg = getDataList('visit',$map,'*');
        if(!empty($msg['data'])){
            foreach($msg['data'] as $key=>$value){
                $msg['data'][$key]['name'] = $etprsData['name'];
                $msg['data'][$key]['contact'] = $etprsData['contact'];
                $msg['data'][$key]['mobile'] = $etprsData['mobile'];
                if($value['visitType'] =='1035001'){
                    $msg['data'][$key]['typeText'] = '例行走访';
                }elseif($value['visitType'] =='1035002'){
                    $msg['data'][$key]['typeText'] = '专题走访';
                }elseif($value['visitType'] =='1035003'){
                    $msg['data'][$key]['typeText'] = '跟踪走访';
                }elseif($value['visitType'] =='1035004'){
                    $msg['data'][$key]['typeText'] = '重点走访';
                }else{
                    $msg['data'][$key]['typeText'] = '其他走访';
                }

            }
            return $msg['data'];
        }else{
            return array();
        }
    }

    //新增拜访计划

    function addVisit($etprsId=0,$visitId=0,$sugId=0){

        if(empty($etprsId)){
            return array('code'=>0,'msg'=>'参数错误');
        }
        $visitInfo = array();
        if(!empty($visitId)){
            $msg = findById('visit',array('id'=>$visitId),'*');
            if($msg['code']==1 &&!empty($msg['data'])){}
            $visitInfo = $msg['data'];
        }
        $etprsName = getfield('enterprise',array('id'=>$etprsId),'name');
        return view('',array('sugId'=>$sugId,'etprsName'=>$etprsName,'etprsId'=>$etprsId,'data'=>$visitInfo));
    }

    //保存拜访计划
    function saveVisit(){
        $postData = input('request.');
        $etprsName = $postData['etprsName'];
        $sugId = $postData['sugId'];
        unset($postData['sugId']);
        unset($postData['etprsName']);
        $postData['visitTime'] = strtotime($postData['visitTime']);
        $postData['addtime'] = time();
        $postData['iqbtId'] = session('iqbtId');
        $postData['adduserId'] = session('userId');
        $res = savedata('visit',$postData,'添加拜访计划');
        if($res['code']==1){
            if(!empty($sugId)){
                //园企互动的ID，添加拜访后状态改为已经添加
                saveDataByCon('EtprsSuggest',array('status'=>2),array('id'=>$sugId));
            }
            if(empty($postData['id'])){
                //添加日程，管理员端
                $data = array();
                $data['aim'] = $postData['visitGoal'];
                $data['remark'] = $postData['visitMain'];
                $data['title'] = '拜访企业【'.$etprsName.'】';
                $data['addtime']= time();
                $data['iqbtId'] = session("iqbtId");
                $data['adduserId'] = session('userId');
                $data['timemark'] =1;
                $data['color'] = '#f8ac59';
                $data['startTime'] = $postData['visitTime'];
               // $data['endTime'] = $data['startTime'] +86399;
                $data['endTime'] = strtotime(date("Y-m-d",$data['startTime']))+86399;
                $mres = saveData("userSchedule",$data);
                $mid = $mres['data'];
                //给自己发一条消息通知，以便显示在手机端消息通知里
                $memailData = array(
                    'title' => '拜访企业计划安排',
                    'content' => '您好，您将于'.date("Y-m-d H:i",$postData['visitTime']).'去公司拜访，请做好相关拜访准备。',
                    'type' => '1020001',
                    'relTable'=>'userSchedule',
                    'relId'=>$mid,
                );
                $muid = session('userId');
                $this->sendAllMsg($muid,$memailData);
                //添加日程，企业端
                //获取企业对应的用户ID
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$postData['etprsId']),'id');

                $userdata = array(
                    'aim'=>$postData['visitGoal'],
                    'remark'=>$postData['visitMain'],
                    'title'=>'管理员'.session("user.realname").'前来拜访',
                    'addtime'=>time(),
                    'iqbtId'=>session('iqbtId'),
                    'timemark'=>1,
                    'color'=>'#f8ac59',
                    'startTime'=>$postData['visitTime'],
                    'endTime'=>strtotime(date("Y-m-d",$postData['visitTime']))+86399,
                    'adduserId'=>$uid
                );
                $eres =saveData('userSchedule',$userdata);
                $eid = $eres['data'];
                $emailData = array(
                    'title' => '管理员拜访通知',
                    'content' => '您好，管理员' .session('user.realname') . '将于'.date("Y-m-d H:i",$postData['visitTime']).'去公司拜访，请做好接待准备。',
                    'type' => '1020001',
                    'relTable'=>'userSchedule',
                    'relId'=>$eid,
                );
                $this->sendAllMsg($uid,$emailData);
                $nid = 0;
                $info = array(
                    'mid'=>$mid,
                    'eid'=>$eid,
                    'nid'=>$nid,
                    'id'=>$res['data']
                );
                saveData('visit',$info,'保存消息ID');
                //工作日志
                $logData = array(
                    'etprsId'=>$postData['etprsId'],
                    'fmenuId'=>27,
                    'smenuId'=>358,
                    'objId'=>$res['data'],
                    'content'=>'添加了拜访计划，于'.date("Y-m-d H:i",$postData['visitTime']).'去拜访企业',
                );
                saveOaLog($logData);
            }else{
                //修改拜访计划，发送的消息和添加的日程都要更改
                $visitInfo = findById('visit',array('id'=>$postData['id']),'mid,eid,nid');
                if($visitInfo['code']==1 && !empty($visitInfo['data'])){
                    //修改日程，管理员端
                    $data = array();
                    $data['id'] = $visitInfo['data']['mid'];
                    $data['aim'] = $postData['visitGoal'];
                    $data['remark'] = $postData['visitMain'];
                    $data['title'] = '拜访企业【'.$etprsName.'】';
                    $data['addtime']= time();
                    $data['iqbtId'] = session("iqbtId");
                    $data['adduserId'] = session('userId');
                    $data['timemark'] =1;
                    $data['color'] = '#f8ac59';
                    $data['startTime'] = $postData['visitTime'];
                    $data['endTime'] = strtotime(date("Y-m-d",$postData['visitTime']))+86399;
                    saveData("userSchedule",$data);
                    //给管理员自己发消息通知
                    $memailData = array(
                        'title' => '拜访企业计划安排',
                        'content' => '您好，您将于'.date("Y-m-d H:i",$postData['visitTime']).'去公司拜访，请做好相应的准备。',
                        'type' => '1020001',
                        'relTable'=>'userSchedule',
                        'relId'=>$visitInfo['data']['mid'],
                    );
                    $this->sendAllMsg(session('userId'),$memailData);
                    //修改日程，企业端
                    //获取企业对应的用户ID
                    $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$postData['etprsId']),'id');

                    $userdata = array(
                        'id'=>$visitInfo['data']['eid'],
                        'aim'=>$postData['visitGoal'],
                        'remark'=>$postData['visitMain'],
                        'title'=>'管理员'.session("user.realname").'前来拜访',
                        'addtime'=>time(),
                        'iqbtId'=>session('iqbtId'),
                        'timemark'=>1,
                        'color'=>'#f8ac59',
                        'startTime'=>$postData['visitTime'],
                        'endTime'=>strtotime(date("Y-m-d",$postData['visitTime']))+86399,
                        'adduserId'=>$uid
                    );
                    saveData('userSchedule',$userdata);
                    //给企业发消息通知
                    $emailData = array(
                        'title' => '管理员拜访通知',
                        'content' => '您好，管理员' .session('user.realname') . '修改了计划，将于'.date("Y-m-d H:i",$postData['visitTime']).'去公司拜访，请做好接待准备。',
                        'type' => '1020001',
                        'relTable'=>'userSchedule',
                        'relId'=>$visitInfo['data']['eid'],
                    );
                    $this->sendAllMsg($uid,$emailData);
                    //工作日志
                    $logData = array(
                        'etprsId'=>$postData['etprsId'],
                        'fmenuId'=>27,
                        'smenuId'=>358,
                        'objId'=>$postData['id'],
                        'content'=>'修改了拜访计划，于'.date("Y-m-d H:i",$postData['visitTime']).'去拜访企业',
                    );
                    saveOaLog($logData);
                }
            }
        }
        return $res;

    }


    //添加拜访总结
    function addSummary($visitId=0){
        if(!empty($visitId)){
            $info = array();
            $etprsName ='';
            $msg = findById('visit',array('id'=>$visitId),'*');
            if($msg['code']==1 && !empty($msg['data'])){
                $info = $msg['data'];
                $etprsName = getField('enterprise',array('id'=>$info['etprsId']),'name');

            }
            return view('',array('data'=>$info,'etprsName'=>$etprsName));

        }
    }

    function saveSummary(){
        $postData = input('request.');
        $postData['visitTime'] = strtotime($postData['visitTime']);
        $postData['summaryTime'] = time();
        $postData['status'] =1;
        $res = savedata('visit',$postData,'添加拜访总结');
        if($res['code']==1){
            //工作日志
            $logData = array(
                'etprsId'=>$postData['etprsId'],
                'fmenuId'=>27,
                'smenuId'=>358,
                'objId'=>$postData['id'],
                'content'=>'针对'.date("Y-m-d H:i",$postData['visitTime']).'的拜访计划，撰写了拜访总结',
            );
            saveOaLog($logData);
        }
        return $res;
    }

    function summaryInfo($visitId=0){
        if(!empty($visitId)){
            $info = array();
            $etprsName ='';
            $msg = findById('visit',array('id'=>$visitId),'*');
            if($msg['code']==1 && !empty($msg['data'])){
                $info = $msg['data'];
                $etprsName = getField('enterprise',array('id'=>$info['etprsId']),'name');

            }
            return view('',array('data'=>$info,'etprsName'=>$etprsName));

        }
    }

    //最高管理员查看所有的拜访记录
    function getManageVisit(){
        $map['a.iqbtId'] = session('iqbtId');
        $map['a.status'] = 1;
        $name = input('name','');
        $username = input('username','');
        $start = input('time_start','');
        $end = input('time_end','');
        $visitType = input('visitType','');
        if(!empty($start)&&!empty($end)){
            $arr = array(
                '0'=>strtotime($start),
                '1'=>strtotime($end)
            );
            $map['a.visitTime'] = array('between',$arr);
        }elseif(!empty($start)){
            $map['a.visitTime'] = array('gt',strtotime($start));
        }elseif(!empty($end)){
            $map['a.visitTime'] = array('lt',strtotime($end));
        }
        if(!empty($visitType)){
            $map['a.visitType'] = $visitType;
        }
        if(!empty($name)){
            //企业名称不为空，查询企业
            $map['b.name'] = array('like','%'.$name.'%');
        }
        if(!empty($username)){
            $map['c.name'] = array('like','%'.$username.'%');
        }
        $join = [['enterprise b','a.etprsId=b.id','left'],['user c','a.adduserId=c.id','left']];
        $msg = getDataList('visit',$map,'a.id,a.visitType,a.visitTime,b.name as etprsName,b.contact,b.mobile,c.name as username','a.id desc',$join);
       if($msg['code']==1 &&!empty($msg['data'])){
           return $msg['data'];
       }else{
           return array();
       }
    }

    //拜访管理，总管理员，按照企业分类查看该企业被某个管理员拜访了多少次。
    function getManEtprs(){
        $start = input('time_start','');
        $end = input('time_end','');
        $name = input('name','');
        $username = input('username','');

        if(!empty($start)&&!empty($end)){
            $arr = array(
                '0'=>strtotime($start),
                '1'=>strtotime($end)
            );
            $map['a.visitTime'] = array('between',$arr);
        }elseif(!empty($start)){
            $map['a.visitTime'] = array('gt',strtotime($start));
        }elseif(!empty($end)){
            $map['a.visitTime'] = array('lt',strtotime($end));
        }else{
            //都为空，默认查询当前一个月的
           // $start = strtotime(date("Y-m",time()));
          //  $end = strtotime("+1 months",$start);
          //  $map['a.visitTime'] = array('between',[$start,$end]);
        }

        //查询所有正在入孵的企业
        $con = array(
            'status'=>'1001016',
            'iqbtId'=>session('iqbtId')
        );
        if(!empty($name)){
            $con['name'] = array('like','%'.$name.'%');
        }
        if(!empty($username)) {
            //如果管理员姓名不为空，则只查询该管理员的企业
            $etprsId = getField('user',array('name'=>$username),'etprsId');
            if(empty($etprsId)) {
                //如果该管理员没有分配企业，直接返回为空
                return array();
            }else {
               $idArr = explode(",",$etprsId);
                $con['id'] = array('in',$idArr);
            }
        }
        $etsMsg = getDataList('enterprise',$con,'id,name,contact,mobile,entertime');

        $data = array();
        if($etsMsg['code']==1 && !empty($etsMsg['data'])){
            $etsData = $etsMsg['data'];
            foreach($etsData as $key=>$value){
                $map['a.etprsId'] = $value['id'];
                $map['a.status'] = 1;
                if(!empty($username)){
                    $uid = getField('user',array('name'=>$username),'id');
                    if(!$uid){
                        $map['a.adduserId'] = $uid;
                    }
                }
                $join = [['user b','a.adduserId=b.id','left']];
                $visitMsg = getDataList('visit',$map,'a.adduserId,b.name as username,count(a.id) as sum','',$join,'a.adduserId');
                if($visitMsg['code']==1 &&!empty($visitMsg['data'])){
                    foreach($visitMsg['data'] as $val){
                        $merArr = array_merge($value,$val);
                        $data[] = $merArr;
                    }
                }else{
                    $empArr = array('adduserId'=>'0','username'=>'','sum'=>0);
                   $data[] = array_merge($value,$empArr);
                }
            }
        }
        return $data;
        //print_r($data);
    }

    //单个企业的拜访记录
    function manEtprsVisit($etprsId=0,$uid=0){
        if(empty($etprsId)){
            return array('code'=>0,'msg'=>"参数错误");
        }
        return view('',array('etprsId'=>$etprsId,'uid'=>$uid));
    }

    //获取单个企业的全部拜访记录

    function getmanEtprsLog($etprsId=0,$uid=0){
        if(empty($etprsId)){
            return array();
        }
        //获取企业的名称、联系人、联系电话
        $etprsMsg = findById('enterprise',array('id'=>$etprsId),'name,contact,mobile');
        if($etprsMsg['code']==1 &&!empty($etprsMsg['data'])){
            $etprsData = $etprsMsg['data'];
        }else{
            $etprsData = array('name'=>'','contact'=>'','mobile'=>'');
        }
        $map['etprsId'] = $etprsId;
        $map['adduserId'] = $uid;
        $map['iqbtId'] = session('iqbtId');
        $start = input('time_start','');
        $end = input('time_end','');
        $visitType = input('visitType','');
        if(!empty($start)&&!empty($end)){
            $arr = array(
                '0'=>strtotime($start),
                '1'=>strtotime($end)
            );
            $map['visitTime'] = array('between',$arr);
        }elseif(!empty($start)){
            $map['visitTime'] = array('gt',strtotime($start));
        }elseif(!empty($end)){
            $map['visitTime'] = array('lt',strtotime($end));
        }
        if(!empty($visitType)){
            $map['visitType'] = $visitType;
        }

        $msg = getDataList('visit',$map,'*');
        if(!empty($msg['data'])){
            foreach($msg['data'] as $key=>$value){
                $msg['data'][$key]['name'] = $etprsData['name'];
                $msg['data'][$key]['contact'] = $etprsData['contact'];
                $msg['data'][$key]['mobile'] = $etprsData['mobile'];
                if($value['visitType'] =='1035001'){
                    $msg['data'][$key]['typeText'] = '例行走访';
                }elseif($value['visitType'] =='1035002'){
                    $msg['data'][$key]['typeText'] = '专题走访';
                }elseif($value['visitType'] =='1035003'){
                    $msg['data'][$key]['typeText'] = '跟踪走访';
                }elseif($value['visitType'] =='1035004'){
                    $msg['data'][$key]['typeText'] = '重点走访';
                }else{
                    $msg['data'][$key]['typeText'] = '其他走访';
                }

            }
            return $msg['data'];
        }else{
            return array();
        }
    }



}