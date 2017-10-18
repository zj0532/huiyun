<?php
/**
 * Author:Lippor
 * Date:   2016年9月28日
 * Time:   上午07:18:40
 */
use think\Db;

/**
 * 新增，修改记录 可以按条件修改多条记录，不局限于ID
 * @param $table 表名
 * @param $saveData 保存数据（数组）array('id'=>1,'name'=>'张三')
 * @param $con 查询条件
 * @param $comments 修改、添加备注
 * @return array() 成功. array('code'=>'1','msg'=>'修改成功','data'=>'') data保存返回ID
 */
function saveDataByCon($table, $saveData, $con = array(), $comments = "")
{
    unset($saveData["s"]);
    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }

    if (!empty($con)) {
        //$id=$saveData["id"];
        if (empty($saveData)) {
            return returnResult("db_info", "db_noupdate_info");
        }
        $oldRecords = getDataList($table, $con);
        if ($oldRecords["code"] === '0') {
            //获取不到旧数据
            return returnResult("db_info", "db_norecord_err", 0);
        }
        //dump($oldRecords);
        try {
            Db::startTrans();
            foreach ($oldRecords["data"] as $oldData) {
                $id = $oldData["id"];
                $diffArr = array_diff_fun($oldData, $saveData);
                if (count($diffArr) > 0) {
                    $result = db($table)->where('id', $id)->update($saveData);
                    //echo db($table)->getLastSql();
                    if ($result !== false) {
                        saveActionRecord(config('action_type.edit'), $table, $id, $diffArr, $saveData, $comments, null);
                    } else {
                        $info = returnResult("db_info", "db_edit_err");
                        throw new \think\Exception($info['msg']);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
        }
        return returnResult("db_info", "db_edit_suc");
    } else {
        $id = db($table)->insertGetId($saveData);
        if ($id) {
            saveActionRecord(config('action_type.add'), $table, $id, null, null, $comments, null);
            return returnResult("db_info", "db_add_suc", $id);
        } else {
            return returnResult("db_info", "db_add_err");
        }
    }
}

/**
 * 新增，修改记录 只针对一条记录
 * @param $table 表名
 * @param $saveData 保存数据（数组）array('id'=>1,'name'=>'张三')
 * @param $comments 修改、添加备注
 * @return array() 成功. array('code'=>'0','msg'=>'用户名不存在','data'=>'') data保存返回ID
 */
function saveData($table, $saveData, $comments = "")
{
    unset($saveData["s"]);
    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    if (!empty($saveData["id"])) {
        unset($saveData["addtime"]);
        $id = $saveData["id"];
        $keyGroup = Array();
        foreach ($saveData as $key => $val) {
            array_push($keyGroup, $key);
        }
        if (count($keyGroup) == 1) {
            return returnResult("db_info", "db_noupdate_info", $id);
        }
        $oldRecord = findById($table, array('id' => $id));
        //halt($oldRecord);
        if (!empty($oldRecord["code"])&&$oldRecord["code"] == '0') {
            //获取不到旧数据
            return returnResult("db_info", "db_norecord_err");
        }
        $oldData = $oldRecord["data"];
        $diffArr = array_diff_fun($oldData, $saveData);
        if (count($diffArr) == 0) {
            return returnResult("db_info", "db_noupdate_info", $id);
        }
        try {
            $result = db($table)->update($saveData);
        } catch (\Exception $e) {
            return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
        }
        if ($result !== false) {
            saveActionRecord(config('action_type.edit'), $table, $id, $diffArr, $saveData, $comments, null);
            return returnResult("db_info", "db_edit_suc", $id);
        } else {
            return returnResult("db_info", "db_edit_err", 0);
        }
    } else {
        try {
            $id = db($table)->insertGetId($saveData);
        } catch (\Exception $e) {
            return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
        }
        if ($id) {
            saveActionRecord(config('action_type.add'), $table, $id, null, null, $comments, null);
            return returnResult("db_info", "db_add_suc", $id);
        } else {
            return returnResult("msg_info", "db_add_err");
        }
    }
    return $id;
}

/**
 * 删除数据（逻辑删除）
 * @param $table 表名
 * @param $idstr id字符串连接 如：1,3,4
 * @param $comments 操作描述
 * @return bool|int 返回修改记录条数。发生错误返回false
 */
function deleteData($table, $idstr, $comments='')
{
    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    $ids = explode(",", $idstr);
    if (count($ids) > 0) {
        $result = db($table)->where(array('isDelete' => 0, 'id' => array("in", $idstr)))->update(['isDelete' => 1]);
        if ($result !== false) {// && 0 != $result
            foreach ($ids as $id) {
                deleteActionRecord(config('action_type.delete'), $table, $id, $comments, null);
            }
            return returnResult("db_info", "db_delete_suc", $result);
        }
        return returnResult("db_info", "db_delete_err", 0);
    } else {
        return returnResult("db_info", "db_delete_err", 0);
    }
}
function deleteByCon($table, $arr, $comments='')
{
    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    if (count($arr) > 0) {
        $msg=getDataList($table,$arr,"id");
        if(!empty($msg["data"])){
            $result = db($table)->where($arr)->update(['isDelete' => 1]);
            if ($result !== false) {// && 0 != $result
                foreach ($msg["data"] as $data) {
                    deleteActionRecord(config('action_type.delete'), $table, $data["id"], $comments, null);
                }
                return returnResult("db_info", "db_delete_suc", $result);
            }
        }

        return returnResult("db_info", "db_delete_err", 0);
    } else {
        return returnResult("db_info", "db_delete_err", 0);
    }
}
/**
 * 根据ID获取一条记录
 * @param $table 表名
 * @param $con 查询条件
 * @param $field 指定查询字段字段
 * @param $isDelete 是否查询isDelete=1的
 * @return array|null 成功-array('id'=>1,'name'=>'张三')  不存在-null
 */
function findById($table, $con = array(), $field = "*",$join=array(), $isDelete = '0',$order="a.id asc")
{
    if ($isDelete === '0') {
        $con = is_string($con) ? (empty($con) ? "a.isDelete=0" : $con . " and a.isDelete=0") : array_merge($con, array('a.isDelete' => 0));
    }
    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    try {
        $info = db($table)->alias('a')->field($field)->join($join)->where($con)->order($order)->find();
        if (empty($info)) {
            return returnResult("db_info", "db_norecord_err");
        }
    } catch (\Exception $e) {
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
    }
    return array('code' => '1', 'msg' => '', 'data' => $info);
}
function findById2($table, $con = array(), $field = "*",$join=array(), $isDelete = '0',$order="a.id asc")
{
    if ($isDelete === '0') {
        $con = is_string($con) ? (empty($con) ? "a.isDelete=0" : $con . " and a.isDelete=0") : array_merge($con, array('a.isDelete' => 0));
    }
    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    try {
        $info = db($table)->alias('a')->field($field)->join($join)->where($con)->fetchSql(true)->find();
        echo db($table)->getLastSql();
        if (empty($info)) {
            return returnResult("db_info", "db_norecord_err");
        }
    } catch (\Exception $e) {
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
    }
    return array('code' => '1', 'msg' => '', 'data' => $info);
}

/**
 * 获取数据列表
 * @param $table 表名
 * @param array $con 查询条件
 * @param string $field 查询字段
 * @param string $order 查询字段
 * @param array $join 左右连接查询 $join = [ ['work w','a.id=w.artist_id'], ['card c','a.card_id=c.id']];
 * @param string $group group操作
 * @return array() 返回查询列表
 */
function getDataList($table, $con = array(), $field = "*", $order = "", $join = array(), $group = "",$limit="")
{
    $con = is_string($con) ? (empty($con) ? "a.isDelete=0" : $con . " and a.isDelete=0") : array_merge($con, array('a.isDelete' => 0));

    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    try {
        $result = db($table)->alias('a')
            ->field($field)
            ->join($join)
            ->group($group)
            ->where($con)
            ->order($order)
            ->select();
    } catch (\Exception $e) {
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
    }
    return array('code' => '1', 'msg' => '', 'data' => $result);
}

function getDataList2($table, $con = array(), $field = "*", $order = "", $join = array(), $group = "")
{
    $con = is_string($con) ? (empty($con) ? "a.isDelete=0" : $con . " and a.isDelete=0") : array_merge($con, array('a.isDelete' => 0));

    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    try {
        $result = db($table)->alias('a')
            ->field($field)
            ->join($join)
            ->group($group)
            ->where($con)
            ->order($order)
            ->fetchSql(true)->select();
        //var_dump(db($table)->getLastsql());
        echo db($table)->getLastSql();
    } catch (\Exception $e) {
        echo $e->getMessage();
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
    }
    return array('code' => '1', 'msg' => '', 'data' => $result);
}

/**
 * 获取数据列表
 * @param $table 表名
 * @param array $con 查询条件
 * @param string $field 查询字段
 * @param string $order 查询字段
 * @param array $join 左右连接查询 $join = [ ['work w','a.id=w.artist_id'], ['card c','a.card_id=c.id']];
 * @param string $group group操作
 * @param int $pageNum 页数，默认1
 * @param int $pageSize 页面大小，默认30
 * @return array() 返回查询列表
 */
function getPageDataList($table, $con = array(), $field = "*", $pageNum = 1, $pageSize = 10, $order = "", $join = array(), $group = "")
{
    $startNum = ($pageNum - 1) * $pageSize;
    $con = is_string($con) ? $con . " and isDelete=0" : array_merge($con, array('isDelete' => 0));

    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    $count=0;
    try {
        $result = db($table)
            ->field($field)
            ->join($join)
            ->group($group)
            ->where($con)
            ->order($order)
            ->limit($startNum, $pageSize)
            ->select();
        $count=db($table)->field("id")
            ->join($join)
            ->group($group)
            ->where($con)
            ->count();
    } catch (\Exception $e) {
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array(),'total'=>$count);
    }
    return array('code' => '1', 'msg' => '', 'data' => $result,'total'=>$count);
}

function getAppPageList($table, $con = array(), $field = "*", $pageNum = 1, $pageSize = 10, $order = "", $join = array(), $group = "")
{
    $startNum = ($pageNum - 1) * $pageSize;
    $con = is_string($con) ? $con . " and isDelete=0" : array_merge($con, array('isDelete' => 0));

    if (empty($table) || (!is_string($table))) {
        return returnResult("db_info", "db_tablename_err");
    }
    $count=0;
    try {
        $result = db($table)
            ->field($field)
            ->join($join)
            ->group($group)
            ->where($con)
            ->order($order)
            ->limit($startNum, $pageSize)
            ->select();
    } catch (\Exception $e) {
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array(),'pageSize'=>$pageSize);
    }
    return array('code' => '1', 'msg' => '', 'data' => $result,'pageSize'=>$pageSize);
}

