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
				WHERE post.cat_id='".Database::escape_string($_GET['cat_id'])."'
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

// Check if the user has sent or received the file.
$sql = "SELECT * FROM ".$dropbox_cnf['tbl_person']." WHERE file_id='".intval($_GET['id'])."' AND user_id='".api_get_user_id()."'";
$result = Database::query($sql);
if (Database::num_rows($result) > 0) {
	$allowed_to_download = true;
}

/*		ERROR IF NOT ALLOWED TO DOWNLOAD */

if (!$allowed_to_download) {
	Display::display_header($nameTools, 'Dropbox');
	Display :: display_error_message(get_lang('YouAreNotAllowedToDownloadThisFile'));
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
	require_once api_get_path(LIBRARY_PATH).'document.lib.php';
	$mimetype = DocumentManager::file_get_mime_type(true);
	$fileparts = explode('.', $file);
	$filepartscount = count($fileparts);
	if (($filepartscount > 1) && isset($mimetype[$fileparts[$filepartscount - 1]]) && $_GET['action'] != 'download') {
	    // give hint to browser about filetype
    	header( 'Content-type: ' . $mimetype[$fileparts[$filepartscount - 1]] . "\n");
	} else {
		//no information about filetype: force a download dialog window in browser
		header( "Content-type: application/octet-stream\n");
	}
	if (!in_array(strtolower($fileparts [$filepartscount - 1]), array('doc', 'xls', 'ppt', 'pps', 'sxw', 'sxc', 'sxi'))) {
		header('Content-Disposition: inline; filename='.$file); // bugs with open office
	} else {
		header('Content-Disposition: attachment; filename='.$file);
	}

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

	header('Content-Description: ' . trim(htmlentities($file)) . "\n");
	header("Content-Transfer-Encoding: binary\n");
	header("Content-Length: " . filesize($path)."\n" );

	$fp = fopen( $path, 'rb');
	fpassthru($fp);
	exit();
}


//@todo clean this file the code below is useless there are 2 exits in previous conditions ... maybe a bad copy/paste/merge?
exit;








/**
 * Dropbox module for Chamilo
 * handles downloads of files. Direct downloading is prevented because of an .htaccess file in the
 * dropbox directory. So everything goes through this script.
 *
 * 1. Initialising vars
 * 2. Authorisation
 * 3. Sanity check of get data & file
 * 4. Send headers
 * 5. Send file
 *
 *
 * NOTE :
 * When testing this with PHP4.0.4 on WinXP and Apache2 there were problems with downloading in IE6
 * After searching the only explanation I could find is a problem with the headers:
 *
 * HEADERS SENT WITH PHP4.3:
 * HTTP/1.1 200 OK(CR)
 * (LF)
 * Date: Fri, 12 Sep 2003 19:07:33 GMT(CR)
 * (LF)
 * Server: Apache/2.0.47 (Win32) PHP/4.3.3(CR)
 * (LF)
 * X-Powered-By: PHP/4.3.3(CR)
 * (LF)
 * Set-Cookie: PHPSESSID=06880edcc8363be3f60929576fc1bc6e; path=/(CR)
 * (LF)
 * Expires: Thu, 19 Nov 1981 08:52:00 GMT(CR)
 * (LF)
 * Cache-Control: public(CR)
 * (LF)
 * Pragma: (CR)
 * (LF)
 * Content-Transfer-Encoding: binary(CR)
 * (LF)
 * Content-Disposition: attachment; filename=SV-262E4.png(CR)
 * (LF)
 * Content-Length: 92178(CR)
 * (LF)
 * Connection: close(CR)
 * (LF)
 * Content-Type: application/octet-stream(CR)
 * (LF)
 * (CR)
 * (LF)
 *
 * HEADERS SENT WITH PHP4.0.4:
 * HTTP/1.1 200 OK(CR)
 * (LF)
 * Date: Fri, 12 Sep 2003 18:28:21 GMT(CR)
 * (LF)
 * Server: Apache/2.0.47 (Win32)(CR)
 * (LF)
 * X-Powered-By: PHP/4.0.4(CR)
 * (LF)
 * Expires: Thu, 19 Nov 1981 08:52:00 GMT(CR)
 * (LF)
 * Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, , public(CR)
 * (LF)
 * Pragma: no-cache, (CR)
 * (LF)
 * Content-Disposition: attachment; filename=SV-262E4.png(CR)
 * (LF)
 * Content-Transfer-Encoding: binary(CR)
 * (LF)
 * Set-Cookie: PHPSESSID=0a5b1c1b9d5e3b474fef359ee55e82d0; path=/(CR)
 * (LF)
 * Content-Length: 92178(CR)
 * (LF)
 * Connection: close(CR)
 * (LF)
 * Content-Type: application/octet-stream(CR)
 * (LF)
 * (CR)
 * (LF)
 *
 * As you can see the there is a difference in the Cache-Control directive. I suspect that this
 * explains the problem. Also have a look at http://bugs.php.net/bug.php?id=16458.
 *
 * @version 1.21
 * @copyright 2004-2005
 * @author Jan Bols <jan@ivpv.UGent.be>, main programmer
 * @author René Haentjens <rene.haentjens@UGent.be>, several contributions
 * @author Roan Embrechts, virtual course support
 *
 */

