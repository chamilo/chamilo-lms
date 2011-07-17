<?php
if (php_sapi_name() != 'cli') { exit; } //do not run from browser
$dir = dirname(__FILE__);
$a_dir = realpath($dir.'/../../archive/');
$list = scandir($a_dir);
$t = time()-(86400*7);
foreach($list as $item) {
	if (substr($item,0,1) == '.') { continue; }
	$stat = @stat($a_dir.'/'.$item);
	if ($stat === false) { error_log('Cron task cannot stat '.$a_dir.'/'.$item); continue; }
	if ($stat['mtime'] > $t) { //if the file is older than one week, delete
		recursive_delete($a_dir.'/'.$item);
	}
}

/**
 * Delete a file or recursively delete a directory
 *
 * @param string $str Path to file or directory
 */
function recursive_delete($str){
	if(is_file($str)){
		return @unlink($str);
	}
	elseif(is_dir($str)){
		$scan = glob(rtrim($str,'/').'/*');
		foreach($scan as $index=>$path){
			recursive_delete($path);
		}
		return @rmdir($str);
	}
}
