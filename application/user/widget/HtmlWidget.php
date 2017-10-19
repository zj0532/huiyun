<?php
namespace app\user\Widget;

use Think\Controller;
class HtmlWidget extends Controller {

    function initDictSelect($con,$default="",$order="code",$firstOption=false,$firstValue="")
    {
        $optHtml="";
        $table="SysDict";
        $field="code,name";
        $con=self::parseCon($con);
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=gethashmap($table,$con,$field,$order);
        if($firstOption){
            $optHtml="<option value='".$firstValue."'>".$firstValue."</option>";
        }

        if($msg["code"]==='1'&&!empty($msg["data"])){
            foreach ($msg["data"] as $code => $name) {
                $optHtml.="<option value='$code' ".($default==$code?'selected':'').">$name</option>";
            }
        }
        return $optHtml;
    }
    /*
     * $iptname 性别
     * $con     查询条件
     * $default 默认
     * $order   排序
     * $con
     */
    function initDictRedio($iptname,$con,$default="",$order="code",$con=array())
    {
        $optHtml="";
        $table="SysDict";
        $field="code,name";
        //将多个查询条件分开
        $con=self::parseCon($con);
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            $i=0;
            foreach ($msg["data"] as $code => $name) {
                $optHtml.='<div class="radio radio-success radio-inline"><input type="radio" id="'.$iptname.$i.'" value="'.$code.'" name="'.$iptname.'" '.($default==$code?"checked":"").'><label for="'.$iptname.$i.'">'.$name.'</label></div>';
                $i++;
            }
        }
        return $optHtml;
    }

    function initDictCheckbox($iptname,$con,$default="",$order="code",$con=array())
    {
        $optHtml="";
        $table="SysDict";
        $field="code,name";
        $con=self::parseCon($con);
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            $i=0;
            foreach ($msg["data"] as $code => $name) {
                $optHtml.='<div class="checkbox checkbox-success checkbox-inline"><input type="checkbox" id="'.$iptname.$i.'" value="'.$code.'" name="'.$iptname.'" '.(in_array($code,explode(",",$default))?'checked':'').'><label for="'.$iptname.$i.'"> '.$name.' </label></div>';
                $i++;
            }
        }
        return $optHtml;
    }
    function initCommonSelect($table,$con=array(),$default="",$field="id,name",$order="id")
    {
        $con=self::parseCon($con);
        $optHtml="";
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            foreach ($msg["data"] as $code => $name) {
                $optHtml.="<option value='$code' ".($default==$code?'selected':'').">$name</option>";
            }
        }
        return $optHtml;
    }
    function initCommonCheckbox($iptname,$table,$con=array(),$default="",$field="id,name",$order="id")
    {
        $con=self::parseCon($con);
        $optHtml="";
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            $i=0;
            foreach ($msg["data"] as $code => $name) {
                $optHtml.='<div class="checkbox checkbox-success checkbox-inline"><input type="checkbox" id="'.$iptname.$i.'" value="'.$code.'" name="'.$iptname.'" '.(in_array($code,explode(",",$default))?'checked':'').'><label for="'.$iptname.$i.'"> '.$name.' </label></div>';
                $i++;
            }
        }
        return $optHtml;
    }
    function initStaticCheckbox($iptname,$table,$con=array(),$default="",$field="id,name",$order="id")
    {
        $con=self::parseCon($con);
        $optHtml="";
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            $i=0;
            foreach ($msg["data"] as $code => $name) {
                $optHtml.='<div class="checkbox checkbox-success checkbox-inline"><input type="checkbox" txt="'.$name.'" id="'.$iptname.$i.'" value="'.$code.'" name="'.$iptname.'" '.(in_array($code,explode(",",$default))?'checked':'').'><label for="'.$iptname.$i.'"> '.$name.' </label></div>';
                $i++;
            }
        }
        return $optHtml;
    }
    //遍历查询条件是否是数组，如果是分开条件
    function parseCon($con)
    {
        $where =array();
        foreach($con as $k=>$v){
            //is_array 检测变量是否是数组
            if(is_array($v)){
                $v[0]=trim($v[0]);
                $where[$k]=$v;
            }else{
                $where[$k]=$v;
            }
        }
        return $where;
    }

    function initEtprsSelect($con)
    {
        $id=session("userId");
        $user=array();
        $umsg=findById("user",array("id"=>$id),"id,etprsId");
        if($umsg["code"]==='1'){
            $user=$umsg["data"];
        }
        $optHtml="";

        if(!empty($user)){
            $table='enterprise';
            $field="id,name";
            $order="id";
            $con=self::parseCon($con);

            //return var_dump($con);
            if(!isset($con["status"])||empty($con["status"])){
                $con["status"]="1001015";
            }
            if(!empty($user["etprsId"])){
                $con["id"]=array("in",$user["etprsId"]);
            }
            $msg=gethashmap($table,$con,$field,$order);
            if($msg["code"]==='1'&&!empty($msg["data"])){
                foreach ($msg["data"] as $code => $name) {
                    $optHtml.="<option value='$code'>$name</option>";
                }
            }
        }

        return $optHtml;
    }

    function initaplDictCheckbox($iptname,$con,$default="",$order="code",$con=array())
    {
        $optHtml="";
        $table="SysDict";
        $field="code,name";
        $con=self::parseCon($con);
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            $i=0;
            foreach ($msg["data"] as $code => $name) {
                $optHtml.='<label><input type="checkbox" id="'.$iptname.$i.'" value="'.$code.'" name="'.$iptname.'" '.(in_array($code,explode(",",$default))?'checked':'').'><span> '.$name.' </span></label>&nbsp;&nbsp;&nbsp;';
                $i++;
            }
        }
        return $optHtml;
    }

    function initAplDictRedio($iptname,$con,$default="",$order="code",$con=array())
    {
        $optHtml="";
        $table="SysDict";
        $field="code,name";
        $con=self::parseCon($con);
        if(!empty(session("iqbtId"))){
            $con["concat(',',exceptIqbt,',')"]=array("notlike",'%,'.session("iqbtId").',%');
        }
        $msg=gethashmap($table,$con,$field,$order);
        if($msg["code"]==='1'&&!empty($msg["data"])){
            $i=0;
            foreach ($msg["data"] as $code => $name) {
                $optHtml.='<label><input type="radio" value="'.$code.'" name="'.$iptname.'" '.($default==$code?"checked":"").'><p>'.$name.'</p></label>';
                $i++;
            }
        }
        return $optHtml;
    }

}