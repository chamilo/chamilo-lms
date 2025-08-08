<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
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

/** @var ExtraFieldOptions|null $extraFieldOption */
$extraFieldOption = $extraFieldOptionsRepo->find($fieldId);

$extraFieldOption->setLocale(Container::getParameter('locale'));
$em->refresh($extraFieldOption);

if (null === $extraFieldOption) {
    api_not_allowed(true);
}

$extraField = $extraFieldOption->getField();

$currentUrl = api_get_self().'?id='.$fieldId;
$languages = $languageRepo->getAllAvailable(true)->getQuery()->getResult();

$form = new FormValidator('translate', 'POST', $currentUrl);
$form->addHidden('id', $fieldId);
$form->addHeader($extraFieldOption->getDisplayText());

$translationsRepo = $em->getRepository(Translation::class);
$translations = $translationsRepo->findTranslations($extraFieldOption);

$defaults = [];
/** @var Language $language */
foreach ($languages as $language) {
    $iso = $language->getIsocode();
    $form->addText(
        'language['.$language->getId().']',
        $language->getOriginalName(),
        false
    );

    if (!empty($translations[$iso]['displayText'])) {
        $defaults['language['.$language->getId().']'] = $translations[$iso]['displayText'];
    }
}

$form->setDefaults($defaults);
$form->addButtonSave(get_lang('Save'));

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin', 'name' => get_lang('Administration')];

$type = \ExtraField::getExtraFieldTypeFromInt($extraField->getItemType());

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/extra_fields.php?type='.$type,
    'name' => get_lang('Extra fields'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).
        'admin/extra_field_options.php?action=edit&field_id='.$extraField->getId().'&type='.$type.'&id='.$fieldId,
    'name' => $extraField->getDisplayText(),
];

if ($form->validate()) {
    $values = $form->getSubmitValues();
    foreach ($languages as $language) {
        if (empty($values['language'][$language->getId()])) {
            continue;
        }

        $translationsRepo->translate(
            $extraFieldOption,
            'displayText',
            $language->getIsocode(),
            $values['language'][$language->getId()],
        );
    }

    $em->flush();

    Display::addFlash(Display::return_message(get_lang('Updated')));
    api_location($currentUrl);
}

$tpl = new Template(get_lang('Translation'));
$tpl->assign('form', $form->returnForm());
$template = $tpl->get_template('extrafield/translate.html.twig');
$content = $tpl->fetch($template);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
