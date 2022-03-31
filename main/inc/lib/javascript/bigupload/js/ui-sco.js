
function addLinkBigUpload(){

    if (!document.getElementById("file_user_file_bu")){

        var ludiiconplus = _p['web_plugin'] + 'chamilo_upload_large/css/import_scorm.png';
        
        var lbu = $('#linkbu').html();

        var h = '<a id="file_user_file_bu" href="'+lbu +'" ';
		h += ' style="cursor:pointer;" ';
		h += ' alt="large files" title="large files">';
		h += '<img id="studioeltools" src="'+ ludiiconplus + '" ';
		h += ' alt="large files" title="large files" style="cursor:pointer;" /> ';
		h += '</a>';
        
        var num = $("#toolbar-upload").length;
        if(num==1){
            $('#toolbar-upload').find("div:first-child").find("div:first-child").append(h);
        } else {
            $('.actions').append(h);
        }

    }

}

setTimeout(function(){
    addLinkBigUpload();
},300);