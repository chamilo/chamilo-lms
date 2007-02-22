<?php
require ('../inc/global.inc.php');

define('FRAME','footer');

echo '<html><head><style>';

if(api_get_setting('stylesheets')!=""){
	$css=api_get_setting('stylesheets');
}
else{
	$css='default';
}

echo '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$css.'/default.css'.'";';

echo '</style></head><body><br>';

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();

?>
