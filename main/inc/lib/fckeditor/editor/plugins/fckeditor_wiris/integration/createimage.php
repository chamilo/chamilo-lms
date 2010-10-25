<?php
include 'libwiris.php';

if (!empty($_POST['mml'])) {
	$toSave = $_POST['mml'] . "\n";
	
	$config = wrs_loadConfig(WRS_CONFIG_FILE);
	global $wrs_imageConfigProperties, $wrs_xmlFileAttributes;
	
	foreach ($wrs_xmlFileAttributes as $serverParam) {
		$configKey = $wrs_imageConfigProperties[$serverParam];
		
		if (isset($_POST[$serverParam])) {
			$config[$configKey] = $_POST[$serverParam];
		}
		
		if (isset($config[$configKey])) {
			$toSave .= $config[$configKey] . "\n";
		}
		else {
			$toSave .= "\n";
		}
	}
	
	if (isset($config['wirisimagefontranges'])) {
		$fontRanges = explode(',', $config['wirisimagefontranges']);
		
		foreach ($fontRanges as $fontRangeName) {
			$fontRangeName = trim($fontRangeName);
			
			if (isset($config[$fontRangeName])) {
				$toSave .= $config[$fontRangeName] . "\n";
			}
		}
	}
	
	$fileName = md5($toSave);
	$url = dirname($_SERVER['PHP_SELF']) . '/showimage.php?formula=' . $fileName . '.png';
	$filePath = WRS_FORMULA_DIRECTORY . '/' . $fileName . '.xml';
	
	if (!is_file($filePath)) {
		if (file_put_contents($filePath, $toSave) !== false) {
			echo (isset($_POST['returnDigest']) && $_POST['returnDigest'] != 'false') ? $fileName . ':' . $url : $url;
		}
		else {
			echo 'Error: can not create the image. Check your file privileges.';
		}
	}
	else {
		echo (isset($_POST['returnDigest']) && $_POST['returnDigest'] != 'false') ? $fileName . ':' . $url : $url;
	}
}
else {
	echo 'Error: no mathml has been sended.';
}
?>