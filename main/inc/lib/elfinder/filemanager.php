<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../global.inc.php';

Chat::setDisableChat();
$template = new Template();
$template->assign('course_condition', api_get_cidreq());

$language = 'en';
$platformLanguage = api_get_interface_language();
$iso = api_get_language_isocode($platformLanguage);
$filePart = "vendor/studio-42/elfinder/js/i18n/elfinder.$iso.js";
$file = api_get_path(SYS_PATH).$filePart;
$includeFile = '';
if (file_exists($file)) {
    $includeFile = '<script type="text/javascript" src="'.api_get_path(WEB_PATH).$filePart.'"></script>';
    $language = $iso;
}
$questionId = isset($_REQUEST['question_id']) ? (int) $_REQUEST['question_id'] : 0;

$template->assign('question_id', $questionId);
$template->assign('elfinder_lang', $language);
$template->assign('elfinder_translation_file', $includeFile);
$template->display('default/javascript/editor/ckeditor/elfinder.tpl');
