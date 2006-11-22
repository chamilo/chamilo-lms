<?php // $Id: document.inc.php 10125 2006-11-22 17:30:33Z elixir_inter $

/*
==============================================================================
		EXTRA FUNCTIONS FOR DOCUMENT.PHP/UPLOAD.PHP
==============================================================================
/////////////////////////////////////////////////
//--> leave these here or move them elsewhere? //
/////////////////////////////////////////////////
*/


/**
 * Builds the form thats enables the user to 
 * select a directory to browse/upload in
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $group_dir
 * @return string html form
 */
function build_directory_selector($folders,$curdirpath,$group_dir='')
{
	$folder_titles = array();
	if(get_setting('use_document_title') == 'true')
	{
		$escaped_folders = $folders;
		array_walk($escaped_folders, 'mysql_real_escape_string');
		$folder_sql = implode("','",$escaped_folders);
		$doc_table = Database::get_course_table(DOCUMENT_TABLE);
		$sql = "SELECT * FROM $doc_table WHERE filetype='folder' AND path IN ('".$folder_sql."')";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$folder_titles = array();
		while($obj = mysql_fetch_object($res))
		{
			$folder_titles[$obj->path] = $obj->title;	
		}
	}
	else
	{
		foreach($folders as $folder)
		{
			$folder_titles[$folder] = basename($folder);	
		}	
	}
	$form = '<form name="selector" action="'.$_SERVER['PHP_SELF'].'" method="post">'."\n";
	$form .= get_lang('CurrentDirectory').' <select name="curdirpath" onchange="javascript:document.selector.submit()">'."\n";

	//group documents cannot be uploaded in the root
	if($group_dir=='') 
	{
		$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		if(is_array($folders))
		{
			foreach ($folders AS $folder)
			{
				$selected = ($curdirpath==$folder)?' selected="selected"':'';
				$path_parts = explode('/',$folder);
				$label = str_repeat('&nbsp;&nbsp;&nbsp;',count($path_parts)-2).' &mdash; '.$folder_titles[$folder];
				$form .= '<option'.$selected.' value="'.$folder.'">'.$label.'</option>'."\n";
			}
		}
	}
	else
	{
		foreach ($folders AS $folder)
		{
			$selected = ($curdirpath==$folder)?' selected="selected"':'';
			$label = $folder_titles[$folder];
			if( $folder == $group_dir)
			{
				$label = '/ ('.get_lang('Root').')';
			}
			else
			{
				$path_parts = explode('/',str_replace($group_dir,'',$folder));
				$label = str_repeat('&nbsp;&nbsp;&nbsp;',count($path_parts)-2).' &mdash; '.$label;			
			}
			$form .= '<option'.$selected.' value="'.$folder.'">'.$label.'</option>'."\n";
		}
	}

	$form .= '</select>'."\n";
	$form .= '<noscript><input type="submit" name="change_path" value="'.get_lang('Ok').'" /></noscript>'."\n";
	$form .= '</form>';

	return $form;
}


function display_document_options()
{
	$message = "<a href=\"quota.php\">".get_lang("ShowCourseQuotaUse")."</a>";
	echo 	/*"<div id=\"smallmessagebox\">"
			.*/ "<p>" . $message . "</p>"
			/*. "</div>"*/;
}

/**
 * Create a html hyperlink depending on if it's a folder or a file
 *
 * @param string $www
 * @param string $title
 * @param string $path
 * @param string $filetype (file/folder)
 * @param int $visibility (1/0)
 * @return string url
 */
