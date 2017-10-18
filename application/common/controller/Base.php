<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/22
 * Time: 17:24
 */

namespace app\common\controller;
use think\Controller;
use org\weixin\WechatPush;
class Base  extends Controller
{

    /**
     * 表名首字母大写，使用TP格式表名
     * 字典配置
     * */
    public function dict()
    {
        $dict=array();
        //信息-- 测试
        $etprs["status"]=array('sysDict','code,name',self::setDictCon("1001"));
        $dict["Enterprise"]=$etprs;
        //用户
        $user["sex"]=array('sysDict','code,name',self::setDictCon("2001"));
        $user["userCate"]=array('sysDict','code,name',self::setDictCon("1011"));
        $user["status"]=array('sysDict','code,name',self::setDictCon("1012"));
        $dict["User"]=$user;
        $resos["category"]=array('sysDict','code,name',self::setDictCon("1005"));
        $dict["ResosResource"]=$resos;
        $resosoperate["status"]=array('sysDict','code,name',self::setDictCon("1007"));
        $dict["ResosCoperate"]=$resosoperate;
        //高管变动
        $execut["roleCode"]=array('sysDict','code,name',self::setDictCon("1003"));
        $execut["origRoleCode"]=array('sysDict','code,name',self::setDictCon("1003"));
        $dict["EtprsExecut"]=$execut;
        $suggest["type"]=array('sysDict','code,name',self::setDictCon("1014"));
        $dict["EtprsSuggest"]=$suggest;
        $partlot["areaNo"]=array('sysDict','code,name',self::setDictCon("1015"));
        $dict["OaParklot"]=$partlot;
        $databank["type"]=array('sysDict','code,name',self::setDictCon("1016"));
        $dict["EstateDatabank"]=$databank;
        $fee["type"]=array('sysDict','code,name',self::setDictCon("1009"));
        $dict["EstateFee"]=$fee;
        $history["category"]=array('sysDict','code,name',self::setDictCon("1002"));
        $dict["PreBaseHistory"]=$history;
        $staff["roleCode"]=array('sysDict','code,name',self::setDictCon("1003"));
        $staff["edu"]=array('sysDict','code,name',self::setDictCon("1010"));
        $dict["PreBaseStaff"]=$staff;
        $customer["roleCode"]=array('sysDict','code,name',self::setDictCon("1003"));
        $dict["PreMarketCustomer"]=$customer;
        $supplier["roleCode"]=array('sysDict','code,name',self::setDictCon("1003"));
        $dict["PreMarketSupplier"]=$supplier;
        $hmrate["edu"]=array('sysDict','code,name',self::setDictCon("1010"));
        $hmrate["category"]=array('sysDict','code,name',self::setDictCon("1018"));
        $dict["PreHmresosRate"]=$hmrate;
        $conflict["roleCode"]=array('sysDict','code,name',self::setDictCon("1003"));
        $dict["PreInvestConflict"]=$conflict;
        $external["category"]=array('sysDict','code,name',self::setDictCon("1019"));
        $dict["ResosExternal"]=$external;
        $msg["type"]=array('sysDict','code,name',self::setDictCon("1020"));
        $dict["SysMsg"]=$msg;
        $roomapl["industry"]=array('sysDict','code,name',self::setDictCon("1023"));
        $roomapl["workstyle"]=array('sysDict','code,name',self::setDictCon("1021"));
        $roomapl["taxpayertype"]=array('sysDict','code,name',self::setDictCon("1022"));
        $roomapl["technical"]=array('sysDict','code,name',self::setDictCon("1024"));
        $roomapl["sex"]=array('sysDict','code,name',self::setDictCon("2001"));
        $roomapl["edu"]=array('sysDict','code,name',self::setDictCon("1010"));
        $roomapl["worktype"]=array('sysDict','code,name',self::setDictCon("1025"));
        $dict["EtprsApl"]=$roomapl;
        $renewapl["status"]=array('sysDict','code,name',self::setDictCon("1027"));
        $dict["EtprsAplRenew"]=$renewapl;
        $quitapl["status"]=array('sysDict','code,name',self::setDictCon("1028"));
        $dict["EtprsAplQuit"]=$quitapl;
        $feeitem["cate"]=array('sysDict','code,name',self::setDictCon("1029"));
        $dict["FeeItem"]=$feeitem;
        $tutor["sex"]=array('sysDict','code,name',self::setDictCon("2001"));
        $dict["Tutor"]=$tutor;

        $etprsinfo["industry"]=array('sysDict','code,name',self::setDictCon("1023"));
        $etprsinfo["technical"]=array('sysDict','code,name',self::setDictCon("1024"));
        $etprsinfo["taxpayertype"]=array('sysDict','code,name',self::setDictCon("1022"));
        $dict["EtprsInfo"]=$etprsinfo;

        //车位
        $partlot["areaNo"]=array('sysDict','code,name',self::setDictCon("1015"));
        $dict["EstateParklot"]=$partlot;

        //孵化器申请
        $iqbtapl["level"]=array('sysDict','code,name',self::setDictCon("1031"));
        $iqbtapl["services"]=array('sysDict','code,name',self::setDictCon("1032"));
        $iqbtapl["facility"]=array('sysDict','code,name',self::setDictCon("1033"));
        $iqbtapl["type"]=array('sysDict','code,name',self::setDictCon("1034"));
        $dict["IqbtApl"]=$iqbtapl;

        $iqbt["facility"]=array('sysDict','code,name',self::setDictCon("1033"));
        $iqbt["services"]=array('sysDict','code,name',self::setDictCon("1032"));
        $iqbt['level'] = array('sysDict','code,name',self::setDictCon("1031"));
        $dict["Incubator"]=$iqbt;

        //拜访类型
        $visit["visitType"]=array('sysDict','code,name',self::setDictCon("1035"));
        $visit['etprsType'] = array('sysDict','code,name',self::setDictCon("1036"));
        $dict["Visit"]=$visit;

        //通知公告分类
        $notice['type'] = array('sysDict','code,name',self::setDictCon("1037"));
        $dict['SysNotice'] = $notice;

        //考核-产品
        $rptpdt['techsource'] = array('sysDict','code,name',self::setDictCon("1038"));
        $rptpdt['stage'] = array('sysDict','code,name',self::setDictCon("1039"));
        $rptpdt['techlevel'] = array('sysDict','code,name',self::setDictCon("1040"));
        $rptpdt["technical"]=array('sysDict','code,name',self::setDictCon("1024"));
        $dict['ReportProduct'] = $rptpdt;

        //考核-项目
        $rptpjt['techsource'] = array('sysDict','code,name',self::setDictCon("1038"));
        $rptpjt['stage'] = array('sysDict','code,name',self::setDictCon("1039"));
        $rptpjt['techlevel'] = array('sysDict','code,name',self::setDictCon("1040"));
        $rptpjt["technical"]=array('sysDict','code,name',self::setDictCon("1024"));
        $dict['ReportProject'] = $rptpjt;

        return $dict;

    }


