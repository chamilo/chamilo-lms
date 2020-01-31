<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows for the addition of sub-languages.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

/**
 *        MAIN CODE.
 */
// setting the name of the tool
$tool_name = get_lang('Create sub-language');

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'languages.php', 'name' => get_lang('Chamilo Portal Languages')];

/**
 * Add sub-language.
 *
 * @param   string  Original language name (Occitan, Wallon, Vlaams)
 * @param   string  English language name (occitan, wallon, flanders)
 * @param   string  ISO code (fr_FR, ...)
 * @param   int     Whether the sublanguage is published (0=unpublished, 1=published)
 * @param   int     ID del idioma padre
 *
 * @return false|string New sub language ID or false on error
 */
function add_sub_language($original_name, $english_name, $isocode, $sublanguage_available, $parent_id)
{
    $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    $original_name = Database::escape_string($original_name);
    $english_name = Database::escape_string($english_name);
    $isocode = Database::escape_string($isocode);
    $sublanguage_available = Database::escape_string($sublanguage_available);
    $parent_id = intval($parent_id);

    $sql = 'INSERT INTO '.$tbl_admin_languages.'(original_name,english_name,isocode,dokeos_folder,available,parent_id)
    	  VALUES ("'.$original_name.'","'.$english_name.'","'.$isocode.'","'.$english_name.'","'.$sublanguage_available.'","'.$parent_id.'")';
    $res = Database::query($sql);
    if (false === $res) {
        return false;
    }

    return Database::insert_id();
}

/**
 * Check if language exists.
 *
 * @param   string  Original language name (Occitan, Wallon, Vlaams)
 * @param   string  English language name (occitan, wallon, flanders)
 * @param   string  ISO code (fr_FR, ...)
 * @param   int     Whether the sublanguage is published (0=unpublished, 1=published)
 *
 * @return array Array describing the number of items found that match the
 *               current language insert attempt (original_name => true,
 *               english_name => true, isocode => true,
 *               execute_add => true/false). If execute_add is true, then we
 *               can proceed.
 *
 * @todo This function is not transaction-safe and should probably be included
 *       inside the add_sub_language function.
 */
function check_if_language_exist($original_name, $english_name, $isocode, $sublanguage_available)
{
    $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    $sql_original_name = 'SELECT count(*) AS count_original_name FROM '.$tbl_admin_languages.' 
                          WHERE original_name="'.Database::escape_string($original_name).'" ';
    $sql_english_name = 'SELECT count(*) AS count_english_name FROM '.$tbl_admin_languages.' 
                         WHERE english_name="'.Database::escape_string($english_name).'" ';
    $rs_original_name = Database::query($sql_original_name);
    $rs_english_name = Database::query($sql_english_name);
    $count_original_name = Database::result($rs_original_name, 0, 'count_original_name');
    $count_english_name = Database::result($rs_english_name, 0, 'count_english_name');

    $has_error = false;
    $message_information = [];

    if (1 == $count_original_name) {
        $has_error = true;
        $message_information['original_name'] = true;
    }
    if (1 == $count_english_name) {
        $has_error = true;
        $message_information['english_name'] = true;
    }

    $iso_list = api_get_platform_isocodes();
    $iso_list = array_values($iso_list);

    if (!in_array($isocode, $iso_list)) {
        $has_error = true;
        $message_information['isocode'] = true;
    }
    if (true === $has_error) {
        $message_information['execute_add'] = false;
    }
    if (false === $has_error) {
        $message_information['execute_add'] = true;
    }

    return $message_information;
}

/**
 * Check if language exist, given its ID. This is just a wrapper for the
 * SubLanguageManager::check_if_exist_language_by_id() method and should not exist.
 *
 * @param   int     Language ID
 *
 * @return bool
 *
 * @todo    deprecate this function and use the static method directly
 */
function check_if_exist_language_by_id($language_id)
{
    return SubLanguageManager::check_if_exist_language_by_id($language_id);
}

/**
 * Check if the given language is a parent of any sub-language.
 *
 * @param   int     Language ID of the presumed parent
 *
 * @return bool True if this language has children, false otherwise
 */
