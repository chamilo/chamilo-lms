<?php
/* For licensing terms, see /dokeos_license.txt */

// including the global dokeos file
require_once '../inc/global.inc.php';
require_once 'admin.class.php';
/*
 * search a term and return description from a glossary
 */
global $charset;
$new_language=Security::remove_XSS($_POST['new_language']);
$language_variable=Security::remove_XSS($_POST['variable_language']);
$file_language=Security::remove_XSS($_POST['file_language']);
$id_language=Security::remove_XSS($_POST['id']);
$sub_language_id=Security::remove_XSS($_POST['sublanguage_id']);
 
$all_data_of_language=AdminManager::get_all_information_of_sub_language($id_language,$sub_language_id);
$dokeos_path_folder=api_get_path('SYS_LANG_PATH').$all_data_of_language['dokeos_folder'].'/'.$file_language;
$all_file_of_directory=AdminManager::get_all_language_variable_in_file($dokeos_path_folder);
AdminManager::add_file_in_language_directory ($dokeos_path_folder);

//update variable language
$all_file_of_directory[$language_variable]="\"".mb_convert_encoding($new_language,$charset,'UTF-8')."\";";

foreach ($all_file_of_directory as $key_value=>$value_info) {
	AdminManager::write_data_in_file ($dokeos_path_folder,$value_info,$key_value);
}