<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();

$extraField = null;
$extraFieldOption = null;
$variableLanguage = null;
$originalName = null;

if (isset($_GET['extra_field'])) {
    $extraField = $em->find('ChamiloCoreBundle:ExtraField', intval($_GET['extra_field']));
    $variableLanguage = '$'.api_underscore_to_camel_case($extraField->getVariable());
    $originalName = $extraField->getDisplayText(false);
} elseif (isset($_GET['extra_field_option'])) {
    $extraFieldOption = $em->find('ChamiloCoreBundle:ExtraFieldOptions', intval($_GET['extra_field_option']));
    $extraField = $extraFieldOption->getField();
    $variableLanguage = '$'.ExtraFieldOption::getLanguageVariable($extraFieldOption->getDisplayText());
    $originalName = $extraFieldOption->getDisplayText(false);
}

if (!$extraField || empty($variableLanguage) || empty($originalName)) {
    api_not_allowed(true);
}

$languageId = isset($_GET['sub_language']) ? (int) $_GET['sub_language'] : 0;

$languages = $em
    ->getRepository('ChamiloCoreBundle:Language')
    ->findAllPlatformSubLanguages();

$languagesOptions = [0 => get_lang('None')];

foreach ($languages as $language) {
    $languagesOptions[$language->getId()] = $language->getOriginalName();
}

$translateUrl = api_get_path(WEB_CODE_PATH).'admin/sub_language_ajax.inc.php';

$form = new FormValidator('new_lang_variable', 'POST', $translateUrl);
$form->addHeader(get_lang('AddWordForTheSubLanguage'));
$form->addText('variable_language', get_lang('LanguageVariable'), false);
$form->addText('original_name', get_lang('OriginalName'), false);
$form->addSelect(
    'sub_language',
    [get_lang('SubLanguage'), get_lang('OnlyActiveSubLanguagesAreListed')],
    $languagesOptions
);

if ($languageId) {
    $languageInfo = api_get_language_info($languageId);
    $form->addText(
        'new_language',
        [get_lang('Translation'), get_lang('IfThisTranslationExistsThisWillReplaceTheTerm')]
    );
    $form->addHidden('file_id', 0);
    $form->addHidden('id', $languageInfo['parent_id']);
    $form->addHidden('sub', $languageInfo['id']);
    $form->addHidden('sub_language_id', $languageInfo['id']);
    $form->addHidden('redirect', true);
    $form->addHidden('extra_field_type', $extraField->getExtraFieldType());
    $form->addButtonSave(get_lang('Save'));
}

$form->setDefaults([
    'variable_language' => $variableLanguage,
    'original_name' => $originalName,
    'sub_language' => $languageId,
]);
$form->addRule('sub_language', get_lang('Required'), 'required');
$form->freeze(['variable_language', 'original_name']);

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin', 'name' => get_lang('Administration')];

switch ($extraField->getExtraFieldType()) {
    case ExtraField::USER_FIELD_TYPE:
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type=user',
            'name' => get_lang('UserFields'),
        ];
        break;
    case ExtraField::COURSE_FIELD_TYPE:
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type=course',
            'name' => get_lang('CourseFields'),
        ];
        break;
    case ExtraField::SESSION_FIELD_TYPE:
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type=session',
            'name' => get_lang('SessionFields'),
        ];
        break;
}

$view = new Template(get_lang('AddWordForTheSubLanguage'));
$view->assign('form', $form->returnForm());
$template = $view->get_template('extrafield/translate.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();
