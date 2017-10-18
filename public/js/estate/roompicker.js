/**
 * Created by Administrator on 2016/10/22.
 */

function resetroompicker(chartStatus){

    var $cart = $('#selected-seats'),
        $counter = $('#counter'),
        $total = $('#total'),
        seatmap=$("#seat-map"),
        sc = $('#seat-map').seatCharts({
            map: [ //座位图
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee',
                'eeeeeee'
            ],
            seats: { //定义座位属性
                e: {
                    price   : 40,
                    classes : 'economy-class',
                    category: '二等座'
                }
            },
            naming : { //定义行列等信息
                top : true,
                columns: ['A', 'B', 'C', 'D', 'E','F','G'],
                rows: ['01','02','03','04','05','06','07','08','09','10','11','12','13'],
                getLabel : function (character, row, column) {
                    return row+"_"+column;
                }
            },
            legend : { //定义图例
                node : $('#legend'),
                items : [
                    [ 'e', 'seted',   '已分配' ],
                    [ 'e', 'free',   '空置房'],
                    [ 'e', 'available', '空区域'],
                    [ 'e', 'floor', '走廊楼道'],
                    ['e','unavailable','内部办公'],
                    ['e','clicked','最后点击'],
                ]
            },
            click: function () {
                var cstmData=new Object();
                var optflo=$("#flooropt").val();
                var dict=seatmap.data("floorData");
                if(optflo==""){
                    toastr.warning("请先选择楼层");
                    return "available";
                }else if(this.status() == 'available'||this.status() == 'clicked'||this.status() == 'selected'){
                    cstmData.roomIndex=this.settings.label;
                    cstmData.id=0;
                    cstmData.buildId=$("#build").val();
                    cstmData.floor=$("#flooropt").val();
                    initform(cstmData);
                }else{
                    initform(dict[this.settings.label]);
                    //return 'free';
                }
                sc.find("clicked").status("available");

                if (this.status() == 'free') {
                    if($('#cart-item-'+this.settings.id).length>0){
                        $('#cart-item-'+this.settings.id).remove();
                        $total.text(parseInt($total.text())-parseInt(dict[this.settings.label].totalarea));
                        var arr=$("#roomIds").val().split(",");
                        for(var i=0;i<arr.length;i++){
                            if(arr[i]==dict[this.settings.label].id){
                                arr.splice(i,1);
                                break;
                            }
                        }
                        $("#roomIds").val(arr.join(","));//更新票数
                        $counter.text(parseInt($counter.text())-1);
                    }else{
                        $('<li>编号：'+dict[this.settings.label].roomIndex+'<br/>房间：'+dict[this.settings.label].roomNo+'<br/>面积：'+dict[this.settings.label].totalarea+'</li>')
                            .attr('id', 'cart-item-'+this.settings.id)
                            .data('seatId', this.settings.id)
                            .appendTo($cart);
                        $total.text(parseInt($total.text())+parseInt(dict[this.settings.label].totalarea));
                        if($("#roomIds").val()!=""){
                            var roomIds=$("#roomIds").val()+","+dict[this.settings.label].id;
                            $("#roomIds").val(roomIds);
                        }else{
                            var roomIds=dict[this.settings.label].id;
                            $("#roomIds").val(roomIds);
                        }
                        //更新票数
                        $counter.text(parseInt($counter.text())+1);
                    }



                    return 'free';
                }else if(this.status() == 'floor'){
                    return 'floor';
                } else if (this.status() == 'seted') {
                    return 'seted';
                }else if (this.status() == 'available'||this.status() == 'clicked') {

                    return 'clicked';
                }else if (this.status() == 'selected') {
                    return 'selected';
                } else if (this.status() == 'unavailable') {
                    //已售出
                    return 'unavailable';
                } else {
                    return this.style();
                }
            },
            focus:function() {
                var dict=seatmap.data("floorData");
                if (this.status() == 'available') {
                    var tiphtm=this.settings.label;
                    showTips(tiphtm);
                    return 'focused';
                }else if(this.status() == 'selected'){
                    var tiphtm=this.settings.label;
                    showTips(tiphtm);
                    return 'selected';
                } else if(this.status() == 'seted'){
                    var dataIndex=this.settings.label;
                    var tiphtm='<div>房间：'+dict[dataIndex]["roomNo"]+'<br/>' +
                        '房间面积：'+dict[dataIndex]["totalarea"]+'平方米<br/>' +
                        '入驻企业：'+dict[dataIndex]["name"] +
                        '</div>';
                    showTips(tiphtm);
                    return 'seted';
                }else if(this.status() == 'free'){
                    var dataIndex=this.settings.label;
                    var tiphtm='<div>房间：'+dict[dataIndex]["roomNo"]+'<br/>' +
                        '房间面积：'+dict[dataIndex]["totalarea"]+'平方米<br/>' +
                        '</div>';
                    showTips(tiphtm);
                    return 'free'
                }else  {
                    return this.style();
                }
            },
            blur:function(){
                $("#seat-info").hide();
                if(this.style()=="focused"){
                    return 'available';
                }else if(this.style()=="selected"){
                    return 'selected';
                }else{
                    return this.style();
                }
            }
        });

    sc.find("seted").status("available");
    sc.find("free").status("available");
    sc.find("floor").status("available");
    sc.find("unavailable").status("available");
    if(chartStatus!=null){
        sc.get(chartStatus.seted).status('seted');
        sc.get(chartStatus.free).status('free');
        sc.get(chartStatus.floor).status('floor');
        sc.get(chartStatus.unvlb).status('unavailable');
    }

}
function initform(data){
    $("#dataform")[0].reset();
    $("#etprsName").html("未分配企业");
    $("#etprsName").html(data.name==null?"未分配企业":data.name);
    $("#roombh").html(data.roomIndex);
    $("#roomNo").val(data.roomNo);
    $("#totalarea").val(data.totalarea);
    $("#id").val(data.id);
    $("#buildId").val(data.buildId);
    $("#floor").val(data.floor);
    $("#etprsId").val(data.etprsId);
    $("#roomIndex").val(data.roomIndex);

    //$("input[name='status'][value='2']").attr("checked",true);

    var type=data.type;
    if(type!=undefined){
        $("input[name='type'][value='"+type+"']").prop("checked","checked");
    }else{
        $("input[name='type'][value='0']").prop("checked","checked");
    }

    var typeVal=$("input[name='type']:checked").val();
    if(typeVal=="0"){
        $("#statusDiv").css("display","block");
    }else{
        $("#statusDiv").css("display","none");
    }

    var status=data.status;
    if(status!=undefined){
        $("input[name='status'][value='"+status+"']").prop("checked","checked");
    }else{
        $("input[name='status'][value='2']").prop("checked","checked");
    }
}
function showTips(tiphtm){
    var cd = getMousePoint(event);
    $("#seat-info").show().html(tiphtm);
    $("#seat-info").css({"left":(cd.x+10)+'px',"top":(cd.y-30)+"px"});
}

