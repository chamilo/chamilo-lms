<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package chamilo.help
 */
/**
 * Code
 */

// Language file that needs to be included
$language_file = 'help';

require_once '../inc/global.inc.php';

$help_name = Security::remove_XSS($_GET['open']);

Display :: display_header(get_lang('Faq'));

if (api_is_platform_admin()) {
	echo '&nbsp;<a href="faq.php?edit=true"><img src="'.api_get_path(WEB_IMG_PATH).'edit.png" /></a>';
}

echo Display::page_header(get_lang('Faq'));

$faq_file = 'faq.html';
if (!empty($_GET['edit']) && $_GET['edit'] == 'true' && api_is_platform_admin()) {
	$form = new FormValidator('set_faq', 'post', 'faq.php?edit=true');
	$form -> add_html_editor('faq_content', null, false, false, array('ToolbarSet' => 'FAQ', 'Width' => '100%', 'Height' => '300'));
	$form -> addElement('style_submit_button', 'faq_submit', get_lang('Ok'));
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
			Display::display_warning_message(get_lang('WarningFaqFileNonWriteable'));
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

Display::display_footer();
