<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package dokeos.help
 */

// Language file that needs to be included
$language_file = 'help';

require '../inc/global.inc.php';

$help_name = Security::remove_XSS($_GET['open']);

header('Content-Type: text/html; charset='. api_get_system_encoding());

/*
 * Choose CSS style platform's, user's, course's, or Learning path CSS
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
			if(!empty($mycoursetheme) && $mycoursetheme != $my_style) {
				$my_style = $mycoursetheme;			// course's css
			}
		}

		$mycourselptheme = api_get_course_setting('allow_learning_path_theme');
		if (!empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
			global $lp_theme_css; //  it comes from the lp_controller.php
			global $lp_theme_config; // it comes from the lp_controller.php

			if (!empty($lp_theme_css)) {
				$theme = $lp_theme_css;
				if (!empty($theme) && $theme != $my_style) {
					$my_style = $theme;	 // LP's css
				}
			}

		}
	}
}

if ($lp_theme_log) {
	$my_style = $platform_theme;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<title>
<?php echo get_lang('H'.$help_name); ?>
</title>
<?php
if (api_get_setting('stylesheets') != '') {
?>
	<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	@import "<?php echo api_get_path(WEB_CSS_PATH), $my_style;?>/default.css";
	/*]]>*/
	</style>
<?php
}
?>
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<div style="margin:10px;">
<div style="text-align:right;"><a href="javascript: window.close();"><?php echo get_lang('Close'); ?></a></div>
<a href="faq.php"><?php echo get_lang('AccessToFaq') ?></a>
<h4>
<?php echo get_lang('H'.$help_name); ?>
</h4>
<?php echo get_lang($help_name.'Content'); ?>
<br /><br />
<a href="faq.php"><?php echo get_lang('AccessToFaq'); ?></a>
<div style="text-align:right;"><a href="javascript: window.close();"><?php echo get_lang('Close'); ?></a></div>
</div>
</body>
</html>