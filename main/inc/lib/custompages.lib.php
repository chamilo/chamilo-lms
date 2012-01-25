<?php
// Custom Pages lib
// Used to implement the loading of custom pages
// 2011, Jean-Karim Bockstael <jeankarim@cblue.be>

require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';

class CustomPages {
	
	public static function displayPage($page_name, $content=array()) {
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

	public static function getURLImages($url_id = null) {
		if (is_null($url_id)) {
			$url = 'http://'.$_SERVER['HTTP_HOST'].'/';
			$url_id = UrlManager::get_url_id($url);
		}
		$url_images_dir = api_get_path(SYS_PATH).'custompages/url-images/';
		$images = array();
		for ($img_id = 1; $img_id <= 3; $img_id++) {
			 if (file_exists($url_images_dir.$url_id.'_url_image_'.$img_id.'.png')) {
			 	$images[] = api_get_path(WEB_PATH).'custompages/url-images/'.$url_id.'_url_image_'.$img_id.'.png';
			 }
		}
		return $images;
	}
}
?>