//获取鼠标坐标位置
function getMousePoint(ev) {
    // 定义鼠标在视窗中的位置
    var point = {
        x:0,
        y:0
    };

    // 如果浏览器支持 pageYOffset, 通过 pageXOffset 和 pageYOffset 获取页面和视窗之间的距离
    if(typeof window.pageYOffset != 'undefined') {
        point.x = window.pageXOffset;
        point.y = window.pageYOffset;
    }
    // 如果浏览器支持 compatMode, 并且指定了 DOCTYPE, 通过 documentElement 获取滚动距离作为页面和视窗间的距离
    // IE 中, 当页面指定 DOCTYPE, compatMode 的值是 CSS1Compat, 否则 compatMode 的值是 BackCompat
    else if(typeof document.compatMode != 'undefined' && document.compatMode != 'BackCompat') {
        point.x = document.documentElement.scrollLeft;
        point.y = document.documentElement.scrollTop;
    }
    // 如果浏览器支持 document.body, 可以通过 document.body 来获取滚动高度
    else if(typeof document.body != 'undefined') {
        point.x = document.body.scrollLeft;
        point.y = document.body.scrollTop;
    }

    // 加上鼠标在视窗中的位置
    point.x += ev.clientX;
    point.y += ev.clientY;

    // 返回鼠标在视窗中的位置
    return point;
}