<?php
/**
==============================================================================
*	@package dokeos.document
==============================================================================
*/
	// name of the language file that needs to be included 
$language_file = 'document';


	include('../inc/global.inc.php');

	$nameTools = $_GET['file'];

	$noPHP_SELF=true;

	$path_array=explode('/',str_replace('\\','/',$_GET['file']));

	$path_array=array_map('urlencode',$path_array);

	$_GET['file']=implode('/',$path_array);

	if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='')
	{
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace'));
	}

	$interbreadcrumb[]= array ("url"=>"./document.php?curdirpath=".dirname($_GET['file']).$req_gid, "name"=> $langDocuments);

	Display::display_header($nameTools,"Doc");

	echo "<div align=\"center\">";
	echo "<a href='".api_get_path('WEB_COURSE_PATH').$_course['path'].'/document'.$_GET['file']."?".api_get_cidreq()."' target='blank'>".$lang_cut_paste_link."</a></div>";

?>