//	INITIALISING VARIABLES 


require_once 'dropbox_init.inc.php';	//only call init1 because init2 outputs data
require_once 'dropbox_class.inc.php';


//	AUTHORISATION SECTION 

if (!isset($_user['user_id']) || !$is_course_member) {
    exit();
}

if ($_GET['mailing']) {
	getUserOwningThisMailing($_GET['mailing'], $_user['user_id'], '500');
}

//	SANITY CHECKS OF GET DATA & FILE 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die(get_lang('GeneralError').' (code 501)');

$work = new Dropbox_work($_GET['id']);

$path = dropbox_cnf('sysPath') . '/' . $work -> filename; //path to file as stored on server
$file = $work->title;

// check that this file exists and that it doesn't include any special characters
//if (!is_file($path) || ! eregi('^[A-Z0-9_\-][A-Z0-9._\-]*$', $file))
if (!is_file($path)) {
    die(get_lang('GeneralError').' (code 504)');
}

//	SEND HEADERS

require_once api_get_path(LIBRARY_PATH).'document.lib.php';
$mimetype = DocumentManager::file_get_mime_type(true);

$fileparts = explode('.', $file);
$filepartscount = count($fileparts);

if (($filepartscount > 1) && isset($mimetype[$fileparts[$filepartscount - 1]])) {
    // give hint to browser about filetype
    header('Content-type: ' . $mimetype[$fileparts[$filepartscount - 1]] . "\n");
} else {
	//no information about filetype: force a download dialog window in browser
	header("Content-type: application/octet-stream\n");
}

if (!in_array(strtolower($fileparts [$filepartscount - 1]), array('doc', 'xls', 'ppt', 'pps', 'sxw', 'sxc', 'sxi'))) {
	header('Content-Disposition: inline; filename='.$file); // bugs with open office
} else {
	header('Content-Disposition: attachment; filename='.$file);
}

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
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . " GMT\n");
    header("Cache-Control: no-store, no-cache, must-revalidate\n"); // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0\n", false);
}*/

header('Content-Description: ' . trim(htmlentities($file)) . "\n");
header("Content-Transfer-Encoding: binary\n");
header('Content-Length: ' . filesize($path)."\n" );

//	SEND FILE 

$fp = fopen( $path, 'rb');
fpassthru($fp);
exit();


/**
 * Found a workaround to another headache that just cropped up tonight.  Apparently Opera 6.1 on Linux (unsure of other versions/platforms) has problems downloading files using the above methods if you have enabled compression via zlib.output_compression in php.ini.
 * It seems that Opera sees that the actual transfer size is less than the size in the "Content-length" header for the download and decides that the transfer was incomplete or corrupted.  It then either continuously retries the download or else leaves you with a corrupted file.
 * Solution:  Make sure your download script/section is off in its own directory. and add the following to your .htaccess file for that directory:
 * php_flag zlib.output_compression off
 */
