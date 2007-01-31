<?php // $Id: activity.php,v 1.5 2006/08/10 14:34:54 pcool Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University
	Copyright (c) 2001 Universite Catholique de Louvain
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
/**
==============================================================================
*         HOME PAGE FOR EACH COURSE
*
*	This page, included in every course's index.php is the home
*	page. To make administration simple, the teacher edits his
*	course from the home page. Only the login detects that the
*	visitor is allowed to activate, deactivate home page links,
*	access to the teachers tools (statistics, edit forums...).
*
*	@package dokeos.course_home
==============================================================================
*/



/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
 * Displays the tools of a certain category.
 *
 * @return void
 * @param string $course_tool_category	contains the category of tools to display:
 * "toolauthoring", "toolinteraction", "tooladmin", "tooladminplatform"
 */

function show_tools_category($course_tool_category)
{
	$web_code_path = api_get_path(WEB_CODE_PATH);
	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);

	switch ($course_tool_category)
	{
		case TOOL_STUDENT_VIEW:
				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE visibility = '1' AND (category = 'authoring' OR category = 'interaction') ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_AUTHORING:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE category = 'authoring' ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_INTERACTION:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE category = 'interaction' ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_ADMIN_VISIBLE:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE category = 'admin' AND visibility ='1' ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_ADMIN_PLATEFORM:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE category = 'admin' ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

	}

	while ($temp_row = mysql_fetch_array($result))
	{
		$all_tools_list[]=$temp_row;

	}


		$i=0;
	// grabbing all the links that have the property on_homepage set to 1
	$course_link_table = Database::get_course_table(TABLE_LINK);
	$course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);



	switch ($course_tool_category)
	{
			case TOOL_AUTHORING:
			$sql_links="SELECT tl.*, tip.visibility
					FROM $course_link_table tl
					LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1'";

			break;

			case TOOL_INTERACTION:
			$sql_links = null;
			/*
			$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' ";
			*/
			break;

			case TOOL_STUDENT_VIEW:
				$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1'";
			break;

			case TOOL_ADMIN:
				$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1'";
			break;

		default:
			$sql_links = null;
			break;
	}

	//edit by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
	if($sql_links != null)
	{
		$result_links = api_sql_query($sql_links,__FILE__,__LINE__);

		while($links_row = mysql_fetch_array($result_links))
		{
			unset($properties);

			$properties['name'] = $links_row['title'];
			$properties['link'] = $links_row['url'];
			$properties['visibility'] = $links_row['visibility'];
			$properties['image'] = ($links_row['visibility']== '0') ? "links.gif" : "links.gif";
			$properties['adminlink'] = api_get_path(WEB_CODE_PATH) . "link/link.php?action=editlink&id=".$links_row['id'];

			$tmp_all_tools_list[] = $properties;
		}
	}

	if(isset($tmp_all_tools_list))
	{
		foreach($tmp_all_tools_list as $toolsRow)
		{

			if($toolsRow['image'] == 'blog.gif')
			{
				// Init
				$tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

				// Get blog id
				$blog_id = substr($toolsRow['link'], strrpos($toolsRow['link'], '=') + 1, strlen($toolsRow['link']));

				// Get blog members
				if(api_is_platform_admin())
				{
					$sql_blogs = "
						SELECT *
						FROM " . $tbl_blogs_rel_user . " `blogs_rel_user`
						WHERE `blog_id` = " . $blog_id;
				}
				else
				{
					$sql_blogs = "
						SELECT *
						FROM " . $tbl_blogs_rel_user . " `blogs_rel_user`
						WHERE
							`blog_id` = " . $blog_id . " AND
							`user_id` = " . api_get_user_id();
				}

				$result_blogs = api_sql_query($sql_blogs, __FILE__, __LINE__);

				if(mysql_num_rows($result_blogs) > 0)
					$all_tools_list[] = $toolsRow;
			}
			else
				$all_tools_list[] = $toolsRow;
		}
	}

	if(isset($all_tools_list))
	{
		foreach($all_tools_list as $toolsRow)
		{
			if(!($i%2))
				{echo	"<tr valign=\"top\">\n";}

			// This part displays the links to hide or remove a tool.
			// These links are only visible by the course manager.
			unset($lnk);
			echo '<td width="50%">' . "\n";
			if(api_is_allowed_to_edit())
			{
				if($toolsRow['visibility'] == '1' && $toolsRow['admin'] !='1' && !strpos($toolsRow['link'],'learnpath_handler.php?learnpath_id'))
				{

					$link['name'] = '<img src="'.api_get_path(WEB_CODE_PATH).'img/remove.gif" align="absmiddle" alt="'.get_lang("Deactivate").'"/>';

					$link['cmd'] = "hide=yes";
					$lnk[] = $link;
				}

				if($toolsRow['visibility'] == '0' && $toolsRow['admin'] !='1')
				{
					$link['name'] = '<img src="'.api_get_path(WEB_CODE_PATH).'img/add.gif" align="absmiddle" alt="'.get_lang("Activate").'"/>';
					$link['cmd'] = "restore=yes";
					$lnk[] = $link;
				}

				if($toolsRow['adminlink'])
				{
					echo	'<a href="'.$toolsRow['adminlink'].'"><img src="'.api_get_path(WEB_CODE_PATH).'img/edit.gif" align="absmiddle" alt="'.get_lang("Edit").'"/></a>';
				}

			}

			if( api_is_platform_admin() )
			{
 				if($toolsRow['admin'] !='1')
				{
					$link['cmd'] = "hide=yes";

				}

			}

			if(is_array($lnk))
			{
				foreach($lnk as $this_link)
				{
					if(!$toolsRow['adminlink'])
						{
							echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?".api_get_cidreq()."&amp;id=" . $toolsRow["id"] . "&amp;" . $this_link['cmd'] . "\">" .	$this_link['name'] . "</a>";
						}
				}
			}
			else{ echo '&nbsp;&nbsp;&nbsp;&nbsp;';}

			// NOTE : table contains only the image file name, not full path
			if(!stristr($toolsRow['link'], 'http://') && !stristr($toolsRow['link'], 'https://') && !stristr($toolsRow['link'],'ftp://'))
				$toolsRow['link'] = $web_code_path . $toolsRow['link'];

			if($toolsRow['visibility'] == '0' && $toolsRow['admin'] != '1')
			{
			  	$class="class=\"invisible\"";
			  	$info = pathinfo($toolsRow['image']);
			  	$basename = basename ($toolsRow['image'],'.'.$info['extension']); // $file is set to "index"
				$toolsRow['image'] = $basename.'_na.'.$info['extension'];
			}
 			else
				$class='';

			$qm_or_amp = ((strpos($toolsRow['link'], '?') === FALSE) ? '?' : '&amp;');
				if(strpos($toolsRow['name'],'visio_')!==false){
					echo "\t" . ' &nbsp <a ' . $class . ' href="#" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_visio\',config=\'height=\'+(screen.height)+\', width=\'+(screen.width-20)+\', toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">&nbsp;&nbsp;';
				}
				else {
					echo "\t" . ' &nbsp <a ' . $class . ' href="' . htmlspecialchars($toolsRow['link']) . '" target="' . $toolsRow['target'] . '">&nbsp;&nbsp;';
				}
					echo '<img src="' . $web_code_path . 'img/' . $toolsRow['image'] . '" align="absmiddle" border="0" alt="' . $toolsRow['image'] . '" /> &nbsp;&nbsp;';
					
					echo ($toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif' || $toolsRow['image'] == 'blog.gif' || $toolsRow['image'] == 'blog_na.gif') ? '  '.stripslashes($toolsRow['name']) : '  '.get_lang($toolsRow['name']);
				echo "\t" . '</a>';
				echo '</td>';
			if($i%2)
			{
				echo "</tr>";
			}

			$i++;
		}
	}

	if($i%2)
	{
		echo	"<td width=\"50%\">&nbsp;</td>\n",
				"</tr>\n";
	}

}

