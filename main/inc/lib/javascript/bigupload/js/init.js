
bigUpload = new bigUpload();
bigUpload.scormid = document.getElementById("scormid").value;

function upload() {
    bigUpload.fire();
    keepProgress();
    $("#bigUploadSubmit").css('display','none');
    $(".bigUploadAbort").css('display','');
}

function abort() {
    bigUpload.abortFileUpload();
    $("#bigUploadSubmit").css('display','');
    $(".bigUploadAbort").css('display','none');
}

var refreshCount = 0;
var refreshpProgress = 1;

function keepProgress(){
    $("#bigUploadProgressBarFilled").html(refreshpProgress + '%');
    $("#bigUploadProgressBarFilled").css("width",refreshpProgress + '%');
    refreshpProgress++;
    if(refreshpProgress>91){
        refreshpProgress = 80;
    }
    setTimeout('keepProgress()',2000);
}

function keepAlive(){
    var kp = document.getElementById('kp');
    kp.src = kp.src;
    setTimeout('keepAlive()',20000);
    refreshCount++;
}
keepAlive();

function controlAlive(){

    var kp = document.getElementById('bigUploadResponse').innerHTML;
    if (kp.indexOf('CODEisOK')!=-1) {
        var nameSrc = document.getElementById('finalNameSrc').innerHTML;
        if (nameSrc!='') {
            document.getElementById('run').style.display = 'none';
            document.getElementById('see').style.display = '';
            var linkact = document.getElementById('linkact').innerHTML;
            window.location = linkact + "&namesrc=" +  nameSrc;
        }
    } else {
        setTimeout('controlAlive()',250);	
    }

}
controlAlive();
