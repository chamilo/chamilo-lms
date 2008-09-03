<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
*	@package dokeos.studentpublications
* 	@author Thomas, Hugues, Christophe - original version
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University - ability for course admins to specify wether uploaded documents are visible or invisible by default.
* 	@author Roan Embrechts, code refactoring and virtual course support
* 	@author Frederic Vauthier, directories management
* 	@version $Id: $
*/
/**
 * Displays action links (for admins, authorized groups members and authorized students)
 * @param	string	Current dir
 * @param	integer	Whether to show tool options
 * @param	integer	Whether to show upload form option
 * @return	void
 */
 
require_once('../document/document.inc.php');
require_once('../inc/lib/fileDisplay.lib.php');


function display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form)
{
	$display_output = "";
	if(strlen($cur_dir_path) > 0 && $cur_dir_path != '/')
	{
		$parent_dir = dirname($cur_dir_path);
		$display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'&curdirpath='.$parent_dir.'">'.Display::return_icon('folder_up.gif').' '.get_lang('Up').'</a>&nbsp;&nbsp;';
		
		
	}
	
	if (! $always_show_upload_form )
	{
		$display_output .= "&nbsp;&nbsp;<a href=\"".api_get_self()."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;display_upload_form=true&amp;origin=".Security::remove_XSS($_GET['origin'])."\">".Display::return_icon('submit_file.gif')." ". get_lang("UploadADocument") .'</a>&nbsp;&nbsp;&nbsp;&nbsp;';			
	}
	
	if (! $always_show_tool_options && api_is_allowed_to_edit(false,true) )
	{
		// Create dir
		$display_output .=	'<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$cur_dir_path.'&amp;createdir=1&origin='.Security::remove_XSS($_GET['origin']).'"><img src="../img/folder_new.gif" border="0"alt ="'.get_lang('CreateDir').'" /> '.get_lang('CreateDir').' </a>&nbsp;&nbsp;';
		
		
		if(api_is_allowed_to_edit()) // the coach can't edit options of the tool
			// Options
			$display_output .=	"<a href=\"".api_get_self()."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;origin=".Security::remove_XSS($_GET['origin'])."&amp;display_tool_options=true&amp;origin=".Security::remove_XSS($_GET['origin'])."\">".Display::return_icon('acces_tool.gif').' ' . get_lang("EditToolOptions") . "</a>&nbsp;&nbsp;";							
	}

	if ($display_output != "")
	{
		echo $display_output;
	}
}

/**
* Displays all options for this tool.
* These are
* - make all files visible / invisible
* - set the default visibility of uploaded files
*
* @param $uploadvisibledisabled
* @param $origin
* @param $base_work_dir Base working directory (up to '/work')
* @param $cur_dir_path	Current subdirectory of 'work/'
* @param $cur_dir_path_url Current subdirectory of 'work/', url-encoded
*/
function display_tool_options($uploadvisibledisabled, $origin,$base_work_dir,$cur_dir_path,$cur_dir_path_url)
{
	global $charset, $group_properties;
	$is_allowed_to_edit = api_is_allowed_to_edit(false,true);
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

	if (! $is_allowed_to_edit) return;

	echo '<form method="post" action="'.api_get_self().'?origin='.$origin.'&display_tool_options=true">';

	echo	"<br/><table class=\"data_table\">\n",
			"<tr><th>&nbsp;</th><th>".get_lang("Modify")."</th></tr><tr class=\"row_even\">\n",
			"<td align=\"right\">",
			get_lang('AllFiles')." : </td>",
			"<td ><a href=\"".api_get_self()."?".api_get_cidreq()."&amp;curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;delete=all&amp;display_tool_options=true\" ",
			"onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">",
			"<img src=\"../img/delete.gif\" border=\"0\" alt=\"".get_lang('Delete')."\" />",
			"</a>",
			"&nbsp;";

	$sql_query = "SHOW COLUMNS FROM ".$work_table." LIKE 'accepted'";
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);

	if ($sql_result)
	{
		$columnStatus = mysql_fetch_array($sql_result);

		if ($columnStatus['Default'] == 1)
		{
			echo	"<a href=\"".api_get_self()."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;origin=$origin&make_invisible=all&display_tool_options=true\">",
					"<img src=\"../img/visible.gif\" border=\"0\" alt=\"".get_lang('Invisible')."\" />",
					"</a>\n";
		}
		else
		{
			echo	"<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;make_visible=all&amp;display_tool_options=true\">",
					"<img src=\"../img/invisible.gif\" border=\"0\" alt=\"".get_lang('Visible')."\">",
					"</a>\n";
		}
	}

	echo "</td></tr>";

	display_default_visibility_form($uploadvisibledisabled);

	echo '</table>';

	echo '<div>'.get_lang("ValidateChanges").' : <input type="submit" name="changeProperties" value="'.get_lang("Ok").'" /></div></form>';

