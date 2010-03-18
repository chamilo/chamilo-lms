<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.document
 *	TODO: There is no indication that this file us used for something.
 */

require_once '../inc/global.inc.php';

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
		$mycoursetheme=api_get_course_setting('course_theme');

		if (!empty($mycoursetheme) && $mycoursetheme != -1) {
			if (!empty($mycoursetheme) && $mycoursetheme != $my_style) {
				$my_style = $mycoursetheme;			// course's css
			}
		}

		$mycourselptheme = api_get_course_setting('allow_learning_path_theme');
		if (!empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {

			global $lp_theme_css; //  it comes from the lp_controller.php
			global $lp_theme_config; // it comes from the lp_controller.php

			if (!$lp_theme_config) {
				if ($lp_theme_css != '') {
					$theme = $lp_theme_css;
					if (!empty($theme) && $theme != $my_style) {
						$my_style = $theme;	 // LP's css
					}
				}
			}
		}
	}
}

?><!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<link rel="stylesheet" href="<?php echo api_get_path(WEB_CSS_PATH).$my_style; ?>/default.css" type="text/css">
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<?php

Display::display_footer();
