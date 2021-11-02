<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Framework\Container;
use Gedmo\Translatable\Entity\Translation;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();

$extraFieldRepo = Container::getExtraFieldRepository();
$extraFieldOptionsRepo = Container::getExtraFieldOptionsRepository();
$languageRepo = Container::getLanguageRepository();

$fieldId = (int) ($_REQUEST['id'] ?? 0);

/** @var \Chamilo\CoreBundle\Entity\ExtraFieldOptions|null $extraFieldOption */
$extraFieldOption = $extraFieldOptionsRepo->find($fieldId);

if (null === $extraFieldOption) {
    api_not_allowed(true);
}

$extraField = $extraFieldOption->getField();

$currentUrl = api_get_self().'?id='.$fieldId;
$qb = $languageRepo->getAllAvailable();
$languages = $qb->getQuery()->getResult();

$form = new FormValidator('translate', 'POST', $currentUrl);
$form->addHidden('id', $fieldId);
$form->addHeader($extraFieldOption->getDisplayText());

$repository = $em->getRepository(Translation::class);
$translations = $repository->findTranslations($extraFieldOption);

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

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).
        'admin/extra_field_options.php?action=edit&field_id='.$extraField->getId().'&type='.$type.'&id='.$fieldId,
    'name' => $extraField->getDisplayText(),
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

        $extraFieldOption = $extraFieldOptionsRepo->find($fieldId);
        $extraFieldOption
            ->setTranslatableLocale($language->getIsocode())
            ->setDisplayText($translation)
        ;
        $em->persist($extraFieldOption);
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
