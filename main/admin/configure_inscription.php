<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 *	This script displays a form for registering new users.
 *
 *	@package	 chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

// Load terms & conditions from the current lang
if (api_get_setting('allow_terms_conditions') == 'true') {
    $get = array_keys($_GET);
    if (isset($get)) {
        if (isset($get[0]) && $get[0] == 'legal') {
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

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$tool_name = get_lang('ConfigureInscription');
if (!empty($action)) {
    $interbreadcrumb[] = ['url' => 'configure_inscription.php', 'name' => get_lang('ConfigureInscription')];
    switch ($action) {
        case 'edit_top':
            $tool_name = get_lang('EditTopRegister');
            break;
    }
}

$lang = ''; //el for "Edit Language"
if (!empty($_SESSION['user_language_choice'])) {
    $lang = $_SESSION['user_language_choice'];
} elseif (!empty($_SESSION['_user']['language'])) {
    $lang = $_SESSION['_user']['language'];
} else {
    $lang = api_get_setting('platformLanguage');
}

// ----- Ensuring availability of main files in the corresponding language -----
/*if (api_is_multiple_url_enabled()) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));

        $clean_url = api_replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';

        $homep = api_get_path(SYS_APP_PATH).'home/'; //homep for Home Path
        $homep_new = api_get_path(SYS_APP_PATH).'home/'.$clean_url; //homep for Home Path added the url
        $new_url_dir = api_get_path(SYS_APP_PATH).'home/'.$clean_url;
        //we create the new dir for the new sites
        if (!is_dir($new_url_dir)) {
            mkdir($new_url_dir, api_get_permissions_for_new_directories());
        }
    }
} else {
    $homep_new = '';
    $homep = api_get_path(SYS_APP_PATH).'home/'; //homep for Home Path
}

$topf = 'register_top'; //topf for Top File
$ext = '.html'; //ext for HTML Extension - when used frequently, variables are
$homef = [$topf];

// If language-specific file does not exist, create it by copying default file
foreach ($homef as $my_file) {
    if (api_is_multiple_url_enabled()) {
        if (!file_exists($homep_new.$my_file.'_'.$lang.$ext)) {
            copy($homep.$my_file.$ext, $homep_new.$my_file.'_'.$lang.$ext);
        }
    } else {
        if (!file_exists($homep.$my_file.'_'.$lang.$ext)) {
            copy($homep.$my_file.$ext, $homep.$my_file.'_'.$lang.$ext);
        }
    }
}

if (!empty($homep_new)) {
    $homep = $homep_new;
}

if (!empty($action)) {
    if (isset($_POST['formSent'])) {
        switch ($action) {
            case 'edit_top':
                // Filter
                $home_top = trim(stripslashes($_POST['register_top']));
                // Write
                if (file_exists($homep.$topf.'_'.$lang.$ext)) {
                    if (is_writable($homep.$topf.'_'.$lang.$ext)) {
                        $fp = fopen($homep.$topf.'_'.$lang.$ext, 'w');
                        fputs($fp, $home_top);
                        fclose($fp);
                    } else {
                        $errorMsg = get_lang('HomePageFilesNotWritable');
                    }
                } else {
                    //File does not exist
                    $fp = fopen($homep.$topf.'_'.$lang.$ext, 'w');
                    fputs($fp, $home_top);
                    fclose($fp);
                }
                break;
        }
        if (empty($errorMsg)) {
            header('Location: '.api_get_self());
            exit();
        }
    } else {
        switch ($action) {
            case 'edit_top':
                // This request is only the preparation for the update of the home_top
                $home_top = '';
                if (is_file($homep.$topf.'_'.$lang.$ext) && is_readable($homep.$topf.'_'.$lang.$ext)) {
                    $home_top = @(string) file_get_contents($homep.$topf.'_'.$lang.$ext);
                } elseif (is_file($homep.$topf.$lang.$ext) && is_readable($homep.$topf.$lang.$ext)) {
                    $home_top = @(string) file_get_contents($homep.$topf.$lang.$ext);
                } else {
                    $errorMsg = get_lang('HomePageFilesNotReadable');
                }
                $home_top = api_to_system_encoding($home_top, api_detect_encoding(strip_tags($home_top)));
                break;
        }
    }
}*/

$form = new FormValidator('registration');
if (api_get_setting('allow_terms_conditions') === 'true') {
    $display_all_form = !isset($_SESSION['update_term_and_condition']['user_id']);
} else {
    $display_all_form = true;
}

if ($display_all_form) {
    if (api_is_western_name_order()) {
        //	FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40, 'disabled' => 'disabled']);
        $form->addElement('text', 'lastname', get_lang('LastName'), ['size' => 40, 'disabled' => 'disabled']);
    } else {
        //	LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname', get_lang('LastName'), ['size' => 40, 'disabled' => 'disabled']);
        $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40, 'disabled' => 'disabled']);
    }
    $form->applyFilter('firstname', 'trim');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

    //	EMAIL
    $form->addElement('text', 'email', get_lang('Email'), ['size' => 40, 'disabled' => 'disabled']);
    if (api_get_setting('registration', 'email') == 'true') {
        $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    }
    $form->addRule('email', get_lang('EmailWrong'), 'email');

    //	USERNAME
    $form->addElement('text', 'username', get_lang('UserName'), ['size' => USERNAME_MAX_LENGTH, 'disabled' => 'disabled']);
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', get_lang('UsernameWrong'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);

    //	PASSWORD
    $form->addElement('password', 'pass1', get_lang('Pass'), ['size' => 40, 'disabled' => 'disabled']);
    $form->addElement('password', 'pass2', get_lang('Confirmation'), ['size' => 40, 'disabled' => 'disabled']);
    $form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(['pass1', 'pass2'], get_lang('PassTwo'), 'compare');
    $form->addPasswordRule('pass1');

    //	PHONE
    $form->addElement('text', 'phone', get_lang('Phone'), ['size' => 40, 'disabled' => 'disabled']);
    if (api_get_setting('registration', 'phone') == 'true') {
        $form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
    }

    //	LANGUAGE
    if (api_get_setting('registration', 'language') == 'true') {
        $form->addSelectLanguage(
            'language',
            get_lang('Language'),
            '',
            ['disabled' => 'disabled']
        );
    }

    //	STUDENT/TEACHER
    if (api_get_setting('allow_registration_as_teacher') != 'false') {
        $form->addElement('radio', 'status', get_lang('Status'), get_lang('RegStudent'), STUDENT, ['disabled' => 'disabled']);
        $form->addElement('radio', 'status', null, get_lang('RegAdmin'), COURSEMANAGER, ['disabled' => 'disabled']);
    }

    //	EXTENDED FIELDS
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true'
    ) {
        $form->addHtmlEditor('competences', get_lang('MyCompetences'), false, false, ['ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130']);
    }

    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true'
    ) {
        $form->addHtmlEditor('diplomas', get_lang('MyDiplomas'), false, false, ['ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130']);
    }

    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'myteach') == 'true'
    ) {
        $form->addHtmlEditor('teach', get_lang('MyTeach'), false, false, ['ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130']);
    }

    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true'
    ) {
        $form->addHtmlEditor('openarea', get_lang('MyPersonalOpenArea'), false, false, ['ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130']);
    }

    if (api_get_setting('extended_profile') == 'true') {
        if (api_get_setting('extendedprofile_registrationrequired', 'mycomptetences') == 'true') {
            $form->addRule('competences', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registrationrequired', 'mydiplomas') == 'true') {
            $form->addRule('diplomas', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registrationrequired', 'myteach') == 'true') {
            $form->addRule('teach', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registrationrequired', 'mypersonalopenarea') == 'true') {
            $form->addRule('openarea', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    $extraField = new ExtraField('user');
    $extraField->addElements($form);
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

    // Version and language //password
    $form->addElement('hidden', 'legal_accept_type', $term_preview['version'].':'.$term_preview['language_id']);
    $form->addElement('hidden', 'legal_info', $term_preview['id'].':'.$term_preview['language_id']);

    if ($term_preview['type'] == 1) {
        $form->addElement(
            'checkbox',
            'legal_accept',
            null,
            get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.get_lang('TermsAndConditions').'</a>'
        );
        $form->addRule('legal_accept', get_lang('ThisFieldIsRequired'), 'required');
    } else {
        if (!empty($term_preview['content'])) {
            $preview = LegalManager::show_last_condition($term_preview);
            $form->addElement('label', get_lang('TermsAndConditions'), $preview);
        }
    }
}

$form->addButtonSave(get_lang('RegisterUser'));

$defaults['status'] = STUDENT;

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

$form->setDefaults($defaults);

$tpl = new Template();

$content = Display::page_header($tool_name);

// The following security condition has been removed, because it makes no sense here. See Bug #1846.
//// Forbidden to self-register
//if (api_get_setting('allow_registration') == 'false') {
//    api_not_allowed();
//}

//api_display_tool_title($tool_name);
if (api_get_setting('allow_registration') == 'approval') {
    $content .= Display::return_message(get_lang('YourAccountHasToBeApproved'), 'normal');
}

$url = api_get_path(WEB_PUBLIC_PATH).'internal_page/edit/inscription';
//Form of language
//$content .= api_display_language_form();
$content .= '&nbsp;&nbsp;<a href="'.api_get_self().'?action=edit_top">'.
    Display::return_icon('edit.gif', get_lang('Edit')).'</a> 
    <a href="'.$url.'">'.get_lang('EditNotice').'</a>';

$content .= $form->returnForm();

$page = Container::getPage('inscription');

$tpl->assign('page', $page);
$tpl->assign('form', $content);

$templateName = $tpl->get_template('auth/inscription_edit.html.twig');
$tpl->display($templateName);
