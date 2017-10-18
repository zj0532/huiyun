<?php
namespace app\app\controller;
use app\index\controller\Common;
use think\Controller;
use think;
use think\Config;
use think\Log;
use JPush\Client as JPush;
class Appcommon extends Common{
    /**
     * 架构函数
     * @param Request    $request     Request对象
     * @access public
     */
    public function __construct(){
        parent::__construct();
    }

    public function _initialize()  {
        $controller=request()->controller();
        $action=request()->action();
        $module= request()->module();
        $data=input("request.");
        $token=$data["token"];
        if(empty($token)){
            $this->redirect(url('/app/Login/golog',['code'=>'s']));
        }else{
            $tkmsg=findById("user",array("token"=>$token),"id,iqbtId");
            if(empty($tkmsg["data"])){
                $this->redirect(url('/app/Login/golog',['code'=>'o']));
            }
            session('userId',$tkmsg['data']['id']);
            session('iqbtId',$tkmsg['data']['iqbtId']);
        }
        Config::load(APP_PATH.'customConfig.php');

        //self::initMenus("/".$module."/".$controller."/".$action);
    }



    /***
     *  $table="",$data=array(),$where=array()
     */
    function updateData()
    {
        $data=input("request.");
        //Log::notice(json_decode($data["data"],true));
        $msg=saveDataByCon($data["table"],json_decode($data["data"],true),json_decode($data["where"],true));
        return json($msg);
    }




}