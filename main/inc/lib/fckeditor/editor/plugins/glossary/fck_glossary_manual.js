$(document).ready(function() {
    $(window).load(function () {
	    $("body .glossary").mouseover(function(){
	        is_glossary_name=$(this).html();
		    random_id=Math.round(Math.random()*100);
	        div_show_id="div_show_id"+random_id;
	        div_content_id="div_content_id"+random_id;
	         $(this).append("<div id="+div_show_id+" ><div id="+div_content_id+">&nbsp;</div></div>");
	         $("div#"+div_show_id).attr("style","display:inline;float:left;position:absolute;background-color:#F5F6CE;border-bottom: 1px dashed #dddddd;border-right: 1px dashed #dddddd;border-left: 1px dashed #dddddd;border-top: 1px dashed #dddddd;color:#305582;margin-left:5px;margin-right:5px;");
	         $("div#"+div_content_id).attr("style","background-color:#F5F6CE;color:#305582;margin-left:8px;margin-right:8px;margin-top:5px;margin-bottom:5px;");

	       $.ajax({
	            contentType: "application/x-www-form-urlencoded",
	            beforeSend: function(objeto) {
	            $("div#"+div_content_id).html("<img src=\'http://"+location.host+"/main/inc/lib/javascript/indicator.gif\' />"); },
	            type: "POST",
	            url: "http://"+location.host+"/main/glossary/glossary_ajax_request.php",
	            data: "glossary_name="+is_glossary_name,
	            success: function(datos) {
	                $("div#"+div_content_id).html(datos);
	            }
	        }); 		 
	    });
	    $("body .glossary").mouseout(function(){
	        current_element=$(this);
	        div_show_id=current_element.find("div").attr("id");
	        $("div#"+div_show_id).remove();      
	    });	 
    });
});