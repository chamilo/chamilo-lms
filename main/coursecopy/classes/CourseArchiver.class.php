<?php
/* For licensing terms, see /license.txt */

require_once 'Course.class.php';
require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';

/**
 * Some functions to write a course-object to a zip-file and to read a course-
 * object from such a zip-file.
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 *
 * @todo Use archive-folder of Dokeos?
 */
class CourseArchiver
{
	/**
	 * Delete old temp-dirs
	 */
	function clean_backup_dir()
	{
		$dir = api_get_path(SYS_ARCHIVE_PATH);
		if ($handle = @ opendir($dir))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file != "." && $file != ".." && strpos($file,'CourseArchiver_') === 0 && is_dir($dir.'/'.$file))
				{
					rmdirr($dir.'/'.$file);
				}
			}
			closedir($handle);
		}
	}
	/**
	 * Write a course and all its resources to a zip-file.
	 * @return string A pointer to the zip-file
	 */
	function write_course($course) {
		$perm_dirs = api_get_permissions_for_new_directories();

		CourseArchiver::clean_backup_dir();
		// Create a temp directory
		$tmp_dir_name = 'CourseArchiver_'.api_get_unique_id();
		$backup_dir   = api_get_path(SYS_ARCHIVE_PATH).$tmp_dir_name.'/';

		// All course-information will be stored in course_info.dat
		$course_info_file = $backup_dir.'course_info.dat';
		$zip_dir = api_get_path(SYS_ARCHIVE_PATH);
		$user = api_get_user_info();
		$zip_file = $user['user_id'].'_'.$course->code.'_'.date("Ymd-His").'.zip';
		$php_errormsg = '';
		$res = @mkdir($backup_dir, $perm_dirs);
		if ($res === false)
		{
			//TODO set and handle an error message telling the user to review the permissions on the archive directory
      		error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini').' - This error, occuring because your archive directory will not let this script write data into it, will prevent courses backups to be created',0);
		}
		// Write the course-object to the file
		$fp = @fopen($course_info_file, 'w');
		if ($fp === false)
		{
      		error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini'),0);
		}
		$res = @fwrite($fp, base64_encode(serialize($course)));
		if ($res === false)
		{
      		error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini'),0);
		}
		$res = @fclose($fp);
		if ($res === false)
		{
      		error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini'),0);
		}

		// Copy all documents to the temp-dir
		if (is_array($course->resources[RESOURCE_DOCUMENT])) {
			foreach ($course->resources[RESOURCE_DOCUMENT] as $id => $document) {
				if ($document->file_type == DOCUMENT) {
					$doc_dir = $backup_dir.$document->path;
					@mkdir(dirname($doc_dir), $perm_dirs, true);
					if (file_exists($course->path.$document->path)) {
						copy($course->path.$document->path, $doc_dir);
					}
				} else {
					@mkdir($backup_dir.$document->path, $perm_dirs, true);
				}
			}
		}

		// Copy all scorm documents to the temp-dir
		if (is_array($course->resources[RESOURCE_SCORM])) {
			foreach ($course->resources[RESOURCE_SCORM] as $id => $document) {
				$doc_dir = dirname($backup_dir.$document->path);
				@mkdir($doc_dir, $perm_dirs, true);
				copyDirTo($course->path.$document->path, $doc_dir, false);
			}
		}
		
		//Copy calendar attachments
		
		if (is_array($course->resources[RESOURCE_EVENT])) {
			$doc_dir = dirname($backup_dir.'/upload/calendar/');
			@mkdir($doc_dir, $perm_dirs, true);		
			copyDirTo($course->path.'upload/calendar/', $doc_dir, false);
		}		
		
		//Copy learningpath author image		
		if (is_array($course->resources[RESOURCE_LEARNPATH])) {
			$doc_dir = dirname($backup_dir.'/upload/learning_path/');
			@mkdir($doc_dir, $perm_dirs, true);
			copyDirTo($course->path.'upload/learning_path/', $doc_dir, false);
		}
		
		//Copy announcements attachments
		
		if (is_array($course->resources[RESOURCE_ANNOUNCEMENT])) {
			$doc_dir = dirname($backup_dir.'/upload/announcements/');
			@mkdir($doc_dir, $perm_dirs, true);		
			copyDirTo($course->path.'upload/announcements/', $doc_dir, false);
		}

		// Zip the course-contents
		$zip = new PclZip($zip_dir.$zip_file);
		$zip->create($zip_dir.$tmp_dir_name, PCLZIP_OPT_REMOVE_PATH, $zip_dir.$tmp_dir_name.'/');
		//$zip->deleteByIndex(0);
		// Remove the temp-dir.
		rmdirr($backup_dir);
		return ''.$zip_file;
	}
	/**
	 *
	 */
	function get_available_backups($user_id = null)
	{
		global $dateTimeFormatLong;
		$backup_files = array();
		$dirname = api_get_path(SYS_ARCHIVE_PATH).'';
		if ($dir = opendir($dirname)) {
  			while (($file = readdir($dir)) !== false) {
  				 $file_parts = explode('_',$file);
  				 if(count($file_parts) == 3)
  				 {
  				 	$owner_id = $file_parts[0];
  				 	$course_code = $file_parts[1];
  				 	$file_parts = explode('.',$file_parts[2]);
  				 	$date = $file_parts[0];
  				 	$ext = $file_parts[1];
  				 	if($ext == 'zip' && ($user_id != null && $owner_id == $user_id || $user_id == null) )
  				 	{
  				 		$date = substr($date,0,4).'-'.substr($date,4,2).'-'.substr($date,6,2).' '.substr($date,8,2).':'.substr($date,10,2).':'.substr($date,12,2);
  				 		$backup_files[] = array('file' => $file, 'date' => $date, 'course_code' => $course_code);
  				 	}
  				 }
  			}
  			closedir($dir);
		}
		return $backup_files;
	}
	/**
	 *
	 */
	function import_uploaded_file($file)
	{
		$new_filename = uniqid('').'.zip';
        $new_dir = api_get_path(SYS_ARCHIVE_PATH);
        if(is_dir($new_dir) && is_writable($new_dir))
        {
		  move_uploaded_file($file,api_get_path(SYS_ARCHIVE_PATH).''.$new_filename);
          return $new_filename;
        }
        return false;
	}
	/**
	 * Read a course-object from a zip-file
	 * @return course The course
	 * @param boolean $delete Delete the file after reading the course?
	 * @todo Check if the archive is a correct Dokeos-export
	 */
	function read_course($filename,$delete = false)
	{
		CourseArchiver::clean_backup_dir();
		// Create a temp directory
		$tmp_dir_name = 'CourseArchiver_'.uniqid('');
		$unzip_dir = api_get_path(SYS_ARCHIVE_PATH).''.$tmp_dir_name;
		@mkdir($unzip_dir, api_get_permissions_for_new_directories(), true);
		@copy(api_get_path(SYS_ARCHIVE_PATH).''.$filename,$unzip_dir.'/backup.zip');
		// unzip the archive
		$zip = new PclZip($unzip_dir.'/backup.zip');
		@chdir($unzip_dir);
		$zip->extract();
		// remove the archive-file
		if($delete)
		{
			@unlink(api_get_path(SYS_ARCHIVE_PATH).''.$filename);
		}
		// read the course
		if(!is_file('course_info.dat'))
		{
			return new Course();
		}
		$fp = @fopen('course_info.dat', "r");
		$contents = @fread($fp, filesize('course_info.dat'));
		@fclose($fp);
		// CourseCopyLearnpath class appeared in Chamilo 1.8.7, it is the former Learnpath class in the "Copy course" tool.
		// For backward comaptibility with archives created on Chamilo 1.8.6.2 or older systems, we have to do the following:
		// Before unserialization, if class name "Learnpath" was found, it should be renamed as "CourseCopyLearnpath".
		$course = unserialize(str_replace('O:9:"Learnpath":', 'O:19:"CourseCopyLearnpath":', base64_decode($contents)));
		if( get_class($course) != 'Course')
		{
			return new Course();
		}
		$course->backup_path = $unzip_dir;
		return $course;
	}
}