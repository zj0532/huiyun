<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/29
 * Time: 19:51
 */

namespace app\app\controller;
//use app\index\controller\Common;
use app\common\controller\Baseservice;

class Service  extends Appcommon
{
    /**
     * 园企互动，获取互动列表
     * @return array
     */
    function getSuggest($title = '')
    {
        $con = array('a.iqbtId' => session("iqbtId"));
        if (!empty($title)) {
            $con['a.title'] = array('like', '%' . $title . '%');
        }
        $base = new Baseservice();
        $res = $base->getSuggest($con);
        foreach ($res as $key => $value) {
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
            switch ($value['status']) {
                case 0:
                    $statusText = "已提交";
                    break;
                case 1:
                    $statusText = "已回复";
                    break;
                case 2:
                    $statusText = '已添加拜访';
                    break;
                case 3:
                    $statusText = '已反馈';
                    break;
                default :
                    $statusText = "未知状态";
            }
            $res[$key]['statusText'] = $statusText;
        }
        $data = array(
            'code' => '1',
            'msg' => '',
            'data' => $res,
        );
        return json($data);
    }

    /**
     * 园企互动详情，只有手机端有页面
     * @param string $sugId 互动ID
     */
    function suggestDetail($sugId = '18')
    {
        if (empty($sugId)) {
            return json(array('code' => 0, 'msg' => '参数错误', 'data' => array()));
        }
        $join = [['enterprise b', 'a.etprsId=b.id', "left"]];
        $con = array('a.id' => $sugId);
        $msg = findById("EtprsSuggest", $con, "a.*,b.name", $join);
        if ($msg['code'] == 1) {
            $data = $msg['data'];
            if (!empty($data)) {
                $tmplist = self::getDictStr("*", "EtprsSuggest");
                $data = $this->setObjIdText($data, $tmplist);
                $data['addtime'] = date("Y-m-d", $data['addtime']);
            }
            return json(array('code' => '1', 'msg' => '', 'data' => $data));
        } else {
            return json(array('code' => '0', 'msg' => '查询错误', 'data' => array()));
        }
    }

    function saveSuggest()
    {
        $postData = input("request.");
        if (!isset($postData['id']) || empty($postData['id'])) {
            return json(array('code' => '0', 'msg' => '确实互动ID参数', 'data' => array()));
        }
        if (!isset($postData['desc']) || empty($postData['desc'])) {
            return json(array('code' => '0', 'msg' => '请输入回复内容', 'data' => array()));
        }
        $msg = saveDataByCon('EtprsSuggest', array('desc' => $postData['desc'], 'status' => '1'), array('id' => $postData['id']), '回复企业意见/建议');
        if ($msg['code'] == 1) {
            $uMsg = findById('EtprsSuggest', array('id' => $postData['id']), 'adduserId,etprsId,title');
            //查询企业的ID 和添加人uid
            if ($uMsg['code'] == 1 && !empty($uMsg['data'])) {
                $uid = $uMsg['data']['adduserId'];
                $etprsId = $uMsg['data']['etprsId'];
                $title = $uMsg['data']['title'];
            } else {
                $uid = 0;
                $etprsId = 0;
                $title = '';
            }
            if (!isset($postData['title'])) {
                $postData['title'] = $title;
            }
            //管理员端回复，给企业发消息
            $link = '需求/建议:' . $postData['title'];
            $status1 = " 管理员已经回复，回复内容为:" . $postData['desc'];
            $emailData = array(
                'title' => $link . '回复结果',
                'content' => '尊敬的客户您好：您提交的' . $link . $status1 . ',详情请查看相应栏目信息',
                'type' => '1020004',
                'relTable' => 'etprsSuggest',
                'relId' => $postData['id'],
            );
            $wxData = array();
            if (config('open_wechat')) {
                $template_id = config('wx_tpl.demand');
                $wxData = array(
                    'tpl' => $template_id,
                    'data' => array(
                        'keyword1' => $postData['title'],
                        'keyword2' => date("Y-m-d:H:i", time()),
                        'keyword3' => $postData['desc'],
                    ),
                    'first' => '需求/建议回复消息',
                    'remark' => '请登录系统，查看详情',
                );
            }

            $this->sendAllMsg($uid, $emailData, $wxData);
            //工作日志
            $logData = array(
                'etprsId' => $etprsId,
                'fmenuId' => 27,
                'smenuId' => 28,
                'objId' => $postData['id'],
                'content' => '对标题为：“' . $postData['title'] . '”的需求/建议进行了回复',
            );
            saveOaLog($logData);
        }
        return json($msg);
    }