    function get_url() {
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $relate_url;
    }

    public function _empty($name)
    {
        return view($name);
    }

    function setDictCon($code){
        return array('level'=>array("EGT",1),'code'=>array('like',$code.'%'));
    }

    /**
     * 检查数据表中字段数据是否唯一
     * @param $table 表名
     * @param array $con 查询条件
     * @return array()
     */
    function chkUniqe($table, $con)
    {
        $chk = findById($table, $con, "id");
        if (!empty($chk["data"])) {
            return returnResult("db_info", "db_exit_info");
        } else {
            return returnResult("db_info", "db_uniqe_info");
        }
    }

    /**
     * 公用查询。如果被关联表数据量比较大，建议测试效率。以及内存情况。
     * param参数格式：(1)：code like '1001%'  (2)：{"name":"张三"}
     * @return array
     */
    public function dataList(){
        $table=input("table");//表名
        $paramStr=input("param");//查询条件
        if(empty($paramStr)){
            $paramStr="";
        }

        /*var_dump(request()->param());*/
        $con=json_decode($paramStr,true);
        $con=empty($con)?$paramStr:$con;
        $filedstr=input("fields");//查询字段
        $fields=empty($filedstr) ?"*":input("fields");
        $sequence=input("sequence");//排序
        if(empty($table)||(!is_string($table))){//表名错误
            return returnResult("db_info","db_tablename_info");
        }
        $msg=getDataList($table,$con,$fields,$sequence);
        if($msg["code"]==='0'){
            return $msg;
        }
        $tmplist=self::getDictStr($fields,$table);
        $msg['data']=$this->setListIdText($msg['data'],$tmplist);
        return $msg;
    }
    /**
     * 保存/修改 数据 如果方法为post方法，修改。
     * @return array|mixed
     */
    public function save(){
        $postData = json_decode(file_get_contents("php://input"),true);
        $table=$postData['table'];
        $id=isset($postData['id'])?$postData['id']:0;
        $data=$postData['param'];
        if(!empty($id)){
            $data["id"]=$id;
        }
        return saveData($table,$data,"添加/修改");
    }

