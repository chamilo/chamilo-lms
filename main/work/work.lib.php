<?php // $Id$
/*
==============================================================================
	Dokeos - elearning and course management software
	Copyright (c) Dokeos S.A.
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

function display_action_links($always_show_tool_options, $always_show_upload_form)
{
	$display_output = "";
	if (! $always_show_upload_form )
	{
		$display_output .= "<li><strong>" . "<a href=\"".$_SERVER['PHP_SELF']."?display_upload_form=true\">". get_lang("UploadADocument") . "</strong></a></li>";
	}
	if (! $always_show_tool_options && api_is_allowed_to_edit() )
	{
		$display_output .=	"<li><strong>" . "<a href=\"".$_SERVER['PHP_SELF']."?display_tool_options=true\">" . get_lang("EditToolOptions") . "</a>" . "</strong></a></li>";
	}
	
	if ($display_output != "") echo "<ul>$display_output</ul>";
}

/**
* Displays all options for this tool.
* These are
* - make all files visible / invisible
* - set the default visibility of uploaded files
*
* @param $uploadvisibledisabled
* @param $origin
*/
function display_tool_options($uploadvisibledisabled, $origin)
{
	$is_allowed_to_edit = api_is_allowed_to_edit();
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	
	if (! $is_allowed_to_edit) return;

	echo	"<table cellpadding=\"5\" cellspacing=\"2\" border=\"0\">\n";
		
	echo	"<tr>\n",
			"<td colspan=\"2\">\n",
	
			"<table cellspacing=\"0\" cellpadding=\"5\" border=\"1\" bordercolor\"gray\">\n",
			"<tr>\n",
			"<td>",
			get_lang('AllFiles')." : ",
			"<a href=\"".$_SERVER['PHP_SELF']."?origin=$origin&delete=all\" ",
			"onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."')) return false;\">",
			"<img src=\"../img/delete.gif\" border=\"0\" alt=\"".get_lang('Delete')."\" align=\"absmiddle\">",
			"</a>",
			"&nbsp;";

	$sql_query = "SHOW COLUMNS FROM ".$work_table." LIKE 'accepted'";
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);

	if ($sql_result)
	{
		$columnStatus = mysql_fetch_array($sql_result);

		if ($columnStatus['Default'] == 1)
		{
			echo	"<a href=\"".$_SERVER['PHP_SELF']."?origin=$origin&make_invisible=all\">",
					"<img src=\"../img/visible.gif\" border=\"0\" alt=\"".get_lang('Invisible')."\" align=\"absmiddle\">",
					"</a>\n";
		}
		else
		{
			echo	"<a href=\"".$_SERVER['PHP_SELF']."?origin=$origin&make_visible=all\">",
					"<img src=\"../img/invisible.gif\" border=\"0\" alt=\"".get_lang('Visible')."\" align=\"absmiddle\">",
					"</a>\n";
		}
	}

	echo "</td></tr>";

	display_default_visibility_form($uploadvisibledisabled, $origin);
	
	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
}

/**
* Displays the form where course admins can specify wether uploaded documents
* are visible or invisible by default.
*
* @param $uploadvisibledisabled
* @param $origin
*/
function display_default_visibility_form($uploadvisibledisabled, $origin)
{
	?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?origin=$origin"; ?>">
	<tr><td align="left">
		<strong><?php echo get_lang("_default_upload"); ?><br></strong>
		<input class="checkbox" type="radio" name="uploadvisibledisabled" value="0"
			<?php if($uploadvisibledisabled==0) echo "checked";  ?>>
		<?php echo get_lang("_new_visible");?><br>
		<input class="checkbox" type="radio" name="uploadvisibledisabled" value="1"
			<?php if($uploadvisibledisabled==1) echo "checked";  ?>>
		<?php echo get_lang("_new_unvisible"); ?><br>
		<input type="submit" name="changeProperties" value="<?php echo get_lang("Ok"); ?>">
	</td></tr>
	</form>
	<?php
}

