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
$_SESSION['whereami'] = 'lp/build';
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
	error_log('New LP - User not authorized in lp_build.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

/* learnpath is just created, go get the last id */
$is_new = false;

if($learnpath_id == 0)
{
	$is_new = true;
	
	$sql = "
		SELECT id
		FROM " . $tbl_lp . "
		ORDER BY id DESC
		LIMIT 0, 1";
	$result = api_sql_query($sql);
	$row = Database::fetch_array($result);
	
	$learnpath_id = $row['id'];
}

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

$interbreadcrumb[]= array ("url"=>api_get_self()."?action=build&lp_id=$learnpath_id", "name" => stripslashes("{$therow['name']}"));

//Theme calls
$lp_theme_css=$_SESSION['oLP']->get_theme();
$show_learn_path=true;
Display::display_header(null,'Path');


//api_display_tool_title($therow['name']);

$suredel = trim(get_lang('AreYouSureToDelete'));
//$suredelstep = trim(get_lang('AreYouSureToDeleteSteps'));
?>
<script type='text/javascript'>
/* <![CDATA[ */
function stripslashes(str) {
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}
function confirmation(name)
{
	name=stripslashes(name);	
	if (confirm("<?php echo $suredel; ?> " + name + " ?"))
	{
		return true;
	}
	else
	{
		return false;
	}	
}
</script>
<?php

//echo $admin_output;
/*
-----------------------------------------------------------
	DISPLAY SECTION
-----------------------------------------------------------
*/
echo $_SESSION['oLP']->build_action_menu();
echo '<table cellpadding="0" cellspacing="0" class="lp_build">';
	echo '<tr>';			
		echo '<td class="tree">';		
			//links for adding a module, chapter or step
			echo '<div class="lp_actions">';				
				echo '<p class="lp_action">';	
				echo '</p>';
				echo '<p class="lp_action">';
				echo '</p>';				
			echo '</div>';			
			echo '<div class="lp_tree">';					
				//build the tree with the menu items in it
				echo $_SESSION['oLP']->build_tree();			
			echo '</div>';					
		echo '</td>';
		echo '<td class="workspace">';
		
			if(isset($is_success) && $is_success === true) {
				echo '<div class="confirmation-message">';			
				echo get_lang("ItemRemoved");			
				echo '</div>';
			} else {
				if($is_new) {
					echo '<div class="confirmation-message">';				
						echo get_lang("LearnPathAdded");				
					echo '</div>';
				}				
				if($is_new) {
					Display::display_normal_message(get_lang("LPCreatedToContinue"));
				} else {
					Display::display_normal_message(get_lang('LPCreatedAddChapterStep'));
				}								
			}		
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