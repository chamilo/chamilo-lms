<?php
/* For licensing terms, see /license.txt */
/**
 *	This file allows creating audio files from a text.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 30/January/2011
 * @todo clean all file
*/
/**
 * Code
 */

/*	INIT SECTION */
$language_file = array('document');

require_once '../inc/global.inc.php';
$_SESSION['whereami'] = 'document/createpaint';
$this_section = SECTION_COURSES;

require_once 'document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('PhotoRetouching');

api_protect_course_script();
api_block_anonymous_users();
if (api_get_setting('enabled_support_paint') == 'false') {
	api_not_allowed(true);
}

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id());
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties   = GroupManager::get_group_properties(api_get_group_id());        
        $document_id        = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data      = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
    }
}

$document_id = $document_data['id'];
$dir         = $document_data['path'];

//$dir = isset($_GET['dir']) ? Security::remove_XSS($_GET['dir']) : Security::remove_XSS($_POST['dir']);
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

//path for pixlr save
$_SESSION['paint_dir']=Security::remove_XSS($dir);
if ($_SESSION['paint_dir']=='/'){
	$_SESSION['paint_dir']='';
}
$_SESSION['paint_file']=get_lang('NewImage');

// Please, do not modify this dirname formatting

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

//groups //TODO: clean
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['_gid'], "name" => get_lang('GroupSpace'));
		$noPHP_SELF = true;
		$to_group_id = $_SESSION['_gid'];
		$group = GroupManager :: get_group_properties($to_group_id);
		$path = explode('/', $dir);
		if ('/'.$path[1] != $group['directory']) {
			api_not_allowed(true);
		}
}

$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($dir).$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}

if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder($_user['user_id'], Security::remove_XSS($dir),api_get_session_id()))) {
	api_not_allowed(true);
}


/*	Header */
event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset ($group)) {
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
	// Copied from document.php
	$dir_array = explode('/', $dir);
	$array_len = count($dir_array);
	$dir_acum = '';
	for ($i = 0; $i < $array_len; $i++) {
		$url_dir = 'document.php?&curdirpath='.$dir_acum.$dir_array[$i];
		//Max char 80
		$url_to_who = cut($dir_array[$i],80);
		if ($is_certificate_mode) {
			$interbreadcrumb[] = array('url' => $url_dir.'&selectcat='.Security::remove_XSS($_GET['selectcat']), 'name' => $url_to_who);
		} else {
			$interbreadcrumb[] = array('url' => $url_dir, 'name' => $url_to_who);
		}
		$dir_acum .= $dir_array[$i].'/';
	}
//
Display :: display_header($nameTools, 'Doc');

echo '<div class="actions">';
		echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
echo '</div>';

///pixlr
// max size 1 Mb
$title=urlencode(utf8_encode(get_lang('NewImage')));//TODO:check
//
$image=api_get_path(WEB_IMG_PATH).'canvas1024x768.png';
//
$pixlr_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
$langpixlr  = api_get_language_isocode();
$langpixlr = isset($pixlr_code_translation_table[$langpixlr]) ? $pixlredit_code_translation_table[$langpixlr] : $langpixlr;
$loc=$langpixlr;// deprecated ?? TODO:check pixlr read user browser

$exit_path=api_get_path(WEB_CODE_PATH).'document/exit_pixlr.php';
$_SESSION['exit_pixlr']= Security::remove_XSS($dir);
$referrer="Chamilo";
$target_path=api_get_path(WEB_CODE_PATH).'document/save_pixlr.php';
$target=$target_path;
$locktarget="true";
$locktitle="false";
echo '<iframe style=\'height: 600px; width: 100%;\' scrolling=\'no\' frameborder=\'0\' src=\'http://pixlr.com/editor/?title='.$title.'&amp;image='.$image.'&amp;loc='.$loc.'&amp;referrer='.$referrer.'&amp;target='.$target.'&amp;exit='.$exit_path.'&amp;locktarget='.$locktarget.'&amp;locktitle='.$locktitle.'\'>';
echo '</iframe>';
