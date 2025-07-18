<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Helpers\ContainerHelper;
use ChamiloSession as Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

$kernel = null;

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

$allowedFieldsConfiguration = api_get_setting('registration.allow_fields_inscription', true);
if ('false' !== $allowedFieldsConfiguration) {
    $allowedFields = $allowedFieldsConfiguration['fields'] ?? [];
    $allowedFields['extra_fields'] = $allowedFieldsConfiguration['extra_fields'] ?? [];
}

$pluginTccDirectoryPath = api_get_path(SYS_PLUGIN_PATH) . 'logintcc';
$isTccEnabled = (is_dir($pluginTccDirectoryPath) && 'true' === api_get_plugin_setting('logintcc', 'tool_enable'));
$webserviceUrl = '';
$hash = '';

if ($isTccEnabled) {
    // Configure TCC plugin settings and JavaScript for the form
    // (This section includes the JavaScript code for the TCC plugin integration)
    $webserviceUrl = api_get_plugin_setting('logintcc', 'webservice_url');
    $hash = api_get_plugin_setting('logintcc', 'hash');
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
                        alert("'.get_lang("Unknown user").'");
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
$registeringText = addslashes(get_lang('Registering'));
$htmlHeadXtra[] = <<<EOD
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[name="registration"]');
    if (form) {
        form.addEventListener('submit', function(event) {
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('disabled');
                btn.innerText = '{$registeringText}';
            });
        });
    }
});
</script>
EOD;


// User is not allowed if Terms and Conditions are disabled and
// registration is disabled too.
$isCreatingIntroPage = isset($_GET['create_intro_page']);
$isPlatformAdmin = api_is_platform_admin();

$isNotAllowedHere = (
    'false' === api_get_setting('allow_terms_conditions') &&
    'false' === api_get_setting('allow_registration')
);

if ($isNotAllowedHere && !($isCreatingIntroPage && $isPlatformAdmin)) {
    api_not_allowed(
        true,
        get_lang('Sorry, you are trying to access the registration page for this portal, but registration is currently disabled. Please contact the administrator (see contact information in the footer). If you already have an account on this site.')
    );
}

