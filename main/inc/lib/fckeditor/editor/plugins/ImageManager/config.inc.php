<?php
/**
 * Image Manager configuration file.
 * @author Wei Zhuo
 * @author Paul Moers <mail@saulmade.nl> - watermarking and replace code + several small enhancements <http://fckplugins.saulmade.nl>
 * @version $Id: config.inc.php,v 1.4 2006/12/17 14:53:50 thierrybo Exp $
 * @package ImageManager
 */


/* 
 File system path to the directory you want to manage the images
 for multiple user systems, set it dynamically.

 NOTE: This directory requires write access by PHP. That is, 
       PHP must be able to create files in this directory.
	   Able to create directories is nice, but not necessary.
*/
#$IMConfig['base_dir'] = '/var/www/FCKeditor/images/';

/*
 The URL to the above path, the web browser needs to be able to see it.
 It can be protected via .htaccess on apache or directory permissions on IIS,
 check you web server documentation for futher information on directory protection
 If this directory needs to be publicly accessiable, remove scripting capabilities
 for this directory (i.e. disable PHP, Perl, CGI). We only want to store assets
 in this directory and its subdirectories.
*/
$language_file = array('document');
include ('../../../../../../inc/global.inc.php');

// Initialization of the repositories.
require_once api_get_path(LIBRARY_PATH).'fckeditor/repositories_config.php';

