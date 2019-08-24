if (window.addEventListener) {
    window.addEventListener("scroll", function () {fix_sidemenu(); });
}
function fix_sidemenu() {
    var w, top;
    w = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    top = scrolltop();
    if (w < 993 && w > 600) {
        if (top == 0) {
            document.getElementById("sidenav").style.top = "144px";
        }
        if (top > 0 && top < 100) {
            document.getElementById("sidenav").style.top = (144 - top) + "px";
        }
        if (top > 100) {
            document.getElementById("sidenav").style.top = document.getElementById("topnav").offsetHeight + "px";
            // document.getElementById("belowtopnav").style.paddingTop = "44px";
            document.getElementById("topnav").style.position = "fixed";
            document.getElementById("topnav").style.top = "0";
            // document.getElementById("googleSearch").style.position = "fixed";
            // document.getElementById("googleSearch").style.top = "0";
            // document.getElementById("google_translate_element").style.position = "fixed";
            // document.getElementById("google_translate_element").style.top = "0";
        } else {
            // document.getElementById("belowtopnav").style.paddingTop = "0";
            document.getElementById("topnav").style.position = "relative";
            // document.getElementById("googleSearch").style.position = "absolute";
            // document.getElementById("googleSearch").style.top = "";
            // document.getElementById("google_translate_element").style.position = "absolute";
            // document.getElementById("google_translate_element").style.top = "";
        }
        document.getElementById("leftmenuinner").style.paddingTop = "0"; //SCROLLNYTT
    } else {
        if (top == 0) {
            document.getElementById("sidenav").style.top = "112px";
        }
        if (top > 0 && top < 66) {
            document.getElementById("sidenav").style.top = (112 - top) + "px";
        }
        if (top > 66) {
            document.getElementById("sidenav").style.top = "44px";
            if (w > 992) {document.getElementById("leftmenuinner").style.paddingTop = "44px";} //SCROLLNYTT
            // document.getElementById("belowtopnav").style.paddingTop = "44px";
            document.getElementById("topnav").style.position = "fixed";
            document.getElementById("topnav").style.top = "0";
            // document.getElementById("googleSearch").style.position = "fixed";
            // document.getElementById("googleSearch").style.top = "0";
            // document.getElementById("google_translate_element").style.position = "fixed";
            // document.getElementById("google_translate_element").style.top = "0";
        } else {
            if (w > 992) { document.getElementById("leftmenuinner").style.paddingTop = (112 - top) + "px";} //SCROLLNYTT
            // document.getElementById("belowtopnav").style.paddingTop = "0";
            document.getElementById("topnav").style.position = "relative";
            // document.getElementById("googleSearch").style.position = "absolute";
            // document.getElementById("googleSearch").style.top = "";
            // document.getElementById("google_translate_element").style.position = "absolute";
            // document.getElementById("google_translate_element").style.top = "";
        }
    }
}
function scrolltop() {
    var top = 0;
    if (typeof(window.pageYOffset) == "number") {
        top = window.pageYOffset;
    } else if (document.body && document.body.scrollTop) {
        top = document.body.scrollTop;
    } else if (document.documentElement && document.documentElement.scrollTop) {
        top = document.documentElement.scrollTop;
    }
    return top;
}

function open_menu() {
    var x, m;
    m = (document.getElementById("leftmenu") || document.getElementById("sidenav"));
    if (m.style.display == "block") {
        close_menu();
    } else {
        w3_close_all_nav();
        m.style.display = "block";
        if (document.getElementsByClassName) {
            x = document.getElementsByClassName("chapter")
            for (i = 0; i < x.length; i++) {
                x[i].style.visibility = "hidden";
            }
            x = document.getElementsByClassName("nav")
            for (i = 0; i < x.length; i++) {
                x[i].style.visibility = "hidden";
            }
            x = document.getElementsByClassName("sharethis")
            for (i = 0; i < x.length; i++) {
                x[i].style.visibility = "hidden";
            }
        }
        fix_sidemenu();
    }
}
function close_menu() {
    var m;
    m = (document.getElementById("leftmenu") || document.getElementById("sidenav"));
    m.style.display = "none";
    if (document.getElementsByClassName) {
        x = document.getElementsByClassName("chapter")
        for (i = 0; i < x.length; i++) {
            x[i].style.visibility = "visible";
        }
        x = document.getElementsByClassName("nav")
        for (i = 0; i < x.length; i++) {
            x[i].style.visibility = "visible";
        }
        x = document.getElementsByClassName("sharethis")
        for (i = 0; i < x.length; i++) {
            x[i].style.visibility = "visible";
        }
    }
}

function w3_close_all_nav() {
    // w3_close_all_topnav();
    close_menu();
}

function w3_close_all_topnav() {
    w3_close_nav("tutorials");
    w3_close_nav("references");
    w3_close_nav("exercises");
}

function w3_close_nav(x) {
    document.getElementById("nav_" + x).style.display = "none";
    if (document.getElementById("topnavbtn_" + x)) {
        document.getElementById("topnavbtn_" + x).getElementsByTagName("i")[0].style.display = "inline";
        document.getElementById("topnavbtn_" + x).getElementsByTagName("i")[1].style.display = "none";
        // document.getElementById("nav_" + x).style.height = "";
    }
}