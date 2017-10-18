<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/15 0015
 * Time: 上午 10:17
 * 问卷调查控制器
 */

namespace app\index\controller;
use think\Db;
use think\Exception;

class Ivst extends Common{

    //获取问卷主题列表
    public function getIvst($name=""){
        $con = array();
        $con["a.iqbtId"]=session("iqbtId");
        if($name!=""){
            $con['a.name'] = array('like','%'.$name.'%');
        }
        $msg=getDataList("ivst",$con,"a.*"," a.id desc");
        if($msg["code"]==="1"){
            //统计该问卷主题下问题的数目
            foreach($msg['data'] as $key=>$value){
                $map = array(
                    'isDelete'=>0,
                    'ivstId'=>$value['id']
                );
                $quest_num = db('IvstQues')->where($map)->count();
                $msg['data'][$key]['count'] = $quest_num ;
            }
            return $msg["data"];
        }else{
            return array();
        }
    }
    //添加问卷主题
    public function addIvst(){
        $id=input("id");
        $c=array();
        if(!empty($id)){
            $msg=findByid("ivst",array("id"=>$id,'iqbtId'=>session('iqbtId')),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }
        return view("",array("data"=>$c));
    }
    //保存问卷主题
    public function saveIvst(){
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("iqbtId");
        $postData["adduserId"]=session("userId");
        $postData["startTime"]=strtotime($postData["startTime"]);
        $postData["endTime"]=strtotime($postData["endTime"]);
        $table="ivst";
        $msg= saveData($table,$postData,"添加/修改问卷主题");
        return $msg;
    }
    //删除问卷主题
    public function delIvst(){
        $id=input("id");
        return deleteData("ivst",$id,"删除问卷主题");
    }

    //问题列表
    public function quesList($id=""){
        if(empty($id)){
           $this->error("参数错误");
        }
        $name = getField('ivst',array('id'=>$id,'iqbtId'=>session('iqbtId')),'name'," ");
        $data = array(
            'ivstId'=>$id,
            'name'=>$name
        );
        return view('queslist',array('data'=>$data));
    }
    //获取问题列表
    public function getQues($ivstId="",$title=""){
        if(empty($ivstId)){
            $this->error("参数错误");
        }
        $con = array('ivstId'=>$ivstId);
        $con["a.iqbtId"]=session("iqbtId");
        if($title!=""){
            $con['a.title'] = array('like','%'.$title.'%');
        }
        $msg=getDataList("IvstQues",$con,"a.*"," a.sort desc");
        if($msg["code"]==="1"){
            //统计该问卷主题下问题的数目
            foreach($msg['data'] as $key=>$value){
                $map = array(
                    'isDelete'=>0,
                    'quesId'=>$value['id']
                );
                $quest_num =  db('IvstOpt')->where($map)->count();
                $msg['data'][$key]['count'] = $quest_num;
                $msg2 = getDataList("IvstOpt",array('quesId'=>$value['id']),"a.*");
                if($msg2['code'] == 1){
                    $msg['data'][$key]['opts'] = $msg2['data'];
                }else{
                    $msg['data'][$key]['opts'] = array();
                }
            }
            return $msg['data'];
        }else{
            return array();
        }
    }
    //添加问题
    public function addQues($ivstId =""){
        if(empty($ivstId)){
            $this->error("参数错误");
        }
        $c=array();
        $id=input("id");
        if(!empty($id)){
            $msg=findByid("IvstQues",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }else{
            $c=array('ivstId'=>$ivstId);
        }
        return view("",array("data"=>$c));
    }
    //保存问题数据
    public function saveQues(){
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("user.iqbtId");
        $postData["adduserId"]=session("userId");
        $table="IvstQues";
        $msg= saveData($table,$postData,"添加/修改问题");
        return $msg;
    }
    //删除问题
    public function delQues(){
        $id=input("id");
        return deleteData("IvstQues",$id,"删除问题");
    }

    //添加选项
    public function addOpt($ivstId ="",$quesId = ""){
        if(empty($ivstId)){
            $this->error("参数错误");
        }
        if(empty($quesId)){
            $this->error("参数错误");
        }
        $c=array();
        $id=input("id");
        if(!empty($id)){
            $msg=findByid("IvstOpt",array("id"=>$id),"*");
            if($msg["code"]==='1'){
                $c=$msg["data"];
            }
        }else{
            $c=array('ivstId'=>$ivstId,'quesId'=>$quesId);
        }
        return view("",array("data"=>$c));
    }

    //保存选项
    public function saveOpt(){
        $postData=input("request.");
        $postData["addtime"]=time();
        $postData["iqbtId"]=session("iqbtId");
        $postData["adduserId"]=session("userId");
        $table="IvstOpt";
        $msg= saveData($table,$postData,"添加/修改问题选项");
        return $msg;
    }
    //删除选项
    public function delOpt(){
        $id=input("id");
        return deleteData("IvstOpt",$id,"删除问题选项");
    }

    //统计问卷调查
    public function ivstStat($id=""){
        if(empty($id)){
            $this->error("参数错误");
        }
        $con["a.iqbtId"]=session("iqbtId");
        $con = array('ivstId'=>$id);
        $data = array();
        //查询当前主题所有的投票人数
        $total_array = getFieldArrry('IvstResult',$con,'etprsId');
        if(!empty($total_array)){
            $total_num = count(array_unique($total_array));
        }else{
            //暂时没有投票
            $total_num = 0;
        }
        //查询当前主题下的所有问题
        $msgQues = getDataList('IvstQues',$con,'id,title, types','sort desc');
        if($msgQues['code'] ==1) {
            $quesList = $msgQues['data'];
            foreach ($quesList as $key => $value) {
                $data[$key]['ques'] = $value;
                //查询当前问题的所有选项，如果是文本的话，再论
                $map = array(
                    'ivstId' => $id,
                    'quesId' => $value['id']
                );
                $msgOpt = getDataList('IvstOpt', $map, 'id,title');
                if ($msgOpt['code'] == 1) {
                    $optList = $msgOpt['data'];
                    foreach ($optList as $key2 => $value2) {
                        $map2 = array(
                            'ivstId' => $id,
                            'quesId' => $value['id'],
                            'optId' => $value2['id'],
                            'isDelete'=>0,
                        );
                        $map2["iqbtId"]=session("iqbtId");
                        $optData = $value2;
                        if ($total_num > 0) {
                            $optCount = db('IvstResult')->where($map2)->count();
                            $optPer = sprintf("%.2f", $optCount / $total_num) * 100;
                        } else {
                            $optCount = 0;
                            $optPer = 0;
                        }
                        $optData['optCount'] = $optCount;
                        $optData['optPer'] = $optPer;
                        $data[$key]['opt'][] = $optData;
                    }
                }else{
                    $data[$key]['opt'] = array();
                }


            }
        }else{
            //没有问题
                $data = array();
        }
        $data_json = json_encode($data);
       // print_r($data_json);exit();
        return view('ivststat',array('data'=>$data,'total'=>$total_num,'json'=>$data_json));

    }


    //获取问卷主题列表
    public function getAnswer(){
       $con=array('a.iqbtId'=>session("iqbtId"));
        $nowTime = time();
        $con['startTime'] =array('elt',$nowTime);
        $con['endTime'] = array('egt',$nowTime);

        $msg=getDataList("ivst",$con,"a.*"," a.id desc");
        if($msg["code"]==="1"){
            //查询当前企业是否已经回答问卷了
          // $conRes=array('a.iqbtId'=>session("iqbtId"));
           $conRes=array('a.etprsId'=>session("user.etprsId"),'a.iqbtId'=>session("iqbtId"));
            //统计该问卷主题下问题的数目
            $ivstIds = getFieldArrry('IvstResult',$conRes,'ivstId');
            $resIds = array_unique($ivstIds);//已经回答过的主题
            foreach($msg['data'] as $key=>$value){
                if(!empty($resIds)){
                    if(in_array($value['id'],$resIds)){
                        //已经回答过的过滤掉
                        unset($msg['data'][$key]);continue;
                    }
                }
                $map = array(
                    'isDelete'=>0,
                    'ivstId'=>$value['id']
                );
                $quest_num = db('IvstQues')->where($map)->count();
                $msg['data'][$key]['count'] = $quest_num ;
            }
            return $msg["data"];
        }else{
            return array();
        }
    }

    //问卷详情页
    public function answerDetail($id=""){
        if(empty($id)){

            $this->error("参数错误");
        }
        $con = array('ivstId'=>$id);
        $con["a.iqbtId"]=session("iqbtId");
        $ivstData = findById('ivst',array('id'=>$id),'id,name,desc,endTime');
        $this->assign('ivstData',$ivstData['data']);
        $msg=getDataList("IvstQues",$con,"a.*"," a.sort desc");
        if($msg["code"]==="1"){
            //查询每个问题下选项
            foreach($msg['data'] as $key=>$value){
                $msg2 = getDataList("IvstOpt",array('quesId'=>$value['id']),"a.*");
                if($msg2['code'] == 1){
                    $msg['data'][$key]['opts'] = $msg2['data'];
                }else{
                    $msg['data'][$key]['opts'] = array();
                }
            }
            return view('answer',array('data'=>$msg['data']));
        }else{
            return view('answer',array('data'=>array()));
        }
    }

    //答问卷
    public function saveAnswer($ivst=""){
        if(empty($ivst)){
            return array('code'=>0,'msg'=>'参数错误');
        }
        $postData=input("request.");
        $optlist = array();
        foreach($postData as $key=>$value){
            $quesId = ltrim(trim($key),'q');
            //分割字符串，CheckBox的格式是"1,3,4"类型
            $arr = explode(',',$value);
            foreach($arr as $value2){
                $optlist[] = array('quesId'=>$quesId,'optId'=>$value2);
            }

        }

        try {
            Db::startTrans();
            foreach ($optlist as $opt) {
                $Data['ivstId'] = $ivst;
                $Data['quesId'] = intval($opt['quesId']);
                $Data['optId'] = $opt['optId'];
                $Data["addtime"]=time();
                $Data["iqbtId"]=session("iqbtId");
                $Data['etprsId'] = session("user.etprsId");
                $table="IvstResult";
                $msg= saveData($table,$Data,"添加问卷答案");
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
       return array('code'=>1,'msg'=>'感谢您的回答');

    }


}