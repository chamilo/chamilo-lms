<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Patrick Cool
	Copyright (c) Denes Nagy
	Copyright (c) Yannick Warnier
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
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
$language_file = "learnpath";

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/ 
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='../css/learnpath.css' />";
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='learnpath.css' />"; //will be a merged with original learnpath.css
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='dtree.css' />"; //will be moved
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/ 
$is_allowed_to_edit = api_is_allowed_to_edit();

$tbl_lp = Database::get_course_table('lp');
$tbl_lp_item = Database::get_course_table('lp_item');
$tbl_lp_view = Database::get_course_table('lp_view');

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];
/*
$chapter_id     = $_GET['chapter_id'];
$title          = $_POST['title'];
$description   = $_POST['description'];
$Submititem     = $_POST['Submititem'];
$action         = $_REQUEST['action'];
$id             = (int) $_REQUEST['id'];
$type           = $_REQUEST['type'];
$direction      = $_REQUEST['direction'];
$moduleid       = $_REQUEST['moduleid'];
$prereq         = $_REQUEST['prereq'];
$type           = $_REQUEST['type'];
*/
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
$result=api_sql_query($sql_query);
$therow=Database::fetch_array($result); 

//$admin_output = '';
/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/ 
/*==================================================
			SHOWING THE ADMIN TOOLS
 ==================================================*/



/*==================================================
	prerequisites setting end
 ==================================================*/		  

$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>"#", "name"=> get_lang("_add_learnpath"));

Display::display_header(null,'Path');
//api_display_tool_title($therow['name']);


//echo $admin_output;
/*
-----------------------------------------------------------
	DISPLAY SECTION
-----------------------------------------------------------
*/
echo '<table cellpadding="0" cellspacing="0" class="lp_build">';

	echo '<tr>';
			
		echo '<td class="tree">';
		
			echo '<p style="border-bottom:1px solid #999999; margin:0; padding:2px;"><strong style="color:#999999">'.get_lang("BasicOverview").'</strong>&nbsp;<strong style="color:#999999">'.get_lang("Display").'</strong></p>';
			
			//links for adding a module, chapter or step
			echo '<div class="lp_actions">';
				
				echo '<p class="lp_action">';
				
					echo '<img align="left" alt="'.get_lang("NewChapter").'" src="../img/lp_dokeos_chapter_add.png" title="'.get_lang("NewChapter").'" /><strong style="color:#999999">'.get_lang("NewChapter").'</strong>';
						
				echo '</p>';
				echo '<p class="lp_action">';
				
					echo '<img align="left" alt="'.get_lang("NewStep").'" src="../img/lp_dokeos_step_add.png" title="'.get_lang("NewStep").'" /><strong style="color:#999999">'.get_lang("NewStep").'</strong>';
				
				echo '</p>';
				
			echo '</div>';
					
		echo '</td>';
		echo '<td class="workspace">';

			Display::display_normal_message(get_lang('AddLpIntro'),false);
			
			echo '<div style="background:#F8F8F8; border:1px solid #999999; margin:15px auto; padding:10px; width:400px;">';
				 
				echo '<p style="font-weight:bold">'.get_lang('AddLpToStart').' :</p>';
				
				echo '<form method="post">';
				
					echo '<label for="idTitle" style="margin-right:10px;">'.get_lang('Title').' :</label><input id="idTitle" name="learnpath_name" type="text" class="input_titles" />';
					echo '<p><input style="background:#FFFFFF; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:75px;" type="submit" value="'.get_lang('Ok').'" /></p>';
					echo '<input name="post_time" type="hidden" value="' . time() . '" />';
			
				echo '</form>';
			
			echo '</div>';
		
		echo '</td>';
			
	echo '</tr>';
		
echo '</table>';



/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>