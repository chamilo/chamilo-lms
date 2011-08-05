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
/**
 * INITIALIZATION
 */
$language_file[] = 'document';
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(LIBRARY_PATH).'glossary.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

// Protection
api_protect_course_script();

$noPHP_SELF = true;
$header_file = Security::remove_XSS($_GET['file']);
$document_id = intval($_GET['id']);

$course_info = api_get_course_info();
$course_code = api_get_course_id(); 

if (empty($course_info)) {
    api_not_allowed(true);
}

//Generate path 
if (!$document_id) {
    $document_id = DocumentManager::get_document_id($course_info, $header_file);
}
$document_data = DocumentManager::get_document_data_by_id($document_id, $course_code);

if (empty($document_data)) {
    api_not_allowed(true);
}

$header_file  = $document_data['path'];
$name_to_show = $document_data['title'];

$path_array = explode('/', str_replace('\\', '/', $header_file));
$path_array = array_map('urldecode', $path_array);
$header_file = implode('/', $path_array);

$file = Security::remove_XSS(urldecode($document_data['path']));

$file_root = $course_info['path'].'/document'.str_replace('%2F', '/', $file);
$file_url_sys = api_get_path(SYS_COURSE_PATH).$file_root;
$file_url_web = api_get_path(WEB_COURSE_PATH).$file_root;

if (!file_exists($file_url_sys)) {
    api_not_allowed(true);
}

if (is_dir($file_url_sys)) {
    api_not_allowed(true);
}

//fix the screen when you try to access a protected course through the url
$is_allowed_in_course = $_SESSION ['is_allowed_in_course'];

if ($is_allowed_in_course == false) {
    api_not_allowed(true);
}

//Check user visibility
//$is_visible = DocumentManager::is_visible_by_id($document_id, $course_info, api_get_session_id(), api_get_user_id());
$is_visible = DocumentManager::check_visibility_tree($document_id, api_get_course_id(), api_get_session_id(), api_get_user_id());

if (!api_is_allowed_to_edit() && !$is_visible) {
    api_not_allowed(true);
}

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);
$current_group_name=$current_group['name'];

if (isset($group_id) && $group_id != '') {
    $req_gid = '&amp;gidReq='.$group_id;
    $interbreadcrumb[] = array ('url' => '../group/group.php?', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$current_group_name);
    $name_to_show = explode('/', $name_to_show);
    unset ($name_to_show[1]);
    $name_to_show = implode('/', $name_to_show);
}

$interbreadcrumb[] = array('url' => './document.php?curdirpath='.dirname($header_file).$req_gid, 'name' => get_lang('Documents'));
$interbreadcrumb[] = array('url' => 'showinframes.php?gid='.$req_gid.'&amp;file='.$header_file, 'name' => $name_to_show);

$this_section = SECTION_COURSES;
$_SESSION['whereami'] = 'document/view';
$nameTools = get_lang('Documents');

/**
 * Main code section
 */
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
$js_glossary_in_documents = '';
if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
    $js_glossary_in_documents = '	//	    $(document).ready(function() {
                                    $.frameReady(function() {
                                       //  $("<div>I am a div courses</div>").prependTo("body");
                                      }, "top.mainFrame",
                                      { load: [
                                              {type:"script", id:"_fr1", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.min.js"},
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
                                              {type:"script", id:"_fr1", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.min.js"},
                                            {type:"script", id:"_fr2", src:"'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js"},
                                            {type:"script", id:"_fr3", src:"'.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/plugins/glossary/fck_glossary_automatic.js"}
                                           ]
                                      }
                                      );
                                //   });';
}

$htmlHeadXtra[] = '<script type="text/javascript">
<!--
    var jQueryFrameReadyConfigPath = \''.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.min.js\';
-->
</script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js"></script>';

$htmlHeadXtra[] = '

<script type="text/javascript">
<!--
    var updateContentHeight = function() {
        //HeaderHeight = document.getElementById("header").offsetHeight;
        //FooterHeight = document.getElementById("footer").offsetHeight;
        //document.getElementById("mainFrame").style.height = ((docHeight-(parseInt(HeaderHeight)+parseInt(FooterHeight)))+60)+"px";
        my_iframe = document.getElementById("mainFrame");
        new_height = my_iframe.contentWindow.document.body.scrollHeight;
        my_iframe.height = my_iframe.contentWindow.document.body.scrollHeight + "px";
    };

    // Fixes the content height of the frame
    window.onload = function() {
        updateContentHeight();
        '.$js_glossary_in_documents.'
    }
-->
</script>';


//Display::display_header($tool_name, 'User');

Display::display_header('');
echo "<div align=\"center\">";
$file_url_web = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$header_file.'?'.api_get_cidreq();
echo '<a href="'.$file_url_web.'" target="_blank">'.get_lang('_cut_paste_link').'</a></div>';

$pathinfo =pathinfo($header_file);
if ($pathinfo['extension']=='wav' && api_get_setting('enable_nanogong') == 'true'){
	echo '<div align="center">';
		echo '<br/>';
		echo '<applet id="applet" archive="../inc/lib/nanogong/nanogong.jar" code="gong.NanoGong" width="160" height="40">';
			echo '<param name="SoundFileURL" value="'.$file_url_web.'" />';
			echo '<param name="ShowSaveButton" value="false" />';
			echo '<param name="ShowTime" value="true" />';
			echo '<param name="ShowRecordButton" value="false" />';
		echo '</applet>';
	echo '</div>';
}
else{
	echo '<iframe border="0" frameborder="0" scrolling="no" style="width:100%;"  id="mainFrame" name="mainFrame" src="'.$file_url_web.'?'.api_get_cidreq().'&amp;rand='.mt_rand(1, 10000).'"></iframe>';
}
Display::display_footer();
