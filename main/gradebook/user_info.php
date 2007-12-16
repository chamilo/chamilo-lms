<?php
// test

// $Id: gradebook_view_result.php 479 2007-04-12 11:50:58Z stijn $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
$language_file = 'gradebook';
$cidReset = true;
include_once ('../inc/global.inc.php');
include_once ('lib/be.inc.php');
include_once ('lib/gradebook_functions.inc.php');
include_once ('lib/fe/userform.class.php');
include_once (api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH) . 'export.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH) . 'import.lib.php');
api_block_anonymous_users();
block_students();

$user = get_user_info_from_id($_GET['userid']);
$form = new UserForm(UserForm :: TYPE_USER_INFO, $user, 'user_info_form', null, api_get_self() . '?userid=' . $_GET['userid'] . '&selecteval=' . $_GET['selecteval']);
if ($form->validate()) {

	header('Location: user_stats.php?selecteval=' . $_GET['selecteval'].'&userid=' . $_GET['userid']);
	exit;

}

$interbreadcrumb[] = array (
	'url' => 'gradebook.php',
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('UserInfo'));
$image = $user['picture_uri'];
$image_file = ($image != '' ? api_get_path(WEB_CODE_PATH)."upload/users/$image" : api_get_path(WEB_CODE_PATH).'img/unknown.jpg');
$image_size = @getimagesize($image_file);

$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.$user_data['lastname'].' '.$user_data['firstname'].'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';

if ($image_size[0] > 300) //limit display width to 300px
	$img_attributes .= 'width="300" ';

echo '<img '.$img_attributes.'/>';
$form->display();
Display :: display_footer();
?>