    /**
     * 获取字典表下拉列表内容.
     * @return array|mixed|null
     */
    public function getDictOptions() {
        $table="sysDict";//表名
        $codes=input("code");

        $codeArr=explode(",",$codes);
        $return =array();
        foreach($codeArr as $code){
            $con["code"]=array('like',$code.'%');
            $level=input("level");
            $con["level"]=empty($level)?"2":$level;
            if(!empty(session("iqbtId"))){
                $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
            }

            $filedstr=input("fields");//查询字段
            $fields=empty($filedstr) ?"code,name,0 as ischk":input("fields");
            $msg = getDataList($table,$con,$fields);
            if($msg["code"]!=="0"){
                $return[$code]=$msg["data"];
            }else{
                return $msg;
            }
        }
        return returnResult("msg_info","global_success_info",$return);
    }

    /**
     * 获取下拉列表
     * param参数格式：(1)：code like '1001%'  (2)：{"name":"张三"}
     * @return array|mixed|null
     */
    public function getOptions(){
        $table=input("table");//表名
        $paramStr=input("param");//查询条件
        $con=json_decode($paramStr,true);
        $con=(empty($paramStr)&&empty($con))?$paramStr:$con;
        $filedstr=input("fields");//查询字段
        $fields=empty($filedstr) ?"id,name":input("fields");
        $fields=$fields.",false as ischk";
        $sequence=input("sequence");//排序
        if(empty($table)||(!is_string($table))){//表名错误
            return returnResult("db_info","db_tablename_info");
        }
        return getDataList($table,$con,$fields,$sequence);
    }

    /**
     * 对象信息
     * @return array|mixed|null
     */
    public function getObjInfo(){
        $table=input("table");//表名
        $paramStr=input("param");//查询条件
        if(empty($paramStr)){
            $paramStr="";
        }
        $con=json_decode($paramStr,true);
        $con=empty($con)?$paramStr:$con;
        $filedstr=input("fields");//查询字段
        $fields=empty($filedstr) ?"*":input("fields");

        $msg = findById($table,$con,$fields);
        //echo $fields."--".$table;
        $tmplist=self::getDictStr($fields,$table);
        if(!empty($msg['data'])){
            $msg['data']=self::setObjIdText($msg['data'],$tmplist);
        }
        return $msg;
    }


    //辅助处理函数----------------------------------------------------------------------------------------------------------------
    public function getDictStr($fileds,$table){
        $dict=self::dict();
        $arr=explode(",",$fileds);
        $table=tableToTp($table);
        if(!isset($dict[$table])){
            return array();
        }
        $data=$dict[$table];
        //print_r($data);
        $result=array();
        $b=$fileds=="*"?true:false;
        foreach ($data as $k => $v) {
            if(in_array($k,$arr)||$b){
                list($dictTable,$dictFileds,$con)=$v;
                $result[]=array(array('fieldkey'=>$k,'fieldname'=>$k."Text"),$dictTable,$dictFileds,$con);
            }
        }
        return $result;
    }

    /**
     * 判断管理员或者企业时候，获取企业ID
     * @return mixed
     */
    function getEtprsId()
    {
        $userCate=session("user.userCate");
        if($userCate=="1011001"){//管理员用户
            $etprsId=session("etprsId");
        }else if($userCate=="1011002"){//如果是企业用户
            $etprsId=session("user.etprsId");
        }
        return $etprsId;
    }



