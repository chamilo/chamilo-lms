<?php // $Id: 2column.php,v 1.5 2006/08/10 14:34:54 pcool Exp $

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
*                  HOME PAGE FOR EACH COURSE
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
 * "Public", "PublicButHide", "courseAdmin", "claroAdmin"
 */
function show_tools($course_tool_category)
{
	global $charset;
	$web_code_path = api_get_path(WEB_CODE_PATH);
	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);

	switch ($course_tool_category)
	{
		case TOOL_PUBLIC:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE visibility=1 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_PUBLIC_BUT_HIDDEN:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE visibility=0 AND admin=0 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##808080";
				break;

		case TOOL_COURSE_ADMIN:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE admin=1 AND visibility != 2 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_PLATFORM_ADMIN:

				$result = api_sql_query("SELECT * FROM $course_tool_table WHERE visibility = 2 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
	}

	$i=0;

	// grabbing all the tools from $course_tool_table
	while ($temp_row = mysql_fetch_array($result))
	{
		if($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN && $temp_row['image'] != 'scormbuilder.gif')
		{
			$temp_row['image']=str_replace('.gif','_na.gif',$temp_row['image']);
		}
		$all_tools_list[]=$temp_row;
	}

	// grabbing all the links that have the property on_homepage set to 1
	$course_link_table = Database::get_course_table(TABLE_LINK);
	$course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
	switch ($course_tool_category)
	{
		case TOOL_PUBLIC:
			$sql_links="SELECT tl.*, tip.visibility
					FROM $course_link_table tl
					LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
					WHERE tl.on_homepage='1' AND tip.visibility = 1";
			break;
		case TOOL_PUBLIC_BUT_HIDDEN:
			$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
				WHERE tl.on_homepage='1' AND tip.visibility = 0";
			break;
		default:
			$sql_links = null;
			break;
	}
	if( $sql_links != null )
	{
		$properties = array();
		$result_links=api_sql_query($sql_links,__FILE__,__LINE__);
		while ($links_row=mysql_fetch_array($result_links))
		{
			unset($properties);
			$properties['name']=$links_row['title'];
			$properties['link']=$links_row['url'];
			$properties['visibility']=$links_row['visibility'];
			$properties['image']=($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN)?"external_na.gif":"external.gif";
			$properties['adminlink']=api_get_path(WEB_CODE_PATH)."link/link.php?action=editlink&id=".$links_row['id'];
			$all_tools_list[]=$properties;
		}
	}
	
	if (isset($all_tools_list))
	{
		$lnk = array();
		foreach ($all_tools_list as $toolsRow)
		{

			if (!($i%2))
			{
				echo	"<tr valign=\"top\">\n";
			}

			// NOTE : table contains only the image file name, not full path
			if(!stristr($toolsRow['link'],'http://') && !stristr($toolsRow['link'],'https://') && !stristr($toolsRow['link'],'ftp://'))
			{
				$toolsRow['link']=$web_code_path.$toolsRow['link'];
			}
			if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN)
			{
			    $class="class=\"invisible\"";
			}
			$qm_or_amp = ((strpos($toolsRow['link'],'?')===FALSE)?'?':'&amp;');

			$toolsRow['link'] = $toolsRow['link'];
			echo	'<td width="50%" height="30">';
			
			if(strpos($toolsRow['name'],'visio_')!==false)
			{
				echo '<a  '.$class.' href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']).(($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
			}
			
			else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
			{					
				/*
				echo  '<a href="#" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) .(($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()). '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"'.$class.'>';
				*/
				echo  '<a href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']).$qm_or_amp.api_get_cidreq() . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"'.$class.'>';
			}			
			else 
			{
				echo	'<a href="'. htmlspecialchars($toolsRow['link']).(($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()).'" target="' , $toolsRow['target'], '" '.$class.'>';				
			}
					
			/*
			echo Display::return_icon($toolsRow['image'], get_lang(ucfirst($toolsRow['name']))),'&nbsp;', ($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif" || $toolsRow['image']=="scormbuilder.gif" || $toolsRow['image']=="blog.gif") ? htmlspecialchars( $toolsRow['name'],ENT_QUOTES,$charset) : get_lang(ucfirst($toolsRow['name'])),'</a>';
			*/
			if ($toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'file_html_na.gif'
				|| $toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif'
				|| $toolsRow['image'] == 'blog.gif' || $toolsRow['image'] == 'blog_na.gif'
				|| $toolsRow['image'] == 'external.gif' || $toolsRow['image'] == 'external_na.gif')
			{
				$tool_name = htmlspecialchars($toolsRow['name'], ENT_QUOTES, $charset);
			}
			else
			{
				$tool_name = get_lang(ucfirst($toolsRow['name']));
			}
			echo Display::return_icon($toolsRow['image'], $tool_name),'&nbsp;', $tool_name,'</a>';

			// This part displays the links to hide or remove a tool.
			// These links are only visible by the course manager.
			unset($lnk);
			if (api_is_allowed_to_edit())
			{
				if ($toolsRow["visibility"] == '1')
				{
					$link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'));
					$link['cmd'] = "hide=yes";
					$lnk[] = $link;
				}

				if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN)
				{
					$link['name'] = Display::return_icon('add.gif', get_lang('Activate'));
					$link['cmd']  = "restore=yes";
					$lnk[] = $link;

					if($toolsRow["added_tool"] == 1)
					{
						$link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
						$link['cmd']  = "remove=yes";
						$lnk[] = $link;
					}
				}
				if ($toolsRow['adminlink'])
				{
					echo	'<a href="'.$toolsRow['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
					//echo "edit link:".$properties['adminlink'];
				}

			}
			if ( api_is_platform_admin() )
			{
				if ($toolsRow["visibility"]==2)
				{
					$link['name'] = Display::return_icon('undelete.gif', get_lang('Activate'));

					$link['cmd']  = "hide=yes";
					$lnk[] = $link;

					if($toolsRow["added_tool"] == 1)
					{
						$link['name'] = get_lang("Delete");
						$link['cmd'] = "askDelete=yes";
						$lnk[] = $link;
					}
				}

				if ($toolsRow["visibility"] == 0 && $toolsRow["added_tool"] == 0)
				{
					$link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
					$link['cmd'] = "remove=yes";
					$lnk[] = $link;
				}
			}
			if (is_array($lnk))
			{
				foreach($lnk as $this_link)
				{
					if (!$toolsRow['adminlink'])
						{
							echo "<a href=\"" .api_get_self(). "?".api_get_cidreq()."&amp;id=" . $toolsRow["id"] . "&amp;" . $this_link['cmd'] . "\">" .	$this_link['name'] . "</a>";
						}
				}
			}

			// Allow editing of invisible homepage links (modified external_module)
			/*
			if ($toolsRow["added_tool"] == 1 &&
					api_is_allowed_to_edit() && !$toolsRow["visibility"])
			*/
			if ($toolsRow["added_tool"] == 1 && api_is_allowed_to_edit() && !$toolsRow["visibility"]
				&& $toolsRow['image'] != 'scormbuilder.gif' && $toolsRow['image'] != 'scormbuilder_na.gif')
				echo	"<a class=\"nobold\" href=\"" . api_get_path(WEB_PATH) .
						'main/external_module/external_module.php' .
						"?".api_get_cidreq()."&amp;id=".$toolsRow["id"]."\">". get_lang("Edit"). "</a>";

			echo "</td>\n";

			if($i%2)
			{
				echo "</tr>\n";
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
if (api_is_allowed_to_edit())
{
	/*  Work request */

	/*
	-----------------------------------------------------------
		Modify home page
	-----------------------------------------------------------
	*/

	/*
	 * display message to confirm that a tool must be hidden from the list of available tools
	 * (visibility 0,1->2)
	 */

	if($_GET["remove"])
	{
		$msgDestroy=get_lang('DelLk').'<br />';
		$msgDestroy.='<a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;';
		$msgDestroy.='<a href="'.api_get_self().'?destroy=yes&amp;id='.$_GET["id"].'">'.get_lang('Yes').'</a>';

		Display :: display_confirmation_message($msgDestroy);
	}

	/*
	 * Process hiding a tools from available tools.
	 * visibility=2 are only view  by Dokeos Administrator (visibility 0,1->2)
	 */

	elseif ($_GET["destroy"])
	{
		api_sql_query("UPDATE $tool_table SET visibility='2' WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
	}

  	/*
	-----------------------------------------------------------
		HIDE
	-----------------------------------------------------------
	*/
	elseif ($_GET["hide"]) // visibility 1 -> 0
	{
		api_sql_query("UPDATE $tool_table SET visibility=0 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
		Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
	}

    /*
	-----------------------------------------------------------
		REACTIVATE
	-----------------------------------------------------------
	*/
	elseif ($_GET["restore"]) // visibility 0,2 -> 1
	{
		api_sql_query("UPDATE $tool_table SET visibility=1  WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
		Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
	}
}

// work with data post askable by admin of course

if (api_is_platform_admin())
{
	// Show message to confirm that a tools must be hide from available tools
	// visibility 0,1->2
	if($_GET["askDelete"])
	{
		?>
			<div id="toolhide">
			<?php echo get_lang("DelLk"); ?>
			<br />&nbsp;&nbsp;&nbsp;
			<a href="<?php echo api_get_self(); ?>"><?php echo get_lang("No"); ?></a>&nbsp;|&nbsp;
			<a href="<?php echo api_get_self(); ?>?delete=yes&id=<?php echo $_GET["id"]; ?>"><?php echo get_lang("Yes"); ?></a>
			</div>
		<?php
	}

	/*
	 * Process hiding a tools from available tools.
	 * visibility=2 are only view  by Dokeos Administrator visibility 0,1->2
	 */

	elseif (isset($_GET["delete"]) && $_GET["delete"])
	{
		api_sql_query("DELETE FROM $tool_table WHERE id='$id' AND added_tool=1",__FILE__,__LINE__);
	}
}

/*
==============================================================================
		TOOLS VISIBLE FOR EVERYBODY
==============================================================================
*/

echo "<div class=\"everybodyview\">";
echo "<table width=\"100%\">";

show_tools(TOOL_PUBLIC);

echo "</table>";
echo "</div>";


/*
==============================================================================
		COURSE ADMIN ONLY VIEW
==============================================================================
*/

// start of tools for CourseAdmins (teachers/tutors)
if (api_is_allowed_to_edit())
{
	echo	"<div class=\"courseadminview\">";
	echo	"<span class=\"viewcaption\">";
	echo	get_lang("CourseAdminOnly");
	echo	"</span>";
	echo	"<table width=\"100%\">";

	show_tools(TOOL_COURSE_ADMIN);

	/*
	-----------------------------------------------------------
		INACTIVE TOOLS - HIDDEN (GREY) LINKS
	-----------------------------------------------------------
	*/

	echo	"<tr><td colspan=\"4\"><hr style='color:\"#4171B5\"' noshade=\"noshade\" size=\"1\" /></td></tr>\n",

			"<tr>\n",
			"<td colspan=\"4\">\n",
			"<div style=\"margin-bottom: 10px;\"><font color=\"#808080\">\n",get_lang("InLnk"),"</font></div>",
			"</td>\n",
			"</tr>\n";

	show_tools(TOOL_PUBLIC_BUT_HIDDEN);

	echo	"</table>";
	echo	"</div> ";
}

/*
-----------------------------------------------------------
	Tools for platform admin only
-----------------------------------------------------------
*/

if (api_is_platform_admin() && api_is_allowed_to_edit())
{
	?>
		<div class="platformadminview">
		<span class="viewcaption"><?php echo get_lang("PlatformAdminOnly"); ?></span>
		<table width="100%">
		<?php
			show_tools(TOOL_PLATFORM_ADMIN);
		?>
		</table>
		</div>
	<?php
}
?>