function ckeck_if_is_parent_of_sub_language($parent_id)
{
    $sql = 'SELECT count(*) AS count FROM language WHERE parent_id= '.intval($parent_id);
    $rs = Database::query($sql);
    if (Database::num_rows($rs) > 0 && 1 == Database::result($rs, 0, 'count')) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get all information of sub-language.
 *
 * @param   int     Parent language ID
 * @param   int     Child language ID
 *
 * @return array
 */
function allow_get_all_information_of_sub_language($parent_id, $sub_language_id)
{
    return SubLanguageManager::get_all_information_of_sub_language($parent_id, $sub_language_id);
}

/*end declare functions*/

//add data

if (isset($_GET['sub_language_id']) && $_GET['sub_language_id'] == strval(intval($_GET['sub_language_id']))) {
    $language_name = SubLanguageManager::get_name_of_language_by_id($_GET['sub_language_id']);
    if (true === check_if_exist_language_by_id($_GET['sub_language_id'])) {
        $sub_language_id = $_GET['sub_language_id'];
        $sub_language_id_exist = true;
    } else {
        $sub_language_id_exist = false;
    }
}
$language_details = [];
$language_name = '';
if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
    $language_details = SubLanguageManager::get_all_information_of_language($_GET['id']);
    $language_name = $language_details['original_name'];
    if (true === check_if_exist_language_by_id($_GET['id'])) {
        $parent_id = $_GET['id'];
        $language_id_exist = true;
    } else {
        $language_id_exist = false;
    }
} else {
    $language_id_exist = false;
}

//removed and register

if ((isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) &&
    (isset($_GET['sub_language_id']) && $_GET['sub_language_id'] == strval(intval($_GET['sub_language_id'])))
) {
    if (true === check_if_exist_language_by_id($_GET['id']) && true === check_if_exist_language_by_id($_GET['sub_language_id'])) {
        $get_all_information = allow_get_all_information_of_sub_language($_GET['id'], $_GET['sub_language_id']);
        $original_name = $get_all_information['original_name'];
        $english_name = $get_all_information['english_name'];
        $isocode = $get_all_information['isocode'];
    }
}

$language_name = get_lang('Create sub-languageForLanguage').' ( '.strtolower($language_name).' )';

if (true === ckeck_if_is_parent_of_sub_language($parent_id) &&
    isset($_GET['action']) && 'deletesublanguage' == $_GET['action']
) {
    $language_name = get_lang('Delete sub-language');
}

$msg = '';

