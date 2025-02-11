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

$sub_language_id_exist = SubLanguageManager::languageExistsById($request->query->getInt('sub_language_id'));
$language_id_exist = SubLanguageManager::languageExistsById($request->query->getInt('id'));
$language_name = '';
$language_details = [];

//add data
if ($sub_language_id_exist) {
    $language_name = SubLanguageManager::get_name_of_language_by_id($_GET['sub_language_id']);
    $sub_language_id = $request->query->getInt('sub_language_id');
}

if ($language_id_exist) {
    $language_details = SubLanguageManager::get_all_information_of_language($_GET['id']);
    $language_name = $language_details['original_name'];
    $parent_id = $request->query->getInt('id');
}

//removed and register
if ($language_id_exist && $sub_language_id_exist) {
    $get_all_information = SubLanguageManager::getAllInformationOfSubLanguage($parent_id, $sub_language_id);
    $original_name = $get_all_information->getOriginalName();
    $english_name = $get_all_information->getEnglishName();
    $isocode = $get_all_information->getIsocode();
}

$language_name = get_lang('Create sub-language').' ( '.strtolower($language_name).' )';

if ($request->request->has('SubmitAddNewLanguage')) {
    $original_name = $request->request->get('original_name');
    $english_name = str_replace(' ', '_', $request->request->get('english_name'));
    $isocode = str_replace(' ', '_', $request->request->get('isocode'));

    $sublanguage_available = $request->request->getInt('sub_language_is_visible');
    $check_information = SubLanguageManager::checkIfLanguageExists($original_name, $english_name, $isocode);
    $allow_insert_info = $check_information['execute_add'] ?? false;

    if ($check_information['original_name'] ) {
        Display::addFlash(
            Display::return_message(
                get_lang('Already exists').' "'.get_lang('Original name').'" '.'('.$original_name.')',
                'error'
            )
        );
    }
    if ($check_information['english_name'] ) {
        Display::addFlash(
            Display::return_message(
                get_lang('Already exists').' "'.get_lang('English name').'" '.'('.$english_name.')',
                'error'
            )
        );
    }
    if ($check_information['isocode'] ) {
        Display::addFlash(
            Display::return_message(get_lang('This code does not exist').': '.$isocode.'', 'error')
        );
    }

    if (strlen($original_name) > 0 && strlen($english_name) > 0 && strlen($isocode) > 0) {
        if ($allow_insert_info && $language_id_exist) {
            $english_name = str_replace(' ', '_', $english_name);
            //Fixes BT#1636
            $english_name = api_strtolower($english_name);

            $firstIso = substr($language_details['isocode'], 0, 2);
            //$english_name = str_starts_with($english_name, $firstIso.'_') ? $english_name : $firstIso.'_'.$english_name;

            $isocode = SubLanguageManager::generateSublanguageCode($firstIso, $request->request->get('english_name'));
            $str_info = '<br/>'.get_lang('Original name').' : '
                .$original_name.'<br/>'.get_lang('English name').' : '
                .$english_name.'<br/>'.get_lang('Character set').' : '.$isocode;

            $mkdir_result = SubLanguageManager::addPoFileForSubLanguage($isocode);
            if ($mkdir_result) {
                $sl_id = SubLanguageManager::addSubLanguage(
                    $original_name,
                    $english_name,
                    $sublanguage_available,
                    $parent_id,
                    $isocode
                );
                if (false === $sl_id) {
                    SubLanguageManager::removePoFileForSubLanguage($isocode);
                    Display::addFlash(
                        Display::return_message(
                            get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'),
                            'error'
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(get_lang('The new sub-language has been added').$str_info, null, false)
                    );
                    api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php?sub_language_id='.$sl_id);
                }
            } else {
                Display::addFlash(
                    Display::return_message(
                        get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'),
                        'error'
                    )
                );
            }
        } elseif (false === $language_id_exist) {
            Display::addFlash(
                Display::return_message(get_lang('The parent language does not exist.'), 'error')
            );
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

if (isset($_POST['SubmitAddDeleteLanguage'])) {
    $removed = SubLanguageManager::removeSubLanguage($_GET['id'], $_GET['sub_language_id']);
    if ($removed) {
        Display::addFlash(
            Display::return_message(get_lang('The sub language has been removed.'))
        );
        api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php');
    }
}

if ('definenewsublanguage' === $requestAction) {
    $form = new FormValidator(
        'addsublanguage',
        'post',
        'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&action=definenewsublanguage'
    );
    $class = 'add';
    $form->addHeader($language_name);
    $form->addText('original_name', get_lang('Original name'), true, ['class' => 'input_titles']);
    $form->addText('english_name', get_lang('English name'), true, ['class' => 'input_titles']);
    $form->addText('isocode', get_lang('ISO code'), true, ['class' => 'input_titles']);
    $form->addElement('static', null, '&nbsp;', '<i>en, es, fr</i>');
    $form->addCheckBox('sub_language_is_visible', '', get_lang('Visibility'));
    $form->addButtonCreate(get_lang('Create sub-language'), 'SubmitAddNewLanguage');
    $form->setDefaults([
        //'original_name' => $language_details['original_name'].'...'; -> cannot be used because of quickform filtering (freeze),
        'english_name' => $language_details['english_name'].'2',
        'isocode' => $language_details['isocode'],
    ]);
    $content .= $form->returnForm();
} else {
    if (true === SubLanguageManager::isParentOfSubLanguage($parent_id)
        && 'deletesublanguage' === $requestAction
    ) {
        $language_name = get_lang('Delete sub-language');

        $form = new FormValidator(
            'deletesublanguage',
            'post',
            'sub_language_add.php?id='.http_build_query([
                'id' => $request->query->getInt('id'),
                'sub_language_id' => $request->query->getInt('sub_language_id'),
            ])
        );
        $class = 'minus';
        $form->addHeader($language_name);
        $form->addElement('static', '', get_lang('Original name'), $original_name);
        $form->addElement('static', '', get_lang('English name'), $english_name);
        $form->addElement('static', '', get_lang('Character set'), $isocode);
        $form->addButtonCreate(get_lang('Delete sub-language'), 'SubmitAddDeleteLanguage');
        $content .= $form->returnForm();
    }
    if ('definenewsublanguage' == $requestAction) {
        Display::addFlash(
            Display::return_message(get_lang('The sub-language of this language has been added'))
        );
    }
}
/**
 * Footer.
 */

$view = new Template($tool_name);
$view->assign('header', $language_name);
$view->assign('content', $content);
$view->display_one_col_template();
