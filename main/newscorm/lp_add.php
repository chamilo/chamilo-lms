<?php // $Id: index.php 16620 2008-10-25 20:03:54Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/

/**
==============================================================================
* This is a learning path creation and player tool in Dokeos - previously learnpath_handler.php
*
* @author Patrick Cool
* @author Denes Nagy
* @author Roan Embrechts, refactoring and code cleaning
* @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
* @package dokeos.learnpath
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include('learnpath_functions.inc.php');
//include('../resourcelinker/resourcelinker.inc.php');
include('resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
// name of the language file that needs to be included
$language_file = 'learnpath';

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/
$currentstyle = api_get_setting('stylesheets');
//$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'css/'.$currentstyle.'/learnpath.css"/>';
//$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="learnpath.css" />'; //will be a merged with original learnpath.css
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="dtree.css" />'; //will be moved
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowed_to_edit = api_is_allowed_to_edit(null,true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
// using the resource linker as a tool for adding resources to the learning path
if ($action=="add" and $type=="learnpathitem")
{
	 $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ( (! $is_allowed_to_edit) or ($isStudentView) )
{
	error_log('New LP - User not authorized in lp_add.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result=Database::query($sql_query);
$therow=Database::fetch_array($result);

/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>"#", "name"=> get_lang("_add_learnpath"));

Display::display_header(null,'Path');

echo '<div class="actions">';
echo '<a href="lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('scorm.gif',get_lang('ReturnToLearningPaths')).' '.get_lang('ReturnToLearningPaths').'</a>';
echo '</div>';

Display::display_normal_message(get_lang('AddLpIntro'),false);

if ($_POST AND empty($_REQUEST['learnpath_name']))
{
	Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'), false);
}

echo '<form method="post">';

// form title
echo '<div class="row"><div class="form_header">'.get_lang('AddLpToStart').'</div></div>';

// title field
echo '<div class="row">';
echo '<div class="label">';
echo '<label for="idTitle"><span class="form_required">*</span> '.get_lang('LPName').'</label>';
echo '</div>';
echo '<div class="formw">';
echo '<input id="idTitle" name="learnpath_name" type="text" size="50" />';
echo '</div>';
echo '</div>';

// submit button
echo '<div class="row">';
echo '<div class="label">';
echo '</div>';
echo '<div class="formw">';
echo '<button  class="save" style="width:150px;" type="submit"/>'.get_lang('CreateLearningPath').'</button>';
echo '</div>';
echo '</div>';
echo '<input name="post_time" type="hidden" value="' . time() . '" />';
echo '</form>';

echo '<div class="row">';
echo '<div class="label"></div>';
echo '<div class="formw"><span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small></div>';
echo '</div>';

// footer
Display::display_footer();
?>