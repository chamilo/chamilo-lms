<?php // $Id: downloadfolder.inc.php 17989 2009-01-25 05:51:54Z yannoo $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	Functions and main code for the download folder feature
*
*	@package dokeos.work
==============================================================================
*/

$path = $_GET['path'];
//prevent some stuff
if(empty($path))
{
	$path='/';
}

//zip library for creation of the zipfile
include(api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php');

//we need this path to clean it out of the zip file
//I'm not using dirname as it gives too much problems (cfr. \)
$remove_dir = ($path!='/') ? substr($path,0,strlen($path) - strlen(basename($path))) : '/';

//place to temporarily stash the zipfiles
$temp_zip_dir = $sys_course_path.$_course['path']."/temp";
//create the temp dir if it doesn't exist
//or do a cleanup befor creating the zipfile

if (!is_dir($temp_zip_dir)) {
	mkdir($temp_zip_dir);
} else {
	//cleanup: check the temp dir for old files and delete them
	$handle=opendir($temp_zip_dir);
	while (false!==($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			//the "age" of the file in hours
			$Diff = (time() - filemtime("$temp_zip_dir/$file"))/60/60;
			//delete files older than 4 hours
			if ($Diff > 4) {
				unlink("$temp_zip_dir/$file");
			}
		}
	}
    closedir($handle);
}

//create zipfile of given directory
$temp_zip_file = $temp_zip_dir."/".md5(time()).".zip";
$zip_folder= new PclZip($temp_zip_file);
$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$prop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
//Put the files in the zip
//2 possibilities: admins get all files and folders in the selected folder (except for the deleted ones)
//normal users get only visible files that are in visible folders

//admins are allowed to download invisible files
if (is_allowed_to_edit()) {
	//folder we want to zip --> no longer used, deleted files are included too like this
 	//$what_to_zip = $sys_course_path.$_course['path']."/document".$path;
 	//creation of the zipped folder
	//$zip_folder->create($what_to_zip ,PCLZIP_OPT_REMOVE_PATH, $sys_course_path.$_course['path']."/document".$remove_dir );
	//set the path that will be used in the query
	if($path=='/') {
		$querypath=''; // to prevent ...path LIKE '//%'... in query
	} else {
		$querypath=$path;
	}
	//search for all files that are not deleted => visibility != 2

	$query = Database::query("SELECT url FROM $tbl_student_publication AS work,$prop_table AS props  WHERE props.tool='work' AND work.id=props.ref AND work.url LIKE 'work".$querypath."/%' AND work.filetype='file' AND props.visibility<>'2'",__FILE__,__LINE__);
	//add tem to the zip file
	while ($not_deleted_file = mysql_fetch_assoc($query)) {	//var_dump($sys_course_path.$_course['path']."/".$not_deleted_file['url']);exit();
		$zip_folder->add($sys_course_path.$_course['path']."/".$not_deleted_file['url'],PCLZIP_OPT_REMOVE_PATH, $sys_course_path.$_course['path']."/work".$remove_dir);
	}
}

//for other users, we need to create a zipfile with only visible files and folders
else
{
	if ($path=='/') {
		$querypath=''; // to prevent ...path LIKE '//%'... in query
	} else {
		$querypath=$path;
	}
	//big problem: visible files that are in a hidden folder are included when we do a query for visiblity='v'!!!
	//so... I do it in a couple of steps:
	//1st: get all files that are visible in the given path
	$query = Database::query("SELECT url FROM $tbl_student_publication AS work,$prop_table AS props  WHERE props.tool='work' AND work.id=props.ref AND work.url LIKE 'work".$querypath."/%' AND work.filetype='file' AND props.visibility='1' AND props.lastedit_user_id='".api_get_user_id()."'",__FILE__,__LINE__);
	//add them to an array
	$all_visible_files_path = array();
	while ($all_visible_files = mysql_fetch_assoc($query)) {
		$all_visible_files_path[] = $all_visible_files['url'];
	}
	//2nd: get all folders that are invisible in the given path
	$query2 = Database::query("SELECT url FROM $tbl_student_publication AS work,$prop_table AS props  WHERE props.tool='work' AND work.id=props.ref AND work.url LIKE 'work".$querypath."/%' AND work.filetype='file' AND props.visibility<>'1' AND props.lastedit_user_id='".api_get_user_id()."'",__FILE__,__LINE__);
	//if we get invisible folders, we have to filter out these results from all visible files we found

	if (Database::num_rows($query2)>0) {
		//add tem to an array
		while ($invisible_folders = mysql_fetch_assoc($query2)) {
		//3rd: get all files that are in the found invisible folder (these are "invisible" too)
			$query3 = Database::query("SELECT url FROM $tbl_student_publication AS work,$prop_table AS props  WHERE props.tool='work' AND work.id=props.ref AND work.url LIKE 'work".$invisible_folders['path']."/%' AND work.filetype='file' AND props.visibility='1' AND props.lastedit_user_id='".api_get_user_id()."'",__FILE__,__LINE__);
			//add tem to an array
			while ($files_in_invisible_folder = mysql_fetch_assoc($query3)) {
				$files_in_invisible_folder_path[] = $files_in_invisible_folder['url'];
			}
		}
		//compare the array with visible files and the array with files in invisible folders
		//and keep the difference (= all visible files that are not in an invisible folder)
		$files_for_zipfile = diff((array) $all_visible_files_path,(array) $files_in_invisible_folder_path);
	} else {
		//no invisible folders found, so all visible files can be added to the zipfile
		$files_for_zipfile = $all_visible_files_path;
	}
	//add all files in our final array to the zipfile
	for ($i=0;$i<count($files_for_zipfile);$i++) {
		$zip_folder->add($sys_course_path.$_course['path']."/".$files_for_zipfile[$i],PCLZIP_OPT_REMOVE_PATH, $sys_course_path.$_course['path']."/work".$remove_dir);
	}
}//end for other users
//logging
// launch event
event_download(basename($path).'.zip (folder)');

//start download of created file
$name = basename($path).'.zip';
DocumentManager::file_send_for_download($temp_zip_file,true,$name);
exit;

/**
==============================================================================
*	Extra function (only used here)
==============================================================================
*/

/**
 * Return the difference between two arrays, as an array of those key/values
 * Use this as array_diff doesn't give the
 *
 * @param array $arr1 first array
 * @param array $arr2 second array
 * @return difference between the two arrays
 */
function diff($arr1,$arr2) {
	$res = array(); $r=0;
	foreach ($arr1 as $av) {
		if (!in_array($av,$arr2)){
			$res[$r]=$av; $r++;
		}
	}
	return $res;
}