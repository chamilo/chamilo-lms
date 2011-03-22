<?php
// Custom Pages lib
// Used to implement the loading of custom pages
// 2011, Jean-Karim Bockstael <jeankarim@cblue.be>

class CustomPages {
	
	public static function displayPage($page_name) {
		$pages_dir = api_get_path(SYS_PATH).'custompages/';
		$file_name = $pages_dir.$page_name.'.php';
		if (file_exists($file_name)) {
			include($file_name);
			exit;
		}
		else {
			error_log('CustomPages::displayPage : could not read file '.$file_name);
		}
	}
}
?>
