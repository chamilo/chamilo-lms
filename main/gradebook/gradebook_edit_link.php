<?php
// $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2007 Stijn Konings, Bert Stepp� (Hogeschool Gent)

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
//$cidReset = true;
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/linkform.class.php');
require_once ('lib/fe/linkaddeditform.class.php');
api_block_anonymous_users();
block_students();

$tbl_forum_thread = Database :: get_course_table(TABLE_FORUM_THREAD);
$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

$linkarray = LinkFactory :: load($_GET['editlink']);
$link = $linkarray[0];

$form = new LinkAddEditForm(LinkAddEditForm :: TYPE_EDIT,
							null,
							null,
							$link,
							'edit_link_form',
							api_get_self() . '?selectcat=' . $_GET['selectcat']
												 . '&editlink=' . $_GET['editlink']);

if ($form->validate()) {
	$values = $form->exportValues();
	$link->set_weight($values['weight']);
	$link->set_date(strtotime($values['date']));
	$link->set_visible(empty ($values['visible']) ? 0 : 1);
	$link->save();
	$sql_t='UPDATE '.$tbl_forum_thread.' SET thread_weight='.$values['weight'].' WHERE thread_id=(SELECT ref_id FROM '.$tbl_grade_links.' where id='.$_GET['editlink'].' and type=5);';
	api_sql_query($sql_t);
	header('Location: '.$_SESSION['gradebook_dest'].'?linkedited=&selectcat=' . $link->get_category_id());
	exit;
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.$_GET['selectcat'],
	'name' => get_lang('Gradebook'
));

Display :: display_header(get_lang('EditLink'));
$form->display();
Display :: display_footer();