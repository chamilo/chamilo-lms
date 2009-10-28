$(document).ready(function() {
    $(window).load(function () {
     var my_text=$("body").html();
     my_protocol = location.protocol;
	 my_pathname=location.pathname;
	 work_path = my_pathname.substr(0,my_pathname.indexOf('/courses/'));
     $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(content_object) {
        },
        type: "POST",
        url: my_protocol+"//"+location.host+work_path+"/main/glossary/glossary_ajax_request.php",
        data: "glossary_data=true",
        success: function(datos) {
			  if (datos.length==0) {
			  	return false;
			  }
                data_terms=datas.split("[|.|_|.|-|.|]");
                for(i=0;i<data_terms.length;i++) {
                    specific_terms=data_terms[i].split("__|__|");
                    var my_specific_terms = new RegExp('[^A-Za-z0-9/_\]('+specific_terms[1]+')',"gi");
				    new_html=my_text.replace(my_specific_terms,"<span class=\"glossary-ajax\" style='color:blue'  name=\"link"+specific_terms[0]+"\">"+specific_terms[1]+"</span>");
                    $("body").html(new_html);
                    my_text=$("body").html();
                }

			  $("body .glossary-ajax").mouseover(function(){
	            random_id=Math.round(Math.random()*100);
	            div_show_id="div_show_id"+random_id;
	            div_content_id="div_content_id"+random_id;
	             $(this).append("<div id="+div_show_id+" ><div id="+div_content_id+">&nbsp;</div></div>");
	             $("div#"+div_show_id).attr("style","display:inline;float:left;position:absolute;background-color:#F5F6CE;border-bottom: 1px dashed #dddddd;border-right: 1px dashed #dddddd;border-left: 1px dashed #dddddd;border-top: 1px dashed #dddddd;color:#305582;margin-left:5px;margin-right:5px;");
	             $("div#"+div_content_id).attr("style","background-color:#F5F6CE;color:#305582;margin-left:8px;margin-right:8px;margin-top:5px;margin-bottom:5px;");
	                notebook_id=$(this).attr("name");
	                data_notebook=notebook_id.split("link");
	                my_glossary_id=data_notebook[1];
	                $.ajax({
	                    contentType: "application/x-www-form-urlencoded",
	                    beforeSend: function(content_object) {
	                    $("div#"+div_content_id).html("<img src="+my_protocol+"//"+location.host+work_path+"/main/inc/lib/javascript/indicator.gif />"); },
	                    type: "POST",
	                    url: my_protocol+"//"+location.host+work_path+"/main/glossary/glossary_ajax_request.php",
	                    data: "glossary_id="+my_glossary_id,
	                    success: function(datas) {
	                        $("div#"+div_content_id).html(datas);
	                    }
	                });
	          });
	          $("body .glossary-ajax").mouseout(function(){
		            var current_element,
		            current_element=$(this);
		            div_show_id=current_element.find("div").attr("id");
		            $("div#"+div_show_id).remove();
	          });


				}

            });
        });
});