function create_document_link($www,$title,$path,$filetype,$size,$visibility)
{
	global $dbl_click_id;
	if(isset($_SESSION['_gid']))
	{
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	}
	else 
	{
		$req_gid = '';
	}
	$url_path = urlencode($path);
	//add class="invisible" on invisible files
	$visibility_class= ($visibility==0)?' class="invisible"':'';
	//build download link (icon)
	$forcedownload_link=($filetype=='folder')?$_SERVER['PHP_SELF'].'?action=downloadfolder&amp;path='.$url_path.$req_gid:$_SERVER['PHP_SELF'].'?action=download&amp;id='.$url_path.$req_gid;
	//folder download or file download?
	$forcedownload_icon=($filetype=='folder')?'folder_zip.gif':'filesave.gif';
	//prevent multiple clicks on zipped folder download
	$prevent_multiple_click =($filetype=='folder')?" onclick=\"javascript:if(typeof clic_$dbl_click_id == 'undefined' || clic_$dbl_click_id == false) { clic_$dbl_click_id=true; window.setTimeout('clic_".($dbl_click_id++)."=false;',10000); } else { return false; }\"":'';
	$target='_top';
	if($filetype=='file') {
		//check the extension
		$ext=explode('.',$path);
		$ext=strtolower($ext[sizeof($ext)-1]);
		//"htmlfiles" are shown in a frameset
		if($ext == 'htm' || $ext == 'html' || $ext == 'gif' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'png')
		{
			$url = "showinframes.php?".api_get_cidreq()."&amp;file=".$url_path.$req_gid;
		}
		else 
		{
			//url-encode for problematic characters (we may not call them dangerous characters...)
			$path = str_replace('%2F', '/',$url_path).'?'.api_get_cidreq();
			$url=$www.$path;
		}
		//files that we want opened in a new window
		if($ext=='txt') //add here
		{
			$target='_blank';
		}
	}
	else {
		$url=$_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&amp;curdirpath='.$url_path.$req_gid;
	}
	//the little download icon
	$force_download_html = ($size==0)?'':'<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'><img src="'.api_get_path(WEB_CODE_PATH).'img/'.$forcedownload_icon.'" alt="" /></a>';
	
	$tooltip_title = str_replace('?cidReq='.$_GET['cidReq'],'',basename($path));
	return '<a href="'.$url.'" title="'.$tooltip_title.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.$title.'</a>'.$force_download_html;
}

/**
 * Builds an img html tag for the filetype
 *
 * @param string $type (file/folder)
 * @param string $path
 * @return string img html tag
 */
function build_document_icon_tag($type,$path)
{
	$icon='folder_document.gif';
	if($type=='file')
	{
		$icon=choose_image(basename($path));
	}
	return '<img src="'.api_get_path(WEB_CODE_PATH).'img/'.$icon.'" border="0" hspace="5" align="middle" alt="" />';
}

/**
 * Creates the row of edit icons for a file/folder
 *
 * @param string $curdirpath current path (cfr open folder)
 * @param string $type (file/folder)
 * @param string $path dbase path of file/folder
 * @param int $visibility (1/0)
 * @param int $id dbase id of the document
 * @return string html img tags with hyperlinks
 */
function build_edit_icons($curdirpath,$type,$path,$visibility,$id)
{
	if(isset($_SESSION['_gid']))
	{
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	}
	else 
	{
		$req_gid = '';
	}
	//build URL-parameters for table-sorting
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
	$visibility_icon = ($visibility==0)?'invisible':'visible';
	$visibility_command = ($visibility==0)?'set_visible':'set_invisible';
	$curdirpath = urlencode($curdirpath);
	
	$modify_icons = '<a href="edit_document.php?curdirpath='.$curdirpath.'&amp;file='.urlencode($path).$gid_req.'"><img src="../img/edit.gif" border="0" title="'.get_lang('Modify').'" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$gid_req.'&amp;'.$sort_params.'" onclick="return confirmation(\''.basename($path).'\');"><img src="../img/delete.gif" border="0" title="'.get_lang('Delete').'" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.$curdirpath.'&amp;move='.urlencode($path).$gid_req.'"><img src="../img/deplacer_fichier.gif" border="0" title="'.get_lang('Move').'" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?curdirpath='.$curdirpath.'&amp;'.$visibility_command.'='.$id.$gid_req.'&amp;'.$sort_params.'"><img src="../img/'.$visibility_icon.'.gif" border="0" title="'.get_lang('Visible').'" alt="" /></a>';
	return $modify_icons;
}


function build_move_to_selector($folders,$curdirpath,$move_file,$group_dir='')
{
	$form = '<form name="move_to" action="'.$_SERVER['PHP_SELF'].'" method="post">'."\n";
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
			foreach ($folders AS $folder)
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
		foreach ($folders AS $folder)
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
?>