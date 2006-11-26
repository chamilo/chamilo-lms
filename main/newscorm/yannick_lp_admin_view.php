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
$language_file = "learnpath";

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/ 
$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='../css/learnpath.css' />";

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
	error_log('New LP - User not authorized in lp_admin_view.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id"; 
$result=api_sql_query($sql_query);
$therow=Database::fetch_array($result); 

$admin_output = '';
/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/ 
/*==================================================
			SHOWING THE ADMIN TOOLS
 ==================================================*/

//Preparing the form to add a new module
$add_module_form = '
			<form name="form1" method="post" action="lp_controller">
			<input type="hidden" name="action" 		value="add_item">
			<input type="hidden" name="lp_id" 		value="'.$learnpath_id.'">
			<input type="hidden" name="parent" 		value="0">
			<input type="hidden" name="previous" 	value="-1">
			<input type="hidden" name="type" 		value="dokeos_module">
			<input type="hidden" name="path"		value="">
			<h4>'.get_lang('_add_learnpath_module').'</h4>
			<table width="400" border="0" cellspacing="2" cellpadding="0">
				<tr>
					<td align="right">'.get_lang('_title').'</td>
					<td><input name="title" type="text" value="" size="50"></td>
				</tr>
				<tr>	
					<td align="right">&nbsp;</td>
					<td><input type="submit" name="submit_button" value="'.get_lang('Ok').'"></td>
				</tr>
			</table>
			</form>';

if (($_REQUEST['action']=="add_item" and $type=="dokeos_module" and !$submit))
{
	//this is when the user has selected the "add module" link previously, so we don't display it this time
}
else
{
	//in case the user hasn't selected the "add module" link previously, first quick-check the database to remove the
	//useless step of asking the user to click on "add module" if the only possible action is to add a module
	$sql = "SELECT * FROM $tbl_lp_item WHERE lp_id = $learnpath_id AND item_type = 'dokeos_module'";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if (Database::num_rows($result) <= 0)
	{
		$admin_output .= "<br />There is no main chapter at the moment. Please add one using the following form. This is a required step in the learning path building process.<br />";
		$admin_output .= $add_module_form;
	}else{
		$admin_output .= "<ul>\n";
		$admin_output .= "<li><a href='lp_controller.php?action=add_item&lp_id=$learnpath_id&type=dokeos_module&parent=0&previous=-1&path='>" . get_lang("_add_learnpath_module") . "</a></li>\n";
		$admin_output .= "</ul>\n\n";
	}
}
/*==================================================
	EDITING A LEARNPATH ITEM: showing one of the forms
 ==================================================*/
if ($_REQUEST['action']=="edititem" and !empty($_REQUEST['id']) AND empty($_REQUEST['submit_item']))
{
	error_log('New LP - edit_item action in lp_admin_view',0);
	$id = (int) $_REQUEST['id'];
	$sql="SELECT * FROM $tbl_lp_item WHERE id=$id"; 
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result);
	$id=$row['id'];
	$title = $row['title'];
	if(empty($title)){
		$title = rl_get_resource_name(api_get_course_id(),$learnpath_id,$_REQUEST['id']);
	}
	$admin_output .= '<h4>'.get_lang('_edit_learnpath_item').'</h4>
	<form name="edititem" method="post" action="">
	  <table width="100%" border="0" cellspacing="0" cellpadding="0">
	    <input type="hidden" name="action" value="edititem">
	    <tr>
	      <td align="right" valign="top">'.get_lang('_title').'</td>
	      <td><input name="title" type="text" id="title3" size="50" value="'.$title.'"></td>
        </tr>';
	    //<tr>
	    //  <td align="right" valign="top">'.get_lang('_description').'</td>
	    //  <td><textarea name="description" cols="45" id="textarea">'.$row["description"].'</textarea></td>
        //</tr>
    $admin_output .= '    <tr>
	      <td align="right" valign="top">&nbsp;</td>
	      <td><input name="submit_item" type="submit" id="Submititem" value="'.get_lang('Ok').'"></td>
        </tr>
      </table>
	</form>';
}
/*==================================================
	prerequisites setting start
==================================================*/
if ($_REQUEST['action']=="edititemprereq" and !empty($_REQUEST['id']) AND empty($_REQUEST['submit_item']))
{
	$id = (int) $_REQUEST['id'];
	$sql="SELECT * FROM $tbl_lp_item WHERE id=$id"; 
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result); 
	$id=$row['id'];

	$title = $row['title'];
	if(empty($title)){
		$title = rl_get_resource_name(api_get_course_id(), $learnpath_id, $id);
	}
	$admin_output .= "<h4>";
	$admin_output .= get_lang('_add_prereq')." : ";
	$admin_output .= $title;
	$admin_output .= '</h4>
		<form name="edititemprereq" method="post" action="">
		<input type="hidden" name="action" value="edititemprereq">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
		<td align="right" valign="top"></td>
		<td><table border="0" cellspacing="1" cellpadding="0">';
	//$learnpath_items = learnpath_items($id); the LP object should be available here
	$learnpath_items = learnpath::get_brother_items($id);

	//if there is any test before the current item, we show the completion limit column
	//TODO
	//This section has been made inactive for now because it should use the prerequisite's
	//mastery score as a limit. Also, this script should instead use the new prerequisites
	//language from AICC (see wiki), so it should actually be completely different
	/*
	$teststhere = false;
	for ($i=0; $i<count($learnpath_items); $i++)
	{
		if ((($learnpath_items[$i]["item_type"]==TOOL_QUIZ) 
			or ($learnpath_items[$i]["item_type"]=='HotPotatoes')) 
			and ($row["display_order"] > $learnpath_items[$i]["display_order"]))
		{
			$teststhere = true;
		}
	}
	
	if ($teststhere)
	{
		$admin_output .= "<tr><td colspan='2'></td><td>".get_lang('CompletionLimit')."</td></tr>";
	}
	*/
	$checked='';
	if (empty($row["prerequisite"])) { $checked = 'checked'; }
	$admin_output .= "<tr><td><input class=\"checkbox\" type=\"radio\" name=\"prereq\" value=\"\" $checked></td><td>- ".get_lang('_none')." -</td></tr>";
	
	for ($i=0; $i<count($learnpath_items); $i++)
	{
		if ($row["display_order"] > $learnpath_items[$i]["display_order"])
		{
			/*
			$testrow = false;
			if (($learnpath_items[$i]["item_type"]==TOOL_QUIZ) 
				or ($learnpath_items[$i]["item_type"]=='HotPotatoes'))
			{
				$testrow = true;
			}
			*/
			$checked = '';
			if ($row["prerequisite"]==$learnpath_items[$i]["id"])
			{
				$checked='checked';
			}
			$admin_output .= '<tr><td><input class="checkbox" type="radio" name="prereq" value="'.$learnpath_items[$i]['id'].'" '.$checked.' ></td><td>';
			$admin_output .= rl_get_resource_name(api_get_course_id(), $learnpath_id, $learnpath_items[$i]['id']);
			$admin_output .= "</td>";
			/*
			if ($testrow)
			{
				//if (!$checked) { $disabled='disabled'; }
				$admin_output .= "<td align=center><input type=text name='completion_limit[{$learnpath_items[$i]['id']}]' size=4 maxlength=20 $disabled value=$completion_limit></td>";
			}
			*/
			$admin_output .= "</tr>";
		}
	}
	
	$learnpath_chapters = learnpath::get_brother_chapters($row['parent_item_id']);
	
	$c=$row['parent_item_id']; //we are now in this chapter
	$sql="SELECT * FROM $tbl_lp_item WHERE id=$c AND item_type='dokeos_module'"; 
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row_chapter=Database::fetch_array($result); 

	for ($i=0; $i<count($learnpath_chapters); $i++)
	{
		if ($row_chapter["display_order"] > $learnpath_chapters[$i]["display_order"])
		{
			$checked='';
			if (($row["prereq_id"]==$learnpath_chapters[$i]["id"]) and ($row["prereq_type"]=='c')) { $checked='checked'; }
			$admin_output .= "<tr><td bgcolor='#cccccc'><input type='radio' name='prereq' value='".$learnpath_chapters[$i]["id"]."' $checked ></td><td bgcolor='#cccccc'>".$learnpath_chapters[$i]['title']."</td></tr>";
		}
	}
	$admin_output .= '
	    </table></td>
		</tr>
		<tr>
		<td align="right" valign="top">&nbsp;</td>
		<td><input name="submit_item" type="submit" id="Submititem" value="'.get_lang('Ok').'"></td>
		</tr>
		</table>
		</form>';
}

