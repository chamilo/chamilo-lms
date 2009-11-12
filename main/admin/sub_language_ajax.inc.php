<?php
/* For licensing terms, see /dokeos_license.txt */
// including the global dokeos file
require_once '../inc/global.inc.php';
require_once 'sub_language.class.php';
/*
 * search a term and return description from a glossary
 */
global $charset;

$new_language		= Security::remove_XSS($_REQUEST['new_language']);
$language_variable	= Security::remove_XSS($_REQUEST['variable_language']);
$file_id			= Security::remove_XSS($_REQUEST['file_id']);

if (isset($new_language) && isset($language_variable) && isset($file_id)) {
	$file_language = $language_files_to_load[$file_id].'.inc.php';
	$id_language = intval($_REQUEST['id']);
	$sub_language_id = intval($_REQUEST['sub']);
	$all_data_of_language=SubLanguageManager::get_all_information_of_sub_language($id_language,$sub_language_id);
	$dokeos_path_folder=api_get_path('SYS_LANG_PATH').$all_data_of_language['dokeos_folder'].'/'.$file_language;
	$all_file_of_directory=SubLanguageManager::get_all_language_variable_in_file($dokeos_path_folder);
	SubLanguageManager::add_file_in_language_directory ($dokeos_path_folder);

	//update variable language
	$all_file_of_directory[$language_variable]="\"".mb_convert_encoding($new_language,$charset,'UTF-8')."\";";

	foreach ($all_file_of_directory as $key_value=>$value_info) {
		SubLanguageManager::write_data_in_file ($dokeos_path_folder,$value_info,$key_value);
	}
}