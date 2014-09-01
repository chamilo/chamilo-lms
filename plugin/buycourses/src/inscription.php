<?php
/* For licensing terms, see /license.txt */
/**
 * This script displays a form for registering new users to a course directly
 * @package  chamilo.plugin.buycourses
 */
/**
 * Init
 */
use ChamiloSession as Session;

$language_file = array('registration', 'admin');

if (!empty($_POST['language'])) { //quick hack to adapt the registration form result to the selected registration language
    $_GET['language'] = $_POST['language'];
}
require_once '../config.php';
require_once api_get_path(CONFIGURATION_PATH) . 'profile.conf.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';

if (!empty($_SESSION['user_language_choice'])) {
    $user_selected_language = $_SESSION['user_language_choice'];
} elseif (!empty($_SESSION['_user']['language'])) {
    $user_selected_language = $_SESSION['_user']['language'];
} else {
    $user_selected_language = get_setting('platformLanguage');
}

$form = new FormValidator('registration');

if (api_get_setting('allow_terms_conditions') == 'true') {
    $user_already_registered_show_terms = isset($_SESSION['term_and_condition']['user_id']);
} else {
    $user_already_registered_show_terms = false;
}

//Direct Link Subscription feature #5299
$course_code_redirect = isset($_REQUEST['c']) && !empty($_REQUEST['c']) ? $_REQUEST['c'] : null;
$exercise_redirect = isset($_REQUEST['e']) && !empty($_REQUEST['e']) ? $_REQUEST['e'] : null;

if (!empty($course_code_redirect)) {
    Session::write('course_redirect', $course_code_redirect);
    Session::write('exercise_redirect', $exercise_redirect);
}

if ($user_already_registered_show_terms == false) {

    if (api_is_western_name_order()) {
        //	FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
        $form->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
    } else {
        //	LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
        $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
    }
    $form->applyFilter(array('lastname', 'firstname'), 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

    //	EMAIL
    $form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
    if (api_get_setting('registration', 'email') == 'true') {
        $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    }

    if (api_get_setting('login_is_email') == 'true') {
        $form->applyFilter('email', 'trim');
        if (api_get_setting('registration', 'email') != 'true') {
            $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
        }
        $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
        $form->addRule('email', get_lang('UserTaken'), 'username_available');
    }

    $form->addRule('email', get_lang('EmailWrong'), 'email');
    if (api_get_setting('openid_authentication') == 'true') {
        $form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40));
    }

    //	USERNAME
    if (api_get_setting('login_is_email') != 'true') {
        $form->addElement('text', 'username', get_lang('UserName'), array('size' => USERNAME_MAX_LENGTH));
        $form->applyFilter('username', 'trim');
        $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
        $form->addRule('username', get_lang('UsernameWrong'), 'username');
        $form->addRule('username', get_lang('UserTaken'), 'username_available');
    }

    //	PASSWORD
    $form->addElement('password', 'pass1', get_lang('Pass'), array('size' => 20, 'autocomplete' => 'off'));
    $form->addElement('password', 'pass2', get_lang('Confirmation'), array('size' => 20, 'autocomplete' => 'off'));
    $form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');

    if (CHECK_PASS_EASY_TO_FIND) {
        $form->addRule('password1', get_lang('PassTooEasy') . ': ' . api_generate_password(), 'callback', 'api_check_password');
    }

    //	PHONE
    $form->addElement('text', 'phone', get_lang('Phone'), array('size' => 20));
    if (api_get_setting('registration', 'phone') == 'true') {
        $form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
    }

    //	LANGUAGE
    if (api_get_setting('registration', 'language') == 'true') {
        $form->addElement('select_language', 'language', get_lang('Language'));
    }

}