    /**
     * 列表字段重新赋值（针对外键字段,多个字段）
     * @param $datalist 需要处理的数据列表
     * @param $fieldarr 配置原来字段名称，赋值后的名称 array('fieldkey'=>'pid','fieldname'=>'parentName')
     * @param $table 新值从哪个表查询
     * @param string $field 查询的字段
     * @param array $con 查询条件
     * @return array() 将处理后的datalist返回
     */
    public function setListIdText($datalist,$tmplist){
        //print_r($tmplist);
        $temp=array();
        foreach($tmplist as $tmp){
            list($fieldarr,$table,$field,$con)=$tmp;
            $list=array();
            if(isset($temp[$table.json_encode($con)])){
                $list=$temp[$table.json_encode($con)];
            }else{
                $listmsg=gethashmap($table,$con,$field);
                if($listmsg["code"]==="1"){
                    $list=$listmsg["data"];
                }
                $temp[$table.json_encode($con)]=$list;
            }
            if(empty($list)){
                return $datalist;
            }
            $fieldkey=$fieldarr["fieldkey"];
            $fieldName=empty($fieldarr['fieldname'])?$fieldkey:$fieldarr['fieldname'];
            for($i=0;$i<count($datalist);$i++){
                if(empty($datalist[$i][$fieldkey])){
                    $datalist[$i][$fieldName]="";
                }else{
                    $datalist[$i][$fieldName]=getidlistText($list,$datalist[$i][$fieldkey]);
                }
            }
        }
        unset($temp);
        return $datalist;
    }
    /**
     * 对象字段重新赋值（针对外键字段,多个字段）
     * @param $datalist 需要处理的数据列表
     * @param $fieldarr 配置原来字段名称，赋值后的名称 array('fieldkey'=>'pid','fieldname'=>'parentName')
     * @param $table 新值从哪个表查询
     * @param string $field 查询的字段
     * @param array $con 查询条件
     * @return array() 讲处理后的datalist返回
     */
    public function setObjIdText($obj,$tmplist){
        if(empty($tmplist)){
            return $obj;
        }
        foreach($tmplist as $tmp){
            list($fieldarr,$table,$field,$con)=$tmp;
            list($id,$name)=explode(",",$field);
            $fieldkey=$fieldarr["fieldkey"];
            $fieldName=empty($fieldarr['fieldname'])?$fieldkey:$fieldarr['fieldname'];
            if(strpos($obj[$fieldkey],",")){
                $con[$id]=array("in",$obj[$fieldkey]);
            }else{
                $con[$id]=$obj[$fieldkey];
            }

            $fieldArr=getFieldArrry($table,$con,$name);

            $obj[$fieldName]=join(",",$fieldArr);
        }
        return $obj;
    }






