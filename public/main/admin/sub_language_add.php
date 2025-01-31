<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows for the addition of sub-languages.
 */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$request = Container::getRequest();
$em = Database::getManager();

$requestAction = $request->query->get('action');

/**
 *        MAIN CODE.
 */
// setting the name of the tool
$tool_name = get_lang('Create sub-language');

$content = '';

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'languages.php', 'name' => get_lang('Chamilo Portal Languages')];

$parent_id = $request->query->getInt('id');

if (!SubLanguageManager::languageExistsById($parent_id)) {
    Display::addFlash(
        Display::return_message(get_lang('The parent language does not exist.'))
    );
    api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php');
}

$language_details = SubLanguageManager::get_all_information_of_language($parent_id);
$language_name = $language_details['original_name'];

if ('definenewsublanguage' === $requestAction) {
    $form = new FormValidator(
        'addsublanguage',
        'post',
        'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&action=definenewsublanguage'
    );
    $form->addHeader(
        get_lang('Create sub-languageForLanguage').' ( '.strtolower($language_name).' )'
    );
    $form->addText('original_name', get_lang('Original name'), true, ['class' => 'input_titles']);
    $form->addText('english_name', get_lang('English name'), true, ['class' => 'input_titles']);
    $form->addText('isocode', get_lang('ISO code'), true, ['class' => 'input_titles']);
    $form->addElement('static', null, '&nbsp;', '<i>en, es, fr</i>');
    $form->addCheckBox('sub_language_is_visible', '', get_lang('Visibility'));
    $form->addButtonCreate(get_lang('Create sub-language'), 'SubmitAddNewLanguage');
    $form->protect();

    if ($form->validate()) {
        $values = $form->exportValues();
        $values['english_name'] = str_replace(' ', '_', $values['english_name']);
        $values['isocode'] = str_replace(' ', '_', $values['isocode']);

        $check_information = SubLanguageManager::checkIfLanguageExists(
            $values['original_name'],
            $values['english_name'],
            $values['isocode']
        );
        $allow_insert_info = $check_information['execute_add'] ?? false;

        if ($check_information['original_name'] ) {
            Display::addFlash(
                Display::return_message(
                    sprintf(
                        '%s "%s (%s)',
                        get_lang('Already exists'),
                        get_lang('Original name'),
                        $values['original_name']
                    ),
                    'error'
                )
            );
        }
        if ($check_information['english_name'] ) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Already exists').' "'.get_lang('English name').'" '.'('.$values['english_name'].')',
                    'error'
                )
            );
        }
        if ($check_information['isocode'] ) {
            Display::addFlash(
                Display::return_message(get_lang('This code does not exist').': '.$values['isocode'], 'error')
            );
        }

        if (strlen($values['original_name']) > 0 && strlen($values['english_name']) > 0 && strlen($values['isocode']) > 0) {
            if ($allow_insert_info) {
                //$values['english_name'] = str_replace(' ', '_', $values['english_name']);
                //Fixes BT#1636
                $values['english_name'] = api_strtolower($values['english_name']);

                try {
                    $newSubLanguage = SubLanguageManager::addSubLanguage(
                        $values['original_name'],
                        $values['english_name'],
                        $values['sub_language_is_visible'] ?? false,
                        $parent_id,
                        $language_details['isocode']
                    );

                    if (SubLanguageManager::addPoFileForSubLanguage($newSubLanguage->getIsocode())) {
                        $str_info = '<br/>'.get_lang('Original name').' : '
                            .$values['original_name'].'<br/>'.get_lang('English name').' : '
                            .$values['english_name'].'<br/>'.get_lang('Character set').' : '.$newSubLanguage->getIsocode();

                        Display::addFlash(
                            Display::return_message(get_lang('The new sub-language has been added').$str_info, 'normal', false)
                        );
                        api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php?sub_language_id='.$newSubLanguage->getId());
                    } else {
                        $em->remove($newSubLanguage);
                        $em->flush();

                        throw new Exception();
                    }
                } catch (Exception $e) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'),
                            'error'
                        )
                    );
                }
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('The form contains incorrect or incomplete data. Please check your input.'),
                    'error'
                )
            );
        }
    }

    $form->setDefaults([
        //'original_name' => $language_details['original_name'].'...'; -> cannot be used because of quickform filtering (freeze),
        'english_name' => $language_details['english_name'].'2',
        'isocode' => $language_details['isocode'],
    ]);
    $content .= $form->returnForm();
} elseif (true === SubLanguageManager::isParentOfSubLanguage($parent_id)
    && 'deletesublanguage' === $requestAction
) {
    $sub_language_id = $request->query->getInt('sub_language_id');

    if (!SubLanguageManager::languageExistsById($sub_language_id)) {
        Display::addFlash(
            Display::return_message(get_lang('The sub-language does not exist.'))
        );

        api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php');
    }

    $language_name = SubLanguageManager::get_name_of_language_by_id($sub_language_id);
    $get_all_information = SubLanguageManager::getAllInformationOfSubLanguage($parent_id, $sub_language_id);

    $form = new FormValidator(
        'deletesublanguage',
        'post',
        'sub_language_add.php?id='.http_build_query([
            'id' => $parent_id,
            'sub_language_id' => $sub_language_id,
        ])
    );
    $form->addHeader(get_lang('Delete sub-language'));
    $form->addElement('static', '', get_lang('Original name'), $get_all_information->getOriginalName());
    $form->addElement('static', '', get_lang('English name'), $get_all_information->getEnglishName());
    $form->addElement('static', '', get_lang('Character set'), $get_all_information->getIsocode());
    $form->addButtonCreate(get_lang('Delete sub-language'), 'SubmitAddDeleteLanguage');
    $form->protect();

    if ($form->validate()) {
        $removed = SubLanguageManager::removeSubLanguage($parent_id, $sub_language_id);

        if ($removed) {
            Display::addFlash(
                Display::return_message(get_lang('The sub language has been removed.'))
            );
            api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php');
        }
    }

    $content .= $form->returnForm();
}
/**
 * Footer.
 */

$view = new Template($tool_name);
$view->assign('header', $language_name);
$view->assign('content', $content);
$view->display_one_col_template();
