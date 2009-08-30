<?php
// $Id: create_backup.php 20084 2009-04-24 20:10:43Z aportugal $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos SPRL
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
 * Create a backup.
 * 
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 * ==============================================================================
 */

// name of the language file that needs to be included 
$language_file = array ('exercice', 'admin', 'coursebackup');

// including the global file
include ('../inc/global.inc.php');

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit())
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
$nameTools = get_lang('CreateBackup');
Display::display_header($nameTools);

// include additional libraries
include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
require_once ('classes/CourseBuilder.class.php');
require_once ('classes/CourseArchiver.class.php');
require_once ('classes/CourseRestorer.class.php');
require_once ('classes/CourseSelectForm.class.php');

// Display the tool title
api_display_tool_title($nameTools);

/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 
if ((isset ($_POST['action']) && $_POST['action'] == 'course_select_form') || (isset ($_POST['backup_option']) && $_POST['backup_option'] == 'full_backup'))
{
	if (isset ($_POST['action']) && $_POST['action'] == 'course_select_form')
	{
		$course = CourseSelectForm :: get_posted_course();
	}
	else
	{
		$cb = new CourseBuilder();
		$course = $cb->build();
	}
	$zip_file = CourseArchiver :: write_course($course);
	Display::display_confirmation_message(get_lang('BackupCreated').str_repeat('<br />',3).'<a class="bottom-link" href="../course_info/download.php?archive='.$zip_file.'">'.$zip_file.'</a>', false);
	//echo '<p><a href="../course_home/course_home.php">&lt;&lt; '.get_lang('CourseHomepage').'</a></p>'; // This is not the preferable way to go to the course homepage.
	echo '<div style="width:200px"><a class="bottom-link" href="'.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/index.php" >'.get_lang('CourseHomepage').'</a></div>';
?>
	<!-- Manual download <script language="JavaScript">
	 setTimeout('download_backup()',2000);
	 function download_backup()
	 {
		window.location="../course_info/download.php?archive=<?php echo $zip_file ?>";
	 }
	</script> //-->
	<?php

}
elseif (isset ($_POST['backup_option']) && $_POST['backup_option'] == 'select_items')
{
	$cb = new CourseBuilder('partial');
	$course = $cb->build();	
	Display::display_normal_message(get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz'));
	CourseSelectForm :: display_form($course);
}
else
{
	$cb = new CourseBuilder();
	$course = $cb->build();
	if (!$course->has_resources())
	{
		echo get_lang('NoResourcesToBackup');
	}
	else
	{
		echo get_lang('SelectOptionForBackup');
		
		include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
		$form = new FormValidator('create_backup_form','POST');
		$renderer = $form->defaultRenderer();
		$renderer->setElementTemplate('<div>{element}</div> ');
		$form->addElement('radio', 'backup_option', '', get_lang('CreateFullBackup'), 'full_backup');
		$form->addElement('radio', 'backup_option', '',  get_lang('LetMeSelectItems'), 'select_items');
		$form->addElement('html','<br />');
		$form->addElement('style_submit_button', null, get_lang('CreateBackup'), 'class="save"');
		
		$form->add_progress_bar();
		
		$values['backup_option'] = 'full_backup';
		$form->setDefaults($values);
		
		$form->display();
	}
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display::display_footer();
?>
