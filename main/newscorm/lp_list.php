<?php //$id:$
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2004 Denes Nagy
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* This file was origially the copy of document.php, but many modifications happened since then ;
* the direct file view is not any more needed, if the user uploads a scorm zip file, a directory
* will be automatically created for it, and the files will be uncompressed there for example ;
*
* @package dokeos.learnpath
* @author Yannick Warnier <ywarnier@beeznest.org>
==============================================================================
*/
/**
 * Script
 */

if(empty($lp_controller_touched) || $lp_controller_touched!=1){
	header('location: lp_controller.php?action=list');
}

require_once('back_compat.inc.php');
$courseDir   = api_get_course_path().'/scorm';
$baseWordDir = $courseDir;
$display_progress_bar = true;

require_once('learnpathList.class.php');
require_once('learnpath.class.php');
require_once('learnpathItem.class.php');
//$charset = 'UTF-8';
//$charset = 'ISO-8859-1';

/**
 * Display initialisation and security checks
 */
//extra javascript functions for in html head:
$htmlHeadXtra[] =
"<script language='javascript' type='text/javascript'>

function confirmation(name)
{
	if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." \"+name+\"?\"))
		{return true;}
	else
		{return false;}
}
</script>";
$nameTools = get_lang(ucfirst(TOOL_LEARNPATH));
event_access_tool(TOOL_LEARNPATH);

if (! $is_allowed_in_course) api_not_allowed();

/**
 * Display
 */
/* Require the search widget and prepare the header with its stuff */
if (api_get_setting('search_enabled') == 'true') {
  require api_get_path(LIBRARY_PATH).'search/search_widget.php';
  search_widget_prepare(&$htmlHeadXtra);
}
Display::display_header($nameTools,"Path");
//api_display_tool_title($nameTools);

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