/*
	echo	"<br/><table cellpadding=\"5\" cellspacing=\"2\" border=\"0\">\n";
	
	//==============================================================================
	//		Display directories list
	//==============================================================================
	
	
	//$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit || $group_member_with_upload_rights);
	if($cur_dir_path=='/'){$my_cur_dir_path='';}else{$my_cur_dir_path=$cur_dir_path;}
	$folders = get_subdirs_list($base_work_dir,1);
	echo '<div id="folderselector">';
	echo(build_work_directory_selector($folders,$cur_dir_path,''));
	echo '</div>';
	echo '</td></tr><tr><td>';
	if ($cur_dir_path!= '/' && $cur_dir_path!=$group_properties['directory'])
	{
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode((dirname($cur_dir_path)=='\\')?'/':dirname($cur_dir_path)).'">'.
				'<img src="../img/parent.gif" border="0" align="absbottom" hspace="5" alt="" />'.
				get_lang("Up").'</a>&nbsp;'."\n";
}
	echo '<!-- create directory -->' .
			'<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$cur_dir_path.'&amp;createdir=1"><img src="../img/folder_new.gif" border="0"alt ="'.get_lang('CreateDir').'" /></a>'.
			'<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$cur_dir_path.'&amp;createdir=1">'.get_lang('CreateDir').'</a>&nbsp;'."\n";
	echo "</td></tr></table>";
	*/
}

/**
* Displays the form where course admins can specify wether uploaded documents
* are visible or invisible by default.
*
* @param $uploadvisibledisabled
* @param $origin
*/
function display_default_visibility_form($uploadvisibledisabled)
{
	?>
	<tr class="row_odd"><td align="right">
		<strong><?php echo get_lang("_default_upload"); ?></strong></td>
		<td><input class="checkbox" type="radio" name="uploadvisibledisabled" value="0"
			<?php if($uploadvisibledisabled==0) echo "checked";  ?> />
		<?php echo get_lang("_new_visible");?><br />
		<input class="checkbox" type="radio" name="uploadvisibledisabled" value="1"
			<?php if($uploadvisibledisabled==1) echo "checked";  ?> />
		<?php echo get_lang("_new_unvisible"); ?><br />
	</td></tr>
	<?php
}


