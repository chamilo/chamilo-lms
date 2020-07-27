/* For licensing terms, see /license.txt */
console.log('h5p extras is active');

/**
 * Find CKEditor's instance and add an H5P button to it
 */
function installInTinyH5p(){

    if (window.jQuery) { 

        var btnLength = $(".cke_button__source").length;

        if(btnLength>0){

            if($(".cke_button__extras_h5p").length==0){

                var gi = '<a id="cke_50" onClick="loadExtrasObjectsH5p();" class="cke_button cke_button__extras_h5p cke_button_off" href="javascript:return false;" ';
                gi += ' title="H5P insert" tabindex="-1" hidefocus="true" ';
                gi += ' role="button" aria-labelledby="cke_50_label" ';
                gi += ' aria-describedby="cke_50_description" aria-haspopup="false" >';
                gi += '<span class="cke_button_icon" style="background-image:url(\''+ _p['web_plugin'] +'h5p/resources/img/h5pchami.png\');">&nbsp;&nbsp;</span>';
                gi += '<span id="cke_50_label" class="cke_button_label cke_button__source_label" aria-hidden="false"></span>';
                gi += '<span id="cke_50_description" class="cke_button_label" aria-hidden="false"></span></a>';
                $(".cke_button__source").after(gi);

            }
        }
        var inputID = $("#idTitle").length;
        
        if(inputID>0){
            
            if($("#idTitle").val()==''){
                $("#idTitle").val("New document !");
            }

        }
        
    }

    setTimeout(function(){installInTinyH5p();},600);
    
}

setTimeout(function(){installInTinyH5p();},600);

//#H5P:1#

/**
 * Load H5P objects through CKEditor
 */
function loadExtrasObjectsH5p(){
    insertExtrasObjectsH5p();
    $(".cke_dialog_background_cover").css('display','block');
    $("#extrasobjectH5p").css('display','block');
}

/**
 * Insert H5P objects in the body of the area edited through CKEditor
 */
function insertExtrasObjectsH5p(){

    if($(".cke_dialog_background_cover").length==0){
        $("body").append('<div tabindex="-1" style="position:fixed;z-index:10001;top:0px;left:0px;right:0px;bottom:0px;background-color:black;opacity:0.5;display:none;" class="cke_dialog_background_cover"></div>');
    }
    
    if($("#extrasobjectH5p").length==0){
        $("body").append('<div id="extrasobjectH5p" >'+inExtrasObjectsH5p()+'</div>');
        showEditH5pLoad();
    }else{
        loadDataH5Pbase();
    }

}

/**
 * Generate the HTML to insert H5P objects in an HTML document
 * @returns {string}
 */
function inExtrasObjectsH5p(){
    
    var h = '';
    h += '<div style="position:relative;left:0px;top:0px;right:0px;height:36px;font-size:18px;line-height:36px;border-bottom:solid 1px gray;cursor:pointer;background:#f8f8f8;" >&nbsp;&nbsp;H5P&nbsp;Helper</div>';
    h += '<div style="position:absolute;right:5px;top:5px;width:20px;font-size:18px;height:20px;line-height:20px;text-align:center;cursor:pointer;" onClick="closeExtrasH5p()" >X</div>';
    
    h += '<div id="innerEditH5p" style="position:absolute;left:10px;top:45px;right:2px;bottom:5px;';
    h += 'border:solid 0px green;background:white;z-index:10010;" ></div>';

    return h;

}

/**
 * Visually remove H5P objects from CKEditor
 */
function closeExtrasH5p(){

    $("#extrasobjectH5p").css('display','none');
    $(".cke_dialog_background_cover").css('display','none');
}

/**
 * Show the H5P options
 */
function showEditH5pLoad(){

    $(".cke_dialog_background_cover").css('z-index','97');
    $("#extrasobjectH5p").css('z-index','98');
    
    var h = '';
    h += '<p><b>H5P URL path</b></p>';
    h += '<p>';
    h += '<input type="text" style="width:520px;" id="inputSelH5PUrl" />&nbsp;';
    h += '</p>';

    h += '<p><b>Select a content</b></p>';
    h += '<div class="bloch5pGrid" >';
    h += '<div class="bloch5pLine" onClick="selectH5Pbase(\'6725\');" >Multiplication quiz</div>';
    h += '<div class="bloch5pLine" onClick="selectH5Pbase(\'711\');" >Simple drag and drop</div>';
    h += '</div>';

    h += '<p style="text-align:center;" >';
    h += '<button class="btn btn-primary" onCLick="addObjectH5PInCKEDITORByBase()" >Insert in document</button>';
    h += '</p>';

    $("#innerEditH5p").html(h);
    $("#innerEditH5p").css("display","block");
    
    loadDataH5Pbase();

}

/**
 * Load (AJAX) the H5P node types
 */
function loadDataH5Pbase(){

    $('.bloch5pGrid').html('<p style="text-align:center;" ><img src="' + _p['web_plugin'] + 'h5p/resources/img/loadtable.gif" /></p>');

    var urlpath = _p['web_plugin'] +'h5p/resources/ajax/getnodes.php';

    $.ajax({
		url : urlpath,cache : false
	}).done(function(codeHtml){
    
        var urlH5p = _p['web_plugin'] +'h5p/list.php';

        codeHtml += '<a target="_blank" href="'+urlH5p+'" onClick="closeExtrasH5p()" class="bloch5pAdd" >&nbsp;</a>';

        $('.bloch5pGrid').html(codeHtml);
    
    }).error(function(xhr, ajaxOptions, thrownError){

        var h = '<a class="bloch5pLine" onCLick="loadDataH5Pbase();" >Reload</a>';
       
        $('.bloch5pGrid').html(h);

	});

}

/**
 * Select a type of node for the base element of an H5P object
 * @param id
 * @param node_type
 */
function selectH5Pbase(id,node_type){

    $(".bloch5pLine").css("background-color","white").css("color","black");
    $(".bloch5pLine" + id).css("background-color","#2575be").css("color","white");

    var pgid = "h5p/cache-h5p/launch/source-" + id + ".html?tn=" + node_type;
    $("#inputSelH5PUrl").val(pgid);

}

/**
 * Add an H5P object in CKEditor
 */
function addObjectH5PInCKEDITORByBase(){

    var pg = $("#inputSelH5PUrl").val();
    
    if(pg!=''){

        pg = _p['web_plugin'] + pg;

        var h5pc = '<iframe style="max-width:500px;width:90%;border:dotted 1px #2575be;" src="' + pg + '" ';
        if(pg.indexOf('dialogcard')!=-1){
            h5pc += ' width="90%" height="700px" ';
        }else{
            if(pg.indexOf('dragthewords')!=-1){
                h5pc += ' width="90%" height="400px" ';
            }else{
                h5pc += ' width="90%" height="500px" ';
            }
        }
        
        h5pc += ' frameborder="0" ';
        h5pc += ' allowfullscreen="allowfullscreen"></iframe>';

        CKEDITOR.instances.content_lp.insertHtml(h5pc);
        closeExtrasH5p();
                
    }

}
