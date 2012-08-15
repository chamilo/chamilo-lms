<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a form for registering new users.
 *	@package	 chamilo.auth
 */

use \ChamiloSession as Session;

$language_file = array('registration', 'admin');

if (!empty($_POST['language'])) { //quick hack to adapt the registration form result to the selected registration language
    $_GET['language'] = $_POST['language'];
}
require_once '../inc/global.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

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
    Session::write('course_redirect',   $course_code_redirect);
    Session::write('exercise_redirect', $exercise_redirect);
}

if ($user_already_registered_show_terms == false) {    
            
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

    if (api_get_setting('login_is_email') == 'true') {
        $form->applyFilter('email','trim');
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
    // Enabled by Ivan Tcholakov, 06-APR-2009. CONFVAL_ASK_FOR_OFFICIAL_CODE = false by default.
    //	OFFICIAL CODE
    if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
        $form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
        if (api_get_setting('registration', 'officialcode') == 'true')
            $form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
    }

    //	USERNAME
    if (api_get_setting('login_is_email') != 'true') {
        $form->addElement('text', 'username', get_lang('UserName'), array('size' => USERNAME_MAX_LENGTH));
        $form->applyFilter('username','trim');
        $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
        $form->addRule('username', get_lang('UsernameWrong'), 'username');
        $form->addRule('username', get_lang('UserTaken'), 'username_available');
    }
    
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
        $form->addElement('radio', 'status', get_lang('Profile'), get_lang('RegStudent'), STUDENT);
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
    $extra_data = UserManager::get_extra_user_data(api_get_user_id(), true);
    UserManager::set_extra_fields_in_form($form, $extra_data, 'registration');
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

if (!CustomPages::enabled()) {
    // Load terms & conditions from the current lang
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

    $tool_name = get_lang('Registration', null, (!empty($_POST['language'])?$_POST['language']:$_user['language']));

    if (api_get_setting('allow_terms_conditions') == 'true' && $user_already_registered_show_terms) {
        $tool_name = get_lang('TermsAndConditions');
    }

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

    if (file_exists($home.'register_top_'.$user_selected_language.'.html')) {
        $home_top_temp = @(string)file_get_contents($home.'register_top_'.$user_selected_language.'.html');
        $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        if (!empty($open)) {
            $content =  '<div class="well_border">'.$open.'</div>';
        }
    }
    
    // Forbidden to self-register    
    /*if (api_get_setting('allow_registration') == 'false') {
        api_not_allowed(true);
    }*/
    
    if (api_get_setting('allow_registration') == 'approval') {
        $content .= Display::return_message(get_lang('YourAccountHasToBeApproved'));
    }
    
    //if openid was not found
    if (!empty($_GET['openid_msg']) && $_GET['openid_msg'] == 'idnotfound') {
        $content .= Display::return_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));
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
        if (!$term_preview) {
            $language = api_get_language_id('english'); //this must work
            $term_preview = LegalManager::get_last_condition($language);
        }
    }

    // Version and language
    $form->addElement('hidden', 'legal_accept_type', $term_preview['version'].':'.$term_preview['language_id']);
    $form->addElement('hidden', 'legal_info', $term_preview['legal_id'].':'.$term_preview['language_id']);

    if ($term_preview['type'] == 1) {
        $form->addElement('checkbox', 'legal_accept', null, get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.get_lang('TermsAndConditions').'</a>');
        $form->addRule('legal_accept',  get_lang('ThisFieldIsRequired'), 'required');
    } else {        
        $preview = LegalManager::show_last_condition($term_preview);
        $form->addElement('label', null, $preview);        
    }
}

$form->addElement('button', 'submit', get_lang('RegisterUser'), array('class' => 'btn btn-primary btn-large'));

