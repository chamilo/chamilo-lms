<?php
/* For licensing terms, see /license.txt */
/**
 *   @package chamilo.admin
 */

$language_file = array('admin');

// resetting the course id
$cidReset = true;

// including some necessary files
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);
//api_protect_global_admin_script();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$form = new FormValidator('archive_cleanup_form');
$form->addElement('style_submit_button','proceed', get_lang('ArchiveDirCleanupProceedButton'),'class="save"');

if ($form->validate()) {
	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$htaccess 	  = @file_get_contents($archive_path.'.htaccess');	
	$result 	  = rmdirr($archive_path, true);
	
	if (!empty($htaccess)) {	
		@file_put_contents($archive_path.'/.htaccess', $htaccess);
	}	
	if ($result) {		
		$message = 'ArchiveDirCleanupSucceeded';
		$type = 'confirmation';
	} else {		
		$message = 'ArchiveDirCleanupFailed';
		$type = 'error';
	}
	header('Location: index.php?msg='.$message.'&type='.$type);
	exit;	
}

Display::display_header(get_lang('ArchiveDirCleanup'));
Display::display_normal_message(get_lang('ArchiveDirCleanupDescr'));
$form->display();
Display::display_footer();