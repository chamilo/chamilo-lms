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
	global $_user;
	$web_code_path = api_get_path(WEB_CODE_PATH);
	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
	$is_allowed_to_edit = api_is_allowed_to_edit();
	$is_platform_admin = api_is_platform_admin();

	//condition for the session
	$session_id = api_get_session_id();
	$condition_session = api_get_session_condition($session_id,true,true);

	switch ($course_tool_category) {

		case TOOL_STUDENT_VIEW:
				$result = Database::query("SELECT * FROM $course_tool_table WHERE visibility = '1' AND (category = 'authoring' OR category = 'interaction') $condition_session ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_AUTHORING:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE category = 'authoring' $condition_session ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_INTERACTION:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE category = 'interaction' $condition_session ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_ADMIN_VISIBLE:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE category = 'admin' AND visibility ='1' $condition_session ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_ADMIN_PLATEFORM:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE category = 'admin' $condition_session ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;


	}

	while ($temp_row = Database::fetch_array($result))
	{
		$all_tools_list[]=$temp_row;
	}

	/*if(api_is_course_coach())
	{
		$result = Database::query("SELECT * FROM $course_tool_table WHERE name='tracking'",__FILE__,__LINE__);
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
						WHERE tl.on_homepage='1' $condition_session";

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
						WHERE tl.on_homepage='1' $condition_session";
			break;

			case TOOL_ADMIN:
				$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' $condition_session";
			break;

		default:
			$sql_links = null;
			break;
	}

	//edit by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
	if($sql_links != null)
	{
		$result_links = Database::query($sql_links,__FILE__,__LINE__);
		$properties = array();
		if (Database::num_rows($result_links) > 0) {
			while($links_row = Database::fetch_array($result_links))
			{
				unset($properties);
				$properties['name'] = $links_row['title'];
				$properties['session_id'] = $links_row['session_id'];
				$properties['link'] = $links_row['url'];
				$properties['visibility'] = $links_row['visibility'];
				$properties['image'] = ($links_row['visibility']== '0') ? "file_html.gif" : "file_html.gif";
				$properties['adminlink'] = api_get_path(WEB_CODE_PATH) . "link/link.php?action=editlink&id=".$links_row['id'];
				$properties['target'] = $links_row['target'];
				$tmp_all_tools_list[] = $properties;
			}
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

				$result_blogs = Database::query($sql_blogs, __FILE__, __LINE__);

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

			if (api_get_session_id()!=0 && in_array($toolsRow['name'],array('course_maintenance','course_setting'))) {
				continue;
			}

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

				//validacion when belongs to a session
				$session_img = api_get_session_image($toolsRow['session_id'], $_user['status']);

				echo '</a> ';

				echo $my_tool_link;
				/*
					echo ($toolsRow['image'] == 'file_html_na.gif' || $toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif' || $toolsRow['image'] == 'blog.gif' || $toolsRow['image'] == 'blog_na.gif' || $toolsRow['image'] == 'external.gif' || $toolsRow['image'] == 'external_na.gif') ? '  '.stripslashes($toolsRow['name']) : '  '.get_lang(ucfirst($toolsRow['name']));
				*/
				echo "{$tool_name}$session_img";
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
		$tool_id = Security::remove_XSS($_GET["id"]);
		$tool_info = api_get_tool_information($tool_id);
		$tool_visibility   = $tool_info['visibility'];
		$tool_image        = $tool_info['image'];
		$new_image         = str_replace('.gif','_na.gif',$tool_image);
		$requested_image   = ($tool_visibility == 0 ) ? $tool_image : $new_image;
		$requested_clase   = ($tool_visibility == 0 ) ? 'visible' : 'invisible';
		$requested_message = ($tool_visibility == 0 ) ? 'is_active' : 'is_inactive';
	    $requested_view    = ($tool_visibility == 0 ) ? 'visible.gif' : 'invisible.gif';
	    $requested_visible = ($tool_visibility == 0 ) ? 1 : 0;

    	$requested_view    = ($tool_visibility == 0 ) ? 'visible.gif' : 'invisible.gif';
    	$requested_visible = ($tool_visibility == 0 ) ? 1 : 0;
	//HIDE AND REACTIVATE TOOL 
	if ($_GET["id"]==strval(intval($_GET["id"]))) {
		$sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".$_GET["id"]."'";
		Database::query($sql,__FILE__,__LINE__);
	}
		/*
		-----------------------------------------------------------
			HIDE
		-----------------------------------------------------------
		*/
/*		if(isset($_GET['visibility']) && $_GET['visibility']==0) // visibility 1 -> 0
		{
			if ($_GET["id"]==strval(intval($_GET["id"]))) {
				$sql="UPDATE $tool_table SET visibility=0 WHERE id='".intval($_GET["id"])."'";
				Database::query($sql,__FILE__,__LINE__);
			}
		}

	  /*
		-----------------------------------------------------------
			REACTIVATE
		-----------------------------------------------------------
		*/
/*		elseif(isset($_GET['visibility'])&& $_GET['visibility']==1) // visibility 0,2 -> 1
		{
			if ($_GET["id"]==strval(intval($_GET["id"]))) {
				Database::query("UPDATE $tool_table SET visibility=1 WHERE id='".intval($_GET["id"])."'",__FILE__,__LINE__);
			}
		}

*/
		$response_data = array(
			'image'   => $requested_image,
			'tclass'  => $requested_clase,
			'message' => $requested_message,
      		'view'    => $requested_view
		);
		print(json_encode($response_data));
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
			Database::query("UPDATE $tool_table SET visibility=0 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
			Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
		}

	  /*
		-----------------------------------------------------------
			REACTIVATE
		-----------------------------------------------------------
		*/
		elseif(!empty($_GET['restore'])) // visibility 0,2 -> 1
		{
			Database::query("UPDATE $tool_table SET visibility=1 WHERE id='".$_GET["id"]."'",__FILE__,__LINE__);
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
		Database::query("DELETE FROM $tool_table WHERE id='$id' AND added_tool=1",__FILE__,__LINE__);
	}
}



