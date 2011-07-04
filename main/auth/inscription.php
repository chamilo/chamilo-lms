<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a form for registering new users.
 *	@package	 chamilo.auth
 */

$language_file = array('registration', 'admin');
if (!empty($_POST['language'])) { //quick hack to adapt the registration form result to the selected registration language
    $_GET['language'] = $_POST['language'];
}
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'legal.lib.php';

// Load terms & conditions from the current lang
if (api_get_setting('allow_terms_conditions') == 'true') {
    $get = array_keys($_GET);
    if (isset($get)) {
        if ($get[0] == 'legal'){
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
            Display :: display_header('');
            echo '<div class="actions-title">';
            echo $tool_name;
            echo '</div>';
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

$tool_name = get_lang('Registration',null,(!empty($_POST['language'])?api_get_valid_language($_POST['language']):$_user['language']));
Display :: display_header($tool_name);

echo Display::tag('h1', $tool_name);

$home = api_get_path(SYS_PATH).'home/';
if ($_configuration['multiple_access_urls']) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';
        $home_old  = api_get_path(SYS_PATH).'home/';
        $home = api_get_path(SYS_PATH).'home/'.$clean_url;
    }
}

if (!empty($_SESSION['user_language_choice'])) {
    $user_selected_language = $_SESSION['user_language_choice'];
} elseif (!empty($_SESSION['_user']['language'])) {
    $user_selected_language = $_SESSION['_user']['language'];
} else {
    $user_selected_language = get_setting('platformLanguage');
}

if (file_exists($home.'register_top_'.$user_selected_language.'.html')) {
    $home_top_temp = @(string)file_get_contents($home.'register_top_'.$user_selected_language.'.html');
    $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    if (!empty($open)) {
        echo '<div style="border:1px solid #E1E1E1; padding:2px;">'.$open.'</div>';
    }
}

// Forbidden to self-register
if (api_get_setting('allow_registration') == 'false') {
    api_not_allowed();
}

//api_display_tool_title($tool_name);
if (api_get_setting('allow_registration') == 'approval') {
    Display::display_normal_message(get_lang('YourAccountHasToBeApproved'));
}
//if openid was not found
if (!empty($_GET['openid_msg']) && $_GET['openid_msg'] == 'idnotfound') {
    Display::display_warning_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));
}

$form = new FormValidator('registration');
if (api_get_setting('allow_terms_conditions') == 'true') {
    $display_all_form = !isset($_SESSION['update_term_and_condition'][1]);
} else {
    $display_all_form = true;
}