/*==================================================
	prerequisites setting end
 ==================================================*/
/*==================================================
	EDITING / ADDING A NEW LEARNPATH chapter: showing the form
 ==================================================*/
if (($_REQUEST['action']=="add_item"))
// and $type=="learnpathcategory")  or $action=="editmodule")
{
	if (!$submit)
	{
		/*
		if ($action=="editmodule")
		{
			$sql="SELECT * FROM $tbl_lp_item WHERE (id='$id' and lp_id=$learnpath_id AND item_type='dokeos_chapter')";
			$result=api_sql_query($sql,__FILE__,__LINE__);
			$row=Database::fetch_array($result);
		}
		*/
		$admin_output .= '
			<form name="form1" method="post" action="">
			<h4>';
		if ($action=="add")
			{ $admin_output .= get_lang('_add_learnpath_module'); }
		else
			{ $admin_output .= get_lang('_edit_learnpath_module'); }
		$admin_output .= '
			</h4>
			<table width="400" border="0" cellspacing="2" cellpadding="0">
			<tr>
			<td align="right">'.get_lang('_title').'</td>
			<td><input name="title" type="text" value="'.$row["chapter_name"].'" size="50"></td>
			</tr>';
		if ( $action=='editmodule' )
		{
			// on edition, allow the user to modify the description (if he really wants it)
			$admin_output .= '<tr>
					<td align="right" valign="top">'.get_lang('_description').'</td>'
					.'<td><textarea name="description" cols="45">'.$row["chapter_description"].'</textarea></td>'
				.'</tr>';
		}else
		{
			//on addition, only give a title field, so ignore the description field 
			$admin_output .= "<input type='hidden' name='description' value='' />";
		}
		$admin_output .= '
			<tr>	
			<td align="right">&nbsp;</td>
			<td><input type="submit" name="submit_button" value="'.get_lang('Ok').'"></td>
			</tr>
			</table>
			</form>';
	} // if (!$submit)
} // if ($action=="add" and $type=="learnpathcategory")
			  



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

echo $admin_output;

/*
-----------------------------------------------------------
	DISPLAY SECTION
-----------------------------------------------------------
*/ 
?>
	<table border="0" cellspacing="0" cellpadding="1" width="100%" class="data_table">
	<tr>
	<td colspan='8'>
	</td>
	</tr>
<?php 


display_learnpath_chapters(); 

echo "</table>";

$learnpath_has_chapters = learnpath_chapters($learnpath_id);
if ($learnpath_has_chapters)
{
	echo "<br /><br /><font color='#999999' size='1'>".get_lang('_short_help')."</font>";
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>