// Choosing the repository to be used.
if (api_is_in_course())
{
	if (!api_is_in_group())
	{
		// 1. We are inside a course and not in a group.
		if (api_is_allowed_to_edit())
		{
			// 1.1. Teacher
			$IMConfig['base_dir'] = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/';
			$IMConfig['base_url'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
		}
		else
		{
			// 1.2. Student
			$IMConfig['base_dir'] = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/shared_folder/';
			$IMConfig['base_url'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/';
		}
	}
	else
	{
		// 2. Inside a course and inside a group.
		$IMConfig['base_dir'] = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
		$IMConfig['base_url'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
	}
}
else
{
	if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin')
	{
		// 3. Platform administration activities.
		$IMConfig['base_dir'] = $_configuration['root_sys'].'home/default_platform_document/';
		$IMConfig['base_url'] = $_configuration['root_web'].'home/default_platform_document/';
	}
	else
	{
		// 4. The user is outside courses.
		$IMConfig['base_dir'] = $_configuration['root_sys'].'main/upload/users/'.api_get_user_id().'/my_files/';
		$IMConfig['base_url'] = $_configuration['root_web'].'main/upload/users/'.api_get_user_id().'/my_files/';
	}
}

$IMConfig['server_name'] = $_SERVER['SERVER_NAME'];

/*
 demo - when true, no saving is allowed
*/
$IMConfig['demo'] = false;

/*

  Possible values: true, false

  TRUE - If PHP on the web server is in safe mode, set this to true.
         SAFE MODE restrictions: directory creation will not be possible,
		 only the GD library can be used, other libraries require
		 Safe Mode to be off.

  FALSE - Set to false if PHP on the web server is not in safe mode.
*/
$IMConfig['safe_mode'] = false;

/* 
 Possible values: 'GD', 'IM', or 'NetPBM'

 The image manipulation library to use, either GD or ImageMagick or NetPBM.
 If you have safe mode ON, or don't have the binaries to other packages, 
 your choice is 'GD' only. Other packages require Safe Mode to be off.
*/
define('IMAGE_CLASS', 'GD');


/*
 After defining which library to use, if it is NetPBM or IM, you need to
 specify where the binary for the selected library are. And of course
 your server and PHP must be able to execute them (i.e. safe mode is OFF).
 GD does not require the following definition.
*/
define('IMAGE_TRANSFORM_LIB_PATH', '/usr/bin/');


/* ==============  OPTIONAL SETTINGS ============== */


/*
  The prefix for thumbnail files, something like .thumb will do. The
  thumbnails files will be named as "prefix_imagefile.ext", that is,
  prefix + orginal filename.
*/
$IMConfig['thumbnail_prefix'] = '.';

/*
  Thumbnail can also be stored in a directory, this directory
  will be created by PHP. If PHP is in safe mode, this parameter
  is ignored, you can not create directories. 

  If you do not want to store thumbnails in a directory, set this
  to false or empty string '';
*/
$IMConfig['thumbnail_dir'] = '.thumbs';

/*
  Possible values: true, false

 TRUE -  Allow the user to create new sub-directories in the
         $IMConfig['base_dir'].

 FALSE - No directory creation.

 NOTE: If $IMConfig['safe_mode'] = true, this parameter
       is ignored, you can not create directories
*/
$IMConfig['allow_new_dir'] = true;

/*
  Possible values: true, false

  TRUE - Allow the user to upload files.

  FALSE - No uploading allowed.
*/
$IMConfig['allow_upload'] = true;

/*
  Possible values: true, false

  TRUE - Allow the user to edit images.

  FALSE - No editing allowed.
*/
$IMConfig['allow_edit'] = true;

/*
  Possible values: true, false

  TRUE - Allow the replacement of the image with a newly uploaded image in the editor dialog.

  FALSE - No replacing allowed.
*/
$IMConfig['allow_replace'] = false;

/*
  Possible values: true, false

  TRUE - Allow the deletion of images

  FALSE - No deleting allowed
*/
$IMConfig['allow_delete'] = true;

/*
  Possible values: true, false

  TRUE - Allow the user to enter a new filename for saving the edited image.

  FALSE - Overwrite
*/
$IMConfig['allow_newFileName'] = false;

/*
  Possible values: true, false
  Only applies when the the user can enter a new filename (The baove settig = 'allow_newFileName' true)

  TRUE - Overwrite file of entered filename, if file already exist.

  FALSE - Save to variant of entered filename, if file already exist.
*/
$IMConfig['allow_overwrite'] = false;

/*
  Specify the paths of the watermarks to use (relative to $IMConfig['base_dir']).
  Specifying none will hide watermarking functionality.
*/
/*
$IMConfig['watermarks'] = array	(
													"imageManager.png",
													"imageManager.jpg",
													"devshed.png",
													"natureWatermark.png"
												);
*/
$IMConfig['watermarks'] = array	();

/*
	To limit the width and height for uploaded files, specify the maximum pixeldimensions.
	Specify more widthxheight sets by copying both lines and increasing the number in the second brackets.
	If only one set is specified, no select list will show and this set will be used by default.
	Setting the single set its values to either zero or empty will allow any size.
*/
//$IMConfig['maxWidth'][0] = 333;
//$IMConfig['maxHeight'][0] = 333;
//$IMConfig['maxWidth'][1] = 100;
//$IMConfig['maxHeight'][1] = 180;
$IMConfig['maxWidth'][0] = 0;
$IMConfig['maxHeight'][0] = 0;


/*
  Uploaded files will be accepted as images if they have file extensions from the list below.
 */
$IMConfig['accepted_extensions'] = array('gif', 'jpg', 'png', 'bmp', 'jpeg');

/*
 Possible values: true, false

 TRUE - If set to true, uploaded files will be validated based on the 
        function getImageSize, if we can get the image dimensions then 
        I guess this should be a valid image. Otherwise the file will be rejected.

 FALSE - All uploaded files will be processed.

 NOTE: If uploading is not allowed, this parameter is ignored.
*/
$IMConfig['validate_images'] = true;

/*
 The default thumbnail if the thumbnails can not be created, either
 due to error or bad image file.
*/
$IMConfig['default_thumbnail'] = 'img/default.gif';

/*
  Thumbnail dimensions.
*/
$IMConfig['thumbnail_width'] = 96;
$IMConfig['thumbnail_height'] = 96;

/*
  Image Editor temporary filename prefix.
*/
$IMConfig['tmp_prefix'] = '.editor_';
?>
