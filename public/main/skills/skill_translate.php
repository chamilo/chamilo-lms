<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Framework\Container;
use Gedmo\Translatable\Entity\Translation;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$request = Container::getRequest();
$em = Database::getManager();
$translationsRepo = $em->getRepository(Translation::class);
$defaultLocale = Container::getParameter('locale');

$skill = null;
$action = $request->query->getString('action') ?: $request->request->getString('action');

$fieldsMap = ['name' => 'title', 'code' => 'shortCode'];

$fieldToTranslate = $fieldsMap[$action] ?? null;

/** @var Skill $skill */
$skill = $em->find(
    Skill::class,
    $request->query->getInt('skill')
);

if (!$skill || empty($skill->getShortCode())) {
    Display::addFlash(
        Display::return_message(get_lang('Could not translate'), 'error')
    );

    header('Location: '.api_get_path(WEB_CODE_PATH).'skills/skill_edit.php?id='.$skill->getId());
    exit;
}

$languages = Container::getLanguageRepository()->getAllAvailable(true)->getQuery()->getResult();
$translations = $translationsRepo->findTranslations($skill);

$languagesOptions = [0 => get_lang('none')];

/** @var Language $language */
foreach ($languages as $language) {
    $languagesOptions[$language->getId()] = $language->getOriginalName();
}

$translateUrl = api_get_self().'?'.http_build_query(['skill' => $skill->getId(), 'action' => $action]);

$skill->setLocale($defaultLocale);
$em->refresh($skill);

$defaults = [
    'original_name' => 'title' === $fieldToTranslate ? $skill->getTitle() : $skill->getShortCode(),
];

$form = new FormValidator('new_lang_variable', 'post', $translateUrl);
$form->addHeader(get_lang('Add terms to the sub-language'));
$form->addText(
    'original_name',
    [
        get_lang('Original name'),
        get_lang('If this term has already been translated, this operation will replace its translation for this sub-language.'),
    ],
    false
);

foreach ($languages as $language) {
    $iso = $language->getIsoCode();
    $form->addText(
        'language['.$language->getId().']',
        $language->getOriginalName(),
        false
    );

    if (!empty($translations[$iso][$fieldToTranslate])) {
        $defaults["language[{$language->getId()}]"] = $translations[$iso][$fieldToTranslate];
    }
}

$form->addButtonSave(get_lang('Save'));
$form->freeze(['original_name']);
$form->setDefaults($defaults);

if ($form->validate()) {
    $values = $form->exportValues();

    foreach ($languages as $language) {
        if (empty($values['language'][$language->getId()])) {
            continue;
        }

        $translationsRepo->translate(
            $skill,
            $fieldToTranslate,
            $language->getIsocode(),
            $values['language'][$language->getId()]
        );
    }

    $em->flush();

    Display::addFlash(
        Display::return_message(get_lang('Translation saved'), 'success')
    );

    header("Location: $translateUrl");
    exit;
}

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'skill_list.php', 'name' => get_lang('Manage skills')];

$view = new Template(get_lang('Add terms to the sub-language'));
$view->assign('form', $form->returnForm());
$template = $view->get_template('extrafield/translate.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();
