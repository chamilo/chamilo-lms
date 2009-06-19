<?php
$language_file[] = 'document';
require_once '../inc/global.inc.php';
require_once '../glossary/glossary.class.php';
/*
============================================================================== 
		Main section
============================================================================== 
*/ 
$file = Security::remove_XSS(urldecode($_GET['file']));
$file=explode('?cidReq',$file);
$file=$file[0];
$file_root=$_course['path'].'/document'.str_replace('%2F', '/',$file);
$file_url_sys=api_get_path(SYS_COURSE_PATH).$file_root;
$file_url_web=api_get_path(WEB_COURSE_PATH).$file_root;

$content_html=file_get_contents($file_url_sys);
$new_file=str_replace('<head>','<head><script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script><script type="text/javascript">
 $(document).ready(function() {
	$("body a").toggle(function(){
	  $(this).append("<div id=\"div_show_id\" ><div id=\"div_content_id\">&nbsp;</div></div>");
   	  $("div#div_show_id").attr("style","display:inline;float:left;position:absolute;background-color:#F5F6CE;border-bottom: 1px dashed #dddddd;border-right: 1px dashed #dddddd;border-left: 1px dashed #dddddd;border-top: 1px dashed #dddddd;color:#305582;margin-left:5px;margin-right:5px;");
   	  $("div#div_content_id").attr("style","background-color:#F5F6CE;color:#305582;margin-left:8px;margin-right:8px;margin-top:5px;margin-bottom:5px;");
 		notebook_id=$(this).attr("name");
  	  	data_notebook=notebook_id.split("link");
  	  	my_glossary_id=data_notebook[1];

  	  	$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("div#div_content_id").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
			type: "POST",
			url: "../glossary/glossary_ajax_request.php",
			data: "glossary_id="+my_glossary_id,
			success: function(datos) {
			 	$("div#div_content_id").html(datos);
			}
	 	});  	
   	  		
	},function(){
	    $("div#div_show_id").remove();
	});
});
</script>',$content_html);
$content_html=explode('</head>',$new_file);
$head_html=$content_html[0];
$content_html=$content_html[1];
$array_glossary=GlossaryManager::get_glossary_terms();
if (count($array_glossary)>0) {
	foreach ($array_glossary as $index_glossary => $value_glossary) {
		$to_be_replaced[]=$str_href='<a name="link'.$value_glossary['id'].'"  href="javascript:void(0)" >'.$value_glossary['name'].'</a>';		
		$to_replaced[]=$value_glossary['name'];
	}
}	
$new_file=str_replace($to_replaced,$to_be_replaced,$content_html);
$new_file=$head_html.$new_file;
echo $new_file;
?>
