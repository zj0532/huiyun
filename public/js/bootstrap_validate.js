function v_required (fid,fname) {
    if(fid!=undefined){
        if(fname==undefined){fname="";}
        var thisobj=$("#"+fid);
        var value_str=thisobj.val();
        var parent = thisobj.parent();
        var root=parent.parent();
        if(value_str!=undefined&&!root.hasClass("has-error")){
            if(root.hasClass("has-error")){
                root.removeClass("has-error");
                root.removeClass("has-success");
            }
            root.removeClass("has-error");
            root.removeClass("has-success");
            if(value_str==""){
                root.addClass("has-error");
                toastr.error("*"+fname+"必填");
                return false;
            }else if(!root.hasClass("has-error")){
                root.addClass("has-success");
            }
        }
    }
    return true;
}
function v_opt_required (fid,fname) {
    if(fid!=undefined){
        if(fname==undefined){fname="";}
        var thisobj=$("#"+fid);
        var value_str=thisobj.val();
        var parent = thisobj.parent();
        var root=parent.parent();
        /*if(!root.hasClass("has-error")){*/
            if(root.hasClass("has-error")){
                root.removeClass("has-error");
                root.removeClass("has-success");
            }
            root.removeClass("has-error");
            root.removeClass("has-success");
            if(value_str==null||value_str==""){
                root.addClass("has-error");
                toastr.error("*"+fname+"必填");
                return false;
            }else if(!root.hasClass("has-error")){
                root.addClass("has-success");
            }
        /*}*/
    }
    return true;
}
function v_email (id,fname) {
    var thisobj=$("#"+id);
    var email_str=thisobj.val();
    var parent = thisobj.parent();
    var root=parent.parent();
    if(fname==undefined){fname="";}
    var msg=fname+":邮箱格式错误";

    if(email_str!=undefined&&email_str!=""&&!root.hasClass("has-error")){
        if(root.hasClass("has-error")){
            root.removeClass("has-error");
            root.removeClass("has-success");
        }
        root.removeClass("has-error");
        root.removeClass("has-success");

        var reg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/;
        if(!reg.test(email_str)){
            root.addClass("has-error");
            toastr.error(msg);
            return false;
        }else if(!root.hasClass("has-error")){
            root.addClass("has-success");
        }
    }
    return true;
}
function v_mobile (id,fname) {
    var thisobj=$("#"+id);
    if(fname==undefined){fname="";}
    var phone_str=thisobj.val();
    var parent = thisobj.parent();
    var root=parent.parent();
    var msg=fname+":手机号码格式错误";
    if(phone_str!=undefined && phone_str!="" && !root.hasClass("has-error")){
        if(root.hasClass("has-error")){
            root.removeClass("has-error");
            root.removeClass("has-success");
        }
        root.removeClass("has-error");
        root.removeClass("has-success");
        var isPhone = /^([0-9]{3,4}-)?[0-9]{7,8}$/;
        var isMob=/^((\+?86)|(\(\+86\)))?(13[0123456789][0-9]{8}|15[0123456789][0-9]{8}|17[0123456789][0-9]{8}|18[0123456789][0-9]{8}|147[0-9]{8}|1349[0-9]{7})$/;
        if(!(isPhone.test(phone_str)||isMob.test(phone_str))&&!root.hasClass("has-error")){
            root.addClass("has-error");
            toastr.error(msg);
            return false;
        }else if(!root.hasClass("has-error")){
            root.addClass("has-success");
        }
    }
    return true;
}
function v_number (id,fname) {
    var thisobj=$("#"+id);
    var number_str=thisobj.val();
    var parent = thisobj.parent();
    var root=parent.parent();
    if(fname==undefined){fname="";}
    var msg=fname+"必须为正整数！";
    if(number_str!=undefined&&number_str!=""&&!root.hasClass("has-error")){
        if(root.hasClass("has-error")){
            root.removeClass("has-error");
            root.removeClass("has-success");
        }
        root.removeClass("has-error");
        root.removeClass("has-success");
        if(!/^[0-9]*$/.test(number_str)&&!root.hasClass("has-error")){
            root.addClass("has-error");
            toastr.error(msg);
            return false;
        }else if(!root.hasClass("has-error")){
            root.addClass("has-success");
        }
    }
    return true;
}
/***
 *  ^(?!0+(?:\.0+)?$)(?:[1-9]\d*|0)(?:\.\d{1,2})?$   不能0 正数，最多两位小数
 *  /^[-+]?0{1}([.]\d{1,2})?$|^[1-9]\d*([.]{1}[0-9]{1,2})?$/
 *  ^[-+]?(0|[1-9][0-9]*)(\.[0-9]{1,2})?$
 * @param id
 * @param fname
 * @returns {boolean}
 */