/**
* Display the list of student publications, taking into account the user status
*
* @param $currentCourseRepositoryWeb, the web location of the course folder
* @param $link_target_parameter - should there be a target parameter for the links
* @param $dateFormatLong - date format
* @param $origin - typically empty or 'learnpath'
*/
function display_student_publications_list($currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin)
{
	//init
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$iprop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$is_allowed_to_edit = api_is_allowed_to_edit();
	$user_id = api_get_user_id();
	$publications_list = array();
	$sort_params = array();
	if( isset($_GET['column']))
	{
		$sort_params[] = 'column='.$_GET['column'];
	}
	if( isset($_GET['page_nr']))
	{
		$sort_params[] = 'page_nr='.$_GET['page_nr'];
	}
	if( isset($_GET['per_page']))
	{
		$sort_params[] = 'per_page='.$_GET['per_page'];
	}
	if( isset($_GET['direction']))
	{
		$sort_params[] = 'direction='.$_GET['direction'];
	}	
	$sort_params = implode('&amp;',$sort_params);
			
	//Get list from database
	if (!empty($_SESSION['toolgroup'])){
		$group_query = " WHERE post_group_id = '".$_SESSION['toolgroup']."' "; // set to select only messages posted by the user's group
	}
	else { 
		$group_query = '';
		}
	$sql_get_publications_list =	"SELECT * " .
									"FROM  ".$work_table." " .
									$group_query.
									"ORDER BY id";
	//echo $sql_get_publications_list;
	$sql_result = api_sql_query($sql_get_publications_list,__FILE__,__LINE__);
	
	$table_header[] = array(get_lang('Title'),true);
	$table_header[] = array(get_lang('Description'),true);
	$table_header[] = array(get_lang('Authors'),true);
	$table_header[] = array(get_lang('Date'),true);
	if( $is_allowed_to_edit)
	{
		$table_header[] = array(get_lang('Modify'),true);
	}
	$table_data = array();
	while( $work = mysql_fetch_object($sql_result))
	{
		//Get the author ID for that document from the item_property table
		$is_author = false;
		$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=".$work->id;
		$author_qry = api_sql_query($author_sql,__FILE__,__LINE__);
		if(Database::num_rows($author_qry)==1){
			$is_author = true;
		}
		
		//display info depending on the permissions
		if( $work->accepted == '1' || $is_allowed_to_edit)
		{
			$row = array();
			if($work->accepted == '0')
			{	
				$class='class="invisible"';
			}
			else 
			{
				$class='';
			}
			$url = implode("/", array_map("rawurlencode", explode("/", $work->url)));
			$row[] = '<a href="'.$currentCourseRepositoryWeb.$url.'"'.$class.'>'.$work->title.'</a>';
			$row[] = $work->description;
			$row[] = $work->author;
			$row[] = $work->sent_date;	
			if( $is_allowed_to_edit)
			{
				if($work->accepted == '1')	
				{
					$action = '<a href="'.$_SERVER['PHP_SELF'].'?origin='.$origin.'&make_invisible='.$work->id.'&amp;'.$sort_params.'"><img src="../img/visible.gif" alt="'.get_lang('Invisible').'"></a>';
				}
				else
				{
					$action = '<a href="'.$_SERVER['PHP_SELF'].'?origin='.$origin.'&make_visible='.$work->id.'&amp;'.$sort_params.'"><img src="../img/invisible.gif" alt="'.get_lang('Visible').'"></a>';
				}
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?origin='.$origin.'&edit='.$work->id.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?origin='.$origin.'&delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'"></a>';
	
				$row[] = $action;
			}elseif($is_author){
				$action = '';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?&origin='.$origin.'&edit='.$work->id.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?&origin='.$origin.'&delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'"></a>';
	
				$row[] = $action;			
			}else{
				$row[] = " "; 
			}
			$table_data[] = $row;
		}
	}
	if( count($table_data) > 0)
	{
		Display::display_sortable_table($table_header,$table_data);
	}
}
