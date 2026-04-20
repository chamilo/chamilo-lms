


var startTime = Date.now();
var globalTimeRd = getGlobalTimePage(idPageTop);
var globalProgressRd = 5;
var globalTimePart = (globalTimeRd / 20);
if (globalTimePart<20) {globalTimePart=20;}

$('.form-progress-update-bar').animate({width: '10%'},200);

setTimeout(function(){

    launchRenderP();
    setTimeout(function(){
        $('.form-progress-update-bar').animate({width: '99%'},globalTimeRd);
    },20);
    
},200);

function launchRenderP() {

    var urRend = '../ajax/teachdoc-render.php?id=' + idPageTop;
    
    $.ajax({
        url : urRend,type : "POST",
        success : function(data,textStatus,jqXHR) {
            var endTime = Date.now();
            var timDiff = parseInt(endTime - startTime);
            if (globalTimeRd<1000) {
                globalTimeRd = 1000;
            }
            setGlobalTimePage(idPageTop,timDiff);
            if (data.indexOf("KOCS")!=-1) {
                $("#logsreturn").html(data);
            } else {
                
                data = data + "<br><a href='" + RedirLP + "' >link</a>";
                //$("#logsreturn").html(data);
                window.location.href = RedirLP;
            }
        },
        error : function (jqXHR, textStatus, errorThrown)
        {
            setTimeout(function(){
            location.reload();
            },1500);
        }
    });

}

function getGlobalTimePage(idPageTop) {
    
    var timR = 4000;

    try{
        if (localStorage) {
            timR = window.localStorage.getItem('GlobalTimeRenderPage'+idPageTop);
        }
    }catch(err){
        timR = 4000;
    }
    if (timR === null||timR == "null"){
        timR = 4000;
    }
    if (timR === undefined) {
        timR = 4000;
    }
    if (isNaN(timR)) {
        timR = 4000;
    }
    if (typeof timR == 'undefined') {
        timR = 4000;
    }
    return parseInt(timR) + 3000;

}

function setGlobalTimePage(idPageTop,TimeRd) {
    if (localStorage) {
        try {
            window.localStorage.setItem('GlobalTimeRenderPage'+idPageTop,TimeRd);
        } catch(err) {
        }
    }
}