    /**
     * 发送短信消息,阿里云短信接口
     * @param $userId 用户ID，（user表，根据ID查找openID） 单个的数字 比如 2
     * @param $data   要发送的数据数组，键名是根据短信模板配置的名字，例如：array('code'=>'456832'),
     * @param $tpl    模板ID，
     */
    function sendAlsms($userId,$tpl,$data){
        import('alsms.Samples.Sms.sms',EXTEND_PATH);
        $instance = new \PublishBatchSMSMessageDemo();
        //从用户表里获取电话号码
        if(!empty($userId)){
            $mobile = getField('user',array('id'=>$userId),'mobile');
        }else{
            return false;
        }
        if(!empty($mobile)){
            $res = $instance->run($mobile,$data,$tpl);
            if($res =='1'){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    /**
     * @param $wxtpl 微信模板ID
     * @param $userId 用户ID，（user表，根据ID查找openID） 单个的数字 比如 2
     * @param $keysArr   模板参数数组，格式为:array('keyword1'=>'入孵申请通知','keyword2'=>'2017年8月3号')
     * @param $first    微信消息开始的提示内容
     * @param $remark   微信消息最后的提示内容
     */
    function sendWeiXin($userId,$wxtpl,$keysArr,$first='',$remark=""){
        $push = new WechatPush();
        $data= array(
            'first' => array('value' => urlencode($first), 'color' => "#743A3A"),
        );
        if(is_array($keysArr)){
            foreach($keysArr as $key=>$keyvalue){
                $data[$key] = array('value'=>urlencode($keyvalue),'color'=>'#743A3A');
            }
        }
        $data['remark'] = array('value'=>urlencode($remark),'color'=>'#743A3A');
        if(!empty($userId)){
            $openid = getField('user',array('id'=>$userId,'is_gz'=>1),'openId');
            if($openid){
                $url = "";
                $res =$push->doSend($openid, $wxtpl, $url, $data);
                return $res;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    /**
     * 发送站内信消息
     * @param $userId 用户ID，user表，单个的数字 比如 2
     * @param $data  内容，包括分类、标题、内容、时间等
     */
    function sendSys($userId,$data){
        if(empty($userId)){
            return array('code'=>0,'msg'=>'参数为空');
        }
        $sdata['toUserId'] = $userId;
        $sdata['oprtUserId'] = session("userId");
        $sdata['addtime'] = time();
        if(!isset($data['iqbtId'])){
            $sdata['iqbtId'] = session("iqbtId");
        }else{
            $sdata['iqbtId'] = $data['iqbtId'];
        }
        if(isset($data['title'])){
            $sdata['title'] = $data['title'];
        }
        if(isset($data['type'])){
            $sdata['type'] = $data['type'];
        }
        if(isset($data['content'])){
            $sdata['content'] = $data['content'];
        }
        if(isset($data['fromUserId'])){
            $sdata['fromUserId'] = $data['fromUserId'];
        }
        if(isset($data['relTable'])){
            $sdata['relTable'] = $data['relTable'];
        }
        if(isset($data['relId'])){
            $sdata['relId'] = $data['relId'];
        }
        if(isset($data['stype'])){
            $sdata['stype'] = $data['stype'];
        }
        $msg = saveData("sysMsg", $sdata, "发送消息");
        return $msg;
    }
    //发送APP消息函数
    #todo
    function sendApp(){

    }
    //发送四种消息的统一函数入口
    /**
     * @param $userId   用户ID，user表，可以是数组或者逗号隔开的字符串
     * @param $emailData  站内信和APP的消息数组，如 array('title'=>'','content'=>'','type'=>'')
     * @param array $wxData   微信消息数组 包含四个字段 array('tpl'=>'','data'=>array(),'first'=>'','remark'=>'')
     * @param array $smsData  短信消息数组,包含两个字段  如：array('tpl'=>'','data'=>'')
     */
    function sendAllMsg($userId,$emailData=array(),$wxData=array(),$smsData=array()){
        if(!empty($userId)){
            if(!is_array($userId)){
                $userId = explode(",",trim($userId,","));
            }
            foreach($userId as $uid){
                //分别发送消息
                //如果站内信数组不为空，则发送站内信
                if(!empty($emailData)){
                    $this->sendSys($uid,$emailData);
                }

                //如果微信消息不为空，则发微信消息
                if(!empty($wxData)){

                    if(isset($wxData['tpl'])&&!empty($wxData['tpl'])){
                        $wxtpl = $wxData['tpl'];
                        if(isset($wxData['data']) && !empty($wxData['data'])){
                            $keysArr = $wxData['data'];
                            if(isset($wxData['first'])){
                                $first = $wxData['first'];
                            }else{
                                $first = "消息通知";
                            }
                            if(isset($wxData['remark'])){
                                $remark = $wxData['remark'];
                            }else{
                                $remark = '请登录系统，查看详情';
                            }
                            $this->sendWeiXin($uid,$wxtpl,$keysArr,$first,$remark);
                        }
                    }
                }

                //如果短信消息不为空，则发送短信消息
                if(!empty($smsData)){
                    if(isset($smsData['tpl'])&&!empty($smsData['tpl'])){
                        $tpl = $smsData['tpl'];
                        if(isset($smsData['data']) && !empty($smsData['data'])){
                            $data = $smsData['data'];
                            $this->sendAlsms($uid,$tpl,$data);
                        }
                    }
                }


            }
        }
    }

    function messageRemind(){
        //待审核
        $con1=array("a.status"=>"1027001",'a.iqbtId'=>session("iqbtId"));
        $con2=array("a.status"=>"1001011",'a.iqbtId'=>session("iqbtId"));
        $join1 = [['enterprise b','a.etprsId=b.id',"left"],["etprsApl c",'a.etprsId=c.etprsId',"left"]];
        $roomaplmsg=getDataList("etprsAplRoom",$con1,"a.id","a.addtime desc",$join1);
        if(!empty($roomaplmsg["data"])){
            $num1=count($roomaplmsg["data"]);
        }else{
            $num1 = 0;
        }
        $join2 = [['etprs_apl b','a.id=b.etprsId',"right"]];
        $etprsaplmsg=getDataList("enterprise",$con2,"b.id","b.addtime desc",$join2);
        if(!empty($etprsaplmsg["data"])){
            $num2=count($etprsaplmsg["data"]);
        }else{
            $num2 = 0;
        }
        $data['apl'] = $num1+$num2;
        //待复审
        $con3 = array("a.status"=>"1001013",'a.iqbtId'=>session("iqbtId"));
        $etprsapl=getDataList("enterprise",$con3,"b.id","b.addtime desc",$join2);
        if(!empty($etprsapl["data"])){
            $num3=count($etprsapl["data"]);
            //如果已经审核过了，就不再显示
            foreach($etprsapl['data'] as $apl){
                $map = array(
                    'iqbtId'=>session('iqbtId'),
                    'adduserId'=>session('userId'),
                    'aplId' =>$apl['id'],
                );
                $checkMsg = findById('etprsAplIndexScore',$map,'id');
                if($checkMsg['code']==1 && !empty($checkMsg['data'])){
                    $num3 --;
                }
            }
        }else{
            $num3 = 0;
        }
        $data['reapl'] = $num3;
        //待分配房间
        $con4=array("a.status"=>"1027002",'a.iqbtId'=>session("iqbtId"));
        $roomaplmsg4=getDataList("etprsAplRoom",$con4,"a.id","a.addtime desc",$join1);
        if(!empty($roomaplmsg4["data"])){
            $num4=count($roomaplmsg4["data"]);
        }else{
            $num4 = 0;
        }
        $con5=array("a.status"=>"1001014",'a.iqbtId'=>session("iqbtId"));
        $etprsaplmsg5=getDataList("enterprise",$con5,"b.id","b.addtime desc",$join2);
        if(!empty($etprsaplmsg5["data"])){
            $num5=count($etprsaplmsg5["data"]);
        }else{
            $num5 = 0;
        }
        $data['room'] = $num4+$num5;
        //待入驻
        $con6=array("a.status"=>"1001015",'a.iqbtId'=>session("iqbtId"));
        $etprsaplmsg6=getDataList("enterprise",$con6,"b.id","b.addtime desc",$join2);
        if(!empty($etprsaplmsg6["data"])){
            $num6=count($etprsaplmsg6["data"]);
        }else{
            $num6 = 0;
        }
        $data['roomin'] = $num6;

        //租赁管理菜单提醒
        //1、续约管理
        $con7 = array('a.status'=>'1027001','a.iqbtId'=>session("iqbtId"));
        $renew = getDataList('etprsAplRenew',$con7,'a.id');
        if($renew['code'] ==1 && (!empty($renew['data']))){
            $num7 = count($renew['data']);
        }else{
            $num7 = 0;
        }
        //退出管理员审核
        $con8 = array('a.status'=>'1028001','a.iqbtId'=>session("iqbtId"));
        $quit = getDataList('etprsAplQuit',$con8,'a.id');
        if($quit['code'] ==1 && (!empty($quit['data']))){
            $num8 = count($quit['data']);
        }else{
            $num8 = 0;
        }
        //退出管理物业审核
        $con9 = array('a.status'=>'1028002','a.iqbtId'=>session('iqbtId'));
        $estate_quit = getDataList('etprsAplQuit',$con9,'a.id');
        if($estate_quit['code']==1 && (!empty($estate_quit['data']))){
            $num9 = count($estate_quit['data']);
        }else{
            $num9 = 0;
        }
        //退出财务管理员审核
        $con10 = array('a.status'=>'1028003','a.iqbtId'=>session('iqbtId'));
        $fince_quit = getDataList('etprsAplQuit',$con10,'a.id');
        if($fince_quit['code']==1 && (!empty($fince_quit['data']))){
            $num10 = count($fince_quit['data']);
        }else{
            $num10 = 0;
        }

        //园企互动提醒
        $con11 = array('a.status'=>0,'a.iqbtId'=>session('iqbtId'));
        $suggest = getDataList('EtprsSuggest',$con11,'a.id');
        if($suggest['code']==1 && (!empty($suggest['data']))){
            $num11 = count($suggest['data']);
        }else{
            $num11 = 0;
        }
        //会议室管理
        $con12 = array('a.status'=>0,'a.iqbtId'=>session('iqbtId'),'a.startTime'=>array('gt',time()));
        $meeting=getDataList("OaMeetroomApl",$con12,"a.id");
        if($meeting['code']==1 && (!empty($meeting['data']))){
            $num12 = count($meeting['data']);
        }else{
            $num12 = 0;
        }
        //公共服务活动
        $con13 = array('a.status'=>1,'a.iqbtId'=>session('iqbtId'));
        $activity = getDataList('activityApply',$con13,'a.id');
        if($activity['code']==1 && !empty($activity['data'])){
            $num13 = count($activity['data']);
        }else{
            $num13 = 0;
        }
        //资源管理
        $con14 = array('a.status'=>1,'a.iqbtId'=>session('iqbtId'));
        $resos = getDataList('resosResource',$con14,'a.id');
        if($resos['code']==1 && (!empty($resos['data']))){
            $num14 = count($resos['data']);
        }else{
            $num14 = 0;
        }
        //企业端公共活动
        $userId=session("userId");
        $join = [['activity_apply b','a.id=b.activityId and b.adduserId='.$userId,"left"]];
        $msg=getDataList("activity",array("a.iqbtId"=>session("iqbtId"),'a.close'=>'0','a.endTime'=>array("gt",time())),"a.id,a.name,a.startTime,a.endTime,a.desc,a.budget,a.appliUserId,a.status,a.close,b.status as aplstatus","a.close asc",$join);
        $num15 = 0;
        if($msg["code"]==="1"){
            for ($i = 0; $i < count($msg["data"]); $i++) {
                if(empty($msg['data'][$i]['aplstatus'])){
                    $num15 +=1;
                }
            }
        }
        //企业端问卷调查
        $conIvst=array('a.iqbtId'=>session("iqbtId"));
        $nowTime = time();
        $conIvst['startTime'] =array('elt',$nowTime);
        $conIvst['endTime'] = array('egt',$nowTime);
        $msg1=getDataList("ivst",$conIvst,"a.*"," a.id desc");

        $num16 = 0;
        if($msg1["code"]==="1"){
            //查询当前企业是否已经回答问卷了
            // $conRes=array('a.iqbtId'=>session("iqbtId"));
            $conRes=array('a.etprsId'=>session("user.etprsId"),'a.iqbtId'=>session("iqbtId"));
            //统计该问卷主题下问题的数目
            $ivstIds = getFieldArrry('IvstResult',$conRes,'ivstId');
            $resIds = array_unique($ivstIds);//已经回答过的主题
            foreach($msg1['data'] as $key=>$value){
                if(!empty($resIds)){
                    if(in_array($value['id'],$resIds)){
                        //已经回答过的过滤掉
                        unset($msg1['data'][$key]);continue;
                    }
                }
            }
            $num16 = count($msg1['data']);
        }
        //复审通知
        $con17 = array("a.status"=>"1001012",'a.iqbtId'=>session("iqbtId"));
        $num17  = 0;
        $etprsaplmsg=getDataList("enterprise",$con17,"a.id","");
        if(!empty($etprsaplmsg["data"])){
            $num17 = count($etprsaplmsg['data']);
        }

        //同意入驻
        $con18 = array('a.status'=>'1001013','a.iqbtId'=>session('iqbtId'));
        $num18 = 0;
        $entermsg = getDataList('enterprise',$con18,'a.id','');
        if(!empty($entermsg['data'])){
            $num18 = count($entermsg['data']);
        }

        //未缴费记录条数
        $con19=array("a.status"=>0,'a.iqbtId'=>session("iqbtId"));
        $num19 = 0;
        $unpayMsg = getDataList('feeRcd',$con19,'id');
        if($unpayMsg['code']==1 && !empty($unpayMsg['data'])){
            $num19 = count($unpayMsg['data']);
        }

        //待退费记录条数
        $con20 = array(
            'a.status'=>array('in',[0,1]),
            'a.iqbtId'=>session('iqbtId'),
        );
        $num20 = 0;
        $quitMsg = getDataList('feeQuitRcd',$con20,'id');
        if($quitMsg['code']==1 && !empty($quitMsg['data'])){
            $num20 = count($quitMsg['data']);
        }



        //返回待提醒的菜单数组
        $menuRemind = array(
            'rootmenu'=>array('7','15','27','40'),
            '7'=>array(
                '8'=>$num1+$num2,
                '9'=>$num17,
                '10'=>$num3,
                '11'=>$num18,
                '12'=>$num4+$num5,
                '13'=>$num6
            ),
            '15'=>array(
                '36'=>$num7,
                '33'=>$num8,
                '34'=>$num9,
                '35'=>$num10
            ),
            '27'=>array(
                '28'=>$num11,
                '29'=>$num12,
                '30'=>$num13,
                '58'=>$num15,
                '55'=>$num16,
                '81'=>$num14,
            ),
            '40'=>array(
                '42'=>$num19,
                '350'=>$num20,
            ),
        );
        //print_r($menuRemind);
        return $menuRemind;
    }










}