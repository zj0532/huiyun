$(function() {
                //定义三维数组
                var data = [
                   
                    [
                        ['&nbsp;很差'],
                        ['&nbsp;一般' ],
                        ['&nbsp;好' ],
                        ['&nbsp;很好' ],
                        ['&nbsp;非常好']
                    ]
                ];
                //定义悬浮每行各个星星图片
                var stars = [
                    ['star_hover.png', 'star_gray.png', 'star_gray.png', 'star_gray.png', 'star_gray.png'],
                    ['star_hover.png', 'star_hover.png', 'star_gray.png', 'star_gray.png', 'star_gray.png'],
                    ['star_gold.png', 'star_gold.png', 'star_gold.png', 'star_gray.png', 'star_gray.png'],
                    ['star_gold.png', 'star_gold.png', 'star_gold.png', 'star_gold.png', 'star_gray.png'],
                    ['star_gold.png', 'star_gold.png', 'star_gold.png', 'star_gold.png', 'star_gold.png'],
                ];
                $(".stars").find("img").hover(function() { //星星悬浮触发
                    var obj = $(this);//当前对象
                    var star_area = obj.parent(".stars"); //当前父级.stars
                    var star_area_index = star_area.parent().find(".stars").index(star_area);//当前父级.stars索引
                    var index = star_area.find("img").index(obj);//当前星星索引
                    
                    var left = obj.position().left;
                    var top = obj.position().top + 25;
                    var comment_title = data[star_area_index][index][0];//标题
                    var comment_description = data[star_area_index][index][1];//描述
                    $("#tip").css({"left": left, "top": top}).show().html(comment_title);//显示定位提示信息
                    for (var i = 0; i < 5; i++) {
                        star_area.find("img").eq(i).attr("src", "images/" + stars[index][i]);//切换每个星星
                    }
                    star_area.find(".comment_description").remove();//星星右侧切换描述
                    star_area.append("<span class='comment_description'><span class='comment_title'>" + comment_title + "</span></span>");

                }, function() { //鼠标离开星星
                    $("#tip").hide();//隐藏提示
                    var obj = $(this);//当前对象
                    var star_area = obj.parent(".stars");//当前父级.stars
                    var star_area_index = star_area.parent().find(".stars").index(star_area);//当前父级.stars索引
                    var index = star_area.attr("data-default-index");//点击后的索引
                    if (index >= 0) { //若该行点击后的索引大于等于0，说明该行星星已被点击
                        var comment_title = data[star_area_index][index][0];//标题
                        var comment_description = data[star_area_index][index][1];//描述
                        star_area.find(".comment_description").remove();//显示切换描述
                        star_area.append("<span class='comment_description'><span class='comment_title'>" + comment_title + "</span></span>");
                        for (var i = 0; i < 5; i++) {
                            star_area.find("img").eq(i).attr("src", "images/" + stars[index][i]);//更新该行星星
                        }
                    } else {
                        var obj = $(this);
                        var star_area = obj.parent(".stars");
                        star_area.find(".comment_description").remove();
                        for (var i = 0; i < 5; i++) {
                            star_area.find("img").eq(i).attr("src", "images/star_gray.png");//更新该行星星都变初始状态
                        }
                    }
                })
                $(".stars").find("img").click(function() { //当点击每颗星星
                    var obj = $(this);//当前对象
                    var star_area = obj.parent(".stars"); //当前父级.stars
                    var star_area_index = star_area.parent().find(".stars").index(star_area);//当前父级.stars索引
                    var index = obj.parent(".stars").find("img").index($(this));//当前星星索引

                    var comment_title = data[star_area_index][index][0];//标题
                    var comment_description = data[star_area_index][index][1];//描述
                    star_area.attr("data-default-index", index);//记录点击后的索引，用来鼠标移出星星后，获取之前点击后的星星索引
                    star_area.find(".comment_description").remove();//显示切换描述
                    star_area.append("<span class='comment_description'><span class='comment_title'>" + comment_title + "</span></span>");
                })
            })