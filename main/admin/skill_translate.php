<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Skill;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();

$skill = null;
$extraFieldOption = null;
$variableLanguage = null;
$originalName = null;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'name';

if (isset($_GET['skill'])) {
    /** @var Skill $skill */
    $skill = $em->find('ChamiloCoreBundle:Skill', intval($_GET['skill']));

    if ($action === 'name') {
        $variableLanguage = ChamiloApi::getLanguageVar(
            $skill->getName(false),
            'Skill'
        );
        $originalName = $skill->getName(false);
    } elseif ($action === 'code') {
        $variableLanguage = ChamiloApi::getLanguageVar(
            $skill->getShortCode(false),
            'SkillCode'
        );
        $originalName = $skill->getShortCode(false);
    }
}

if (!$skill || empty($variableLanguage)) {
    api_not_allowed(true);
}

if (empty($originalName)) {
    Display::addFlash(
        Display::return_message(get_lang('CanNotTranslate'), 'error')
    );
    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/skill_edit.php?id='.$skill->getId());
    exit;
}

$languageId = isset($_GET['sub_language']) ? intval($_GET['sub_language']) : 0;

$languages = $em
    ->getRepository('ChamiloCoreBundle:Language')
    ->findAllPlatformSubLanguages();

$languagesOptions = [0 => get_lang('None')];

/** @var Language $language */
foreach ($languages as $language) {
    $languagesOptions[$language->getId()] = $language->getOriginalName();
}

$translateUrl = api_get_path(WEB_CODE_PATH).'admin/sub_language_ajax.inc.php?skill='.$skill->getId();

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
    $form->addButtonSave(get_lang('Save'));
}

$form->setDefaults([
    'variable_language' => '$'.$variableLanguage,
    'original_name' => $originalName,
    'sub_language' => $languageId,
    'new_language' => $action === 'code' ? $skill->getShortCode() : $skill->getName(),
]);
$form->addRule('sub_language', get_lang('Required'), 'required');
$form->freeze(['variable_language', 'original_name']);

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'skill_list.php', 'name' => get_lang('ManageSkills')];

$view = new Template(get_lang('AddWordForTheSubLanguage'));
$view->assign('form', $form->returnForm());
$template = $view->get_template('extrafield/translate.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();
