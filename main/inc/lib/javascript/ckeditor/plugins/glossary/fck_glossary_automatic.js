/**
* Makes posible to load glossary items from the Glossary Tool
*   This library will be loaded in:
*
*    document/showinframes.php
*    newscorm/lp_view.php
*    newscorm/scorm_api.php
*    
*/

/*$(document).ready(function() {
    $(window).load(function() {
	*/	
    my_protocol = location.protocol;
    my_pathname=location.pathname;
    work_path = my_pathname.substr(0,my_pathname.indexOf('/courses/'));

    $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(content_object) {},
        type: "POST",
        url: my_protocol+"//"+location.host+work_path+"/main/glossary/glossary_ajax_request.php",
        data: "glossary_data=true",
        success: function(datas) {
            if (datas.length==0) {
                return false;
            }
            // glossary terms 
            data_terms=datas.split("[|.|_|.|-|.|]");
            var complex_array = new Array();
            var cp_complex_array = new Array();
            for(i=0;i<data_terms.length;i++) {
                specific_terms=data_terms[i].split("__|__|");
                var real_term = specific_terms[1]; // glossary term 
                var real_code = specific_terms[0]; // glossary id
                complex_array[real_code] = real_term;
                cp_complex_array[real_code] = real_term;
            }

            complex_array.reverse();

            for (var my_index in complex_array) {
                n = complex_array[my_index];
                if (n == null) {
                    n = '';
                } else {
                    for (var cp_my_index in cp_complex_array) {
                        cp_data = cp_complex_array[cp_my_index];
                        if (cp_data == null) {
                            cp_data = '';
                        } else {
                            if (cp_data == n) {
                                my_index = cp_my_index;
                            }
                        }
                    }
                    //alert(n + ' ' + my_index);
                    $('body').removeHighlight().highlight(n,my_index) 
                }                   
            }

            var complex_array = new Array();

            //mouse over event
            //$("body .glossary-ajax").mouseover(function(){
            //
            //mouse on click
            $("body").on("click", ".glossary-ajax", function(e) {
                random_id = Math.round(Math.random()*100);
                
                //div_show_id = "div_show_id"+random_id;
                //div_content_id = "div_content_id"+random_id;
                div_show_id = "div_show_id";
                div_content_id = "div_content_id";
                
                $(this).append("<div id="+div_show_id+"><div id="+div_content_id+">&nbsp;</div></div>");
                
                //$("div#"+div_show_id).attr("style","z-index:99;display:inline;float:left;position:absolute;background-color:#F2F2F2;border-bottom: 1px solid #2E2E2E;border-right: 1px solid #2E2E2E;border-left: 1px solid #2E2E2E;border-top: 1px solid #2E2E2E;color:#305582;margin-left:5px;margin-right:5px;");
                //$("div#"+div_content_id).attr("style","z-index:99;background-color:#F2F2F2;color:#0B3861;margin-left:8px;margin-right:8px;margin-top:5px;margin-bottom:5px;");
                
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
                
                notebook_id = $(this).attr("name");
                data_notebook = notebook_id.split("link");
                my_glossary_id = data_notebook[1];
                
                $.ajax({
                    contentType: "application/x-www-form-urlencoded",
                    beforeSend: function(content_object) {
                        $("div#"+div_content_id).html("<img src='../../../../../../../main/inc/lib/javascript/indicator.gif' />");
                    },
                    type: "POST",
                    url: "../../../../../../../main/glossary/glossary_ajax_request.php",
                    data: "glossary_id="+my_glossary_id,
                    success: function(datas) {
                        $("div#"+div_content_id).html(datas);
                        $("#"+div_show_id).dialog("open");
                    }
                });
                
            });

            //mouse out event
            /*$("body .glossary-ajax").mouseout(function(){
                var current_element = $(this);
                div_show_id=current_element.find("div").attr("id");
                $("div#"+div_show_id).remove();
            });*/
            
            //mouse click
            
            /*$("body").on("click", ".glossary-ajax", function(){
                var current_element = $(this);
                div_show_id=current_element.find("div").attr("id");
                $("div#"+div_show_id).remove();
            });*/
            
        }

    });
/*			
        });
});*/