// These settings should stay here.
// TODO: Here a calculation mechanism has to be added in order the editor to work
// properly with documents that are not at the root of the repository.
$fck_attribute['Config']['CreateDocumentWebDir'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/';
$fck_attribute['Config']['CreateDocumentDir'] = '../../courses/'.api_get_course_path().'/document/';
$fck_attribute['Config']['BaseHref'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/';

Display::display_introduction_section(TOOL_LEARNPATH);

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

if(api_is_allowed_to_edit())
{


  /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/

  if (!empty($dialog_box))
  {
    Display::display_normal_message($dialog_box);
  }
  if (api_failure::get_last_failure())
  {
    Display::display_normal_message(api_failure::get_last_failure());
  }

  //include('content_makers.inc.php');
  echo '<div class="actions">';
  echo	'<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add_lp">'.
		'<img src="../img/wizard.gif" border="0" align="absmiddle" alt="'.get_lang('_add_learnpath').
		'">&nbsp;'.get_lang('_add_learnpath').
		'</a>' .
		str_repeat('&nbsp;',3).
		'<a href="../upload/index.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH.'"><img src="../img/file_zip.gif" border="0" alt="'.get_lang("UploadScorm").'" align="absmiddle">&nbsp;'.get_lang("UploadScorm").'</a>';
		if (api_get_setting('service_ppt2lp', 'active') == 'true')
		{
			echo  str_repeat('&nbsp;',3).'<a href="../upload/upload_ppt.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH.'"><img src="../img/powerpoint.gif" border="0" alt="'.get_lang("PowerPointConvert").'" align="absmiddle">&nbsp;'.get_lang("PowerPointConvert").'</a>';
       		echo  str_repeat('&nbsp;',3).'<a href="../upload/upload_word.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH.'"><img src="../img/word.gif" border="0" alt="'.get_lang("WordConvert").'" align="absmiddle">&nbsp;'.get_lang("WordConvert").'</a>';
        }
	echo '</div>';
			
       
}

echo '<table width="100%" border="0" cellspacing="2" class="data_table">';
api_is_allowed_to_edit() ? $colspan = 9 : $colspan = 3;

/*
if ($curDirName) // if the $curDirName is empty, we're in the root point and we can't go to a parent dir
{
  ?>
  <!-- parent dir -->
  <a href="<?php echo api_get_self().'?'.api_get_cidreq().'&openDir='.$cmdParentDir.'&subdirs=yes'; ?>">
  <img src="../img/parent.gif" border="0" align="absbottom" hspace="5" alt="parent" />
  <?php echo get_lang("Up"); ?></a>&nbsp;
  <?php
}
*/
if (!empty($curDirPath))
{
  if(substr($curDirPath,1,1)=='/'){
  	$tmpcurDirPath=substr($curDirPath,1,strlen($curDirPath));
  }else{
  	$tmpcurDirPath = $curDirPath;
  }
  ?>
  <!-- current dir name -->
  <tr>
    <td colspan="<?php echo $colspan ?>" align="left" bgcolor="#4171B5">
      <img src="../img/opendir.gif" align="absbottom" vspace="2" hspace="3" alt="open_dir" />
      <font color="#ffffff"><b><?php echo $tmpcurDirPath ?></b></font>
    </td>
  </tr>
  <?php
}

/* CURRENT DIRECTORY */

echo	'<tr>';
echo	'<th>'.get_lang('Name').'</th>'."\n" .
		'<th>'.get_lang('Progress')."</th>\n";
if (api_is_allowed_to_edit())
{
  echo '<th>'.get_lang('Description')."</th>\n" .
  		//xport now is inside "Edit"
  		//'<th>'.get_lang('ExportShort')."</th>\n" .
		'<th>'.get_lang('Modify')."</th>\n" .
		'<th>'.get_lang('Move')."</th>\n";
}

echo		"</tr>\n";

/*--------------------------------------
	  DISPLAY SCORM LIST
  --------------------------------------*/
$list = new LearnpathList(api_get_user_id());
$flat_list = $list->get_flat_list();
$is_allowed_to_edit = api_is_allowed_to_edit();
$test_mode = api_get_setting('server_type');
$max = count($flat_list);
//var_dump($flat_list);
if (is_array($flat_list))
{
	$counter = 0;
	$current = 0;
	foreach ($flat_list as $id => $details)
	{
	    if(!$is_allowed_to_edit && $details['lp_visibility'] == 0)
	    {
	    	// This is a student and this path is invisible, skip
	    	continue;		
	    }
		$counter++;
	    if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }

		$url_start_lp = 'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$id;
		$name = $details['lp_name'];
		$image='<img src="../img/kcmdf.gif" border="0" align="absmiddle" alt="' . $name . '">'."\n";
	    $dsp_line =	'<tr align="center" class="'.$oddclass.'">'."\n" .
        	'<td align="left" valign="top">' .
			'<div style="float: left; width: 35px; height: 22px;"><a href="'.$url_start_lp.'">' .
			$image . '</a></div><a href="'.$url_start_lp.'">' . $name . '</a>' .
			"</td>\n";
	    //$dsp_desc='<td>'.$details['lp_desc'].'</td>'."\n";
	    $dsp_desc = '';

	    $dsp_export = '';
	    $dsp_edit = '';
	    $dsp_edit_close = '';
	    $dsp_delete = '';
	    $dsp_visible = '';
	    $dsp_default_view = '';
	    $dsp_debug = '';
	    $dsp_order = '';
	    
	    // Select course theme	
		if (!empty($platform_theme))
		{	
			$mystyle=$platform_theme;
		}
		
		if (!empty($user_theme)) 
		{	 
			$mystyle=$user_theme;
		}		
				
		if (!empty($mycoursetheme)) 
		{	 
			$mystyle=$mycoursetheme;
		}
		
		$lp_theme_css=$mystyle;
		
	    
	    if($display_progress_bar)
	    {
	    	$dsp_progress = '<td>'.learnpath::get_progress_bar('%',learnpath::get_db_progress($id,api_get_user_id()),'').'</td>';
	    }
	    else
	    {
			$dsp_progress = '<td style="padding-top:1em;">'.learnpath::get_db_progress($id,api_get_user_id(),'both').'</td>';
	    }
	    if($is_allowed_to_edit)
	    {
		    $dsp_desc = '<td valign="middle" style="color: grey; padding-top:1em;"><em>'.$details['lp_maker'].'</em>  &nbsp;&nbsp; '.$details['lp_proximity'].' &nbsp;&nbsp; '.$details['lp_encoding'].'<a href="lp_controller.php?'.api_get_cidreq().'&action=edit&lp_id='.$id.'">&nbsp;&nbsp;<img src="../img/edit.gif" border="0" title="'.get_lang('_edit_learnpath').'"></a></td>'."\n";

			/* export */
			//Export is inside "Edit"
			//export not available for normal lps yet
			/*if($details['lp_type']==1){
				$dsp_export = '<td align="center">' .
					"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id'>" .
					"<img src=\"../img/cd.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
					"</a>" .
					"";
			}elseif($details['lp_type']==2){
				$dsp_export = '<td align="center">' .
					"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id&export_name=".replace_dangerous_char($name,'strict').".zip'>" .
					"<img src=\"../img/cd.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
					"</a>" .
					"";
			}else{
				$dsp_export = '<td align="center">' .
					//"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id'>" .
					"<img src=\"../img/cd_gray.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
					//"</a>" .
					"";
			}*/
			/* edit title and description */

			$dsp_edit = '<td align="center">';
	    	$dsp_edit_close = '</td>';
			
			/* DELETE COMMAND */
			$dsp_delete = "<a href=\"lp_controller.php?".api_get_cidreq()."&action=delete&lp_id=$id\" " .
			"onClick=\"return confirmation('".addslashes($name)."');\">" .
			"<img src=\"../img/delete.gif\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" />" .
			"</a>";

			/* VISIBILITY COMMAND */

			if ($details['lp_published'] == "i")
			{
		        $dsp_publish =	"<a href=\"".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=v\">" .
				"<img src=\"../img/invisible_LP_list.gif\" border=\"0\" title=\"".get_lang('_publish')."\" />" .
				"</a>" .
				"";
			}
			else
			{
				$dsp_publish =	"<a href='".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=i'>" .
				"<img src=\"../img/visible_LP_list.gif\" border=\"0\" title=\"".get_lang('_no_publish')."\" />" .
				"</a>".
				"";
			}
			/* VISIBILITY COMMAND */

			if ($details['lp_visibility'] == 0)
			{
		        $dsp_visible =	"<a href=\"".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=1\">" .
				"<img src=\"../img/invisible.gif\" border=\"0\" title=\"".get_lang('Show')."\" />" .
				"</a>" .
				"";
			}
			else
			{
				$dsp_visible =	"<a href='".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=0'>" .
				"<img src=\"../img/visible.gif\" border=\"0\" title=\"".get_lang('Hide')."\" />" .
				"</a>".
				"";
			}
			/* Default view mode settings (fullscreen/embedded) */
			if($details['lp_view_mode'] == 'fullscreen'){
				$dsp_default_view = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_view_mode&lp_id='.$id.'">' .
						'<img src="../img/view_fullscreen.gif" border="0" alt="'.get_lang("ViewModeEmbedded").'" title="'.get_lang("ViewModeEmbedded").'"/>' .
						'</a>&nbsp;';
			}else{
				$dsp_default_view = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_view_mode&lp_id='.$id.'">' .
						'<img src="../img/view_choose.gif" border="0" alt="'.get_lang("ViewModeFullScreen").'" title="'.get_lang("ViewModeFullScreen").'"/>' .
						'</a>&nbsp;';
			}
			/* Increase SCORM recording */
			/*
			if($details['lp_force_commit'] == 1){
				$dsp_force_commit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_force_commit&lp_id='.$id.'">' .
						'<img src="../img/clock.gif" border="0" alt="Normal SCORM recordings" title="'.get_lang("MakeScormRecordingNormal").'"/>' .
						'</a>&nbsp;';
			}else{
				$dsp_force_commit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_force_commit&lp_id='.$id.'">' .
						'<img src="../img/clock_gray.gif" border="0" alt="Extra SCORM recordings" title="'.get_lang("MakeScormRecordingExtra").'"/>' .
						'</a>&nbsp;';
			}
			*/
			if($details['lp_prevent_reinit']==1){
				$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
						'<img src="../img/kaboodleloop_gray.gif" border="0" alt="Allow reinit" title="'.get_lang("AllowMultipleAttempts").'"/>' .
						'</a>&nbsp;';
			}else{
				$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
						'<img src="../img/kaboodleloop.gif" border="0" alt="Prevent reinit" title="'.get_lang("PreventMultipleAttempts").'"/>' .
						'</a>&nbsp;';
			}
			if($details['lp_type']==1 || $details['lp_type']==2){
				$dsp_build = '<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=build&amp;lp_id='.$id.'"><img src="../img/wizard.gif" border="0" title="'.get_lang("Build").'"></a>&nbsp;';
			}else{
				$dsp_build = '<img src="../img/wizard_gray.gif" border="0" title="'.get_lang("Build").'">&nbsp;';
			}
			if($test_mode == 'test' or api_is_platform_admin())
			{
				if($details['lp_scorm_debug']==1){
					$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
							'<img src="../img/bug.gif" border="0" alt="'.get_lang("HideDebug").'" title="'.get_lang("HideDebug").'"/>' .
							'</a>&nbsp;';
				}else{
					$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
							'<img src="../img/bug_gray.gif" border="0" alt="'.get_lang("ShowDebug").'" title="'.get_lang("ShowDebug").'"/>' .
							'</a>&nbsp;';
				}
		 	}
		 	/*   Export  */
	    	if($details['lp_type']==1){
				$dsp_disk = 
					"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id'>" .
					"<img src=\"../img/cd.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
					"</a>" .
					"";
			}elseif($details['lp_type']==2){
				$dsp_disk = 
					"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id&export_name=".replace_dangerous_char($name,'strict').".zip'>" .
					"<img src=\"../img/cd.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
					"</a>" .
					"";
			}else{
				$dsp_disk = 
					//"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id'>" .
					"<img src=\"../img/cd_gray.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
					//"</a>" .
					"";
			}
			if($details['lp_display_order'] == 1 && $max != 1)
	    	{
	    		$dsp_order .= '<td><a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_down&lp_id='.$id.'">' .
	    				'<img src="../img/arrow_down_0.gif" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
	    				'</a><img src="../img/blanco.png" border="0" alt="" title="" /></td>';
	    	}
	    	elseif($current == $max-1 && $max != 1) //last element
	    	{
	    		$dsp_order .= '<td><img src="../img/blanco.png" border="0" alt="" title="" /><a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_up&lp_id='.$id.'">' .
	    				'<img src="../img/arrow_up_0.gif" border="0" alt="'.get_lang("MoveUp").'" title="'.get_lang("MoveUp").'"/>' .
	    				'</a></td>';
	    	}
	    	elseif($max == 1)
	    	{
	    		$dsp_order = '<td></td>';
	    	}
	    	else
	    	{
	    		$dsp_order .= '<td><a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_down&lp_id='.$id.'">' .
	    				'<img src="../img/arrow_down_0.gif" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
	    				'</a>&nbsp;';
	    		$dsp_order .= '<a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_up&lp_id='.$id.'">' .
	    				'<img src="../img/arrow_up_0.gif" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
	    				'</a></td>';
	    	}
	    }	// end if($is_allowedToEdit)
	    //echo $dsp_line.$dsp_desc.$dsp_export.$dsp_edit.$dsp_delete.$dsp_visible;
	    echo $dsp_line.$dsp_progress.$dsp_desc.$dsp_export.$dsp_edit.$dsp_build.$dsp_visible.$dsp_publish.$dsp_reinit.$dsp_default_view.$dsp_debug.$dsp_delete.$dsp_disk.$dsp_order;
	    //echo $dsp_line.$dsp_progress.$dsp_desc.$dsp_export.$dsp_edit.$dsp_build.$dsp_visible.$dsp_reinit.$dsp_force_commit.$dsp_delete;
	    echo	"</tr>\n";
		$current ++; //counter for number of elements treated
	}	// end foreach ($flat_list)
	//TODO print some user-friendly message if counter is still = 0 to tell nothing can be displayd yet
}// end if ( is_array($flat_list)
echo "</table>";
echo "<br/><br/>";

/*
==============================================================================
  FOOTER
==============================================================================
*/
Display::display_footer();
?>
