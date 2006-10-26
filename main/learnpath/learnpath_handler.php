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
* This is a learning path creation and player tool in Dokeos.
*
* @author Patrick Cool
* @author Denes Nagy
* @author Roan Embrechts, refactoring and code cleaning
* @package dokeos.learnpath
* @todo titel and omschrijving are dutch, change to english
============================================================================== 
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 

// setting the language file
$langFile = "learnpath";

include("../inc/global.inc.php");
$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/ 
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include("learnpath_functions.inc.php");
include('../resourcelinker/resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
$langFile = "learnpath";

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

$tbl_learnpath_item     = Database::get_course_table(LEARNPATH_ITEM_TABLE);
$tbl_learnpath_chapter  = Database::get_course_table(LEARNPATH_CHAPTER_TABLE);
$tbl_learnpath_main     = Database::get_course_table(LEARNPATH_MAIN_TABLE);
$tbl_learnpath_user     = Database::get_course_table(LEARNPATH_USER_TABLE);

$learnpath_id   = mysql_real_escape_string($_GET['learnpath_id']);
$chapter_id     = $_GET['chapter_id'];
$titel          = $_POST['titel'];
$omschrijving   = $_POST['omschrijving'];
$Submit         = $_POST['Submit'];
$Submititem     = $_POST['Submititem'];
$action         = $_REQUEST['action'];
$type           = $_REQUEST['type'];
$id             = $_REQUEST['id'];
$direction      = $_REQUEST['direction'];
$moduleid       = $_REQUEST['moduleid'];
$prereq         = $_REQUEST['prereq'];
$type           = $_REQUEST['type'];
$isStudentView  = $_REQUEST['isStudentView'];

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
	$htmlHeadXtra[] =  "<script language='JavaScript' type='text/javascript'> window.location=\"showinframes.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}

$sql_query = "SELECT * FROM $tbl_learnpath_main WHERE learnpath_id = '$learnpath_id'"; 
$result=api_sql_query($sql_query);
$therow=mysql_fetch_array($result); 

if (api_is_allowed_to_edit())
{
	$interbreadcrumb[]= array ("url"=>"../scorm/scormdocument.php", "name"=> get_lang("_learning_path"));

	/*-----------------------------------------------------------
  				SPECIAL REDIRECTION IF NEEDED
	-----------------------------------------------------------*/
	if (($action!="add" or $type!="learnpathcategory"))
	{
		//in case the user hasn't selected the "add module" link previously, first quick-check the database to remove the
		//useless step of asking the user to click on "add module" if the only possible action is to add a module
		$sql = "SELECT * FROM $tbl_learnpath_chapter WHERE learnpath_id = $learnpath_id";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		if (Database::num_rows($result) == 0)
		{
			header('location:../learnpath/learnpath_handler.php?'.api_get_cidreq().'&learnpath_id='.$learnpath_id.'&action=add&type=learnpathcategory');
			exit();
		}
	}//otherwise proceed as usual
}

$interbreadcrumb[]= array ("url"=>$_SERVER['PHP_SELF']."?learnpath_id=$learnpath_id", "name" => "{$therow['learnpath_name']}");

//include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
//event_access_tool($nameTools);
Display::display_header(null,'Path');
api_display_tool_title($therow['learnpath_name']);

?>

<!--table width="100%" border="0" cellspacing="2" cellpadding="4">
<tr>
<td align="left">
<h3><?php //This info is considered useless here //echo get_lang("_learning_path") . "  - {$therow['learnpath_name']}"; ?></h3>

</td>

<td align="right" valign="top">
</td></tr></table!-->
<?php
echo "<script type='text/javascript'>
/* <![CDATA[ */
function confirmation (name)
{
	if (confirm(\" ". get_lang("AreYouSureToDelete") . "\"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
/* ]]> */
</script>";

/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students
-----------------------------------------------------------
*/ 
if(api_is_allowed_to_edit())
{	
	/*-----------------------------------------------------------
		  		DELETE A LEARNPATH chapter
				and all the items in it
 	 -----------------------------------------------------------*/

	if ($action=="deletemodule" and !empty($id))
	{
		deletemodule($id);
		Display :: display_normal_message(get_lang("_learnpath_module_deleted"));
	}

	/*-----------------------------------------------------------
		  		MOVING A LEARNPATH module
 	 -----------------------------------------------------------*/

	if ($action=="movemodule" and !empty($direction) and !empty($id))
	{
		movemodule($direction,$id);
	}

	/*-----------------------------------------------------------
		  		DELETE A LEARNPATH chapter
				and all the items in it
 	 -----------------------------------------------------------*/

	if ($action=="deleteitem" and !empty($id))
	{
		deleteitem($id);
		Display :: display_normal_message(get_lang("_learnpath_item_deleted"));
	}


	/*-----------------------------------------------------------
		  		MOVING A LEARNPATH ITEM
 	 -----------------------------------------------------------*/

	if ($action=="moveitem" and !empty($direction) and !empty($id) and !empty($moduleid) and !empty($type))
	{
		moveitem($direction,$id,$moduleid,$type);
	}

	/*-----------------------------------------------------------
  		EDITING A NEW LEARNPATH ITEM: treating the form
 	 -----------------------------------------------------------*/
	
	if ($Submititem and $id)
	{
		if ($prereq)
		{
			if ($prereq != "none")
			{
				$which=substr($prereq,0,1); //c=chapter, i=item
				$prereq=substr($prereq,1,strlen($prereq));
				$prereq_completion_limit=$completion_limit[$prereq];
				$sql ="UPDATE $tbl_learnpath_item SET prereq_id='$prereq', prereq_type='$which', prereq_completion_limit='$prereq_completion_limit' WHERE id=$id";
			}
			else
			{
				$sql ="UPDATE $tbl_learnpath_item SET prereq_id=NULL, prereq_type=NULL, prereq_completion_limit=NULL WHERE id=$id";
			}
		}
		else
		{ 
			if (($title) or ($description))
			{
				$sql ="UPDATE $tbl_learnpath_item SET title='".domesticate(htmlspecialchars($title))."', description='".domesticate(htmlspecialchars($description))."' WHERE id=$id";
			}
		}
		$result=api_sql_query($sql,__FILE__,__LINE__);
		Display :: display_normal_message(get_lang("_learnpath_item_edited"));
	}
	
	/*-----------------------------------------------------------
  		ADDING A NEW LEARNPATH chapter: treating the form
 	 -----------------------------------------------------------*/

	if ($action=="add" and $type=="learnpathcategory" and $Submit) 
	{
		// getting the last order number of the chapters
		$sql="SELECT * FROM $tbl_learnpath_chapter WHERE learnpath_id=$learnpath_id ORDER BY display_order desc";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=mysql_fetch_array($result);
		$last_order=$row["display_order"];
		$new_order=$last_order+1; 

		$sql ="INSERT INTO $tbl_learnpath_chapter (learnpath_id, chapter_name, chapter_description, display_order) VALUES ('".domesticate($learnpath_id)."', '".domesticate(htmlspecialchars($titel))."','".domesticate(htmlspecialchars($omschrijving))."', '".domesticate($new_order)."')"; 
		$result=api_sql_query($sql,__FILE__,__LINE__); 
		Display :: display_normal_message(get_lang("_learnpath_module_added")); 
	}
		
	/*-----------------------------------------------------------
  		EDITING A NEW LEARNPATH chapter: treating the form
 	 -----------------------------------------------------------*/
	if ($action=="editmodule" and $Submit) 
	{
		$sql ="UPDATE $tbl_learnpath_chapter SET chapter_name='".domesticate(htmlspecialchars($titel))."', chapter_description='".domesticate(htmlspecialchars($omschrijving))."' WHERE (id=$id and learnpath_id=$learnpath_id)"; 
		$result=api_sql_query($sql,__FILE__,__LINE__); 
		Display :: display_normal_message(get_lang("_learnpath_module_edited")); 
	}


	/*==================================================
  				SHOWING THE ADMIN TOOLS
 	 ==================================================*/
	if (($action=="add" and $type=="learnpathcategory" and !$Submit))
	{
		//this is when the user has selected the "add module" link previously, so we don't display it this time
	}
	else
	{
		//in case the user hasn't selected the "add module" link previously, first quick-check the database to remove the
		//useless step of asking the user to click on "add module" if the only possible action is to add a module
		$sql = "SELECT * FROM $tbl_learnpath_chapter WHERE learnpath_id = $learnpath_id";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		if (Database::num_rows($result) > 0)
		{
			echo "<ul>\n";
			echo "<li><a href='".$_SERVER['PHP_SELF']."?".api_get_cidreq()."&learnpath_id=$learnpath_id&amp;action=add&amp;type=learnpathcategory'>" . get_lang("_add_learnpath_module") . "</a></li>\n";
			echo "</ul>\n\n";
		}
		else
		{
			header('location:/learnpath/learnpath_handler.php?'.api_get_cidreq().'&learnpath_id='.$learnpath_id.'&action=add&type=learnpathcategory');
			exit();
		}
	}
	
	/*==================================================
  		EDITING A LEARNPATH ITEM: showing one of the forms
 	 ==================================================*/
	if ($action=="edititem" and $id and !$Submititem)
	{
		?>
		<h4><?php echo get_lang('_edit_learnpath_item'); ?></h4>
		<form name="edititem" method="post" action="">
		  <table width="100%" border="0" cellspacing="0" cellpadding="0">
		    <tr>
		      <td align="right"></td>
		      <td>
			  <?php 
			  $sql="SELECT* FROM $tbl_learnpath_item WHERE id=$id"; 
			  $result=api_sql_query($sql,__FILE__,__LINE__);
			  $row=mysql_fetch_array($result);
			  $id=$row['id'];
			  display_addedresource_link_in_learnpath($row["item_type"],$row["item_id"],'', $id,'builder','icon');
			  ?>
			  </td>
	        </tr>
		    <tr>
		      <td align="right" valign="top"><?php echo get_lang('_title');?></td>
		      <td><input name="title" type="text" id="title3" size="50" value=<?php echo "\"${row["title"]}\""; ?>></td>
	        </tr>
		    <tr>
		      <td align="right" valign="top"><?php echo get_lang('_description');?></td>
		      <td><textarea name="description" cols="45" id="textarea"><?php echo $row["description"]; ?></textarea></td>
	        </tr>
		    <tr>
		      <td align="right" valign="top">&nbsp;</td>
		      <td><input name="Submititem" type="submit" id="Submititem" value="<?php echo get_lang('Ok'); ?>"></td>
	        </tr>
	      </table>
		</form>
		<?php
	}
	
	/*==================================================
		prerequisities setting start
	==================================================*/
	
	if ($action=="edititemprereq" and $id and !$Submititem)
	{
		echo "<h4>";
		$sql="SELECT * FROM $tbl_learnpath_item WHERE id=$id"; 
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=mysql_fetch_array($result); 
		$id=$row['id'];
		echo get_lang('_add_prereq')." : ";
		display_addedresource_link_in_learnpath($row["item_type"], $row["item_id"], '', $id, 'builder', 'icon');
		echo "</h4>";
		?>
			<form name="edititemprereq" method="post" action="">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td align="right" valign="top"></td>
			<td><table border="0" cellspacing="1" cellpadding="0">
		<?php
		$learnpath_items = learnpath_items($id);

		//if there is any test before the current item, we show the completion limit column
		$teststhere = false;
		for ($i=0; $i<count($learnpath_items); $i++)
		{
			if ((($learnpath_items[$i]["item_type"]=='Exercise') or ($learnpath_items[$i]["item_type"]=='HotPotatoes')) and ($row["display_order"] > $learnpath_items[$i]["display_order"]))
			{
				$teststhere = true;
			}
		}
		
		if ($teststhere)
		{
			echo "<tr><td colspan='2'></td><td>".get_lang('CompletionLimit')."</td></tr>";
		}

		$checked='';
		if ($row["prereq_id"]==NULL) { $checked = 'checked'; }
		echo "<tr><td><input class=\"checkbox\" type=\"radio\" name=\"prereq\" value=\"none\" $checked></td><td>- ".get_lang('_none')." -</td></tr>";
		
		for ($i=0; $i<count($learnpath_items); $i++)
		{
			if ($row["display_order"] > $learnpath_items[$i]["display_order"])
			{
				$testrow = false;
				if (($learnpath_items[$i]["item_type"]=='Exercise') or ($learnpath_items[$i]["item_type"]=='HotPotatoes'))
				{
					$testrow = true;
				}
				$checked = '';
				if (($row["prereq_id"]==$learnpath_items[$i]["id"]) and ($row["prereq_type"]=='i'))
				{
					$checked='checked';
					$completion_limit=$row['prereq_completion_limit'];
				}
				echo '<tr><td><input class="checkbox" type="radio" name="prereq" value="i'.$learnpath_items[$i]['id'].'" '.$checked.' ></td><td>';
				display_addedresource_link_in_learnpath($learnpath_items[$i]["item_type"],$learnpath_items[$i]["item_id"],'',$learnpath_items[$i]["id"],'builder','icon');
				echo "</td>";
				if ($testrow)
				{
					//if (!$checked) { $disabled='disabled'; }
					echo "<td align=center><input type=text name='completion_limit[{$learnpath_items[$i]['id']}]' size=4 maxlength=20 $disabled value=$completion_limit></td>";
				}
				echo "</tr>";
			}
		}
		
		$sql="SELECT * FROM $tbl_learnpath_chapter WHERE learnpath_id=$learnpath_id"; 
		//we now have to list only those chapters which are before our selected item
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row_chapter=mysql_fetch_array($result); 
		$learnpath_chapters=learnpath_chapters($learnpath_id);
		
		$c=$row["chapter_id"]; //we are now in this chapter
		$sql="SELECT * FROM $tbl_learnpath_chapter WHERE id=$c"; 
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row_chapter=mysql_fetch_array($result); 

		for ($i=0; $i<count($learnpath_chapters); $i++)
		{
			if ($row_chapter["display_order"] > $learnpath_chapters[$i]["display_order"])
			{
				$checked='';
				if (($row["prereq_id"]==$learnpath_chapters[$i]["id"]) and ($row["prereq_type"]=='c')) { $checked='checked'; }
				echo "<tr><td bgcolor='#cccccc'><input type='radio' name='prereq' value='c{$learnpath_chapters[$i]["id"]}' $checked ></td><td bgcolor='#cccccc'>{$learnpath_chapters[$i]['chapter_name']}</td></tr>";
			}
		}
		?>
			</table></td>
			</tr>
			<tr>
			<td align="right" valign="top">&nbsp;</td>
			<td><input name="Submititem" type="submit" id="Submititem" value="<?php echo get_lang('Ok'); ?>"></td>
			</tr>
			</table>
			</form>
		<?php 
	}
				
	/*==================================================
		prerequisities setting end
		==================================================*/
				  
	/*==================================================
  		EDITING / ADDING A NEW LEARNPATH chapter: showing the form
 	 ==================================================*/
	if (($action=="add" and $type=="learnpathcategory")  or $action=="editmodule")
	{
		if (!$Submit)
		{
			if ($action=="editmodule")
			{
				$sql="SELECT * FROM $tbl_learnpath_chapter WHERE (id='$id' and learnpath_id=$learnpath_id)";
				$result=api_sql_query($sql,__FILE__,__LINE__);
				$row=mysql_fetch_array($result);
			}
			?>
				<form name="form1" method="post" action="">
				<h4>
			<?php 
			if ($action=="add")
				{ echo get_lang('_add_learnpath_module'); }
			else
				{ echo get_lang('_edit_learnpath_module'); }
			?>
				</h4>
				<table width="400" border="0" cellspacing="2" cellpadding="0">
				<tr>
				<td align="right"><?php echo get_lang('_title');?></td>
				<td><input name="titel" type="text" value="<?php echo $row["chapter_name"];?>" size="50"></td>
				</tr>
			<?php
			if ( $action=='editmodule' )
			{
				// on edition, allow the user to modify the description (if he really wants it)
				echo '<tr>
						<td align="right" valign="top">'.get_lang('_description').'</td>'
						.'<td><textarea name="omschrijving" cols="45">'.$row["chapter_description"].'</textarea></td>'
					.'</tr>';
			}else
			{
				//on addition, only give a title field, so ignore the description field 
				echo "<input type='hidden' name='omschrijving' value='' />";
			}
			?>
				<tr>	
				<td align="right">&nbsp;</td>
				<td><input type="submit" name="Submit" value="<?php echo get_lang('Ok'); ?>"></td>
				</tr>
				</table>
				</form>
			<?php 
		} // if (!$submit)
	} // if ($action=="add" and $type=="learnpathcategory")
} // if($is_allowed_to_edit)

echo "<hr size=\"1\" />";

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