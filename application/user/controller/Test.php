<?php
namespace app\user\controller;
use think\Controller;

use think\Db;
use think\Exception;

class Test extends Controller{
    function index()
    {
        return view();
    }

    function getNews($nid,$nowpage=1,$pagesize=2)
    {
        $startNum=($nowpage-1)*$pagesize;

        $con=array('nid'=>$nid);
        $result = db("news")
            ->field("*")
            ->where($con)
            ->limit($startNum, $pagesize)
            ->select();
        $count=db("news")->field("id")
            ->where($con)
            ->count();

        $data["data"]=$result;
        $data["nowpage"]=$nowpage;
        $data["pagesize"]=$pagesize;
        $data["nid"]=$nid;
        $pagetotal=0;
        if($count%$pagesize==0){
            $pagetotal=intval($count/$pagesize);
        }else{
            $pagetotal=intval($count/$pagesize+1);
        }
        $data["pagetotal"]=$pagetotal;
        $nextpage=0;
        $prepage=0;
        if($nowpage>1){
            $prepage=$nowpage-1;
        }
        if($nowpage<$pagetotal){
            $nextpage=$nowpage+1;
        }
        $data["prepage"]=$prepage;
        $data["nextpage"]=$nextpage;
        return $data;
    }
}