if ($display_all_form) {

    if (api_is_western_name_order()) {
        //	FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
        $form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
    } else {
        //	LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
        $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
    }
    $form->applyFilter(array('lastname', 'firstname'), 'trim');
    $form->addRule('lastname',  get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
    //	EMAIL
    $form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
    if (api_get_setting('registration', 'email') == 'true') {
        $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    }
    $form->addRule('email', get_lang('EmailWrong'), 'email');
    if (api_get_setting('openid_authentication') == 'true') {
        $form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40));
    }
    // Enabled by Ivan Tcholakov, 06-APR-2009. CONFVAL_ASK_FOR_OFFICIAL_CODE = false by default.
    //	OFFICIAL CODE
    if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
        $form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
        if (api_get_setting('registration', 'officialcode') == 'true')
            $form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
    }
    //
    //	USERNAME
    $form->addElement('text', 'username', get_lang('UserName'), array('size' => USERNAME_MAX_LENGTH));
    $form->applyFilter('username','trim');
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('username', get_lang('UsernameWrong'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available');
    //	PASSWORD
    $form->addElement('password', 'pass1', get_lang('Pass'),         array('size' => 20, 'autocomplete' => 'off'));
    $form->addElement('password', 'pass2', get_lang('Confirmation'), array('size' => 20, 'autocomplete' => 'off'));
    $form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');
    if (CHECK_PASS_EASY_TO_FIND)
        $form->addRule('password1', get_lang('PassTooEasy').': '.api_generate_password(), 'callback', 'api_check_password');

    //	PHONE
    $form->addElement('text', 'phone', get_lang('Phone'), array('size' => 20));
    if (api_get_setting('registration', 'phone') == 'true')
        $form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');

    // PICTURE
    /*if (api_get_setting('profile', 'picture') == 'true') {
        $form->addElement('file', 'picture', get_lang('AddPicture'));
        $allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
        $form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
    }*/

    //	LANGUAGE
    if (api_get_setting('registration', 'language') == 'true') {
        $form->addElement('select_language', 'language', get_lang('Language'));
    }
    //	STUDENT/TEACHER
    if (api_get_setting('allow_registration_as_teacher') != 'false') {
        $form->addElement('radio', 'status', get_lang('Status'), get_lang('RegStudent'), STUDENT);
        $form->addElement('radio', 'status', null, get_lang('RegAdmin'), COURSEMANAGER);
    }

    //	EXTENDED FIELDS
    if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true') {
        $form->add_html_editor('competences', get_lang('MyCompetences'), false, false, array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130'));
    }
    if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true') {
        $form->add_html_editor('diplomas', get_lang('MyDiplomas'), false, false, array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130'));
    }
    if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'myteach') == 'true') {
        $form->add_html_editor('teach', get_lang('MyTeach'), false, false, array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130'));
    }
    if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true') {
        $form->add_html_editor('openarea', get_lang('MyPersonalOpenArea'), false, false, array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130'));
    }
    if (api_get_setting('extended_profile') == 'true') {
        if (api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true' && api_get_setting('extendedprofile_registrationrequired', 'mycomptetences') == 'true') {
            $form->addRule('competences', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true' && api_get_setting('extendedprofile_registrationrequired', 'mydiplomas') == 'true') {
            $form->addRule('diplomas', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registration', 'myteach') == 'true' && api_get_setting('extendedprofile_registrationrequired', 'myteach') == 'true') {
            $form->addRule('teach', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true' && api_get_setting('extendedprofile_registrationrequired','mypersonalopenarea') == 'true') {
            $form->addRule('openarea', get_lang('ThisFieldIsRequired'), 'required');
        }
    }
    // EXTRA FIELDS
    $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC');
    $extra_data = UserManager::get_extra_user_data(api_get_user_id(), true);
    foreach ($extra as $id => $field_details) {
        if ($field_details[6] == 0) {
            continue;
        }
        switch($field_details[2]) {
            case USER_FIELD_TYPE_TEXT:
                $form->addElement('text', 'extra_'.$field_details[1], $field_details[3], array('size' => 40));
                $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                $form->applyFilter('extra_'.$field_details[1], 'trim');
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
                break;
            case USER_FIELD_TYPE_TEXTAREA:
                $form->add_html_editor('extra_'.$field_details[1], $field_details[3], false, false, array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130'));
                //$form->addElement('textarea', 'extra_'.$field_details[1], $field_details[3], array('size' => 80));
                $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                $form->applyFilter('extra_'.$field_details[1], 'trim');
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
                break;
            case USER_FIELD_TYPE_RADIO:
                $group = array();
                foreach ($field_details[9] as $option_id => $option_details) {
                    $options[$option_details[1]] = $option_details[2];
                    $group[] =& HTML_QuickForm::createElement('radio', 'extra_'.$field_details[1], $option_details[1], $option_details[2].'<br />',$option_details[1]);
                }
                $form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '');
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
                break;
            case USER_FIELD_TYPE_SELECT:
                $get_lang_variables = false;
                if (in_array($field_details[1], array('mail_notify_message','mail_notify_invitation', 'mail_notify_group_message'))) {
                    $get_lang_variables = true;
                }
                $options = array();
                foreach($field_details[9] as $option_id => $option_details) {
                    if ($get_lang_variables) {
                        $option_details[2] = get_lang($option_details[2]);
                    }
                    $options[$option_details[1]] = $option_details[2];
                }
                if ($get_lang_variables) {
                    $field_details[3] = get_lang($field_details[3]);
                }
                $form->addElement('select', 'extra_'.$field_details[1], $field_details[3], $options, '');
                break;
            case USER_FIELD_TYPE_SELECT_MULTIPLE:
                $options = array();
                foreach ($field_details[9] as $option_id => $option_details) {
                    $options[$option_details[1]] = $option_details[2];
                }
                $form->addElement('select', 'extra_'.$field_details[1], $field_details[3], $options, array('multiple' => 'multiple'));
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
                break;
            case USER_FIELD_TYPE_DATE:
                $form->addElement('datepickerdate', 'extra_'.$field_details[1], $field_details[3], array('form_name' => 'registration'));
                $form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear', 1900);
                $defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
                $form -> setDefaults($defaults);
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
                $form->applyFilter('theme', 'trim');
                break;
            case USER_FIELD_TYPE_DATETIME:
                $form->addElement('datepicker', 'extra_'.$field_details[1], $field_details[3], array('form_name' => 'registration'));
                $form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear', 1900);
                $defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
                $form -> setDefaults($defaults);
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
                $form->applyFilter('theme', 'trim');
                break;
            case USER_FIELD_TYPE_DOUBLE_SELECT:
                foreach ($field_details[9] as $key => $element) {
                    if ($element[2][0] == '*') {
                        $values['*'][$element[0]] = str_replace('*', '', $element[2]);
                    } else {
                        $values[0][$element[0]] = $element[2];
                    }
                }

                $group = '';
                $group[] =& HTML_QuickForm::createElement('select', 'extra_'.$field_details[1], '', $values[0], '');
                $group[] =& HTML_QuickForm::createElement('select', 'extra_'.$field_details[1].'*', '', $values['*'], '');
                $form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '&nbsp;');
                if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);

                // recoding the selected values for double : if the user has selected certain values, we have to assign them to the correct select form
                if (key_exists('extra_'.$field_details[1], $extra_data)) {
                    // exploding all the selected values (of both select forms)
                    $selected_values = explode(';', $extra_data['extra_'.$field_details[1]]);
                    $extra_data['extra_'.$field_details[1]]  =array();

                    // looping through the selected values and assigning the selected values to either the first or second select form
                    foreach ($selected_values as $key => $selected_value) {
                            if(is_array($values)){
                            if (array_key_exists($selected_value, $values[0])) {
                                $extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1]] = $selected_value;
                            } else {
                                $extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1].'*'] = $selected_value;
                            }
                        }
                    }
                }
                break;
            case USER_FIELD_TYPE_DIVIDER:
                $form->addElement('static', $field_details[1], '<br /><strong>'.$field_details[3].'</strong>');
                break;
        }
    }

}

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
        if (!$term_preview){
            $language = api_get_language_id('english'); //this must work
            $term_preview = LegalManager::get_last_condition($language);
        }
    }
    // Version and language
    $form->addElement('hidden', 'legal_accept_type', $term_preview['version'].':'.$term_preview['language_id']);
    $form->addElement('hidden', 'legal_info', $term_preview['legal_id'].':'.$term_preview['language_id']);
    // Password
    if (isset($_SESSION['info_current_user'][1]) && isset($_SESSION['info_current_user'][2])) {
        $form->addElement('hidden', 'login', $_SESSION['info_current_user'][1]);
        $form->addElement('hidden', 'password', $_SESSION['info_current_user'][2]);
    }
    if ($term_preview['type'] == 1) {
        $form->addElement('checkbox', 'legal_accept', null, get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.get_lang('TermsAndConditions').'</a>');
        $form->addRule('legal_accept',  get_lang('ThisFieldIsRequired'), 'required');
    } else {
        if (!empty($term_preview['content'])) {
            $preview = LegalManager::show_last_condition($term_preview);
            $term_preview  = '<div class="row">
                    <div class="label">'.get_lang('TermsAndConditions').'</div>
                    <div class="formw">
                    '.$preview.'
                    <br />
                    </div>
                    </div>';
            $form->addElement('html', $term_preview);
        }
    }
}

