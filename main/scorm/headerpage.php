<?php // $id: $
/**
============================================================================== 
*	@package dokeos.scorm
============================================================================== 
*/
	$langFile = "scormdocument";

	include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

	$openDir = $_GET['openDir'];
	$pos=strrpos($openDir,'//');
	$nameTools = substr($openDir,$pos+1,strlen($openDir));

	$noPHP_SELF=true;

	$interbreadcrumb[]= array ("url"=>"./scormdocument.php", "name"=> get_lang('Doc'));
	Display::display_header($nameTools,"Path");
?>