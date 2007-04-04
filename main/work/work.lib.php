<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
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
function display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form)
{
	$display_output = "";
	if(strlen($cur_dir_path) > 0 && $cur_dir_path != '/')
	{
		$parent_dir = dirname($cur_dir_path);
		$display_output .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.$parent_dir.'">'.Display::return_icon('folder_up.gif').' '.get_lang('Up').'</a> ';
	}
	if (! $always_show_upload_form )
	{
		$display_output .= "<a href=\"".$_SERVER['PHP_SELF']."?curdirpath=".$cur_dir_path."&amp;display_upload_form=true&amp;origin=".$_GET['origin']."\">".Display::return_icon('submit_file.gif')." ". get_lang("UploadADocument") . "</a> ";
	}
	if (! $always_show_tool_options && api_is_allowed_to_edit() )
	{
		$display_output .=	"<a href=\"".$_SERVER['PHP_SELF']."?curdirpath=".$cur_dir_path."&amp;display_tool_options=true&amp;origin=".$_GET['origin']."\">".Display::return_icon('acces_tool.gif').' ' . get_lang("EditToolOptions") . "</a> ";
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
	$is_allowed_to_edit = api_is_allowed_to_edit();
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

	if (! $is_allowed_to_edit) return;

	echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?origin='.$origin.'&display_tool_options=true">';

	echo	"<br/><table class=\"data_table\">\n",
			"<tr><th>&nbsp;</th><th>".get_lang("Modify")."</th></tr><tr class=\"row_even\">\n",
			"<td>",
			get_lang('AllFiles')." : </td>",
			"<td><a href=\"".$_SERVER['PHP_SELF']."?".api_get_cidreq()."&amp;curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;delete=all&amp;display_tool_options=true\" ",
			"onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."')) return false;\">",
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
			echo	"<a href=\"".$_SERVER['PHP_SELF']."?".api_get_cidreq()."&curdirpath=".$cur_dir_path."&amp;origin=$origin&make_invisible=all&display_tool_options=true\">",
					"<img src=\"../img/visible.gif\" border=\"0\" alt=\"".get_lang('Invisible')."\" />",
					"</a>\n";
		}
		else
		{
			echo	"<a href=\"".$_SERVER['PHP_SELF']."?".api_get_cidreq()."&amp;curdirpath=".$cur_dir_path."&amp;origin=$origin&amp;make_visible=all&amp;display_tool_options=true\">",
					"<img src=\"../img/invisible.gif\" border=\"0\" alt=\"".get_lang('Visible')."\">",
					"</a>\n";
		}
	}

	echo "</td></tr>";

	display_default_visibility_form($uploadvisibledisabled);

	echo '</table>';

	echo '<div>'.get_lang("ValidateChanges").' : <input type="submit" name="changeProperties" value="'.get_lang("Ok").'" /></div></form>';

	echo	"<br/><table cellpadding=\"5\" cellspacing=\"2\" border=\"0\">\n";


	/*
	==============================================================================
			Display directories list
	==============================================================================
	*/
	//$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit || $group_member_with_upload_rights);
	if($cur_dir_path=='/'){$my_cur_dir_path='';}else{$my_cur_dir_path=$cur_dir_path;}
	$folders = get_subdirs_list($base_work_dir,1);
	echo '<div id="folderselector">';
	echo(build_directory_selector($folders,$cur_dir_path,''));
	echo '</div>';
	echo '</td></tr><tr><td>';
	if ($cur_dir_path!= '/' && $cur_dir_path!=$group_properties['directory'])
	{
		echo '<a href="'.$_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&curdirpath='.urlencode((dirname($cur_dir_path)=='\\')?'/':dirname($cur_dir_path)).'">'.
				'<img src="../img/parent.gif" border="0" align="absbottom" hspace="5" alt="" />'.
				get_lang("Up").'</a>&nbsp;'."\n";
}
	echo '<!-- create directory -->' .
			'<a href="'.$_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&amp;curdirpath='.$cur_dir_path.'&amp;createdir=1"><img src="../img/folder_new.gif" border="0"alt ="" /></a>'.
			'<a href="'.$_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&amp;curdirpath='.$cur_dir_path.'&amp;createdir=1">'.get_lang("CreateDir").'</a>&nbsp;'."\n";

	echo "</td></tr></table>";
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
	<tr class="row_odd"><td align="left">
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
* Display the list of student publications, taking into account the user status
*
* @param $currentCourseRepositoryWeb, the web location of the course folder
* @param $link_target_parameter - should there be a target parameter for the links
* @param $dateFormatLong - date format
* @param $origin - typically empty or 'learnpath'
*/
function display_student_publications_list($work_dir,$sub_course_dir,$currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin)
{
	// Database table names
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

	if(substr($sub_course_dir,-1,1)!='/' && !empty($sub_course_dir))
	{
		$sub_course_dir = $sub_course_dir.'/';
	}
	if($sub_course_dir == '/')
	{
		$sub_course_dir='';
	}

	//Get list from database
	if($is_allowed_to_edit)
	{
		$sql_get_publications_list = 	"SELECT * " .
										"FROM  ".$work_table." " .
										"WHERE url LIKE '$sub_course_dir%' " .
										"AND url NOT LIKE '$sub_course_dir%/%' " .
		                 				"ORDER BY id";
	}
	else
	{
		if (!empty($_SESSION['toolgroup']))
		{
			$group_query = " WHERE post_group_id = '".$_SESSION['toolgroup']."' "; // set to select only messages posted by the user's group
		}
		else
		{
			$group_query = '';
		}
		$sql_get_publications_list =	"SELECT * FROM  $work_table $group_query ORDER BY id";
	}
	//echo $sql_get_publications_list;
	$sql_result = api_sql_query($sql_get_publications_list,__FILE__,__LINE__);

	$table_header[] = array(get_lang('Title'),true);
	$table_header[] = array(get_lang('Description'),true);
	$table_header[] = array(get_lang('Authors'),true);
	$table_header[] = array(get_lang('Date'),true);
	//if( $is_allowed_to_edit)
	//{
		$table_header[] = array(get_lang('Modify'),true);
	//}
	$table_data = array();

	$dirs_list = get_subdirs_list($work_dir);

	$my_sub_dir = str_replace('work/','',$sub_course_dir);
	foreach($dirs_list as $dir)
	{
		$mydir = $my_sub_dir.$dir;
		$action = '';
		//display info depending on the permissions
	if( $is_allowed_to_edit)
	{
			$row = array();
			$class = '';
			$url = implode("/", array_map("rawurlencode", explode("/", $work->url)));
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.api_get_course_id().
				'&curdirpath='.$mydir.'"'.$class.'><img src="../img/folder_document.gif" alt="dir" height="20" width="20" align="absbottom"/>&nbsp;'.$dir.'</a>';
			$row[] = '';
			$row[] = '';
			$row[] = '';
			if( $is_allowed_to_edit)
			{
				//$action .= '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.api_get_course_id().
				//	'&edit_dir='.$mydir.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?'.
					'delete_dir='.$mydir.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" alt="'.get_lang('DirDelete').'"></a>';
				$row[] = $action;
			}else{
				$row[] = "";
	}
			$table_data[] = $row;
		}
	}
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
				$action = '';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;edit='.$work->id.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'"></a>';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;move='.$work->id.'"><img src="../img/deplacer_fichier.gif" border="0" title="'.get_lang('Move').'" alt="" /></a>';
				if($work->accepted == '1')
				{
					$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;make_invisible='.$work->id.'&amp;'.$sort_params.'"><img src="../img/visible.gif" alt="'.get_lang('Invisible').'"></a>';
				}
				else
				{
					$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;make_visible='.$work->id.'&amp;'.$sort_params.'"><img src="../img/invisible.gif" alt="'.get_lang('Visible').'"></a>';
				}

				$row[] = $action;
			}elseif($is_author){
				$action = '';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;edit='.$work->id.'"><img src="../img/edit.gif" alt="'.get_lang('Modify').'"></a>';
				$action .= '<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.urlencode($my_sub_dir).'&amp;origin='.$origin.'&amp;delete='.$work->id.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" alt="'.get_lang('WorkDelete').'"></a>';

				$row[] = $action;
			}else{
				$row[] = " ";
			}
			$table_data[] = $row;
		}
	}
	//if( count($table_data) > 0)
	//{
		Display::display_sortable_table($table_header,$table_data);
	//}
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
function build_directory_selector($folders,$curdirpath,$group_dir='')
{
	$form = '<form name="selector" action="'.$_SERVER['PHP_SELF'].'?'.api_get_cidreq().'" method="POST">'."\n";
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
function build_move_to_selector($folders,$curdirpath,$move_file,$group_dir='')
{
	$form = '<form name="move_to" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
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
	if ( mkdir($base_work_dir.$desired_dir_name.$nb, 0777))
	{
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
function del_dir($base_work_dir,$dir){
	if(empty($dir) or $dir=='/'){return -1;}//not authorized
	//escape hacks
	$dir = str_replace('../','',$dir);
	$dir = str_replace('..','',$dir);
	$dir = str_replace('./','',$dir);
	$dir = str_replace('.','',$dir);
	if(!is_dir($base_work_dir.$dir)) {return -1;}
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "DELETE FROM $table WHERE url LIKE 'work/".$dir."/%'";
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
function update_work_url($id,$new_path){
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
?>