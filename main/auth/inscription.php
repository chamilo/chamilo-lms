<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script displays a form for registering new users.
 */

//quick hack to adapt the registration form result to the selected registration language
if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}
require_once __DIR__.'/../inc/global.inc.php';
$hideHeaders = isset($_GET['hide_headers']);

$allowedFields = [
    'official_code',
    'phone',
    'status',
    'language',
    'extra_fields',
    'address',
];

$allowedFieldsConfiguration = api_get_configuration_value('allow_fields_inscription');
if ($allowedFieldsConfiguration !== false) {
    $allowedFields = isset($allowedFieldsConfiguration['fields']) ? $allowedFieldsConfiguration['fields'] : [];
    $allowedFields['extra_fields'] = isset($allowedFieldsConfiguration['extra_fields']) ? $allowedFieldsConfiguration['extra_fields'] : [];
}

$extraFieldsLoaded = false;
$htmlHeadXtra[] = api_get_password_checker_js('#username', '#pass1');
// User is not allowed if Terms and Conditions are disabled and
// registration is disabled too.
$isNotAllowedHere = api_get_setting('allow_terms_conditions') === 'false' &&
    api_get_setting('allow_registration') === 'false';

if ($isNotAllowedHere) {
    api_not_allowed(true, get_lang('RegistrationDisabled'));
}

if (!empty($_SESSION['user_language_choice'])) {
    $user_selected_language = $_SESSION['user_language_choice'];
} elseif (!empty($_SESSION['_user']['language'])) {
    $user_selected_language = $_SESSION['_user']['language'];
} else {
    $user_selected_language = api_get_setting('platformLanguage');
}

$extraConditions = api_get_configuration_value('show_conditions_to_user');

if ($extraConditions && isset($extraConditions['conditions'])) {
    // Create user extra fields for the conditions
    $userExtraField = new ExtraField('user');
    $extraConditions = $extraConditions['conditions'];
    foreach ($extraConditions as $condition) {
        $exists = $userExtraField->get_handler_field_info_by_field_variable($condition['variable']);
        if ($exists == false) {
            $params = [
                'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'variable' => $condition['variable'],
                'display_text' => $condition['display_text'],
                'default_value' => '',
                'visible_to_self' => true,
                'visible_to_others' => false,
                'changeable' => true,
                'filter' => false,
            ];
            $userExtraField->save($params);
        }
    }
}

if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
    $layoutForm = FormValidator::LAYOUT_GRID;
} else {
    $layoutForm = FormValidator::LAYOUT_HORIZONTAL;
}

$form = new FormValidator('registration', 'post', '', '', [], $layoutForm);
$user_already_registered_show_terms = false;
if (api_get_setting('allow_terms_conditions') === 'true') {
    $user_already_registered_show_terms = isset($_SESSION['term_and_condition']['user_id']);
}

$sessionPremiumChecker = Session::read('SessionIsPremium');
$sessionId = Session::read('sessionId');

// Direct Link Session Subscription feature #12220
$sessionRedirect = isset($_REQUEST['s']) && !empty($_REQUEST['s']) ? $_REQUEST['s'] : null;
$onlyOneCourseSessionRedirect = isset($_REQUEST['cr']) && !empty($_REQUEST['cr']) ? $_REQUEST['cr'] : null;

if (api_get_configuration_value('allow_redirect_to_session_after_inscription_about')) {
    if (!empty($sessionRedirect)) {
        Session::write('session_redirect', $sessionRedirect);
        Session::write('only_one_course_session_redirect', $onlyOneCourseSessionRedirect);
    }
}

// Direct Link Subscription feature #5299
$course_code_redirect = isset($_REQUEST['c']) && !empty($_REQUEST['c']) ? $_REQUEST['c'] : null;
$exercise_redirect = isset($_REQUEST['e']) && !empty($_REQUEST['e']) ? $_REQUEST['e'] : null;

if (!empty($course_code_redirect)) {
    Session::write('course_redirect', $course_code_redirect);
    Session::write('exercise_redirect', $exercise_redirect);
}

