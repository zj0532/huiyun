<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/25
 * Time: 9:25
 */

namespace app\common\controller;
use think\Db;
class Baseapply extends Base
{
    /***
     *配置流程的，获取下一次的状态
     */
    function getNextStatus($status)
    {
        if($status=='1001000'){
            return $status;
        }
        $arr=array(
            "apllist"=>1001011,
            "batchapl"=>1001012,
            "enterapl"=>1001013,
            "roomdstb"=>1001014,
            "enteriqbt"=>1001015
        );
        $iqbtId=session("iqbtId");
        $stepmsg=findById("enterStep",array("iqbtId"=>$iqbtId),"apllist,batchapl,enterapl,roomdstb,enteriqbt");
        if(!empty($stepmsg['data'])){
            $steps=$stepmsg['data'];
            foreach ($steps as $k=>$v){
                $tmpstatus=$arr[$k];
                if($tmpstatus>=$status&&$v=='1'){
                    return $tmpstatus;
                }
            }
        }else{
            return $status;
        }
    }

    //设置申请状态
    function setAplStatus($table,$id,$status,$sms="0")
    {
        if(empty($id)){
            return array('code'=>0,'msg'=>'参数错误','data'=>array());
        }
        $copyStatus = $status;  //用来下面判断发消息的类型，不能变
        //对于已配置流程的孵化器.如果是1001000标示退回。
        if($table=="enterprise"&&$status!='1001000'){
            $status=self::getNextStatus($status);
        }
        $msg=saveDataByCon($table,array("status"=>$status),array("id"=>$id));
        if($msg['code']==1){

            //加租房间，确认完成以后，加租的房间状态直接改为正常使用状态
            if($copyStatus=='1027003'){
                $aplEtprsId = getfield('etprsAplRoom',array('id'=>$id),'etprsId');
                if(!empty($aplEtprsId)){
                    $roomids = getFieldArrry('estateRoom',array('etprsId'=>$aplEtprsId,'status'=>'1'),'id');
                    if(!empty($roomids)){
                        saveDataByCon('estateRoom',array('status'=>2),array('etprsId'=>$aplEtprsId,'id'=>array('in',$roomids)),'修改加租房间状态');
                    }
                }
            }
            //修改状态成功再发消息提醒
            //短信消息
            $smsData = array();
            switch($copyStatus){
                case '1001012' : $info=array('type'=>'初审','msg'=>'通过');$link= '入孵初审';$status1 = "审核通过";break;
                case '1001000': $info=array('type'=>'初审','msg'=>'被拒绝');$link = '入孵初审';$status1 = "被拒绝";break;
                case '1001015':$info=array('type'=>'分配房间','msg'=>'分配完毕');$link = "房间分配" ;$status1 = "分配完毕";break;
                case '1027002' :$info=array('type'=>'加租房间','msg'=>'通过');$link = '申请加租房间';$status1 = "审核通过";break;
                case '1027000' :$info=array('type'=>'加租房间','msg'=>'被拒绝');$link = '申请加租房间';$status1 = '被拒绝';break;
                case '1027003' :$info=array('type'=>'加租房间','msg'=>'分配完毕'); $link = '加租房间';$status1 = "分配完毕";break;
                default :$info=array('type'=>'未知类型','msg'=>'');$link = '';$status1 ='';
            }
            if($sms =='1'){
                $tpl_id = config('sms_tpl_id.check');
                $smsData = array(
                    'tpl'=>$tpl_id,
                    'data'=>$info,
                );
            }
            //微信消息
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$link,
                        'keyword2'=>$status1,
                    ),
                    'first'=>'系统审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }else{
                $wxData = array();
            }
            //发送站内消息
            $emailData = array(
                'title'=>$link.'审核结果',
                'content'=>'尊敬的客户您好：您的'.$link.$status1.',详情请查看相应栏目信息',
                'type'=>'1020002',
            );

