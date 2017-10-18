<?php
namespace app\service\controller;
use think\Controller;

use app\index\controller\Common;
use think\Db;
use think\Exception;
class External extends Common{
    //外部资源
    function getExternal(){
        $con=array('iqbtId'=>session("iqbtId"));
        $table="ResosExternal";
        $sequence="addtime desc";//排序
        $msg=getDataList($table,$con,"*",$sequence);

        if($msg["code"]=="1"&&!empty($msg["data"])){
            $resos=$msg["data"];
            $tmplist=self::getDictStr("*",$table);
            $resos=$this->setListIdText($resos,$tmplist);
            return $resos;

        }else{
            return array();
        }
    }
    function addExternal()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("ResosExternal",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveExternal()
    {
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("iqbtId");
        $postData["addUserId"]=session("userId");
        $postData["deadline"]=strtotime($postData["deadline"]);
        $table="ResosExternal";
        $msg= saveData($table,$postData,"添加/修改 外部资源");
        //工作日志
        if(empty($postData['id'])){
            $logData = array(
                'etprsId'=>0,
                'fmenuId'=>27,
                'smenuId'=>61,
                'objId'=>$msg['data'],
                'content'=>'新增了第三方资源：'.$postData['name']
            );
            saveOaLog($logData);
        }
        return $msg;
    }
    function deleteExternal(){
        $id=input("id");
        $name = getField('ResosExternal',array('id'=>$id),'name');
        $res = deleteData("ResosExternal",$id,"删除外部资源");
        if($res['code']==1){
            $logData = array(
                'etprsId'=>0,
                'fmenuId'=>27,
                'smenuId'=>61,
                'objId'=>$id,
                'content'=>'删除了第三方资源：'.$name
            );
            saveOaLog($logData);
        }
        return $res;
    }
    function getPlatExternal($page="1",$search="",$pageSize='10')
    {
        $table="ResosExternal";
        $sequence="addtime desc";//排序
        /*$con=array('iqbtId'=>session("iqbtId"),'deadline'=>array('gt',time()));//审核通过
        if(!empty($search)){
            $con["name|desc"]=array("like","%".$search."%");
        }*/
        $con="iqbtId=".session("iqbtId")." and (deadline>".time()." or deadline=0) ";
        if(!empty($search)){
            $con=$con." and (name like '%".$search."%' or `desc` like '%".$search."%')";
        }
        $msg=getPageDataList($table,$con,"*",$page,$pageSize,$sequence);
        for ($i = 0; $i < count($msg["data"]); $i++) {
            if(empty($msg["data"][$i]["deadline"])){
                $msg["data"][$i]["deadlinetime"]="永久";
            }else{
                $msg["data"][$i]["deadlinetime"]=date("Y-m-d",$msg["data"][$i]["deadline"]);
            }

        }
        $msg["pageSize"]=$pageSize;
        return $msg;
    }

    function etprsExternal()
    {
        return view("");
    }
    function external()
    {
        return view("");
    }
    function externaldetail($id)
    {
        if(!empty($id)){
            $msg=findById("ResosExternal",array("id"=>$id));
            if($msg["code"]==="1"){
                return view("detail",array("data"=>$msg["data"]));
            }
        }
    }


    //企业每次查看第三方的联系信息，就记录一次，代表着服务次数
    function contact($id='')
    {
        if (empty($id)) {
            return array('code' => 0, 'msg' => '参数错误');
        }

        $msg = findById('ResosExternal', array('id' => $id), 'contact,mobile,email,sernum');
        if (!empty($msg['data'])) {
            //每点一次，服务次数增加一次
            $num = $msg['data']['sernum'] + 1;
            saveDataByCon('ResosExternal', array('sernum' => $num), array('id' => $id));
        }

        return $msg;
    }
    function detail($id=0)
    {

        $data=array();
        if(!empty($id)){
            $msg=findById("ResosExternal",array("id"=>$id));
            if(!empty($msg["data"])){

                $dictcon=array("code"=>$msg["data"]["category"]);
                if(!empty(session("iqbtId"))){
                    $dictcon["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
                }
                $msg["data"]["category"]=getField("sysDict",$dictcon,"name");
                $data=$msg["data"];
            }
        }
        return view("",array("data"=>$data));

    }
}