    //拜访
    function saveSchedule()
    {
        $postData = input("request.");
        if(isset($postData['token'])){
            unset($postData['token']);
        }
        //时间格式转换成时间戳
        if (isset($postData['startTime'])) {
            $postData['startTime'] = strtotime($postData['startTime']);
        }
        if (isset($postData['endTime'])) {
            $postData['endTime'] = strtotime($postData['endTime']);
        } else {
            $postData['startTime'] = time();
        }
        $postData["addtime"] = time();
        $postData["adduserId"] = session("userId");
        $postData["iqbtId"] = session("iqbtId");
        $res = saveData("userSchedule", $postData);
        if ($res['code'] == 1) {
            //添加一条消息通知
            //只在新增的时候添加
            if (!(isset($postData['id']) && !empty($postData['id']))) {
                if (!isset($postData['title'])) {
                    $postData['title'] = '';
                }
                if (!isset($postData['aim'])) {
                    $postData['aim'] = '';
                }
                $emailData = array(
                    'type' => '1020001',
                    'title' => $postData['title'],
                    'content' => '日程开始时间:' . date("Y-m-d H:i", $postData['startTime']) . ';日程描述：' . $postData['aim'],
                    'relTable' => 'userSchedule',
                    'relId' => $res['data']
                );
                $uid = session('userId');
                $this->sendAllMsg($uid, $emailData);
            }
        }
        return json($res);
    }

    /**
     * 服务活动，获取活动列表
     * @param bool|false $past 是否过期，true 是查询已经过期的
     * @return \think\response\Json
     */
    function getActivity($past='0')
    {
        $con = array("a.iqbtId" => session("iqbtId"));
        if($past =='1'){
            $con['a.close'] = '1'; //已过期的，查询已经关闭的
        }else{
            $con['a.close'] = '0';
        }
        $join = [['user b', 'a.appliUserId=b.id']];
        $msg = getDataList("activity", $con, "a.id,a.name,a.banner,a.appliUserId,a.status,a.close,b.name as username", "a.close asc", $join);
        if ($msg["code"] === "1") {
            for ($i = 0; $i < count($msg["data"]); $i++) {
                //获取banner图片
                $banner = '';//默认图片地址为空
                if (!empty($msg['data'][$i]['banner'])) {
                    $picArr = explode(",", $msg['data'][$i]['banner']); //可能有多张图片，这里只需要获取一样就行了
                    if (!empty($picArr)) {
                        $picfirst = $picArr[0];
                        $banner = getField('sysFile', array('id' => $picfirst), 'savePath');
                    }
                }
                $msg['data'][$i]['banner'] = $banner;
                //申请
                $actId = $msg["data"][$i]["id"];
                $unhandle = 0;
                $aplmsg = getDataList("activityApply", array("a.activityId" => $actId, 'a.status' => '1'), "a.id");
                if ($aplmsg["code"] === "1" && !empty($aplmsg["data"])) {
                    $unhandle = count($aplmsg['data']);
                }
                $msg['data'][$i]['unhandle'] = $unhandle;
            }
            return json(array('code' => '1', 'msg' => '', 'data' => $msg["data"]));
        } else {
            return json(array('code' => '0', 'msg' => '查询错误', 'data' => array()));
        }
    }

