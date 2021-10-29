<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Framework\Container;
use Gedmo\Translatable\Entity\Translation;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();

$extraFieldRepo = Container::getExtraFieldRepository();
$languageRepo = Container::getLanguageRepository();

$fieldId = (int) ($_REQUEST['id'] ?? 0);

/** @var ExtraField|null $extraField */
$extraField = $extraFieldRepo->find($fieldId);

if (null === $extraField) {
    api_not_allowed(true);
}

$currentUrl = api_get_self().'?id='.$fieldId;
$qb = $languageRepo->getAllAvailable();
$languages = $qb->getQuery()->getResult();

$form = new FormValidator('translate', 'POST', $currentUrl);
$form->addHidden('id', $fieldId);
$form->addHeader($extraField->getDisplayText());

$repository = $em->getRepository(Translation::class);
$translations = $repository->findTranslations($extraField);

$defaults = [];

/** @var Language $language */
foreach ($languages as $language) {
    $iso = $language->getIsocode();
    $variable = 'variable['.$iso.']';
    $form->addText($variable, $language->getOriginalName().' ('.$iso.')', false);
    if (isset($translations[$iso]) && $translations[$iso]['displayText']) {
        $defaults['variable['.$iso.']'] = $translations[$iso]['displayText'];
    }
}

$form->setDefaults($defaults);
$form->addButtonSave(get_lang('Save'));

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin', 'name' => get_lang('Administration')];

$type = \ExtraField::getExtraFieldTypeFromInt($extraField->getExtraFieldType());

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type='.$type,
    'name' => get_lang('Fields'),
];

if ($form->validate()) {
    $values = $form->getSubmitValues();
    foreach ($languages as $language) {
        if (!isset($values['variable'][$language->getIsocode()])) {
            continue;
        }
        $translation = $values['variable'][$language->getIsocode()];
        if (empty($translation)) {
            continue;
        }

        $extraField = $extraFieldRepo->find($fieldId);
        $extraField
            ->setTranslatableLocale($language->getIsocode())
            ->setDisplayText($translation)
        ;
        $em->persist($extraField);
        $em->flush();
    }

    Display::addFlash(Display::return_message(get_lang('Updated')));
    api_location($currentUrl);
}

$tpl = new Template(get_lang('Translations'));
$tpl->assign('form', $form->returnForm());
$template = $tpl->get_template('extrafield/translate.html.twig');
$content = $tpl->fetch($template);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
