<?php
namespace app\service\controller;
use think\Controller;
use app\index\controller\Common;
use think\Db;
use think\Exception;

class Report extends Common
{
    function index()
    {
        $etprsId = session("etprsId");
        return view("",array("etprsId"=>$etprsId));
    }
    //---------------------------START---------考核-----------------------------------------------------------------------------------------
    //会议室管理
    function getReports($etprsId = 0){

        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        $msg = getDataList("Report", $con, "*");
        if ($msg["code"] === "1") {
            $rpts=$msg["data"];
            for ($i = 0; $i < count($rpts); $i++) {
                $rpts[$i]["addtime"]=date("Y-m-d H:i:s",$rpts[$i]["addtime"]);
            }
            return $rpts;
        } else {
            return array();
        }
    }

    //考核基本信息  ------------------start------------------
    function addReport()
    {
        $id = input("id");
        $c = array();
        $fls = [];
        $file=[];
        if (!empty($id)) {
            $msg = findByid("Report", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            } else {
                return $msg;
            }

            $msg2 = findByid("ReportFinance", array("etprsId" => $c["etprsId"], 'tag' => $c["tag"]), "*");
            if ($msg2["code"] === '1') {
                $fls = $msg2["data"];
            } else {
                $fls["tag"] = $c["tag"];
            }
            $msg3 = findByid("ReportFiles", array("etprsId" => $c["etprsId"], 'tag' => $c["tag"]), "*");
            if ($msg3["code"] === '1') {
                $file = $msg3["data"];
            } else {
                $file["tag"] = $c["tag"];
            }
        } else {
            $etprsId = session("etprsId");
            $c["tag"] = time();
            $fls["tag"]=$c["tag"];
            $file["tag"]=$c["tag"];
            $c["etprsId"] = $etprsId;
        }
        return view("", array("data" => $c, 'fls' => $fls,'file'=>$file));
    }

    function detail()
    {
        $id = input("id");
        $c = array();
        $fls = [];
        $file=[];
        if (!empty($id)) {
            $msg = findByid("Report", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            } else {
                return $msg;
            }

            $msg2 = findByid("ReportFinance", array("etprsId" => $c["etprsId"], 'tag' => $c["tag"]), "*");
            if ($msg2["code"] === '1') {
                $fls = $msg2["data"];
            } else {
                $fls["tag"] = $c["tag"];
            }
            $msg3 = findByid("ReportFiles", array("etprsId" => $c["etprsId"], 'tag' => $c["tag"]), "*");
            if ($msg3["code"] === '1') {
                $file = $msg3["data"];
            } else {
                $file["tag"] = $c["tag"];
            }
        } else {
            $etprsId = session("etprsId");
            $c["tag"] = time();
            $fls["tag"]=$c["tag"];
            $file["tag"]=$c["tag"];
            $c["etprsId"] = $etprsId;
        }
        return view("", array("data" => $c, 'fls' => $fls,'file'=>$file));
    }

    function saveReport()
    {
        //jiangchengzi
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "Report";

        if(empty($postData["name"])){
            $msg=findById("enterprise",array("id"=>$postData["etprsId"]),"name");
            if(!empty($msg['data'])){
                 $postData["name"]=$msg["data"]["name"];
            }
        }

        $msg = saveData($table, $postData, "添加/修改考核信息");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }
    //考核基本信息  ------------------end------------------
    //产品  ------------------start------------------------
    function getPdts($etprsId = 0, $tag = ''){
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportProduct", $con, "*");
        if ($msg["code"] === "1") {
            $pdt=$msg["data"];
            $tmplist=self::getDictStr("*","ReportProject");
            $pdt=$this->setListIdText($pdt,$tmplist);
            return $pdt;
        } else {
            return array();
        }
    }