/**
* This function displays the firstname and lastname of the user as a link to the user tool.
*
* @see this is the same function as in the new forum, so this probably has to move to a user library.
*
* @todo move this function to the user library
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_user_link($user_id, $name='')
{
	global $_otherusers;

	if ($user_id<>0)
	{
		if ($name=='')
		{
			$table_user = Database::get_main_table(TABLE_MAIN_USER);
			$sql="SELECT * FROM $table_user WHERE user_id='".Database::escape_string($user_id)."'";
			$result=api_sql_query($sql,__FILE__,__LINE__);
			$row=mysql_fetch_array($result);
			return "<a href=\"../user/userInfo.php?uInfo=".$row['user_id']."\">".$row['firstname']." ".$row['lastname']."</a>";
		}
		else
		{
			return "<a href=\"../user/userInfo.php?uInfo=".$user_id."\">".$name."</a>";
		}
	}
	else
	{
		return $name.' ('.get_lang('Anonymous').')';
	}
}



/**
* Display the list of student publications, taking into account the user status
*
* @param $currentCourseRepositoryWeb, the web location of the course folder
* @param $link_target_parameter - should there be a target parameter for the links
* @param $dateFormatLong - date format
* @param $origin - typically empty or 'learnpath'
*/
function display_student_publications_list($work_dir,$sub_course_dir,$currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin)
{
	global $charset;
	// Database table names
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$iprop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$is_allowed_to_edit = api_is_allowed_to_edit(false,true);
	$user_id = api_get_user_id();
	$publications_list = array();
	$sort_params = array();
	
	if( isset($_GET['column']))
	{
		$sort_params[] = 'column='.Security::remove_XSS($_GET['column']);
	}
	if( isset($_GET['page_nr']))
	{
		$sort_params[] = 'page_nr='.Security::remove_XSS($_GET['page_nr']);
	}
	if( isset($_GET['per_page']))
	{
		$sort_params[] = 'per_page='.Security::remove_XSS($_GET['per_page']);
	}
	if( isset($_GET['direction']))
	{
		$sort_params[] = 'direction='.Security::remove_XSS($_GET['direction']);
	}
	$sort_params = implode('&amp;',$sort_params);
	
	$origin=Security::remove_XSS($origin);
	
	if(substr($sub_course_dir,-1,1)!='/' && !empty($sub_course_dir))
	{
		$sub_course_dir = $sub_course_dir.'/';
	}
	if($sub_course_dir == '/')
	{
		$sub_course_dir='';
	}
	
	$session_condition =  intval($_SESSION['id_session'])!=0 ?"AND session_id IN (0,".intval($_SESSION['id_session']).")" : "";
	//Get list from database
	if($is_allowed_to_edit)
	{
		
		$sql_get_publications_list = 	"SELECT * " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE BINARY '$sub_course_dir%' " .
										"AND url NOT LIKE BINARY '$sub_course_dir%/%' " .
										$session_condition.
		                 				"ORDER BY id";
		                 				
		$sql_get_publications_num = 	"SELECT count(*) " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE BINARY '$sub_course_dir%' " .
										"AND url NOT LIKE BINARY '$sub_course_dir%/%' " .
										$session_condition.
		                 				"ORDER BY id";
		                 				
	}
	else
	{
		if (!empty($_SESSION['toolgroup']))
		{
			$group_query = " WHERE post_group_id = '".$_SESSION['toolgroup']."' "; // set to select only messages posted by the user's group
			$subdirs_query = "AND url NOT LIKE BINARY '$sub_course_dir%/%' AND url LIKE BINARY '$sub_course_dir%'";
		}
		else
		{
			$group_query = '';
			$subdirs_query = "WHERE url NOT LIKE '$sub_course_dir%/%' AND url LIKE '$sub_course_dir%'";
		}
		
		
		$sql_get_publications_list = "SELECT * FROM  $work_table $group_query $subdirs_query AND session_id IN (0,".intval($_SESSION['id_session']).") ORDER BY id";
		
		$sql_get_publications_num = "SELECT count(url) " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE BINARY '$sub_course_dir%' " .
										"AND url NOT LIKE BINARY '$sub_course_dir%/%' " .
										$session_condition.
		                 				"ORDER BY id";
		                 				
	}
	
	$sql_result = api_sql_query($sql_get_publications_list,__FILE__,__LINE__);
	$sql_result_num = api_sql_query($sql_get_publications_num,__FILE__,__LINE__);
	
	$row=Database::fetch_array($sql_result_num);
	$count_files=$row[0];

	$table_header[] = array(get_lang('Type'),true,'style="width:40px"');
	$table_header[] = array(get_lang('Title'),true);
		
	if ($count_files!=0)
	{
		$table_header[] = array(get_lang('Authors'),true);
	}

	
	$table_header[] = array(get_lang('Date'),true);
	
	if( $is_allowed_to_edit)
	{
	$table_header[] = array(get_lang('Modify'),true);
	}
	
	$table_header[] = array('RealDate',false);
		
	// An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide 
	$column_show[]=1; // type
	$column_show[]=1; // title
	
	if ($count_files!=0)
	{
		$column_show[]=1;	 // authors
	}

	$column_show[]=1; //date
	
	if( $is_allowed_to_edit)
	{
		$column_show[]=1; //modify
	}
	
	$column_show[]=0;	 //real date in correct format
	
	
	// Here we change the way how the colums are going to be sort
	// in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate 
	// because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"
	
	$column_order[]=1; //type
	$column_order[]=2; // title
	
	if ($count_files!=0)
	{
		$column_order[]=3; //authors
	}
	
	$column_order[]=6; // date
	
	if( $is_allowed_to_edit)
	{
		$column_order[]=5;
	}
	
	$column_order[]=6;
	
	$table_data = array();
	$dirs_list = get_subdirs_list($work_dir);
	$my_sub_dir = str_replace('work/','',$sub_course_dir);
	
	// List of all folders
	foreach($dirs_list as $dir)
	{
		if ($my_sub_dir=='')
		{	
				$mydir_temp = '/'.$dir;
		}
		else
		{
			$mydir_temp = '/'.$my_sub_dir.$dir;
		}
		
		// select the directory's date
        /*$sql_select_directory= "SELECT sent_date FROM ".$work_table." WHERE " .
							   "url LIKE BINARY '".$mydir_temp."' AND filetype = 'folder'";
							   
		*/		
		$session_condition =  intval($_SESSION['id_session'])!=0 ?"AND work.session_id IN (0,".intval($_SESSION['id_session']).")" : "";		   
		$sql_select_directory= "SELECT prop.lastedit_date, author FROM ".$iprop_table." prop INNER JOIN ".$work_table." work ON (prop.ref=work.id) WHERE " .
							   	    "work.url LIKE BINARY '".$mydir_temp."' AND work.filetype = 'folder' AND prop.tool='work' $session_condition";													   
		$result=api_sql_query($sql_select_directory,__FILE__,__LINE__);
		$row=Database::fetch_array($result);
		
		if(!$row) // the folder belongs to another session
			continue;
			
		$direc_date= $row['lastedit_date']; //directory's date
		$author= $row['author']; //directory's author
			
		$mydir = $my_sub_dir.$dir;	
			
		if ($is_allowed_to_edit) 
		{				
			$clean_edit_dir=Security :: remove_XSS(Database::escape_string($_GET['edit_dir']));
				
			// form edit directory				
			if(isset($clean_edit_dir) && $clean_edit_dir==$mydir)
			{	
				$form_folder = new FormValidator('edit_dir', 'post', api_get_self().'?curdirpath='.$my_sub_dir.'&origin='.$origin.'&edit_dir='.$mydir);
				$group_name[] = FormValidator :: createElement('text','dir_name');
				$group_name[] = FormValidator :: createElement('submit','submit_edit_dir',get_lang('Ok'));
				$form_folder -> addGroup($group_name,'my_group');
				$form_folder -> addGroupRule('my_group',get_lang('ThisFieldIsRequired'),'required');
				$form_folder -> setDefaults(array('my_group[dir_name]'=>$dir));				
				$display_edit_form=true;		
					
				if($form_folder -> validate())
				{
					$values = $form_folder -> exportValues();
					$values = $values['my_group'];					
					update_dir_name($mydir,$values['dir_name']);
					$mydir = $my_sub_dir.$values['dir_name'];
					$dir = $values['dir_name'];
					$display_edit_form=false;
								
				}
				
			}
		}
		
		$action = '';
		$row = array();
		$class = '';										
		$row[] = '<img src="../img/folder_document.gif" border="0" hspace="5" align="middle" alt="'.get_lang('Folder').'" />'; //image
		$a_count_directory=count_dir($work_dir.'/'.$dir,false);
		$cant_files=$a_count_directory[0];
		$cant_dir=$a_count_directory[1];

		$text_file=get_lang('FilesUpload');
		$text_dir=get_lang('Directories');
				
		if ($cant_files==1)
		{
			$text_file=strtolower(get_lang('FileUpload'));			
		}
		
		if ($cant_dir==1)
		{
			$text_dir=get_lang('directory');	
		}
		
		if ($cant_dir!=0) 
		{
			$dirtext=' ('.$cant_dir.' '.$text_dir.')';
		}
		else
		{
			$dirtext='';
		}

		if($display_edit_form && isset($clean_edit_dir) && $clean_edit_dir==$mydir)
		{
			$row[] = '<span class="invisible" style="display:none">'.$dir.'</span>'.$form_folder->toHtml(); // form to edit the directory's name
		}
		else
		{
			$row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&curdirpath='.$mydir.'"'.$class.'>'.$dir.'</a><br>'.$cant_files.' '.$text_file.$dirtext;				
		}
		
		if ($count_files!=0)
		{
			$row[] = "";
		}	
	
		if ($direc_date!='' && $direc_date!='0000-00-00 00:00:00') 
		{
			$row[]= date_to_str_ago($direc_date).'<br><span class="dropbox_date">'.$direc_date.'</span>';
		}
		else
		{
			$row[]='';			
		}	
		
		if( $is_allowed_to_edit)
		{
			$action .= '<a href="'.api_get_self().'?cidReq='.api_get_course_id().
				'&curdirpath='.$my_sub_dir.'&origin='.$origin.'&edit_dir='.$mydir.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';						
			$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&delete_dir='.$mydir.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."'".')) return false;" title="'.get_lang('DirDelete').'"  ><img src="'.api_get_path(WEB_IMG_PATH).'delete.gif" alt="'.get_lang('DirDelete').'"></a>';
			$row[] = $action;
		}
		else
		{
			$row[] = "";
		}		
		$table_data[] = $row;
	}
	
	while( $work = mysql_fetch_object($sql_result))
	{
		//Get the author ID for that document from the item_property table
		$is_author = false;
		$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=".$work->id;
		$author_qry = api_sql_query($author_sql,__FILE__,__LINE__);
		
		if(Database::num_rows($author_qry)==1)
		{
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
			$row[]= build_document_icon_tag('file',$work->url);			
			$row[]= '<a href="'.$currentCourseRepositoryWeb.$url.'"'.$class.'><img src="../img/filesave.gif" style="float:right;" alt="'.get_lang('Save').'" />'.$work->title.'</a><br />'.$work->description;
			$row[]= display_user_link($user_id,$work->author);// $work->author;			
			$row[]= date_to_str_ago($work->sent_date).'<br><span class="dropbox_date">'.$work->sent_date.'</span>';
			
			if( $is_allowed_to_edit)
			{
				$action = '';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;edit='.$work->id.'" title="'.get_lang('Modify').'"  ><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."'".')) return false;" title="'.get_lang('WorkDelete').'" ><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;move='.$work->id.'" title="'.get_lang('Move').'"><img src="../img/deplacer_fichier.gif" border="0" title="'.get_lang('Move').'" alt="" /></a>';
				if($work->accepted == '1')
				{
					$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;make_invisible='.$work->id.'&amp;'.$sort_params.'" title="'.get_lang('Invisible').'" ><img src="../img/visible.gif" alt="'.get_lang('Invisible').'"></a>';
				}
				else
				{
					$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;make_visible='.$work->id.'&amp;'.$sort_params.'" title="'.get_lang('Visible').'" ><img src="../img/invisible.gif" alt="'.get_lang('Visible').'"></a>';
				}

				$row[] = $action;
			}
			elseif($is_author)
			{
				$action = '';				
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;edit='.$work->id.'" title="'.get_lang('Modify').'"  ><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."'".')) return false;" title="'.get_lang('WorkDelete').'"  ><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'"></a>';

				$row[] = $action;
			}
			else
			{
				$row[] = " ";
			}
			$table_data[] = $row;
		}
	}

	$sorting_options=array();
	$sorting_options['column']=1; 
	
	$paging_options=array();
	Display::display_sortable_config_table($table_header,$table_data,$sorting_options, $paging_options,NULL,$column_show,$column_order);
}
/**
 * Returns a list of subdirectories found in the given directory.
 *
 * The list return starts from the given base directory.
 * If you require the subdirs of /var/www/ (or /var/www), you will get 'abc/', 'def/', but not '/var/www/abc/'...
 * @param	string	Base dir
 * @param	integer	0 if we only want dirs from this level, 1 if we want to recurse into subdirs
 * @return	strings_array	The list of subdirs in 'abc/' form, -1 on error, and 0 if none found
 * @todo	Add a session check to see if subdirs_list doesn't exist yet (cached copy)
 */
