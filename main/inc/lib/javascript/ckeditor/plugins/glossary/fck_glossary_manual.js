/**

Makes posible to load glossary items from the Glossary Tool
This library will be loaded in:

document/showinframes.php
newscorm/lp_view.php
newscorm/scorm_api.php

 */
//$(document).ready(function() {
//    $(window).load(function () {
    my_protocol = location.protocol;
    my_pathname=location.pathname;
    work_path = my_pathname.substr(0,my_pathname.indexOf('/courses/'));      

    //$("body .glossary").mouseover(function(){
    $("body").on("click", ".glossary", function() {
        is_glossary_name = $(this).html();	        
        random_id = Math.round(Math.random()*100);

        /*div_show_id="div_show_id"+random_id;
                div_content_id="div_content_id"+random_id;*/

        div_show_id="div_show_id";
        div_content_id="div_content_id";
        
        $(this).append("<div id="+div_show_id+" ><div id="+div_content_id+">&nbsp;</div></div>");

        var $target = $(this);             

        $("#"+div_show_id).dialog("destroy");
        $("#"+div_show_id).dialog({
            autoOpen: false,
            width: 600,
            height: 200,
            position:  { my: 'left top', at: 'right top', of: $target },
            close: function(){
                $("div#"+div_show_id).remove();
                $("div#"+div_content_id).remove();
            }
        });


        
        /* $("div#"+div_show_id).attr("style","z-index:99;display:inline;float:left;position:absolute;background-color:#F2F2F2;border-bottom: 1px solid #2E2E2E;border-right: 1px solid #2E2E2E;border-left: 1px solid #2E2E2E;border-top: 1px solid #2E2E2E;color:#305582;margin-left:5px;margin-right:5px;");
                 $("div#"+div_content_id).attr("style","z-index:99;background-color:#F2F2F2;color:#0B3861;margin-left:8px;margin-right:8px;margin-top:5px;margin-bottom:5px;");*/

        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(result) {
                $("div#"+div_content_id).html("<img src='../../../../../../../main/inc/lib/javascript/indicator.gif' />"); 
            },        
            type: "POST",
            url: "../../../../../../../main/glossary/glossary_ajax_request.php",
            data: "glossary_name="+is_glossary_name,
            success: function(data) {
                $("div#"+div_content_id).html(data);
                $("#"+div_show_id).dialog("open");
            }
        });
    });
        
/*
	    $("body .glossary").mouseout(function(){
	        current_element=$(this);
	        div_show_id=current_element.find("div").attr("id");
	        $("div#"+div_show_id).remove();
	    });
*/
		
//    });
//});