if ($form->validate()) {
    
    $values = $form->exportValues();
    $values['username'] = api_substr($values['username'], 0, USERNAME_MAX_LENGTH); //make *sure* the login isn't too long

    if (api_get_setting('allow_registration_as_teacher') == 'false') {
        $values['status'] = STUDENT;
    }
    // Added by Ivan Tcholakov, 06-MAR-2008.
    if (empty($values['official_code'])) {
        $values['official_code'] =  api_strtoupper($values['username']);
    }

    if (api_get_setting('login_is_email') == 'true') {
        $values['username'] = $values['email'];
    }
    
    if ($user_already_registered_show_terms && api_get_setting('allow_terms_conditions') == 'true') {
        $user_id = $_SESSION['term_and_condition']['user_id'];
        $is_admin = UserManager::is_admin($user_id);
        Session::write('is_platformAdmin', $is_admin);
    } else {
        // Creates a new user
        $user_id = UserManager::create_user($values['firstname'], $values['lastname'], $values['status'], $values['email'], $values['username'], $values['pass1'], $values['official_code'], $values['language'], $values['phone'], $picture_uri, PLATFORM_AUTH_SOURCE, null, 1, 0, null, null, true);

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
                UserManager::update_extra_field_value($user_id, $key, $value);
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

                // 2. Send mail to all platform admin
                
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
                $url_edit        = Display::url(api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id, api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id);
                $emailbody		.= get_lang('ManageUser',null,$values['language']).": $url_edit";

                $admins = UserManager::get_all_administrators();
                foreach ($admins as $admin_info) {
                    MessageManager::send_message($admin_info['user_id'], $emailsubject, $emailbody, null, null, null, null, null, null, $user_id);
                }
                
                // 3. exit the page
                unset($user_id);
                
                Display :: display_header($tool_name);
                echo Display::page_header($tool_name);    
                echo $content;
                Display::display_footer();
            }
        }
    }

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
        $values = api_get_user_info($user_id);
    }

    /* SESSION REGISTERING */
    /* @todo move this in a function */
    $_user['firstName'] = stripslashes($values['firstname']);
    $_user['lastName'] 	= stripslashes($values['lastname']);
    $_user['mail'] 		= $values['email'];
    $_user['language'] 	= $values['language'];
    $_user['user_id']	= $user_id;
    $is_allowedCreateCourse = $values['status'] == 1;

    Session::write('_user', $_user);
    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

    //stats
    event_login();

    // last user login date is now
    $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
    Session::write('user_last_login_datetime', $user_last_login_datetime);
    $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);
    
    $text_after_registration = '<p>'.get_lang('Dear', null, $_user['language']).' '.stripslashes(Security::remove_XSS($recipient_name)).',<br /><br />'.get_lang('PersonalSettings',null,$_user['language']).".</p>";

    $form_data = array( 'button' => Display::button('next', get_lang('Next', null, $_user['language']), array('class' => 'btn btn-primary btn-large')),
                        'message' => null,
                        'action' => api_get_path(WEB_PATH).'user_portal.php');
    
    if (api_get_setting('allow_terms_conditions') == 'true' && $user_already_registered_show_terms) {        
        $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
    } else {
        
        if (!empty ($values['email'])) {
            $text_after_registration.= '<p>'.get_lang('MailHasBeenSent',null,$_user['language']).'.</p>';
        }
        
        if ($is_allowedCreateCourse) {
            $form_data['message'] = '<p>'. get_lang('NowGoCreateYourCourse',null,$_user['language']). "</p>";            
            $form_data['action']  = '../create_course/add_course.php';
            
            if (api_get_setting('course_validation') == 'true') {                
                $form_data['button'] = Display::button('next', get_lang('CreateCourseRequest', null, $_user['language']), array('class' => 'btn btn-primary btn-large'));
            } else {
                $form_data['button'] = Display::button('next', get_lang('CourseCreate', null, $_user['language']), array('class' => 'btn btn-primary btn-large'));                          
            }
        } else {
            if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                $form_data['action']    = 'courses.php?action=subscribe';
                $form_data['message']   = '<p>'. get_lang('NowGoChooseYourCourses',null,$_user['language']). ".</p>";
            } else {
                $form_data['action']  = api_get_path(WEB_PATH).'user_portal.php';                
            }     
            $form_data['button'] = Display::button('next', get_lang('Next', null, $_user['language']), array('class' => 'btn btn-primary btn-large'));
        }
    }
    
    $course_code_redirect = Session::read('course_redirect');
        
    if (!empty($course_code_redirect)) {
        $course_info = api_get_course_info($course_code_redirect);        
        if (!empty($course_info)) {
            
            if (in_array($course_info['visibility'], array(COURSE_VISIBILITY_OPEN_PLATFORM, COURSE_VISIBILITY_OPEN_WORLD))) {
                $user_id = api_get_user_id();
                if (CourseManager::subscribe_user($user_id, $course_info['code'])) {
                                    
                    $form_data['action'] = $course_info['course_public_url'];            
                    $form_data['message'] = sprintf(get_lang('YouHaveBeenRegisteredToCourseX'), $course_info['title']);
                    $form_data['button'] = Display::button('next', get_lang('GoToCourse', null, $_user['language']), array('class' => 'btn btn-primary btn-large'));                    
                    
                    $exercise_redirect = intval(Session::read('exercise_redirect'));
                    
                    if (!empty($exercise_redirect)) {
                        $form_data['action'] = api_get_path(WEB_CODE_PATH).'exercice/overview.php?exerciseId='.intval($exercise_redirect).'&cidReq='.$course_info['code'];                                                
                        $form_data['message'] .= '<br />'.get_lang('YouCanAccessTheExercise');
                        $form_data['button'] = Display::button('next', get_lang('Go', null, $_user['language']), array('class' => 'btn btn-primary btn-large'));
                    }
                    
                    if (!empty($form_data['action'])) {
                        header('Location: '.$form_data['action']);
                        exit;
                    }
                }
            }
        }
    }
    
    $form_register = new FormValidator('form_register', 'post', $form_data['action']);    
    if (!empty($form_data['message'])) {
        $form_register->addElement('html', $form_data['message'].'<br /><br />');
    }    
    $form_register->addElement('html', $form_data['button']);    
    $text_after_registration .= $form_register->return_form();  
    
    //Just in case
    Session::erase('course_redirect');
    Session::erase('exercise_redirect');
    
    Display :: display_header($tool_name);
    echo Display::page_header($tool_name);
    
    echo $content;    
    echo $text_after_registration;
    
    if (CustomPages::enabled()) {
        CustomPages::display(CustomPages::REGISTRATION_FEEDBACK, array('info' => $text_after_registration));
    }    
} else {
    
    Display :: display_header($tool_name);
    echo Display::page_header($tool_name);
    
    echo $content;    
    
    // Custom pages
    if (CustomPages::enabled()) {
        CustomPages::display(CustomPages::REGISTRATION, array('form' => $form));
    } else {
        $form->display();
    }
}
Display :: display_footer();