function get_subdirs_list($basedir='',$recurse=0){
	//echo "Looking for subdirs of $basedir";
	if(empty($basedir) or !is_dir($basedir)){return -1;}
	if(substr($basedir,-1,1)!='/'){$basedir = $basedir.'/';}
	$dirs_list = array();
	$dh = opendir($basedir);
	while($entry = readdir($dh)){
		if(is_dir($basedir.$entry) && $entry!='..' && $entry!='.'){
			$dirs_list[] = $entry;
			if($recurse==1){
				foreach(get_subdirs_list($basedir.$entry) as $subdir){
					$dirs_list[] = $entry.'/'.$subdir;
				}
			}
		}
	}
	closedir($dh);
	return $dirs_list;
}
/**
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $group_dir
 * @return string html form
 */
function build_work_directory_selector($folders,$curdirpath,$group_dir='')
{
	$form = '<form name="selector" action="'.api_get_self().'?'.api_get_cidreq().'" method="POST">'."\n";
	$form .= get_lang('CurrentDirectory').' <select name="curdirpath" onchange="javascript:document.selector.submit()">'."\n";
	//group documents cannot be uploaded in the root
	if($group_dir=='')
	{
		$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		if(is_array($folders))
		{
			foreach ($folders as $folder)
			{
				$selected = ($curdirpath==$folder)?' selected="selected"':'';
				$form .= '<option'.$selected.' value="'.$folder.'">'.$folder.'</option>'."\n";
			}
		}
	}
	else
	{
		foreach ($folders as $folder)
		{
			$selected = ($curdirpath==$folder)?' selected="selected"':'';
			$display_folder = substr($folder,strlen($group_dir));
			$display_folder = ($display_folder == '')?'/ ('.get_lang('Root').')':$display_folder;
			$form .= '<option'.$selected.' value="'.$folder.'">'.$display_folder.'</option>'."\n";
		}
	}

	$form .= '</select>'."\n";
	$form .= '<noscript><input type="submit" name="change_path" value="'.get_lang('Ok').'" /></noscript>'."\n";
	$form .= '</form>';

	return $form;
}
/**
 * Builds the form thats enables the user to
 * move a document from one directory to another
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $move_file
 * @return string html form
 */
