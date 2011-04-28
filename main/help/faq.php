<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package chamilo.help
 */

// Language file that needs to be included
$language_file = 'help';

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$help_name = Security::remove_XSS($_GET['open']);

header('Content-Type: text/html; charset='. api_get_system_encoding());
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<title>
<?php echo get_lang('H'.$help_name); ?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CSS_PATH); ?>chamilo/default.css";
/*]]>*/
</style>
<?php
if (api_get_setting('stylesheets') != '') {
?>
	<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	@import "<?php echo api_get_path(WEB_CSS_PATH), api_get_setting('stylesheets'); ?>/default.css";
	/*]]>*/
	</style>
<?php
}
?>
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<div style="margin:10px;">
<div style="text-align:right;"><a href="javascript: window.close();"><?php echo get_lang('Close'); ?></a></div>
<h4>
<?php
echo get_lang('Faq');

if (api_is_platform_admin()) {
	echo '&nbsp;<a href="faq.php?edit=true"><img src="'.api_get_path(WEB_IMG_PATH).'edit.gif" /></a>';
}
?>
</h4>
<?php
$faq_file = 'faq.html';
if (!empty($_GET['edit']) && $_GET['edit'] == 'true' && api_is_platform_admin()) {
	$form = new FormValidator('set_faq', 'post', 'faq.php?edit=true');
	$form -> add_html_editor('faq_content', null, false, false, array('ToolbarSet' => 'FAQ', 'Width' => '100%', 'Height' => '300'));
	$form -> addElement('submit', 'faq_submit', get_lang('Ok'));
	$faq_content = @(string)file_get_contents(api_get_path(SYS_PATH).'home/faq.html');
	$faq_content = api_to_system_encoding($faq_content, api_detect_encoding(strip_tags($faq_content)));
	$form -> setDefaults(array('faq_content' => $faq_content));
	if ($form -> validate()) {
		$content = $form -> getSubmitValue('faq_content');
		$fpath = api_get_path(SYS_PATH).'home/'.$faq_file;
		if (is_file($fpath) && is_writeable($fpath)) {
			$fp = fopen(api_get_path(SYS_PATH).'home/'.$faq_file, 'w');
			fwrite($fp, $content);
			fclose($fp);
		} else {
			echo get_lang('WarningFaqFileNonWriteable').'<br />';
		}
		echo $content;
	} else {
		$form -> display();
	}
} else {
	$faq_content = @(string)file_get_contents(api_get_path(SYS_PATH).'home/'.$faq_file);
	$faq_content = api_to_system_encoding($faq_content, api_detect_encoding(strip_tags($faq_content)));
	echo $faq_content;
}
?>
<div style="text-align:right;"><a href="javascript: window.close();"><?php echo get_lang('Close'); ?></a></div>
</div>
</body>
</html>