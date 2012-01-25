<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include.internationalization
 */
/**
 * Code
 */
$_current_dir = str_replace('\\', '/', realpath(dirname(__FILE__))).'/';

$_sys_code_path = str_replace('\\', '/', realpath($_current_dir.'../../../../')).'/';
$_sys_include_path = $_sys_code_path.'inc/';
$_sys_library_path = $_sys_code_path.'inc/lib/';

require_once $_sys_include_path.'global.inc.php';

header('Content-Type: text/html; charset=UTF-8');

$_SESSION['_user']['user_id'] = 1;

function read_file($file_name) {
	$handle = fopen($file_name, 'rb');
	$string = fread($handle, filesize($file_name));
	fclose($handle);
	return $string;
}

function write_file($file_name, $text) {
	$handle = fopen($file_name, "w");
	fwrite($handle, $text);
	fclose($handle);
}

function get_directory_content($path) {
	$exceptions = array('.', '..', 'CVS', '.htaccess', '.svn', '_svn', 'index.html');
	$result = array();
	$path = realpath($path);
	if (!is_dir($path)) return $result;
	if (!$handle = opendir($path)) return $result;
	while (($dir_entry = readdir($handle)) !== false) {
		if (api_in_array_nocase($dir_entry, $exceptions)) continue;
		$dir_entry_full_path = $path .'/'. $dir_entry;
		if (filetype($dir_entry_full_path) != 'dir') {
			$result[] = str_replace("\\", '/', $dir_entry_full_path);
		}
	}
	closedir($handle);
    asort($result);
	return $result;
}

$files = get_directory_content($_current_dir.'sample_texts/');
echo 'Updating language profiles...<br />';
echo '<br />';
foreach ($files as $file) {
	$language = basename($file, '.txt');
	echo $language.'<br />';
	write_file($_current_dir.'language_profiles/'.$language.'.txt', join("\n", _api_generate_n_grams(read_file($file), 'UTF-8', 400, 4)));
}
echo '<br />';
echo 'Done.<br />';
