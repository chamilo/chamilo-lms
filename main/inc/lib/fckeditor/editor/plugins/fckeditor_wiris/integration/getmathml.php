<?php
include 'libwiris.php';
$digest = NULL;

if (isset($_POST['md5']) && mb_strlen($_POST['md5']) == 32) {		// Support for "generic simple" integration.
	$digest = $_POST['md5'];
}
else if (isset($_POST['digest'])) {		// Support for future integrations (where maybe they aren't using md5 sums).
	$digest = $_POST['digest'];
}

if (!is_null($digest)) {
	$filePath = WRS_FORMULA_DIRECTORY . '/' . basename($digest) . '.xml';
	
	if (is_file($filePath)) {
		if (($handle = fopen($filePath, 'r')) !== false) {
			if (($line = fgets($handle)) !== false) {
				echo $line;
			}
			
			fclose($handle);
		}
		else {
			echo 'Error: can not read the formula. Check your file privileges.';
		}
	}
	else {
		echo 'Error: formula does not exists.';
	}
}
else {
	echo 'Error: no digest has been sended.';
}
?>