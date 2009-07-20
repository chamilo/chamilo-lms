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
require_once '../../main/inc/global.inc.php';
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
	$is_allowed_to_edit = api_is_allowed_to_edit();
	$is_platform_admin = api_is_platform_admin();

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

	while ($temp_row = Database::fetch_array($result))
	{
		$all_tools_list[]=$temp_row;
	}
	
	/*if(api_is_course_coach())
	{
		$result = api_sql_query("SELECT * FROM $course_tool_table WHERE name='tracking'",__FILE__,__LINE__);
		$all_tools_list[]=Database :: fetch_array($result);
	}*/

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
		$properties = array();
		while($links_row = Database::fetch_array($result_links))
		{
			unset($properties);

			$properties['name'] = $links_row['title'];
			$properties['link'] = $links_row['url'];
			$properties['visibility'] = $links_row['visibility'];
			$properties['image'] = ($links_row['visibility']== '0') ? "file_html.gif" : "file_html.gif";
			$properties['adminlink'] = api_get_path(WEB_CODE_PATH) . "link/link.php?action=editlink&id=".$links_row['id'];
			$properties['target'] = $links_row['target'];
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
				if($is_platform_admin)
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

				if(Database::num_rows($result_blogs) > 0)
					$all_tools_list[] = $toolsRow;
			}
			else
				$all_tools_list[] = $toolsRow;
		}
	}

	if(isset($all_tools_list))
	{
		$lnk = '';
		foreach($all_tools_list as $toolsRow)
		{
			if(!($i%2))
				{echo	"<tr valign=\"top\">\n";}

			// This part displays the links to hide or remove a tool.
			// These links are only visible by the course manager.
			unset($lnk);
			echo '<td width="50%">' . "\n";
			if($is_allowed_to_edit)
			{
				if($toolsRow['visibility'] == '1' && $toolsRow['admin'] !='1')
				{

					$link['name'] = Display::return_icon('visible.gif', get_lang('Deactivate'),array('id'=>'linktool_'.$toolsRow["id"]));

					$link['cmd'] = "hide=yes";
					$lnk[] = $link;
				}

				if($toolsRow['visibility'] == '0' && $toolsRow['admin'] !='1')
				{
					$link['name'] = Display::return_icon('invisible.gif', get_lang('Activate'),array('id'=>'linktool_'.$toolsRow["id"]));
					$link['cmd'] = "restore=yes";
					$lnk[] = $link;
				}

				if(!empty($toolsRow['adminlink']))
				{
					echo	'<a href="'.$toolsRow['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
				}

			}
			
			// Both checks are necessary as is_platform_admin doesn't take student view into account
			if( $is_platform_admin && $is_allowed_to_edit) 
			{
 				if($toolsRow['admin'] !='1')
				{
					$link['cmd'] = "hide=yes";

				}

			}

			if(isset($lnk) && is_array($lnk))
			{
				foreach($lnk as $this_link)
				{
					if(empty($toolsRow['adminlink']))
					{
							echo "<a class=\"make_visible_and_invisible\"  href=\"" .api_get_self(). "?".api_get_cidreq()."&amp;id=" . $toolsRow["id"] . "&amp;" . $this_link['cmd'] . "\">" .	$this_link['name'] . "</a>";
							//echo "<a  class=\"make_visible_and_invisible\" href=\"javascript:void(0)\" >" .	$this_link['name'] . "</a>";

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
			//If it's a link, we don't add the cidReq
			if($toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'file_html_na.gif'){
				$toolsRow['link'] = $toolsRow['link'].$qm_or_amp;
			}
			else{
				$toolsRow['link'] = $toolsRow['link'].$qm_or_amp.api_get_cidreq();
			}
				if(strpos($toolsRow['name'],'visio_')!==false) {
					
					/*
					$toollink = "\t" . '<a ' . $class . ' href="#" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_visio\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
					*/
	
					$toollink = "\t" . '<a id="tooldesc_'.$toolsRow["id"].'"  ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
					$my_tool_link = "\t" . '<a id="istooldesc_'.$toolsRow["id"].'"  ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
				} else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true) {
					/*
					$toollink = "\t" . '<a ' . $class . ' href="#" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
					*/
					$toollink = "\t" . '<a id="tooldesc_'.$toolsRow["id"].'" ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
					$my_tool_link="\t" . '<a id="istooldesc_'.$toolsRow["id"].'" ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';

				} else {

					if (count(explode('type=classroom',$toolsRow['link']))==2 || count(explode('type=conference',$toolsRow['link']))==2) {					
						//$toollink = "\t" . '<a ' . $class . ' href="' . $toolsRow['link'] . '" target="_blank">';	
						$toollink = "\t" . '<a id="tooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . $toolsRow['link'] . '" target="_blank">';													
						$my_tool_link = "\t" . '<a id="istooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . $toolsRow['link'] . '" target="_blank">';							
					
					} else {					
						//$toollink = "\t" . '<a ' . $class . ' href="' . htmlspecialchars($toolsRow['link']) . '" target="' . $toolsRow['target'] . '">';	
						$toollink = "\t" . '<a id="tooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . htmlspecialchars($toolsRow['link']) . '" target="' . $toolsRow['target'] . '">';							
						$my_tool_link = "\t" . '<a id="istooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . htmlspecialchars($toolsRow['link']) . '" target="' . $toolsRow['target'] . '">';	
					
					}

				}
				echo $toollink;
				//var_dump($toollink);
				/*
				Display::display_icon($toolsRow['image'], get_lang(ucfirst($toolsRow['name'])));
				*/
				if ($toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'file_html_na.gif'
					|| $toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif'
					|| $toolsRow['image'] == 'blog.gif' || $toolsRow['image'] == 'blog_na.gif'
					|| $toolsRow['image'] == 'external.gif' || $toolsRow['image'] == 'external_na.gif')
				{
					$tool_name = stripslashes($toolsRow['name']);
				} else {
					$tool_name = get_lang(ucfirst($toolsRow['name']));
				}
				Display::display_icon($toolsRow['image'], $tool_name, array('class'=>'tool-icon','id'=>'toolimage_'.$toolsRow["id"]));
				echo '</a> ';
					
				echo $my_tool_link;	
				/*	
					echo ($toolsRow['image'] == 'file_html_na.gif' || $toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif' || $toolsRow['image'] == 'blog.gif' || $toolsRow['image'] == 'blog_na.gif' || $toolsRow['image'] == 'external.gif' || $toolsRow['image'] == 'external_na.gif') ? '  '.stripslashes($toolsRow['name']) : '  '.get_lang(ucfirst($toolsRow['name']));
				*/
				echo $tool_name;
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
if (isset($_GET['sent_http_request']) && $_GET['sent_http_request']==1) {
	if(api_is_allowed_to_edit()) {
		$tool_table = Database::get_course_table(TABLE_TOOL_LIST);
	 	/*
		-----------------------------------------------------------
			HIDE
		-----------------------------------------------------------
		*/
		if(isset($_GET['visibility']) && $_GET['visibility']==0) // visibility 1 -> 0
		{
			if ($_GET["id"]==strval(intval($_GET["id"]))) {
				$sql="UPDATE $tool_table SET visibility=0 WHERE id='".$_GET["id"]."'";
	
				api_sql_query($sql,__FILE__,__LINE__);
				echo 'ToolIsNowHidden';
			}
		}
	
	  /*
		-----------------------------------------------------------
			REACTIVATE
		-----------------------------------------------------------
		*/
		elseif(isset($_GET['visibility'])&& $_GET['visibility']==1) // visibility 0,2 -> 1
		{
			if ($_GET["id"]==strval(intval($_GET["id"]))) {
				api_sql_query("UPDATE $tool_table SET visibility=1 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
				echo 'ToolIsNowVisible';
			}
		}
		exit;
	}
} else {
	
	if(api_is_allowed_to_edit()) {
	 	/*
		-----------------------------------------------------------
			HIDE
		-----------------------------------------------------------
		*/
		if(!empty($_GET['hide'])) // visibility 1 -> 0
		{
			api_sql_query("UPDATE $tool_table SET visibility=0 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
			Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
		}
	
	  /*
		-----------------------------------------------------------
			REACTIVATE
		-----------------------------------------------------------
		*/
		elseif(!empty($_GET['restore'])) // visibility 0,2 -> 1
		{
			api_sql_query("UPDATE $tool_table SET visibility=1 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
			Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
		}
	}
	
}

// work with data post askable by admin of course

if(api_is_platform_admin())
{
	// Show message to confirm that a tools must be hide from available tools
	// visibility 0,1->2
	if(!empty($_GET['askDelete']))
	{
		?>
			<div id="toolhide">
			<?php echo get_lang("DelLk")?>
			<br />&nbsp;&nbsp;&nbsp;
			<a href="<?php echo api_get_self()?>"><?php echo get_lang("No")?></a>&nbsp;|&nbsp;
			<a href="<?php echo api_get_self()?>?delete=yes&id=<?php echo $_GET["id"]?>"><?php echo get_lang("Yes")?></a>
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
if(api_is_allowed_to_edit())
{
	?>
	<div id="id_content_message"></div>
	<div class="courseadminview">
		<span class="viewcaption"><?php echo get_lang("Authoring") ?></span>
		<table width="100%">
			<?php show_tools_category(TOOL_AUTHORING);?>
		</table>
	</div>
	<div class="courseadminview">
		<span class="viewcaption"><?php echo get_lang("Interaction") ?></span>
		<table width="100%">
			<?php show_tools_category(TOOL_INTERACTION); ?>
		</table>
	</div>
	<div class="courseadminview">
		<span class="viewcaption"><?php echo get_lang("Administration") ?></span>
		<table width="100%">
			<?php show_tools_category(TOOL_ADMIN_PLATEFORM); ?>
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
			<?php show_tools_category(TOOL_STUDENT_VIEW); ?>
		</table>
	</div>
<?php
}
?>