            if($table =="EtprsAplRoom"){
                $etprsid = getField($table,array('id'=>$id),'etprsId');
            }else{
                $etprsid = $id;
            }
            if($etprsid){
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsid),'id');
            }
            if($uid){
                $this->sendAllMsg($uid,$emailData,$wxData,$smsData);
            }

            //记录工作日志
            $logData = array(
                'etprsId'=>$etprsid,
                'fmenuId'=>7,
                'smenuId'=>8,
                'content'=>$link.$status1
            );
            saveOaLog($logData);
            //给下一个操作发送消息通知#
            $typeArr = array(
                '1001011'=>array('1','8','材料初审：有新的任务需要处理'),   //第一个代表消息的二级类型:stype    第二个代表菜单ID
                '1001012'=>array('2','9','复审通知：有新的任务需要处理'),
                '1001013'=>array('4','11','同意入驻：有新的任务需要处理'),
                '1001014'=>array('5','12','房间分配：有新的任务需要处理'),
                '1001015'=>array('6','13','签约入驻：有新的任务需要处理'),
            );
            if(isset($typeArr[$status])){
                $adminIds = getAdminIds($typeArr[$status][1],false);
                $adminmsg = array(
                    'title'=>$typeArr[$status][2],
                    'content'=>$typeArr[$status][2],
                    'type'=>'1020002',
                    'relTable'=>$table,
                    'relId'=>$id,
                    'stype'=>$typeArr[$status][0]
                );
                //自己不给自己发
                $selfId = array(session('userId'));
                $adminIds = array_diff($adminIds,$selfId);
                $this->sendAllMsg($adminIds,$adminmsg);
            }elseif($copyStatus=='1027002'){
                $adminIds = getAdminIds('12',false);
                $adminmsg = array(
                    'title'=>'房间分配：有新的任务需要处理',
                    'content'=>'房间分配：有新的任务需要处理',
                    'type'=>'1020002',
                    'relTable'=>$table,
                    'relId'=>$id,
                    'stype'=>5
                );
                //自己不给自己发
                $selfId = array(session('userId'));
                $adminIds = array_diff($adminIds,$selfId);
                $this->sendAllMsg($adminIds,$adminmsg);
            }


            return array('code'=>'1','msg'=>'操作成功','data'=>array());

        }else{
            return $msg;
        }
    }

    //材料初审，获取所有入驻申请
    function getApllist($etprs="",$contact="",$apltype="",$type="apl")
    {
        if($type=="apl"){
            $con1=array("a.status"=>"1027001");
            $con2=array("a.status"=>"1001011");
        }else if($type=="pass"){
            //审核通过
            $con1=array("a.status"=>"1027002");
            $con2=array("a.status"=>"1001012");
        }else if($type=="back"){
            //退回 或中止
            $con1=array("a.status"=>"1027000");
            $con2=array("a.status"=>"1001000");
        }else{
            $con1=array("1"=>"-1");
            $con2=array("1"=>"-1");
        }
        $con1["a.iqbtId"]=session("iqbtId");
        $con2["a.iqbtId"]=session("iqbtId");
        $roomapl=array();
        if($apltype=="seated"||empty($apltype)){
            if(!empty($etprs)){
                $con1["b.name"]=array("like","%".$etprs."%");
            }

            if(!empty($contact)){
                $con1["b.contact"]=array("like","%".$contact."%");
            }
            $join1 = [['enterprise b','a.etprsId=b.id',"left"],["etprsApl c",'a.etprsId=c.etprsId',"left"]];
            $roomaplmsg=getDataList("etprsAplRoom",$con1,"a.id,b.`name` as etprsName,'roomapl' as apltype,a.addtime,b.contact,a.mobile,c.industry,c.workstyle,'seated' as type,a.etprsId","a.addtime desc",$join1);
            if(!empty($roomaplmsg["data"])){
                $roomapl=$roomaplmsg["data"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $roomapl=$this->setListIdText($roomapl,$tmplist);
            }
        }

        //入驻申请
        $etprsapl=array();
        if($apltype!="seated"){
            $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];

            if(!empty($etprs)){
                $con2["a.name"]=array("like","%".$etprs."%");
            }
            if(!empty($contact)){
                $con2["a.contact"]=array("like","%".$contact."%");
            }
            if($apltype!=""){
                $con2["b.type"]=$apltype;
            }
            $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.addtime,b.type,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId","b.addtime desc",$join2);
            if(!empty($etprsaplmsg["data"])){
                $etprsapl=$etprsaplmsg["data"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $etprsapl=$this->setListIdText($etprsapl,$tmplist);
            }
        }
        $result = array_merge($roomapl, $etprsapl);
        return $result;
    }

    //复审通知，获取所有入驻申请
    function getBatchApl($etprs="",$contact="",$apltype="")
    {
        $con2=array("a.status"=>"1001012",'a.iqbtId'=>session("iqbtId"));
        //入驻申请
        $etprsapl=array();
        $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];

        if(!empty($etprs)){
            $con2["a.name"]=array("like","%".$etprs."%");
        }
        if(!empty($contact)){
            $con2["a.contact"]=array("like","%".$contact."%");
        }
        if($apltype!=""){
            $con2["b.type"]=$apltype;
        }
        $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.type,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId","b.addtime desc",$join2);
        if(!empty($etprsaplmsg["data"])){
            $etprsapl=$etprsaplmsg["data"];
            $tmplist=self::getDictStr("*","EtprsApl");
            $etprsapl=$this->setListIdText($etprsapl,$tmplist);
        }
        return $etprsapl;
    }


    //创建批次时，备份指标集，以后查询的指标从备份里查询，不能改了
    function backupIndex($batch){
        //查询当前的指标集
        $indexMsg = getDataList('etprsAplIndex',array('iqbtId'=>session('iqbtId')),"*");
        if($indexMsg['code']==1 &&!empty($indexMsg['data'])){
            foreach($indexMsg['data'] as $value){
                $data = array(
                    'iqbtId'=>$value['iqbtId'],
                    'adduserId'=>$value['adduserId'],
                    'addtime'=>time(),
                    'desc'=>$value['desc'],
                    'title'=>$value['title'],
                    'score'=>$value['score'],
                    'parentId'=>$value['parentId'],
                    'level'=>$value['level'],
                    'sort'=>$value['sort'],
                    'indexId'=>$value['id'],
                    'batch'=>$batch
                );
                savedata('etprsAplIndexBackup',$data,'备份批次表');
            }
        }
    }
    //保存批次，
    function saveBatch($postData=array(),$sms= '0'){
        $data = array(
            'batch'=>$postData['batch'],
            'batchTime'=>$postData['batchTime'],
            'batchAddress'=>$postData['batchAddress'],
            'batchRemark'=>$postData['batchRemark'],
            'status'=>'1001013'
        );
        $data["status"]=self::getNextStatus($data["status"]);
        if($data['status']=='1001013'){
            //如果有导师复审环节，则判断指标设置的是否正确
            $indexMsg = getDataList('etprsAplIndex',array('iqbtId'=>session('iqbtId'),'parentId'=>0),"id,title");
            if($indexMsg['code']==1 && empty($indexMsg['data'])){
                return array('code'=>0,'msg'=>'当前指标集为空，请先设置复审的指标集','data'=>array());
            }
            if($indexMsg['code']==1 &&!empty($indexMsg['data'])){
                foreach($indexMsg['data'] as $parent){
                    $childMsg = getDataList('etprsAplIndex',array('iqbtId'=>session('iqbtId'),'parentId'=>$parent['id']),"id");
                    if($childMsg['code']==1 && empty($childMsg['data'])){
                        $err = "大指标：".$parent['title'].' 下无小指标，会导致该大指标无法评分，请先添加小指标';
                        return array('code'=>0,'msg'=>$err,'data'=>array());
                    }
                }
            }
        }
        $msg= saveDataByCon("enterprise",$data,array("id"=>array("in",$postData["ids"])),"复审通知");
        if($msg['code'] ==1){
            if($data['status']=='1001013') {
                //备份这个批次的指标,先查看一下这个批次的指标是否备份，如果已经有了，则不再备份
                $batchmsg = getDataList('etprsAplIndexBackup', array('iqbtId'=>session('iqbtId'),'batch' => $postData['batch']), 'batch');
                if ($batchmsg['code'] == 1) {
                    if (empty($batchmsg['data'])) {
                        //如果备份指标为空，则备份该指标
                        self::backupIndex($postData['batch']);
                    }
                }
            }
            //给企业负责人发送微信通知
            $etprsIds = explode(',',$postData['ids']);
            $link = "答辩通知";
            $status1 = "答辩时间：".$postData['batchTime'].',答辩地点:'.$postData['batchAddress'].',其他说明：'.$postData['batchRemark'];
            $emailData = array(
                'title'=>$link,
                'content'=>'尊敬的客户您好：您的答辩通知：'.$status1.',详情请查看相应栏目信息',
                'type'=>'1020002',
            );
            $wxData = array();
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$link,
                        'keyword2'=>$status1,
                    ),
                    'first'=>'系统审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            $smsData = array();
            if($sms ==1){
                $tpl_id = config('sms_tpl_id.batch');
                $smsData = array(
                    'tpl'=>$tpl_id,
                    'data'=>array(
                        'time'=>$postData['batchTime'],
                        'address'=>$postData['batchAddress'],
                        'other'=>$postData['batchRemark'],
                    ),
                );
            }
            foreach($etprsIds as $val){
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$val),'id');
                if($uid){
                    $this->sendAllMsg($uid,$emailData,$wxData,$smsData);
                }
            }

            //记录工作日志
            $logData = array(
                'etprsId'=>$postData['ids'],
                'fmenuId'=>7,
                'smenuId'=>9,
                'content'=>$status1
            );
            saveOaLog($logData);
            //给下一个操作发送消息通知#
            $typeArr = array(
                '1001011'=>array('1','8','材料初审：有新的任务需要处理'),   //第一个代表消息的二级类型:stype    第二个代表菜单ID
                '1001012'=>array('2','9','复审通知：有新的任务需要处理'),
                '1001013'=>array('4','11','同意入驻：有新的任务需要处理'),
                '1001014'=>array('5','12','房间分配：有新的任务需要处理'),
                '1001015'=>array('6','13','签约入驻：有新的任务需要处理'),
            );
            //给下一个流程发通知
            $status = $data['status'];
            if(isset($typeArr[$status])){
                $adminIds = getAdminIds($typeArr[$status][1],false);
                //自己不给自己发
                $selfId = array(session('userId'));
                $adminIds = array_diff($adminIds,$selfId);
                foreach($etprsIds as $relId){
                    $adminmsg = array(
                        'title'=>$typeArr[$status][2],
                        'content'=>$typeArr[$status][2],
                        'type'=>'1020002',
                        'relTable'=>'enterprise',
                        'relId'=>$relId,
                        'stype'=>$typeArr[$status][0]
                    );
                    $this->sendAllMsg($adminIds,$adminmsg);
                }
            }

            return array('code'=>1,'msg'=>'复审通知成功','status'=>1);

        }else{
            return $msg;
        }
    }

    //在复审通知的时候的退回
    function setAplBack($postData=array())
    {
        $msg= saveDataByCon("enterprise",array("status"=>"1001000"),array("id"=>array("in",$postData["ids"])),"复审通知");
        if($msg["code"]==='1'){
            $idarr=explode(",",$postData["ids"]);
            foreach ($idarr as $id) {
                $note["etprsId"]=$id;
                $note['aplId'] = getField('etprsApl',array('etprsId'=>$id),'id');
                $note["content"]=$postData["content"];
                $note["status"]=$postData["status"];
                $note["iqbtId"]=session("iqbtId");
                $note["addtime"]=time();
                $note["adduserId"]=session("userId");
                $note["type"]=1;
                saveData("etprsAplNote",$note,"复审退回备注");

                //给企业发通知
                $uid = getField('user',array('etprsId'=>$id,'userCate'=>'1011002'),'id');
                //发送站内消息
                $emailData = array(
                    'title'=>'复审审核未通过',
                    'content'=>'尊敬的客户您好：您的复审审核未通过，原因为：'.$postData['content'],
                    'type'=>'1020002',
                );
                $this->sendAllMsg($uid,$emailData);
            }
            //记录工作日志
            $logData = array(
                'etprsId'=>$postData['ids'],
                'fmenuId'=>7,
                'smenuId'=>9,
                'content'=>'复审未通过，原因：'.$postData['content'],
            );
            saveOaLog($logData);

        }
        return $msg;
    }

    //导师复审，单独拿出来，已经复审过的过滤掉，不再显示
    function getTutorReApl($etprs="",$contact=""){
        $con2=array("a.status"=>"1001013",'a.iqbtId'=>session("iqbtId"));
        $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];
        if(!empty($etprs)){
            $con2["a.name"]=array("like","%".$etprs."%");
        }
        if(!empty($contact)){
            $con2["a.contact"]=array("like","%".$contact."%");
        }
        $data = array();
        $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.type,a.batch,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId","b.addtime desc",$join2);
        if(!empty($etprsaplmsg["data"])) {
            $etprsapl = $etprsaplmsg["data"];
            $tmplist = self::getDictStr("*", "EtprsApl");
            $etprsapl = $this->setListIdText($etprsapl, $tmplist);

            foreach($etprsapl as $key1 =>$apl){
                $map = array(
                    'iqbtId'=>session('iqbtId'),
                    'adduserId'=>session('userId'),
                    'aplId' =>$apl['id'],
                );
                $checkMsg = findById('etprsAplIndexScore',$map,'id');
                if($checkMsg['code']==1 && !empty($checkMsg['data'])){
                    continue;
                }else{
                    $data[] = $apl;
                }
            }
        }

        return $data;
    }

    /**
     * 复审详情页，根据指标，读出打分列表来
     * @param int $id  申请ID
     * @return \think\response\View
     */
    function retrial($id=0)
    {
        $map2 = array("aplId"=>$id,"adduserId"=>session("userId"),'iqbtId'=>session('iqbtId'));
        $scoreMsg = getDataList('etprsAplIndexScore',$map2,'indexId,score');
        $teacher = array(); //导师的评分，格式： 指标ID=> 评分
        if($scoreMsg['code'] ==1) {
            if (!empty($scoreMsg['data'])) {
                //把二维数组提取出一位数组
                $teaKey = i_array_column($scoreMsg['data'], 'indexId');
                $teaValue = i_array_column($scoreMsg['data'], 'score');
                $teacher = array_combine($teaKey, $teaValue);
            }
        }

        //申请信息
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("etprsApl",array("a.id"=>$id),"b.name as etprsname,b.batch,a.*",$join);
        $data=array();
        $etprsId=0;
        $batch = '';
        if($msg["code"]==='1'){
            $data=$msg["data"];
            $etprsId=$data["etprsId"];
            $batch = $data['batch'];
            $tmplist=self::getDictStr("*","EtprsApl");
            $data=$this->setObjIdText($data,$tmplist);
        }

        //指标查询 从备份表里根据批次查询
        $con = array(
            'batch'=>$batch,
            'parentId'=>0,
            'level'=>1,
            'iqbtId'=>session('iqbtId')
        );

        $msg=getDataList("EtprsAplIndexBackup",$con,"a.id,a.desc,a.title,a.score,a.indexId"," a.sort desc");
        if($msg["code"]==="1"){

            foreach($msg['data'] as $key=>$value){
                $map = array(
                    'batch'=>$batch,
                    'parentId'=>$value['indexId'],
                    'level'=>2,
                    'iqbtId'=>session('iqbtId')
                );
                $conMsg = getDataList('EtprsAplIndexBackup',$map,'a.id,a.desc,a.title,a.score,a.indexId,a.parentId');
                if($conMsg['code']==1){
                    foreach($conMsg['data'] as $key1=>$value4){
                        if(!empty($teacher) && isset($teacher[$value4['id']])){
                            $conMsg['data'][$key1]['org'] = $teacher[$value4['id']]; //org，导师的评的分，从teacher数组里根据指标ID对应过来的，
                        }else{
                            $conMsg['data'][$key1]['org'] = 0;
                        }

                    }
                    $msg['data'][$key]['child'] = $conMsg['data'] ; //每一个大指标下的二级指标
                }else{
                    $msg['data'][$key]['child'] = array();
                }
            }
            $grade = $msg["data"];
        }else{
            $grade = array();
        }
        //复审的备注信息
        $notes=array();
        if(!empty($teacher)){
            $nmsg=findById("etprsAplNote",array("aplId"=>$id,"adduserId"=>session("userId"),'type'=>0),"id,content");
            if(!empty($nmsg["data"])){
                $notes=$nmsg["data"];
            }
        }
        $result = array(
            'data'=>$data,   //申请信息
            'note'=>$notes,   //如果已经复审，复审的备注信息
            'grade'=>$grade,   //指标集，二维数组
            'etprsId'=>$etprsId,   //enterprise　ID
        );
        return $result;
    }

    /**
     * @param array $grade  grade ,指标评分的数组，格式：指标ID=>得分 ,不能包括其他无用的值
     * @param string $aplId  申请ID
     * @param string $content  复审意见
     * @param string $noteId   复审意见ID，理论上不需要这个参数，因为电脑端用到了，暂时留着
     * @return array
     */
    function saveRetrialInfo($grade=array(),$aplId='',$content='',$noteId='')
    {
        if(!empty($noteId)){
            $note['id'] = $noteId;
        }
        //保存复审意见
        $note["content"]=trim($content);
        $note["aplId"]=$aplId;
        $note["status"]="1001013";
        $note["iqbtId"]=session("iqbtId");
        $note["addtime"]=time();
        $note["type"]=0;
        $note["adduserId"]=session("userId");
        $msg=saveData("etprsAplNote",$note,"复审备注");
        try {
            Db::startTrans();
            foreach($grade as $key=>$value){
                if($key==0){
                    continue;
                }
                $data['aplId'] = $aplId;
                $data['iqbtId'] = session("iqbtId");
                $data['adduserId'] = session("userId");
                $data['addtime'] = time();
                $data['indexId'] = $key;
                $data['score'] = $value;
                $msg = saveData("etprsAplIndexScore",$data,"复审指标评分");
                if ($msg["code"] !== '1') {//出现错误
                    Db::rollback();
                    return $msg;
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $msg['code'] = '0';
            $msg["msg"] = $e->getMessage();
            return $msg;
        }
        //记录工作日志
        $etprsId = getField('etprsApl',array('id'=>$aplId),'etprsId');
        $logData = array(
            'etprsId'=>$etprsId,
            'fmenuId'=>7,
            'smenuId'=>10,
            'content'=>'评审意见：'.$content,
        );
        saveOaLog($logData);
        return array('code'=>1,'msg'=>"评分成功");
    }

    //获取所有同意入驻的信息
    function getRetrialApl($etprsname="",$contact="")
    {
        $con2=array("a.status"=>"1001013",'a.iqbtId'=>session("iqbtId"));
        //入驻申请
        $etprsapl=array();
        $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];

        if(!empty($etprsname)){
            $con2["a.name"]=array("like","%".$etprsname."%");
        }
        if(!empty($contact)){
            $con2["a.contact"]=array("like","%".$contact."%");
        }

        $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.type,a.batch,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId","b.addtime desc",$join2);
        if(!empty($etprsaplmsg["data"])){
            $etprsapl=$etprsaplmsg["data"];
            $tmplist=self::getDictStr("*","EtprsApl");
            $etprsapl=$this->setListIdText($etprsapl,$tmplist);

            //统计该企业的最终评分
            foreach($etprsapl as $key=>$etprs){
                //获取该企业的批次
                //查询所有指标
                $con = array(
                    'batch'=>$etprs['batch'],
                    'parentId'=>0,
                    'level'=>1,
                    'iqbtId'=>session('iqbtId')
                );
                $msgKpi=getDataList("EtprsAplIndexBackup",$con,"a.id,a.indexId,a.score,a.title"," a.sort desc");
                if($msgKpi["code"]==="1" && !empty($msgKpi['data'])){
                    //查询二级指标
                    foreach($msgKpi['data'] as $key1=>$value){
                        $map = array(
                            'batch'=>$etprs['batch'],
                            'parentId'=>$value['indexId'],
                            'level'=>2,
                            'iqbtId'=>session('iqbtId')
                        );
                        $conMsg = getDataList('EtprsAplIndexBackup',$map,'a.id,a.score,a.title');
                        if($conMsg['code']==1){
                            $msgKpi['data'][$key1]['child'] = $conMsg['data'] ;
                        }else{
                            $msgKpi['data'][$key1]['child'] = array();
                        }
                    }
                    $score = $msgKpi['data'];  //二维指标数组
                }else{
                    $etprsapl[$key]['score'] = 0;
                    $etprsapl[$key]['fullScore'] = 0;
                    continue;
                }
                //获取全部的评分
                $finalScore = 0; //最终得分
                $fullScore = 0;  //指标设置的总分
                foreach($score as $key2=>$value1){
                    if(!empty($value1['child']) && is_array($value1['child'])){
                        $indexScore = 0;//指标的满分
                        $realScore = 0;//实际得分
                        foreach($value1['child'] as $key3=>$value2){
                            $indexScore += $value2['score'];
                            //统计所有导师给这个单项指标打的分，然后求出平均分
                            $map1 = array('aplId'=>$etprs['id'],'indexId'=>$value2['id'],'iqbtId'=>session('iqbtId'));
                            $scoreMsg = getDataList('etprsAplIndexScore',$map1,'a.adduserId,a.score','a.adduserId');
                            if($scoreMsg['code'] ==1 && !empty($scoreMsg['data'])){
                                //把二维数提取出一维数组，并求出平均值
                                $teaValue = i_array_column($scoreMsg['data'],'score');
                                $avg = array_sum($teaValue)/count($teaValue);
                                $realScore += $avg;
                            }else{
                                $realScore += 0;
                            }
                        }

                        $finalScore += ($realScore/$indexScore*$value1['score']); //realScore,由小指标计算出的实际得分，indexScore,由小指标计算出的满分,value1['score']，该大指标的分值
                    }else{
                        $finalScore += 0;
                    }
                    $fullScore += $value1['score'];
                }
                $etprsapl[$key]['fullScore'] = $fullScore;
                $etprsapl[$key]['score'] = $finalScore;

            }

        }
        return $etprsapl;
    }

    //同意入驻，入驻详情信息
    function enterAplInfo($id=0)
    {
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("etprsApl",array("a.id"=>$id),"b.name as etprsname,b.batch,a.*",$join);
        $data=array(); //申请信息
        $etprsId=0;
        $score = array(); //指标数组
        $total = array();
        $batch = '';
        if($msg["code"]=='1'){
            $data=$msg["data"];
            $tmplist=self::getDictStr("*","EtprsApl");
            $data=$this->setObjIdText($data,$tmplist);
            $etprsId=$data["etprsId"];
            $batch = $data['batch'];
        }

        //复审所有专家的备注信息
        $retrials=array();
        $join2 = [['user c','a.adduserId=c.id',"left"]];
        $aplmsg=getDataList("etprsAplNote",array("aplId"=>$id,'type'=>0,'a.iqbtId'=>session('iqbtId')),"a.adduserId,a.content,c.realname as userName","a.adduserId ",$join2);
        if($aplmsg["code"]==='1'){
            $retrials=$aplmsg["data"];
        }
        if(empty($retrials)){
            $res = array(
                'data'=>$data,   //申请信息
                'retrials'=>$retrials,  //复审备注数组
                'score'=>$score,   //指标各项评分信息
                'total'=>$total,   //导师汇总评分信息
                'etprsId'=>$etprsId,  //公司ID
            );
            return $res;
        }

        //查询所有指标
        $con = array(
            'batch'=>$batch,
            'parentId'=>0,
            'level'=>1,
            'iqbtId'=>session('iqbtId')
        );
        $msgKpi=getDataList("EtprsAplIndexBackup",$con,"a.id,a.indexId,a.score,a.title"," a.sort desc");
        if($msgKpi["code"]==="1"){
            //查询二级指标
            foreach($msgKpi['data'] as $k=>$value){
                $map = array(
                    'batch'=>$batch,
                    'parentId'=>$value['indexId'],
                    'level'=>2,
                    'iqbtId'=>session('iqbtId')
                );
                $conMsg = getDataList('EtprsAplIndexBackup',$map,'a.id,a.score,a.title');
                if($conMsg['code']==1){
                    $msgKpi['data'][$k]['child'] = $conMsg['data'] ;  //二级指标作为一级指标的child 参数
                    $msgKpi['data'][$k]['count'] = count($conMsg['data']);  //指标个数
                }else{
                    $msgKpi['data'][$k]['child'] = array();
                }
            }
            $score = $msgKpi['data'];
        }else{
            $res = array(
                'data'=>$data,   //申请信息
                'retrials'=>$retrials,  //复审备注数组
                'score'=>$score,   //指标各项评分信息
                'total'=>$total,   //导师汇总评分信息
                'etprsId'=>$etprsId,  //公司ID
            );
            return $res;
        }
        //按照指标查询导师评分
        foreach($score as $key=>$value1){
            if(!empty($value1['child']) && is_array($value1['child'])){
                foreach($value1['child'] as $key1=>$value2){
                    $map1 = array('aplId'=>$id,'indexId'=>$value2['id'],'iqbtId'=>session('iqbtId'));

                    $scoreMsg = getDataList('etprsAplIndexScore',$map1,'a.adduserId,a.score','a.adduserId');
                    if($scoreMsg['code'] ==1 && !empty($scoreMsg['data'])){
                        //把二维数组提取一维数组，并找出最大值、最小值和平均值
                        $teaKey = i_array_column($scoreMsg['data'],'adduserId');
                        $teaValue = i_array_column($scoreMsg['data'],'score');
                        $max = max($teaValue);
                        $min = min($teaValue);
                        $avg = array_sum($teaValue)/count($teaValue);
                        $teacher = array_combine($teaKey,$teaValue);
                        $teacher['max'] = $max;
                        $teacher['min'] = $min;
                        $teacher['avg'] = $avg;
                        $score[$key]['child'][$key1]['teacher'] = $teacher;
                    }else{
                        $score[$key]['child'][$key1]['teacher'] = array();
                    }

                }
            }

        }
        $tecTotal = array();
        if(!empty($retrials)){
            foreach($retrials as $val){
                $tecTotal[$val['adduserId']] = 0;  //计算每一个导师的最终的总得分
            }
            foreach($score as $val2){
                $full = $val2['score']; //大指标的设置分数
                $infull = 0; //该大指标下小指标的总分
                //每一个导师的二级指标得分，大指标循环时得清零
                foreach($retrials as $val6){
                    $tecScore[$val6['adduserId']] = 0;
                }
                foreach($val2['child'] as $val3){ //每一个小指标的情况
                    $infull += $val3['score'];
                    //计算每一个导师每一项小指标的得分
                    foreach($tecScore as $adduserId =>$v){
                        if(isset($val3['teacher'][$adduserId])){
                            $tecScore[$adduserId] += $val3['teacher'][$adduserId];
                        }else{
                            $tecScore[$adduserId] +=0;
                        }
                    }
                }

                foreach($tecTotal as $userId =>$v1){
                    $tecTotal[$userId] += $tecScore[$userId]/$infull*$full; //计算一项大指标的得分，然后相加
                }
            }
            $total = $tecTotal;  //导师汇总的得分数组
            $total['max'] = max($tecTotal);
            $total['min'] = min($tecTotal);
            $total['avg'] = array_sum($tecTotal)/count($tecTotal);
        }else{
            $total = array();
            $total['max'] = 0;
            $total['min'] = 0;
            $total['avg'] = 0;
        }
        $res = array(
            'data'=>$data,   //申请信息
            'retrials'=>$retrials,  //复审备注数组
            'score'=>$score,   //指标各项评分信息
            'total'=>$total,   //导师汇总评分信息
            'etprsId'=>$etprsId,  //公司ID
        );
        return $res;
    }

    //同意入驻，同意或者拒绝操作
    function saveEnterNote($aplId='',$content='',$status='',$sms='0')
    {

        $aplmsg=findById("etprsApl",array("id"=>$aplId),"etprsId,id");
        if($aplmsg["code"]==='1'){
            $etprsId=$aplmsg["data"]["etprsId"];
        }

        $note["etprsId"]=$etprsId;
        $note["content"]=$content;
        $note["aplId"]=$aplId;
        $note["status"]="1001013";
        $note["iqbtId"]=session("iqbtId");
        $note["addtime"]=time();
        $note["type"]=2;
        $note["adduserId"]=session("userId");
        saveData("etprsAplNote",$note,"复审备注");

        $etprsStatus=self::getNextStatus($status);
        $msg=saveDataByCon("enterprise",array("status"=>$etprsStatus),array("id"=>$etprsId));
        if($msg['code']==1){
            switch($status){
                case '1001014' :$info=array('type'=>'同意入驻审核','msg'=>'通过');$status1 = "同意入驻";break;
                case '1001000': $info=array('type'=>'同意入驻审核','msg'=>'被拒绝'); $status1 = "拒绝入驻";break;
                default : $info=array('type'=>'未知类型','msg'=>' '); $status1 = "未知状态";
            }

            $smsData = array();
            if($sms =="1"){
                $tpl_id = config('sms_tpl_id.check');
                $smsData = array(
                    'tpl'=>$tpl_id,
                    'data'=>$info,
                );
            }
            $wxData = array();
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>'同意入驻审核',
                        'keyword2'=>$status1,
                    ),
                    'first'=>'系统审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            $emailData =  array(
                'title'=>'同意入驻审核',
                'content'=>'尊敬的客户您好：您的同意入驻审核结果：'.$status1.',理由：'.$content,
                'type'=>'1020002',
            );
            //给企业负责人发送消息
            $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsId),'id');
            $this->sendAllMsg($uid,$emailData,$wxData,$smsData);
        }

        //工作日志
        $logData = array(
            'etprsId'=>$etprsId,
            'fmenuId'=>7,
            'smenuId'=>11,
            'content'=>'审核结果：'.$status1.',理由：'.$content,
        );
        saveOaLog($logData);

        //给下一个操作发送消息通知#
        $typeArr = array(
            '1001011'=>array('1','8','材料初审：有新的任务需要处理'),   //第一个代表消息的二级类型:stype    第二个代表菜单ID
            '1001012'=>array('2','9','复审通知：有新的任务需要处理'),
            '1001013'=>array('4','11','同意入驻：有新的任务需要处理'),
            '1001014'=>array('5','12','房间分配：有新的任务需要处理'),
            '1001015'=>array('6','13','签约入驻：有新的任务需要处理'),
        );
        //给下一个流程发通知
        if(isset($typeArr[$status])){
            $adminIds = getAdminIds($typeArr[$status][1],false);
            //自己不给自己发
            $selfId = array(session('userId'));
            $adminIds = array_diff($adminIds,$selfId);
            $adminmsg = array(
                'title'=>$typeArr[$status][2],
                'content'=>$typeArr[$status][2],
                'type'=>'1020002',
                'relTable'=>'enterprise',
                'relId'=>$etprsId,
                'stype'=>$typeArr[$status][0]
            );
            $this->sendAllMsg($adminIds,$adminmsg);

        }


        if($msg['code']=="1"){
            return array('code'=>1,'msg'=>'1','data'=>array());
        }else{
            return $msg;
        }
    }


    //分配房间，获取待分配的房间列表
    function getRoomDitbApl($etprs="",$contact="",$apltype="",$type="apl")
    {
        if ($type == "apl") {
            $con1 = array("a.status" => "1027002");
            $con2 = array("a.status" => "1001014");
        } else if ($type == "pass") {
            //审核通过
            $con1 = array("a.status" => "1027003");
            $con2 = array("a.status" => "1001015");
        } else {
            $con1 = array("1" => "-1");
            $con2 = array("1" => "-1");
        }
        $con1["a.iqbtId"] = session("iqbtId");
        $con2["a.iqbtId"] = session("iqbtId");
        $roomapl = array();
        if ($apltype == "seated" || empty($apltype)) {
            if (!empty($etprs)) {
                $con1["b.name"] = array("like", "%" . $etprs . "%");
            }
            if (!empty($contact)) {
                $con1["b.contact"] = array("like", "%" . $contact . "%");
            }
            $join1 = [['enterprise b', 'a.etprsId=b.id', "left"], ["etprsApl c", 'a.etprsId=c.etprsId', "left"]];
            $roomaplmsg = getDataList("etprsAplRoom", $con1, "a.id,b.`name` as etprsName,'roomapl' as apltype,b.contact,a.mobile,c.industry,c.workstyle,'seated' as type,a.etprsId", "a.addtime desc", $join1);
            if (!empty($roomaplmsg["data"])) {
                $roomapl = $roomaplmsg["data"];
                $tmplist = self::getDictStr("*", "EtprsApl");
                $roomapl = $this->setListIdText($roomapl, $tmplist);
            }
        }
        //入驻申请
        $etprsapl = array();
        if ($apltype != "seated") {
            $join2 = [['etprs_apl b', 'a.id=b.etprsId', "right"]];

            if (!empty($etprs)) {
                $con2["a.name"] = array("like", "%" . $etprs . "%");
            }
            if (!empty($contact)) {
                $con2["a.contact"] = array("like", "%" . $contact . "%");
            }
            if ($apltype != "") {
                $con2["b.type"] = $apltype;
            }
            $etprsaplmsg = getDataList("enterprise", $con2, "b.id,a.`name` as etprsName,b.apltype,b.type,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId", "b.addtime desc", $join2);
            if (!empty($etprsaplmsg["data"])) {
                $etprsapl = $etprsaplmsg["data"];
                $tmplist = self::getDictStr("*", "EtprsApl");
                $etprsapl = $this->setListIdText($etprsapl, $tmplist);
            }
        }
        $result = array_merge($roomapl, $etprsapl);
        for ($i = 0; $i < count($result); $i++) {
            $etprsId = $result[$i]["etprsId"];
            $roomNos = "";
            $msg = getDataList("EstateRoom", array("etprsId" => $etprsId), "id,roomNo");
            if (!empty($msg["data"])) {
                foreach ($msg["data"] as $no) {
                    $roomNos .= "," . $no["roomNo"];
                }
            }
            $roomNos = trim($roomNos, ",");
            $result[$i]["roomNos"] = $roomNos;
        }
        return $result;
    }

    /**
     * 初始化楼层
     * @param string $id  楼的ID
     * @return array|null 返回楼的ID 和楼层数
     */
    function initFloor($id="")
    {
        $msg=findById("EstateBuilding",array("id"=>$id),"id,floor");
        return $msg;
    }


    /**
     * 初始化一层楼的房间
     * @param string $id 楼的ID
     * @param string $floor  楼层号
     * @return array
     */
    function initFloorRoom($id="",$floor="")
    {
        $join1 = [['enterprise b','a.etprsId=b.id',"left"],['estateRoomEtprs c','c.roomId=a.id and c.status<>2',"left"]];
        $msg=getDataList("EstateRoom",array("buildId"=>$id,"floor"=>$floor,'a.iqbtId'=>session("iqbtId")),"a.id,a.type,a.floor,a.roomNo,a.totalarea,b.name as etprsName,a.etprsId,a.status,c.endTime","a.roomNo desc",$join1);
        $data=$msg["data"];
        for ($i = 0; $i < count($data); $i++) {
            $type = $data[$i]['type'];
            if($type ==1){
                $data[$i]['typeStr'] = "办公室";
            }else{
                $data[$i]['typeStr'] = "工&nbsp;&nbsp;&nbsp;&nbsp;位";
            }
            $status=$data[$i]["status"];
            if($status=="2"){
                $endtime=$data[$i]["endTime"];
                $now=time();

                //如果过期了，显示待续费，待续费的可以直接释放（该功能为了解决一些企业只续期部分房间，不续约的房间得释放掉
                if($endtime-$now <0){
                    $data[$i]['status']=3;
                }
            }

        }
        $msg["data"]=$data;
        return $msg;
    }

    /**
     * 单个房间的信息，包括基本信息和缴费项目
     * @param $id   房间的ID
     *
     * @return array
     */
    function initetprsroom($id)
    {
        $data=array();
        $join1 = [['estate_room_etprs b','a.id=b.roomId and a.etprsId=b.etprsId',"left"],['enterprise c','a.etprsId=c.id',"left"]];
        $msg=getDataList("EstateRoom",array("a.id"=>$id),"c.name as etprsName,a.id,a.type,a.status,a.feeOptIds,a.totalarea,a.roomNo,b.starttime,b.endtime","b.endtime desc",$join1);

        if(!empty($msg["data"])){
            $data=$msg["data"][0];
            //房间的缴费类型
            $optName = '';
            if($data['type']==1){
                if(!empty($data['feeOptIds'])){

                    $feeOpt = getFieldArrry('FeeItemOpt',array('id'=>array('in',$data['feeOptIds'])),'name');
                    if(!empty($feeOpt)){
                        $optName = implode(",",$feeOpt);
                    }
                }
            }
            //如果房间已过期，状态变成3
            $now = time();
            if($data['endtime']){
                if($data['endtime']-$now <0){
                    $data['status'] = 3;
                }
            }
            $data['optName'] = $optName;
            if(!empty($data['starttime'])){
                $data['starttime']=date("Y-m-d",$data['starttime']);
            }
            if(!empty($data['endtime'])){
                $data['endtime']=date("Y-m-d",$data['endtime']);
            }
            if($data['type'] ==1){
                $data['typeStr'] = "办公室";
            }else{
                $data['typeStr'] = "工&nbsp;&nbsp;&nbsp;&nbsp;位";
            }
        }
        $msg["data"]=$data;
        return $msg;
    }

    /**
     * 未分配的房间，点击后显示分配页面
     * @param $id  房间ID
     * @param $etprsId  企业ID，得知道给哪个企业分配
     * @return array
     */
    function initemptyroom($id='',$etprsId='')
    {

        $etprsmsg=findById("enterprise",array("id"=>$etprsId),"name");
        if(!empty($etprsmsg["data"])){
            $etprsname=$etprsmsg["data"]["name"];
        }else{
            $etprsname = '';
        }
        $msg=getDataList("EstateRoom",array("id"=>$id),"id,type,totalarea,roomNo");
        if(!empty($msg['data'])){
            $msg["data"][0]["etprsname"]=$etprsname;
            if( $msg["data"][0]['type'] ==1){
                $msg["data"][0]['typeStr'] = "办公室";
            }else{
                $msg["data"][0]['typeStr'] = "工&nbsp;&nbsp;&nbsp;&nbsp;位";
            }
            $msg["data"]=$msg["data"][0];
        }else{
            $msg = array('code'=>'0','msg'=>'查询错误','data'=>array());
        }
        return $msg;
    }

    /**
     * 分配操作，保存分配房间
     * @param $data  要保存的数据数组
     * @return array
     */
    function dstbEtprsRoom($data=array()){
        $postData['startTime'] = $data['startTime'];
        $postData['endTime'] = $data['endTime'];
        $postData['roomId'] = $data['roomId'];
        $postData['etprsId'] = $data['etprsId'];
        if(isset($data['optId'])){
            $optIds = trim(implode(",",$data['optId']),",");
        }else{
            $optIds='';
        }
        $iqbtId=session("iqbtId");
        $userId=session("userId");
        $postData["adduserId"]=$userId;
        $postData["iqbtId"]=$iqbtId;
        $postData["addtime"]=time();
        $postData["startTime"]=strtotime($postData["startTime"]);
        $postData["endTime"]=strtotime($postData["endTime"]);
        if($postData['startTime']>=$postData['endTime']){
            return array('code'=>0,'msg'=>'开始时间不能大于结束时间','data'=>array());
        }

        $chkmsg=findById("estateRoomEtprs",array("roomId"=>$postData["roomId"],'status'=>array("<>",'2')),"id");
        if(!empty($chkmsg['data'])){
            return array('code'=>0,'msg'=>'该房间已经分配使用');
        }
        $msg= saveData("estateRoomEtprs",$postData,"分配房间");
        if($msg["code"]==='1'){
            saveDataByCon("EstateRoom",array("etprsId"=>$postData["etprsId"],"status"=>1,'feeOptIds'=>$optIds),array("id"=>$postData["roomId"]));
        }
        //工作日志
        $join = [['estate_building b','b.id=a.buildId']];
        $roomMsg = findById('estateRoom',array('a.id'=>$postData['roomId']),'a.floor,a.roomNo,b.name',$join);
        if($roomMsg['code']==1 && !empty($roomMsg['data'])){
            $roominfo = $roomMsg['data']['name'].$roomMsg['data']['floor'].'层'.$roomMsg['data']['roomNo'];
        }else{
            $roominfo = '';
        }
        $logData = array(
            'etprsId'=>$postData["etprsId"],
            'fmenuId'=>7,
            'smenuId'=>12,
            'objId'=>$postData['roomId'],
            'content'=>'分配房间：'.$roominfo,
        );
        saveOaLog($logData);
        return $msg;
    }

    /**
     * 分配未使用的房间，重置 ； 过期的房间，释放
     * @param string $roomid  房间ID
     * @return array
     */
    function roomCancel($roomid='')
    {

        //查找该房间对应的企业
        $join = [['estate_building b', 'b.id=a.buildId']];
        $roomMsg = findById('estateRoom', array('a.id' =>$roomid), 'a.floor,a.etprsId,a.roomNo,b.name', $join);
        if ($roomMsg['code'] == 1 && !empty($roomMsg['data'])) {
            $etprsId = $roomMsg['data']['etprsId'];
            $roomNum = $roomMsg['data']['name'] . $roomMsg['data']['floor'] . '层' . $roomMsg['data']['roomNo'];
        } else {
            $etprsId = 0;
            $roomNum = '';
        }
        $data = array(
            'isDelete' => 1,
            'status' => 2,
        );
        $res = saveDataByCon('estateRoomEtprs', $data, array("roomId" => $roomid, 'etprsId' => $etprsId));
        if ($res['code'] == 1) {
            $msg = saveDataByCon('estateRoom', array('etprsId' => 0, 'status' => 0, 'feeOptIds' => ''), array('id' => $roomid, 'iqbtId' => session('iqbtId')));
            if ($msg['code'] == 1) {
                //发站内信
                $uid = getField('user', array('userCate' => '1011002', 'etprsId' => $etprsId), 'id');
                if ($uid) {
                    $emailData = array(
                        'title' => '房间取消释放',
                        'content' => '尊敬的客户您好：您已分配的房间' . $roomNum . '被管理员取消释放，详情请联系管理员',
                        'type' => '1020002',
                    );
                    $this->sendAllMsg($uid, $emailData, array(), array());
                }
                //工作日志
                $logData = array(
                    'etprsId' => $etprsId,
                    'fmenuId' => 7,
                    'smenuId' => 12,
                    'objId' => $roomid,
                    'content' => '释放房间：' . $roomNum,
                );
                saveOaLog($logData);
                return array('code' => '1', 'msg' => '取消成功', 'data' => array());
            } else {
                return $msg;
            }

        } else {
            return $res;
        }
    }

    //签约入驻列表，
    function getEnterApl($etprs="",$contact="",$apltype="",$status="1001015")
    {
        $con2=array("a.status"=>$status);
        //入驻申请
        $etprsapl=array();
        $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];

        if(!empty($etprs)){
            $con2["a.name"]=array("like","%".$etprs."%");
        }
        if(!empty($contact)){
            $con2["a.contact"]=array("like","%".$contact."%");
        }
        if($apltype!=""){
            $con2["b.type"]=$apltype;
        }
        $con1["a.iqbtId"]=session("iqbtId");
        $con2["a.iqbtId"]=session("iqbtId");
        $etprsaplmsg=getDataList("enterprise",$con2,"b.id,a.`name` as etprsName,b.apltype,b.type,a.contact,a.mobile,b.industry,b.workstyle,b.etprsId","b.addtime desc",$join2);
        if(!empty($etprsaplmsg["data"])){
            $etprsapl=$etprsaplmsg["data"];
            $tmplist=self::getDictStr("*","EtprsApl");
            $etprsapl=$this->setListIdText($etprsapl,$tmplist);
        }
        for ($i = 0; $i < count($etprsapl); $i++) {
            $etprsId=$etprsapl[$i]["etprsId"];
            $roomNos="";
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.=",".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,",");
            $etprsapl[$i]["roomNos"]=$roomNos;
        }
        return $etprsapl;
    }

    /**
     * 签约入驻阶段的取消入驻
     * @param string $id  企业ID
     * @param string $sms  短信状态，1为发送短信，2为不发送
     * @return array
     */
    function setCancel($id='',$sms="2"){

        //查找企业分配的房间，全部清空，企业状态改为拒绝状态
        $data = array(
            'isDelete'=>1,
            'status'=>2,
        );
        saveDataByCon('estateRoomEtprs',$data,array("etprsId"=>$id,'iqbtId'=>session('iqbtId')));
        saveDataByCon('estateRoom',array('etprsId'=>0,'status'=>0,'feeOptIds'=>''),array('etprsId'=>$id,'iqbtId'=>session('iqbtId')));
        $msg=saveDataByCon('enterprise',array("status"=>'1001000'),array("id"=>$id));
        if($msg['code']==1){
            //发短信
            $smsData = array();
            if($sms =="1"){
                $info = array(
                    'type'=>'签约入驻',
                    'msg'=>'被取消入驻'
                );
                $tpl_id = config('sms_tpl_id.check');
                $smsData = array(
                    'tpl'=>$tpl_id,
                    'data'=>$info
                );
            }
            //发站内信
            $emailData = array(
                'title'=>'签约人驻',
                'content'=>'尊敬的客户您好：您在签约入驻环节被管理员取消入驻,详情请联系管理员',
                'type'=>'1020002',
                'addtime'=>time()
            );
            $wxData = array();
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>'签约入驻',
                        'keyword2'=>'入驻资格被取消,详情请联系管理员'
                    ),
                    'first'=>'系统审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$id),'id');
            if($uid){
                $this->sendAllMsg($uid,$emailData,$wxData,$smsData);
            }
            //工作日志
            $logData = array(
                'etprsId'=>$id,
                'fmenuId'=>7,
                'smenuId'=>13,
                'content'=>'入驻资格被取消'
            );
            saveOaLog($logData);
            return array('code'=>'1','msg'=>'操作成功','data'=>array());
        }else{
            return $msg;
        }
    }

    /**
     * 签约入驻，确定入驻操作，设置入驻的时间
     * @param $etprsId
     * @return array()
     */
    function setEnterTime($etprsId='')
    {
        $msg=getDataList("estateRoomEtprs",array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId")),"startTime","startTime asc");
        $ettime=date("Y-m-d",time());
        if(!empty($msg["data"])){
            $ettime=date("Y-m-d",$msg["data"][0]["startTime"]);
        }

        $msg2=getDataList("estateRoomEtprs",array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId")),"endTime","endTime desc");
        $qttime=date("Y-m-d",strtotime("+1 years",time()));

        if(!empty($msg2["data"])){
            $qttime=date("Y-m-d",$msg2["data"][0]["endTime"]);
        }
        $data = array(
            'etprsId'=>$etprsId,
            'entertime'=>$ettime,
            'pactquittime'=>$qttime,
        );
        return $data;
    }

    /**
     * 签约入驻，保存入驻时间
     * @param string $etprsId 企业ID
     * @param string $ettime 入驻开始时间
     * @param string $qttime  合同到期时间
     * @return array
     */
    function saveEnterTime($etprsId='',$ettime='',$qttime='',$sms='0'){

        $msg=saveDataByCon("enterprise",array("entertime"=>$ettime,"status"=>'1001016','pactquittime'=>$qttime),array("id"=>$etprsId));
        if($msg["code"]=='1'){

            $rmsg=getDataList("estateRoom",array("etprsId"=>$etprsId),"id");
            $ids="";
            if(!empty($rmsg["data"])){
                saveDataByCon("estateRoom",array("status"=>2),array("etprsId"=>$etprsId));
                foreach ($rmsg["data"] as $d) {
                    $ids=$ids.",".$d["id"];
                }
                $ids=trim($ids,",");
            }
            saveDataByCon("estateRoomEtprs",array("status"=>1),array("roomId"=>array("in",$ids),"etprsId"=>$etprsId,'status'=>0));
            //发送站内信
            $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsId),'id');
            if($uid){
                //发送站内信
                $emailData = array(
                    'title'=>'签约入驻',
                    'content'=>'恭喜您已成功签约入驻孵化器，入驻开始时间：'.date("Y-m-d",$ettime).',结束时间:'.date("Y-m-d",$qttime),
                    'type'=>'1020002',
                    'addtime'=>time()
                );
                $wxData = array();
                if(config('open_wechat')){
                    $template_id = config('wx_tpl.change');
                    $wxData = array(
                        'tpl'=>$template_id,
                        'data'=>array(
                            'keyword1'=>'签约入驻',
                            'keyword2'=>'成功入驻，入驻开始时间：'.date("Y-m-d",$ettime).',结束时间:'.date("Y-m-d",$qttime),
                        ),
                        'first'=>'系统审核通知',
                        'remark'=>'请登录系统，查看详情'
                    );
                }
                $smsData = array();
                if($sms =="1"){
                    $tpl_id = config('sms_tpl_id.check');
                    $smsData = array(
                        'tpl'=>$tpl_id,
                        'data'=>array(
                            'type'=>'签约入驻',
                            'msg'=>'正式入驻，入驻开始时间'.date("Y-m-d",$ettime).',结束时间:'.date("Y-m-d",$qttime),
                        ),
                    );
                }
                $this->sendAllMsg($uid,$emailData,$wxData,$smsData);
            }
            //工作日志
            $logData = array(
                'etprsId'=>$etprsId,
                'fmenuId'=>7,
                'smenuId'=>13,
                'content'=>'签约入驻通过，入驻开始时间'.date("Y-m-d",$ettime).',结束时间:'.date("Y-m-d",$qttime),
            );
            saveOaLog($logData);
        }
        return $msg;
    }


    /**
     * 企业端查看复审信息
     * @param string $id 申请ID
     * @return \think\response\View
     */
    function gradeInfo($id=''){
        //复审所有专家的备注信息
        $retrials=array();
        $join2 = [['user c','a.adduserId=c.id',"left"]];
        $aplmsg=getDataList("etprsAplNote",array("aplId"=>$id,'type'=>0),"a.adduserId,a.content,c.realname as userName","a.adduserId ",$join2);
        if($aplmsg["code"]==='1'){
            $retrials=$aplmsg["data"];
        }
        if(empty($retrials)){
            $score = array();
            $total = array();
            return array("retrials"=>$retrials,'score'=>$score,'total'=>$total);
        }
        $backjoin = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("etprsApl",array("a.id"=>$id),"b.batch,a.etprsId",$backjoin);

        $data=array();
        $batch = '';
        if($msg["code"]=='1'){
            $data=$msg["data"];
            $batch = $data['batch'];
        }

        //查询所有指标
        $con = array(
            'batch'=>$batch,
            'parentId'=>0,
            'level'=>1,
            'iqbtId'=>session('iqbtId')
        );
        $msgKpi=getDataList("EtprsAplIndexBackup",$con,"a.id,a.indexId,a.score,a.title"," a.sort desc");
        if($msgKpi["code"]==="1"){
            //查询二级指标
            foreach($msgKpi['data'] as $key=>$value){
                $map = array(
                    'batch'=>$batch,
                    'parentId'=>$value['indexId'],
                    'level'=>2,
                    'iqbtId'=>session('iqbtId')
                );
                $conMsg = getDataList('EtprsAplIndexBackup',$map,'a.id,a.score,a.title');
                if($conMsg['code']==1){
                    $msgKpi['data'][$key]['child'] = $conMsg['data'] ;
                    $msgKpi['data'][$key]['count'] = count($conMsg['data']);
                }else{
                    $msgKpi['data'][$key]['child'] = array();
                }
            }
            $score = $msgKpi['data'];
        }else{
            $score = array();
            $total = array();
            return array("retrials"=>$retrials,'score'=>$score,'total'=>$total);
        }
        //按照指标查询导师评分
        foreach($score as $key=>$value1){
            if(!empty($value1['child']) && is_array($value1['child'])){
                foreach($value1['child'] as $key1=>$value2){
                    $map1 = array('aplId'=>$id,'indexId'=>$value2['id']);

                    $scoreMsg = getDataList('etprsAplIndexScore',$map1,'a.adduserId,a.score','a.adduserId');
                    if($scoreMsg['code'] ==1 &&(!empty($scoreMsg['data']))){

                        //把二维数组转换成以为数组，并找出最大值、最小值和平均值
                        $teaKey = i_array_column($scoreMsg['data'],'adduserId');
                        $teaValue = i_array_column($scoreMsg['data'],'score');
                        $max = max($teaValue);
                        $min = min($teaValue);
                        $avg = array_sum($teaValue)/count($teaValue);
                        $teacher = array_combine($teaKey,$teaValue);
                        $teacher['max'] = $max;
                        $teacher['min'] = $min;
                        $teacher['avg'] = $avg;
                        $score[$key]['child'][$key1]['teacher'] = $teacher;
                    }else{
                        $score[$key]['child'][$key1]['teacher'] = array();
                    }

                }
            }

        }
        foreach($retrials as $val){
            $tecScore2[$val['adduserId']] = 0;
        }
        foreach($score as $val2){
            $full = $val2['score']; //大指标满分40分
            $infull = 0;
            foreach($retrials as $val6){
                $tecScore[$val6['adduserId']] = 0;
            }

            foreach($val2['child'] as $val3){
                $infull += $val3['score'];
                foreach($retrials as $val4){
                    $tecScore[$val4['adduserId']] += $val3['teacher'][$val4['adduserId']];
                }
            }

            foreach($retrials as $val5){
                $tecScore2[$val5['adduserId']] += $tecScore[$val5['adduserId']]/$infull*$full; //一个大指标的得分
            }
        }
        $total = $tecScore2;
        $total['max'] = max($tecScore2);
        $total['min'] = min($tecScore2);
        $total['avg'] = array_sum($tecScore2)/count($tecScore2);

        $res = array(
            'retrials'=>$retrials,
            'score'=>$score,
            'total'=>$total,
        );
        return $res;
    }

    /**
     * 导师分配，保存分配的导师
     * @param $etprsId  企业ID
     * @param $tutorIds 导师ID，字符串形式，用逗号隔开
     * @return array
     */
    function saveEtprsTutor($etprsId,$tutorIds)
    {
        return saveDataByCon("enterprise",array("tutorIds"=>$tutorIds),array("id"=>$etprsId));
    }

    /**
     * 签约入驻，获取合同列表
     * @param int $etprsId 企业ID
     * @return array
     */
    function getEtprsPact($etprsId=0)
    {
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=getDataList("pact",array("a.etprsId"=>$etprsId,'a.iqbtId'=>session("iqbtId")),"a.*,b.name as etprsName","a.addtime desc",$join);
        if($msg["code"]==="1"){
            if(!empty($msg["data"])){
                for ($i = 0; $i < count($msg['data']); $i++) {
                    $msg['data'][$i]["addtime"]=date("Y-m-d H:i:s",$msg['data'][$i]["addtime"]);
                    $fileIds=$msg['data'][$i]["filesId"];
                    $msg['data'][$i]["files"]=array();
                    if(!empty($fileIds)){
                        $fmsg=getDataList("sysFile",array("id"=>array("in",$fileIds)),"fileName,savePath","id asc");
                        if(!empty($fmsg["data"])){
                            $msg['data'][$i]["files"]=$fmsg["data"];
                        }
                    }
                }
            }else{
                return array();
            }
            //print_r($msg['data']);exit();
            return $msg["data"];
        }else{
            return array();
        }
    }

    /**
     * 签约入驻，删除合同
     * @param $id 合同ID
     * @return bool|int
     */
    function deltPact($id)
    {
        $pactMsg = findById('pact',array('id'=>$id),'*');
        if($pactMsg['code']==1){
            $data = $pactMsg['data'];
        }
        $res = deleteData("pact",$id,"删除合同");
        if($res['code']==1){
            //工作日志
            $logData = array(
                'etprsId'=>$data['etprsId'],
                'fmenuId'=>7,
                'smenuId'=>13,
                'content'=>'删除了合同：'.$data['name'],
            );
            saveOaLog($logData);
        }
        return $res;
    }

    /**
     * 续约管理 获取续约的列表
     * @param string $status  状态，1027001：待处理 1027002：已通过
     * @return array
     */
    function getRenewApl($status='1027001')
    {

        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $con=array("a.status"=>$status,'a.iqbtId'=>session("iqbtId"));
        $msg=getDataList("etprsAplRenew",$con,"a.*,b.name as etprsName","a.addtime desc",$join);
        if($msg["code"]==="1"){
            if(!empty($msg["data"])){
                for ($i = 0; $i < count($msg['data']); $i++) {
                    $msg['data'][$i]["addtime"]=date("Y-m-d",$msg['data'][$i]["addtime"]);
                    $msg['data'][$i]["startTime"]=date("Y-m-d",$msg['data'][$i]["startTime"]);
                    $msg['data'][$i]["endTime"]=date("Y-m-d",$msg['data'][$i]["endTime"]);
                    $tmplist=self::getDictStr("*","EtprsAplRenew");
                    $msg['data']=$this->setListIdText($msg['data'],$tmplist);
                }
            }else{
                return array();
            }

            return $msg["data"];
        }else{
            return array();
        }
    }

    /**
     * 续约管理，查看续约详情信息
     * @param int $id  续约申请的ID
     * @return \think\response\View
     */
    function renewdetail($id=0)
    {
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("etprsAplRenew",array("a.id"=>$id),"b.name as etprsname,b.rgsttime,b.lealPerson,a.*",$join);
        $data=array();
        if($msg["code"]==='1'){
            $data=$msg["data"];
        }
        return $data;
    }

    /**
     * 续约管理，通过或者拒绝操作
     * @param $table 要操作的表,就是etprsAplRenew表，由于电脑端原因，这个参数暂时留着
     * @param $id  申请ID
     * @param $status 操作状态 1027000 拒绝  1027002 通过
     * @param string $sms 是否短信通知
     * @return array
     */
    function setRenewStatus($table,$id,$status,$sms="0")
    {
        if(empty($id)){
            return array('code'=>0,'msg'=>'参数错误','data'=>array());
        }
        $msg=saveDataByCon($table,array("status"=>$status),array("id"=>$id));
        if($msg['code']==1){
            if($status=='1027002'){
                //企业合同退出时间修改为新时间
                //相关入驻房间时间修改为新时间
                $aplmsg=findById("etprsAplRenew",array('id'=>$id),"startTime,endTime,roomNo,etprsId");

                if(!empty($aplmsg["data"])){
                    $quitTime=$aplmsg["data"]["endTime"];
                    $roomNo=$aplmsg["data"]["roomNo"];
                    $etprsId=$aplmsg["data"]["etprsId"];
                    $msg2=saveDataByCon("enterprise",array("pactquittime"=>$quitTime,'renewStatus'=>1),array("id"=>$etprsId));
                    $roomIds=getFieldArrry("estateRoom",array('roomNo'=>array("in",explode(",",$roomNo))),"id");

                    //续约时房间的缴费状态更改为续约的缴费
                    $join = [['fee_item b','a.itemId=b.id',"left"]];
                    $imsg=getDataList("feeItemCfg",array("a.feetype"=>"1030002",'a.iqbtId'=>session("iqbtId"),'b.about'=>'1'),"a.id,a.itemId,a.optId,b.name as itemName","",$join);
                    $items=array();
                    if(!empty($imsg["data"])){
                        $items=$imsg["data"];
                    }
                    $feeOptIds = '';
                    foreach($items as $value){
                        $feeOptIds .= $value['optId'].',';
                    }
                    $feeOptIds = trim($feeOptIds,",");
                    //续约以后，结束时间变成续约的结束时间
                    saveDataByCon("estateRoomEtprs",array("endTime"=>$quitTime),array('iqbtId'=>session("iqbtId"),"etprsId"=>$etprsId,'roomId'=>array('in',$roomIds),'status'=>array('in',[0,1,3]),'startTime'=>array('lt',time()),'endTime'=>array('gt',time())));
                    saveDataByCon('estateRoom',array('feeOptIds'=>$feeOptIds),array('id'=>array('in',$roomIds)));
                }else{
                }
            }
            //给企业负责人发送微信通知
            switch($status){
                case '1027002' :$info = array('type'=>'续约申请','msg'=>'通过');$link = '续约申请';$status1 = "审核通过";break;
                case '1027000' :$info = array('type'=>'续约申请','msg'=>'被拒绝'); $link = '续约申请';$status1 = '被拒绝';break;
                default:$info = array('type'=>'续约申请','msg'=>'');$link = '';$status1 ='';
            }

            //发送站内消息
            $emailData = array(
                'title'=>$link.'审核结果',
                'content'=>'尊敬的客户您好：您的'.$link.$status1.',详情请查看相应栏目信息',
                'type'=>'1020002',
            );
            $wxData = array();
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$link,
                        'keyword2'=>$status1,
                    ),
                    'first'=>'续约审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            $smsData = array();
            if($sms =="1"){
                $tpl_id = config('sms_tpl_id.check');
                $smsData = array(
                    'tpl'=>$tpl_id,
                    'data'=>$info,
                );
            }
            if($table =="EtprsAplRenew"){
                $etprsid = getField($table,array('id'=>$id),'etprsId');
            }else{
                $etprsid = $id;
            }
            if($etprsid){
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsid),'id');
            }
            if($uid){
                $this->sendAllMsg($uid,$emailData,$wxData,$smsData);
            }
            //工作日志:
            $logData = array(
                'etprsId'=>$etprsid,
                'fmenuId'=>15,
                'smenuId'=>36,
                'objId'=>$id,
                'content'=>$link.$status1,
            );
            saveOaLog($logData);
            return array('code'=>'1','msg'=>'操作成功','data'=>array());

        }else{
            return $msg;
        }
    }


    /**
     * 退出管理 获取退出企业列表
     * @param string $status 状态：1028001：管理员待审核，1028002：物业待审核, 1028003:财务待审核， 1028004：待退款（所有审核都通过的）
     * @return array
     */
    function getQuitApl($status="1028001")
    {
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $con=array("a.status"=>$status,'a.iqbtId'=>session("iqbtId"));
        $msg=getDataList("etprsAplQuit",$con,"a.*,b.name as etprsName,b.entertime,b.rgsttime,b.lealPerson","a.addtime desc",$join);
        if($msg["code"]==="1"){
            if(!empty($msg["data"])){
                for ($i = 0; $i < count($msg['data']); $i++) {
                    $msg['data'][$i]["addtime"]=date("Y-m-d ",$msg['data'][$i]["addtime"]);
                    $msg['data'][$i]["entertime"]=date("Y-m-d",$msg['data'][$i]["entertime"]);
                    $tmplist=self::getDictStr("*","EtprsAplQuit");
                    $msg['data']=$this->setListIdText($msg['data'],$tmplist);

                }
            }else{
                return array();
            }
            return $msg["data"];
        }else{
            return array();
        }
    }

    /**退出管理 查看退出详情
     * @param int $id 退出申请ID
     * @return array
     */
    function quitdetail($id=0)
    {
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        $msg=findById("etprsAplQuit",array("a.id"=>$id),"b.name as etprsname,b.entertime,b.rgsttime,b.lealPerson,a.*",$join);
        $data=array();
        if($msg["code"]==='1'){
            $data=$msg["data"];
            $data["entertime"]=date("Y-m-d",$data["entertime"]);
        }
        return $data;
    }

    /**
     * 退出管理,管理员审核通过或者拒绝
     * @param int $id  退出申请ID
     * @param string $status 状态：1028000：拒绝 , 1028002：通过
     * @return array
     */
    function setQuitApl($id='',$status="")
    {
        $msg=findById("etprsAplQuit",array("id"=>$id),"etprsId,id");
        $etprsName="";//企业名字
        if(!empty($msg["data"])){
            $etprsId=$msg["data"]["etprsId"];
            $emsg=findById("enterprise",array("id"=>$etprsId),"id,name");
            if(!empty($emsg["data"])){
                $etprsName=$emsg["data"]["name"];
            }
        }
        $res = array(
            'id'=>$id,
            'status'=>$status,
            'etprsName'=>$etprsName,
        );
        return $res;
    }

    /**
     * 管理同意退出操作，通过或者拒绝操作
     * @param $quitId  退出申请ID
     * @param string $status   审核状态
     * @param string $admindesc 审核备注
     * @return array|null
     */
    function passQuitApl($quitId,$status='',$admindesc='')
    {
        $postData['id'] = $quitId;
        $postData['status'] = $status;
        $postData['adminDesc'] = $admindesc;
        $etprsId = '0';
        $msg=findById("etprsAplQuit",array("id"=>$postData["id"]),"etprsId,id,quitdate");
        if(!empty($msg["data"])){
            $etprsId=$msg["data"]["etprsId"];
        }
        if($postData["status"]=="1028002"){
            if(!empty($etprsId)){
                $data["id"]=$etprsId;
                //企业状态设置为退出状态
                $data['status'] = '1001017';
                $data["quittime"]=strtotime($msg["data"]["quitdate"]);
                saveData("enterprise",$data);
            }
            //房间空置
            if(!empty($etprsId)){
                $room=array();
                $room["status"]="0";
                $room["etprsId"]="0";
                saveDataByCon("estateRoom",$room,array("etprsId"=>$etprsId));
                $eroom=array();
                $eroom["status"]="2";
                saveDataByCon("estateRoomEtprs",$eroom,array("etprsId"=>$etprsId));
            }
        }
        $postData["adminUserId"]=session("userId");
        $msg=saveData("etprsAplQuit",$postData,"申请退出");
        if($msg['code']==1){

            //给企业负责人发送微信通知
            switch($postData["status"]){
                case '1028002' :$link= '退出申请';$status1 = "孵化器管理员审核通过";break;
                case '1028000' :$link = '退出申请';$status1 = "被孵化器管理员拒绝";break;

                default:$link = '';$status1 ='';
            }
            //发送站内消息
            $emailData = array(
                'title'=>$link.'审核结果',
                'content'=>'尊敬的客户您好：您的'.$link.$status1.',详情请查看相应栏目信息',
                'type'=>'1020002',
            );
            $wxData = array();
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$link,
                        'keyword2'=>$status1,
                    ),
                    'first'=>'系统审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            if($etprsId){
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsId),'id');
            }
            if($uid){
                $this->sendAllMsg($uid,$emailData,$wxData);
            }
            //记录工作日志
            $logData = array(
                'etprsId'=>$etprsId,
                'fmenuId'=>15,
                'smenuId'=>33,
                'content'=>$link.$status1,
            );
            saveOaLog($logData);
        }
        return $msg;
    }

    /**
     * 退出管理，物业管理员通过操作，填写备注
     * @param int $id  退出申请ID
     * @param string $status 审核状态
     * @return \think\response\View
     */
    function setEstateQuitApl($id='',$status="")
    {
        $msg=findById("etprsAplQuit",array("id"=>$id),"etprsId,id");
        $etprsName = '';
        if(!empty($msg["data"])){
            $etprsId=$msg["data"]["etprsId"];
            $emsg=findById("enterprise",array("id"=>$etprsId),"id,name");
            if(!empty($emsg["data"])){
                $etprsName=$emsg["data"]["name"];
            }
        }
        $res = array(
            'id'=>$id,
            'status'=>$status,
            'etprsName'=>$etprsName,
        );
        return $res;
    }

    /**
     * 退出管理，保存物业管理员的退出备注
     * @param $quitId 退出ID
     * @param string $status 状态
     * @param string $estatedesc 备注信息
     * @return array
     */
    function saveEstateQuitApl($quitId,$status='',$estatedesc='')
    {
        $postData['id'] = $quitId;
        $postData['status'] = $status;
        $postData['estateDesc'] = $estatedesc;
        $postData["estateUserId"]=session("userId");
        //企业退出时间以物业审核时间为准
        $data["quittime"]=time();
        $etprsId = getField('etprsAplQuit',array('id'=>$postData['id']),'etprsId');
        if($etprsId){
            $data['id'] = $etprsId;
            saveData("enterprise",$data);
        }
        $msg=saveData("etprsAplQuit",$postData,"申请退出");
        if($msg['code']==1){

            switch($postData["status"]){
                case '1028003' :$link= '退出申请,';$status1 = "物业管理员审核通过";break;
                default:$link = '';$status1 ='';
            }
            //发送站内消息
            $emailData = array(
                'title'=>$link.'审核结果',
                'content'=>'尊敬的客户您好：您的'.$link.$status1.',详情请查看相应栏目信息',
                'type'=>'1020002',
            );
            $wxData = array();
            //给企业负责人发送微信通知
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$link,
                        'keywrod2'=>$status1
                    ),
                    'first'=>'退出审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            if($etprsId){
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsId),'id');
            }
            if($uid){
                $this->sendAllMsg($uid,$emailData,$wxData);
            }
            //工作日志
            $logData = array(
                'etprsId'=>$etprsId,
                'fmenuId'=>15,
                'smenuId'=>34,
                'content'=>$link.$status1,
            );
            saveOaLog($logData);
        }
        return $msg;
    }

    /**
     * 退出管理，财务退出操作
     * @param string $id 退出ID
     * @param string $status 审核状态
     * @return array
     */
    function setFiceQuitApl($id='',$status='')
    {
        $msg=findById("etprsAplQuit",array("id"=>$id),"etprsId,id");
        $etprsId = '0';
        $etprsName = '';
        if(!empty($msg["data"])){
            $etprsId=$msg["data"]["etprsId"];
            $emsg=findById("enterprise",array("id"=>$etprsId),"id,name");
            if(!empty($emsg["data"])){
                $etprsName=$emsg["data"]["name"];
            }
        }
        $res = array(
            'id'=>$id,
            'status'=>$status,
            'etprsName'=>$etprsName,
            'etprsId'=>$etprsId,
        );
        return $res;
    }

    /**
     * 退出管理，保存财务管理员的退出备注，同时生成退费记录
     * @param $quitId 退出ID
     * @param string $status 状态
     * @param string $estatedesc 备注信息
     * @return array
     */
    function saveFiceQuitApl($quitId,$status='',$ficeDesc='')
    {
        $postData['id'] = $quitId;
        $postData['status'] = '1028004';
        $postData['ficeDesc'] = $ficeDesc;
        $postData['ficeUserId'] = session('userId');
        $msg=saveData("etprsAplQuit",$postData);
        if($msg["code"]==='1'){
            //退费计算
            self::createQuitFee($postData['id']);

            $link= '退出申请';$status1 = "财务管理员审核通过";
            //发送站内消息
            $emailData = array(
                'title'=>$link.'审核结果',
                'content'=>'尊敬的客户您好：您的'.$link.$status1.',详情请查看相应栏目信息',
                'type'=>'1020002',
            );
            $wxData = array();
            if(config('open_wechat')){
                $template_id = config('wx_tpl.change');
                $wxData = array(
                    'tpl'=>$template_id,
                    'data'=>array(
                        'keyword1'=>$link,
                        'keywrod2'=>$status1
                    ),
                    'first'=>'退出审核通知',
                    'remark'=>'请登录系统，查看详情'
                );
            }
            $etprsId = getField('etprsAplQuit',array('id'=>$postData['id']),'etprsId');
            if($etprsId){
                $uid = getField('user',array('userCate'=>'1011002','etprsId'=>$etprsId),'id');
            }
            if($uid){
                $this->sendAllMsg($uid,$emailData,$wxData);
            }
            //工作日志：
            $logData = array(
                'etprsId'=>$etprsId,
                'fmenuId'=>15,
                'smenuId'=>35,
                'content'=>$link.$status1,
            );
            saveOaLog($logData);

        }
        return $msg;
    }

    /**
     * 生成某个公司的退费记录 分房间相关和不相关，与房间相关的分别生成记录  与房间无关的生成一条记录
     * @param $quitId 退出ID
     * @return array
     */
    function createQuitFee($quitId){
        $quitMsg = findById('etprsAplQuit',array('id'=>$quitId),'etprsId,roomNo');
        if($quitMsg['code']==1 && !empty($quitMsg['data'])){
            $quitInfo = $quitMsg['data'];
            $etprsId = $quitInfo['etprsId'];
            //查询要退出的房间
            $roomMsg = getdatalist('estateRoom',array('roomNo'=>array('in',explode(",",$quitInfo['roomNo']))),'id,type');
            if($roomMsg['code']==1 &&!empty($roomMsg['data'])){
                $roomInfo = $roomMsg['data'];
            }
        }else{
            return array('code'=>0,'msg'=>'参数错误');
        }

        //退费项目
        $quitOptlist=array();
        $quitcon=array("a.iqbtId"=>session("iqbtId"),'a.feetype'=>'1030003');
        $join = [['fee_item b','a.itemId=b.id','left'],['fee_item_opt c','a.optId=c.id','left']];
        $quitOptmsg=getDataList("feeItemCfg",$quitcon,"c.*,b.about",'a.id',$join);
        if(!empty($quitOptmsg["data"])){
            $quitOptlist=$quitOptmsg["data"];
        }
        // header("Content-Type: text/html;charset=utf-8");
        //  print_r($quitOptlist);exit();
        foreach($quitOptlist as $key=>$value){
            if($value['about'] ==1){
                //如果与房间相关
                if(!empty($roomInfo)){
                    foreach($roomInfo as $room){
                        //获取房间类型，工位就不生成记录
                        //每个房间生成一条记录
                        if($room['type']==1){
                            self::savequitLog($etprsId,$value,$room['id']);
                        }

                    }
                }
            }else{
                self::savequitLog($etprsId,$value,'0');
            }
        }

    }

    /**
     * 生成退费记录
     * @param $etprsId
     * @param $opt
     * @param $roomid
     */
    function savequitLog($etprsId,$opt,$roomid){
        $data= array(
            'iqbtId'=>session('iqbtId'),
            'adduserId'=>session('userId'),
            'addtime'=>time(),
            'etprsId'=>$etprsId,
            'optId'=>$opt['id'],
            'itemId'=>$opt['itemId'],
            'total'=>0,
            'roomId'=>$roomid
        );
        saveData('feeQuitRcd',$data,'添加退费记录');

    }

    /**
     * 获取企业的房间信息，强制清退时用的
     * @param $etprsId  企业ID
     * @return array
     */
    function initEtprsRoomNos($etprsId='88')
    {
        if(!empty($etprsId)){
            $roomNos="";
            $msg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'a.iqbtId'=>session("iqbtId")),"id,roomNo");
            if(!empty($msg["data"])){
                foreach($msg["data"] as $no){
                    $roomNos.=",".$no["roomNo"];
                }
            }
            $roomNos=trim($roomNos,",");
            $emsg=findById("enterprise",array("id"=>$etprsId),"id,lealPerson,mobile");
            if(!empty($emsg["data"])){
                $etprs=$emsg["data"];
                $etprs["roomNos"]=$roomNos;
            }
            return array("code"=>1,"msg"=>"","data"=>$etprs);
        }else{
            return array("code"=>0,"msg"=>"企业ID错误",'data'=>array());
        }
    }





}