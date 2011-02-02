<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 30/january/2011
*/

/*	INIT SECTION */

$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/editpaint';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

//
api_protect_course_script();
api_block_anonymous_users();

if (!isset($_GET['curdirpath']) || !isset($_GET['file'])){
	api_not_allowed(true);
}

/* Constants & Variables */
$current_session_id=api_get_session_id();
//path for pixlr save
$_SESSION['paint_dir']=Security::remove_XSS($_GET['curdirpath']);
if($_SESSION['paint_dir']=='/'){
	$_SESSION['paint_dir']='';
}
$_SESSION['paint_file']=basename(Security::remove_XSS($_GET['file']));

//
$get_file = Security::remove_XSS($_GET['file']);

$file = basename($get_file);

$temp_file = explode(".",$file);
$filename=$temp_file[0];
$nameTools = get_lang('EditDocument') . ': '.$filename;
$dir = Security::remove_XSS($_GET['curdirpath']);

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
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
	
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace'));
	$group_document = true;
	$noPHP_SELF = true;	
}


$my_cur_dir_path = Security::remove_XSS($_GET['curdirpath']);
if (!$is_certificate_mode)
	$interbreadcrumb[]=array("url"=>"./document.php?curdirpath=".urlencode($my_cur_dir_path).$req_gid, "name"=> get_lang('Documents'));
else
	$interbreadcrumb[]= array (	'url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));

$is_allowedToEdit = is_allowed_to_edit() || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder($_user['user_id'], $my_cur_dir_path, $current_session_id);

if (!$is_allowedToEdit) {
	api_not_allowed(true);
}

event_access_tool(TOOL_DOCUMENT);

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
		echo '<a href="document.php?curdirpath='.Security::remove_XSS($_GET['curdirpath']).'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview')).get_lang('BackTo').' '.get_lang('DocumentsOverview').'</a>';
		
		echo '<a href="edit_document.php?'.api_get_cidreq().'&curdirpath='.Security::remove_XSS($_GET['curdirpath']).'&amp;file='.urlencode($dir.$file).$req_gid.'&amp;origin=editpaint">'.Display::return_icon('edit.gif',get_lang('Rename')).get_lang('Rename').', '.get_lang('Comment').'</a>';
echo '</div>';

///pixlr
// max size 1 Mb
$title=$file;//disk name. No sql name because pixlr return this when save

$image=urlencode(api_get_path(WEB_COURSE_PATH).$courseDir.$dir.$file);//TODO: only work with public courses

//
$pixlr_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
$langpixlr  = api_get_language_isocode();
$langpixlr = isset($pixlr_code_translation_table[$langpixlr]) ? $pixlredit_code_translation_table[$langpixlr] : $langpixlr;
$loc=$langpixlr;// deprecated ?? TODO:check pixlr read user browser

$exit_path=api_get_path(WEB_CODE_PATH).'document/exit_pixlr.php';
$_SESSION['exit_pixlr']= Security::remove_XSS($_GET['curdirpath']);
$exit=$exit_path;

$referrer="Chamilo";

$target_path=api_get_path(WEB_CODE_PATH).'document/save_pixlr.php';
$target=$target_path;

$locktarget="true";
$locktitle="false";


echo '<iframe style=\'height: 600px; width: 100%;\' scrolling=\'no\' frameborder=\'0\' src=\'http://pixlr.com/editor/?title='.$title.'&amp;image='.$image.'&amp;loc='.$loc.'&amp;referrer='.$referrer.'&amp;target='.$target.'&amp;exit='.$exit.'&amp;locktarget='.$locktarget.'&amp;locktitle='.$locktitle.'\'>';
echo '</iframe>';

Display::display_footer();
?>