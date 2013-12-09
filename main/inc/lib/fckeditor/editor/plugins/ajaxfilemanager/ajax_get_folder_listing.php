<?php
/* For licensing terms, see /license.txt */

require_once '../../../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/editor/plugins/ajaxfilemanager/inc/config.php';

echo '{';
	$count = 1;
	foreach(getFolderListing(CONFIG_SYS_ROOT_PATH) as $k=>$v)
	{


		echo (($count > 1)?', ':''). "'" . $v . "':'" . $k . "'";
		$count++;
	}
	echo "}";
?>
