<?php
/**
==============================================================================
*	@package dokeos.document
==============================================================================
*/
	// name of the language file that needs to be included
$language_file = 'document';
require_once '../inc/global.inc.php';
$noPHP_SELF=true;
$header_file= Security::remove_XSS($_GET['file']);	
$path_array=explode('/',str_replace('\\','/',$header_file));
$path_array = array_map('urldecode',$path_array);
$header_file=implode('/',$path_array);
$nameTools = $header_file;

if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='') {
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace'));
}

$interbreadcrumb[]= array ("url"=>"./document.php?curdirpath=".dirname($header_file).$req_gid, "name"=> get_lang('Documents'));
$interbreadcrumb[]= array ("url"=>"showinframes.php?file=".$header_file, "name"=>$header_file);
$file_url_sys=api_get_path(SYS_COURSE_PATH).'document'.$header_file;
$path_info= pathinfo($file_url_sys);

Display::display_header(null,"Doc");
echo "<div align=\"center\">";

if ($path_info['extension']=='html' && false) { //should be added in the platform admin
	$file_url_web='document_with_glossary_terms.php?file='.urlencode($header_file);
}  else {
	$file_url_web=api_get_path('WEB_COURSE_PATH').$_course['path'].'/document'.$header_file."?".api_get_cidreq();	
}
echo "<a href='".$file_url_web."' target='blank'>".get_lang('_cut_paste_link')."</a></div>";
?>