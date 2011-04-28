<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package dokeos.help
 */

// Language file that needs to be included
$language_file = 'help';
require_once '../inc/global.inc.php';
$help_name = Security::remove_XSS($_GET['open']);

header('Content-Type: text/html; charset='. api_get_system_encoding());

/*
 * Choose CSS style platform's, user's, course's, or Learning path CSS
 */

$my_style = api_get_visual_theme();

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
<a href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq') ?></a>
<h4>
<?php echo get_lang('H'.$help_name); ?>
</h4>
<?php echo get_lang($help_name.'Content'); ?>
<br /><br />
<a href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq'); ?></a>
</div>
</body>
</html>