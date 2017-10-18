/**
 * Created by Administrator on 2016/9/28.
 */
$(document).ready(function(){$(".i-checks").iCheck({checkboxClass:"icheckbox_square-green",radioClass:"iradio_square-green"})});
$(document).ready(function(){
    $('.summernote').summernote();
    var elem = document.querySelector('.js-switch');
    var init = new Switchery(elem,{ size: 'small' });

    $(".chosen-select").chosen();
    /*$(".default-hide").css('display','none');*/
    $(".btn-submit").click(function(){
        var form=$(this).closest("form"); //3 return false;
        var iboxcontent=$(this).closest("div.ibox-content");
        iboxcontent.find("div.default-hide").css('display','none');
        iboxcontent.find("div.default-display").css('display','block');
    });
    $(".btn-reset").click(function(){
        var form=$(this).closest("form");
        form[0].reset();
    });
    $(".btn-cancel").click(function(){
        var iboxcontent=$(this).closest("div.ibox-content");
        iboxcontent.find("div.default-hide").css('display','none');
        iboxcontent.find("div.default-display").css('display','block');
    });
});