if ($user_already_registered_show_terms === false &&
    api_get_setting('allow_registration') !== 'false'
) {
    // STUDENT/TEACHER
    if (api_get_setting('allow_registration_as_teacher') !== 'false') {
        if (in_array('status', $allowedFields)) {
            $form->addRadio(
                'status',
                get_lang('RegistrationRoleWhatDoYouWantToDo'),
                [
                    STUDENT => '<p class="caption">'.get_lang('RegistrationRoleFollowCourses').'</p>',
                    COURSEMANAGER => '<p class="caption">'.get_lang('RegistrationRoleTeachCourses').'</p>',
                ],
                ['class' => 'register-profile']
            );
            $form->addRule('status', get_lang('ThisFieldIsRequired'), 'required');
        }
    }
    $LastnameLabel = get_lang('LastName');
    if (api_get_configuration_value('registration_add_helptext_for_2_names') == true) {
        $LastnameLabel = [$LastnameLabel, get_lang('InsertTwoNames')];
    }
    if (api_is_western_name_order()) {
        // FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40]);
        $form->addElement('text', 'lastname', $LastnameLabel, ['size' => 40]);
    } else {
        // LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname', $LastnameLabel, ['size' => 40]);
        $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40]);
    }
    $form->applyFilter(['lastname', 'firstname'], 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

    // EMAIL
    $form->addElement('text', 'email', get_lang('Email'), ['size' => 40]);
    if (api_get_setting('registration', 'email') === 'true') {
        $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    }

    if (api_get_setting('login_is_email') === 'true') {
        $form->applyFilter('email', 'trim');
        if (api_get_setting('registration', 'email') != 'true') {
            $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
        }
        $form->addRule(
            'email',
            sprintf(
                get_lang('UsernameMaxXCharacters'),
                (string) USERNAME_MAX_LENGTH
            ),
            'maxlength',
            USERNAME_MAX_LENGTH
        );
        $form->addRule('email', get_lang('UserTaken'), 'username_available');
    }

    $form->addRule('email', get_lang('EmailWrong'), 'email');
    if (api_get_setting('openid_authentication') === 'true') {
        $form->addElement('text', 'openid', get_lang('OpenIDURL'), ['size' => 40]);
    }

    // USERNAME
    if (api_get_setting('login_is_email') != 'true') {
        $form->addText(
            'username',
            get_lang('UserName'),
            true,
            [
                'id' => 'username',
                'size' => USERNAME_MAX_LENGTH,
                'autocomplete' => 'off',
            ]
        );
        $form->applyFilter('username', 'trim');
        $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule(
            'username',
            sprintf(
                get_lang('UsernameMaxXCharacters'),
                (string) USERNAME_MAX_LENGTH
            ),
            'maxlength',
            USERNAME_MAX_LENGTH
        );
        $form->addRule('username', get_lang('UsernameWrong'), 'username');
        $form->addRule('username', get_lang('UserTaken'), 'username_available');
    }

    // PASSWORD
    $form->addElement(
        'password',
        'pass1',
        get_lang('Pass'),
        ['id' => 'pass1', 'size' => 20, 'autocomplete' => 'off', 'show_hide' => true]
    );

    $form->addElement(
        'password',
        'pass2',
        get_lang('Confirmation'),
        ['id' => 'pass2', 'size' => 20, 'autocomplete' => 'off']
    );
    $form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(['pass1', 'pass2'], get_lang('PassTwo'), 'compare');
    $form->addPasswordRule('pass1');

    // PHONE
    if (in_array('phone', $allowedFields)) {
        $form->addElement(
            'text',
            'phone',
            get_lang('Phone'),
            ['size' => 20]
        );
        if (api_get_setting('registration', 'phone') == 'true') {
            $form->addRule(
                'phone',
                get_lang('ThisFieldIsRequired'),
                'required'
            );
        }
    }

    // Language
    if (in_array('language', $allowedFields)) {
        if (api_get_setting('registration', 'language') == 'true') {
            $form->addSelectLanguage(
                'language',
                get_lang('Language')
            );
        }
    }

    // OFFICIAL CODE
    if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
        if (in_array('official_code', $allowedFields)) {
            $form->addElement(
                'text',
                'official_code',
                get_lang('OfficialCode'),
                ['size' => 40]
            );
            if (api_get_setting('registration', 'officialcode') == 'true') {
                $form->addRule(
                    'official_code',
                    get_lang('ThisFieldIsRequired'),
                    'required'
                );
            }
        }
    }

    // EXTENDED FIELDS

    //    MY PERSONAL OPEN AREA
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true'
    ) {
        $form->addHtmlEditor(
            'openarea',
            get_lang('MyPersonalOpenArea'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }
    //    MY COMPETENCES
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true'
    ) {
        $form->addHtmlEditor(
            'competences',
            get_lang('MyCompetences'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }
    //    MY DIPLOMAS
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true'
    ) {
        $form->addHtmlEditor(
            'diplomas',
            get_lang('MyDiplomas'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }
    // WHAT I AM ABLE TO TEACH
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'myteach') == 'true'
    ) {
        $form->addHtmlEditor(
            'teach',
            get_lang('MyTeach'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if (api_get_setting('extended_profile') === 'true') {
        //    MY PERSONAL OPEN AREA
        if (api_get_setting('extendedprofile_registration', 'mypersonalopenarea') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'mypersonalopenarea') === 'true'
        ) {
            $form->addRule('openarea', get_lang('ThisFieldIsRequired'), 'required');
        }
        //    MY COMPETENCES
        if (api_get_setting('extendedprofile_registration', 'mycomptetences') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'mycomptetences') === 'true'
        ) {
            $form->addRule('competences', get_lang('ThisFieldIsRequired'), 'required');
        }
        //    MY DIPLOMAS
        if (api_get_setting('extendedprofile_registration', 'mydiplomas') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'mydiplomas') === 'true'
        ) {
            $form->addRule('diplomas', get_lang('ThisFieldIsRequired'), 'required');
        }
        // WHAT I AM ABLE TO TEACH
        if (api_get_setting('extendedprofile_registration', 'myteach') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'myteach') === 'true'
        ) {
            $form->addRule('teach', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    // EXTRA FIELDS
    if (array_key_exists('extra_fields', $allowedFields) ||
        in_array('extra_fields', $allowedFields)
    ) {
        $extraField = new ExtraField('user');
        $extraFieldList = [];
        if (isset($allowedFields['extra_fields']) && is_array($allowedFields['extra_fields'])) {
            $extraFieldList = $allowedFields['extra_fields'];
        }
        $requiredFields = api_get_configuration_value('required_extra_fields_in_inscription');

        if (!empty($requiredFields) && $requiredFields['options']) {
            $requiredFields = $requiredFields['options'];
        }

        $returnParams = $extraField->addElements(
            $form,
            0,
            [],
            false,
            false,
            $extraFieldList,
            [],
            false,
            false,
            false,
            [],
            [],
            [],
            false,
            $requiredFields
        );
        $extraFieldsLoaded = true;
    }

    // CAPTCHA
    $captcha = api_get_setting('allow_captcha');
    $allowCaptcha = $captcha === 'true';

    if ($allowCaptcha) {
        $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
        $options = [
            'width' => 220,
            'height' => 90,
            'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
            'sessionVar' => basename(__FILE__, '.php'),
            'imageOptions' => [
                'font_size' => 20,
                'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
                'font_file' => 'OpenSans-Regular.ttf',
                //'output' => 'gif'
            ],
        ];
        $form->setLayout('inline');
        $captcha_question = $form->addElement(
            'CAPTCHA_Image',
            'captcha_question',
            '',
            $options
        );
        $form->addElement('static', null, null, get_lang('ClickOnTheImageForANewOne'));

        $form->addElement(
            'text',
            'captcha',
            get_lang('EnterTheLettersYouSee'),
            ['size' => 40]
        );
        $form->addRule(
            'captcha',
            get_lang('EnterTheCharactersYouReadInTheImage'),
            'required',
            null,
            'client'
        );
        $form->addRule(
            'captcha',
            get_lang('TheTextYouEnteredDoesNotMatchThePicture'),
            'CAPTCHA',
            $captcha_question
        );
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

if (api_get_setting('openid_authentication') === 'true' && !empty($_GET['openid'])) {
    $defaults['openid'] = Security::remove_XSS($_GET['openid']);
}

$defaults['status'] = STUDENT;
$defaults['extra_mail_notify_invitation'] = 1;
$defaults['extra_mail_notify_message'] = 1;
$defaults['extra_mail_notify_group_message'] = 1;

$form->applyFilter('__ALL__', 'Security::remove_XSS');
$form->setDefaults($defaults);
$content = null;

$tool_name = get_lang('Registration');

// Load terms & conditions from the current lang
if (api_get_setting('allow_terms_conditions') === 'true') {
    $get = array_keys($_GET);
    if (isset($get)) {
        if (isset($get[0]) && $get[0] === 'legal') {
            $language = api_get_language_id(api_get_interface_language());
            $term_preview = LegalManager::get_last_condition($language);
            if (!$term_preview) {
                //look for the default language
                $language = api_get_setting('platformLanguage');
                $language = api_get_language_id($language);
                $term_preview = LegalManager::get_last_condition($language);
            }
            Display::display_header(get_lang('TermsAndConditions'));
            if (!empty($term_preview['content'])) {
                echo $term_preview['content'];

                $termExtraFields = new ExtraFieldValue('terms_and_condition');
                $values = $termExtraFields->getAllValuesByItem($term_preview['id']);
                foreach ($values as $value) {
                    if (!empty($value['value'])) {
                        echo '<h3>'.get_lang($value['display_text']).'</h3><br />'.$value['value'].'<br />';
                    }
                }
            } else {
                echo get_lang('ComingSoon');
            }
            Display::display_footer();
            exit;
        }
    }
}

if (api_get_setting('allow_terms_conditions') === 'true' && $user_already_registered_show_terms) {
    $tool_name = get_lang('TermsAndConditions');
}

$home = api_get_path(SYS_HOME_PATH);
if (api_is_multiple_url_enabled()) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = api_replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';
        $home_old = api_get_path(SYS_HOME_PATH);
        $home = api_get_path(SYS_HOME_PATH).$clean_url;
    }
}

if (file_exists($home.'register_top_'.$user_selected_language.'.html')) {
    $home_top_temp = @(string) file_get_contents($home.'register_top_'.$user_selected_language.'.html');
    $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    if (!empty($open)) {
        $content = '<div class="well well-sm help-registration">'.$open.'</div>';
    }
}

// Forbidden to self-register
if ($isNotAllowedHere) {
    api_not_allowed(true, get_lang('RegistrationDisabled'));
}

if (api_get_setting('allow_registration') === 'approval') {
    $content .= Display::return_message(get_lang('YourAccountHasToBeApproved'));
}

//if openid was not found
if (!empty($_GET['openid_msg']) && $_GET['openid_msg'] == 'idnotfound') {
    $content .= Display::return_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));
}

$showTerms = false;
// Terms and conditions
if (api_get_setting('allow_terms_conditions') === 'true' && $user_already_registered_show_terms) {
    if (!api_is_platform_admin()) {
        if (api_get_setting('show_terms_if_profile_completed') === 'true') {
            $userInfo = api_get_user_info();
            if ($userInfo && $userInfo['status'] != ANONYMOUS) {
                if ((int) $userInfo['profile_completed'] !== 1) {
                    api_not_allowed(true);
                }
            }
        }
    }

    // Get user language ignoring the platform language
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
    $form->addHidden('legal_accept_type', $term_preview['version'].':'.$term_preview['language_id']);
    $form->addHidden('legal_info', $term_preview['id'].':'.$term_preview['language_id']);

    // Show if only HTML type
    if ($term_preview['type'] == '2') {
        $termExtraFields = new ExtraFieldValue('terms_and_condition');
        $values = $termExtraFields->getAllValuesByItem($term_preview['id']);
        foreach ($values as $value) {
            //if ($value['variable'] === 'category') {
            $form->addLabel($value['display_text'], $value['value']);
            //}
        }
    }

    if ($term_preview['type'] == 1) {
        $form->addElement(
            'checkbox',
            'legal_accept',
            null,
            get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.get_lang(
                'TermsAndConditions'
            ).'</a>'
        );
        $form->addRule('legal_accept', get_lang('WeNeedYouToAcceptOurTreatmentOfYourData'), 'required');
    } else {
        $preview = LegalManager::show_last_condition($term_preview);
        $form->addElement('label', null, $preview);
    }
    $showTerms = true;
}

$allowDoubleValidation = api_get_configuration_value('allow_double_validation_in_registration');

$formContainsSendButton = false;
if ($allowDoubleValidation && $showTerms == false) {
    $htmlHeadXtra[] = '<script>
        $(function() {
            $("#pre_validation").click(function() {
                $(this).hide();
                $("#final_button").show();
            });
        });
    </script>';

    $form->addLabel(
        null,
        Display::url(
            get_lang('Ok'),
            'javascript:void',
            ['class' => 'btn btn-default', 'id' => 'pre_validation']
        )
    );
    $form->addHtml('<div id="final_button" style="display: none">');
    $form->addLabel(
        null,
        Display::return_message(get_lang('DoubleValidationMessage'), 'info', false)
    );
    $form->addButton('submit', get_lang('RegisterUser'), '', 'primary');
    $form->addHtml('</div>');
    $formContainsSendButton = true;
} else {
    // In normal cases (without double validation), we check if the
    // registration is allowed in any way or if the user is already registered
    // but needs to confirm terms. If not, send not allowed message
    if (
        api_get_setting('allow_registration') === 'approval' ||
        api_get_setting('allow_registration') === 'true' ||
        api_get_setting('allow_registration') === 'confirmation' ||
        $user_already_registered_show_terms ||
        $showTerms
    ) {
        $form->addButton('register', get_lang('RegisterUser'), null, 'primary', 'btn-block');
        $formContainsSendButton = true;
    }
}

// Blocks page because there's any action to do.
if (!$formContainsSendButton) {
    api_not_allowed(true);
}

$course_code_redirect = Session::read('course_redirect');
$sessionToRedirect = Session::read('session_redirect');

if ($extraConditions && $extraFieldsLoaded) {
    // Set conditions as "required" and also change the labels
    foreach ($extraConditions as $condition) {
        /** @var HTML_QuickForm_group $element */
        $element = $form->getElement('extra_'.$condition['variable']);
        if ($element) {
            $children = $element->getElements();
            /** @var HTML_QuickForm_checkbox $child */
            foreach ($children as $child) {
                $child->setText(get_lang($condition['display_text']));
            }
            $form->setRequired($element);
            if (!empty($condition['text_area'])) {
                $element->setLabel(
                    [
                        '',
                        '<div class="form-control" disabled=disabled style="height: 100px; overflow: auto;">'.
                        get_lang(nl2br($condition['text_area'])).
                        '</div>',
                    ]
                );
            }
        }
    }
}

if ($form->validate()) {
    $values = $form->getSubmitValues(1);
    // Make *sure* the login isn't too long
    if (isset($values['username'])) {
        $values['username'] = api_substr($values['username'], 0, USERNAME_MAX_LENGTH);
    }

    if (api_get_setting('allow_registration_as_teacher') === 'false') {
        $values['status'] = STUDENT;
    }

    if (empty($values['official_code']) && !empty($values['username'])) {
        $values['official_code'] = api_strtoupper($values['username']);
    }

    if (api_get_setting('login_is_email') === 'true') {
        $values['username'] = $values['email'];
    }

    if ($user_already_registered_show_terms &&
        api_get_setting('allow_terms_conditions') === 'true'
    ) {
        $user_id = $_SESSION['term_and_condition']['user_id'];
        $is_admin = UserManager::is_admin($user_id);
        Session::write('is_platformAdmin', $is_admin);
    } else {
        // Moved here to include extra fields when creating a user. Formerly placed after user creation
        // Register extra fields
        $extras = [];
        foreach ($values as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') {
                //an extra field
                $extras[substr($key, 6)] = $value;
            } elseif (strpos($key, 'remove_extra_') !== false) {
                $extra_value = Security::filter_filename(urldecode(key($value)));
                // To remove from user_field_value and folder
                UserManager::update_extra_field_value(
                    $user_id,
                    substr($key, 13),
                    $extra_value
                );
            }
        }

        $status = isset($values['status']) ? $values['status'] : STUDENT;
        $phone = isset($values['phone']) ? $values['phone'] : null;
        $values['language'] = isset($values['language']) ? $values['language'] : api_get_interface_language();
        $values['address'] = isset($values['address']) ? $values['address'] : '';

        // Creates a new user
        $user_id = UserManager::create_user(
            $values['firstname'],
            $values['lastname'],
            $status,
            $values['email'],
            $values['username'],
            $values['pass1'],
            $values['official_code'],
            $values['language'],
            $phone,
            null,
            PLATFORM_AUTH_SOURCE,
            null,
            1,
            0,
            $extras,
            null,
            true,
            false,
            $values['address'],
            false,
            $form
        );

        // Update the extra fields
        $count_extra_field = count($extras);
        if ($count_extra_field > 0 && is_int($user_id)) {
            foreach ($extras as $key => $value) {
                // For array $value -> if exists key 'tmp_name' then must not be empty
                // This avoid delete from user field value table when doesn't upload a file
                if (is_array($value)) {
                    if (array_key_exists('tmp_name', $value) && empty($value['tmp_name'])) {
                        //Nothing to do
                    } else {
                        if (array_key_exists('tmp_name', $value)) {
                            $value['tmp_name'] = Security::filter_filename($value['tmp_name']);
                        }
                        if (array_key_exists('name', $value)) {
                            $value['name'] = Security::filter_filename($value['name']);
                        }
                        UserManager::update_extra_field_value($user_id, $key, $value);
                    }
                } else {
                    UserManager::update_extra_field_value($user_id, $key, $value);
                }
            }
        }

        if ($user_id) {
            // Storing the extended profile
            $store_extended = false;
            $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)." SET ";

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true'
            ) {
                $sql_set[] = "competences = '".Database::escape_string($values['competences'])."'";
                $store_extended = true;
            }

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true'
            ) {
                $sql_set[] = "diplomas = '".Database::escape_string($values['diplomas'])."'";
                $store_extended = true;
            }

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'myteach') == 'true'
            ) {
                $sql_set[] = "teach = '".Database::escape_string($values['teach'])."'";
                $store_extended = true;
            }

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true'
            ) {
                $sql_set[] = "openarea = '".Database::escape_string($values['openarea'])."'";
                $store_extended = true;
            }

            if ($store_extended) {
                $sql .= implode(',', $sql_set);
                $sql .= " WHERE user_id = ".intval($user_id)."";
                Database::query($sql);
            }

            // Saving user to Session if it was set
            if (!empty($sessionToRedirect) && !$sessionPremiumChecker) {
                $sessionInfo = api_get_session_info($sessionToRedirect);
                if (!empty($sessionInfo)) {
                    SessionManager::subscribeUsersToSession(
                        $sessionToRedirect,
                        [$user_id],
                        SESSION_VISIBLE_READ_ONLY,
                        false
                    );
                }
            }

            // Saving user to course if it was set.
            if (!empty($course_code_redirect)) {
                $course_info = api_get_course_info($course_code_redirect);
                if (!empty($course_info)) {
                    if (in_array(
                        $course_info['visibility'],
                        [
                            COURSE_VISIBILITY_OPEN_PLATFORM,
                            COURSE_VISIBILITY_OPEN_WORLD,
                        ]
                    )
                    ) {
                        CourseManager::subscribeUser(
                            $user_id,
                            $course_info['code']
                        );
                    }
                }
            }

            /* If the account has to be approved then we set the account to inactive,
            sent a mail to the platform admin and exit the page.*/
            if (api_get_setting('allow_registration') === 'approval') {
                // 1. Send mail to all platform admin
                $chamiloUser = api_get_user_entity($user_id);
                MessageManager::sendNotificationOfNewRegisteredUserApproval($chamiloUser);

                // 2. set account inactive
                UserManager::disable($user_id);

                // 3. exit the page
                unset($user_id);

                Display::display_header($tool_name);
                echo Display::page_header($tool_name);
                echo $content;
                Display::display_footer();
                exit;
            } elseif (api_get_setting('allow_registration') === 'confirmation') {
                // 1. Send mail to the user
                $thisUser = api_get_user_entity($user_id);
                UserManager::sendUserConfirmationMail($thisUser);

                // 2. set account inactive
                UserManager::disable($user_id);

                // 3. exit the page
                unset($user_id);

                Display::addFlash(
                    Display::return_message(
                        get_lang('YouNeedConfirmYourAccountViaEmailToAccessThePlatform'),
                        'warning'
                    )
                );

                Display::display_header($tool_name);
                //echo $content;
                Display::display_footer();
                exit;
            }
        }
    }

    // Terms & Conditions
    if (api_get_setting('allow_terms_conditions') === 'true') {
        // Update the terms & conditions.
        if (isset($values['legal_accept_type'])) {
            $cond_array = explode(':', $values['legal_accept_type']);
            if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                $conditionToSave = (int) $cond_array[0].':'.(int) $cond_array[1].':'.time();

                Event::addEvent(
                    LOG_TERM_CONDITION_ACCEPTED,
                    LOG_USER_OBJECT,
                    api_get_user_info($user_id),
                    api_get_utc_datetime()
                );

                LegalManager::sendEmailToUserBoss($user_id, $conditionToSave);
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
    $_user['status'] = $values['status'] ?? STUDENT;
    ConditionalLogin::check_conditions($_user);
    Session::write('_user', $_user);

    $is_allowedCreateCourse = isset($values['status']) && $values['status'] == 1;
    $usersCanCreateCourse = api_is_allowed_to_create_course();

    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

    // Stats
    Event::eventLogin($user_id);

    // last user login date is now
    $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
    Session::write('user_last_login_datetime', $user_last_login_datetime);
    $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);
    $text_after_registration =
        '<p>'.
        get_lang('Dear').' '.
        stripslashes(Security::remove_XSS($recipient_name)).',<br /><br />'.
        get_lang('PersonalSettings').".</p>";

    $form_data = [
        'button' => Display::button(
            'next',
            get_lang('Next'),
            ['class' => 'btn btn-primary btn-large']
        ),
        'message' => '',
        'action' => api_get_path(WEB_PATH).'user_portal.php',
        'go_button' => '',
    ];

    if (api_get_setting('allow_terms_conditions') === 'true' && $user_already_registered_show_terms) {
        if (api_get_setting('load_term_conditions_section') === 'login') {
            $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
        } else {
            $courseInfo = api_get_course_info();
            if (!empty($courseInfo)) {
                $form_data['action'] = $courseInfo['course_public_url'].'?id_session='.api_get_session_id();
                $cidReset = true;
                Session::erase('_course');
                Session::erase('_cid');
            } else {
                $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
            }
        }
    } else {
        if (!empty($values['email'])) {
            $text_after_registration .= '<p>'.get_lang('MailHasBeenSent').'.</p>';
        }

        if ($is_allowedCreateCourse) {
            if ($usersCanCreateCourse) {
                $form_data['message'] = '<p>'.get_lang('NowGoCreateYourCourse').'</p>';
            }
            $form_data['action'] = api_get_path(WEB_CODE_PATH).'create_course/add_course.php';

            if (api_get_setting('course_validation') === 'true') {
                $form_data['button'] = Display::button(
                    'next',
                    get_lang('CreateCourseRequest'),
                    ['class' => 'btn btn-primary btn-large']
                );
            } else {
                $form_data['button'] = Display::button(
                    'next',
                    get_lang('CourseCreate'),
                    ['class' => 'btn btn-primary btn-large']
                );
                $form_data['go_button'] = '&nbsp;&nbsp;<a href="'.api_get_path(WEB_PATH).'index.php'.'">'.
                    Display::span(
                        get_lang('Next'),
                        ['class' => 'btn btn-primary btn-large']
                    ).'</a>';
            }
        } else {
            if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                $form_data['action'] = 'courses.php?action=subscribe';
                $form_data['message'] = '<p>'.get_lang('NowGoChooseYourCourses').".</p>";
            } else {
                $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
            }
            $form_data['button'] = Display::button(
                'next',
                get_lang('Next'),
                ['class' => 'btn btn-primary btn-large']
            );
        }
    }

    if ($sessionPremiumChecker && $sessionId) {
        $url = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process.php?i='.$sessionId.'&t=2';
        Session::erase('SessionIsPremium');
        Session::erase('sessionId');
        header('Location:'.$url);
        exit;
    }

    SessionManager::redirectToSession();

    $redirectBuyCourse = Session::read('buy_course_redirect');
    if (!empty($redirectBuyCourse)) {
        $form_data['action'] = api_get_path(WEB_PATH).$redirectBuyCourse;
        Session::erase('buy_course_redirect');
    }

    $form_data = CourseManager::redirectToCourse($form_data);
    $form_register = new FormValidator('form_register', 'post', $form_data['action']);
    if (!empty($form_data['message'])) {
        $form_register->addElement('html', $form_data['message'].'<br /><br />');
    }

    if ($usersCanCreateCourse) {
        $form_register->addElement('html', $form_data['button']);
    } else {
        if (!empty($redirectBuyCourse)) {
            $form_register->addButtonNext(get_lang('Next'));
        } else {
            $form_register->addElement('html', $form_data['go_button']);
        }
    }

    $text_after_registration .= $form_register->returnForm();

    // Just in case
    Session::erase('course_redirect');
    Session::erase('exercise_redirect');
    Session::erase('session_redirect');
    Session::erase('only_one_course_session_redirect');

    if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION_FEEDBACK)) {
        CustomPages::display(
            CustomPages::REGISTRATION_FEEDBACK,
            ['info' => $text_after_registration]
        );
    } else {
        $tpl = new Template($tool_name);
        $tpl->assign('inscription_content', $content);
        $tpl->assign('text_after_registration', $text_after_registration);
        $tpl->assign('hide_header', $hideHeaders);
        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
} else {
    // Custom pages
    if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
        CustomPages::display(
            CustomPages::REGISTRATION,
            ['form' => $form, 'content' => $content]
        );
    } else {
        if (!api_is_anonymous()) {
            // Saving user to course if it was set.
            if (!empty($course_code_redirect)) {
                $course_info = api_get_course_info($course_code_redirect);
                if (!empty($course_info)) {
                    if (in_array(
                        $course_info['visibility'],
                        [
                            COURSE_VISIBILITY_OPEN_PLATFORM,
                            COURSE_VISIBILITY_OPEN_WORLD,
                        ]
                    )
                    ) {
                        CourseManager::subscribeUser(
                            $user_id,
                            $course_info['code']
                        );
                    }
                }
            }
            CourseManager::redirectToCourse([]);
        }

        $tpl = new Template($tool_name);

        $tpl->assign('inscription_header', Display::page_header($tool_name));
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', $form->returnForm());
        $tpl->assign('hide_header', $hideHeaders);

        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
}