    /**
     * 活动详情信息
     * @param string $actId 活动的ID
     * @return \think\response\Json
     */
    function activityDetail($actId='12'){
        if(empty($actId)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $con = array('a.id'=>$actId);
        $info = array();
        $msg = findById('activity',$con,'a.*');
        if($msg['code']==1 && !empty($msg['data'])){
            $info = $msg['data'];
            $info['startTime'] = date("Y-m-d H:i",$info['startTime']);
            $info['endTime'] = date("Y-m-d H:i",$info['endTime']);
            $bannerPath = array();
            if(!empty($info['banner'])){
                $picArr = explode(",", $info['banner']); //可能有多张图片，这里只需要获取一样就行了
                foreach($picArr as $value){
                    $path = getField('sysFile',array('id'=>$value),'savePath');
                    $bannerPath[] = array($value=>$path);
                }
            }
            $info['bannerPath'] = $bannerPath;
        }
        return json(array('code'=>'1','msg'=>'','data'=>$info));
    }

    /**
     * 服务活动，申请记录列表
     * @param string $actId 活动ID
     * @return \think\response\Json
     */
    function actApplyList($actId=''){

        $join2 = [['enterprise b', 'a.etprsId=b.id', "left"]];
        $aplmsg = getDataList("activityApply", array("a.activityId" => $actId), "a.id,a.contact,a.mobile,a.position,a.number,a.addtime,a.etprsId,b.name as etprsName,a.status", "a.addtime desc", $join2);
        $list = array();
        $totalnum = 0; //总报名人数
        $unhandlenum = 0; //待确认的人数
        if($aplmsg['code']==1){
            $list = $aplmsg['data'];

            foreach($list as $key=>$value){
                $list[$key]['addtime'] = date("Y-m-d",$value['addtime']);
                if($value['status']==1){
                    $statusText = '已申请';
                }elseif($value['status']==2){
                    $statusText = '已通过';
                } elseif($value['status']==3){
                    $statusText = '已拒绝';
                }else{
                    $statusText = '未知状态';
                }
                $list[$key]['statusText'] = $statusText;
                $totalnum += $value['number'];
                if($value['status']==1){
                    $unhandlenum += $value['number'];
                }
                //获取头像
                $list[$key]['logo'] = getuserHeader($value['etprsId']);
            }
        }
        $data = array(
            'totalnum'=>$totalnum,
            'unhandlenum'=>$unhandlenum,
            'apllist'=>$list
        );
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }

    /**服务活动，活动审核操作，通过或者拒绝
     * @param $id 申请ID
     * @param $status 状态 2：通过， 3：拒绝
     * @return json
     */
    function aplStatus($id,$status='')
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'申请ID不能为空','data'=>array()));
        }
        $base = new Baseservice();
        $res = $base->aplStatus($id,$status);
        return json($res);
    }

    /**
     * 服务活动，关闭活动
     * @param string $id 活动ID
     * @return array
     */
    function closeActivity($actId=''){
        if(empty($actId)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseservice();
        $res = $base->closeActivity($actId);
        return json($res);
    }

    /**
     * 会议室管理， 获取会议室列表
     * @return \think\response\Json
     */
    function getMeetroom(){
        $con = array("a.iqbtId" => session("iqbtId"));

        $msg = getDataList("oaMeetroom", $con, "a.*", "a.id desc");
        if ($msg["code"] === "1") {
            for ($i = 0; $i < count($msg["data"]); $i++) {
                //获取banner图片
                $banner = '';//默认图片地址为空
                if (!empty($msg['data'][$i]['banner'])) {
                    $picArr = explode(",", $msg['data'][$i]['banner']); //可能有多张图片，这里只需要获取一样就行了
                    if (!empty($picArr)) {
                        $picfirst = $picArr[0];
                        $banner = getField('sysFile', array('id' => $picfirst), 'savePath');
                    }
                }
                $msg['data'][$i]['banner'] = $banner;
            }
            return json(array('code' => '1', 'msg' => '', 'data' => $msg["data"]));
        } else {
            return json(array('code' => '0', 'msg' => '查询错误', 'data' => array()));
        }
    }

    /**
     * 会议室详情， 查询了目前所有的字段，手机端的有些字段暂时没有
     * @param string $roomId 房间ID
     * @return \think\response\Json
     */
    function roomDetail($roomId='1'){
        if(empty($roomId)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $con = array('a.id'=>$roomId);
        $info = array();
        $msg = findById('oaMeetroom',$con,'a.*');
        if($msg['code']==1 && !empty($msg['data'])){
            $info = $msg['data'];
            $info['addtime'] = date("Y-m-d",$info['addtime']);
            $bannerPath = array();
            if(!empty($info['banner'])){
                $picArr = explode(",", $info['banner']); //可能有多张图片，这里只需要获取一样就行了
                foreach($picArr as $value){
                    $path = getField('sysFile',array('id'=>$value),'savePath');
                    $bannerPath[] = array($value=>$path);
                }
            }
            $info['bannerPath'] = $bannerPath;
        }
        return json(array('code'=>'1','msg'=>'','data'=>$info));
    }

    /**
     * 获取会议室的申请列表
     * @param string $roomId  会议室ID
     * @param string $type ，类型， apls:待处理的  pass:已经审批过的   history: 历史记录
     */
    function roomApplyList($roomId='',$type='apls'){
        if(empty($roomId)){
            return json(array('code'=>'0','msg'=>'房间ID不能为空','data'=>array()));
        }
        $con=array('a.iqbtId'=>session("iqbtId"),"a.roomId"=>$roomId);
        $join = [['enterprise b','a.etprsId=b.id']];
        $msg=getDataList("OaMeetroomApl",$con,"a.*,b.name","a.startTime desc",$join);

        $apls=array();
        $pass=array();
        $history=array();
        if($msg["code"]=='1'){
            $records=$msg["data"];
            foreach ($records as $apl) {
                $now=time();
                $status=$apl["status"];
                switch ($status){
                    case "0":
                        if($apl["startTime"]>$now){
                            $apl["statusText"]="已申请";
                        }else{
                            $apl["statusText"]="已过期";
                        }
                        break;
                    case "1":
                        $apl["statusText"]="已通过";
                        break;
                    case "2":
                        $apl["statusText"]="未通过";
                        break;

                }
                $startTime = $apl['startTime'];
                $apl['startTime'] = date("Y-m-d H:i",$apl['startTime']);
                $apl['endTime'] = date("Y-m-d H:i",$apl['endTime']);
                $apl['addtime'] = date("Y-m-d H:i",$apl['addtime']);
                //该企业对该会议室历史使用次数
                $htynum = 0;
                $map = array(
                    'roomId'=>$roomId,
                    'etprsId'=>$apl['etprsId'],
                    'status'=>1,
                    'iqbtId'=>session('iqbtId')
                );
                $htyMsg = getDataList('OaMeetroomApl',$map,'id');
                if($htyMsg['code']==1 && !empty($htyMsg['data'])){
                    $htynum = count($htyMsg['data']);
                }
                $apl['htynum'] = $htynum;
                if ($startTime>$now&&$status=='0') {
                    //开始使用时间大于当前时间，未审核的/未通过审核的 申请记录的申请 $apls
                    $apls[]=$apl;
                }else if ($startTime>$now&&$status=='1'){
                    //开始时间大于当前时间，已通过审核的申请记录 $pass
                    $pass[]=$apl;
                }else if ($startTime<$now){
                    //管理员 ，开始时间小于当前时间，已经审核 status==1 $history
                    $history[]=$apl;
                }

            }
        }
        if($type =='history'){
            $data = $history;
        }elseif($type=='pass'){
            $data = $pass;
        }else{
            $data = $apls;
        }
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }

    /**
     * 会议室管理，申请处理，通过或者拒绝
     * @param $id 申请ID
     * @param string $status 状态：1：通过 2 拒绝
     * @return json
     */
    function roomAplStat($id,$status='')
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'申请ID不能为空','data'=>array()));
        }
        $base = new Baseservice();
        $res = $base->roomAplStat($id,$status);
        return json($res);
    }



}