function build_work_move_to_selector($folders,$curdirpath,$move_file,$group_dir='')
{
	$form = '<form name="move_to" action="'.api_get_self().'" method="POST">'."\n";
	$form .= '<input type="hidden" name="move_file" value="'.$move_file.'" />'."\n";
	$form .= get_lang('MoveTo').' <select name="move_to">'."\n";

	//group documents cannot be uploaded in the root
	if($group_dir=='')
	{
		if($curdirpath!='/')
		{
			$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		}
		if(is_array($folders))
		{
			foreach ($folders as $folder)
			{
				//you cannot move a file to:
				//1. current directory
				//2. inside the folder you want to move
				//3. inside a subfolder of the folder you want to move
				if(($curdirpath!=$folder) && ($folder!=$move_file) && (substr($folder,0,strlen($move_file)+1) != $move_file.'/'))
				{
					$form .= '<option value="'.$folder.'">'.$folder.'</option>'."\n";
				}
			}
		}
	}
	else
	{
		if($curdirpath!='/')
		{
			$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		}
		foreach ($folders as $folder)
		{
			if(($curdirpath!=$folder) && ($folder!=$move_file) && (substr($folder,0,strlen($move_file)+1) != $move_file.'/'))//cannot copy dir into his own subdir
			{
				$display_folder = substr($folder,strlen($group_dir));
				$display_folder = ($display_folder == '')?'/ ('.get_lang('Root').')':$display_folder;
				$form .= '<option value="'.$folder.'">'.$display_folder.'</option>'."\n";
			}
		}
	}

	$form .= '</select>'."\n";
	$form .= '<input type="submit" name="move_file_submit" value="'.get_lang('Ok').'" />'."\n";
	$form .= '</form>';

	return $form;
}
/**
 * Checks if the first given directory exists as a subdir of the second given directory
 * This function should now be deprecated by Security::check_abs_path()
 * @param	string	Subdir
 * @param	string	Base dir
 * @return	integer	-1 on error, 0 if not subdir, 1 if subdir
 */
