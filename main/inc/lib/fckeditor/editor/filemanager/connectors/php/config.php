<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2009 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * Configuration file for the File Manager Connector for PHP.
 */

// Modifications by Ivan Tcholakov, JUN-2009.

// Some language variables are needed.
$language_file = array('create_course');

// Loading the global initialization file, Dokeos LMS.
require_once '../../../../../../global.inc.php';

// Initialization of the repositories.
require_once api_get_path(LIBRARY_PATH).'fckeditor/repositories_config.php' ;

global $Config ;

// SECURITY: You must explicitly enable this "connector". (Set it to "true").
// WARNING: don't just set "$Config['Enabled'] = true ;", you must be sure that only
//		authenticated users can access this file or use some kind of session checking.
$Config['Enabled'] = true ;


// Path to user files relative to the document root.
$Config['UserFilesPath'] = $_GET['ServerPath'] ;

// Fill the following value it you prefer to specify the absolute path for the
// user files directory. Useful if you are using a virtual directory, symbolic
// link or alias. Examples: 'C:\\MySite\\userfiles\\' or '/root/mysite/userfiles/'.
// Attention: The above 'UserFilesPath' must point to the same directory.
$Config['UserFilesAbsolutePath'] = rtrim(api_get_path(SYS_SERVER_ROOT_PATH), '/') . $Config['UserFilesPath'] ;

// Due to security issues with Apache modules, it is recommended to leave the
// following setting enabled.
$Config['ForceSingleExtension'] = true ;

// Perform additional checks for image files.
// If set to true, validate image size (using getimagesize).
$Config['SecureImageUploads'] = true;

// What the user can do with this connector.
$Config['ConfigAllowedCommands'] = array('QuickUpload', 'FileUpload', 'GetFolders', 'GetFoldersAndFiles', 'CreateFolder') ;

// Allowed Resource Types.
$Config['ConfigAllowedTypes'] = array('File', 'Audio', 'Images', 'Flash', 'Media', 'MP3', 'Video', 'Video/flv') ;

// For security, HTML is allowed in the first Kb of data for files having the
// following extensions only.
$Config['HtmlExtensions'] = array("html", "htm", "xhtml", "xml", "xsd", "txt", "js") ;

// After file is uploaded, sometimes it is required to change its permissions
// so that it was possible to access it at the later time.
// If possible, it is recommended to set more restrictive permissions, like 0755.
// Set to 0 to disable this feature.
// Note: not needed on Windows-based servers.
$Config['ChmodOnUpload'] = $permissions_for_new_files ;

// See comments above.
// Used when creating folders that does not exist.
$Config['ChmodOnFolderCreate'] = $permissions_for_new_directories ;

/*
	Configuration settings for each Resource Type

	- AllowedExtensions: the possible extensions that can be allowed.
		If it is empty then any file type can be uploaded.
	- DeniedExtensions: The extensions that won't be allowed.
		If it is empty then no restrictions are done here.

	For a file to be uploaded it has to fulfill both the AllowedExtensions
	and DeniedExtensions (that's it: not being denied) conditions.

	- FileTypesPath: the virtual folder relative to the document root where
		these resources will be located.
		Attention: It must start and end with a slash: '/'

	- FileTypesAbsolutePath: the physical path to the above folder. It must be
		an absolute path.
		If it's an empty string then it will be autocalculated.
		Useful if you are using a virtual directory, symbolic link or alias.
		Examples: 'C:\\MySite\\userfiles\\' or '/root/mysite/userfiles/'.
		Attention: The above 'FileTypesPath' must point to the same directory.
		Attention: It must end with a slash: '/'

	 - QuickUploadPath: the virtual folder relative to the document root where
		these resources will be uploaded using the Upload tab in the resources
		dialogs.
		Attention: It must start and end with a slash: '/'

	 - QuickUploadAbsolutePath: the physical path to the above folder. It must be
		an absolute path.
		If it's an empty string then it will be autocalculated.
		Useful if you are using a virtual directory, symbolic link or alias.
		Examples: 'C:\\MySite\\userfiles\\' or '/root/mysite/userfiles/'.
		Attention: The above 'QuickUploadPath' must point to the same directory.
		Attention: It must end with a slash: '/'

	 	NOTE: by default, QuickUploadPath and QuickUploadAbsolutePath point to
	 	"userfiles" directory to maintain backwards compatibility with older versions of FCKeditor.
	 	This is fine, but you in some cases you will be not able to browse uploaded files using file browser.
	 	Example: if you click on "image button", select "Upload" tab and send image
	 	to the server, image will appear in FCKeditor correctly, but because it is placed
	 	directly in /userfiles/ directory, you'll be not able to see it in built-in file browser.
	 	The more expected behaviour would be to send images directly to "image" subfolder.
	 	To achieve that, simply change
			$Config['QuickUploadPath']['Image']			= $Config['UserFilesPath'] ;
			$Config['QuickUploadAbsolutePath']['Image']	= $Config['UserFilesAbsolutePath'] ;
		into:
			$Config['QuickUploadPath']['Image']			= $Config['FileTypesPath']['Image'] ;
			$Config['QuickUploadAbsolutePath']['Image'] 	= $Config['FileTypesAbsolutePath']['Image'] ;

*/


