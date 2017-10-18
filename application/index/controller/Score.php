<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/31 0015
 * Time: 上午 10:17
 * 复审评分指标控制器
 */

namespace app\index\controller;
use think\Db;
use think\Exception;

class Score extends Common{

	//指标列表
	public function index(){
		return view();
	}
	public function getList(){
		 $con = array(
		 		'parentId'=>0,
		 		'level'=>1
	 	        );
         $con["a.iqbtId"]=session("user.iqbtId");
        
        $msg=getDataList("EtprsAplIndex",$con,"a.*"," a.sort desc");
        if($msg["code"]==="1"){
            //查询二级指标
            foreach($msg['data'] as $key=>$value){
                $map = array(
                    'parentId'=>$value['id'],
                    'level'=>2,
                    'iqbtId'=>session("user.iqbtId")
                );
                $conMsg = getDataList('EtprsAplIndex',$map,'a.*',"a.sort desc");
                if($conMsg['code']==1){
                    $msg['data'][$key]['child'] = $conMsg['data'] ;
                    $msg['data'][$key]['count'] = count($conMsg['data']);
                }else{
                    $msg['data'][$key]['child'] = array();
                }
            }
            return $msg["data"];
        }else{
            return array();
        }
	}

	public function addScore($parentId=0){
        $c=array();
        $id=input("id");
        if(!empty($id)){
            $msg=findByid("EtprsAplIndex",array("id"=>$id,'iqbtId'=>session("user.iqbtId")),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }else{
            $c=array('parentId'=>$parentId);
        }
        return view("",array("data"=>$c));
	}

	public function saveScore(){
		$postData=input("request.");
		if(empty($postData['parentId'])){
			$postData['level'] = 1;
		}else{
			$postData['level'] = 2;
		}
        if(empty($postData['score']) ||!is_numeric($postData['score']) ||$postData['score']<=0){
            return array('code'=>0,'msg'=>'所占分值不能为空且必须为大于0的数值。');
        }
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $table="EtprsAplIndex";
        $msg= saveData($table,$postData,"添加/修改指标");
        return $msg;
	}

	public function delScore(){
 		$id=input("id");
        $idStr = '';
        $msg = findById('etprsAplIndex',array('id'=>$id),'*');
        if(!empty($msg['data'])){
            if($msg['data']['parentId']=='0'){
                //如果删除一级指标，则自动删除二级指标
                $secondIds = getFieldArrry('etprsAplIndex',array('parentId'=>$id),'id');
                if(!empty($secondIds)){
                    $idStr = implode(",",$secondIds);
                    $idStr = trim($idStr,",").",".$id;
                }else{
                    $idStr = $id;
                }
            }else{
                $idStr = $id;
            }
        }
      //  print_r($idStr);exit();
        return deleteData("EtprsAplIndex",$idStr,"删除指标");
	}
}