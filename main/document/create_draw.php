<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new html documents with an online WYSIWYG html editor.
 *
 *	@package chamilo.document
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/createdraw';
$this_section = SECTION_COURSES;
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
$nameTools = get_lang('CreateDraw');

api_protect_course_script();
api_block_anonymous_users();


/*	Constants and variables */

$dir = isset($_GET['dir']) ? Security::remove_XSS($_GET['dir']) : Security::remove_XSS($_POST['dir']);

/*	MAIN CODE */

if (api_is_in_group()) {
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
}

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


if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != '') {
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
	$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($_GET['dir']).$req_gid, "name" => get_lang('Documents'));


////////////////////////
Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
		echo '<a href="document.php?curdirpath='.Security::remove_XSS($_GET['dir']).'">'.Display::return_icon('back.png',get_lang('Back').' '.get_lang('To').' '.get_lang('DocumentsOverview')).get_lang('BackTo').' '.get_lang('DocumentsOverview').'</a>';
echo '</div>';

echo '<iframe style=\'height: 500px; width: 100%;\' scrolling=\'no\' frameborder=\'0\' src=\''.api_get_path(WEB_LIBRARY_PATH).'svg-edit/svg-editor.php \'>';
echo '</iframe>';

Display :: display_footer();
?>