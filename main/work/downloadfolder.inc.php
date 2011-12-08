<?php
/* For licensing terms, see /license.txt */

/**
 *	Functions and main code for the download folder feature
 *  @todo use ids instead of the path like the document tool 
 *	@package chamilo.work
 */

$work_id = $_GET['id'];

$work_data = get_work_data_by_id($work_id);
if (empty($work_data)) {
    exit;
}

//prevent some stuff
if (empty($path)) {
	$path = '/';
}

if (empty($_course) || empty($_course['path'])) {
    api_not_allowed();
}
$sys_course_path = api_get_path(SYS_COURSE_PATH);

//zip library for creation of the zipfile
require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';

//Creating a ZIP file
$temp_zip_file = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";

$zip_folder = new PclZip($temp_zip_file);

$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$prop_table              = Database::get_course_table(TABLE_ITEM_PROPERTY);

//Put the files in the zip
//2 possibilities: admins get all files and folders in the selected folder (except for the deleted ones)
//normal users get only visible files that are in visible folders

//admins are allowed to download invisible files
$files = array();
$course_id = api_get_course_int_id();

if (api_is_allowed_to_edit()) {	
	//search for all files that are not deleted => visibility != 2
    
    $sql = "SELECT url, title FROM $tbl_student_publication AS work, $prop_table AS props  
 			WHERE   props.c_id = $course_id AND 
 			        work.c_id = $course_id AND 
 			        props.tool='work' AND 
 			        work.id=props.ref AND 
 			        work.parent_id = $work_id AND 
 			        work.filetype='file' AND props.visibility<>'2'";
	$query = Database::query($sql);
	//add tem to the zip file
	while ($not_deleted_file = Database::fetch_assoc($query)) {
		if (file_exists($sys_course_path.$_course['path'].'/'.$not_deleted_file['url'])) {
			$files[basename($not_deleted_file['url'])] = $not_deleted_file['title'];
		    $zip_folder->add($sys_course_path.$_course['path'].'/'.$not_deleted_file['url'], PCLZIP_OPT_REMOVE_PATH, $sys_course_path.$_course['path'].'/work', PCLZIP_CB_PRE_ADD, 'my_pre_add_callback');
		}
    }    
} else {
    //for other users, we need to create a zipfile with only visible files and folders    
    $sql = "SELECT url, title FROM $tbl_student_publication AS work, $prop_table AS props  
            WHERE   props.c_id = $course_id AND work.c_id = $course_id AND 
                    props.tool='work' AND 
                    work.accepted = 1 AND 
                    work.id=props.ref AND 
                    work.parent_id = $work_id AND
                    work.filetype='file' AND 
                    props.visibility = '1' AND props.insert_user_id='".api_get_user_id()."' ";
    $query = Database::query($sql);
    //add tem to the zip file
    while ($not_deleted_file = Database::fetch_assoc($query)) {
        if (file_exists($sys_course_path.$_course['path'].'/'.$not_deleted_file['url'])) {
            $files[basename($not_deleted_file['url'])] = $not_deleted_file['title'];
            $zip_folder->add($sys_course_path.$_course['path'].'/'.$not_deleted_file['url'], PCLZIP_OPT_REMOVE_PATH, $sys_course_path.$_course['path'].'/work', PCLZIP_CB_PRE_ADD, 'my_pre_add_callback');
        }
    }   
    
}//end for other users

//logging
event_download(basename($work_data['title']).'.zip (folder)');

//start download of created file
$name = basename($work_data['title']).'.zip';

if (Security::check_abs_path($temp_zip_file, api_get_path(SYS_ARCHIVE_PATH))) {    
    DocumentManager::file_send_for_download($temp_zip_file, true, $name);    
    @unlink($temp_zip_file);    
    exit;    
}

/*	Extra function (only used here) */

function my_pre_add_callback($p_event, &$p_header) {
	global $files;	
	if (isset($files[basename($p_header['stored_filename'])])) {
		$p_header['stored_filename'] = $files[basename($p_header['stored_filename'])];
		return 1;
	}
	return 0;	
}



/**
 * Return the difference between two arrays, as an array of those key/values
 * Use this as array_diff doesn't give the
 *
 * @param array $arr1 first array
 * @param array $arr2 second array
 * @return difference between the two arrays
 */
function diff($arr1, $arr2) {
	$res = array();
	$r = 0;
	foreach ($arr1 as $av) {
		if (!in_array($av, $arr2)) {
			$res[$r] = $av;
			$r++;
		}
	}
	return $res;
}
