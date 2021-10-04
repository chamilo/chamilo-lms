<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;
require_once __DIR__.'/../inc/global.inc.php';

/**
 * This script displays a form for registering new users.
 */

//quick hack to adapt the registration form result to the selected registration language
if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}

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
if (false !== $allowedFieldsConfiguration) {
    $allowedFields = isset($allowedFieldsConfiguration['fields']) ? $allowedFieldsConfiguration['fields'] : [];
    $allowedFields['extra_fields'] = isset($allowedFieldsConfiguration['extra_fields']) ? $allowedFieldsConfiguration['extra_fields'] : [];
}

$webserviceUrl = api_get_plugin_setting('logintcc', 'webservice_url');
$hash = api_get_plugin_setting('logintcc', 'hash');

if (!empty($webserviceUrl)) {
    $htmlHeadXtra[] = '<script>
    $(document).ready(function() {
        $("#search_user").click(function() {

            var data = new Object();
            data.Mail = $("input[name=\'email\']").val();
            data.HashKey = "'.$hash.'";

            $.ajax({
                url: "'.$webserviceUrl.'/IsExistEmail",
                data: JSON.stringify(data),
                dataType: "json",
                type: "POST",
                contentType: "application/json; charset=utf-8",
                success: function (data, status) {
                    if (data.d.Exist) {
                        var monU = data.d.User;
                        $("input[name=\'extra_tcc_user_id\']").val(monU.UserID);
                        $("input[name=\'extra_tcc_hash_key\']").val(monU.HashKey);
                        var $radios = $("input:radio[name=\'extra_terms_genre[extra_terms_genre]\']");
                        if (monU.Genre == "Masculin") {
                            $radios.filter(\'[value=homme]\').prop(\'checked\', true);
                        } else {
                            $radios.filter(\'[value=femme]\').prop(\'checked\', true);
                        }
                        $("input[name=\'lastname\']").val(monU.Nom);
                        $("input[name=\'firstname\']").val(monU.Prenom);

                        var date = monU.DateNaissance; // 30/06/1986
                        if (date != "") {
                            var parts = date.split(\'/\');
                            $("#extra_terms_datedenaissance").datepicker("setDate", new Date(parts[2], parts[1], parts[0]));
                        }

                        if (monU.Langue == "fr-FR") {
                            $("#language").selectpicker("val", "french");
                            $("#language").selectpicker(\'render\');
                        }

                        if (monU.Langue == "de-DE") {
                            $("#language").selectpicker("val", "german");
                            $("#language").selectpicker(\'render\');
                        }

                        $("input[name=\'extra_terms_nationalite\']").val(monU.Nationalite);
                        $("input[name=\'extra_terms_paysresidence\']").val(monU.PaysResidence);
                        $("input[name=\'extra_terms_adresse\']").val(monU.Adresse);
                        $("input[name=\'extra_terms_codepostal\']").val(monU.CP);
                        $("input[name=\'extra_terms_ville\']").val(monU.Ville);
                    } else {
                        alert("'.get_lang("UnknownUser").'");
                    }

                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(textStatus);
                }
            });

            return false;
        });
    });
    </script>';
}

$extraFieldsLoaded = false;
$htmlHeadXtra[] = api_get_password_checker_js('#username', '#pass1');
// User is not allowed if Terms and Conditions are disabled and
// registration is disabled too.
$isNotAllowedHere = 'false' === api_get_setting('allow_terms_conditions') &&
    'false' === api_get_setting('allow_registration');

if ($isNotAllowedHere) {
    api_not_allowed(true, get_lang('Sorry, you are trying to access the registration page for this portal, but registration is currently disabled. Please contact the administrator (see contact information in the footer). If you already have an account on this site.'));
}

$extraConditions = api_get_configuration_value('show_conditions_to_user');
if ($extraConditions && isset($extraConditions['conditions'])) {
    // Create user extra fields for the conditions
    $userExtraField = new ExtraField('user');
    $extraConditions = $extraConditions['conditions'];
    foreach ($extraConditions as $condition) {
        $exists = $userExtraField->get_handler_field_info_by_field_variable($condition['variable']);
        if (false == $exists) {
            $params = [
                'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'variable' => $condition['variable'],
                'display_text' => $condition['display_text'],
                'default_value' => '',
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 0,
                'filter' => 0,
            ];
            $userExtraField->save($params);
        }
    }
}

$form = new FormValidator('registration');
$user_already_registered_show_terms = false;
$termRegistered = Session::read('term_and_condition');
if ('true' === api_get_setting('allow_terms_conditions')) {
    $user_already_registered_show_terms = isset($termRegistered['user_id']);
    // Ofaj change
    if (true === api_is_anonymous()) {
        $user_already_registered_show_terms = false;
    }
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

if (false === $user_already_registered_show_terms &&
    'false' !== api_get_setting('allow_registration')
) {
    // EMAIL
    $form->addElement('text', 'email', get_lang('e-mail'), ['size' => 40]);
    if ('true' === api_get_setting('registration', 'email')) {
        $form->addRule('email', get_lang('Required field'), 'required');
    }

    if (!empty($webserviceUrl)) {
        $form->addButtonSearch(get_lang('SearchTCC'), 'search', ['id' => 'search_user']);
    }

    // STUDENT/TEACHER
    if ('false' != api_get_setting('allow_registration_as_teacher')) {
        if (in_array('status', $allowedFields)) {
            $form->addRadio(
                'status',
                get_lang('What do you want to do?'),
                [
                    STUDENT => '<p class="caption">'.get_lang('Follow courses').'</p>',
                    COURSEMANAGER => '<p class="caption">'.get_lang('Teach courses').'</p>',
                ],
                ['class' => 'register-profile']
            );
            $form->addRule('status', get_lang('Required field'), 'required');
        }
    }

    $LastnameLabel = get_lang('LastName');
    if (true == api_get_configuration_value('registration_add_helptext_for_2_names')) {
        $LastnameLabel = [$LastnameLabel, get_lang('InsertTwoNames')];
    }
    if (api_is_western_name_order()) {
        // FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
        $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
    } else {
        // LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
        $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
    }
    $form->applyFilter(['lastname', 'firstname'], 'trim');
    $form->addRule('lastname', get_lang('Required field'), 'required');
    $form->addRule('firstname', get_lang('Required field'), 'required');

    if ('true' === api_get_setting('login_is_email')) {
        $form->applyFilter('email', 'trim');
        if ('true' != api_get_setting('registration', 'email')) {
            $form->addRule('email', get_lang('Required field'), 'required');
        }
        $form->addRule(
            'email',
            sprintf(
                get_lang('The login needs to be maximum %s characters long'),
                (string) User::USERNAME_MAX_LENGTH
            ),
            'maxlength',
            User::USERNAME_MAX_LENGTH
        );
        $form->addRule('email', get_lang('This login is already in use'), 'username_available');
    }

    $form->addEmailRule('email');

    // USERNAME
    if ('true' != api_get_setting('login_is_email')) {
        $form->addText(
            'username',
            get_lang('Username'),
            true,
            [
                'id' => 'username',
                'size' => User::USERNAME_MAX_LENGTH,
                'autocomplete' => 'off',
            ]
        );
        $form->applyFilter('username', 'trim');
        $form->addRule('username', get_lang('Required field'), 'required');
        $form->addRule(
            'username',
            sprintf(
                get_lang('The login needs to be maximum %s characters long'),
                (string) User::USERNAME_MAX_LENGTH
            ),
            'maxlength',
            User::USERNAME_MAX_LENGTH
        );
        $form->addRule('username', get_lang('Your login can only contain letters, numbers and _.-'), 'username');
        $form->addRule('username', get_lang('This login is already in use'), 'username_available');
    }

    $passDiv = '<div id="password_progress"></div><div id="password-verdict"></div><div id="password-errors"></div>';

    $checkPass = api_get_setting('allow_strength_pass_checker');
    if ($checkPass === 'true') {
        $checkPass = '';
    }

    // PASSWORD
    $form->addElement(
        'password',
        'pass1',
        [get_lang('Pass'), $passDiv],
        ['id' => 'pass1', 'size' => 20, 'autocomplete' => 'off']
    );

    $checkPass = api_get_setting('allow_strength_pass_checker');

    $form->addElement(
        'password',
        'pass2',
        get_lang('Confirm password'),
        ['id' => 'pass2', 'size' => 20, 'autocomplete' => 'off']
    );
    $form->addRule('pass1', get_lang('Required field'), 'required');
    $form->addRule('pass2', get_lang('Required field'), 'required');
    $form->addRule(['pass1', 'pass2'], get_lang('You have typed two different passwords'), 'compare');
    $form->addPasswordRule('pass1');

    if ($checkPass) {
        $form->addRule(
            'pass1',
            get_lang('PassTooEasy').': '.api_generate_password(),
            'callback',
            'api_check_password'
        );
    }

    // PHONE
    if (in_array('phone', $allowedFields)) {
        $form->addElement(
            'text',
            'phone',
            get_lang('Phone'),
            ['size' => 20]
        );
        if ('true' === api_get_setting('registration', 'phone')) {
            $form->addRule(
                'phone',
                get_lang('Required field'),
                'required'
            );
        }
    }

    // Language
    if (in_array('language', $allowedFields)) {
        if ('true' === api_get_setting('registration', 'language')) {
            $form->addSelectLanguage(
                'language',
                get_lang('Language'),
                [],
                ['id' => 'language']
            );
        }
    }

    if (in_array('official_code', $allowedFields)) {
        $form->addElement(
            'text',
            'official_code',
            get_lang('Code'),
            ['size' => 40]
        );
        if ('true' == api_get_setting('registration', 'officialcode')) {
            $form->addRule(
                'official_code',
                get_lang('Required field'),
                'required'
            );
        }
    }

    // STUDENT/TEACHER
    if (api_get_setting('allow_registration_as_teacher') != 'false') {
        if (in_array('status', $allowedFields)) {
            $form->addElement(
                'radio',
                'status',
                get_lang('Profile'),
                get_lang('RegStudent'),
                STUDENT
            );
            $form->addElement(
                'radio',
                'status',
                null,
                get_lang('RegAdmin'),
                COURSEMANAGER
            );
        }
    }

    $captcha = api_get_setting('allow_captcha');
    $allowCaptcha = $captcha === 'true';

    // EXTENDED FIELDS
    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'mycomptetences')
    ) {
        $form->addHtmlEditor(
            'competences',
            get_lang('My competences'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'mydiplomas')
    ) {
        $form->addHtmlEditor(
            'diplomas',
            get_lang('My diplomas'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'myteach')
    ) {
        $form->addHtmlEditor(
            'teach',
            get_lang('What I am able to teach'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'mypersonalopenarea')
    ) {
        $form->addHtmlEditor(
            'openarea',
            get_lang('My personal open area'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile')) {
        if ('true' === api_get_setting('extendedprofile_registration', 'mycomptetences') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'mycomptetences')
        ) {
            $form->addRule('competences', get_lang('Required field'), 'required');
        }
        if ('true' === api_get_setting('extendedprofile_registration', 'mydiplomas') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'mydiplomas')
        ) {
            $form->addRule('diplomas', get_lang('Required field'), 'required');
        }
        if ('true' === api_get_setting('extendedprofile_registration', 'myteach') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'myteach')
        ) {
            $form->addRule('teach', get_lang('Required field'), 'required');
        }
        if ('true' === api_get_setting('extendedprofile_registration', 'mypersonalopenarea') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'mypersonalopenarea')
        ) {
            $form->addRule('openarea', get_lang('Required field'), 'required');
        }
    }

    $form->addElement(
        'hidden',
        'extra_tcc_user_id'
    );

    $form->addElement(
        'hidden',
        'extra_tcc_hash_key'
    );

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
            [],
            $requiredFields,
            true
        );
        $extraFieldsLoaded = true;
    }

    // CAPTCHA
    $captcha = api_get_setting('allow_captcha');
    $allowCaptcha = 'true' === $captcha;

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

        $captcha_question = $form->addElement(
            'CAPTCHA_Image',
            'captcha_question',
            '',
            $options
        );
        $form->addElement('static', null, null, get_lang('Click on the image to load a new one.'));

        $form->addElement(
            'text',
            'captcha',
            get_lang('Enter the letters you see.'),
            ['size' => 40]
        );
        $form->addRule(
            'captcha',
            get_lang('Enter the characters you see on the image'),
            'required',
            null,
            'client'
        );
        $form->addRule(
            'captcha',
            get_lang('The text you entered doesn\'t match the picture.'),
            'CAPTCHA',
            $captcha_question
        );
    }
}

if (isset($_SESSION['user_language_choice']) && '' != $_SESSION['user_language_choice']) {
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

if ('true' === api_get_setting('openid_authentication') && !empty($_GET['openid'])) {
    $defaults['openid'] = Security::remove_XSS($_GET['openid']);
}

$defaults['status'] = STUDENT;
$defaults['extra_mail_notify_invitation'] = 1;
$defaults['extra_mail_notify_message'] = 1;
$defaults['extra_mail_notify_group_message'] = 1;

$form->applyFilter('__ALL__', 'Security::remove_XSS');
$form->setDefaults($defaults);
$content = null;

$_user['language'] = 'french';
$userInfo = api_get_user_info();
if (!empty($userInfo)) {
    $langInfo = api_get_language_from_iso($userInfo['language']);
    $_user['language'] = $langInfo->getEnglishName();
}

$tool_name = get_lang('Registration');
if (!CustomPages::enabled()) {
// Load terms & conditions from the current lang
    if ('true' === api_get_setting('allow_terms_conditions')) {
        $get = array_keys($_GET);
        if (isset($get)) {
            if (isset($get[0]) && 'legal' == $get[0]) {
                $language = api_get_language_isocode();
                $language = api_get_language_id($language);
                $term_preview = LegalManager::get_last_condition($language);
                if (!$term_preview) {
                    //look for the default language
                    $language = api_get_setting('platformLanguage');
                    $language = api_get_language_id($language);
                    $term_preview = LegalManager::get_last_condition($language);
                }

                Display::display_header(get_lang('Terms and Conditions'));
                if (!empty($term_preview['content'])) {
                    echo $term_preview['content'];

                    $termExtraFields = new ExtraFieldValue('terms_and_condition');
                    $values = $termExtraFields->getAllValuesByItem($term_preview['id']);
                    foreach ($values as $value) {
                        echo '<h3>'.$value['display_text'].'</h3><br />'.$value['value'].'<br />';
                    }
                } else {
                    echo get_lang('Coming soon...');
                }
                Display::display_footer();
                exit;
            }
        }
    }

    $tool_name = get_lang('Registration');

    if ('true' === api_get_setting('allow_terms_conditions') && $user_already_registered_show_terms) {
        $tool_name = get_lang('Terms and Conditions');
    }

// Forbidden to self-register
    if ($isNotAllowedHere) {
        api_not_allowed(
            true,
            get_lang(
                'Sorry, you are trying to access the registration page for this portal, but registration is currently disabled. Please contact the administrator (see contact information in the footer). If you already have an account on this site.'
            )
        );
    }

    if ('approval' === api_get_setting('allow_registration')) {
        $content .= Display::return_message(get_lang('Your account has to be approved'));
    }

    //if openid was not found
    if (!empty($_GET['openid_msg']) && 'idnotfound' == $_GET['openid_msg']) {
        $content .= Display::return_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));
    }
}

$blockButton = false;
$termActivated = false;
$showTerms = false;
// Terms and conditions
if ('true' === api_get_setting('allow_terms_conditions')) {
    if (!api_is_platform_admin()) {
        if ('true' === api_get_setting('ticket.show_terms_if_profile_completed')) {
            $userInfo = api_get_user_info(api_get_user_id());
            if ($userInfo && ANONYMOUS != $userInfo['status']) {
                $extraFieldValue = new ExtraFieldValue('user');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    api_get_user_id(),
                    'termactivated'
                );
                if (isset($value['value'])) {
                    $termActivated = !empty($value['value']) && 1 === (int) $value['value'];
                }

                if (false === $termActivated) {
                    $blockButton = true;
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Term activated is needed description'),
                            'warning',
                            false
                        )
                    );
                }

                if (false === $blockButton) {
                    if (1 !== (int) $userInfo['profile_completed']) {
                        $blockButton = true;
                        Display::addFlash(
                            Display::return_message(
                                get_lang('Term your profile is not completed'),
                                'warning',
                                false
                            )
                        );
                    }
                }
            }
        }
    }

    // Ofaj
    if (!api_is_anonymous()) {
        $language = api_get_language_isocode();
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

        // ofaj
        if ($termActivated !== false) {
            // Version and language
            $form->addElement(
                'hidden',
                'legal_accept_type',
                $term_preview['version'].':'.$term_preview['language_id']
            );
            $form->addElement(
                'hidden',
                'legal_info',
                $term_preview['id'].':'.$term_preview['language_id']
            );
            if ($term_preview['type'] == 1) {
                $form->addElement(
                    'checkbox',
                    'legal_accept',
                    null,
                    get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.
                    get_lang('TermsAndConditions').'</a>'
                );
                $form->addRule(
                    'legal_accept',
                    get_lang('ThisFieldIsRequired'),
                    'required'
                );
            } else {
                $preview = LegalManager::show_last_condition($term_preview);
                $form->addElement('label', null, $preview);

                $termExtraFields = new ExtraFieldValue('terms_and_condition');
                $values = $termExtraFields->getAllValuesByItem($term_preview['id']);
                foreach ($values as $value) {
                    //if ($value['variable'] === 'category') {
                    $form->addLabel($value['display_text'], $value['value']);
                    //}
                }
            }
        }
    }
}