function is_subdir_of($subdir,$basedir){
	if(empty($subdir) or empty($basedir)){return -1;}
	if(substr($basedir,-1,1)!='/'){$basedir=$basedir.'/';}
	if(substr($subdir,0,1)=='/'){$subdir = substr($subdir,1);}
	if(is_dir($basedir.$subdir)){
		return 1;
	}else{
		return 0;
	}
}
/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist
 * (we could use unique_name() here...)
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @author Yannick Warnier <ywarnier@beeznest.org> Adaptation for work tool
 * @param	string	Base work dir (.../work)
 * @param 	string $desiredDirName complete path of the desired name
 * @return 	string actual directory name if it succeeds,
 *         boolean false otherwise
 */

function create_unexisting_work_directory($base_work_dir,$desired_dir_name)
{
	$nb = '';
	$base_work_dir = (substr($base_work_dir,-1,1)=='/'?$base_work_dir:$base_work_dir.'/');
	while ( file_exists($base_work_dir.$desired_dir_name.$nb) )
	{
		$nb += 1;
	}
	//echo "creating ".$base_work_dir.$desired_dir_name.$nb."#...";
	$perm = api_get_setting('permissions_for_new_directories');
	$perm = octdec(!empty($perm)?$perm:'0770');
	if ( mkdir($base_work_dir.$desired_dir_name.$nb, $perm))
	{
		chmod($base_work_dir.$desired_dir_name.$nb, $perm);
		return $desired_dir_name.$nb;
	}
	else
	{
	return false;
	}
}
/**
 * Delete a work-tool directory
 * @param	string	Base "work" directory for this course as /var/www/dokeos/courses/ABCD/work/
 * @param	string	The directory name as the bit after "work/", without trailing slash
 * @return	integer	-1 on error
 */
