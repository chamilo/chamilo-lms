<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once '../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();

$extraFieldInfo = null;
$extraFieldOptionInfo = null;
$variableLanguage = null;
$originalName = null;

if (isset($_GET['extra_field'])) {
    $extraFieldInfo = ExtraField::getInfoById($_GET['extra_field'], false);
    $variableLanguage = '$' . api_underscore_to_camel_case($extraFieldInfo['variable']);
    $originalName = $extraFieldInfo['display_text'];
} elseif (isset($_GET['extra_field_option'])) {
    $extraFieldOptionInfo = ExtraFieldOption::getInfoById($_GET['extra_field_option'], false);
    $variableLanguage = '$' . api_underscore_to_camel_case($extraFieldOptionInfo['display_text']);
    $originalName = $extraFieldOptionInfo['display_text'];
}

if ((empty($extraFieldInfo) && empty($extraFieldOptionInfo)) || empty($variableLanguage) || empty($originalName)) {
    api_not_allowed(true);
}

$languageId = isset($_GET['language']) ? intval($_GET['language']) : 0;

$languages = $em
    ->getRepository('ChamiloCoreBundle:Language')
    ->findAllPlatformSubLanguages();

$languagesOptions = [0 => get_lang('None')];

foreach ($languages as $language) {
    $languagesOptions[$language->getId()] = $language->getOriginalName();
}

$translateUrl = api_get_path(WEB_CODE_PATH) . 'admin/sub_language_ajax.inc.php';

$form = new FormValidator('new_lang_variable', 'POST', $translateUrl);
$form->addHeader(get_lang('AddWordForTheSubLanguage'));
$form->addText('variable_language', get_lang('LanguageVariable'), false);
$form->addText('original_name', get_lang('OriginalName'), false);
$form->addSelect('language', get_lang('Language'), $languagesOptions);

if ($languageId) {
    $languageInfo = api_get_language_info($languageId);

    $form->addText('new_language', get_lang('SubLanguage'));
    $form->addHidden('file_id', 0);
    $form->addHidden('id', $languageInfo['parent_id']);
    $form->addHidden('sub', $languageInfo['id']);
    $form->addHidden('sub_language_id', $languageInfo['id']);
    $form->addHidden('redirect', true);
    $form->addButtonSave(get_lang('Save'));
}

$form->setDefaults([
    'variable_language' => $variableLanguage,
    'original_name' => $originalName,
    'language' => $languageId
]);
$form->addRule('language', get_lang('Required'), 'required');
$form->freeze(['variable_language', 'original_name']);

$view = new Template(get_lang('AddWordForTheSubLanguage'));
$view->assign('form', $form->returnForm());
$template = $view->get_template('extrafield/translate.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();
