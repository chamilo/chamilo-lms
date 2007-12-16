<?php
// $Id: gradebook_add_result.php 328 2007-04-04 14:02:48Z stijn $
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
$this_section = SECTION_MYGRADEBOOK;
include_once ('lib/be.inc.php');
include_once ('lib/fe/displaygradebook.php');
include_once ('lib/gradebook_functions.inc.php');
include_once ('lib/fe/evalform.class.php');
include_once ('lib/scoredisplay.class.php');
api_block_anonymous_users();
block_students();

$evaluation= Evaluation :: load($_GET['selecteval']);
$newstudents = $evaluation[0]->get_not_subscribed_students();
if (count($newstudents) == '0')
{
	header('Location: gradebook_view_result.php?nouser=&selecteval=' . $_GET['selecteval']);
	exit;
}
$add_user_form= new EvalForm(EvalForm :: TYPE_ADD_USERS_TO_EVAL,
							 $evaluation[0],
							 null,
							 'add_users_to_evaluation',
							 null,
							 api_get_self() . '?selecteval=' . $_GET['selecteval'],
							 $_GET['firstletter'],
							 $newstudents);

if ($_POST['submit_button'])
{
	$users= is_array($_POST['add_users']) ? $_POST['add_users'] : array ();
	foreach ($users as $key => $value)
		$users[$key]= intval($value);

	if (count($users) == 0)
	{
		header('Location: ' . api_get_self() . '?erroroneuser=&selecteval=' . $_GET['selecteval']);
		exit;
	}
	else
	{
		foreach ($users as $user_id)
		{
			$result= new Result();
			$result->set_user_id($user_id);
			$result->set_evaluation_id($_GET['selecteval']);
			$result->set_date(time());
			$result->add();
		}
	}
	header('Location: gradebook_view_result.php?adduser=&selecteval=' . $_GET['selecteval']);
	exit;

}
elseif ($_POST['firstLetterUser'])
{
	$firstletter= $_POST['firstLetterUser'];
	if (!empty ($firstletter))
	{
		header('Location: ' . api_get_self() . '?firstletter=' . $firstletter . '&selecteval=' . $_GET['selecteval']);
		exit;
	}
}



$interbreadcrumb[]= array (
	'url' => 'gradebook.php',
	'name' => get_lang('Gradebook'
));
$interbreadcrumb[]= array (
	'url' => 'gradebook_view_result.php?selecteval=' . $_GET['selecteval'],
	'name' => get_lang('ViewResult'
));
Display :: display_header(get_lang('AddUserToEval'));
if (isset ($_GET['erroroneuser']))
{
	Display :: display_warning_message(get_lang('AtLeastOneUser'),false);
}
DisplayGradebook :: display_header_result($evaluation[0], null, 0,0);
echo '<div class="main">';
echo $add_user_form->toHtml();
echo '</div>';
Display :: display_footer();
?>