function del_dir($base_work_dir,$dir)
{
	if(empty($dir) or $dir=='/'){return -1;}//not authorized
	//escape hacks
/*
	$dir = str_replace('../','',$dir);
	$dir = str_replace('..','',$dir);
	$dir = str_replace('./','',$dir);
	$dir = str_replace('.','',$dir);
*/
	$check = Security::check_abs_path($base_work_dir.$dir,$base_work_dir);
	if (!$check || !is_dir($base_work_dir.$dir)) return -1;
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "DELETE FROM $table WHERE url LIKE BINARY 'work/".$dir."/%'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	
	//delete from DB the directories
	$sql = "DELETE FROM $table WHERE filetype = 'folder' AND url LIKE BINARY '/".$dir."%'";
	$res = api_sql_query($sql,__FILE__,__LINE__);	
		
	require_once(api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
	my_delete($base_work_dir.$dir);
}
/**
 * Get the path of a document in the student_publication table (path relative to the course directory)
 * @param	integer	Element ID
 * @return	string	Path (or -1 on error)
 */
function get_work_path($id){
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT * FROM $table WHERE id=$id";
	$res = api_sql_query($sql);
	if(Database::num_rows($res)!=1){
		return -1;
	}else{
		$row = Database::fetch_array($res);
		return $row['url'];
	}
}
/**
 * Update the url of a work in the student_publication table
 * @param	integer	ID of the work to update
 * @param	string	Destination directory where the work has been moved (must end with a '/')
 * @return	-1 on error, sql query result on success
 */
function update_work_url($id,$new_path)
{
	if(empty($id)) return -1;
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT * FROM $table WHERE id=$id";
	$res = api_sql_query($sql);
	if(Database::num_rows($res)!=1){
		return -1;
	}else{
		$row = Database::fetch_array($res);
		$filename = basename($row['url']);
		$new_url = $new_path.$filename;
		$sql2 = "UPDATE $table SET url = '$new_url' WHERE id=$id";
		$res2 = api_sql_query($sql2);
		return $res2;
	}
}

/**
 * Update the url of a dir in the student_publication table
 * @param	string old path
 * @param	string new path
 */
function update_dir_name($path, $new_name)
{
	global $base_work_dir; 
	
	include_once(api_get_path(LIBRARY_PATH) . "/fileManage.lib.php");
	include_once(api_get_path(LIBRARY_PATH) . "/fileUpload.lib.php");
	
	$path_to_dir = dirname($path);
	
	if($path_to_dir=='.') 
	{
		$path_to_dir = '';
	}
	else
	{
		$path_to_dir .= '/';
	}
	
	my_rename($base_work_dir.'/'.$path,$new_name);			
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

	//update all the files in the other directories according with the next query
	$sql = 'SELECT id, url FROM '.$table.' WHERE url LIKE BINARY "work/'.$path.'/%"'; // like binary (Case Sensitive) 
	
	$rs = api_sql_query($sql, __FILE__, __LINE__);		
	$work_len=strlen('work/'.$path);	
	while($work = Database :: fetch_array($rs))
	{		 				 
		$new_dir=$work['url'];		
		$name_with_directory=substr($new_dir,$work_len,strlen($new_dir));				
		$sql = 'UPDATE '.$table.' SET url="work/'.$path_to_dir.$new_name.$name_with_directory.'" WHERE id= '.$work['id'];		
		api_sql_query($sql, __FILE__, __LINE__);		
	}

	//update all the directory's children according with the next query 
	$sql = 'SELECT id, url FROM '.$table.' WHERE url LIKE BINARY "/'.$path.'%"';
	$rs = api_sql_query($sql, __FILE__, __LINE__);	
	$work_len=strlen('/'.$path);	
	while($work = Database :: fetch_array($rs))
	{		
		$new_dir=$work['url'];
		$name_with_directory=substr($new_dir,$work_len,strlen($new_dir));	
		$sql = 'UPDATE '.$table.' SET url="/'.$path_to_dir.$new_name.$name_with_directory.'" WHERE id= '.$work['id'];		
		api_sql_query($sql, __FILE__, __LINE__);		
	}	
}

/**
 * Return an array with all the folder's ids that are in the given path  
 * @param	string Path of the directory  
 * @return	array The list of ids of all the directories in the path 
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */
  
 function get_parent_directories($my_cur_dir_path)
{
			$list_parents = explode('/', $my_cur_dir_path);						
			$dir_acum = '';
			global $work_table;			
			$list_id=array();
			for ($i = 0; $i < count($list_parents) - 1; $i++)
			{			
				$where_sentence = "url  LIKE BINARY '" . $dir_acum . "/" . $list_parents[$i]."'";							
				$dir_acum .= '/' . $list_parents[$i];							
				$sql = "SELECT id FROM ". $work_table . " WHERE ". $where_sentence;								
				$result = api_sql_query($sql, __FILE__, __LINE__);
				$row= Database::fetch_array($result);									
				$list_id[]=$row['id'];	
			}			
			return $list_id;
}

/**
 * Transform an all directory structure (only directories) in an array 
 * @param	string path of the directory
 * @return	array the directory structure into an array
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */ 
function directory_to_array($directory)
{
	$array_items = array();
	if ($handle = opendir($directory)) 
	{
		while (false !== ($file = readdir($handle))) 
		{
			if ($file != "." && $file != "..") 
			{
				if (is_dir($directory. "/" . $file)) 
				{
					$array_items = array_merge($array_items, directory_to_array($directory. "/" . $file));					
					$file = $directory . "/" . $file;					
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				}	
			}
		}
		closedir($handle);
	}
	return $array_items;
}

/**
 * Insert into the DB of the course all the directories  
 * @param	string path of the /work directory of the course 
 * @return	-1 on error, sql query result on success
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */ 
 
function insert_all_directory_in_course_table($base_work_dir)
{	
	$dir_to_array =directory_to_array($base_work_dir,true);	
	$only_dir=array();	
	
	for($i=0;$i<count($dir_to_array);$i++)
	{
		$only_dir[]=substr($dir_to_array[$i],strlen($base_work_dir), strlen($dir_to_array[$i]));				
	}	
	/*	
	echo "<pre>";
	print_r($only_dir);
	echo "<pre>";
	*/
	for($i=0;$i<count($only_dir);$i++)
	{		
		global $work_table;
		$sql_insert_all= "INSERT INTO " . $work_table . " SET url = '" . $only_dir[$i] . "', " .
							  "title        = '',
			                   description 	= '',
			                   author      	= '',
							   active		= '0',
							   accepted		= '1',
							   filetype		= 'folder',
							   post_group_id = '0',
							   sent_date	= '0000-00-00 00:00:00' ";				  
		//api_sql_query($sql_insert_all, __FILE__, __LINE__);
	}	
}

/**
* This function displays the number of files contained in a directory
*
* @param	string the path of the directory 
* @param	boolean true if we want the total quantity of files include in others child directorys , false only  files in the directory		
* @return	array the first element is an integer with the number of files in the folder, the second element is the number of directories
* @author 	Julio Montoya Dokeos
* @version	April 2008
*/
function count_dir($path_dir, $recurse)
{
	$count = 0;
	$count_dir= 0;
    $d = dir($path_dir);   
    while ($entry = $d->Read())
    {    
    	if (!(($entry == "..") || ($entry == ".")))
		{		
        	if (is_dir($path_dir.'/'.$entry))
        	{       		
        		$count_dir++;
          		if ($recurse)
          		{
            		$count += count_dir($path_dir . '/' . $entry, $recurse);
          		}
          		
        	}
			else
        	{
        		$count++;
        	}
		}
	}
	$return_array=array();
	$return_array[]=$count;
	$return_array[]=$count_dir;	
    return $return_array;
}
?>