//End of functions show tools

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
/*
-----------------------------------------------------------
	Work with data post askable by admin of course (franglais, clean this)
-----------------------------------------------------------
*/
if(api_is_allowed_to_edit())
{
	/* Work request */

	/*
	-----------------------------------------------------------
		Modify home page
	-----------------------------------------------------------
	*/

	/*
	 * display message to confirm that a tool must be hidden from the list of available tools
	 * (visibility 0,1->2)
	 */

	/*if($_GET["remove"])
	{
		$msgDestroy=get_lang('DelLk').'<br />';
		$msgDestroy.='<a href="'.$_SERVER['PHP_SELF'].'">'.get_lang('No').'</a>&nbsp;|&nbsp;';
		$msgDestroy.='<a href="'.$_SERVER['PHP_SELF'].'?destroy=yes&amp;id='.$_GET["id"].'">'.get_lang('Yes').'</a>';

		Display :: display_normal_message($msgDestroy);
	}*/

	/*
	 * Process hiding a tools from available tools.
	 * visibility=2 are only view by Dokeos Administrator (visibility 0,1->2)
	 */

	/*elseif($_GET["destroy"])
	{
		api_sql_query("UPDATE $tool_table SET visibility='2' WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
	}*/



 	/*
	-----------------------------------------------------------
		HIDE
	-----------------------------------------------------------
	*/
	if($_GET["hide"]) // visibility 1 -> 0
	{
		api_sql_query("UPDATE $tool_table SET visibility=0 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
		Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
	}

  /*
	-----------------------------------------------------------
		REACTIVATE
	-----------------------------------------------------------
	*/
	elseif($_GET["restore"]) // visibility 0,2 -> 1
	{
		api_sql_query("UPDATE $tool_table SET visibility=1 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
		Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
	}
}

// work with data post askable by admin of course

if(api_is_platform_admin())
{
	// Show message to confirm that a tools must be hide from available tools
	// visibility 0,1->2
	if($_GET["askDelete"])
	{
		?>
			<div id="toolhide">
			<?php echo get_lang("DelLk")?>
			<br />&nbsp;&nbsp;&nbsp;
			<a href="<?php echo $_SERVER['PHP_SELF']?>"><?php echo get_lang("No")?></a>&nbsp;|&nbsp;
			<a href="<?php echo $_SERVER['PHP_SELF']?>?delete=yes&id=<?php echo $_GET["id"]?>"><?php echo get_lang("Yes")?></a>
			</div>
		<?php
	}

	/*
	 * Process hiding a tools from available tools.
	 */

	elseif(isset($_GET["delete"]) && $_GET["delete"])
	{
		api_sql_query("DELETE FROM $tool_table WHERE id='$id' AND added_tool=1",__FILE__,__LINE__);
	}
}



/*
==============================================================================
		COURSE ADMIN ONLY VIEW
==============================================================================
*/

// start of tools for CourseAdmins (teachers/tutors)
if(api_is_allowed_to_edit() && !api_is_platform_admin())
{
	?>
	<div class="courseadminview">
		<span class="viewcaption"><font size="3" style="color:#FF9900;"><?php echo get_lang("Authoring") ?></font></span>
		<table width="100%">
			<?php show_tools_category(TOOL_AUTHORING);?>
		</table>
	</div>
	<div class="courseadminview">
		<span class="viewcaption"><font size="3" style="color:#FF9900;"><?php echo get_lang("Interaction") ?></font></span>
		<table width="100%">
			<?php show_tools_category(TOOL_INTERACTION) ?>
		</table>
	</div>
	<div class="courseadminview">
		<span class="viewcaption"><font size="3" style="color:#FF9900;"><?php echo get_lang("Administration") ?></font></span>
		<table width="100%">
			<?php show_tools_category(TOOL_ADMIN_PLATEFORM) ?>
		</table>
	</div>
	<?php
}

/*
-----------------------------------------------------------
	Tools for platform admin only
-----------------------------------------------------------
*/

elseif(api_is_platform_admin() || api_is_allowed_to_edit())
{
	?>
	<div class="courseadminview">
		<span class="viewcaption"><font size="3" style="color:#FF9900;"><?php echo get_lang("Authoring") ?></font></span>
		<table width="100%">
			<?php show_tools_category(TOOL_AUTHORING);?>
		</table>
	</div>
	<div class="courseadminview">
		<span class="viewcaption"><font size="3" style="color:#FF9900;"><?php echo get_lang("Interaction") ?></font></span>
		<table width="100%">
			<?php show_tools_category(TOOL_INTERACTION) ?>
		</table>
	</div>
	<div class="courseadminview">
		<span class="viewcaption"><font size="3" style="color:#FF9900;"><?php echo get_lang("Administration") ?></font></span>
		<table width="100%">
			<?php show_tools_category(TOOL_ADMIN_PLATEFORM) ?>
		</table>
	</div>
	<?php
}

/*
==============================================================================
		TOOLS AUTHORING
==============================================================================
*/
else{
?>
	<div class="Authoringview">

		<table width="100%">
			<?php show_tools_category(TOOL_STUDENT_VIEW) ?>
		</table>
	</div>
<?php
}
?>