if (isset($_SESSION['user_language_choice']) && $_SESSION['user_language_choice'] != '') {
    $defaults['language'] = $_SESSION['user_language_choice'];
} else {
    $defaults['language'] = api_get_setting('platformLanguage');
}
if (!empty($_GET['username'])) {
    $defaults['username'] = Security::remove_XSS($_GET['username']);
}
if (!empty($_GET['email'])) {
    $defaults['email'] = Security::remove_XSS($_GET['email']);
}

if (!empty($_GET['phone'])) {
    $defaults['phone'] = Security::remove_XSS($_GET['phone']);
}

if (api_get_setting('openid_authentication') == 'true' && !empty($_GET['openid'])) {
    $defaults['openid'] = Security::remove_XSS($_GET['openid']);
}
$defaults['status'] = STUDENT;

if (is_array($extra_data)) {
    $defaults = array_merge($defaults, $extra_data);
}

$form->setDefaults($defaults);

$content = null;

if (api_get_setting('allow_terms_conditions') == 'true') {
    $get = array_keys($_GET);
    if (isset($get)) {
        if ($get[0] == 'legal') {
            $language = api_get_interface_language();
            $language = api_get_language_id($language);
            $term_preview = LegalManager::get_last_condition($language);
            if (!$term_preview) {
                //look for the default language
                $language = api_get_setting('platformLanguage');
                $language = api_get_language_id($language);
                $term_preview = LegalManager::get_last_condition($language);
            }
            $tool_name = get_lang('TermsAndConditions');
            Display :: display_header($tool_name);

            if (!empty($term_preview['content'])) {
                echo $term_preview['content'];
            } else {
                echo get_lang('ComingSoon');
            }
            Display :: display_footer();
            exit;
        }
    }
}

$tool_name = get_lang('Registration', null, (!empty($_POST['language']) ? $_POST['language'] : $_user['language']));

if (api_get_setting('allow_terms_conditions') == 'true' && $user_already_registered_show_terms) {
    $tool_name = get_lang('TermsAndConditions');
}

$home = api_get_path(SYS_PATH) . 'home/';
if (api_is_multiple_url_enabled()) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';
        $home_old = api_get_path(SYS_PATH) . 'home/';
        $home = api_get_path(SYS_PATH) . 'home/' . $clean_url;
    }
}

if (file_exists($home . 'register_top_' . $user_selected_language . '.html')) {
    $home_top_temp = @(string)file_get_contents($home . 'register_top_' . $user_selected_language . '.html');
    $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    if (!empty($open)) {
        $content = '<div class="well_border">' . $open . '</div>';
    }
}

$content .= Display::return_message(get_lang('YourAccountHasToBeApproved'));

// Terms and conditions
if (api_get_setting('allow_terms_conditions') == 'true') {
    $language = api_get_interface_language();
    $language = api_get_language_id($language);
    $term_preview = LegalManager::get_last_condition($language);

    if (!$term_preview) {
        //we load from the platform
        $language = api_get_setting('platformLanguage');
        $language = api_get_language_id($language);
        $term_preview = LegalManager::get_last_condition($language);

        //if is false we load from english
        if (!$term_preview) {
            $language = api_get_language_id('english'); //this must work
            $term_preview = LegalManager::get_last_condition($language);
        }
    }

    // Version and language
    $form->addElement('hidden', 'legal_accept_type', $term_preview['version'] . ':' . $term_preview['language_id']);
    $form->addElement('hidden', 'legal_info', $term_preview['legal_id'] . ':' . $term_preview['language_id']);

    if ($term_preview['type'] == 1) {
        $form->addElement('checkbox', 'legal_accept', null, get_lang('IHaveReadAndAgree') . '&nbsp;<a href="inscription.php?legal" target="_blank">' . get_lang('TermsAndConditions') . '</a>');
        $form->addRule('legal_accept', get_lang('ThisFieldIsRequired'), 'required');
    } else {
        $preview = LegalManager::show_last_condition($term_preview);
        $form->addElement('label', null, $preview);
    }
}

$form->addElement('button', 'submit', get_lang('RegisterUser'), array('class' => 'btn btn-primary btn-large'));

