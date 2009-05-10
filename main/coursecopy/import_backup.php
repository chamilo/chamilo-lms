<?php // $Id: import_backup.php 20448 2009-05-10 10:17:22Z ivantcholakov $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2008 Dokeos SPRL
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
	
	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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

// including the global file
include ('../inc/global.inc.php');

// Check access rights (only teachers are allowed here)
if( ! api_is_allowed_to_edit())
{
	api_not_allowed(true);	
}

//remove memory and time limits as much as possible as this might be a long process...
if(function_exists('ini_set'))
{
	ini_set('memory_limit','256M');
	ini_set('max_execution_time',1800);
}

// section for the tabs
$this_section=SECTION_COURSES;

// breadcrumbs
$interbreadcrumb[] = array ("url" => "../course_info/maintenance.php", "name" => get_lang('Maintenance'));

// Displaying the header
$nameTools = get_lang('ImportBackup');
Display::display_header($nameTools);

// include additional libraries
include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
require_once('classes/CourseBuilder.class.php');
require_once('classes/CourseArchiver.class.php');
require_once('classes/CourseRestorer.class.php');
require_once('classes/CourseSelectForm.class.php');

// Display the tool title
api_display_tool_title($nameTools);


/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 

if((isset($_POST['action']) && $_POST['action'] == 'course_select_form' ) || (isset($_POST['import_option']) && $_POST['import_option'] == 'full_backup' )) {
	$error=false;	
	if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {
		// partial backup here we recover the documents posted
		$course = CourseSelectForm::get_posted_course();
						
	} else {
		if( $_POST['backup_type'] == 'server') {
			$filename = $_POST['backup_server'];	
			$delete_file = false;
		} else {
			if($_FILES['backup']['error']==0){
				$filename = CourseArchiver::import_uploaded_file($_FILES['backup']['tmp_name']);
				if ($filename === false) {
                	$error = true;
                } else                {
                    $delete_file = true;
                }
			} else {
				$error=true;
			}
		}
        if(!$error) {
		  // full backup
		  $course = CourseArchiver::read_course($filename,$delete_file);
        }
	}
	
	if(!$error && $course->has_resources()) {		
		$cr = new CourseRestorer($course);		
		$cr->set_file_option($_POST['same_file_name_option']);
		$cr->restore();
		Display::display_normal_message(get_lang('ImportFinished').
                //'<a class="bottom-link" href="../course_home/course_home.php?'.api_get_cidreq().'">&gt;&gt; '.get_lang('CourseHomepage').'</a>',false); // This is not the preferable way to go to the course homepage.
                '<a class="bottom-link" href="'.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/index.php">&lt;&lt; '.get_lang('CourseHomepage').'</a>',false);
	} else {
		if(!$error){
			Display::display_warning_message(get_lang('NoResourcesInBackupFile').
                '<a class="bottom-link" href="import_backup.php?'.api_get_cidreq().'">&lt;&lt; '.get_lang('TryAgain').'</a>',false);
		} elseif ($filename === false) {
            Display::display_error_message(get_lang('ArchivesDirectoryNotWriteableContactAdmin').
                '<a class="bottom-link" href="import_backup.php?'.api_get_cidreq().'">&lt;&lt; '.get_lang('TryAgain').'</a>',false);
        } else {
			Display::display_error_message(api_ucfirst(get_lang('UploadError')).
                '<a class="bottom-link" href="import_backup.php?'.api_get_cidreq().'">&lt;&lt; '.get_lang('TryAgain').'</a>',false);
		}
	}
	CourseArchiver::clean_backup_dir();
} elseif (isset($_POST['import_option']) && $_POST['import_option'] == 'select_items') {	
	if( $_POST['backup_type'] == 'server') {
		$filename = $_POST['backup_server'];
		$delete_file = false;	
	} else {
		$filename = CourseArchiver::import_uploaded_file($_FILES['backup']['tmp_name']);
		$delete_file = true;
	}
	$course = CourseArchiver::read_course($filename,$delete_file);	
	if ($course->has_resources() && ($filename !== false)) {
		CourseSelectForm::display_form($course,array('same_file_name_option'=>$_POST['same_file_name_option']));
	} elseif ($filename === false) {
    	Display::display_error_message(get_lang('ArchivesDirectoryNotWriteableContactAdmin').
                '<a class="bottom-link" href="import_backup.php?'.api_get_cidreq().'">&lt;&lt; '.get_lang('TryAgain').'</a>',false);
    } else {
		Display::display_warning_message(get_lang('NoResourcesInBackupFile').	
                '<a class="bottom-link" href="import_backup.php?'.api_get_cidreq().'">&lt;&lt; '.get_lang('TryAgain').'</a>',false);
	}
} else {
	$user = api_get_user_info();
	$backups = CourseArchiver::get_available_backups($is_platformAdmin?null:$user['user_id']);
	$backups_available = (count($backups)>0);
	
	echo get_lang('SelectBackupFile').'<br /><br />';
	
	include (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
	$form = new FormValidator('import_backup_form','POST','import_backup.php', '','multipart/form-data');
	
	$renderer = $form->defaultRenderer();
	$renderer->setElementTemplate('<div>{element}</div> ');
	
	$form->addElement('hidden','action', 'restore_backup');
	
	$form->addElement('radio', 'backup_type', '', get_lang('LocalFile'), 'local', 'id="bt_local" class="checkbox" onclick="javascript:document.import_backup_form.backup_server.disabled=true;document.import_backup_form.backup.disabled=false;"');
	$form->addElement('file', 'backup', '', 'style="margin-left: 50px;"');
	$form->addElement('html', '<br />');
	
	if( $backups_available ){
		$form->addElement('radio', 'backup_type', '', get_lang('ServerFile'), 'server', 'id="bt_server" class="checkbox" onclick="javascript:document.import_backup_form.backup_server.disabled=false;document.import_backup_form.backup.disabled=true;"');
		$options['null'] = '-';
		foreach($backups as $index => $backup)
		{
			$options[$backup['file']]= $backup['course_code'].' ('.$backup['date'];
		}
		$form->addElement('select', 'backup_server', '', $options, 'style="margin-left: 50px;"');
		$form->addElement('html','<script type="text/javascript">document.import_backup_form.backup_server.disabled=true;</script>');
	}
	else
	{
		$form->addElement('radio', '', '', '<i>'.get_lang('NoBackupsAvailable').'</i>', '', 'disabled="true"');
	}
	
	$form->addElement('html', '<br /><br />');
	
	$form->addElement('radio', 'import_option', '', get_lang('ImportFullBackup'), 'full_backup', 'id="import_option_1" class="checkbox"');
	$form->addElement('radio', 'import_option', '', get_lang('LetMeSelectItems'), 'select_items', 'id="import_option_2" class="checkbox"');
	
	$form->addElement('html', '<br /><br />');
	
	$form->addElement('html', get_lang('SameFilename'));
	$form->addElement('html', '<br /><br />');
	$form->addElement('radio', 'same_file_name_option', '', get_lang('SameFilenameSkip'), FILE_SKIP, 'id="same_file_name_option_1" class="checkbox"');
	$form->addElement('radio', 'same_file_name_option', '', get_lang('SameFilenameRename'), FILE_RENAME, 'id="same_file_name_option_2" class="checkbox"');
	$form->addElement('radio', 'same_file_name_option', '', get_lang('SameFilenameOverwrite'), FILE_OVERWRITE, 'id="same_file_name_option_3" class="checkbox"');
	
	$form->addElement('html', '<br />');
	$form->addElement('style_submit_button', null, get_lang('ImportBackup'), 'class="save"');
	
	$values['backup_type'] = 'local';
	$values['import_option'] = 'full_backup';
	$values['same_file_name_option'] = FILE_OVERWRITE;
	$form->setDefaults($values);
	
	$form->add_progress_bar();
	
	$form->display();
		
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>
