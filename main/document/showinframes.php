<?php
/* For licensing terms, see /license.txt */

/**
 *	This file will show documents in a separate frame.
 *	We don't like frames, but it was the best of two bad things.
 *
 *	display html files within Chamilo - html files have the Chamilo header.
 *
 *	--- advantages ---
 *	users "feel" like they are in Chamilo,
 *	and they can use the navigation context provided by the header.
 *
 *	--- design ---
 *	a file gets a parameter (an html file)
 *	and shows
 *	- chamilo header
 *	- html file from parameter
 *	- (removed) chamilo footer
 *
 *	@version 0.6
 *	@author Roan Embrechts (roan.embrechts@vub.ac.be)
 *	@package chamilo.document
 */

/*   INITIALIZATION */

$language_file[] = 'document';
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'glossary.lib.php';

$noPHP_SELF = true;
$header_file = Security::remove_XSS($_GET['file']);
$path_array = explode('/', str_replace('\\', '/', $header_file));
$path_array = array_map('urldecode', $path_array);
$header_file = implode('/', $path_array);
$nameTools = $header_file;

if (isset($_SESSION['_gid']) && $_SESSION['_gid'] != '') {
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ('url' => '../group/group.php?', 'name' => get_lang('Groups'));
	$interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace'));
}

$interbreadcrumb[] = array('url' => './document.php?curdirpath='.dirname($header_file).$req_gid, 'name' => get_lang('Documents'));
$name_to_show = cut($header_file, 80);
$interbreadcrumb[] = array('url' => 'showinframes.php?gid='.$req_gid.'&file='.$header_file, 'name' => $name_to_show);

$file_url_sys = api_get_path(SYS_COURSE_PATH).'document'.$header_file;
$path_info = pathinfo($file_url_sys);
$this_section = SECTION_COURSES;


/*
if (!empty($_GET['nopages'])) {
	$nopages = Security::remove_XSS($_GET['nopages']);
	if ($nopages == 1) {
		require_once api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
		Display::display_error_message(get_lang('FileNotFound'));
	}
	exit;
}
*/

$_SESSION['whereami'] = 'document/view';

$nameTools = get_lang('Documents');
$file = Security::remove_XSS(urldecode($_GET['file']));

/*	Main section */

header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
//header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Last-Modified: Wed, 01 Jan 2100 00:00:00 GMT');

header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$browser_display_title = 'Documents - '.Security::remove_XSS($_GET['cidReq']).' - '.$file;

// Only admins get to see the "no frames" link in pageheader.php, so students get a header that's not so high
$frameheight = 135;
if ($is_courseAdmin) {
	$frameheight = 165;
}

$file_root = $_course['path'].'/document'.str_replace('%2F', '/', $file);
$file_url_sys = api_get_path(SYS_COURSE_PATH).$file_root;
$file_url_web = api_get_path(WEB_COURSE_PATH).$file_root;
$path_info = pathinfo($file_url_sys);

$js_glossary_in_documents = '';
if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
	$js_glossary_in_documents = '	//	    $(document).ready(function() {
									$.frameReady(function() {
								       //  $("<div>I am a div courses</div>").prependTo("body");
								      }, "top.mainFrame",
								      { load: [
								      		{type:"script", id:"_fr1", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js"},
								            {type:"script", id:"_fr2", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js"},
								            {type:"script", id:"_fr3", src:"'.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/plugins/glossary/fck_glossary_manual.js"}
								      	 ]
								      }
								      );
								    //});';
} elseif (api_get_setting('show_glossary_in_documents') == 'isautomatic') {
	$js_glossary_in_documents =	'//    $(document).ready(function() {
								      $.frameReady(function(){
								       //  $("<div>I am a div courses</div>").prependTo("body");

								      }, "top.mainFrame",
								      { load: [
								      		{type:"script", id:"_fr1", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js"},
								            {type:"script", id:"_fr2", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js"},
								            {type:"script", id:"_fr3", src:"'.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/plugins/glossary/fck_glossary_automatic.js"}
								      	 ]
								      }
								      );
								//   });';
}

$htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js"></script>';
$htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js"></script>';

$htmlHeadXtra[] = '<script type="text/javascript">
<!--
	var updateContentHeight = function() {
		HeaderHeight = document.getElementById("header").offsetHeight;
		FooterHeight = document.getElementById("footer").offsetHeight;
		docHeight = document.body.clientHeight;
		document.getElementById("mainFrame").style.height = ((docHeight-(parseInt(HeaderHeight)+parseInt(FooterHeight)))+60)+"px";
	};

	// Fixes the content height of the frame
	window.onload = function() {
		updateContentHeight();
		'.$js_glossary_in_documents.'
	}
-->
</script>';

//fix the screen when you try to access a protected course through the url
$is_allowed_in_course = $_SESSION ['is_allowed_in_course'];   
if($is_allowed_in_course==false){
	Display::display_header();
	echo '<div align="center">';
		Display::display_error_message(get_lang('NotAllowedClickBack').'<br /><br /><a href="javascript:history.back(1)">'.get_lang('BackToPreviousPage').'</a><br />', false);
	echo '</div>';
	Display::display_footer();
die();
}
		
//Display::display_header($tool_name, 'User');

Display::display_header('');
echo "<div align=\"center\">";
$file_url_web = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$header_file.'?'.api_get_cidreq();
echo '<a href="'.$file_url_web.'" target="_blank">'.get_lang('_cut_paste_link').'</a></div>';
//echo '<div>';

if (file_exists($file_url_sys)) {
	echo '<iframe border="0" frameborder="0" scrolling="auto"  style="width:100%;"  id="mainFrame" name="mainFrame" src="'.$file_url_web.'?'.api_get_cidreq().'&rand='.mt_rand(1, 10000).'"></iframe>';
} else {
	echo '<frame name="mainFrame" id="mainFrame" src=showinframes.php?nopages=1 />';
}

//echo '</div>';

Display::display_footer();