    function addPdt($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportProduct", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function savePdt()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportProduct";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deletePdt($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportProduct", array("id" => $id));
            deleteByCon("ReportProduct", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }
    //产品  ------------------end------------------
    //入驻以来承担的国家、省市科技计划项目情况  ------------------start------------------
    function getItems($etprsId = 0, $tag = '')
    {
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportItem", $con, "*");
        if ($msg["code"] === "1") {
            return $msg["data"];
        } else {
            return array();
        }
    }

    function addItem($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportItem", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function saveItem()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportItem";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deleteItem($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportItem", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }
    //入驻以来承担的国家、省市科技计划项目情况  ------------------end------------------
    //入驻以来获得企业软件著作权情况  ------------------start------------------
    function getSoft($etprsId = 0, $tag = '')
    {
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportSoft", $con, "*");
        if ($msg["code"] === "1") {
            return $msg["data"];
        } else {
            return array();
        }
    }

    function addSoft($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportSoft", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function saveSoft()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportSoft";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deleteSoft($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportSoft", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }
    //入驻以来获得企业软件著作权情况  ------------------end------------------
    //入驻以来企业专利（申请）情况  ------------------start------------------
    function getPatent($etprsId = 0, $tag = '')
    {
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportPatent", $con, "*");
        if ($msg["code"] === "1") {
            return $msg["data"];
        } else {
            return array();
        }
    }

    function addPatent($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportPatent", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function savePatent()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportPatent";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deletePatent($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportPatent", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }
    //入驻以来企业专利（申请）情况  ------------------end------------------
    ////项目  ------------------start------------------
    function getProject($etprsId = 0, $tag = '')
    {
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportProject", $con, "*");
        if ($msg["code"] === "1") {
            $pjt=$msg["data"];
            $tmplist=self::getDictStr("*","ReportProject");
            $pjt=$this->setListIdText($pjt,$tmplist);
            return $pjt;
        } else {
            return array();
        }
    }

    function addPjt($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportProject", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function saveProject()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportProject";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deleteProject($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportProject", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            //throw new \think\Exception($e->getMessage());
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }
    //项目  ------------------end------------------
    ////融资  ------------------start------------------
    function addFinance()
    {
        $id = input("id");
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportFinance", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        }
        return view("", array("data" => $c));
    }

    function saveFinance()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportFinance";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }
    //融资  ------------------end------------------
    ////文件  ------------------start------------------
    function addFiles()
    {
        $id = input("id");
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportFiles", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        }
        return view("", array("data" => $c));
    }

    function saveFiles()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportFiles";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }
    //文件 ------------------end------------------
    ////企业管理制度建设情况  ------------------start------------------
    function getSys($etprsId = 0, $tag = '')
    {
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportSys", $con, "*");
        if ($msg["code"] === "1") {
            return $msg["data"];
        } else {
            return array();
        }
    }

    function addSys($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportSys", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function saveSys()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportSys";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deleteSys($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportSys", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            //throw new \think\Exception($e->getMessage());
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }
    //企业管理制度建设情况  ------------------end------------------

    //企业配合基地情况  ------------------start--------------------
    function getact($etprsId = 0, $tag = '')
    {
        $con = array('iqbtId' => session("iqbtId"));
        if (!empty($etprsId)) {
            $con["etprsId"] = $etprsId;
        }
        if (!empty($tag)) {
            $con["tag"] = $tag;
        }
        $msg = getDataList("ReportActivity", $con, "*");
        if ($msg["code"] === "1") {
            return $msg["data"];
        } else {
            return array();
        }
    }

    function addAct($tag = '', $id = 0)
    {
        $c = array();
        if (!empty($id)) {
            $msg = findByid("ReportActivity", array("id" => $id), "*");
            if ($msg["code"] === '1') {
                $c = $msg["data"];
            }
        } else {
            $c["tag"] = $tag;
        }
        return view("", array("data" => $c));
    }

    function saveAct()
    {
        $postData = input("request.");
        $postData["iqbtId"] = session("iqbtId");
        $postData["etprsId"] = session("user.etprsId");
        $postData["adduserId"] = session("userId");
        $postData["addtime"] = time();
        $table = "ReportActivity";
        $msg = saveData($table, $postData, "添加/修改");
        if (!empty($msg['data'])) {
            self::initReport($postData["tag"], $postData["etprsId"]);
        }
        return $msg;
    }

    function deleteAct($id = 0)
    {
        if (empty($id)) {
            return array('code' => '0', 'msg' => '', 'data' => "id不能为空");
        }
        try {
            $msg1 = deleteByCon("ReportActivity", array("id" => $id));
            return $msg1;
        } catch (\Exception $e) {
            //throw new \think\Exception($e->getMessage());
            return array('code' => '0', 'msg' => $e->getMessage(), 'data' => []);
        }
    }

    //企业配合基地情况  ------------------end------------------
    function deleteReport($tag = "",$etprsId='')
    {
        if (empty($tag)||empty($etprsId)) {
            return array('code' => '0', 'msg' => '', 'data' => "考核标示不能为空");
        }
        try {
            $msg1 = deleteByCon("report", array("tag" => $tag));
            $msg1 = deleteByCon("ReportProduct", array("tag" => $tag));
            $msg1 = deleteByCon("reportItem", array("tag" => $tag));
            $msg1 = deleteByCon("reportSoft", array("tag" => $tag));
            $msg1 = deleteByCon("reportPatent", array("tag" => $tag));
            $msg1 = deleteByCon("ReportProject", array("tag" => $tag));
            $msg1 = deleteByCon("ReportFinance", array("tag" => $tag));
            $msg1 = deleteByCon("ReportFiles", array("tag" => $tag));
            $msg1 = deleteByCon("reportSys", array("tag" => $tag));
            $msg1 = deleteByCon("reportActivity", array("tag" => $tag));
            return array('code' => '1', 'msg' => "删除成功", 'data' => []);
        } catch (\Exception $e) {
            //throw new \think\Exception($e->getMessage());
            return json(array('code' => '0', 'msg' => $e->getMessage(), 'data' => []));
        }
    }

    function wordData($tag = '', $etprsId = 0)
    {
        if (empty($tag) || empty($etprsId)) {
            echo  "企业或者考核标示不能为空";
        }
        $con = array("tag" => $tag);
        $con["etprsId"] = $etprsId;
        $report = array();
        $msg1 = findById("report", $con, "*");
        if (!empty($msg1["data"])) {
            $report = $msg1["data"];
        }

        $pdts = array();
        $msg2 = getDataList("ReportProduct", $con, "*");
        if (!empty($msg2["data"])) {
            $pdts = $msg2["data"];
            $tmplist=self::getDictStr("*","ReportProduct");
            $pdts=$this->setListIdText($pdts,$tmplist);
        }

        $items = array();
        $msg3 = getDataList("reportItem", $con);
        if (!empty($msg3["data"])) {
            $items = $msg3["data"];
        }

        $softs = array();
        $msg4 = getDataList("reportSoft", $con);
        if (!empty($msg4["data"])) {
            $softs = $msg4["data"];
        }

        $patents = array();
        $msg5 = getDataList("reportPatent", $con);
        if (!empty($msg5["data"])) {
            $patents = $msg5["data"];
        }

        $pjts = array();
        $msg6 = getDataList("ReportProject", $con, "*");
        if (!empty($msg6["data"])) {
            $pjts = $msg6["data"];
            $tmplist=self::getDictStr("*","ReportProject");
            $pjts=$this->setListIdText($pjts,$tmplist);

        }

        $fls = array();
        $msg7 = findById("ReportFinance", $con, "*");
        if (!empty($msg7["data"])) {
            $fls = $msg7["data"];
        }

        $syses = array();
        $msg8 = getDataList("reportSys", $con);
        if (!empty($msg8["data"])) {
            $syses = $msg8["data"];
        }

        $acts = array();
        $msg9 = getDataList("reportActivity", $con);
        if (!empty($msg9["data"])) {
            $acts = $msg9["data"];
        }

        $data = array('report' => $report, "pdts" => $pdts, "items" => $items, "softs" => $softs, "patents" => $patents, 'pjts' => $pjts, 'fls' => $fls, 'syses' => $syses, 'acts' => $acts);
        self::word($data);
    }

    function initReport($tag = '', $etprsId = 0)
    {
        $report = "report";
        $flance = "ReportFinance";
        $file = "ReportFiles";
        $msg1 = findById($report, array("tag" => $tag, "etprsId" => $etprsId), "id");
        if ($msg1["code"]==='0') {
            $emsg = findById("enterprise", array("id" => $etprsId));
            $etprs = $emsg["data"];
            $data["name"] = $etprs["name"];
            $data["tag"] = $tag;
            $data["etprsId"] = $etprsId;
            $data["iqbtId"] = session("iqbtId");
            $data["adduserId"] = session("userId");
            $data["addtime"] = time();
            saveData($report, $data);
        }
        $msg2 = findById($flance, array("tag" => $tag, "etprsId" => $etprsId), "id");
        if ($msg2["code"]==='0') {
            $data["tag"] = $tag;
            $data["etprsId"] = $etprsId;
            $data["iqbtId"] = session("iqbtId");
            $data["adduserId"] = session("userId");
            $data["addtime"] = time();
            saveData($flance, $data);
        }
        $msg3 = findById($file, array("tag" => $tag, "etprsId" => $etprsId), "id");
        if($msg3["code"]==='0'){
            $data["tag"] = $tag;
            $data["etprsId"] = $etprsId;
            $data["iqbtId"] = session("iqbtId");
            $data["adduserId"] = session("userId");
            $data["addtime"] = time();
            saveData($file, $data);
        }

    }

    function initRptFiles($tag='',$etprsId=0)
    {
        if(empty($tag)||empty($etprsId)){
            return array('code' => '0', 'msg' => '', 'data' => "参数错误");
        }
        $msg=findById("ReportFiles",array("tag"=>$tag,"etprsId"=>$etprsId));
        if(!empty($msg)){
             return $msg;
        }else{
            return array('code' => '1', 'msg' => '', 'data' => []);
        }
    }

    function product($id=0)
    {
        $pdt=[];
        $msg=findById("ReportProduct",array("id"=>$id));
        if(!empty($msg)){
            $pdt=$msg["data"];
        }
        $tmplist=self::getDictStr("*","ReportProject");
        $pdt=$this->setObjIdText($pdt,$tmplist);
        return view("", array("data" => $pdt));
    }
    function project($id=0)
    {
        $pdt=[];
        $msg=findById("ReportProject",array("id"=>$id));
        if(!empty($msg)){
            $pdt=$msg["data"];
        }
        $tmplist=self::getDictStr("*","ReportProject");
        $pdt=$this->setObjIdText($pdt,$tmplist);
        return view("", array("data" => $pdt));
    }

    function delete($tab='',$id=0)
    {
        if(empty($tab)){
            return array('code' => '0', 'msg' => '', 'data' => "表名不能为空");
        }
        return deleteByCon($tab, array("id" => $id));
    }

    function word($data=[])
    {
        $report = $data["report"];
        $pdts = $data["pdts"];
        $items = $data["items"];
        $softs = $data["softs"];
        $patents = $data["patents"];
        $pjts = $data["pjts"];
        $fls = $data["fls"];
        $syses = $data["syses"];
        $acts = $data["acts"];

        vendor("PHPWord");
        vendor("PHPWord.IOFactory");

        // New Word Document
        $PHPWord = new \PHPWord();

        // New portrait section
        $PHPWord->addParagraphStyle('pStyle', array('align' => 'center'));
        $PHPWord->addFontStyle('titleStyle', array('bold' => true, 'color' => '000000', 'size' => 16));
        //首页 ---start------------------------------------------------------------------------------------------------------------------------------------
        $section = $PHPWord->createSection();
        $section->addTextBreak(1);
        $section->addTextBreak(1);
        $PHPWord->addFontStyle('rStyle', array('bold' => true, 'color' => '000000', 'size' => 22));
        $PHPWord->addParagraphStyle('pStyle', array('align' => 'center'));
        $section->addText('新疆上海科技合作基地', 'rStyle', 'pStyle');
        $section->addTextBreak(1);
        $section->addText('2016年度在孵企业考核表', 'rStyle', 'pStyle');
        $section->addTextBreak(1);
        $PHPWord->addFontStyle('rStyle2', array('bold' => false, 'color' => '000000', 'size' => 12));
        //下划线：,'underline'=>\PHPWord_Style_Font::UNDERLINE_SINGLE
        $section->addText('(适用于初创期和成长型企业)', 'rStyle2', 'pStyle');
        $section->addTextBreak(8);

        $PHPWord->addParagraphStyle('pStylet', array('align' => 'left'));
        $PHPWord->addFontStyle('iStyle', array('bold' => false, 'color' => '000000', 'size' => 16));
        $section->addText('        企 业 名 称：' . $report["name"], 'iStyle', 'pStylet');
        $section->addTextBreak(1);
        $section->addText('        企业负责人：' . $report["gm"], 'iStyle', 'pStylet');
        $section->addTextBreak(1);
        $section->addText('        联 系 电 话：' . $report["gmmobile"], 'iStyle', 'pStylet');
        $section->addTextBreak(1);
        $section->addText('        固定联络员：' . $report["liaison"], 'iStyle', 'pStylet');
        $section->addTextBreak(1);
        $section->addText('        联 系 电 话：' . $report["lsmobile"], 'iStyle', 'pStylet');
        $section->addTextBreak(1);
        $section->addText('        填 表 日 期：' . date("Y-m-d", $report["addtime"]), 'iStyle', 'pStylet');
        $section->addTextBreak(12);
        $PHPWord->addFontStyle('iStyle2', array('bold' => true, 'color' => '000000', 'size' => 18));
        $section->addText('新疆上海科技合作基地', 'iStyle2', 'pStyle');
        $section->addText('二○一七年制', 'iStyle2', 'pStyle');
        //首页 ---end------------------------------------------------------------------------------------------------------------------------------------
        //企业基本情况 ---start----------------------------------------------------------------------------------------------------------------------------------
        $section2 = $PHPWord->createSection();
        $section2->addText('一、企业基本情况', 'iStyle');
        // Define table style arrays
        $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80);
        // Add table style
        $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);

        // Add table
        $table = $section2->addTable('myOwnTableStyle');
        $fontStyle = array('bold' => true, 'align' => 'center', 'valign' => "center",'size'=>12);

        // Add more rows / cells
        $table->addRow(400);
        $table->addCell(2600, array('cellMerge' => 'restart', 'valign' => "center"))->addText("企业名称", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'valign' => "center"))->addText($report["name"]);
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600)->addText("成立时间", $fontStyle);
        $table->addCell(1400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["rgsttime"]);
        $table->addCell(1000, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("入驻时间", $fontStyle);
        $table->addCell(1000, array('cellMerge' => 'continue'));
        $table->addCell(1400)->addText($report["entertime"]);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("入驻面积", $fontStyle);
        $table->addCell(500, array('cellMerge' => 'continue'));
        $table->addCell(1400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["area"]);
        $table->addCell(500, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600)->addText("注册资本", $fontStyle);
        $table->addCell(1400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["rgstment"]);
        $table->addCell(1000, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("法定代表人", $fontStyle);
        $table->addCell(1000, array('cellMerge' => 'continue'));
        $table->addCell(1400)->addText($report["lealPerson"]);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("联系电话", $fontStyle);
        $table->addCell(500, array('cellMerge' => 'continue'));
        $table->addCell(1400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["mobile"]);
        $table->addCell(500, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600)->addText("注册地址", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["address"]);
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600)->addText("生产场地", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["factory"]);
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600)->addText("所属行业", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["industry"]);
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(2000)->addText("总经理", $fontStyle);
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["gm"]);
        $table->addCell(800, array('cellMerge' => 'continue'));
        $table->addCell(800, array('cellMerge' => 'continue'));
        $table->addCell(800, array('cellMerge' => 'continue'));
        $table->addCell(3000)->addText("联系电话", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["gmmobile"]);
        $table->addCell(800, array('cellMerge' => 'continue'));
        $table->addCell(800, array('cellMerge' => 'continue'));
        $table->addCell(800, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(2000)->addText("固定联络员", $fontStyle);
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["liaison"]);
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(3000)->addText("联系电话", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["lsmobile"]);
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('企业从业人员情况', $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("总数", $fontStyle);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => false, 'valign' => "center"))->addText($report["total"]);
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('其中', $fontStyle);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('博士', $fontStyle);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["doctor"]);
        $table->addCell(1000, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('其中', $fontStyle);
        $table->addCell(2000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('管理人员', $fontStyle);
        $table->addCell(200, array('cellMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["manage"]);
        $table->addCell(1200, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('硕士', $fontStyle);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["postgrad"]);
        $table->addCell(1000, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('研发人员', $fontStyle);
        $table->addCell(1200, array('cellMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["rd"]);
        $table->addCell(1200, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('本科', $fontStyle);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["undgrad"]);
        $table->addCell(1000, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('营销人员', $fontStyle);
        $table->addCell(1200, array('cellMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["market"]);
        $table->addCell(1200, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('大专', $fontStyle);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["junior"]);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("其中", $fontStyle);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('高级职称', $fontStyle);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["senior"]);
        $table->addCell(1200)->addText("中级或初级", $fontStyle);
        $table->addCell(1200, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["itmedate"]);

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('rowMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('其他', $fontStyle);
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(3000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["other"]);
        $table->addCell(1000)->addText("其中", $fontStyle);
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('留学人员', $fontStyle);
        $table->addCell(1200, array('cellMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["overseas"]);
        $table->addCell(1200, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('主要财务指标（万元）', $fontStyle);
        $table->addCell(1155)->addText("总资产", $fontStyle);
        $table->addCell(1155)->addText("总资产较上一年度增长率%", $fontStyle);
        $table->addCell(1155)->addText("销售收入", $fontStyle);
        $table->addCell(1155)->addText("销售收入较上一年度增长率%", $fontStyle);
        $table->addCell(1155)->addText("净利润", $fontStyle);
        $table->addCell(1155)->addText("研发经费", $fontStyle);
        $table->addCell(1155)->addText("研发经费占产品销售收入比例（%）", $fontStyle);
        $table->addCell(1155)->addText("上缴税金", $fontStyle);
        $table->addCell(1155)->addText("较上一年度增长率（%）", $fontStyle);

        $table->addRow(400);
        $table->addCell(1600, array('rowMerge' => 'continue'));
        $table->addCell(1155)->addText($report["assets"]);
        $table->addCell(1155)->addText($report["growth"] . "%");
        $table->addCell(1155)->addText($report["income"]);
        $table->addCell(1155)->addText($report["incomerate"] . "%");
        $table->addCell(1155)->addText($report["profit"]);
        $table->addCell(1155)->addText($report["develop"]);
        $table->addCell(1155)->addText($report["developrate"] . "%");
        $table->addCell(1155)->addText($report["fax"]);
        $table->addCell(1155)->addText($report["faxrate"] . "%");

        $table->addRow(400);
        $table->addCell(1600)->addText("企业获得各类资质和荣誉情况", $fontStyle);
        $table->addCell(2400, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($report["honor"]);
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(2400, array('cellMerge' => 'continue'));
        $table->addCell(1600, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        $table->addCell(0, array('cellMerge' => 'continue'));
        //企业基本情况 ---end------------------------------------------------------------------------------------------------------------------------------------
        //经营管理 ---start----------------------------------------------------------------------------------------------------------------------------------
        $section3 = $PHPWord->createSection();
        $section3->addText('二、经营管理', 'iStyle');
        // Define table style arrays
        $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80);
        // Add table style
        $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);

        // Add table
        $table = $section3->addTable('myOwnTableStyle');
        $fontStyle = array('bold' => true, 'align' => 'center');
        $tdStyle = array('align' => 'center');

        $PHPWord->addFontStyle('titleTdStyle', array('bold' => true, 'color' => '000000', 'size' => 14));
        // Add more rows / cells
        $table->addRow(400);
        $table->addCell(2500, array('cellMerge' => 'restart', 'valign' => "center"))->addText("主要负责人基本情况", 'titleStyle', 'pStyle');
        $table->addCell(2500, array('cellMerge' => 'continue'));
        $table->addCell(2500, array('cellMerge' => 'continue'));
        $table->addCell(2500, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(2500, array('valign' => "center"))->addText("企业负责人", $fontStyle);
        $table->addCell(2500)->addText($report["gm"]);
        $table->addCell(2500, array('valign' => "center"))->addText("联系电话", $fontStyle);
        $table->addCell(2500)->addText($report["gmmobile"]);

        $table->addRow(400);
        $table->addCell(2500, array('valign' => "center"))->addText("企业联系人", $fontStyle);
        $table->addCell(2500)->addText($report["liaison"]);
        $table->addCell(2500, array('valign' => "center"))->addText("联系电话", $fontStyle);
        $table->addCell(2500)->addText($report["lsmobile"]);

        $table->addRow(400);
        $table->addCell(2500, array('cellMerge' => 'restart', 'valign' => "center"))->addText("企业管理制度建设情况", 'titleStyle', 'pStyle');
        $table->addCell(2500, array('cellMerge' => 'continue'));
        $table->addCell(2500, array('cellMerge' => 'continue'));
        $table->addCell(2500, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("制度名称", 'titleTdStyle', 'pStyle');
        $table->addCell(4000, array('cellMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("制定时间", 'titleTdStyle', 'pStyle');
        $table->addCell(1000, array('cellMerge' => 'continue'));

        if (count($syses) < 6) {
            $syscount = count($syses);
            $blanksys = 6 - $syscount;
        } else {
            $syscount = count($syses);
            $blanksys = 0;
        }
        for ($i = 0; $i < $syscount; $i++) {
            $table->addRow(400);
            $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center",'align'=>'center'))->addText($syses[$i]["name"],$tdStyle);
            $table->addCell(4000, array('cellMerge' => 'continue'));
            $table->addCell(1000, array('cellMerge' => 'restart', 'valign' => "center",'align'=>'center'))->addText($syses[$i]["createtime"]);
            $table->addCell(1000, array('cellMerge' => 'continue'));
        }
        for ($i = 0; $i < $blanksys; $i++) {
            $table->addRow(400);
            $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("");
            $table->addCell(4000, array('cellMerge' => 'continue'));
            $table->addCell(1000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("");
            $table->addCell(1000, array('cellMerge' => 'continue'));
        }


        $table->addRow(400);
        $table->addCell(2500, array('cellMerge' => 'restart', 'valign' => "center"))->addText("企业配合基地情况（参加基地组织的活动）", 'titleStyle', 'pStyle');
        $table->addCell(2500, array('cellMerge' => 'continue'));
        $table->addCell(2500, array('cellMerge' => 'continue'));
        $table->addCell(2500, array('cellMerge' => 'continue'));

        $table->addRow(400);
        $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("活动名称", 'titleTdStyle', 'pStyle');
        $table->addCell(4000, array('cellMerge' => 'continue'));
        $table->addCell(1000, array('cellMerge' => 'restart', 'valign' => "center"))->addText("活动时间", 'titleTdStyle', 'pStyle');
        $table->addCell(1000, array('cellMerge' => 'continue'));


        if (count($acts) < 6) {
            $actcount = count($acts);
            $blankact = 6 - $actcount;
        } else {
            $actcount = count($acts);
            $blankact = 0;
        }
        for ($i = 0; $i < $actcount; $i++) {
            $table->addRow(400);
            $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center",'align' => 'center'))->addText($acts[$i]["name"],$tdStyle);
            $table->addCell(4000, array('cellMerge' => 'continue'));
            $table->addCell(1000, array('cellMerge' => 'restart', 'valign' => "center",'align' => 'center'))->addText($acts[$i]["acttime"],$tdStyle);
            $table->addCell(1000, array('cellMerge' => 'continue'));
        }
        for ($i = 0; $i < $blankact; $i++) {
            $table->addRow(400);
            $table->addCell(4000, array('cellMerge' => 'restart', 'valign' => "center",'align' => 'center'))->addText("",$tdStyle);
            $table->addCell(4000, array('cellMerge' => 'continue'));
            $table->addCell(1000, array('cellMerge' => 'restart', 'valign' => "center",'align' => 'center'))->addText("");
            $table->addCell(1000, array('cellMerge' => 'continue'));
        }

        //经营管理 ---end------------------------------------------------------------------------------------------------------------------------------------

        //主要产品（项目）技术情况和市场前景（注：每个产品填写一页） ---end------------------------------------------------------------------------------------------------------------------------------------
        for ($i = 0; $i < count($pdts); $i++) {
            $section4 = $PHPWord->createSection();
            $section4->addText('三、主要产品（项目）技术情况和市场前景--' . $pdts[$i]["name"], 'iStyle');
            // Define table style arrays
            $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80,'valign'=>'center');
            // Add table style
            $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);

            // Add table
            $table4 = $section4->addTable('myOwnTableStyle');

            $table4->addRow(600);
            $table4->addCell(2500, array('valign' => "center"))->addText("产品名称", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["name"]);
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));

            $table4->addRow(600);
            $table4->addCell(2500, array('valign' => "center"))->addText("技术领域", $fontStyle);
            $table4->addCell(2500)->addText($pdts[$i]["technicalText"]);
            $table4->addCell(2500, array('valign' => "center"))->addText("是否出口", $fontStyle);
            $table4->addCell(2500)->addText($pdts[$i]["export"]=='1'?"是":"否");

            $table4->addRow(600);
            $table4->addCell(2500, array('valign' => "center"))->addText("技术来源", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["techsourceText"]);//"□自有技术 □产学研合作开发技术 □国内其他单位技术 □引进技术本企业消化创新  □国外技术"
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));

            $table4->addRow(600);
            $table4->addCell(2500, array('valign' => "center"))->addText("所处研发阶段", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["stageText"]);//"□研发阶段 □中试阶段 □批量生产 □产业化"
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));

            $table4->addRow(600);
            $table4->addCell(2500, array('valign' => "center"))->addText("技术水平", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["techlevelText"]);//"□国际领先 □国际先进 □国内领先 □国内先进 □省内领先  □未鉴定"
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));

            $table4->addRow(1600);
            $table4->addCell(2500, array('valign' => "center"))->addText("产品功能及应用领域", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["desc"]);
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addRow(1600);
            $table4->addCell(2500, array('valign' => "center"))->addText("主要技术指标", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["techindex"]);
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addRow(1600);
            $table4->addCell(2500, array('valign' => "center"))->addText("市场情况", $fontStyle);
            $table4->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pdts[$i]["market"]);
            $table4->addCell(2500, array('cellMerge' => 'continue'));
            $table4->addCell(2500, array('cellMerge' => 'continue'));
        }


        //主要产品（项目）技术情况和市场前景（注：每个产品填写一页） ---end------------------------------------------------------------------------------------------------------------------------------------
        //四、研发能力 ---end------------------------------------------------------------------------------------------------------------------------------------
        $section5 = $PHPWord->createSection();
        $section5->addText('四、研发能力', 'iStyle');
        // Define table style arrays
        $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80);
        // Add table style
        $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);

        // Add table
        $table5 = $section5->addTable('myOwnTableStyle');
        $fontStyle = array('bold' => true, 'align' => 'center');


        $table5->addRow(600);
        $table5->addCell(2500, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("入驻以来承担的国家、省市科技计划项目情况", $fontStyle);
        $table5->addCell(2500)->addText("项目名称", $fontStyle);
        $table5->addCell(2500)->addText("项目编号", $fontStyle);
        $table5->addCell(2500)->addText("资助方式", $fontStyle);
        $table5->addCell(2500)->addText("资助金额", $fontStyle);
        $table5->addCell(2500)->addText("项目种类", $fontStyle);
        $table5->addCell(2500)->addText("立项年度", $fontStyle);
        $table5->addCell(2500)->addText("完成情况", $fontStyle);

        if (count($items) < 6) {
            $itemcount = count($items);
            $blankitem = 6 - $itemcount;
        } else {
            $itemcount = count($items);
            $blankitem = 0;
        }
        for ($i = 0; $i < $itemcount; $i++) {
            $table5->addRow(600);
            $table5->addCell(2500, array('rowMerge' => 'continue'));
            $table5->addCell(2500)->addText($items[$i]["name"]);
            $table5->addCell(2500)->addText($items[$i]["no"]);
            $table5->addCell(2500)->addText($items[$i]["type"]);
            $table5->addCell(2500)->addText($items[$i]["total"]);
            $table5->addCell(2500)->addText($items[$i]["projecttype"]);
            $table5->addCell(2500)->addText($items[$i]["year"]);
            $table5->addCell(2500)->addText($items[$i]["progress"]);
        }
        for ($i = 0; $i < $blankitem; $i++) {
            $table5->addRow(600);
            $table5->addCell(2500, array('rowMerge' => 'continue'));
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
        }

        $table5->addRow(600);
        $table5->addCell(2500, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("入驻以来企业专利（申请）情况", $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("专利（申请）名称", $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'continue'));
        $table5->addCell(2500)->addText("类型", $fontStyle);
        $table5->addCell(2500)->addText("是否批准", $fontStyle);
        $table5->addCell(2500)->addText("批准（申请）号", $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('批准（申请）时间', $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'continue'));

        if (count($patents) < 6) {
            $patcount = count($patents);
            $blankpat = 6 - $patcount;
        } else {
            $patcount = count($patents);
            $blankpat = 0;
        }
        for ($i = 0; $i < $patcount; $i++) {
            $table5->addRow(600);
            $table5->addCell(2500, array('rowMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($patents[$i]["name"]);
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500)->addText($patents[$i]["type"]);
            $table5->addCell(2500)->addText($patents[$i]["approval"]=='1'?"是":"否");
            $table5->addCell(2500)->addText($patents[$i]["no"]);
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($patents[$i]["time"]);
            $table5->addCell(2500, array('cellMerge' => 'continue'));
        }
        for ($i = 0; $i < $blankpat; $i++) {
            $table5->addRow(600);
            $table5->addCell(2500, array('rowMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("");
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("");
            $table5->addCell(2500, array('cellMerge' => 'continue'));
        }

        $table5->addRow(600);
        $table5->addCell(2500, array('rowMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("入驻以来获得企业软件著作权情况", $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("名称", $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'continue'));
        $table5->addCell(2500, array('cellMerge' => 'continue'));
        $table5->addCell(2500, array('cellMerge' => 'continue'));
        $table5->addCell(2500)->addText("编号", $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText('时间', $fontStyle);
        $table5->addCell(2500, array('cellMerge' => 'continue'));
        if (count($softs) < 6) {
            $softcount = count($softs);
            $blanksoft = 6 - $softcount;
        } else {
            $softcount = count($softs);
            $blanksoft = 0;
        }
        for ($i = 0; $i < $softcount; $i++) {
            $table5->addRow(600);
            $table5->addCell(2500, array('rowMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($softs[$i]["name"]);
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500)->addText($softs[$i]["no"]);
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($softs[$i]["time"]);
            $table5->addCell(2500, array('cellMerge' => 'continue'));
        }
        for ($i = 0; $i < $blanksoft; $i++) {
            $table5->addRow(600);
            $table5->addCell(2500, array('rowMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("");
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500, array('cellMerge' => 'continue'));
            $table5->addCell(2500)->addText("");
            $table5->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText("");
            $table5->addCell(2500, array('cellMerge' => 'continue'));
        }
        //四、研发能力 ---end------------------------------------------------------------------------------------------------------------------------------------
        //五、在研项目的技术情况和市场前景） ---end------------------------------------------------------------------------------------------------------------------------------------

        for ($i = 0; $i < count($pjts); $i++) {
            $section6 = $PHPWord->createSection();
            $section6->addText('五、在研项目的技术情况和市场前景--'.$pjts[$i]["name"], 'iStyle');
            // Define table style arrays
            $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80);
            // Add table style
            $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);

            // Add table
            $table6 = $section6->addTable('myOwnTableStyle');
            $fontStyle = array('bold' => true, 'align' => 'center');

            $table6->addRow(600);
            $table6->addCell(2500, array('valign' => "center"))->addText("项目名称", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["name"]);
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));

            $table6->addRow(600);
            $table6->addCell(2500, array('valign' => "center"))->addText("技术领域", $fontStyle);
            $table6->addCell(2500)->addText($pjts[$i]["technicalText"]);
            $table6->addCell(2500, array('valign' => "center"))->addText("研发投入", $fontStyle);
            $table6->addCell(2500)->addText($pjts[$i]["rdinput"]);

            $table6->addRow(600);
            $table6->addCell(2500, array('valign' => "center"))->addText("开发形式", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["develop"]);//"□独立开发  □引进消化吸收  □合作开发  □委托开发  □其它"
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));

            $table6->addRow(600);
            $table6->addCell(2500, array('valign' => "center"))->addText("技术来源", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["techsourceText"]);//"□自有技术 □产学研合作开发技术 □国内其他单位技术 □引进技术本企业消化创新  □国外技术"
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addRow(600);
            $table6->addCell(2500, array('valign' => "center"))->addText("所处研发阶段", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["stageText"]);//"□研发阶段 □中试阶段 □批量生产 □产业化"
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));

            $table6->addRow(600);
            $table6->addCell(2500, array('valign' => "center"))->addText("技术水平", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["techlevelText"]);//"□国际领先 □国际先进 □国内领先 □国内先进 □省内领先  □未鉴定"
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));

            $table6->addRow(2600);
            $table6->addCell(2500, array('valign' => "center"))->addText("主要技术指标", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["techindex"]);
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addRow(2600);
            $table6->addCell(2500, array('valign' => "center"))->addText("市场情况", $fontStyle);
            $table6->addCell(2500, array('cellMerge' => 'restart', 'bold' => true, 'valign' => "center"))->addText($pjts[$i]["market"]);
            $table6->addCell(2500, array('cellMerge' => 'continue'));
            $table6->addCell(2500, array('cellMerge' => 'continue'));
        }

        //在研项目的技术情况和市场前景 ---end------------------------------------------------------------------------------------------------------------------------------------


        //六、融资情况 ---end------------------------------------------------------------------------------------------------------------------------------------
        $section6 = $PHPWord->createSection();
        $section6->addText('六、融资情况', 'iStyle');
        // Define table style arrays
        $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80);
        // Add table style
        $PHPWord->addTableStyle('myOwnTableStyle', $styleTable);

        // Add table
        $table6 = $section6->addTable('myOwnTableStyle');
        $fontStyle = array('bold' => true, 'align' => 'center');

        $table6->addRow(2600);
        $table6->addCell(12500)->addText($fls["desc"]);
        //六、融资情况 ---end------------------------------------------------------------------------------------------------------------------------------------*/
        //Add image
        /*$section->addImage('logo.jpg', array('width'=>100, 'height'=>100,'align'=>'right'));*/

        /*$objWrite = \PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
        $objWrite->save('word/tmp/' . time() . '.docx');*/


        /**设置在浏览器下载**/
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control:must-revalidate， post-check=0， pre-check=0');
        header('Content-Type:application/force-download');
        header('Content-Type:application/vnd.ms-word');
        header('Content-Type:application/octet-stream');
        header('Content-Type:application/download');
        header('Content-Disposition:attachment;filename=' . "考核表" . '.docx');
        header('Content-Transfer-Encoding:binary');
        $objWriter = \PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
        $objWriter->save('php://output');
    }

    //---------------------------END---------考核------------------------------------------

}