function v_decimal (id,fname) {
    var thisobj=$("#"+id);
    var number_str=thisobj.val();
    var parent = thisobj.parent();
    var root=parent.parent();
    if(fname==undefined){fname="";}
    var msg=fname+"必须为数字，最多两位小数！";

    if(number_str!=undefined&&number_str!=""&& !root.hasClass("has-error")){
        if(root.hasClass("has-error")){
            root.removeClass("has-error");
            root.removeClass("has-success");
        }
        root.removeClass("has-error");
        root.removeClass("has-success");
        if(!/^[-+]?(0|[1-9][0-9]*)(\.[0-9]{1,2})?$/.test(number_str)&&!root.hasClass("has-error")){
            root.addClass("has-error");
            toastr.error(msg);
            return false;
        }else if(!root.hasClass("has-error")){
            root.addClass("has-success");
        }
    }
    return true;
}
function v_unique(table,field,id,urlid){
    var pkvalue=$("#id").val();
    var thisobj=$("#"+id);
    var f_str=thisobj.val();
    var parent = thisobj.parent();
    var root=parent.parent();
    var msg="已经存在该值";
    if(f_str!=undefined&&f_str!=""&&!root.hasClass("has-error")){
        if(root.hasClass("has-error")){
            root.removeClass("has-error");
            root.removeClass("has-success");
        }
        root.removeClass("has-error");
        root.removeClass("has-success");
        var URL = $("#"+urlid).attr("data-url");
        $.ajax({
            type: "POST",
            async:false,
            url: URL,
            data: {"table":table,"field":field,"val":f_str,"id":$("#id").val()},
            success: function(data){
                if(data.code=="0"){
                    root.addClass("has-error");
                    toastr.error(msg);
                    return false;
                }else if(!root.hasClass("has-error")){
                    root.addClass("has-success");
                }
            }
        });
    }
}
function v_eqConfirm (fid,fname,forField){
    var thisobj=$("#"+fid);
    var parent = thisobj.parent();
    var root=parent.parent();
    if(fname==undefined){fname="";}
    var msg="两次输入密码不一致！";
    if(forField!=undefined&&!root.hasClass("has-error")){
        var rePass_str=thisobj.val();
        var pass=$("#"+forField).val();
        if(rePass_str!=undefined&&pass!=undefined){
            if(root.hasClass("has-error")){
                root.removeClass("has-error");
                root.removeClass("has-success");
            }
            root.removeClass("has-error");
            root.removeClass("has-success");
            if(rePass_str!=pass){
                root.addClass("has-error");
                toastr.error(msg);
                return false;
            }else if(!root.hasClass("has-error")){
                root.addClass("has-success");
            }
        }
    }
    return true;
}

function custom_validate(formId){
    $("#"+formId+" .require").blur();
    $("#"+formId+" .opt-require").change();
    $("#"+formId+" .email").blur();
    $("#"+formId+" .mobile").blur();
    $("#"+formId+" .number").blur();
    $("#"+formId+" .decimal").blur();
    $("#"+formId+" .eqConfirm").blur();
    $("#"+formId+" .unique").blur();
    var numError = $('#'+formId+' .has-error').length;
    if(numError>0){
        return 0;
    }else{
        return 1;
    }
}

$(function(){
	//验证
	$(".require,.opt-require").each(function(){
	    $(this).parent().prev().find("span").remove();
	    var required = $("<span style='color:red;'> *</span>"); //创建元素
	    $(this).parent().prev().append(required); //然后将它追加到文档中
	});

    $(".require").blur(function(){
        v_required($(this).attr("id"),$(this).attr("placeholder"));
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });
    $(".opt-require").change(function(){
        v_opt_required($(this).attr("id"),$(this).attr("data-placeholder"));
    });
    /*.focus(function(){
        alert(22);
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });*/
    $(".email").blur(function(){
        v_email($(this).attr("id"),$(this).attr("placeholder"));
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });

    $(".mobile").blur(function(){
        v_mobile($(this).attr("id"),$(this).attr("placeholder"));
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });

    $(".number").blur(function(){
        v_number($(this).attr("id"),$(this).attr("placeholder"));
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });
    $(".decimal").blur(function(){
        v_decimal($(this).attr("id"),$(this).attr("placeholder"));
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });

    $(".eqConfirm").blur(function(){
        v_eqConfirm($(this).attr("id"),$(this).attr("placeholder"),$(this).attr("forfield"));
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });

    $(".unique").blur(function(){
        v_unique($(this).attr("table"),$(this).attr("name"),$(this).attr("id"),"uniqeUrl");
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });
    $(".iqbtunique").blur(function(){
        v_unique($(this).attr("table"),$(this).attr("name"),$(this).attr("id"),"uniqeiqbtUrl");
    }).focus(function(){
        var root=$(this).parent().parent();
        root.removeClass("has-error").removeClass("has-success");
    });


});