/**
 * 获取单个字段在数据库中存取值
 * @param $table 表名
 * @param $con 查询条件
 * @param $field 需要返回字段
 * @return null|string 返回空或者字段值
 */
function getField($table, $con = array(), $field = "name",$default='')
{
    try {
        $field=trim($field);
        $data = findById($table, $con, $field);
        if ($data["code"] === '1') {
            $field=substr(" ".$field,strrpos(" ".$field," ")+1);
            return $data['data'][$field];
        }
    } catch (\Exception $e) {
        return $default;
    }
    return $default;
}
function getField2($table, $con = array(), $field = "name",$default='')
{
    try {
        $field=trim($field);
        $data = findById2($table, $con, $field);
        if ($data["code"] === '1') {
            $field=substr(" ".$field,strrpos(" ".$field," ")+1);
            return $data['data'][$field];
        }
    } catch (\Exception $e) {
        return $default;
    }
    return $default;
}
/**
 * 获取单个字段在数据库中列表，并以一维数组返回
 * @param $table 表名
 * @param $con 查询条件
 * @param $field 需要返回字段
 * @return null|array() 返回空或者一维数组 array('1','2','3')
 */
function getFieldArrry($table, $con = array(), $field = "id", $order = "", $join = array(), $group = array())
{
    $field=trim($field);
    $list = getDataList($table, $con, $field, $order, $join, $group);
    $field=substr(" ".$field,strrpos(" ".$field," ")+1);
    if (strpos($field, ".")) {
        list($d, $field) = explode(".", $field);
    }
    $result = array();
    if ($list['code'] === '1') {
        foreach ($list['data'] as $obj) {
            $result[] = $obj[$field];
        }
    }
    return $result;
}

