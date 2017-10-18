/**
 * Created by Administrator on 2016/9/28.
 */

/*5.加载文件*/
/* 已加载文件缓存列表,用于判断文件是否已加载过，若已加载则不再次加载*/
var classcodes =[];
var root="/incubator/public/";
window.Import={
    /*加载一批文件，_files:文件路径数组,可包括js,css,less文件,succes:加载成功回调函数*/
    LoadFileList:function(_files,succes){
        var FileArray=[];
        if(typeof _files==="object"){
            FileArray=_files;
        }else{
            /*如果文件列表是字符串，则用,切分成数组*/
            if(typeof _files==="string"){
                FileArray=_files.split(",");
            }
        }
        if(FileArray!=null && FileArray.length>0){
            var LoadedCount=0;
            for(var i=0;i< FileArray.length;i++){
                loadFile(FileArray[i],function(){
                    LoadedCount++;
                    if(LoadedCount==FileArray.length){
                        succes();
                    }
                })
            }
        }
        /*加载JS文件,url:文件路径,success:加载成功回调函数*/
        function loadFile(url, success) {
            if (!FileIsExt(classcodes,url)) {
                var ThisType=GetFileType(url);
                var fileObj=null;
                if(ThisType==".js"){
                    fileObj=document.createElement('script');
                    fileObj.src = root+"js/"+url;

                }else if(ThisType==".css"){
                    fileObj=document.createElement('link');
                    fileObj.href = root+"css/" +url;
                    fileObj.type = "text/css";
                    fileObj.rel="stylesheet";
                }
                success = success || function(){};
                fileObj.onload = fileObj.onreadystatechange = function() {
                    //alert(fileObj.readyState);
                    if (!this.readyState || 'loaded' === this.readyState || 'complete' === this.readyState) {

                        success();
                        classcodes.push(url)
                    }
                };
                document.getElementsByTagName('head')[0].appendChild(fileObj);
            }else{
                success();
            }
        }
        /*获取文件类型,后缀名，小写*/
        function GetFileType(url){
            if(url!=null && url.length>0){
                if(url.lastIndexOf("?")>0){
                    url=url.substring(0,url.lastIndexOf("?"));
                }

                return url.substr(url.lastIndexOf(".")).toLowerCase();
            }
            return "";
        }
        /*文件是否已加载*/
        function FileIsExt(FileArray,_url){
            if(FileArray!=null && FileArray.length>0){
                var len =FileArray.length;
                for (var i = 0; i < len; i++) {
                    if (FileArray[i] ==_url) {
                        return true;
                    }
                }
            }
            return false;
        };
    }
};
var csses=['plugins/iCheck/custom.css','animate.min.css','style.min862f.css?v=4.1.0','plugins/awesome-bootstrap-checkbox/awesome-bootstrap-checkbox.css','plugins/summernote/summernote.css','plugins/switchery/switchery.css','plugins/chosen/chosen.min.css','bootstrap.min14ed.css?v=3.3.6'];
Import.LoadFileList(csses,function(){});

var jss=['bootstrap.min.js?v=3.3.6','plugins/summernote/summernote.min.js','plugins/iCheck/icheck.min.js','plugins/switchery/switchery.js','plugins/chosen/chosen.jquery.min.js'];
Import.LoadFileList(jss,function(){
    var jss2=['content.min.js?v=1.0.0','custom/form.init.js'];
    Import.LoadFileList(jss2,function(){});
});



