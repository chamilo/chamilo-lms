<?php //$id:$
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2006 Dokeos S.A.
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

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
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
function confirmation (name)
{
	if (confirm(\" ".get_lang('AreYouSureToDelete')." \"+ name + \" ?\"))
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
Display::display_header($nameTools,"Path");
//api_display_tool_title($nameTools);

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_LEARNPATH);

if(api_is_allowed_to_edit())
{


  /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/

  if ($dialog_box)
  {
    Display::display_normal_message($dialog_box);
  }
  if (api_failure::get_last_failure())
  {
    Display::display_normal_message(api_failure::get_last_failure());
  }

  //include('content_makers.inc.php');
  echo	'<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add_lp">'.
		'<img src="../img/wizard.gif" border="0" align="absmiddle" alt="scormbuilder">&nbsp;'.get_lang('_add_learnpath').
		'</a>' .
		str_repeat('&nbsp;',3).
		'<a href="../upload/index.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH.'"><img src="../img/file_zip.gif" border="0" alt="scormbuilder" align="absmiddle">&nbsp;'.get_lang("UploadScorm").'</a>';
		if(api_get_setting('service_ppt2lp','active')==true)
		{
			echo  str_repeat('&nbsp;',3).'<a href="../upload/upload_ppt.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH.'"><img src="../img/powerpoint.gif" border="0" alt="scormbuilder" align="absmiddle">&nbsp;'.get_lang("PowerPointConvert").'</a>';
       }
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
if ($curDirPath)
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
echo	'<th>'.get_lang("Name").'</th>'."\n" .
		'<th>'.get_lang("progress")."</th>\n";
if (api_is_allowed_to_edit())
{
  echo "<th>",get_lang("Description"),"</th>\n" .
  		"<th>",get_lang("ExportShort"),"</th>\n",
	'<th>',get_lang("Modify"),"</th>\n";
}

echo		"</tr>\n";

/*--------------------------------------
	  DISPLAY SCORM LIST
  --------------------------------------*/
$list = new LearnpathList(api_get_user_id());
$flat_list = $list->get_flat_list();
$is_allowed_to_edit = api_is_allowed_to_edit();
//var_dump($flat_list);
if (is_array($flat_list))
{
	$counter = 0;
	foreach ($flat_list as $id => $details)
	{
	    if(!$is_allowed_to_edit && $details['lp_visibility'] == "i")
	    {
	    	// This is a student and this path is invisible, skip
	    	continue;		
	    }
		$counter++;
	    if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }

		$url_start_lp = 'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$id;
		$name = $details['lp_name'];
		$image='<img src="../img/kcmdf.gif" border="0" align="absmiddle" alt="scorm">'."\n";
	    $dsp_line =	'<tr align="center" class="'.$oddclass.'">'."\n" .
        	'<td align="left" valign="top">' .
			'<div style="float: left; width: 35px; height: 22px;"><a href="'.$url_start_lp.'" '.$style.'>' .
			$image . '</a></div><a href="'.$url_start_lp.'" '.$style.'>' . $name . '</a>' .
			"</td>\n";
	    //$dsp_desc='<td>'.$details['lp_desc'].'</td>'."\n";
	    $dsp_desc = '';

	    $dsp_export = '';
	    $dsp_edit = '';
	    $dsp_delete = '';
	    $dsp_visible = '';
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
			$fileExtension=explode('.',$dspFileName);
			$fileExtension=strtolower($fileExtension[sizeof($fileExtension)-1]);

			/* export */
			//export not available for normal lps yet
			if($details['lp_type']==1){
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
			}
			/* edit title and description */

			$dsp_edit = '<td align="center">';

			/* DELETE COMMAND */
			$dsp_delete = "<a href=\"lp_controller.php?".api_get_cidreq()."&action=delete&lp_id=$id\" " .
			"onClick=\"return confirmation('".addslashes($dspFileName)."');\">" .
			"<img src=\"../img/delete.gif\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" />" .
			"</a>";

			/* VISIBILITY COMMAND */

			if ($details['lp_visibility'] == "i")
			{
		        $dsp_visible =	"<a href=\"".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=v\">" .
				"<img src=\"../img/invisible_LP_list.gif\" border=\"0\" title=\"".get_lang('_publish')."\" />" .
				"</a>" .
				"";
			}
			else
			{
				$dsp_visible =	"<a href='".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=i'>" .
				"<img src=\"../img/visible_LP_list.gif\" border=\"0\" title=\"".get_lang('_no_publish')."\" />" .
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
			if($details['lp_force_commit'] == 1){
				$dsp_force_commit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_force_commit&lp_id='.$id.'">' .
						'<img src="../img/clock.gif" border="0" alt="Normal SCORM recordings" title="'.get_lang("MakeScormRecordingNormal").'"/>' .
						'</a>&nbsp;';
			}else{
				$dsp_force_commit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_force_commit&lp_id='.$id.'">' .
						'<img src="../img/clock_gray.gif" border="0" alt="Extra SCORM recordings" title="'.get_lang("MakeScormRecordingExtra").'"/>' .
						'</a>&nbsp;';
			}
			if($details['lp_prevent_reinit']==1){
				$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
						'<img src="../img/kaboodleloop.gif" border="0" alt="Allow reinit" title="'.get_lang("AllowMultipleAttempts").'"/>' .
						'</a>&nbsp;';
			}else{
				$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
						'<img src="../img/kaboodleloop_gray.gif" border="0" alt="Prevent reinit" title="'.get_lang("PreventMultipleAttempts").'"/>' .
						'</a>&nbsp;';
			}
			if($details['lp_type']==1 || $details['lp_type']==2){
				$dsp_build = '<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=build&amp;lp_id='.$id.'"><img src="../img/wizard.gif" border="0" title="'.get_lang("Build").'"></a>&nbsp;';
			}else{
				$dsp_build = '<img src="../img/wizard_gray.gif" border="0" title="'.get_lang("build").'">&nbsp;';
			}
			if($details['lp_scorm_debug']==1){
				$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
						'<img src="../img/bug.gif" border="0" alt="'.get_lang("HideDebug").'" title="'.get_lang("HideDebug").'"/>' .
						'</a>&nbsp;';
			}else{
				$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
						'<img src="../img/bug_gray.gif" border="0" alt="'.get_lang("ShowDebug").'" title="'.get_lang("ShowDebug").'"/>' .
						'</a>&nbsp;';
			}

	    }	// end if($is_allowedToEdit)
	    //echo $dsp_line.$dsp_desc.$dsp_export.$dsp_edit.$dsp_delete.$dsp_visible;
	    //echo $dsp_line.$dsp_progress.$dsp_desc.$dsp_export.$dsp_edit.$dsp_build.$dsp_visible.$dsp_reinit.$dsp_default_view.$dsp_force_commit.$dsp_debug.$dsp_delete;
	    echo $dsp_line.$dsp_progress.$dsp_desc.$dsp_export.$dsp_edit.$dsp_build.$dsp_visible.$dsp_reinit.$dsp_force_commit.$dsp_delete;
	    echo	"</tr>\n";

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