/**
 * 获取两个字段，并以key=>value形式返回
 * @param $table 表名
 * @param $fields 需要返回字段（只限两个字段，用‘,’分割）
 * @param $con 查询条件（数组）
 * @return null|array() 返回空或者k=>v数组
 */
function gethashmap($table, $con = array(), $fields = "id,name", $sequence = "")
{
    $fieldArr = explode(",", $fields);
    if ($fieldArr < 2) {
        return array();
    }
    $return = array();
    try {
        $kfield = $fieldArr[0];
        $vfield = $fieldArr[1];
        $result = getDataList($table, $con, $fields, $sequence = "");
        if ($result['code'] === '1') {
            foreach ($result["data"] as $data) {
                $return[$data[$kfield]] = $data[$vfield];
            }
        }
    } catch (\Exception $e) {
        return array('code' => '0', 'msg' => $e->getMessage(), 'data' => array());
    }
    return returnResult("global_info", "global_operate_suc", $return);
}


/**
 * 增删改文件保存字段时候，设置files表文件状态
 * @param $ids 原id  1,2,3
 * @param $newIds 新id 2,3,4
 * @return array() 返回配置提示信息
 */
function setFileStatus($ids, $newIds)
{
    $arr = explode(",", $ids);
    $newArr = explode(",", $newIds);
    $common = array_intersect($arr, $newArr);
    $result = array();
    foreach ($arr as $id) {
        if (!in_array($id, $common)){//isDelete=1
            $data["id"] = $id;
            $data["isDelete"] = 1;
            $result[] = $data;
        }
    }
    foreach ($newArr as $id) {
        if (!in_array($id, $common)) {//isDelete=0
            $data["id"] = $id;
            $data["isDelete"] = 0;
            $result[] = $data;
        }
    }
    try {
        Db::startTrans();
        foreach ($result as $data) {
            $msg = saveData("sysFile", $data, "file");
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
    return returnResult("global_info", "global_operate_suc", "");
}

/**
 * 上传文件
 * @param $directory 存放目录，所有上传文件按类型分目录保存。
 * @return array data中保存files表ID
 * array (size=3)
 * 'code' => string '1' (length=1)
 * 'msg' => string '上传成功' (length=18)
 * 'data' =>string '1,2'//file表id
 */
function upload($directory = "", $extarr = array('jpg','JPG', 'gif','GIF', 'png','PNG','jpeg','GPEG', "xlsx", "docx", "rar", "zip", "doc", "xls", "txt", "ppt", "pptx", "pdf"), $dftsize =15,$ret='',$fmt="")
{
    $files = request()->file();
    $i = 0;
    $result = array();
    $rlt=array();
    //\think\Log::notice(json_encode($files));
    if(count($files)>0) {
        foreach ($files as $file) {
            $data = array();
            $fileInfo = $file->getInfo();

            $fileName = $fileInfo["name"];

            $filesize = $file->getSize();
            //判断文件大小
            if ($filesize > $dftsize * 1024 * 1024) {
                return returnResult("file_info", "file_size_err");
            }
            if (false !== strpos($fileName, ".")) {
                //$name = substr($fileName,0,strrpos($fileName,"."));
                $exts = substr($fileName, strrpos($fileName, ".") + 1, strlen($fileName) - strrpos($fileName, ".") - 1);
            } else {
                $exts = "";
            }
            $filesize=$filesize/(1024 * 1024);
            //判断文件类型
            if (!empty($extarr) && !in_array($exts, $extarr)) {
                return returnResult("file_info", "file_exts_err");
            }
            $tmppath = "/files/" . $directory;
            //$tmppath = "/huizhi/developer/filerepo/videoevlt/" . $directory;
            $savename = session("userId") . "-" . time() . "-" . rand(1, 100000) . "-" . $i . "." . $exts;
            $info = $file->move(ROOT_PATH . 'public' . $tmppath, "/" . $savename);
            $i++;
            $fullpath = $info->getPathName();
            if (false !== $info) {
                $data['fileName'] = $fileName;
                $data['savePath'] = substr($fullpath, strrpos($fullpath, $tmppath));
                $data['saveName'] = $savename;
                $data['exts'] = $exts;
                $data['size'] = $filesize;
                $data['uploadUserId'] = session('userId');
                $data['uploadTime'] = date('Y-m-d H:i:s', time());
                $id = db('sysFile')->insertGetId($data);
                if (!empty($id)) {
                    if (empty($ret)) {
                        $result[] = $id;
                    } else {
                        $result[] = $data['savePath'];
                    }

                    if(!empty($fmt)){
                        $rlt["id"]=$id;
                        $rlt["savepath"]=$data['savePath'];
                    }
                } else {
                    return returnResult("file_info", "file_upload_err");
                }
            } else {
                // 上传失败获取错误信息
                //\think\Log::notice("22");
                return returnResult("file_info", "file_upload_err", $file->getError());
            }
        }
    }else {
        // 上传失败获取错误信息
        //\think\Log::notice(json_encode($files));
        return returnResult("file_info", "file_upload_err", "上传失败");
    }
    if(!empty($fmt)){
        return returnResult("file_info", "file_upload_suc", $rlt);
    }else{
        return returnResult("file_info", "file_upload_suc", join(",", $result));
    }

}



function saveActionRecord($type, $object, $objectId, $diffArr, $newData, $comments, $extra)
{
    $data['objectType'] = $object;
    $data['actionType'] = $type;
    $data['objectId'] = $objectId;
    $data['comments'] = $comments;
    $data['extra'] = $extra;
    $data['actorId'] = session('userId');
    $data['iqbtId'] = session('iqbtId');
    $data['actTime'] = date('Y-m-d H:i:s', time());
    $id = db('sysAction')->insertGetId($data);

    if ($type === config('action_type.edit') && !empty($diffArr)) {
        saveFieldChange($id, $diffArr, $newData);
    }
    if ($id) {
        return $objectId;
    } else {
        return false;
    }
}

//保存工作日志记录
//$data ,数组，根据不同的操作菜单传递不同的数据
function saveOaLog($data=array()){
    if(empty($data)){
        return array('code'=>0,'msg'=>'数据不能为空');
    }
    $arr = array('iqbtId','isDelete','adduserId','addtime','etprsId','fmenuId','smenuId','objId','content');
    foreach($data as $key=>$value){
        if(!in_array($key,$arr)){
            return array('code'=>0,'msg'=>'数据参数错误，不存在'.$key.'键');
        }
    }
    if(!isset($data['iqbtId'])){
        $data['iqbtId'] = session('iqbtId');
    }
    if(!isset($data['adduserId'])){
        $data['adduserId'] = session('userId');
    }
    if(!isset($data['addtime'])){
        $data['addtime'] = time();
    }
    $id = db('oaActionlog')->insertGetId($data);
    if($id){
        return array('code'=>1,'msg'=>$id);
    }else{
        return array('code'=>0,'msg'=>'保存失败');
    }
}

/**
 * 设置checkbox为选中
 * @param $options
 * @param $idGroup
 * @return returnresult
 */
function setCbxCheck($options, $idGroup)
{
    $ids = explode(",", $idGroup);
    for ($i = 0; $i < count($options); $i++) {
        $id = $options[$i]["id"];
        $options[$i]['chk'] = 0;
        if (in_array($id, $ids)) {
            $options[$i]['chk'] = 1;
        }
    }
    return $options;
}

/**
 * 根据键值返回值字符串，多个之间用,隔开
 * @param $list hash表结构结构
 * @param $ids
 * @return string
 */
function getidlistText($list, $ids)
{
    $str = "";
    $idlist = explode(",", $ids);
    foreach ($idlist as $id) {
        if (isset($list[$id])) {
            $str .= "," . $list[$id];
        }
    }
    return trim($str, ",");
}
/**
 * 把表名转为tp使用结构类型
 * @param $table
 * @return string
 */
function tableToTp($table)
{
    $retTab = "";
    if (!empty($table)) {
        $tabletemp = $table;
        $retTab = strtoupper($tabletemp[0]);
        $b = false;
        for ($i = 1; $i < strlen($tabletemp); $i++) {
            $c = $tabletemp[$i];
            if ("_" != $c) {
                if ($b) {
                    $c = strtoupper($c);
                    $b = false;
                }
                $retTab = $retTab . $c;
            } else {
                $b = true;
            }
        }
    }
    return $retTab;
}
//辅助处理函数
function saveFieldChange($actionId, $diffArr, $newData)
{
    if(!empty($diffArr)){
        foreach ($diffArr as $key => $val) {
            $data['actionId'] = $actionId;
            $data['field'] = $key;
            $data['old'] = $val;
            $data['new'] = $newData[$key];
            db('sysHistory')->insert($data);
        }
    }
}

function deleteActionRecord($type, $object, $objectId, $comments, $extra)
{
    $data['objectType'] = $object;
    $data['actionType'] = $type;
    $data['objectId'] = $objectId;
    $data['comments'] = $comments;
    $data['extra'] = $extra;
    $data['actorId'] = session('userId');
    $data['actTime'] = date('Y-m-d H:i:s', time());
    $id = db('sysAction')->insert($data);
    if ($id) {
        return $objectId;
    } else {
        return false;
    }
}

/**
 * 比较两个数组，返回两个数组中的不同，并将第一个数组的数据返回。
 * @param $old
 * @param $new
 * @return array
 */
function array_diff_fun($old, $new)
{
    $diffArr = Array();
    foreach ($new as $key => $val) {
        if (isset($old[$key])) {
            if ($val != $old[$key]) {
                $diffArr[$key] = $old[$key];
            }
        }else{
            $diffArr[$key]='';
        }
    }
    return $diffArr;
}

/**
 * 组织返回结果数组
 * @param $code
 * @param $key
 * @param array $result
 * @return mixed
 */
function returnResult($code, $key, $result = array())
{
    $return = config($code . '.' . $key);

    $return['data'] = $result;
    return $return;
}

function i_array_column($input, $columnKey, $indexKey=null){
    if(!function_exists('array_column')){
        $columnKeyIsNumber  = (is_numeric($columnKey))?true:false;
        $indexKeyIsNull            = (is_null($indexKey))?true :false;
        $indexKeyIsNumber     = (is_numeric($indexKey))?true:false;
        $result                         = array();
        foreach((array)$input as $key=>$row){
            if($columnKeyIsNumber){
                $tmp= array_slice($row, $columnKey, 1);
                $tmp= (is_array($tmp) && !empty($tmp))?current($tmp):null;
            }else{
                $tmp= isset($row[$columnKey])?$row[$columnKey]:null;
            }
            if(!$indexKeyIsNull){
                if($indexKeyIsNumber){
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key))?current($key):null;
                    $key = is_null($key)?0:$key;
                }else{
                    $key = isset($row[$indexKey])?$row[$indexKey]:0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    }else{
        return array_column($input, $columnKey, $indexKey);
    }
}

/**
 * 聚合数据短信接口
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url,$params=false,$ispost=0){
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
    curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
    if( $ispost )
    {
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
        curl_setopt( $ch , CURLOPT_URL , $url );
    }
    else
    {
        if($params){
            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
        }else{
            curl_setopt( $ch , CURLOPT_URL , $url);
        }
    }
    $response = curl_exec( $ch );
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
    curl_close( $ch );
    return $response;
}

/*
    ***聚合数据（JUHE.CN）短信API服务接口PHP请求示例源码
    ***DATE:2015-05-25
 *  * @param  mobile  手机号码
 *  @param $tpl_value 模板变量
 *  @param $tpl_id   模板ID,是在聚合数据里设置的，默认是发送验证码的模板ID
*/
function sendSms($mobile,$tpl_value="",$tpl_id="31902"){
    $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
    $smsConf = array(
        'key'   => 'b6cb27cf1e6f9d084a4f92c0d060a06d', //您申请的APPKEY ,在聚合数据网站查看https://www.juhe.cn/
        'mobile'    => $mobile, //接受短信的用户手机号码
        'tpl_id'    =>$tpl_id, //您申请的短信模板ID，根据实际情况修改 31902，默认设置的发送手机验证码的模板
        'tpl_value' => $tpl_value    //您设置的模板变量，根据实际情况修改 例如'#code#=1234&#company#=聚合数据'
    );
    $content = juhecurl($sendUrl,$smsConf,1); //请求发送短信
    if($content){
        $result = json_decode($content,true);
        $error_code = $result['error_code'];
        if($error_code == 0){
            //状态为0，说明短信发送成功,返回短信ID
            return array('code' => '1', 'msg' =>'短息发送成功' , 'data' => $result['result']['sid']);
        }else{
            //状态非0，说明失败
            $msg = $result['reason'];
            return array('code'=>'0','msg'=>$msg,'data'=>$error_code);
        }
    }else{
        //返回内容异常，以下可根据业务逻辑自行修改
        return array('code'=>'0','msg'=>'短息发送失败，未知原因','data'=>'');
    }
}

/**
 * 产生随机字符串
 * @param    int $length 输出长度
 * @param    string $chars 可选的 ，默认为 0123456789
 * @return   string     字符串
 */
function get_random($length, $chars = '0123456789')
{
    $hash = '';
    $max  = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * 验证手机号码
 * @param $phone_mob  手机号码
 * @param $code  手机验证码
 * @param $period  验证码有效期，默认十分钟有效
 * @return bool
 */
function verifySmsCode($mobile,$code,$period="600")
{
    $map = array(
        'mobile' => $mobile,
        'msg' => trim($code),
        'type'=>0
    );
    $smsMsg = findById('SmsLog',$map,'*',array(),'0','id desc');
    if(($smsMsg['code']!="1") || (empty($smsMsg['data'])))
    {
        return array('code'=>'0','msg'=>'验证码错误','data'=>'');
    }
    $sms_info = $smsMsg['data'];
    if(time() - $sms_info['addtime'] > $period)
    {
        return array('code'=>'0','msg'=>'验证码已过期，请重新获取','data'=>'');
    }
    return  array('code'=>'1','msg'=>'验证成功','data'=>'验证成功');
}

/**
 * 管理员发送消息
 * @param $userId 接收人ids，可以是数组或者逗号隔开的字符串
 * @param $data  内容，包括主题、内容、时间等
 * @param string $optId 发送者ID，默认session（userId）
 */
function sendEmail($userId,$data,$optId=''){
    if(empty($userId)){
        return array('code'=>0,'msg'=>'参数为空');
    }
    if(!is_array($userId)){
        $idArr = explode(",",$userId);
    }else{
        $idArr = $userId;
    }
    if(empty($optId)){
        $optId = session("userId");
    }
    try {
        Db::startTrans();
        foreach ($idArr as $value) {
            $data['toUserId'] = $value;
            $data['oprtUserId'] = $optId;
            if(!isset($data['iqbtId'])){
                $data['iqbtId'] = session("iqbtId");
            }
            $msg = saveData("sysMsg", $data, "发送消息");
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
    return $msg;
}

    /**
     * excel文件导出
     * @param $fileName  导出文件名,
     * @param $headArr   表头数组
     * @param $data   二维数据数组
     * @param imgs array 需要导出图片的字段数组
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    function getExcel($fileName,$headArr,$data,$imgs = array()){
        //对数据进行检验
        if(empty($data) || !is_array($data)){
            $this->error('没有要导出的数据');
        }
        //检查文件名
        if(empty($fileName)){
            exit;
        }
        // H:i:s
        $date = date("YmdHis",time());
        $fileName .="_{$date}.xls";

        //创建PHPExcel对象，注意，不能少了\
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();

        //第一列设置报表列头
        $key = ord("A");
        foreach($headArr as $v){
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
            //设置水平居中
            $objPHPExcel->setActiveSheetIndex(0)->getStyle($colum)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置垂直居中
            $objPHPExcel->getActiveSheet()->getStyle($colum)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            // 设置个表格宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($colum)->setWidth(16);
            $key += 1;
        }
        // $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20); 单独设置某一列的宽度
        //  $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20); 单独设置某一列的宽度

        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();

        foreach($data as $key => $rows){ //行写入
            $span = ord("A");
            foreach($rows as $keyName=>$value){// 列写入
                $j = chr($span);
                //如果需要导出图片到excel,需要改写名称
                if(in_array($keyName,$imgs) ){
                    // 图片生成
                    $objDrawing[$key] = new \PHPExcel_Worksheet_Drawing();
                    $objDrawing[$key]->setPath($value);
                    // 设置宽度高度
                    $objDrawing[$key]->setHeight(80);//照片高度
                    $objDrawing[$key]->setWidth(80); //照片宽度
                    /*设置图片要插入的单元格*/
                    $objDrawing[$key]->setCoordinates($j.$column);
                    // 图片偏移距离
                    $objDrawing[$key]->setOffsetX(12);
                    $objDrawing[$key]->setOffsetY(12);
                    $objDrawing[$key]->setWorksheet($objPHPExcel->getActiveSheet());
                }else{
                    $objActSheet->setCellValue($j.$column, $value);
                }
                $span++;
            }
            $objActSheet->getRowDimension($key+2)->setRowHeight(30);
            $column++;
        }
        $fileName = iconv("utf-8", "gb2312", $fileName);
        //重命名表
        // $objPHPExcel->getActiveSheet()->setTitle('test');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control:must-revalidate， post-check=0， pre-check=0');
        header('Content-Type:application/force-download');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');
        header('Content-Transfer-Encoding:binary');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载
        exit;
    }

/**
 * 获取孵化器所有有role_id权限的管理员ID
 * @param string $role_id 权限ID，8是入孵申请的权限
 * @return array  返回管理员ID数组
 */
function getAdminIds($role_id='8',$is_gz=true){
    $rolemap = array(
        'iqbtId'=>session('iqbtId'),
        'isDelete'=>0,
    );
    //获取当前孵化器下所有的角色，筛选出包含role_id的角色
    $roleMsg = getDataList('UserRole',$rolemap,'id,rolename,menuIds');
    $roleIds = array();
    if($roleMsg['code']==1 &&(!empty($roleMsg['data']))){
        foreach($roleMsg['data'] as $value1){
            if(!empty($value1['menuIds'])){
                $arr = explode(",",$value1['menuIds']);
                if(in_array($role_id,$arr)){
                    $roleIds[] = $value1['id'];
                }
            }
        }
    }
    if(empty($roleIds)){
        return array();
    }
    $map = array(
        'iqbtId'=>session('iqbtId'),
        'userCate'=>'1011001',
        'status'=>'1012001',
    );
    if($is_gz == true){
        $map['is_gz'] ='1';
    }
    $userIds = array();
    //获取所有管理员用户
    $userMsg = getDataList('user',$map,'id,name,roleIds');
    if($userMsg['code'] ==1 &&(!empty($userMsg['data']))){
        foreach($userMsg['data'] as $key=>$value){
            if(in_array($value['roleIds'],$roleIds)){
                $userIds[] = $value['id'];
            }
        }
    }
    return $userIds;
}

//验证是否是手机号码
function isMobile($mobile){
    if(empty($mobile)){
        return false;
    }
    if(preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
        return true;
    }else{
       return false;
    }
}

/**
 * 文件、图片由ID获取具体保存地址
 * @param string $fileArr 要查询的字段 如： array('idcardfile'=>234)
 * @return array  返回的数组  如： array('idcardfile'=>'file/default/20170809/384448484.jpg')
 */
function filePath($fileArr=''){
    if(!empty($fileArr)){
        $ids = array();
        foreach($fileArr as $key=>$value){
            if(!empty($value)){
                $ids[] = $value;
            }
        }
        $files = array();
        if(!empty($ids)){
            $msg=getDataList("sysFile",array("id"=>array("in",$ids)),"id,savePath,fileName");
            if(!empty($msg["data"])){
                $files=$msg["data"];
            }
        }
        $result = array();
        foreach($fileArr as $key1=>$value1){
            $result[$key1] = '';
            foreach($files as $val){
                if($val['id'] == $value1){
                    $result[$key1] = $val['savePath'];
                }
            }
        }
        return $result;
    }else{
        return array();
    }
}

//获取企业ID，根据etprsID
function getuserHeader($etprsId=''){
    if(empty($etprsId)){
        return '';
    }
    //获取企业头像 从user表里获取
    $con = array(
        'etprsId'=>$etprsId,
        'iqbtId'=>session('iqbtId'),
        'userCate'=>'1011002',
    );
    $userheader = getField('user',$con,'userheader');
    if(!empty($userheader)){
        $logo = getField('sysFile',array('id'=>$userheader),'savePath');
    }else{
        $logo = '';
    }
    return $logo;
}