/*
==============================================================================
		SESSION DATA
==============================================================================
*/
/**
 * Shows the general data for a particular meeting
 *
 * @param id	session id
 * @return string	session data
 *
 */
function show_session_data($id_session) {
	$session_table = Database::get_main_table(TABLE_MAIN_SESSION);
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

	$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id, visibility
				FROM '.$session_table.'
			LEFT JOIN '.$user_table.'
				ON id_coach = user_id
			WHERE '.$session_table.'.id='.$id_session;

	$rs = Database::query($sql, __FILE__, __LINE__);
	$session = Database::store_result($rs);
	$session = $session[0];

	$sql_category = 'SELECT name FROM '.$session_category_table.' WHERE id = "'.intval($session['session_category_id']).'"';
	$rs_category = Database::query($sql_category, __FILE__, __LINE__);
	$session_category = '';
	if (Database::num_rows($rs_category) > 0) {
		$rows_session_category = Database::store_result($rs_category);
		$rows_session_category = $rows_session_category[0];
		$session_category = $rows_session_category['name'];
	}

	if ($session['date_start'] == '00-00-0000') {
		$msg_date = get_lang('NoTimeLimits');
	} else {
		$msg_date = get_lang('From').' '.$session['date_start'].' '.get_lang('To').' '.$session['date_end'];
	}

	$output  = '';
	if (!empty($session_category)) {
		$output .= '<tr><td>'. get_lang('SessionCategory') . ': ' . '<b>' . $session_category .'</b></td></tr>';
	}
	$output .= '<tr><td style="width:50%">'. get_lang('SessionName') . ': ' . '<b>' . $session['name'] .'</b></td><td>'. get_lang('GeneralCoach') . ': ' . '<b>' . $session['lastname'].' '.$session['firstname'].' ('.$session['username'].')' .'</b></td></tr>';
	$output .= '<tr><td>'. get_lang('SessionIdentifier') . ': '. Display::return_icon('star.png', ' ', array('align' => 'absmiddle')) .'</td><td>'. get_lang('Date') . ': ' . '<b>' . $msg_date .'</b></td></tr>';

	return $output;
}

/*
==============================================================================
		COURSE ADMIN ONLY VIEW
==============================================================================
*/

// start of tools for CourseAdmins (teachers/tutors)
if(api_is_allowed_to_edit())
{
    $current_protocol  = $_SERVER['SERVER_PROTOCOL'];
    $current_host      = $_SERVER['HTTP_HOST'];
    $server_protocol = substr($current_protocol,0,strrpos($current_protocol,'/'));
    $server_protocol = $server_protocol.'://';
    if ($current_host == 'localhost') {
      //Get information of path
      $info = explode('courses',api_get_self());
      $path_work = substr($info[0], 0, strlen($info[0]));
    } else {
      $path_work = "";
    }

?>
	<div class="courseadminview" style="border:0px;">
		<div class="normal-message" id="id_normal_message" style="display:none">		
		<?php			
			echo '<img src="'.$server_protocol.$current_host.'/'.$path_work.'main/inc/lib/javascript/indicator.gif"/>'."&nbsp;&nbsp;";
			echo get_lang('PleaseStandBy');
		?>
		</div>
		<div class="confirmation-message" id="id_confirmation_message" style="display:none"></div>
	</div>
	
	<?php
		if (api_get_setting('show_session_data') === 'true' && $id_session > 0) {
	?>
	<div class="courseadminview">
		<span class="viewcaption"><?php echo get_lang("SessionData") ?></span>
		<table width="100%">
			<?php echo show_session_data($id_session);?>
		</table>
	</div>
	<?php
		}
	?>
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
		} elseif (api_is_coach()) {

			if (api_get_setting('show_session_data') === 'true' && $id_session > 0) {
	?>
		<div class="courseadminview">
			<span class="viewcaption"><?php echo get_lang("SessionData") ?></span>
			<table width="100%">
				<?php echo show_session_data($id_session);?>
			</table>
		</div>
	<?php
			}
	?>
		<div class="Authoringview">
			<table width="100%">
				<?php show_tools_category(TOOL_STUDENT_VIEW); ?>
			</table>
		</div>
	<?php

/*
==============================================================================
		TOOLS AUTHORING
==============================================================================
*/

	} else {
?>
	<div class="Authoringview">

		<table width="100%">
			<?php show_tools_category(TOOL_STUDENT_VIEW); ?>
		</table>
	</div>
<?php
}
?>
