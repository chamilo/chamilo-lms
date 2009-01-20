<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
$language_file = 'gradebook';
//$cidReset = true;
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/userform.class.php');
require_once (api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH) . 'export.lib.inc.php');
require_once (api_get_path(LIBRARY_PATH) . 'import.lib.php');
require_once (api_get_path(LIBRARY_PATH). 'usermanager.lib.php');
api_block_anonymous_users();
block_students();

$user = get_user_info_from_id($_GET['userid']);
$form = new UserForm(UserForm :: TYPE_USER_INFO, $user, 'user_info_form', null, api_get_self() . '?userid=' . $_GET['userid'] . '&selectcat=' . $_GET['selectcat']);
if ($form->validate()) {
	header('Location: user_stats.php?selectcat=' . Security::remove_XSS($_GET['selectcat']).'&userid=' .Security::remove_XSS($_GET['userid']));
	exit;
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('UserInfo'));

//User picture size is calculated from SYSTEM path
$image_syspath = UserManager::get_user_picture_path_by_id($userid,'system',false,true);
$image_size = getimagesize($image_syspath['dir'].$image_syspath['file']);
//Web path
$image_path = UserManager::get_user_picture_path_by_id($_user['user_id'],'web',false,true);
$image_file = $image_path['dir'].$image_path['file'];

$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.$user_data['lastname'].' '.$user_data['firstname'].'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';

if ($image_size[0] > 300) {
	//limit display width to 300px
	$img_attributes .= 'width="300" ';
}
echo '<img '.$img_attributes.'/>';
$form->display();
Display :: display_footer();