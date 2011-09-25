<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'import.lib.php';
api_block_anonymous_users();

if (isset($_GET['userid'])) {
	$user_id = Security::remove_XSS($_GET['userid']);
	$user = Usermanager::get_user_info_by_id($user_id);
	if (!$user) {
		api_not_allowed();
	}
} else {
	api_not_allowed();
}

require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/userform.class.php';
block_students();

$form = new UserForm(UserForm :: TYPE_USER_INFO, $user, 'user_info_form', null, api_get_self() . '?userid=' . $user_id . '&selectcat=' . Security::remove_XSS($_GET['selectcat']));
if ($form->validate()) {
	header('Location: user_stats.php?selectcat=' . Security::remove_XSS($_GET['selectcat']).'&userid=' .$user_id);
	exit;
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('UserInfo'));

//User picture size is calculated from SYSTEM path
$image_syspath = UserManager::get_user_picture_path_by_id($user_id,'system',false,true);
$image_size = getimagesize($image_syspath['dir'].$image_syspath['file']);
//Web path
$image_path = UserManager::get_user_picture_path_by_id($user_id,'web',false,true);
$image_file = $image_path['dir'].$image_path['file'];

$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.api_get_person_name($user_data['firstname'], $user_data['lastname']).'" '
	.'style="float:left; padding:5px;" ';

if ($image_size[0] > 300) {
	//limit display width to 300px
	$img_attributes .= 'width="300" ';
}
//@todo need a "makeup"
echo '<img '.$img_attributes.'/>';
$form->display();
Display :: display_footer();
