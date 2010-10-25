<?php
include 'libwiris.php';

$currentPath = dirname($_SERVER['PHP_SELF']) . '/';

if (isset($_POST['image'])) {
	$fileName = md5($_POST['image']);
	$formulaPath = WRS_FORMULA_DIRECTORY . '/' . $fileName . '.xml';
	
	if (isset($_POST['mml']) && !is_file($formulaPath)) {
		file_put_contents($formulaPath, $_POST['mml']);
	}
	
	$url = $currentPath . 'showcasimage.php?formula=' . $fileName . '.png';
	$imagePath = WRS_CACHE_DIRECTORY . '/' . $fileName . '.png';
	
	if (!is_file($imagePath)) {
		if (file_put_contents($imagePath, base64_decode($_POST['image'])) !== false) {
			echo $url;
		}
		else {
			echo $currentPath . '../core/cas.gif';
		}
	}
	else {
		echo $url;
	}
}
else {
	echo $currentPath . '../core/cas.gif';
}
?>