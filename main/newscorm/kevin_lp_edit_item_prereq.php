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

// setting the language file
//$langFile = "learnpath";

//require_once('back_compat.inc.php');
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
$langFile = "learnpath";

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/ 
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='../css/learnpath.css' />";
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='learnpath.css' />"; //will be a merged with original learnpath.css
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='dtree.css' />"; //will be moved
$htmlHeadXtra[] = "<script src='dtree.js' type='text/javascript'></script>"; //will be moved
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
	error_log('New LP - User not authorized in lp_edit_item_prereq.php');
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

$interbreadcrumb[]= array ("url"=>$_SERVER['PHP_SELF']."?action=admin_view&lp_id=$learnpath_id", "name" => stripslashes("{$therow['name']}"));

Display::display_header(null,'Path');
//api_display_tool_title($therow['name']);

$suredel = get_lang('AreYouSureToDelete');
$suredelstep = get_lang('AreYouSureToDeleteSteps');
?>
<script type='text/javascript'>
/* <![CDATA[ */
function confirmation (name)
{
	if (name!='Users' && name!='Assignments' && name!='Document' && name!='Forum' && name!='Agenda' && name!='Groups' && name!='Link _self'  && name!='Dropbox' && name!='Course_description' && name!='Exercise' && name!='Introduction_text')
	{ 
		if (confirm("<?php echo $suredel; ?> "+ name + " <?php echo $suredelstep;?>?"))
			{return true;}
		else
			{return false;}
	}
	else
	{
		if (confirm("<?php echo $suredel; ?> "+ name + "?"))
			{return true;}
		else
			{return false;}
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
echo '<table cellpadding="0" cellspacing="0" class="lp_build">';

	echo '<tr>';
			
		echo '<td class="tree">';
		
			echo '<p style="border-bottom:1px solid #999999; margin:0; padding:2px;"><a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=admin_view&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">Basic overview</a></p>';
			
			//links for adding a module, chapter or step
			echo '<div class="lp_actions">';
				
				echo '<p class="lp_action">';
				
					echo '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=module&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="New module"><img align="left" alt="New module" src="img/lp_dokeos_module_add.png" title="New module" />New module</a>';
				
				echo '</p>';
				echo '<p class="lp_action">';
				
					echo '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=chapter&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="New chapter"><img align="left" alt="New chapter" src="img/lp_dokeos_chapter_add.png" title="New chapter" />New chapter</a>';
						
				echo '</p>';
				echo '<p class="lp_action">';
				
					echo '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="New step"><img align="left" alt="New step" src="img/lp_dokeos_step_add.png" title="New step" />New step</a>';
				
				echo '</p>';
				
			echo '</div>';
			
			echo '<div class="lp_tree">';
					
				//build the tree with the menu items in it
				echo $_SESSION['oLP']->build_tree();
			
			echo '</div>';
					
		echo '</td>';
		echo '<td class="workspace">';
		
			if(isset($is_success) && $is_success === true)
			{
				echo '<div class="lp_message" style="margin:3px 10px;">';
			
					echo 'Prerequisites to the current step have been added.';
			
				echo '</div>';
			}
			else
			{
				echo $_SESSION['oLP']->display_item_prerequisites_form($_GET['id']);
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