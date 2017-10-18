<?php
namespace app\index\controller;
use think\Controller;

use think\Db;
use think\Exception;

class Config extends Common{
    //
    function getDict()
    {
        $con["flag"]=array("in","2,3");
        //$con["iqbtId"]=session("user.iqbtId");
        $msg=getDataList("SysDict",$con,"*","code asc");
        if($msg["code"]==="1"){
            $dicts=$msg["data"];
            $root=array();
            foreach ($dicts as $dict) {
                if($dict["level"]=='1'){
                    $root[]=$dict;
                }
            }
            $root=self::setDictFmt($root,$dicts);
            return $root;
        }else{
            return array();
        }
    }

    function dataDict()
    {
        $iqbtId=0;
        if(!empty(session("iqbtId"))){
            $iqbtId=session("iqbtId");
        }
        return view("",array("iqbtId"=>$iqbtId));
    }

    function getDataDict()
    {
        $con["flag"]=4;
        $con["iqbtId"]=array("in","0,".session("iqbtId"));
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }

        $msg=getDataList("SysDict",$con,"*","code asc");
        if($msg["code"]==="1"){
            $dicts=$msg["data"];
            $root=array();
            foreach ($dicts as $dict) {
                if($dict["level"]=='1'){
                    $root[]=$dict;
                }
            }
            $root=self::setDictFmt($root,$dicts);
            return $root;
        }else{
            return array();
        }
    }

    function setDictFmt($root,$dicts)
    {
        for ($i = 0; $i < count($root); $i++) {
            $level=$root[$i]["level"]+1;
            $code=$root[$i]["code"];
            $root[$i]["child"]=array();
            foreach ($dicts as $dict) {
                if($dict["level"]>=$level&&substr($dict['code'],0,($level-1)*3+1)==$code){
                    $root[$i]["child"][]=$dict;
                }
            }
            //$root[$i]["child"]=self::setDictFmt($root[$i]["child"],$dicts);
        }
        return $root;
    }
    function addDict()
    {
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("SysDict",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    function saveDict(){
        $postData=input("request.");
        $table="SysDict";
        if(empty($postData["code"])){
            unset($postData["code"]);
        }else{
            $msg=findById("SysDict",array("code"=>$postData["code"]),"id");
            if(!empty($msg["data"])){
                return array("code"=>0,"msg"=>"字典编码 【".$postData["code"]."】 错误");
            }
        }
        if(empty($postData["level"])){
            unset($postData["level"]);
        }
        if(empty($postData["flag"])){
            unset($postData["flag"]);
        }
        $postData["iqbtId"]=session("iqbtId");
        return saveData($table,$postData,"添加/修改字典信息");
    }
    //添加子级字典
    function addCldDict($code,$level,$flag)
    {
        $newCode=$code;
        $con=array('level'=>$level+1,"code"=>array("like",$code."%"));
        $msg=findById("SysDict",$con,"max(code) as code");
        if(!empty($msg["data"]["code"])){
            $newCode=$msg["data"]["code"]+1;
        }else{
            $newCode=$newCode."001";
        }
        $postData["iqbtId"]=session("iqbtId");
        return view("addDict",array("code"=>$newCode,"level"=>$level+1,'flag'=>$flag));
    }

    /*function saveDict(){
        $postData=input("request.");
        $table="SysDict";
        return saveData($table,$postData,"添加/修改字典信息");;
    }*/

    function deleteDict($id,$code,$level){
        $msg=findById("SysDict",array("code"=>array('like',$code."%"),'level'=>array("<>",$level)));
        if($msg["code"]==='1'&&!empty($msg["data"])){
            return array('code'=>0,'msg'=>'有下级字典，删除失败！');
        }

        $cmsg=findById("SysDict",array("id"=>$id));
        if(!empty($cmsg["data"])){
            if($cmsg["data"]["iqbtId"]==session("iqbtId")){
                //删除，孵化器自己添加的
                return deleteData("SysDict",$id,"删除字典信息");
            }else{
                //非删除，管理员添加的公共字典
                $v=$cmsg["data"]["exceptIqbt"];
                return saveDataByCon("SysDict",array('exceptIqbt'=>$v.",".session("iqbtId")),array("id"=>$id));
            }
        }else{
            return array('code'=>1,'msg'=>'删除成功');
        }
    }

    //系统设置->首页模块设置
    /* function homepage(){
         return view();
     }*/

    function getHomepage(){
        $table="UserHomepage";
        $msg=getDataList($table,array(),"*");
        return $msg["data"];
    }

    function addHomepage(){
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("UserHomepage",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveHomepage(){
        $postData=input("request.");
        if(empty($postData['id'])){
            $postData['addtime'] = time();
        }
        $msg= saveData("UserHomepage",$postData,"添加/修改首页模块");
        return $msg;
    }

    function delHomepage(){
        $id=input("id");
        return deleteData("UserHomepage",$id,"删除首页模块");
    }

    //钉钉设置
    function dingding(){
        $c=array();
        $iqbtId=session("iqbtId");
        if(!empty($iqbtId)){
            $msg=findByid("dingCfg",array("iqbtId"=>$iqbtId),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }

    function saveDingCfg(){
        $postData=input("request.");
        if(empty($postData['id'])){
            $postData['addtime'] = time();
            $postData['iqbtId'] = session("iqbtId");
        }
        $msg= saveData("dingCfg",$postData,"添加/修改首页模块");
        return $msg;
    }
    //钉钉结束


    //生成企业申请链接
    function apllink()
    {
        if(isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']){
            $htp="https";
        }else{
            $htp="http";
        }
        $url=$htp.'://'.$_SERVER["SERVER_NAME"];
        $iqbtId=session("iqbtId");
        $etprsIqbtId=session("etprsIqbtId");
        return view("",array("url"=>$url,'iqbtId'=>$iqbtId,'etprsIqbtId'=>$etprsIqbtId));
    }

    /***
     *
     */
    function enterstep()
    {
        $iqbtId=session("iqbtId");
        $data=[];
        $msg=findById("enterStep",array("iqbtId"=>$iqbtId));
        if(!empty($msg["data"])){
             $data=$msg["data"];
        }
        return view("",array("data"=>$data));
    }

    /***
     *保存入驻流程
     */
    function saveEnterStep($name='',$ischeck='1')
    {
        if(empty($name)){
            return array("code"=>0,"msg"=>"参数错误");
        }
        $iqbtId=session("iqbtId");
        $msg=findById("enterStep",array("iqbtId"=>$iqbtId));
        if(!empty($msg['data'])){
             return saveDataByCon("enterStep",array($name=>$ischeck),array("iqbtId"=>$iqbtId));
        }else{
            return saveData("enterStep",array($name=>$ischeck,"iqbtId"=>$iqbtId));
        }
    }


}