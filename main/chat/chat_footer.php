<?php

require ('../inc/global.inc.php');

echo '<html><head><style>';

echo '@import "'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css'.'";';

echo '</style></head><body>';

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();

?>