if ($user_already_registered_show_terms === false) {
    $form->addCheckBox(
        'extra_platformuseconditions',
        null,
        get_lang('PlatformUseConditions')
    );
    $form->addRule(
        'extra_platformuseconditions',
        get_lang('ThisFieldIsRequired'),
        'required'
    );
}

if ($blockButton) {
    if ($termActivated !== false) {
        $form->addButton(
            'submit',
            get_lang('RegisterUserOk'),
            'check',
            'primary',
            null,
            null,
            ['disabled' => 'disabled'],
            false
        );
    }
} else {
    $allow = api_get_configuration_value('allow_double_validation_in_registration');

    if (false === $allow && $termActivated) {
        $htmlHeadXtra[] = '<script>
            $(document).ready(function() {
                $("#pre_validation").click(function() {
                    $(this).hide();
                    $("#final_button").show();
                });
            });
        </script>';

        $form->addLabel(
            null,
            Display::url(
                get_lang('Validate'),
                'javascript:void',
                ['class' => 'btn btn-default', 'id' => 'pre_validation']
            )
        );
        $form->addHtml('<div id="final_button" style="display: none">');
        $form->addLabel(
            null,
            Display::return_message(get_lang('You confirm that you really want to subscribe to this plateform.'),
                'info', false)
        );
        $form->addButton('submit', get_lang('Register'), '', 'primary');
        $form->addHtml('</div>');
    } else {
        $form->addButtonNext(get_lang('Register User'));
    }
    $showTerms = true;
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

$text_after_registration = '';
if ($form->validate()) {
    $values = $form->getSubmitValues(1);
    // Make *sure* the login isn't too long
    if (isset($values['username'])) {
        $values['username'] = api_substr($values['username'], 0, User::USERNAME_MAX_LENGTH);
    }

    if ('false' === api_get_setting('allow_registration_as_teacher')) {
        $values['status'] = STUDENT;
    }

    if (empty($values['official_code']) && !empty($values['username'])) {
        $values['official_code'] = api_strtoupper($values['username']);
    }

    if ('true' === api_get_setting('login_is_email')) {
        $values['username'] = $values['email'];
    }

    if ($user_already_registered_show_terms &&
        'true' === api_get_setting('allow_terms_conditions')
    ) {
        $user_id = $termRegistered['user_id'];
        $is_admin = UserManager::is_admin($user_id);
        Session::write('is_platformAdmin', $is_admin);
    } else {
        // Moved here to include extra fields when creating a user. Formerly placed after user creation
        // Register extra fields
        $extras = [];
        foreach ($values as $key => $value) {
            if ('extra_' == substr($key, 0, 6)) {
                //an extra field
                $extras[substr($key, 6)] = $value;
            } elseif (false !== strpos($key, 'remove_extra_')) {
                /*$extra_value = Security::filter_filename(urldecode(key($value)));
                // To remove from user_field_value and folder
                UserManager::update_extra_field_value(
                    $user_id,
                    substr($key, 13),
                    $extra_value
                );*/
            }
        }

        $status = isset($values['status']) ? $values['status'] : STUDENT;
        $phone = isset($values['phone']) ? $values['phone'] : null;
        $values['language'] = isset($values['language']) ? $values['language'] : api_get_language_isocode();
        $values['address'] = isset($values['address']) ? $values['address'] : '';

        // It gets a creator id when user is not logged
        $creatorId = 0;
        if (api_is_anonymous()) {
            $adminList = UserManager::get_all_administrators();
            $creatorId = 1;
            if (!empty($adminList)) {
                $adminInfo = current($adminList);
                $creatorId = (int) $adminInfo['user_id'];
            }
        }

        // Creates a new user
        $user_id = UserManager::create_user(
            $values['firstname'],
            $values['lastname'],
            (int) $status,
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
            true,
            $form,
            $creatorId
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

            if ('true' == api_get_setting('extended_profile') &&
                'true' == api_get_setting('extendedprofile_registration', 'mycomptetences')
            ) {
                $sql_set[] = "competences = '".Database::escape_string($values['competences'])."'";
                $store_extended = true;
            }

            if ('true' == api_get_setting('extended_profile') &&
                'true' == api_get_setting('extendedprofile_registration', 'mydiplomas')
            ) {
                $sql_set[] = "diplomas = '".Database::escape_string($values['diplomas'])."'";
                $store_extended = true;
            }

            if ('true' == api_get_setting('extended_profile') &&
                'true' == api_get_setting('extendedprofile_registration', 'myteach')
            ) {
                $sql_set[] = "teach = '".Database::escape_string($values['teach'])."'";
                $store_extended = true;
            }

            if ('true' == api_get_setting('extended_profile') &&
                'true' == api_get_setting('extendedprofile_registration', 'mypersonalopenarea')
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
                            $course_info['real_id']
                        );
                    }
                }
            }

            /* If the account has to be approved then we set the account to inactive,
            sent a mail to the platform admin and exit the page.*/
            if ('approval' === api_get_setting('allow_registration')) {
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
            } elseif ('confirmation' === api_get_setting('allow_registration')) {
                // 1. Send mail to the user
                $thisUser = api_get_user_entity($user_id);
                UserManager::sendUserConfirmationMail($thisUser);

                // 2. set account inactive
                UserManager::disable($user_id);

                // 3. exit the page
                unset($user_id);

                Display::addFlash(
                    Display::return_message(
                        get_lang('You need confirm your accountViae - mail to access the platform'),
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
    if ('true' === api_get_setting('allow_terms_conditions')) {
        // Update the terms & conditions.
        if (isset($values['legal_accept_type'])) {
            $cond_array = explode(':', $values['legal_accept_type']);
            if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                $time = time();
                $conditionToSave = (int) $cond_array[0].':'.(int) $cond_array[1].':'.$time;
                UserManager::update_extra_field_value(
                    $user_id,
                    'legal_accept',
                    $conditionToSave
                );

                Event::addEvent(
                    LOG_TERM_CONDITION_ACCEPTED,
                    LOG_USER_OBJECT,
                    api_get_user_info($user_id),
                    api_get_utc_datetime()
                );

                $bossList = UserManager::getStudentBossList($user_id);
                if (!empty($bossList)) {
                    $bossList = array_column($bossList, 'boss_id');
                    $currentUserInfo = api_get_user_info($user_id);
                    $followUpPath = api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$currentUserInfo['id'];
                    foreach ($bossList as $bossId) {
                        $subjectEmail = sprintf(
                            get_lang('User %s signed the agreement.'),
                            $currentUserInfo['complete_name']
                        );
                        $contentEmail = sprintf(
                            get_lang('User %s signed the agreement.TheY'),
                            $currentUserInfo['complete_name'],
                            api_get_local_time($time)
                        );

                        MessageManager::send_message_simple(
                            $bossId,
                            $subjectEmail,
                            $contentEmail,
                            $user_id
                        );
                    }
                }
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
    Session::write('_user', $_user);

    $is_allowedCreateCourse = isset($values['status']) && 1 == $values['status'];
    $usersCanCreateCourse = api_is_allowed_to_create_course();

    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

    // Stats
    //Event::eventLogin($user_id);

    // last user login date is now
    $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
    Session::write('user_last_login_datetime', $user_last_login_datetime);
    $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);
    $text_after_registration =
        '<p>'.
        get_lang('Dear').' '.
        stripslashes(Security::remove_XSS($recipient_name)).',<br /><br />'.
        get_lang('Your personal settings have been registered').".</p>";

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

    if ('true' === api_get_setting('allow_terms_conditions') && $user_already_registered_show_terms) {
        if ('login' === api_get_setting('load_term_conditions_section')) {
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
            $text_after_registration .= '<p>'.get_lang('An e-mail has been sent to remind you of your login and password').'.</p>';
        }

        if ($is_allowedCreateCourse) {
            if ($usersCanCreateCourse) {
                $form_data['message'] = '<p>'.get_lang('You can now create your course').'</p>';
            }
            $form_data['action'] = api_get_path(WEB_CODE_PATH).'create_course/add_course.php';

            if ('true' === api_get_setting('course_validation')) {
                $form_data['button'] = Display::button(
                    'next',
                    get_lang('Create a course request'),
                    ['class' => 'btn btn-primary btn-large']
                );
            } else {
                $form_data['button'] = Display::button(
                    'next',
                    get_lang('Create a course'),
                    ['class' => 'btn btn-primary btn-large']
                );
                $form_data['go_button'] = '&nbsp;&nbsp;<a href="'.api_get_path(WEB_PATH).'index.php'.'">'.
                    Display::span(
                        get_lang('Next'),
                        ['class' => 'btn btn-primary btn-large']
                    ).'</a>';
            }
        } else {
            if ('true' == api_get_setting('allow_students_to_browse_courses')) {
                $form_data['action'] = 'courses.php?action=subscribe';
                $form_data['message'] = '<p>'.get_lang('You can now select, in the list, the course you want access to').".</p>";
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
        Session::erase('SessionIsPremium');
        Session::erase('sessionId');
        header('Location:'.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process.php?i='.$sessionId.'&t=2');
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
        $tpl->assign('inscription_header', Display::page_header($tool_name));
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', '');
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
                            api_get_user_id(),
                            $course_info['real_id']
                        );
                    }
                }
            }
            CourseManager::redirectToCourse([]);
        }

        $tpl = new Template($tool_name);
        $inscription_header = '';
        if ($termActivated !== false) {
            $inscription_header = Display::page_header($tool_name);
        }
        $tpl->assign('inscription_header', $inscription_header);
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', $form->returnForm());
        $tpl->assign('hide_header', $hideHeaders);
        $tpl->assign('text_after_registration', $text_after_registration);
        //$page = Container::getPage('inscription');
        //$tpl->assign('page', $page);

        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
}
