<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.dropbox
 */
/**
 * Code
 */
/*	INIT SECTION */

// We cannot use dropbox_init.inc.php because this one already outputs data.

$language_file = 'dropbox';

// including the basic Chamilo initialisation file
require_once '../inc/global.inc.php';

// the dropbox configuration parameters
require_once 'dropbox_config.inc.php';

// the dropbox file that contains additional functions
require_once 'dropbox_functions.inc.php';

// the dropbox class
require_once 'dropbox_class.inc.php';

require_once api_get_path(LIBRARY_PATH).'document.lib.php';

/*	DOWNLOAD A FOLDER */
$course_id = api_get_course_int_id();

if (isset($_GET['cat_id']) AND is_numeric($_GET['cat_id']) AND $_GET['action'] == 'downloadcategory' AND isset($_GET['sent_received'])) {
	// step 1: constructingd' the sql statement. Due to the nature off the classes of the dropbox the categories for sent files are stored in the table
	// dropbox_file while the categories for the received files are stored in dropbox_post. It would have been more elegant if these could be stored
	// in dropbox_person (which stores the link file-person)
	// Therefore we have to create to separate sql statements to find which files are in the categorie (depending if we zip-download a sent category or a
	// received category)
	if ($_GET['sent_received'] == 'sent') {
		// here we also incorporate the person table to make sure that deleted sent documents are not included.
		$sql = "SELECT DISTINCT file.id, file.filename, file.title FROM ".$dropbox_cnf['tbl_file']." file, ".$dropbox_cnf['tbl_person']." person
				WHERE file.uploader_id='".Database::escape_string($_user['user_id'])."'
				AND file.cat_id='".Database::escape_string($_GET['cat_id'])."'
				AND person.user_id='".Database::escape_string($_user['user_id'])."'
				AND person.file_id=file.id
				" ;
	}
	if ($_GET['sent_received'] == 'received') {
		$sql = "SELECT DISTINCT file.id, file.filename, file.title FROM ".$dropbox_cnf['tbl_file']." file, ".$dropbox_cnf['tbl_person']." person, ".$dropbox_cnf['tbl_post']." post
				WHERE
                file.c_id = $course_id AND
                person.c_id = $course_id AND 
                post.c_id = $course_id AND  
				post.cat_id='".Database::escape_string($_GET['cat_id'])."'
				AND person.user_id='".Database::escape_string($_user['user_id'])."'
				AND person.file_id=file.id
				AND post.file_id=file.id
				" ;
	}
	$result = Database::query($sql);
	while ($row = Database::fetch_array($result)) {
		$files_to_download[] = $row['id'];
	}
	if (!is_array($files_to_download) OR empty($files_to_download)) {
		header('location: index.php?view='.Security::remove_XSS($_GET['sent_received']).'&error=ErrorNoFilesInFolder');
		exit;
	}
	zip_download($files_to_download);
	exit;
}


/*	DOWNLOAD A FILE */

/*		AUTHORIZATION */

// Check if the id makes sense
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	Display::display_header($nameTools, 'Dropbox');
	Display :: display_error_message(get_lang('Error'));
	Display::display_footer();
	exit;
}

// Check if the user is allowed to download the file
$allowed_to_download = false;
if (user_can_download_file($_GET['id'], api_get_user_id())) {
	$allowed_to_download = true;
}

/*		ERROR IF NOT ALLOWED TO DOWNLOAD */

if (!$allowed_to_download) {
	Display::display_header($nameTools, 'Dropbox');
	Display::display_error_message(get_lang('YouAreNotAllowedToDownloadThisFile'));
	Display::display_footer();
	exit;
} else {    
    /*      DOWNLOAD THE FILE */    
    // the user is allowed to download the file
	$_SESSION['_seen'][$_course['id']][TOOL_DROPBOX][] = intval($_GET['id']);

	$work = new Dropbox_work($_GET['id']);
	$path = dropbox_cnf('sysPath') . '/' . $work -> filename; //path to file as stored on server
	
    if (!Security::check_abs_path($path, dropbox_cnf('sysPath').'/')) {
    	exit;
    }
	$file = $work->title;	
	$mimetype = DocumentManager::file_get_mime_type(true);
	$fileinfo = pathinfo($file);
	$extension = $fileinfo['extension'];
	
	if (!empty($extension) && isset($mimetype[$extension]) && $_GET['action'] != 'download') {		
	    // give hint to browser about filetype
    	header( 'Content-type: ' . $mimetype[$extension] . "\n");
	} else {
		//no information about filetype: force a download dialog window in browser
		header( "Content-type: application/octet-stream\n");
	}
	
	/*if (!in_array(strtolower($extension), array('doc', 'xls', 'ppt', 'pps', 'sxw', 'sxc', 'sxi'))) {
		header('Content-Disposition: inline; filename='.$file); // bugs with open office
	} else {
		header('Content-Disposition: attachment; filename='.$file);
	}*/
	
	header('Content-Disposition: attachment; filename='.$file);

	/**
	 * Note that if you use these two headers from a previous example:
	 * header('Cache-Control: no-cache, must-revalidate');
	 * header('Pragma: no-cache');
	 * before sending a file to the browser, the "Open" option on Internet Explorer's file download dialog will not work properly. If the user clicks "Open" instead of "Save," the target application will open an empty file, because the downloaded file was not cached. The user will have to save the file to their hard drive in order to use it.
	 * Make sure to leave these headers out if you'd like your visitors to be able to use IE's "Open" option.
	 */
	header("Pragma: \n");
	header("Cache-Control: \n");
	header("Cache-Control: public\n"); // IE cannot download from sessions without a cache

	/*if (isset($_SERVER['HTTPS'])) {
	    /**
	     * We need to set the following headers to make downloads work using IE in HTTPS mode.
	     *
	    //header('Pragma: ');
	    //header('Cache-Control: ');
	    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\n");
	    header("Last-Modified: " . gmdate( "D, d M Y H:i:s") . " GMT\n");
	    header("Cache-Control: no-store, no-cache, must-revalidate\n"); // HTTP/1.1
	    header("Cache-Control: post-check=0, pre-check=0\n", false);
	}*/
	
	header('Content-Description: '.trim(htmlentities($file)));
	header('Content-transfer-encoding: binary');
	
	header("Content-Length: " . filesize($path)."\n" );

	$fp = fopen( $path, 'rb');
	fpassthru($fp);
	exit();
}

//@todo clean this file the code below is useless there are 2 exits in previous conditions ... maybe a bad copy/paste/merge?
exit;