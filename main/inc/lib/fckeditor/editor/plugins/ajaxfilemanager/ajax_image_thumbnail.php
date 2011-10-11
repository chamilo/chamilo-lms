<?php
/**
 * ajax preview
 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
 * @link www.phpletter.com
 * @since 22/April/2007
 *
 */

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "inc" . DIRECTORY_SEPARATOR . "config.php");	
$path = base64_decode($_GET['path']);
if(!empty($path) && file_exists($path) && is_file($path)) {
	require_once CLASS_IMAGE;	
	$image = new ImageAjaxFileManager(true);
	if($image->loadImage($path)) {
		if($image->resize(CONFIG_IMG_THUMBNAIL_MAX_X, CONFIG_IMG_THUMBNAIL_MAX_Y, true, true)) {
			$image->showImage();
		} else {
			echo PREVIEW_NOT_PREVIEW . ".";	
		}
	} else {
		echo PREVIEW_NOT_PREVIEW . "..";			
	}		
} else {
	echo PREVIEW_NOT_PREVIEW . "...";
}