if ($form->validate()) {
    $values = $form->exportValues();
    $values['username'] = api_substr($values['username'], 0, USERNAME_MAX_LENGTH); //make *sure* the login isn't too long
    $values['status'] = STUDENT;
    $values['official_code'] = api_strtoupper($values['username']);

    if (api_get_setting('login_is_email') == 'true') {
        $values['username'] = $values['email'];
    }

    // Creates a new user
    $user_id = UserManager::create_user($values['firstname'], $values['lastname'], $values['status'], $values['email'], $values['username'], $values['pass1'], $values['official_code'], $values['language'], $values['phone'], $picture_uri, PLATFORM_AUTH_SOURCE, null, 1, 0, null, null, true);

    // Register extra fields
    $extras = array();
    foreach ($values as $key => $value) {
        if (substr($key, 0, 6) == 'extra_') { //an extra field
            $extras[substr($key, 6)] = $value;
        }
    }

    //update the extra fields
    $count_extra_field = count($extras);
    if ($count_extra_field > 0) {
        foreach ($extras as $key => $value) {
            UserManager::update_extra_field_value($user_id, $key, $value);
        }
    }

    if ($user_id) {
        // storing the extended profile
        $store_extended = false;
        $sql = "UPDATE " . Database::get_main_table(TABLE_MAIN_USER) . " SET ";
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true') {
            $sql_set[] = "competences = '" . Database::escape_string($values['competences']) . "'";
            $store_extended = true;
        }
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true') {
            $sql_set[] = "diplomas = '" . Database::escape_string($values['diplomas']) . "'";
            $store_extended = true;
        }
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'myteach') == 'true') {
            $sql_set[] = "teach = '" . Database::escape_string($values['teach']) . "'";
            $store_extended = true;
        }
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true') {
            $sql_set[] = "openarea = '" . Database::escape_string($values['openarea']) . "'";
            $store_extended = true;
        }
        if ($store_extended) {
            $sql .= implode(',', $sql_set);
            $sql .= " WHERE user_id = '" . Database::escape_string($user_id) . "'";
            Database::query($sql);
        }

        // if there is a default duration of a valid account then we have to change the expiration_date accordingly
        if (api_get_setting('account_valid_duration') != '') {
            $sql = "UPDATE " . Database::get_main_table(TABLE_MAIN_USER) . " SET expiration_date='registration_date+1' WHERE user_id='" . $user_id . "'";
            Database::query($sql);
        }

        // if the account has to be approved then we set the account to inactive, sent a mail to the platform admin and exit the page.

        $TABLE_USER = Database::get_main_table(TABLE_MAIN_USER);
        // 1. set account inactive
        $sql = "UPDATE " . $TABLE_USER . "	SET active='0' WHERE user_id='" . $user_id . "'";
        Database::query($sql);
    }


    // Terms & Conditions
    if (api_get_setting('allow_terms_conditions') == 'true') {
        // update the terms & conditions

        if (isset($values['legal_accept_type'])) {
            $cond_array = explode(':', $values['legal_accept_type']);
            if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                $time = time();
                $condition_to_save = intval($cond_array[0]) . ':' . intval($cond_array[1]) . ':' . $time;
                UserManager::update_extra_field_value($user_id, 'legal_accept', $condition_to_save);
            }
        }
        $values = api_get_user_info($user_id);
    }

    /* SESSION REGISTERING */
    /* @todo move this in a function */
    $_user['firstName'] = stripslashes($values['firstname']);
    $_user['lastName'] = stripslashes($values['lastname']);
    $_user['mail'] = $values['email'];
    $_user['language'] = $values['language'];
    $_user['user_id'] = $user_id;
    $_user['username'] = $values['username'];
    Session::write('bc_user', $_user);
    header('Location:process.php');
} else {
    Display :: display_header($tool_name);
    echo Display::page_header($tool_name);
    echo $content;
    $form->display();
    Display :: display_footer();
}