$settingConditions = api_get_setting('profile.show_conditions_to_user', true);
$extraConditions = 'false' !== $settingConditions ? $settingConditions : [];
if ($extraConditions && isset($extraConditions['conditions'])) {
    // Create user extra fields for the conditions
    $userExtraField = new ExtraField('user');
    $extraConditions = $extraConditions['conditions'];
    foreach ($extraConditions as $condition) {
        $exists = $userExtraField->get_handler_field_info_by_field_variable($condition['variable']);
        if (false == $exists) {
            $params = [
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
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
$userAlreadyRegisteredShowTerms = false;
$termRegistered = Session::read('term_and_condition');
if ('true' === api_get_setting('allow_terms_conditions')) {
    $userAlreadyRegisteredShowTerms = isset($termRegistered['user_id']);
    // Ofaj change
    if (true === api_is_anonymous() &&  'course' === api_get_setting('load_term_conditions_section')) {
        $userAlreadyRegisteredShowTerms = false;
    }
}

$sessionPremiumChecker = Session::read('SessionIsPremium');
$sessionId = Session::read('sessionId');

// Direct Link Session Subscription feature #12220
$sessionRedirect = isset($_REQUEST['s']) && !empty($_REQUEST['s']) ? $_REQUEST['s'] : null;
$onlyOneCourseSessionRedirect = isset($_REQUEST['cr']) && !empty($_REQUEST['cr']) ? $_REQUEST['cr'] : null;

if ('true' === api_get_setting('session.allow_redirect_to_session_after_inscription_about')) {
    if (!empty($sessionRedirect)) {
        Session::write('session_redirect', $sessionRedirect);
        Session::write('only_one_course_session_redirect', $onlyOneCourseSessionRedirect);
    }
}

// Direct Link Subscription feature #5299
$course_code_redirect = isset($_REQUEST['c']) && !empty($_REQUEST['c']) ? $_REQUEST['c'] : null;
$exercise_redirect = isset($_REQUEST['e']) && !empty($_REQUEST['e']) ? $_REQUEST['e'] : null;

if (!empty($course_code_redirect)) {
    if (!api_is_anonymous()) {
        $course_info = api_get_course_info($course_code_redirect);
        $subscribed = CourseManager::autoSubscribeToCourse($course_code_redirect);
        if ($subscribed) {
            header('Location: ' . api_get_path(WEB_PATH) . 'course/'.$course_info['real_id'].'/home?sid=0');
        } else {
            header('Location: ' . api_get_path(WEB_PATH) . 'course/'.$course_info['real_id'].'/about');
        }
        exit;
    }
    Session::write('course_redirect', $course_code_redirect);
    Session::write('exercise_redirect', $exercise_redirect);
}

// allow_registration can be 'true', 'false', 'approval' or 'confirmation'. Only 'false' hides the form.
if (false === $userAlreadyRegisteredShowTerms &&
    'false' !== api_get_setting('allow_registration')
) {
    // EMAIL
    $form->addElement('text', 'email', get_lang('e-mail'), ['size' => 40]);
    if ('true' === api_get_setting('registration', 'email')) {
        $form->addRule('email', get_lang('Required field'), 'required');
    }

    if ($isTccEnabled) {
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
    if ('true' === api_get_setting('profile.registration_add_helptext_for_2_names')) {
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

    $form->addRule(
        'email',
        get_lang('This e-mail address has already been used by the maximum number of allowed accounts. Please use another.'),
        'callback',
        function ($email) {
            return !api_email_reached_registration_limit($email);
        }
    );

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
    if ('true' === $checkPass) {
        $checkPass = '';
    }

    // PASSWORD
    $form->addElement(
        'password',
        'pass1',
        [get_lang('Pass'), $passDiv],
        ['id' => 'pass1', 'size' => 20, 'autocomplete' => 'off', 'show_hide' => true]
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
            get_lang('Password too easy to guess').': '.api_generate_password(),
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
        //if ('true' === api_get_setting('registration', 'phone')) {
        $form->addRule(
            'phone',
            get_lang('Required field'),
            'required'
        );
        //}
    }

    // Language
    if (in_array('language', $allowedFields)) {
        //if ('true' === api_get_setting('registration', 'language')) {
        $form->addSelectLanguage(
            'language',
            get_lang('Language'),
            [],
            ['id' => 'language']
        );
        //}
    }

    if (in_array('official_code', $allowedFields)) {
        $form->addElement(
            'text',
            'official_code',
            get_lang('Official code'),
            ['size' => 40]
        );
        //if ('true' === api_get_setting('registration', 'officialcode')) {
        $form->addRule(
            'official_code',
            get_lang('Required field'),
            'required'
        );
        //}
    }

    // STUDENT/TEACHER
    if ('false' !== api_get_setting('allow_registration_as_teacher')) {
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

    $captcha = api_get_setting('allow_captcha');
    $allowCaptcha = 'true' === $captcha;

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
        $settingRequiredFields = api_get_setting('registration.required_extra_fields_in_inscription', true);
        $requiredFields = 'false' !== $settingRequiredFields ? $settingRequiredFields : [];

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
            [],
            false,
            false,
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
if (!empty($_POST['language'])) {
    $defaults['language'] = Security::remove_XSS($_POST['language']);
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

$user['language'] = 'french';
$userInfo = api_get_user_info();
if (!empty($userInfo)) {
    $langInfo = api_get_language_from_iso($userInfo['language']);
}

$toolName = get_lang('Registration');
if ('approval' === api_get_setting('allow_registration')) {
    $content .= Display::return_message(get_lang('Your account has to be approved'));
}

//if openid was not found
if (!empty($_GET['openid_msg']) && 'idnotfound' == $_GET['openid_msg']) {
    $content .= Display::return_message(get_lang('This OpenID could not be found in our database. Please register for a new account. If you have already an account with us, please edit your profile inside your account to add this OpenID'));
}

$blockButton = false;
$termActivated = false;
$showTerms = false;
$infoMessage = '';

if ($blockButton) {
    if (!empty($infoMessage)) {
        $form->addHtml($infoMessage);
    }
    $form->addButton(
        'submit',
        get_lang('Register'),
        'check',
        'primary',
        null,
        null,
        ['disabled' => 'disabled'],
        false
    );
} else {
    $allow = ('true' === api_get_setting('platform.allow_double_validation_in_registration'));
    ChamiloHelper::addLegalTermsFields($form, $userAlreadyRegisteredShowTerms);
    if ($allow && !$termActivated) {
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
                ['class' => 'btn btn--plain', 'id' => 'pre_validation']
            )
        );
        $form->addHtml('<div id="final_button" style="display: none">');
        $form->addLabel(
            null,
            Display::return_message(get_lang('You confirm that you really want to subscribe to this plateform.'), 'info', false)
        );
        $form->addButton('submit', get_lang('Register'), '', 'primary');
        $form->addHtml('</div>');
    } else {
        $form->addButtonNext(get_lang('Register'));
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

$tpl = new Template($toolName);
$textAfterRegistration = '';
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

    // Moved here to include extra fields when creating a user. Formerly placed after user creation
    // Register extra fields
    $extras = [];
    $extraParams = [];
    foreach ($values as $key => $value) {
        if ('extra_' === substr($key, 0, 6)) {
            //an extra field
            $extras[substr($key, 6)] = $value;
            $extraParams[$key] = $value;
        }
    }

    $status = $values['status'] ?? STUDENT;
    $phone = $values['phone'] ?? null;
    $values['language'] = isset($values['language']) ? $values['language'] : api_get_language_isocode();
    $values['address'] = $values['address'] ?? '';

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
    $userId = UserManager::create_user(
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
        [UserAuthSource::PLATFORM],
        null,
        1,
        0,
        $extraParams,
        null,
        true,
        false,
        $values['address'],
        true,
        $form,
        $creatorId
    );

    // save T&C acceptance
    if ('true' === api_get_setting('allow_terms_conditions')
        && !empty($values['legal_accept_type'])
    ) {
        ChamiloHelper::saveUserTermsAcceptance($userId, $values['legal_accept_type']);
    }

    // Update the extra fields
    $countExtraField = count($extras);
    if ($countExtraField > 0 && is_int($userId)) {
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
                    UserManager::update_extra_field_value($userId, $key, $value);
                }
            } else {
                UserManager::update_extra_field_value($userId, $key, $value);
            }
        }
    }

    if ($userId) {
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
            $sql .= " WHERE user_id = ".intval($userId)."";
            Database::query($sql);
        }

        // Saving user to Session if it was set
        if (!empty($sessionToRedirect) && !$sessionPremiumChecker) {
            $sessionInfo = api_get_session_info($sessionToRedirect);
            if (!empty($sessionInfo)) {
                SessionManager::subscribeUsersToSession(
                    $sessionToRedirect,
                    [$userId],
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
                        $userId,
                        $course_info['real_id']
                    );
                }
            }
        }

        /* If the account has to be approved then we set the account to inactive,
        sent a mail to the platform admin and exit the page.*/
        if ('approval' === api_get_setting('allow_registration')) {
            // 1. Send mail to all platform admin
            $chamiloUser = api_get_user_entity($userId);
            MessageManager::sendNotificationOfNewRegisteredUserApproval($chamiloUser);

            // 2. set account inactive
            UserManager::disable($userId);

            // 3. exit the page
            unset($userId);

            Display::display_header($toolName);
            echo Display::page_header($toolName);
            echo $content;
            Display::display_footer();
            exit;
        } elseif ('confirmation' === api_get_setting('allow_registration')) {
            // 1. Send mail to the user
            $thisUser = api_get_user_entity($userId);
            UserManager::sendUserConfirmationMail($thisUser);

            // 2. set account inactive
            UserManager::disable($userId);

            // 3. exit the page
            unset($userId);

            Display::addFlash(
                Display::return_message(
                    get_lang('You need confirm your account via e-mail to access the platform'),
                    'warning'
                )
            );

            Display::display_header($toolName);
            //echo $content;
            Display::display_footer();
            exit;
        }
    }


    /* SESSION REGISTERING */
    /* @todo move this in a function */
    $user['firstName'] = stripslashes($values['firstname']);
    $user['lastName'] = stripslashes($values['lastname']);
    $user['mail'] = $values['email'];
    $user['language'] = $values['language'];
    $user['user_id'] = $userId;
    Session::write('_user', $user);

    $is_allowedCreateCourse = isset($values['status']) && 1 == $values['status'];
    $usersCanCreateCourse = api_is_allowed_to_create_course();

    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

    if ('AppCache' == get_class($kernel)) {
        $kernel = $kernel->getKernel();
    }
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine.orm.default_entity_manager');
    $userRepository = $entityManager->getRepository(User::class);
    $userEntity = $userRepository->find($userId);

    $providerKey = 'main';
    $roles = $userEntity->getRoles();
    $token = new UsernamePasswordToken($userEntity, $providerKey, $roles);

    $container->get(ContainerHelper::class)->getTokenStorage()->setToken($token);
    $request = $container->get('request_stack')->getMainRequest();
    $sessionHandler = $container->get('request_stack')->getSession();
    $sessionHandler->set('_security_' . $providerKey, serialize($token));
    $userData = [
        'firstName' => stripslashes($values['firstname']),
        'lastName' => stripslashes($values['lastname']),
        'mail' => $values['email'],
        'language' => $values['language'],
        'user_id' => $userId
    ];

    $sessionHandler->set('_user', $userData);
    $sessionHandler->set('_locale_user', $userEntity->getLocale());
    $is_allowedCreateCourse = isset($values['status']) && 1 == $values['status'];
    $sessionHandler->set('is_allowedCreateCourse', $is_allowedCreateCourse);

    // Stats
    Container::getTrackELoginRepository()
        ->createLoginRecord($userEntity, new DateTime(), $request->getClientIp())
    ;
    // @todo implement Auto-subscribe according to STATUS_autosubscribe setting

    // last user login date is now
    $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
    Session::write('user_last_login_datetime', $user_last_login_datetime);
    $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);
    $textAfterRegistration =
        '<p>'.
        get_lang('Dear', $userEntity->getLocale()).' '.
        stripslashes(Security::remove_XSS($recipient_name)).',<br /><br />'.
        get_lang('Your personal settings have been registered', $userEntity->getLocale())."</p>";

    $formData = [
        'button' => Display::button(
            'next',
            get_lang('Next'),
            ['class' => 'btn btn--primary btn-large']
        ),
        'message' => '',
        'action' => api_get_path(WEB_PATH).'home',
        'go_button' => '',
    ];

    if ('true' === api_get_setting('allow_terms_conditions') && $userAlreadyRegisteredShowTerms) {
        if ('login' === api_get_setting('load_term_conditions_section')) {
            header('Location: /home');
            exit;
            //$formData['action'] = api_get_path(WEB_PATH).'user_portal.php';
        } else {
            $courseInfo = api_get_course_info();
            if (!empty($courseInfo)) {
                $formData['action'] = $courseInfo['course_public_url'].'?id_session='.api_get_session_id();
                $cidReset = true;
                Session::erase('_course');
                Session::erase('_cid');
            } else {
                $formData['action'] = api_get_path(WEB_PATH).'home';
            }
        }
    } else {
        if (!empty($values['email'])) {
            $linkDiagnostic = api_get_path(WEB_PATH).'main/search/search.php';
            $textAfterRegistration .= '<p>'.get_lang('An e-mail has been sent to remind you of your login and password', $userEntity->getLocale()).'</p>';
            $diagnosticPath = '<a href="'.$linkDiagnostic.'" class="custom-link">'.$linkDiagnostic.'</a>';
            $textAfterRegistration .= '<p>';
            if ('true' === api_get_setting('session.allow_search_diagnostic')) {
                $textAfterRegistration .= sprintf(
                    get_lang('Welcome, please go to diagnostic at %s.', $userEntity->getLocale()),
                    $diagnosticPath
                );
            }
            $textAfterRegistration .= '</p>';
        }

        if ($is_allowedCreateCourse) {
            if ($usersCanCreateCourse) {
                $formData['message'] = '<p>'.get_lang('You can now create your course').'</p>';
            }
            $formData['action'] = api_get_path(WEB_CODE_PATH).'create_course/add_course.php';

            if ('true' === api_get_setting('course_validation')) {
                $formData['button'] = Display::button(
                    'next',
                    get_lang('Create a course request'),
                    ['class' => 'btn btn--primary btn-large']
                );
            } else {
                $formData['button'] = Display::button(
                    'next',
                    get_lang('Create a course'),
                    ['class' => 'btn btn--primary btn-large']
                );
                $formData['go_button'] = '&nbsp;&nbsp;<a href="'.api_get_path(WEB_PATH).'index.php'.'">'.
                    Display::span(
                        get_lang('Next'),
                        ['class' => 'btn btn--primary btn-large']
                    ).'</a>';
            }
        } else {
            if ('true' == api_get_setting('allow_students_to_browse_courses')) {
                $formData['action'] = 'courses.php?action=subscribe';
                $formData['message'] = '<p>'.get_lang('You can now select, in the list, the course you want access to').".</p>";
            } else {
                $formData['action'] = api_get_path(WEB_PATH).'user_portal.php';
            }
            $formData['button'] = Display::button(
                'next',
                get_lang('Next'),
                ['class' => 'btn btn--primary btn-large']
            );
        }
    }

    if ($sessionPremiumChecker && $sessionId) {
        Session::erase('SessionIsPremium');
        Session::erase('sessionId');
        header('Location:'.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process.php?i='.$sessionId.'&t=2');
        exit;
    }

    SessionManager::redirectToSession();

    $redirectBuyCourse = Session::read('buy_course_redirect');
    if (!empty($redirectBuyCourse)) {
        $formData['action'] = api_get_path(WEB_PATH).$redirectBuyCourse;
        Session::erase('buy_course_redirect');
    }

    $formData = CourseManager::redirectToCourse($formData);
    $formRegister = new FormValidator('form_register', 'post', $formData['action']);
    if (!empty($formData['message'])) {
        $formRegister->addElement('html', $formData['message'].'<br /><br />');
    }

    if ($usersCanCreateCourse) {
        $formRegister->addElement('html', $formData['button']);
    } else {
        if (!empty($redirectBuyCourse)) {
            $formRegister->addButtonNext(get_lang('Next'));
        } else {
            $formRegister->addElement('html', $formData['go_button']);
        }
    }

    $textAfterRegistration .= $formRegister->returnForm();

    // Just in case
    Session::erase('course_redirect');
    Session::erase('exercise_redirect');
    Session::erase('session_redirect');
    Session::erase('only_one_course_session_redirect');
    Session::write('textAfterRegistration', $textAfterRegistration);

    header('location: '.api_get_self());
    exit;

} else {
    $textAfterRegistration = Session::read('textAfterRegistration');
    if (isset($textAfterRegistration)) {
        $tpl->assign('inscription_header', Display::page_header($toolName));
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', '');
        $tpl->assign('text_after_registration', $textAfterRegistration);
        $tpl->assign('hide_header', $hideHeaders);
        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);

        Session::erase('textAfterRegistration');
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

        $inscriptionHeader = '';
        if (false !== $termActivated) {
            $inscriptionHeader = Display::page_header($toolName);
        }
        $em = Container::getEntityManager();
        $categoryRepo = $em->getRepository(PageCategory::class);
        $pageRepo = $em->getRepository(Page::class);
        $accessUrl = api_get_url_entity();
        $locale = api_get_language_isocode();

        $category = $categoryRepo->findOneBy(['title' => 'introduction']);
        $introPage = null;
        if ($category) {
            $introPage = $pageRepo->findOneBy([
                'category' => $category,
                'url' => $accessUrl,
                'enabled' => true,
            ]);
        }

        if ($introPage) {
            $content = '<div class="alert alert-info shadow-sm rounded border-start border-4 border-primary p-3 mb-4">'
                . $introPage->getContent()
                . '</div>' . $content;
        }

        if ($isCreatingIntroPage && $isPlatformAdmin) {
            $user = api_get_user_entity();

            if ($introPage) {
                header('Location: '.api_get_path(WEB_PATH).'resources/pages/edit?id=/api/pages/'.$introPage->getId());
                exit;
            }

            if (!$category) {
                $category = new PageCategory();
                $category
                    ->setTitle('introduction')
                    ->setType('cms')
                    ->setCreator($user);
                $em->persist($category);
                $em->flush();
            }

            $page = new Page();
            $page
                ->setTitle(get_lang("Introduction to registration"))
                ->setContent('<p>'.get_lang("Welcome to the registration process.").'</p>')
                ->setSlug('intro-inscription')
                ->setLocale($locale)
                ->setCategory($category)
                ->setEnabled(true)
                ->setCreator($user)
                ->setUrl($accessUrl)
                ->setPosition(1);

            $em->persist($page);
            $em->flush();

            header('Location: '.api_get_path(WEB_PATH).'resources/pages/edit?id=/api/pages/'.$page->getId());
            exit;
        }
        $tpl->assign('inscription_header', $inscriptionHeader);
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', $form->returnForm());
        $tpl->assign('hide_header', $hideHeaders);
        $tpl->assign('text_after_registration', $textAfterRegistration);
        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
}
