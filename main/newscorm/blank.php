<?php //$id: $
/**
 * Script that displays a blank page (with later a message saying why)
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
 
if(isset($_GET['error']) && $_GET['error']=='document_deleted'){
	$language_file[] = "learnpath";
	require('../inc/global.inc.php');
}

?>

<html>
<head>
	<style type="text/css">
		<?php
		$my_style = api_get_setting('stylesheets');
		if(empty($my_style)){$my_style = 'default';}
		echo '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css";'."\n";
		?>
	</style>
</head>
<body>

<?php
if(isset($_GET['error']) && $_GET['error']=='document_deleted'){
	Display::display_error_message(get_lang('DocumentHasBeenDeleted'));
}
?>

</body>
</html>