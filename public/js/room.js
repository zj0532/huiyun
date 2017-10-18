$(function () {
    var right = $('.right');
    var bg = $('.bgDiv');
    var rightNav = $('.rightNav');
    $("#roomfield").on("click",".right",function() {
        var direction="right";
        bg.css({
            display: "block",
            transition: "opacity .5s"
        });
        if (direction == "right") {
            rightNav.css({
                right: "0px",
                transition: "right 1s"
            });
        }
    });


    $('span').each(function () {
        var dom = $(this);
        dom.on('click', function () {
            hideNav();
            alert(dom.text())
        });
    });


    bg.on('click', function () {
        hideNav();
    });

    function hideNav() {
        rightNav.css({
            right: "-50%",
            transition: "right .5s"
        });
        bg.css({
            display: "none",
            transition: "display 1s"
        });
    }
});