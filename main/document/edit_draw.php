<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Ra�a Trabado
 * @since 25/september/2010
*/

/*	INIT SECTION */

$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/editdraw';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

api_protect_course_script();
api_block_anonymous_users();

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id());    
if (empty($document_data)) {
    api_not_allowed();
} else {    
    $document_id    = $document_data['id'];
    $file_path      = $document_data['path'];
    $dir            = dirname($document_data['path']);
    $parent_id      = DocumentManager::get_document_id(api_get_course_info(), $dir);
}

$dir= str_replace('\\', '/',$dir);//and urlencode each url $curdirpath (hack clean $curdirpath under Windows - Bug #3261)

/* Constants & Variables */
$current_session_id=api_get_session_id();
//path for svg-edit save
$_SESSION['draw_dir']=Security::remove_XSS($dir);
if ($_SESSION['draw_dir']=='/'){
	$_SESSION['draw_dir'] = '';
}
$_SESSION['draw_file']=basename(Security::remove_XSS($file_path));
$get_file = Security::remove_XSS($file_path);
$file = basename($get_file);
$temp_file = explode(".",$file);
$filename=$temp_file[0];
$nameTools = get_lang('EditDocument') . ': '.$filename;

$courseDir   = $_course['path'].'/document';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

/*	Other initialization code */

/* Please, do not modify this dirname formatting */

if (strstr($dir, '..')) {
	$dir = '/';
}

if ($dir[0] == '.') {
	$dir = substr($dir, 1);
}

if ($dir[0] != '/') {
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
	$dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

//groups //TODO:clean
if (isset($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {	
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace'));
	$group_document = true;
	$noPHP_SELF = true;	
}

if (!$is_certificate_mode)
	$interbreadcrumb[]=array("url"=>"./document.php?id=".$document_id.$req_gid, "name"=> get_lang('Documents'));
else
	$interbreadcrumb[]= array (	'url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder(api_get_user_id(), $dir, $current_session_id);

if (!$is_allowedToEdit) {
	api_not_allowed(true);
}

event_access_tool(TOOL_DOCUMENT);

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
		echo '<a href="document.php?id='.$parent_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
		
		echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'&origin=editdraw">'.Display::return_icon('edit.png',get_lang('Rename').'/'.get_lang('Comments'),'','32').'</a>';
echo '</div>';

if (api_browser_support('svg')) {	
	//automatic loading the course language
	$svgedit_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
	$langsvgedit  = api_get_language_isocode();
	$langsvgedit = isset($svgedit_code_translation_table[$langsvgedit]) ? $svgedit_code_translation_table[$langsvgedit] : $langsvgedit;
	$langsvgedit = file_exists(api_get_path(LIBRARY_PATH).'svg-edit/locale/lang.'.$langsvgedit.'.js') ? $langsvgedit : 'en';	
	echo '<iframe style=\'height: 550px; width: 100%;\' scrolling=\'no\' frameborder=\'0\' src=\''.api_get_path(WEB_LIBRARY_PATH).'svg-edit/svg-editor.php?url=../../../../courses/'.$courseDir.$dir.$file.'&amp;lang='.$langsvgedit.'\'>';	
	echo '</iframe>';
} else {	
	Display::display_error_message(get_lang('BrowserDontSupportsSVG'));
}
Display::display_footer();