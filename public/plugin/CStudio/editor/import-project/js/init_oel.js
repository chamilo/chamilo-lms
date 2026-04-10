
bigUpload = new bigUpload();
bigUpload.scormid = document.getElementById("scormid").value;

function upload() {
    
    bigUpload.fire();
    keepProgress();
    $("#bigUploadSubmit").css('display','none');
    $(".bigUploadAbort").css('display','');
    $("#bigUploadProgressBarFilled").html('1%');
    $("#bigUploadProgressBarFilled").css("width",'1%');
    
    setTimeout(function(){
        if (GloabalDisplayPercent<3) {
            $("#bigUploadProgressBarFilled").html('2%');
            $("#bigUploadProgressBarFilled").css("width",'2%');
            setTimeout(function(){
                if(refreshpProgress<10){
                    $("#bigUploadProgressBarFilled").html('3%');
                    $("#bigUploadProgressBarFilled").css("width",'3%');
                    setTimeout(function(){
                        if(refreshpProgress<10){
                            $("#bigUploadProgressBarFilled").html('4%');
                            $("#bigUploadProgressBarFilled").css("width",'4%');
                            setTimeout(function(){
                                if(refreshpProgress<10){
                                    $("#bigUploadProgressBarFilled").html('5%');
                                    $("#bigUploadProgressBarFilled").css("width",'5%');
                                    setTimeout(function(){
                                        if(refreshpProgress<10){
                                            $("#bigUploadProgressBarFilled").html('6%');
                                            $("#bigUploadProgressBarFilled").css("width",'6%');
                                            setTimeout(function(){
                                                if(refreshpProgress<10){
                                                    $("#bigUploadProgressBarFilled").html('7%');
                                                    $("#bigUploadProgressBarFilled").css("width",'7%');
                                                }
                                            },7000);
                                        }
                                    },6000);
                                }
                            },4000);
                        }
                    },2000);
                }
            },1000);
        }
    },1000);

}

function abort() {
    bigUpload.abortFileUpload();
    $("#bigUploadSubmit").css('display','');
    $(".bigUploadAbort").css('display','none');
}

var refreshCount = 0;
var refreshpProgress = 1;
var refreshpProgressOld = 1;

function keepProgress(){

    if (refreshpProgress>refreshpProgressOld) {
        refreshpProgressOld = refreshpProgress;
        $("#bigUploadProgressBarFilled").html(refreshpProgress + '%');
        $("#bigUploadProgressBarFilled").css("width",refreshpProgress + '%');
        refreshpProgress++;
        if(refreshpProgress>91){
            refreshpProgress = 80;
        }
    }

    setTimeout('keepProgress()',2000);

}

var finalNameSrc = '';

function controlAlive(){

    var kp = document.getElementById('bigUploadResponse').innerHTML;
    
    if (kp.indexOf('CODEisOK')!=-1) {

        var nameSrc = document.getElementById('finalNameSrc').innerHTML;

        if (nameSrc!='') {
            finalNameSrc = nameSrc;
            $(".bigUploadButton").css("display","none");
            $("#bigUploadProgressBarFilled").stop();
            $("#bigUploadProgressBarFilled").html("100%");
            $("#bigUploadProgressBarFilled").animate({
                width : '100%'
            }, 500, function() {

            });
            setTimeout(function(){
                redirectAfterUpload();
            },700);
        }

    } else {
        setTimeout('controlAlive()',500);	
    }

}
controlAlive();

function redirectAfterUpload() {

    document.getElementById('run').style.display = 'none';
    document.getElementById('see').style.display = '';
    var linkact = document.getElementById('linkact').innerHTML;
    var finalurl = linkact + "&action=present&namesrc=" + encodeURIComponent(finalNameSrc);
    finalurl = finalurl.replace(/&amp;/g, '&');
    window.location = finalurl;

}

function cleanFileName(r) {
    r = r.toLowerCase();
    r = r.replace(/ /g,"-");
    r = r.replace(/é/g,"e");
    r = r.replace(new RegExp("\\s", 'g'),"");
    r = r.replace(new RegExp("[àáâãäå]", 'g'),"a");
    r = r.replace(new RegExp("æ", 'g'),"ae");
    r = r.replace(new RegExp("ç", 'g'),"c");
    r = r.replace(new RegExp("[èéêë]", 'g'),"e");
    r = r.replace(new RegExp("[ìíîï]", 'g'),"i");
    r = r.replace(new RegExp("ñ", 'g'),"n");                            
    r = r.replace(new RegExp("[òóôõö]", 'g'),"o");
    r = r.replace(new RegExp("œ", 'g'),"oe");
    r = r.replace(new RegExp("[ùúûü]", 'g'),"u");
    r = r.replace(new RegExp("[ýÿ]", 'g'),"y");

    return r;
}
