
var canvas = document.createElement("canvas");
canvas.width = 700;
canvas.height = 980;

$(document).ready(function() {

    setTimeout(function() {

        html2canvas(document.getElementById('baseHtmltoRender'),{
            canvas : canvas,
            width : 700,
            height : 980
            }).then( function (canvas) {
            var canvasImg = canvas.toDataURL("image/png");
            $("#imgHtmlCanvas").attr("src", canvasImg);
            uploadImageToCachePass2(canvasImg);
            $("#baseHtmltoRender").css("left","300px");
        });
    
    }, 3000);
        
});

function uploadImageToCachePass2(canvasBlob){

    var v = Math.floor(Math.random() * 10000);
    const formData = new FormData();
    
    formData.append('file64', canvasBlob);

    const req = new XMLHttpRequest();
    req.open('POST', '../ajax/ajax.upldblob.php?modexport='+modExport+'&class=1&mypath=page'+idPageRef+'&id='+idPageRef,true);
    req.onload = function () {
        if (req.status >= 200 && req.status < 400) {
            const res = req.responseText;
            if (res.indexOf("KO")==-1) {
                console.log("load :" + res);
            } else {
                console.log("error :" + res);
            }
        }
    };

    req.send(formData);

}