<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: ���� 9:37
 * //�ͻ���ϵ���������
 */
namespace app\index\controller;
use think\Exception;

class Crm extends Common{

    //��ȡ�ͻ��б�
    public function getCrm($name="",$status=""){
        $con = array();
        $con["a.iqbtId"]=session("iqbtId");
         $con['a.etprsId'] = session("etprsId");
        if($name!=""){
            $con['a.name'] = array('like','%'.$name.'%');
        }
        if($status!=""){
            $con["a.status"]=$status;
        }
        $msg=getDataList("CrmCstmor",$con,"a.*"," a.id desc");
        if($msg["code"]==="1"){
            //��ѯ����ϵ�ͻ���������ϵ��
            foreach($msg['data'] as $key=>$value){
                $map = array(
                    'crmId'=>$value['id']
                );
                $conMsg = getDataList('CrmContact',$map,'a.*');
                if($conMsg['code']==1){
                    $msg['data'][$key]['con'] = $conMsg['data'] ;
                }else{
                    $msg['data'][$key]['con'] = array();
                }
            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    //��ӿͻ�
    public function addCrm(){
        $id=input("id");
        $c=array();

        if(!empty($id)){
            $msg=findByid("CrmCstmor",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    //����ͻ�
    public function saveCrm(){
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData['etprsId']= session("user.etprsId");
        $postData["adduserId"]=session("userId");
        $table="CrmCstmor";
        $msg= saveData($table,$postData,"���/�޸Ŀͻ���Ϣ");
        return $msg;
    }
    //ɾ���ͻ�
    public function delCrm(){
        $id=input("id");
        return deleteData("CrmCstmor",$id,"ɾ���ͻ���Ϣ");
    }


    //������ϵ��
    public function addContact($crmId=""){
        if(empty($crmId)){
            $this->error("��������");
        }
        $c=array();
        $id=input("id");
        if(!empty($id)){
            $msg=findByid("CrmContact",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }else{
            $c=array('crmId'=>$crmId);
        }
        return view("",array("data"=>$c));
    }

    //������ϵ����Ϣ
    public function saveContact(){
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData['etprsId']= session("user.etprsId");
        $postData["adduserId"]=session("userId");
        $table="CrmContact";
        $msg= saveData($table,$postData,"���/�޸���ϵ��");
        return $msg;
    }

    //ɾ����ϵ��
    public function delContact(){
        $id=input("id");
        return deleteData("CrmContact",$id,"ɾ����ϵ��");
    }

    //�������
    public function crmFollow($crmId=""){
        if(empty($crmId)){
            $this->error("��������");
        }
        $name = getField('CrmCstmor',array('id'=>$crmId),'name'," ");
        $data = array(
            'crmId'=>$crmId,
            'name'=>$name
        );
        return view('',array('data'=>$data));
    }

    public function getFollow($crmId=""){
        if(empty($crmId)){
            $this->error("��������");
        }
        $con = array();
        // $con["a.iqbtId"]=session("user.iqbtId");
        // $con['a.etprsId'] = session("user.etprsId");
        $con['crmId'] = $crmId;
        $msg=getDataList("CrmFollow",$con,"a.*"," a.id desc");
        if($msg["code"]==="1"){

            return $msg["data"];
        }else{
            return array();
        }
    }
    //��Ӹ�����¼
    public function addFollow($crmId = ""){
        if(empty($crmId)){
            $this->error("��������");
        }
        $c=array();
        $id=input("id");
        if(!empty($id)){
            $msg=findByid("CrmFollow",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }else{
            $c=array('crmId'=>$crmId);
        }
        return view("",array("data"=>$c));
    }

    //��������
    public function saveFollow(){
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData['etprsId']= session("user.etprsId");
        $postData["adduserId"]=session("userId");
        $table="CrmFollow";
        $msg= saveData($table,$postData,"���/�޸ĸ�����¼");
        return $msg;
    }

    //ɾ����¼
    public function delFollow(){
        $id=input("id");
        return deleteData("CrmFollow",$id,"ɾ��������¼");
    }

}


