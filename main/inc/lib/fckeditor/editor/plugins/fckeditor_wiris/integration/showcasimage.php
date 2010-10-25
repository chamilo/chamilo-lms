<?php
include 'libwiris.php';

if (empty($_GET['formula'])) {
	echo 'Error: no image name has been sended.';
}
else {
	$formula = basename($_GET['formula']);
	$filePath = WRS_CACHE_DIRECTORY . '/' . $formula;
	
	if (is_file($filePath)) {
		header('Content-Type: image/png');
		readfile($filePath);
	}
	else {
		header('Content-Type: image/gif');
		readfile('../core/cas.gif');
	}
}
?>