if (isset($_POST['SubmitAddNewLanguage'])) {
    $original_name = $_POST['original_name'];
    $english_name = $_POST['english_name'];
    $isocode = $_POST['isocode'];
    $english_name = str_replace(' ', '_', $english_name);
    $isocode = str_replace(' ', '_', $isocode);

    $sublanguage_available = $_POST['sub_language_is_visible'];
    $check_information = [];
    $check_information = check_if_language_exist($original_name, $english_name, $isocode, $sublanguage_available);
    foreach ($check_information as $index_information => $value_information) {
        $allow_insert_info = false;
        if ('original_name' == $index_information) {
            $msg .= Display::return_message(
                get_lang('Already exists').' "'.get_lang('Original name').'" '.'('.$original_name.')',
                'error'
            );
        }
        if ('english_name' == $index_information) {
            $msg .= Display::return_message(
                get_lang('Already exists').' "'.get_lang('English name').'" '.'('.$english_name.')',
                'error'
            );
        }
        if ('isocode' == $index_information) {
            $msg .= Display::return_message(get_lang('This code does not exist').': '.$isocode.'', 'error');
        }
        if ('execute_add' == $index_information && true === $value_information) {
            $allow_insert_info = true;
        }
    }

    if (strlen($original_name) > 0 && strlen($english_name) > 0 && strlen($isocode) > 0) {
        if (true === $allow_insert_info && true === $language_id_exist) {
            $english_name = str_replace(' ', '_', $english_name);
            //Fixes BT#1636
            $english_name = api_strtolower($english_name);

            $isocode = str_replace(' ', '_', $isocode);
            $str_info = '<br/>'.get_lang('Original name').' : '.$original_name.'<br/>'.get_lang('English name').' : '.$english_name.'<br/>'.get_lang('Character set').' : '.$isocode;

            $mkdir_result = SubLanguageManager::add_language_directory($english_name);
            if ($mkdir_result) {
                $sl_id = add_sub_language($original_name, $english_name, $isocode, $sublanguage_available, $parent_id);
                if (false === $sl_id) {
                    SubLanguageManager::remove_language_directory($english_name);
                    $msg .= Display::return_message(get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'), 'error');
                } else {
                    Display::addFlash(
                        Display::return_message(get_lang('The new sub-language has been added').$str_info, null, false)
                    );
                    unset($interbreadcrumb);
                    $_GET['sub_language_id'] = $_REQUEST['sub_language_id'] = $sl_id;
                    require 'sub_language.php';
                    exit();
                }
            } else {
                $msg .= Display::return_message(get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'), 'error');
            }
        } else {
            if (false === $language_id_exist) {
                $msg .= Display::return_message(get_lang('The parent language does not exist.'), 'error');
            }
        }
    } else {
        $msg .= Display::return_message(get_lang('The form contains incorrect or incomplete data. Please check your input.'), 'error');
    }
}

Display:: display_header($language_name);

echo $msg;

if (isset($_POST['SubmitAddDeleteLanguage'])) {
    $rs = SubLanguageManager::remove_sub_language($_GET['id'], $_GET['sub_language_id']);
    if (true === $rs) {
        echo Display::return_message(get_lang('The sub language has been removed'), 'confirm');
    } else {
        echo Display::return_message(get_lang('The sub-language has not been removed.'), 'error');
    }
}
// ckeck_if_is_parent_of_sub_language($parent_id)===false
if (isset($_GET['action']) && 'definenewsublanguage' == $_GET['action']) {
    $text = $language_name;
    $form = new FormValidator(
        'addsublanguage',
        'post',
        'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&action=definenewsublanguage'
    );
    $class = 'add';
    $form->addElement('header', '', $text);
    $form->addElement('text', 'original_name', get_lang('Original name'), 'class="input_titles"');
    $form->addRule('original_name', get_lang('Required field'), 'required');
    $form->addElement('text', 'english_name', get_lang('English name'), 'class="input_titles"');
    $form->addRule('english_name', get_lang('Required field'), 'required');
    $form->addElement('text', 'isocode', get_lang('ISO code'), 'class="input_titles"');
    $form->addRule('isocode', get_lang('Required field'), 'required');
    $form->addElement('static', null, '&nbsp;', '<i>en, es, fr</i>');
    $form->addElement('checkbox', 'sub_language_is_visible', '', get_lang('Visibility'));
    $form->addButtonCreate(get_lang('Create sub-language'), 'SubmitAddNewLanguage');
    //$values['original_name'] = $language_details['original_name'].'...'; -> cannot be used because of quickform filtering (freeze)
    $values['english_name'] = $language_details['english_name'].'2';
    $values['isocode'] = $language_details['isocode'];
    $form->setDefaults($values);
    $form->display();
} else {
    if (isset($_GET['action']) && 'deletesublanguage' == $_GET['action']) {
        $text = $language_name;
        $form = new FormValidator(
            'deletesublanguage',
            'post',
            'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&sub_language_id='.Security::remove_XSS($_GET['sub_language_id'])
        );
        $class = 'minus';
        $form->addElement('header', '', $text);
        $form->addElement('static', '', get_lang('Original name'), $original_name);
        $form->addElement('static', '', get_lang('English name'), $english_name);
        $form->addElement('static', '', get_lang('Character set'), $isocode);
        $form->addButtonCreate(get_lang('Delete sub-language'), 'SubmitAddDeleteLanguage');
        $form->display();
    }
    if (isset($_GET['action']) && 'definenewsublanguage' == $_GET['action']) {
        echo Display::return_message(get_lang('The sub-language of this language has been added'));
    }
}
/**
 * Footer.
 */
Display:: display_footer();
