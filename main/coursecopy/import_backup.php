<?php // $Id: import_backup.php 11374 2007-03-03 22:32:33Z yannoo $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)
	
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
 * ==============================================================================
 * Import a backup.
 * 
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 * ==============================================================================
 */
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
// name of the language file that needs to be included 
$language_file = array('coursebackup','admin');
include ('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
$nameTools = get_lang('ImportBackup');
$interbreadcrumb[] = array ("url" => "../course_info/maintenance.php", "name" => get_lang('Maintenance'));
Display::display_header($nameTools);
require_once('classes/CourseBuilder.class.php');
require_once('classes/CourseArchiver.class.php');
require_once('classes/CourseRestorer.class.php');
require_once('classes/CourseSelectForm.class.php');
api_display_tool_title($nameTools);
if( ! api_is_allowed_to_edit())
{
	api_not_allowed();	
}
/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 
if(  (isset($_POST['action']) && $_POST['action'] == 'course_select_form' ) || (isset($_POST['import_option']) && $_POST['import_option'] == 'full_backup' ) )
{
	if(isset($_POST['action']) && $_POST['action'] == 'course_select_form' )
	{
		$course = CourseSelectForm::get_posted_course();
	}
	else
	{
		if( $_POST['backup_type'] == 'server')
		{
			$filename = $_POST['backup_server'];	
			$delete_file = false;
		}
		else
		{
			$filename = CourseArchiver::import_uploaded_file($_FILES['backup']['tmp_name']);
			$delete_file = true;
		}
		$course = CourseArchiver::read_course($filename,$delete_file);
	}
	if( $course->has_resources())
	{
		$cr = new CourseRestorer($course);
		$cr->set_file_option($_POST['same_file_name_option']);
		$cr->restore();
		echo get_lang('ImportFinished');
	}
	else
	{
		echo get_lang('NoResourcesInBackupFile');
	}
	CourseArchiver::clean_backup_dir();
	echo '<p><a href="../course_home/course_home.php">&lt;&lt; '.get_lang('CourseHomepage').'</a></p>';
}
elseif( isset($_POST['import_option']) && $_POST['import_option'] == 'select_items')
{
	if( $_POST['backup_type'] == 'server')
	{
		$filename = $_POST['backup_server'];
		$delete_file = false;	
	}
	else
	{
		$filename = CourseArchiver::import_uploaded_file($_FILES['backup']['tmp_name']);
		$delete_file = true;
	}
	$course = CourseArchiver::read_course($filename,$delete_file);
	if( $course->has_resources())
	{
		CourseSelectForm::display_form($course,array('same_file_name_option'=>$_POST['same_file_name_option']));
	}
	else
	{
		echo get_lang('NoResourcesInBackupFile');	
		echo '<p><a href="../course_home/course_home.php">&lt;&lt; '.get_lang('CourseHomepage').'</a></p>';
	}
}
else
{
	$user = api_get_user_info();
	$backups = CourseArchiver::get_available_backups($is_platformAdmin?null:$user['user_id']);
	$backups_available = (count($backups)>0);
	?>
	<form method="post" action="import_backup.php" enctype="multipart/form-data" name="import_backup_form">
	<input type="hidden" name="action" value="restore_backup"/>
	<?php echo get_lang('SelectBackupFile') ?> 
	<br/><br/>
	<input type="radio" class="checkbox" name="backup_type" id="bt_local" value="local" checked="checked" onClick="javascript:document.import_backup_form.backup_server.disabled=true;document.import_backup_form.backup.disabled=false;">
	<label for="bt_local"><?php echo get_lang('LocalFile') ?></label>
	<br/>
	<blockquote>
	<input type="file" name="backup"/> (*.zip)
	</blockquote>
	<?php
	if( $backups_available )
	{
	?>
	<input type="radio" class="checkbox" name="backup_type" id="bt_server" value="server"  onClick="javascript:document.import_backup_form.backup_server.disabled=false;document.import_backup_form.backup.disabled=true;">
	<label for="bt_server"><?php echo get_lang('ServerFile') ?></label>
    <blockquote>
	<select name="backup_server">
	<option value="null">-</option>
	<?php
	// see line 117 $backups = CourseArchiver::get_available_backups($is_platformAdmin?null:$user['user_id']);
	foreach($backups as $index => $backup)
	{
		echo '<option value="'.$backup['file'].'">'.$backup['course_code'].' ('.$backup['date'].')</option>';	
	}
	?>
	</select>
	<script type="text/javascript">
  	document.import_backup_form.backup_server.disabled=true;
	</script>
	</blockquote>
	<?php
	}
	else
	{
		echo '<input type="radio" disabled="true"/>';
		echo '<i>'.get_lang('NoBackupsAvailable').'</i><br/><br/>';	
	}
	?>
	<input type="radio" class="checkbox" id="import_option_1" name="import_option" value="full_backup" checked="checked"/>
	<label for="import_option_1"><?php echo get_lang('ImportFullBackup') ?></label>
	<br/>
	<input type="radio" class="checkbox" id="import_option_2" name="import_option" value="select_items"/>
	<label for="import_option_2"><?php echo get_lang('LetMeSelectItems') ?></label>
	<br/>
	<br/>
	<?php echo get_lang('SameFilename') ?>
	<blockquote>
	<input type="radio" class="checkbox"  id="same_file_name_option_1" name="same_file_name_option" value="<?php echo FILE_SKIP ?>"/>
	<label for="same_file_name_option_1"><?php echo  get_lang('SameFilenameSkip') ?></label>
	<br/>
	<input type="radio" class="checkbox" id="same_file_name_option_2" name="same_file_name_option" value="<?php echo FILE_RENAME ?>"/>
	<label for="same_file_name_option_2"><?php echo get_lang('SameFilenameRename') ?></label>
	<br/>
	<input type="radio" class="checkbox"  id="same_file_name_option_3" name="same_file_name_option"  value="<?php echo FILE_OVERWRITE ?>"  checked="checked"/>
	<label for="same_file_name_option_3"><?php echo get_lang('SameFilenameOverwrite') ?></label>
	</blockquote>
	<br/>
	<input type="submit" value="<?php echo get_lang('ImportBackup') ?>"/>
	</form>
	<?php	
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>