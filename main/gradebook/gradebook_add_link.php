<?php
// $Id: gradebook_add_link.php 338 2007-04-06 14:02:05Z stijn $
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
$cidReset = true;
include_once ('../inc/global.inc.php');
include_once ('lib/be.inc.php');
include_once ('lib/gradebook_functions.inc.php');
include_once ('lib/fe/linkform.class.php');
include_once ('lib/fe/linkaddeditform.class.php');
api_block_anonymous_users();
block_students();

$category = Category :: load($_GET['selectcat']);

$typeform = new LinkForm(LinkForm :: TYPE_CREATE,
						$category[0],
						null,
						'create_link',
						null,
						api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
											 . '&newtypeselected=',
						isset($_GET['typeselected']) ? Security::remove_XSS($_GET['typeselected']) : null);

// if user selected a link type
if ($typeform->validate() && isset($_GET['newtypeselected']))
{
	// reload page, this time with a parameter indicating the selected type
	header('Location: '.api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
											 . '&typeselected='.$typeform->exportValue('select_link'));
	exit;
}

// link type selected, show 2nd form to retrieve the link data
if (isset($_GET['typeselected']) && $_GET['typeselected'] != '0')
{
	$addform = new LinkAddEditForm(LinkAddEditForm :: TYPE_ADD,
								   $category[0],
								   intval($_GET['typeselected']),
								   null,
								   'add_link',
								   api_get_self() . '?selectcat=' . $_GET['selectcat']
														. '&typeselected=' . $_GET['typeselected']);
	if ($addform->validate())
	{
		$addvalues = $addform->exportValues();

		$link= LinkFactory :: create($_GET['typeselected']);
		$link->set_user_id(api_get_user_id());
		$link->set_course_code($category[0]->get_course_code());
		$link->set_category_id($category[0]->get_id());
		
		if ($link->needs_name_and_description())
			$link->set_name($addvalues['name']);
		else
			$link->set_ref_id($addvalues['select_link']);

		$link->set_weight($addvalues['weight']);
		
		if ($link->needs_max())
			$link->set_max($addvalues['max']);
		
		$link->set_date(strtotime($addvalues['date']));
		
		if ($link->needs_name_and_description())
			$link->set_description($addvalues['description']);
		
		$link->set_visible(empty ($addvalues['visible']) ? 0 : 1);
		
		$link->add();


		if ($addvalues['addresult'] == 1)
		{
			header('Location: gradebook_add_result.php?selecteval=' . $link->get_ref_id());
			exit;
		}
		else
		{
			header('Location: gradebook.php?linkadded=&selectcat=' . $_GET['selectcat']);
			exit;
		}

	}
}



$interbreadcrumb[]= array (
	'url' => 'gradebook.php?selectcat=' . $_GET['selectcat'],
	'name' => get_lang('Gradebook'
));

Display :: display_header(get_lang('MakeLink'));
if (isset ($typeform))
	$typeform->display();
if (isset ($addform))
	$addform->display();
Display :: display_footer();
?>
