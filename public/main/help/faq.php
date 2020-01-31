<?php
/* For licensing terms, see /license.txt */

/**
 * This script displays a help window.
 */
require_once __DIR__.'/../inc/global.inc.php';
$help_name = isset($_GET['open']) ? Security::remove_XSS($_GET['open']) : null;

Display::display_header(get_lang('Frequently Asked Question'));

if (api_is_platform_admin()) {
    echo '&nbsp;<a href="faq.php?edit=true">'.Display::return_icon('edit.png').'</a>';
}

echo Display::page_header(get_lang('Frequently Asked Question'));

$faq_file = 'faq.html';
if (!empty($_GET['edit']) && 'true' == $_GET['edit'] && api_is_platform_admin()) {
    $form = new FormValidator('set_faq', 'post', 'faq.php?edit=true');
    $form->addHtmlEditor(
        'faq_content',
        null,
        false,
        false,
        ['ToolbarSet' => 'FAQ', 'Width' => '100%', 'Height' => '300']
    );
    $form->addButtonSave(get_lang('Validate'), 'faq_submit');
    $faq_content = @(string) file_get_contents(api_get_path(SYS_APP_PATH).'home/faq.html');
    $faq_content = api_to_system_encoding($faq_content, api_detect_encoding(strip_tags($faq_content)));
    $form->setDefaults(['faq_content' => $faq_content]);
    if ($form->validate()) {
        $content = $form->getSubmitValue('faq_content');
        $fpath = api_get_path(SYS_APP_PATH).'home/'.$faq_file;
        if (is_file($fpath) && is_writeable($fpath)) {
            $fp = fopen(api_get_path(SYS_APP_PATH).'home/'.$faq_file, 'w');
            fwrite($fp, $content);
            fclose($fp);
        } else {
            echo Display::return_message(get_lang('File not writeable'), 'warning');
        }
        echo $content;
    } else {
        $form->display();
    }
} else {
    $faq_content = @(string) file_get_contents(api_get_path(SYS_APP_PATH).'home/'.$faq_file);
    $faq_content = api_to_system_encoding($faq_content, api_detect_encoding(strip_tags($faq_content)));
    echo $faq_content;
}

Display::display_footer();
