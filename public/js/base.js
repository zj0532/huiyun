$(function(){
    if($('.resettable').length>0){window.onresize = function () {$('.resettable').bootstrapTable('resetView');}
        $('.resettable').attr('data-page-size','20');
    }

});

function baseadd(url,msg,modelsize){
    $.post(url, function(data) {
        show_modal(msg,data,modelsize);
    });
}
function baseedit(tableId,url,msg,modelsize){
    id=singleCheck(tableId);
    if(id!=null){
        $.post(url,{id:id}, function(data) {
            show_modal(msg,data,modelsize);
        });
    }else{
        toastr.warning("没有记录ID");
    }
}

function basedelete(tableId,url,id){
    var data='<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span></button><h4 class="modal-title" id="myModalLabel"></h4></div><div class="modal-body">确定要删除么？</div><div class="modal-footer"><button class="btn btn-primary" onclick="common_delete(\''+url+'\',\''+id+'\',\''+tableId+'\')">确定</button><button class="btn btn-glyph " data-dismiss="modal" onclick="return false;">取消</span></button></div>';
    show_modal("提示",data,"modal-sm");
}
function common_delete(url,id,tableId){
    $.post(url, {id: id}, function (data) {
        if (data.code == 1) {
            toastr.success(data.msg);
            $("#" + tableId).bootstrapTable('refresh');
            $('#myModal').modal('hide');
        }else if(data.code == 0){
            toastr.warning(data.msg);
        }else{
            toastr.error(data.msg);
        }
    });
}
function getIdSelections(tableId) {
    var $table = $("#"+tableId);
    var selections=$table.bootstrapTable('getSelections');
    var chk_value =[];
    $.each(selections, function(index, value, array) {
        chk_value.push(value.id);
    });
    return chk_value;
}
function singleCheck(tableId){
    var ids=getIdSelections(tableId);
    if(ids.length!=1){
        toastr.warning("必须且只能选择一条记录");
        return null;
    }
    var id=ids[0];
    return id;
}
function mulitecheck(tableId){
    var ids=getIdSelections(tableId);
    if(ids.length==0){
        toastr.warning("至少选择一条记录");
        return null;
    }
    ids=ids.join(",");
    return ids;
}
function show_modal(title, data,modalsize){
    $('#myModal').remove();
    if($('#myModal').length == 0) {
        $('body').append('<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog '+modalsize+'"><div class="modal-content"></div></div></div>');
    }
    $('#myModal .modal-content').html(data);
    $('#myModalLabel').html(title);
    $('#myModal').modal('show');
}
function serializeJson(formdata){
    var serializeObj={};
    $(formdata).each(function(){
        serializeObj[this.name]=serializeObj[this.name]?serializeObj[this.name]+","+this.value:this.value;
    });
    return serializeObj;
};

function checkEtprsAccess(url,usercate,loginUrl,checkEtprsUrl,stat,refrash){
    $.post(url,{stat:stat}, function(code) {
        switch (code){
            case "1013005":
                initpage();
                break;
            case "1013006":
                //用户类型错误，非管理员和企业用户。需要重新登录
                alert("用户类型错误");
                location.href=loginUrl;
                break;
            case "1013004":
                //未选择企业
                if(usercate=="1011001"){
                    //选择企业
                    checkUserEtprs(checkEtprsUrl,refrash,stat,false);
                }else if(usercate=="1011002"){
                    //企业用户，需要重新登录
                    alert("获取不到企业ID，请重新登录");
                    location.href=loginUrl;
                }
                break;
            case "1013008":
                //企业状态错误
                if(usercate=="1011001"){
                    //选择企业
                    checkUserEtprs(checkEtprsUrl,refrash,stat,false);

                }else if(usercate=="1011002"){
                    //企业用户，需要重新登录
                    toastr.warning("状态错误，当前企业还没进行到当前状态");
                }

                break;
            case "1013007":
                //未找到相关企业信息
                if(usercate=="1011001"){
                    //选择企业
                    checkUserEtprs(checkEtprsUrl,refrash,stat,false);
                }else if(usercate=="1011002"){
                    alert("获取不到企业详细信息，请重新登录");
                    location.href=loginUrl;
                }
                break;
            default :
                break;
        }
    });
}


function checkUserEtprs(url,refrash,stat,close){
    //选择企业，
    $.post(url,{status:stat,close:close}, function(data) {
        $('#myModal').remove();
        if($('#myModal').length == 0) {
            $('body').append('<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" data-backdrop="static" data-keyboard="false"><div class="modal-dialog"><div class="modal-content"></div></div></div>');
        }
        $('#myModal').data("isRefrash",refrash);
        $('#myModal').data("close",close);
        $('#myModal .modal-content').html(data);
        $('#myModalLabel').html("请选择企业");
        $('#myModal').modal('show');
    });
}
function smntUploadFile(files,smnt,url,dir) {
    for (var i = 0; i < files.length; i++) {
        var obj = files[i];
        data = new FormData();
        data.append("file", obj);
        $.ajax({
            data: data,
            type: "POST",
            url: url,
            cache: false,
            contentType: false,
            processData: false,
            success: function(url) {
                smnt.summernote('insertImage', dir+url, 'image name'); // the insertImage API
            }
        });
    }

}