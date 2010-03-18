<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

define('FRAME', 'footer');

?><!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<style>
<?php

/*
 * Choose CSS style (platform's, user's, or course's)
 */

$platform_theme = api_get_setting('stylesheets'); 	// plataform's css
$my_style = $platform_theme;
if (api_get_setting('user_selected_theme') == 'true') {
	$useri = api_get_user_info();
	$user_theme = $useri['theme'];
	if (!empty($user_theme) && $user_theme != $my_style) {
		$my_style = $user_theme;					// user's css
	}
}

$mycourseid = api_get_course_id();
if (!empty($mycourseid) && $mycourseid != -1) {
	if (api_get_setting('allow_course_theme') == 'true') {
		$mycoursetheme = api_get_course_setting('course_theme');
		if (!empty($mycoursetheme) && $mycoursetheme != -1) {
			if (!empty($mycoursetheme) && $mycoursetheme != $my_style) {
				$my_style = $mycoursetheme;			// course's css
			}
		}
	}
}
?>
@import "<?php echo api_get_path(WEB_CSS_PATH).$my_style.'/default.css'; ?>";
</style>
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<br />
<?php

Display::display_footer();