$form->addElement('style_submit_button', 'submit', get_lang('RegisterUser'), 'class="save"');

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

if ($form->validate()) {
    /*
      STORE THE NEW USER DATA INSIDE THE MAIN DATABASE
     */
    $values = $form->exportValues();

    $values['username'] = api_substr($values['username'], 0, USERNAME_MAX_LENGTH); //make *sure* the login isn't too long

    if (api_get_setting('allow_registration_as_teacher') == 'false') {
        $values['status'] = STUDENT;
    }
    // Added by Ivan Tcholakov, 06-MAR-2008.
    if (empty($values['official_code'])) {
        $values['official_code'] =  api_strtoupper($values['username']);
    } 

    // creating a new user
    $user_id = UserManager::create_user($values['firstname'], $values['lastname'], $values['status'], $values['email'], $values['username'], $values['pass1'], $values['official_code'], $values['language'], $values['phone'], $picture_uri);

        // Terms & Conditions
    if (api_get_setting('allow_terms_conditions') == 'true') {
        // update the terms & conditions
        if (isset($values['legal_accept_type'])) {
            $cond_array = explode(':', $values['legal_accept_type']);
            if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                $time = time();
                $condition_to_save = intval($cond_array[0]).':'.intval($cond_array[1]).':'.$time;
                UserManager::update_extra_field_value($user_id, 'legal_accept', $condition_to_save);
            }
        }
    }
    
    // Register extra fields
    $extras = array();
    foreach ($values as $key => $value) {
        if (substr($key, 0, 6) == 'extra_') { //an extra field
            $extras[substr($key,6)] = $value;
        }
    }
    
    //update the extra fields
    $count_extra_field = count($extras);
    if ($count_extra_field > 0) {
        foreach ($extras as $key => $value) {
            $myres = UserManager::update_extra_field_value($user_id, $key, $value);
        }
    }

    if ($user_id) {
        // storing the extended profile
        $store_extended = false;
        $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)." SET ";
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true') {
            $sql_set[] = "competences = '".Database::escape_string($values['competences'])."'";
            $store_extended = true;
        }
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true') {
            $sql_set[] = "diplomas = '".Database::escape_string($values['diplomas'])."'";
            $store_extended = true;
        }
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'myteach') == 'true') {
            $sql_set[] = "teach = '".Database::escape_string($values['teach'])."'";
            $store_extended = true;
        }
        if (api_get_setting('extended_profile') == 'true' && api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true') {
            $sql_set[] = "openarea = '".Database::escape_string($values['openarea'])."'";
            $store_extended = true;
        }
        if ($store_extended) {
            $sql .= implode(',', $sql_set);
            $sql .= " WHERE user_id = '".Database::escape_string($user_id)."'";
            Database::query($sql);
        }

        // if there is a default duration of a valid account then we have to change the expiration_date accordingly
        if (api_get_setting('account_valid_duration') != '') {
            $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)." SET expiration_date='registration_date+1' WHERE user_id='".$user_id."'";
            Database::query($sql);
        }

        // if the account has to be approved then we set the account to inactive, sent a mail to the platform admin and exit the page.
        if (api_get_setting('allow_registration') == 'approval') {
            $TABLE_USER = Database::get_main_table(TABLE_MAIN_USER);
            // 1. set account inactive
            $sql = "UPDATE ".$TABLE_USER."	SET active='0' WHERE user_id='".$user_id."'";
            Database::query($sql);

            $table_main_admin = Database::get_main_table(TABLE_MAIN_ADMIN);

            if ($_configuration['multiple_access_urls']) {
                $access_url_id = api_get_current_access_url_id();
                $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
                $sql_get_id_admin = "SELECT admin.user_id FROM ".$tbl_url_rel_user." as url,  ".$table_main_admin." as admin WHERE access_url_id='".$access_url_id."' AND admin.user_id=url.user_id";
            } else {
                $sql_get_id_admin = "SELECT * FROM ".$table_main_admin;
            }
            $result = Database::query($sql_get_id_admin);
            while ($row = Database::fetch_array($result)) {

                $sql_admin_list = "SELECT * FROM ".$TABLE_USER." WHERE user_id='".$row['user_id']."'";
                $result_list = Database::query($sql_admin_list);
                $admin_list = Database::fetch_array($result_list);
                $emailto = $admin_list['email'];

                // 2. send mail to the platform admin
                $emailfromaddr 	 = api_get_setting('emailAdministrator');
                $emailfromname 	 = api_get_setting('siteName');
                $emailsubject	 = get_lang('ApprovalForNewAccount',null,$values['language']).': '.$values['username'];
                $emailbody		 = get_lang('ApprovalForNewAccount',null,$values['language'])."\n";
                $emailbody		.= get_lang('UserName',null,$values['language']).': '.$values['username']."\n";
                if (api_is_western_name_order()) {
                    $emailbody	.= get_lang('FirstName',null,$values['language']).': '.$values['firstname']."\n";
                    $emailbody	.= get_lang('LastName',null,$values['language']).': '.$values['lastname']."\n";
                } else {
                    $emailbody	.= get_lang('LastName',null,$values['language']).': '.$values['lastname']."\n";
                    $emailbody	.= get_lang('FirstName',null,$values['language']).': '.$values['firstname']."\n";
                }
                $emailbody		.= get_lang('Email',null,$values['language']).': '.$values['email']."\n";
                $emailbody		.= get_lang('Status',null,$values['language']).': '.$values['status']."\n\n";
                $emailbody		.= get_lang('ManageUser',null,$values['language']).': '.api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id;

                $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
                $email_admin = api_get_setting('emailAdministrator');
                @api_mail('', $emailto, $emailsubject, $emailbody, $sender_name, $email_admin);
            }
            // 3. exit the page
            unset($user_id);
            Display :: display_footer();
            exit;
        }

        /*
                  SESSION REGISTERING
         */
        $_user['firstName'] = stripslashes($values['firstname']);
        $_user['lastName'] 	= stripslashes($values['lastname']);
        $_user['mail'] 		= $values['email'];
        $_user['language'] 	= $values['language'];
        $_user['user_id']	= $user_id;
        $is_allowedCreateCourse = $values['status'] == 1;
        api_session_register('_user');
        api_session_register('is_allowedCreateCourse');

        //stats
        event_login();
        // last user login date is now
        $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970

        api_session_register('user_last_login_datetime');

        /*
                     EMAIL NOTIFICATION
         */

        if (strpos($values['email'], '@') !== false) {
            // Let us predefine some variables. Be sure to change the from address!
            $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);
            $email = $values['email'];
            $emailfromaddr = api_get_setting('emailAdministrator');
            $emailfromname = api_get_setting('siteName');
            $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg',null,$_user['language']).' '.api_get_setting('siteName');

            // The body can be as long as you wish, and any combination of text and variables
            $portal_url = $_configuration['root_web'];
            if ($_configuration['multiple_access_urls']) {
                $access_url_id = api_get_current_access_url_id();
                if ($access_url_id != -1 ){
                    $url = api_get_access_url($access_url_id);
                    $portal_url = $url['url'];
                }
            }

            $emailbody = get_lang('Dear',null,$_user['language']).' '.stripslashes(Security::remove_XSS($recipient_name)).",\n\n".get_lang('YouAreReg',null,$_user['language']).' '.api_get_setting('siteName').' '.get_lang('WithTheFollowingSettings',null,$_user['language'])."\n\n".get_lang('Username',null,$_user['language']).' : '.$values['username']."\n".get_lang('Pass',null,$_user['language']).' : '.stripslashes($values['pass1'])."\n\n".get_lang('Address',null,$_user['language']).' '.api_get_setting('siteName').' '.get_lang('Is',null,$_user['language']).' : '.$portal_url."\n\n".get_lang('Problem',null,$_user['language'])."\n\n".get_lang('Formula',null,$_user['language']).",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager',null,$_user['language']).' '.api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email',null,$_user['language']).' : '.api_get_setting('emailAdministrator');

            // Here we are forming one large header line
            // Every header must be followed by a \n except the last
            $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
            $email_admin = api_get_setting('emailAdministrator');
            @api_mail($recipient_name, $email, $emailsubject, $emailbody, $sender_name, $email_admin);
        }
    }

    echo '<p>'.get_lang('Dear',null,$_user['language']).' '.stripslashes(Security::remove_XSS($recipient_name)).',<br /><br />'.get_lang('PersonalSettings',null,$_user['language']).".</p>\n";

    if (!empty ($values['email'])) {
        echo '<p>'.get_lang('MailHasBeenSent',null,$_user['language']).'.</p>';
    }

    $button_text = '';
    if ($is_allowedCreateCourse) {
        echo '<p>', get_lang('NowGoCreateYourCourse',null,$_user['language']), ".</p>\n";
        $action_url = '../create_course/add_course.php';
        $button_text = api_get_setting('course_validation') == 'true'
            ? get_lang('CreateCourseRequest', null, $_user['language'])
            : get_lang('CourseCreate', null, $_user['language']);
    } else {
        if (api_get_setting('allow_students_to_browse_courses') == 'true')
            $action_url = 'courses.php?action=subscribe';
        else
            $action_url = api_get_path(WEB_PATH).'user_portal.php';
        echo '<p>', get_lang('NowGoChooseYourCourses',null,$_user['language']), ".</p>\n";

        $button_text = get_lang('Next',null,$_user['language']);
    }
    // ?uidReset=true&uidReq=$_user['user_id']

    echo '<form action="', $action_url, '"  method="post">', "\n", '<button type="submit" class="next" name="next" value="', get_lang('Next',null,$_user['language']), '" validationmsg=" ', get_lang('Next',null,$_user['language']), ' ">', $button_text, '</button>', "\n", '</form><br />', "\n";

} else {
    $form->display();
}
?>
<br />
<?php
if (!isset($_POST['username'])) {
/*    
    <div class="actions">
<a href="<?php echo api_get_path(WEB_PATH); ?>" class="fake_button_back" ><?php echo get_lang('Back'); ?></a>
</div>
*/
?>

<?php
}
Display :: display_footer();