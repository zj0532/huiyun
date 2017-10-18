<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/27
 * Time: 16:12
 */

namespace app\app\controller;
//use app\index\controller\Common;
use app\common\controller\Baseapply;
class Apply extends Appcommon
{

    /**
     * 管理员端材料初审,获取初审列表，包括待审核、已通过和已拒绝的
     * @param string $type  类型：apl:待审核  pass：已通过  back:已拒绝
     * @param string $etprs  企业名字  搜索的时候用到
     * @return \think\response\Json
     *
     */
    function getAplList($type='apl',$etprs=''){
        $contact = '';
        $apltype = '';
        $base = new Baseapply();
        $res = $base->getApllist($etprs,$contact,$apltype,$type);
        if(!empty($res)){
            foreach($res as $key=>$value){
                $res[$key]['addtime'] = date("Y-m-d",$value['addtime']);
                if($value['apltype'] == '0'){
                    $res[$key]['apltype'] = '企业入驻';
                }else if($value['apltype'] =='1'){
                    $res[$key]['apltype'] = '团队入驻';
                }else if($value['apltype'] =='roomapl'){
                    $res[$key]['apltype'] = '加租房间';
                }
                if($value['type'] =='etprs'){
                    $res[$key]['typeText'] = '企业';
                }elseif($value['type'] =='team'){
                    $res[$key]['typeText'] = '团队';
                }elseif($value['type'] =='seated'){
                    $res[$key]['typeText'] = '已入驻';
                }
                //获取用户头像
                $res[$key]['logo'] = getuserHeader($value['etprsId']);
            }
        }
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 材料初审详情
     * @param string $id  参数ID，直接传信息列表的ID字段
     * @param string $type 类型，传信息列表的type字段
     * @return \think\response\Json
     */
    function etprsAplDetail($id='',$type='etprs'){
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'id 参数不能为空','data'=>array()));
        }
        $data = array();
        $join = [['enterprise b','a.etprsId=b.id',"left"]];
        if($type=='seated'){
            //加租房间类型，需要读取加租房间的申请信息
            $msg=findById("etprsAplRoom",array("a.id"=>$id),"b.name as etprsname,b.entertime,b.rgsttime,b.lealPerson,a.*",$join);
            if($msg["code"]=='1' && !empty($msg['data'])){
                $data=$msg["data"];
                if(!empty ($data['entertime'])){
                    $data['entertime'] = date("Y-m-d",$data['entertime']);
                }else{
                    $data['entertime'] = '';
                }
                $data['addtime'] = date("Y-m-d",$data['addtime']);
                $data['type'] = 'seated';//添加的，为审核用
                $fileArr = array(
                    'taxfile'=>$data['taxfile'],
                    'security'=>$data['security'],
                    'reward'=>$data['reward']
                );
                $filePath = filePath($fileArr);
                foreach($filePath as $k=>$v){
                    $data[$k] = $v;
                }
            }
        }else{
            $msg=findById("etprsApl",array("a.id"=>$id),"b.name as etprsname,a.*",$join);
            if($msg["code"]==='1' && !empty($msg['data'])){
                $data=$msg["data"];
                $tmplist=self::getDictStr("*","EtprsApl");
                $data=$this->setObjIdText($data,$tmplist);
                //文件、图片换成地址
                $fileArr = array(
                    'idcartfile'=>$data['idcartfile'],
                    'edufile' =>$data['edufile'],
                    'projectdesc'=>$data['projectdesc'],
                    'patent' =>$data['patent'],
                );
                $filePath = filePath($fileArr);
                foreach($filePath as $k=>$v){
                    $data[$k] = $v;
                }
                //获取产品列表
                $con=array("etprsId"=>$data['etprsId'],'iqbtId'=>session("iqbtId"));
                $table="etprsAplProduct";
                $pdtMsg=getDataList($table,$con,"id,pdtname,pdtdesc");
                if($pdtMsg['code'] ==1){
                    $data['products'] = $pdtMsg['data'];
                }else{
                    $data['products'] = array();
                }
            }
        }
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }

    /**
     * 材料初审、加租房间审核操作
     * @param string $id  ID，传列表或者详情的ID
     * @param string $status status：审核状态,
     * @param string $type  type,传 列表或者详情的type
     * @return \think\response\Json
     */
    function setAplStatus($id='',$status='',$type='etprs'){
        if($type =="seated"){
            $table = 'EtprsAplRoom';
        }else{
            $table = 'enterprise';
        }
        $base = new Baseapply();
        $res = $base->setAplStatus($table,$id,$status);
        return json($res);
    }

    /**
     * 复审通知，获取复审列表
     * @param string $etprs 企业名称，搜索时可以按照企业名搜索
     * @return \think\response\Json
     */
    function getBatchApl($etprs=''){
        $base = new Baseapply();
        $res = $base->getBatchApl($etprs);
        if(!empty($res)){
            foreach($res as $key=>$value){
                if($value['apltype'] == '0'){
                    $res[$key]['apltype'] = '企业入驻';
                }else if($value['apltype'] =='1'){
                    $res[$key]['apltype'] = '团队入驻';
                }else if($value['apltype'] =='roomapl'){
                    $res[$key]['apltype'] = '加租房间';
                }
                if($value['type'] =='etprs'){
                    $res[$key]['typeText'] = '企业';
                }elseif($value['type'] =='team'){
                    $res[$key]['typeText'] = '团队';
                }elseif($value['type'] =='seated'){
                    $res[$key]['typeText'] = '已入驻';
                }
                //获取用户头像，作为logo
                $res[$key]['logo'] = getuserHeader($value['etprsId']);
            }
        }
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 创建复审批次
     * @param array $ids  企业ID数组，
     * @param string $names 企业名称，用逗号隔开
     * @return \think\response\Json
     */
    function setBatch($ids=array(),$names="")
    {
        if(empty($names)){
            $names="没有选择需要复审通知的企业";
        }else{
            if(is_array($names)){
                $names = implode(",",$names);
            }
            $names=trim($names,",");
        }
        $ids = join(",",$ids);
        $data = array(
            'ids'=>$ids,
            'names'=>$names
        );
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }

    /**
     * 保存复审通知批次
     * @return \think\response\Json
     */
    function saveBatch(){
        $postData=input("request."); //必须包含字段：batch,batchTime,batchAddress,batchRemark ,ids
        if(!isset($postData['ids']) || empty($postData['ids'])){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        if(!isset($postData['batch'])){
            $postData['batch'] = '';
        }
        if(!isset($postData['batchTime'])){
            $postData['batchTime'] = '';
        }
        if(!isset($postData['batchAddress'])){
            $postData['batchAddress'] = '';
        }
        if(!isset($postData['batchRemark'])){
            $postData['batchRemark'] = '';
        }
        $sms = input('sms','0');
        $base = new Baseapply();
        $res = $base->saveBatch($postData,$sms);
        return json($res);
    }

    /**
     * 复审通知，退回操作
     * @param array $ids  企业ID数组，
     * @param string $names 企业名称，用逗号隔开
     * @return \think\response\Json
     */
    function setetprsback($ids=array(),$names="")
    {
        if(empty($names)){
            $names="没有需要退回的企业";
        }else{
            if(is_array($names)){
                $names = implode(",",$names);
            }
            $names=trim($names,",");
        }
        $ids = join(",",$ids);
        $data = array(
            'ids'=>$ids,
            'names'=>$names
        );
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }

    /**
     * 复审通知，保存退回操作
     * @return json
     */
    function setAplBack()
    {
        $postData=input("request.");
        if(!isset($postData['content']) || empty($postData['content'])){
            return json(array('code'=>'0','msg'=>'请输入退回原因','data'=>array()));
        }
        if(!isset($postData['ids']) || empty($postData['ids'])){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $postData['status'] = '1001012'; //保存备注的时候用到了，
        $base = new Baseapply();
        $res = $base->setAplBack($postData);
        return json($res);
    }

    function getTutorReApl($etprs=''){
        $base = new Baseapply();
        $res = $base->getTutorReApl($etprs);
        foreach($res as $key=>$value){
            if($value['apltype'] == '0'){
                $res[$key]['apltype'] = '企业入驻';
            }else if($value['apltype'] =='1'){
                $res[$key]['apltype'] = '团队入驻';
            }else if($value['apltype'] =='roomapl'){
                $res[$key]['apltype'] = '加租房间';
            }
            if($value['type'] =='etprs'){
                $res[$key]['typeText'] = '企业';
            }elseif($value['type'] =='team'){
                $res[$key]['typeText'] = '团队';
            }elseif($value['type'] =='seated'){
                $res[$key]['typeText'] = '已入驻';
            }
            //获取用户头像
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
        }
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 企业复审详情页 打分页
     * @param int $id  申请ID
     * @param string $type  申请类型
     */
    function retrial($id='',$type='etprs'){
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->retrial($id);
        $data = array(
            'grade'=>$res['grade'],  //指标集 二维数组
            'note'=>$res['note'],   //复审的备注意见，
            'aplId'=>$id,    //申请ID
            'etprsId'=>$res['etprsId'],  //企业ID
            'type'=>$type,  //申请类型，可能有用,暂时先传过去
        );
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }

    /**
     * 企业评分，保存评分
     * 需要三个字段，$grade,指标集的数组，格式 指标集ID=>得分，不能包括其他无用的项  ,第二个字段，aplId ,申请ID  第三个字段，content,复审意见
     * @return json
     * #todo  这个函数可能根据实际传参形式改写
     */
    function saveRetrialInfo()
    {
        $postData=input("request.");
        $content = '';
        if(isset($postData['content'])){
            $content = $postData['content'];
            unset($postData['content']);
        }
        $aplId = '';
        if(isset($postData['aplId'])){
            $aplId = $postData['aplId'];unset($postData['aplId']);
        }
        if(isset($postData['token'])){
            unset($postData['token']);
        }
        //剩下的postData 的数据全是 指标集的评分数组
        $grade = $postData;
        $base = new Baseapply();
        $res = $base->saveRetrialInfo($grade,$aplId,$content);
        return json($res);
    }

    /**
     * 同意入驻，获取列表数据
     * @param string $etprs  企业名称，搜索时用
     * @return \think\response\Json
     */
    function getRetrialApl($etprs=''){
        $base = new Baseapply();
        $res = $base->getRetrialApl($etprs);
        foreach($res as $key=>$value){
            if($value['apltype'] == '0'){
                $res[$key]['apltype'] = '企业入驻';
            }else if($value['apltype'] =='1'){
                $res[$key]['apltype'] = '团队入驻';
            }else if($value['apltype'] =='roomapl'){
                $res[$key]['apltype'] = '加租房间';
            }
            if($value['type'] =='etprs'){
                $res[$key]['typeText'] = '企业';
            }elseif($value['type'] =='team'){
                $res[$key]['typeText'] = '团队';
            }elseif($value['type'] =='seated'){
                $res[$key]['typeText'] = '已入驻';
            }
            //获取用户头像
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
        }
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }


    //同意入驻，查看入驻详情，评分信息
    function enterAplInfo($id='',$type="etprs")
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->enterAplInfo($id);
        $res['type'] = $type;
        $checktutor=getField("enterStep",array("iqbtId"=>session("iqbtId")),"retrialapl");
        if(!empty($checktutor)){
            $tutorflag=1;
        }else{
            $tutorflag = 0;
        }
        $res['tutorflag'] = $tutorflag;
        //把企业的申请信息换成企业名字，因为页面只用到一个名字信息
        if(isset($res['data']['etprsname'])){
            $res['etprsname'] = $res['data']['etprsname'];
            unset($res['data']);
        }else{
            $res['data'] = '未知企业名字';
        }
        $res['aplId'] = $id;
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    //同意入驻，同意或者拒绝操作
    function saveEnterNote()
    {
        $postData=input("request.");
        if(isset($postData['aplId']) && !empty($postData['aplId'])){
            $aplId = $postData['aplId'];
        }else{
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        if(isset($postData['content'])){
            $content = $postData['content'];
        }else{
            $content = '';
        }
        if(isset($postData['status'])){
            $status = $postData['status'];
        }else{
            $status = '1001014';
        }
        if(isset($postData['sms'])){
            $sms = $postData['sms'];
        }else{
            $sms = '0';
        }
        $base = new Baseapply();
        $res = $base->saveEnterNote($aplId,$content,$status,$sms);
        return json($res);
    }

    /**
     * 房间分配，获取待分配的房间列表
     * @param string $etprs 企业名称，搜索时用
     * @param string $type 类型，待分配值为apl , 已完成，值为pass
     * @return json
     */
    function getRoomDitbApl($etprs="",$type="apl")
    {
        $contact = '';
        $apltype = '';
        $base = new Baseapply();
        $res = $base->getRoomDitbApl($etprs,$contact,$apltype,$type);
        foreach($res as $key=>$value){
            if($value['apltype'] == '0'){
                $res[$key]['apltype'] = '企业入驻';
            }else if($value['apltype'] =='1'){
                $res[$key]['apltype'] = '团队入驻';
            }else if($value['apltype'] =='roomapl'){
                $res[$key]['apltype'] = '加租房间';
            }
            if($value['type'] =='etprs'){
                $res[$key]['typeText'] = '企业';
            }elseif($value['type'] =='team'){
                $res[$key]['typeText'] = '团队';
            }elseif($value['type'] =='seated'){
                $res[$key]['typeText'] = '已入驻';
            }
            //获取用户头像
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
        }
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 分配房间，点击分配/增加房间按钮，
     * @param $id 企业的ID，给某个企业分配房间
     * @return \think\response\Json
     */
    function distrib($id='')
    {
        $join = [['fee_item b','a.itemId=b.id',"left"]];
        $imsg=getDataList("feeItemCfg",array("a.feetype"=>"1030001",'a.iqbtId'=>session("iqbtId"),'b.about'=>'1'),"a.id,a.itemId,a.optId,b.name as itemName","",$join);
        $items=array();
        if(!empty($imsg["data"])){
            $items=$imsg["data"];
        }
        $data = array(
            'id'=>$id,
            'items'=>$items,
            'iqbtId'=>session('iqbtId'),
        );
        return json(array('code'=>'1','msg'=>'','data'=>$data));

    }

    /**
     * 初始化楼层，
     * @param string $id  楼的ID
     *  返回 楼层ID  和 楼层数
     */
    function initFloor($id=''){
        if(empty($id)){
            return json(array('code'=>'1','msg'=>'请输入楼的ID','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->initFloor($id);
        return json($res);
    }

    /**
     * 获取孵化器的办公楼信息
     * @param string $iqbt 孵化器ID
     * @return \think\response\Json
     */
    function getBuild($iqbt=''){
        if(empty($iqbt)){
            $iqbt = session('iqbtId');
        }
        $build = getDataList('estateBuilding',array('iqbtId'=>$iqbt),'id,name');
        return json($build);
    }




    /**
     * 初始化房间
     * @param string $id  楼的ID
     * @param string $floor  楼层的编号，比如，8楼
     * @return json
     */
    function initFloorRoom($id="",$floor="")
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'请输入楼的ID','data'=>array()));
        }
        if(empty($floor)){
            return json(array('code'=>'0','msg'=>'请输入具体楼层','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->initFloorRoom($id,$floor);
        return json($res);
    }

    /**
     * 获取单个房间的信息,已经分配有企业， 包括缴费的信息
     * @param $id 房间的ID
     * @return \think\response\Json
     */
    function initetprsroom($id='')
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'请输入房间ID','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->initetprsroom($id);
        return json($res);
    }

    function initemptyroom($id='14',$etprsId='45'){
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'请传入房间ID','data'=>array()));
        }
        if(empty($etprsId)){
            return json(array('code'=>'0','msg'=>'企业ID不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->initemptyroom($id,$etprsId);
        //查询出入驻的缴费项和每项的缴费标准
        $join = [['fee_item b','a.itemId=b.id',"left"],['fee_item_opt c','a.optId=c.id','left']];
        $imsg=getDataList("feeItemCfg",array("a.feetype"=>"1030001",'a.iqbtId'=>session("iqbtId"),'b.about'=>'1'),"a.itemId,b.name as itemName,a.optId,c.name as optName","",$join);
        $items=array();
        if(!empty($imsg["data"])){
            $items=$imsg["data"];
            foreach($items as $key=>$value){
                //查询出每个缴费项下的全部缴费标准
                $optMsg = getDataList('feeItemOpt',array('iqbtId'=>session('iqbtId'),'itemId'=>$value['itemId']),'id,name');
                if($optMsg['code']==1 &&!empty($optMsg['data'])){
                    $items[$key]['optInfo'] = $optMsg['data'];
                }else{
                    $items[$key]['optInfo'] = array();
                }
            }
        }
        $res['data']['items'] = $items;
        return json($res);
    }

    /**
     * 分配操作，保存分配房间
     * @return json
     */
    function dstbEtprsRoom(){

        $postData=input("request.");
        /* postData 必须包含的以下4个字段
         * postData = array(
            'startTime'=>'开始时间，字符串类型的，因为在公共函数里用了strtotime()进行了转换,如，2017-09-28',
            'endTime' =>'结束时间,也是字符串类型的，',
            'roomId'  =>'房间ID',
            'etprsId' =>'企业Id',

        )*/
        if(!isset($postData['startTime']) || empty($postData['startTime'])){
            return json(array('code'=>'0','msg'=>'请选择开始时间','data'=>array()));
        }
        if(!isset($postData['endTime']) || empty($postData['endTime'])){
            return json(array('code'=>'0','msg'=>'请选择结束时间','data'=>array()));
        }
        if(!isset($postData['roomId'])){
            return json(array('code'=>'0','msg'=>'缺少参数roomId','data'=>array()));
        }
        if(!isset($postData['etprsId'])){
            return json(array('code'=>'0','msg'=>'缺少参数etprsId','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->dstbEtprsRoom($postData);
        return json($res) ;
    }

    /**
     * 分配未使用的房间，取消重置 ； 过期的房间，释放重置
     * @param string $roomid  房间ID
     * @return json
     */
    function roomCancel($roomid=''){

        if(empty($roomid)){
            return json(array('code'=>0,'msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->roomCancel($roomid);
        return json($res);
    }

    /**
     * 签约入驻。获取列表页
     * @param string $etprs  公司名  搜索用
     * @param string $status  状态  1001015，待签约  1001016 已完成
     * @return \think\response\Json
     */
    function getEnterApl($etprs='',$status="1001015"){
        $contact = '';
        $apltype = '';
        $base = new Baseapply();
        $res = $base->getEnterApl($etprs,$contact,$apltype,$status);
        foreach($res as $key=>$value){
            if($value['apltype'] == '0'){
                $res[$key]['apltype'] = '企业入驻';
            }else if($value['apltype'] =='1'){
                $res[$key]['apltype'] = '团队入驻';
            }else if($value['apltype'] =='roomapl'){
                $res[$key]['apltype'] = '加租房间';
            }
            if($value['type'] =='etprs'){
                $res[$key]['typeText'] = '企业';
            }elseif($value['type'] =='team'){
                $res[$key]['typeText'] = '团队';
            }elseif($value['type'] =='seated'){
                $res[$key]['typeText'] = '已入驻';
            }
            //获取用户头像
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
        }
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 签约入驻阶段的取消入驻
     * @param string $id 企业的ID
     * @return json
     */
    function setCancel($id=''){

        if(empty($id)){
            return json(array('code'=>0,'msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->setCancel($id);
        return json($res);
    }

    /**
     * 签约入驻，确定入驻，设置入驻时间
     * @param string $etprsId 企业ID
     * @return \think\response\Json
     */
    function setEnterTime($etprsId='')
    {
        if(empty($etprsId)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->setEnterTime($etprsId);
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 签约入驻，保存入驻时间
     * data 必须包含三个参数  id:企业ID  entertime：入驻开始时间  pactquittime：入驻结束时间
     * @return \think\response\Json
     */
    function saveEnterTime(){
        $data=input("request.");
        if(!isset($data['id']) || empty($data['id'])){
            return json(array('code'=>'0','msg'=>'企业ID不能为空','data'=>array()));
        }
        if(!isset($data['entertime']) || empty($data['entertime'])){
            return json(array('code'=>'0','msg'=>'入驻时间不能为空','data'=>array()));
        }
        if(!isset($data['pactquittime']) || empty($data['pactquittime'])){
            return json(array('code'=>'0','msg'=>'退出时间不能为空','data'=>array()));
        }
        $etprsId=$data["id"];
        $ettime=strtotime($data["entertime"]);
        $qttime=strtotime($data["pactquittime"]);
        $sms = 0;
        if(isset($data['sms'])){
            $sms = $data['sms'];
        }
        $base = new Baseapply();
        $res = $base->saveEnterTime($etprsId,$ettime,$qttime,$sms);
        return json($res) ;
    }

    //签约入驻，入驻详情
    function aplInfoFlow($etprsId='',$type='etrps'){
        if(empty($etprsId)){
            return json(array('code'=>'0','msg'=>'企业ID不能为空','data'=>array()));
        }
        //基本入驻信息
        $join = [['etprs_apl b','a.id=b.etprsId','left']];
        $msg = findById('enterprise',array('a.id'=>$etprsId),'a.name as etprsname,a.batch,a.batchTime,a.batchAddress,a.batchRemark,a.status,a.addtime,b.id as aplId',$join);
        $data = array();
        if($msg['code']==1 && !empty($msg['data'])){
            $data = $msg['data'];
            $data['addtime'] = date("Y-m-d",$data['addtime']);
        }
        //房间信息
        $roommsg=getDataList("EstateRoom",array("etprsId"=>$etprsId,'iqbtId'=>session("iqbtId")),"id,roomNo");
        $roomNos = '';
        if(!empty($roommsg["data"])){
            foreach($roommsg["data"] as $no){
                $roomNos.=(",".$no["roomNo"]);
            }
        }
        $roomNos=trim($roomNos,",");
        $data["roomNos"]=$roomNos;
        $data['type'] = $type;
        $data['etprsId'] = $etprsId;
        $steps=[];
        $stepsmsg=findById("EnterStep",array("iqbtId"=>session("iqbtId")));
        if(!empty($stepsmsg["data"])){
            $steps=$stepsmsg["data"];
        }
        $res = array(
            'info'=>$data,
            'steps'=>$steps,
        );
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 签约入驻，导师分配，保存导师信息
     * @param $etprsId  企业ID
     * @param $tutorIds  导师ID ，字符串形式，以逗号隔开
     * @return \think\response\Json
     */
    function saveEtprsTutor($etprsId,$tutorIds)
    {
        if(empty($etprsId)){
            return json(array('code'=>'0','msg'=>'企业ID不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->saveEtprsTutor($etprsId,$tutorIds);
        return json($res);
    }

    /**
     * 签约入驻，查看导师复审详情接口
     * @param string $aplId 申请ID
     * @return \think\response\Json
     */
    function gradeInfo($aplId=''){
        if(empty($aplId)){
            return json(array('code'=>'0','msg'=>'申请ID不呢为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->gradeInfo($aplId);
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 签约入驻，获取合同列表
     * @param int $etprsId 企业ID
     * @return array
     */
    function getEtprsPact($etprsId='88')
    {
        if(empty($etprsId)){
            return json(array('code'=>'0','msg'=>'参数错误','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->getEtprsPact($etprsId);
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 签约入驻，删除合同
     * @param string $id 合同ID
     * @return \think\response\Json
     */
    function deltPact($id=''){
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'合同ID不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->deltPact($id);
        return json($res);
    }

    /**
     * 签约入驻，导师分配，获取导师列表
     * @param string $etprsId 企业ID
     */
    function getTutorList($etprsId=''){
        if(empty($etprsId)){
            return json(array('code'=>'0','msg'=>'企业ID不能为空','data'=>array()));
        }
        $msg=findById("enterprise",array("id"=>$etprsId),"id,tutorIds");
        $ids = ''; //已经分配的导师ID
        if(!empty($msg["data"])){
            $ids=$msg["data"]["tutorIds"];
        }
        if(!empty($ids)){
            $idArr = explode(",",$ids);
        }else{
            $idArr = array();
        }
        $totalMsg = getDataList('tutor',array('iqbtId'=>session('iqbtId')),'id,name');
        $tutorList = array();
        if($totalMsg['code']==1 &&!empty($totalMsg['data'])){
            foreach($totalMsg['data'] as $key=> $value){
                $tutorList[$key]['id'] = $value['id'];
                $tutorList[$key]['name'] = $value['name'];
                $tutorList[$key]['check'] = 0;
                if(!empty($idArr)){
                    if(in_array($value['id'],$idArr)){
                        $tutorList[$key]['check'] = 1;
                    }
                }
            }
        }
        $data = array(
            'etprsId'=>$etprsId,
            'tutor'=>$tutorList,
        );
        return json(array('code'=>'1','msg'=>'','data'=>$data));
    }


    /**
     * 续约管理，获取续约列表
     * @param string $status  状态，1027001：待处理 1027002：已通过
     * @return \think\response\Json
     */
    function getRenewApl($status='1027001')
    {
        $base = new Baseapply();
        $res = $base->getRenewApl($status);
        foreach($res as $key=>$value){
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
        }
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        //data里面字段说明： settled：入驻时间，startTime：续约开始时间，endTime：续约结束时间,addtime：申请时间
        return json($data);
    }

    /**
     * 续约管理，查看续约详情
     * @param int $id 续约申请的ID
     * @return array|\think\response\Json
     */
    function renewdetail($id='13')
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'申请ID不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->renewdetail($id);
        if(!empty($res)){
            $res['startTime'] = date("Y-m-d",$res['startTime']);
            $res['endTime'] = date("Y-m-d",$res['endTime']);
            $res['addtime'] = date("Y-m-d",$res['addtime']);
            $fileArr = array(
                'taxfile'=>$res['taxfile'],
                'security'=>$res['security'],
                'reward'=>$res['reward']
            );
            $filePath = filePath($fileArr);
            foreach($filePath as $k=>$v){
                $res[$k] = $v;
            }
        }
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 续约管理，审核操作，拒绝/通过
     * @param string $id 申请ID
     * @param string $status  状态：1027000 拒绝  1027002 通过
     * @return \think\response\Json
     */
    function setRenewStatus($id='',$status=''){
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'申请ID不能为空','data'=>array()));
        }
        if(empty($status)){
            return json(array('code'=>'0','msg'=>'操作状态不能为空','data'=>array()));
        }
        $table = 'etprsAplRenew';
        $sms = '0';
        $base = new Baseapply();
        $res = $base->setRenewStatus($table,$id,$status,$sms);
        return json($res);

    }


    /**
     * 退出管理，获取退出企业列表
     * @param string $status 状态：1028001：管理员待审核，1028002：物业待审核, 1028003:财务待审核， 1028004：待退款（所有审核都通过的）
     * @return \think\response\Json
     */
    function getQuitApl($status="1028001")
    {
        $base = new Baseapply();
        $res = $base->getQuitApl($status);
        foreach($res as $key=>$value){
            if($value['types'] ==0){
                $res[$key]['types'] = '申请退出';
            }else{
                $res[$key]['types'] = '强制清退';
            }
            $res[$key]['logo'] = getuserHeader($value['etprsId']);
        }

        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 退出管理，查看退出详情
     * @param int $id 退出申请ID
     * @return array|\think\response\View
     */
    function quitdetail($id='14')
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->quitdetail($id);
        if(!empty($res)){
            $res['renvtion'] = ($res['renvtion']==0)?'否':'是';
            $res['renvtionremove'] = ($res['renvtionremove']==0)?'否':'是';
            $res['isleave'] = ($res['isleave']==0)?'否':'是';
        }
        $data = array(
            'code'=>'1',
            'msg'=>'',
            'data'=>$res,
        );
        return json($data);
    }

    /**
     * 退出管理,管理员审核通过或者拒绝,填写审核备注
     * @param int $id 退出申请ID
     * @param string $status 审核状态 状态：1028000：拒绝 , 1028002：通过
     * @return \think\response\Json
     */
    function setQuitApl($id='',$status="1028000")
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array()));
        }
        if(empty($status)){
            return json(array('code'=>'0','msg'=>'审核状态不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->setQuitApl($id,$status);
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 退出管理，管理员通过、拒绝操作
     * post数据必须包含 id,status ,adminDesc 三个字段
     * @return array|null
     */
    function passQuitApl()
    {
        $postData=input("request.");
        if(!isset($postData['id']) || empty($postData['id'])){
            return json(array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array()));
        }
        if(!isset($postData['status']) || empty($postData['status'])){
            return json(array('code'=>'0','msg'=>'审核状态不能为空','data'=>array()));
        }
        $quitId = $postData['id'];
        $status = $postData['status'];
        $adminDesc = '';
        if(isset($postData['adminDesc'])){
            $adminDesc = $postData['adminDesc'];
        }
        $base = new Baseapply();
        $res = $base->passQuitApl($quitId,$status,$adminDesc);
        return json($res);
    }

    /**
     * 退出管理，物业管理员退出操作,填写退出备注
     * @param int $id 退出申请ID
     * @param string $status 退出状态
     * @return json
     */
    function setEstateQuitApl($id='',$status="")
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array()));
        }
        if(empty($status)){
            return json(array('code'=>'0','msg'=>'审核状态不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->setEstateQuitApl($id,$status);
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 退出管理 ,物业管理员通过操作
     * post数据必须包含 id,status ,estateDesc 三个字段
     * @return array|null
     */
    function saveEstateQuitApl()
    {
        $postData=input("request.");
        if(!isset($postData['id']) || empty($postData['id'])){
            return json(array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array()));
        }
        if(!isset($postData['status']) || empty($postData['status'])){
            return json(array('code'=>'0','msg'=>'审核状态不能为空','data'=>array()));
        }
        $quitId = $postData['id'];
        $status = $postData['status'];
        $estateDesc = '';
        if(isset($postData['estateDesc'])){
            $estateDesc = $postData['estateDesc'];
        }
        $base = new Baseapply();
        $res = $base->saveEstateQuitApl($quitId,$status,$estateDesc);
        return json($res);
    }

    /**
     * 退出管理，财务管理员退出操作,填写退出备注
     * @param int $id 退出申请ID
     * @param string $status 退出状态
     * @return json
     */
    function setFiceQuitApl($id='14',$status="1028004")
    {
        if(empty($id)){
            return json(array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array()));
        }
        if(empty($status)){
            return json(array('code'=>'0','msg'=>'审核状态不能为空','data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->setFiceQuitApl($id,$status);
        return json(array('code'=>'1','msg'=>'','data'=>$res));
    }

    /**
     * 退出管理 ,财务管理员通过操作
     * post数据必须包含 id,status ,ficeDesc 三个字段
     * @return array|null
     */
    function saveFiceQuitApl()
    {
        $postData=input("request.");
        if(!isset($postData['id']) || empty($postData['id'])){
            return json(array('code'=>'0','msg'=>'退出申请ID不能为空','data'=>array()));
        }
        if(!isset($postData['status']) || empty($postData['status'])){
            return json(array('code'=>'0','msg'=>'审核状态不能为空','data'=>array()));
        }
        $quitId = $postData['id'];
        $status = $postData['status'];
        $ficeDesc = '';
        if(isset($postData['ficeDesc'])){
            $ficeDesc = $postData['ficeDesc'];
        }
        $base = new Baseapply();
        $res = $base->saveFiceQuitApl($quitId,$status,$ficeDesc);
        return json($res);
    }

    /**
     * 获取所有入孵的企业，强制清退的时候可能用的上
     */
    function getfcsquitEtprs(){
        $map = array(
            'status'=>'1001016',
            'iqbtId'=>session('iqbtId')
        );
        $list = getDataList('enterprise',$map,'id,name');
        if($list['code']==1){
            $data = $list['data'];
            return json(array('code'=>'1','msg'=>'','data'=>$data));
        }else{
            return json(array('code'=>'0','msg'=>'查询错误','data'=>array()));
        }
    }

    /**
     * 强制清退企业的时候用的,获取企业相关的房间信息和法人信息
     * @param string $etprsId 企业ID
     * @return json
     */
    function initEtprsRoomNos($etprsId='3')
    {
        if(empty($etprsId)){
            return json(array("code"=>0,"msg"=>"企业ID错误",'data'=>array()));
        }
        $base = new Baseapply();
        $res = $base->initEtprsRoomNos($etprsId);
        return json($res);
    }


}