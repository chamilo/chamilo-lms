<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006-2008 Dokeos SPRL
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
require_once ('lib/fe/linkform.class.php');
require_once ('lib/fe/linkaddeditform.class.php');
require_once ('../forum/forumfunction.inc.php');
api_block_anonymous_users();
block_students();

$course_info =isset($_GET['course_code'])? api_get_course_info($_GET['course_code']) : '';
$my_db_course=isset($_GET['course_code'])?$course_info['dbName']:'';

$tbl_forum_thread = Database :: get_course_table(TABLE_FORUM_THREAD,$my_db_course);
$tbl_link=Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$category = Category :: load($_GET['selectcat']);
$typeform = new LinkForm(LinkForm :: TYPE_CREATE,
						$category[0],
						null,
						'create_link',
						null,
						api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
						. '&newtypeselected=' . (isset($_GET['typeselected']) ? Security::remove_XSS($_GET['typeselected']) : '')
						. '&course_code=' . (empty($_GET['course_code'])?'':Security::remove_XSS($_GET['course_code'])))
						;

// if user selected a link type
if ($typeform->validate() && isset($_GET['newtypeselected'])) {
	// reload page, this time with a parameter indicating the selected type
	header('Location: '.api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
											 . '&typeselected='.$typeform->exportValue('select_link')
											 . '&course_code=' . Security::remove_XSS($_GET['course_code']));
	exit;
}

// link type selected, show 2nd form to retrieve the link data
if (isset($_GET['typeselected']) && $_GET['typeselected'] != '0') {
	$addform = new LinkAddEditForm(LinkAddEditForm :: TYPE_ADD,
								   $category[0],
								   intval($_GET['typeselected']),
								   null,
								   'add_link',
								   api_get_self() . '?selectcat=' . $_GET['selectcat']
														. '&typeselected=' . $_GET['typeselected'] . '&course_code=' . $_GET['course_code']);
	if ($addform->validate()) {
		$addvalues = $addform->exportValues();
		$link= LinkFactory :: create($_GET['typeselected']);
		$link->set_user_id(api_get_user_id());
		if($category[0]->get_course_code() == '' && !empty($_GET['course_code'])) {
			$link->set_course_code(Database::escape_string($_GET['course_code']));
			
		} else {
			$link->set_course_code($category[0]->get_course_code());
		}
		$link->set_category_id($category[0]->get_id());
		
		if ($link->needs_name_and_description()) {
			$link->set_name($addvalues['name']);
		} else {
			$link->set_ref_id($addvalues['select_link']);
		}
		$link->set_weight($addvalues['weight']);
		
		if ($link->needs_max()) {
			$link->set_max($addvalues['max']);
		}
		$link->set_date(strtotime($addvalues['date']));
		
		if ($link->needs_name_and_description()) {
			$link->set_description($addvalues['description']);
		}
		$link->set_visible(empty ($addvalues['visible']) ? 0 : 1);
		
		//update view_properties
		$work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);	
		if ( isset($_GET['typeselected']) && 5==$_GET['typeselected'] && (isset($addvalues['select_link']) && $addvalues['select_link']<>"")) {
			$sql1='SELECT thread_title from '.$tbl_forum_thread.' where thread_id='.$addvalues['select_link'].';';
			$res1=api_sql_query($sql1);
			$rowtit=Database::fetch_row($res1);
			$course_id=api_get_course_id();
			$sql_l='SELECT count(*) FROM '.$tbl_link.' WHERE ref_id='.$addvalues['select_link'].' and course_code="'.$course_id.'" and type=5;';
			$res_l=api_sql_query($sql_l);
			$row=Database::fetch_row($res_l);
			
			if ( $row[0]==0 ) {
				$link->add();
				$sql='UPDATE '.$tbl_forum_thread.' set thread_qualify_max='.$addvalues['weight'].',thread_weight='.$addvalues['weight'].',thread_title_qualify="'.$rowtit[0].'" WHERE thread_id='.$addvalues['select_link'].';';
				$sql_l='UPDATE '.$tbl_link.' SET weight='.$addvalues['weight'].' WHERE ref_id='.$addvalues['select_link'].';';
				api_sql_query($sql);
				api_sql_query($sql_l);
			}
			
		} 
		$link->add();
		$addvalue_result=!empty($addvalues['addresult'])?$addvalues['addresult']:array();
		if ($addvalue_result == 1) {
			header('Location: gradebook_add_result.php?selecteval=' . $link->get_ref_id());
			exit;
		} else {
			header('Location: '.$_SESSION['gradebook_dest'].'?linkadded=&selectcat=' . $_GET['selectcat']);
			exit;
		}

	}
}



$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat=' . $_GET['selectcat'],
	'name' => get_lang('Gradebook'
));

Display :: display_header(get_lang('MakeLink'));
if (isset ($typeform)) {
	$typeform->display();
}
if (isset ($addform)) {
	$addform->display();
}
Display :: display_footer();