// Files
$Config['AllowedExtensions']['File']			= array('7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vsd', 'wav', 'wma', 'wmv', 'xls', 'xml', 'zip') ;
$Config['DeniedExtensions']['File']				= array('php', 'php3', 'php4', 'php5', 'php6', 'phps', 'phtml', 'asp', 'aspx', 'ascx', 'jsp', 'cfm', 'cfc', 'pl', 'bat', 'exe', 'dll', 'reg', 'cgi') ;
$Config['FileTypesPath']['File']				= $Config['UserFilesPath'] ;
$Config['FileTypesAbsolutePath']['File']		= $Config['UserFilesAbsolutePath'] ;
$Config['QuickUploadPath']['File']				= $Config['UserFilesPath'] ;
$Config['QuickUploadAbsolutePath']['File']		= $Config['UserFilesAbsolutePath'] ;

// Audio (MP3 is an alias)
$Config['AllowedExtensions']['Audio']			= array('mp3') ;
$Config['DeniedExtensions']['Audio']			= $Config['DeniedExtensions']['File'] ;
$Config['DeniedExtensions']['Audio']			= array() ;
$Config['FileTypesPath']['Audio']				= $Config['UserFilesPath'] . 'audio/' ;
$Config['FileTypesAbsolutePath']['Audio']		= $Config['UserFilesAbsolutePath'] . 'audio/' ;
$Config['QuickUploadPath']['Audio']				= $Config['UserFilesPath'] . 'audio/' ;
$Config['QuickUploadAbsolutePath']['Audio']		= $Config['UserFilesAbsolutePath'] . 'audio/' ;

// Images
$Config['AllowedExtensions']['Images']			= array('bmp', 'gif', 'jpg', 'jpeg', 'png') ;
$Config['DeniedExtensions']['Images']			= $Config['DeniedExtensions']['File'] ;
$Config['FileTypesPath']['Images']				= $Config['UserFilesPath'] . 'images/' ;
$Config['FileTypesAbsolutePath']['Images']		= $Config['UserFilesAbsolutePath'] . 'images/' ;
$Config['QuickUploadPath']['Images']			= $Config['UserFilesPath'] . 'images/' ;
$Config['QuickUploadAbsolutePath']['Images']	= $Config['UserFilesAbsolutePath'] . 'images/' ;

// Flash
$Config['AllowedExtensions']['Flash']			= array('swf') ;
$Config['DeniedExtensions']['Flash']			= $Config['DeniedExtensions']['File'] ;
$Config['FileTypesPath']['Flash']				= $Config['UserFilesPath'] . 'flash/' ;
$Config['FileTypesAbsolutePath']['Flash']		= $Config['UserFilesAbsolutePath'] . 'flash/' ;
$Config['QuickUploadPath']['Flash']				= $Config['UserFilesPath'] . 'flash/' ;
$Config['QuickUploadAbsolutePath']['Flash']		= $Config['UserFilesAbsolutePath'] . 'flash/' ;

// MP3 (audio)
$Config['AllowedExtensions']['MP3']				= $Config['AllowedExtensions']['Audio'] ;
$Config['DeniedExtensions']['MP3']				= $Config['DeniedExtensions']['Audio'] ;
$Config['FileTypesPath']['MP3']					= $Config['FileTypesPath']['Audio'] ;
$Config['FileTypesAbsolutePath']['MP3']			= $Config['FileTypesAbsolutePath']['Audio'] ;
$Config['QuickUploadPath']['MP3']				= $Config['QuickUploadPath']['Audio'] ;
$Config['QuickUploadAbsolutePath']['MP3']		= $Config['QuickUploadAbsolutePath']['Audio'] ;

// Video
$Config['AllowedExtensions']['Video']			= array('asf', 'avi', 'mpg', 'mpeg', 'mp4', 'mov', 'wmv') ;
$Config['DeniedExtensions']['Video']			= $Config['DeniedExtensions']['File'] ;
$Config['FileTypesPath']['Video']				= $Config['UserFilesPath'] . 'video/' ;
$Config['FileTypesAbsolutePath']['Video']		= $Config['UserFilesAbsolutePath'] . 'video/' ;
$Config['QuickUploadPath']['Video']				= $Config['UserFilesPath'] . 'video/' ;
$Config['QuickUploadAbsolutePath']['Video']		= $Config['UserFilesAbsolutePath'] . 'video/' ;

// Video/flv
$Config['AllowedExtensions']['Video/flv']		= array('flv', 'mp4' ) ;
$Config['DeniedExtensions']['Video/flv']		= $Config['DeniedExtensions']['File'] ;
$Config['FileTypesPath']['Video/flv']			= $Config['UserFilesPath'] . 'video/flv/' ;
$Config['FileTypesAbsolutePath']['Video/flv']	= $Config['UserFilesAbsolutePath'] . 'video/flv/' ;
$Config['QuickUploadPath']['Video/flv']			= $Config['UserFilesPath'] . 'video/flv/' ;
$Config['QuickUploadAbsolutePath']['Video/flv']	= $Config['UserFilesAbsolutePath'] . 'video/flv/' ;

?>
