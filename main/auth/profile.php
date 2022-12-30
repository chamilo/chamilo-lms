<?php

/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;

/**
 * This file displays the user's profile,
 * optionally it allows users to modify their profile as well.
 *
 * See inc/conf/profile.conf.php to modify settings
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
api_block_inactive_user();

$this_section = SECTION_MYPROFILE;
$allowSocialTool = api_get_setting('allow_social_tool') === 'true';
if ($allowSocialTool) {
    $this_section = SECTION_SOCIAL;
}

$logInfo = [
    'tool' => 'profile',
    'action' => $this_section,
];
Event::registerLog($logInfo);

$_SESSION['this_section'] = $this_section;

if (!(isset($_user['user_id']) && $_user['user_id']) || api_is_anonymous($_user['user_id'], true)) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = api_get_password_checker_js('#username', '#password1');
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$htmlHeadXtra[] = '<script>
$(function() {
    $("#id_generate_api_key").on("click", function (e) {
        e.preventDefault();

        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "POST",
            url: "'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=generate_api_key",
            data: "num_key_id="+"",
            success: function(datos) {
                $("#div_api_key").html(datos);
            }
        });
    });

});

function confirmation(name) {
    if (confirm("'.get_lang('AreYouSureToDeleteJS', '').' " + name + " ?")) {
            document.forms["profile"].submit();
    } else {
        return false;
    }
}
function show_image(image,width,height) {
    width = parseInt(width) + 20;
    height = parseInt(height) + 20;
    window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}
</script>';

$jquery_ready_content = '';
if (api_get_setting('allow_message_tool') === 'true') {
    $jquery_ready_content = <<<EOF
    $(".message-content .message-delete").click(function(){
        $(this).parents(".message-content").animate({ opacity: "hide" }, "slow");
        $(".message-view").animate({ opacity: "show" }, "slow");
    });
EOF;
}

$tool_name = is_profile_editable() ? get_lang('ModifProfile') : get_lang('ViewProfile');
$table_user = Database::get_main_table(TABLE_MAIN_USER);

/*
 * Get initial values for all fields.
 */
$user_data = $originalUserInfo = api_get_user_info(
    api_get_user_id(),
    false,
    false,
    false,
    false,
    true,
    true
);
$currentUser = api_get_user_entity($user_data['id']);
$array_list_key = UserManager::get_api_keys(api_get_user_id());
$id_temp_key = UserManager::get_api_key_id(api_get_user_id(), 'dokeos');
$value_array = [];
if (isset($array_list_key[$id_temp_key])) {
    $value_array = $array_list_key[$id_temp_key];
}
$user_data['api_key_generate'] = $value_array;

if ($user_data !== false) {
    if (api_get_setting('login_is_email') === 'true') {
        $user_data['username'] = $user_data['email'];
    }
    if (is_null($user_data['language'])) {
        $user_data['language'] = api_get_setting('platformLanguage');
    }
}

$form = new FormValidator('profile');

if (api_is_western_name_order()) {
    // FIRST NAME and LAST NAME
    $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40]);
    $form->addElement('text', 'lastname', get_lang('LastName'), ['size' => 40]);
} else {
    // LAST NAME and FIRST NAME
    $form->addElement('text', 'lastname', get_lang('LastName'), ['size' => 40]);
    $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40]);
}
if (api_get_setting('profile', 'name') !== 'true') {
    $form->freeze(['lastname', 'firstname']);
}
$form->applyFilter(['lastname', 'firstname'], 'stripslashes');
$form->applyFilter(['lastname', 'firstname'], 'trim');
$form->applyFilter(['lastname', 'firstname'], 'html_filter');
$form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

//    USERNAME
$form->addElement(
    'text',
    'username',
    get_lang('UserName'),
    [
        'id' => 'username',
        'maxlength' => USERNAME_MAX_LENGTH,
        'size' => USERNAME_MAX_LENGTH,
    ]
);

if (api_get_setting('profile', 'login') !== 'true' || api_get_setting('login_is_email') === 'true') {
    $form->freeze('username');
}
$form->applyFilter('username', 'stripslashes');
$form->applyFilter('username', 'trim');
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);

//    OFFICIAL CODE
if (defined('CONFVAL_ASK_FOR_OFFICIAL_CODE') && CONFVAL_ASK_FOR_OFFICIAL_CODE === true) {
    $form->addElement('text', 'official_code', get_lang('OfficialCode'), ['size' => 40]);
    if (api_get_setting('profile', 'officialcode') !== 'true') {
        $form->freeze('official_code');
    }
    $form->applyFilter('official_code', 'stripslashes');
    $form->applyFilter('official_code', 'trim');
    $form->applyFilter('official_code', 'html_filter');
    if (api_get_setting('registration', 'officialcode') === 'true' &&
        api_get_setting('profile', 'officialcode') === 'true'
    ) {
        $form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
    }
}

// EMAIL
$form->addElement('email', 'email', get_lang('Email'), ['size' => 40]);
if (api_get_setting('profile', 'email') !== 'true') {
    $form->freeze('email');
}

if (api_get_setting('registration', 'email') === 'true' && api_get_setting('profile', 'email') === 'true') {
    $form->applyFilter('email', 'stripslashes');
    $form->applyFilter('email', 'trim');
    $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('email', get_lang('EmailWrong'), 'email');
}

// OPENID URL
if (is_profile_editable() && api_get_setting('openid_authentication') === 'true') {
    $form->addElement('text', 'openid', get_lang('OpenIDURL'), ['size' => 40]);
    if (api_get_setting('profile', 'openid') !== 'true') {
        $form->freeze('openid');
    }
    $form->applyFilter('openid', 'trim');
}

//    PHONE
$form->addElement('text', 'phone', get_lang('Phone'), ['size' => 20]);
if (api_get_setting('profile', 'phone') !== 'true') {
    $form->freeze('phone');
}
$form->applyFilter('phone', 'stripslashes');
$form->applyFilter('phone', 'trim');
$form->applyFilter('phone', 'html_filter');

//  PICTURE
if (is_profile_editable() && api_get_setting('profile', 'picture') == 'true') {
    $form->addFile(
        'picture',
        [
            $user_data['picture_uri'] != '' ? get_lang('UpdateImage') : get_lang('AddImage'),
            get_lang('OnlyImagesAllowed'),
        ],
        [
            'id' => 'picture',
            'class' => 'picture-form',
            'crop_image' => true,
            'crop_ratio' => '1 / 1',
            'accept' => 'image/*',
        ]
    );

    $form->addProgress();
    if (!empty($user_data['picture_uri'])) {
        $form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
    }
    $allowed_picture_types = api_get_supported_image_extensions(false);
    $form->addRule(
        'picture',
        get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowed_picture_types).')',
        'filetype',
        $allowed_picture_types
    );
}

//    LANGUAGE
$form->addSelectLanguage('language', get_lang('Language'));
if (api_get_setting('profile', 'language') !== 'true') {
    $form->freeze('language');
}

// THEME
if (is_profile_editable() && api_get_setting('user_selected_theme') === 'true') {
    $form->addElement('SelectTheme', 'theme', get_lang('Theme'));
    if (api_get_setting('profile', 'theme') !== 'true') {
        $form->freeze('theme');
    }
    $form->applyFilter('theme', 'trim');
}

//    EXTENDED PROFILE  this make the page very slow!
if (api_get_setting('extended_profile') === 'true') {
    $width_extended_profile = 500;
    //    MY PERSONAL OPEN AREA
    $form->addHtmlEditor(
        'openarea',
        [get_lang('MyPersonalOpenArea'), get_lang('MyPersonalOpenAreaHelp')],
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '350',
        ]
    );
    //    MY COMPETENCES
    $form->addHtmlEditor(
        'competences',
        [get_lang('MyCompetences'), get_lang('MyCompetencesHelp')],
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '130',
        ]
    );
    //    MY DIPLOMAS
    $form->addHtmlEditor(
        'diplomas',
        [get_lang('MyDiplomas'), get_lang('MyDiplomasHelp')],
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '130',
        ]
    );
    // WHAT I AM ABLE TO TEACH
    $form->addHtmlEditor(
        'teach',
        [get_lang('MyTeach'), get_lang('MyTeachingCapabilitiesHelp')],
        false,
        false,
        [
            'ToolbarSet' => 'Profile',
            'Width' => $width_extended_profile,
            'Height' => '130',
        ]
    );

    //    MY PRODUCTIONS
    $form->addElement('file', 'production', [get_lang('MyProductions'), get_lang('MyProductionsHelp')]);
    if ($production_list = UserManager::build_production_list(api_get_user_id(), '', true)) {
        $form->addElement('static', 'productions_list', null, $production_list);
    }
    // openarea is untrimmed for maximum openness
    $form->applyFilter(['competences', 'diplomas', 'teach', 'openarea'], 'stripslashes');
    $form->applyFilter(['competences', 'diplomas', 'teach'], 'trim');
}

$showPassword = is_platform_authentication();
$links = api_get_configuration_value('auth_password_links');
$extraLink = '';
if (!empty($links) &&
    isset($links['profiles']) &&
    isset($links['profiles'][$user_data['status']]) &&
    isset($links['profiles'][$user_data['status']][$user_data['auth_source']])
) {
    $extraUserConditions = $links['profiles'][$user_data['status']][$user_data['auth_source']];
    if (isset($extraUserConditions['show_password_field'])) {
        $showPassword = $extraUserConditions['show_password_field'];
    }

    if (isset($extraUserConditions['extra_link'])) {
        $extraLink = $extraUserConditions['extra_link'];
    }
}

//    PASSWORD, if auth_source is platform
if ($showPassword &&
    is_profile_editable() &&
    api_get_setting('profile', 'password') === 'true'
) {
    $form->addElement(
        'password',
        'password0',
        [get_lang('Pass'), get_lang('TypeCurrentPassword')],
        [
            'size' => 40,
            'show_hide' => true,
        ]
    );
    $form->addElement(
        'password',
        'password1',
        get_lang('NewPass'),
        [
            'id' => 'password1',
            'size' => 40,
            'show_hide' => true,
            'placeholder' => get_lang('EnterYourNewPassword'),
        ]
    );
    $form->addElement(
        'password',
        'password2',
        [get_lang('Confirmation'), get_lang('RepeatYourNewPassword')],
        [
            'size' => 40,
            'show_hide' => true,
        ]
    );
    //    user must enter identical password twice so we can prevent some user errors
    $form->addRule(['password1', 'password2'], get_lang('PassTwo'), 'compare');
    $form->addPasswordRule('password1');
    $form->addNoSamePasswordRule('password1', $currentUser);
}

$form->addHtml($extraLink);

$extraField = new ExtraField('user');
$return = $extraField->addElements($form, api_get_user_id(), ['pause_formation', 'start_pause_date', 'end_pause_date']);
$jquery_ready_content = $return['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that
// will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(function() {
    '.$jquery_ready_content.'
});
</script>';

if (api_get_setting('profile', 'apikeys') == 'true') {
    $form->addElement('html', '<div id="div_api_key">');
    $form->addElement(
        'text',
        'api_key_generate',
        get_lang('MyApiKey'),
        ['size' => 40, 'id' => 'id_api_key_generate']
    );
    $form->addElement('html', '</div>');
    $form->addButton(
        'generate_api_key',
        get_lang('GenerateApiKey'),
        'cogs',
        'default',
        'default',
        null,
        ['id' => 'id_generate_api_key']
    );
}
$form->addHidden('origin', 'profile');
//    SUBMIT
if (is_profile_editable()) {
    $form->addButtonUpdate(get_lang('SaveSettings'), 'apply_change');
} else {
    $form->freeze();
}

// Student cannot modified their user conditions
$extraConditions = api_get_configuration_value('show_conditions_to_user');
if ($extraConditions && isset($extraConditions['conditions'])) {
    $extraConditions = $extraConditions['conditions'];
    foreach ($extraConditions as $condition) {
        $element = $form->getElement('extra_'.$condition['variable']);
        if ($element) {
            $element->freeze();
        }
    }
}

$form->setDefaults($user_data);

$filtered_extension = false;

if ($form->validate()) {
    $hook = HookUpdateUser::create();

    if ($hook) {
        $hook->notifyUpdateUser(HOOK_EVENT_TYPE_PRE);
    }

    $wrong_current_password = false;
    $user_data = $form->getSubmitValues(1);
    /** @var User $user */
    $user = UserManager::getRepository()->find(api_get_user_id());

    // set password if a new one was provided
    $validPassword = false;
    $passwordWasChecked = false;

    if ($user &&
        (!empty($user_data['password0']) &&
        !empty($user_data['password1'])) ||
        (!empty($user_data['password0']) &&
        api_get_setting('profile', 'email') == 'true')
    ) {
        $passwordWasChecked = true;
        $validPassword = UserManager::checkPassword(
            $user->getPassword(),
            $user_data['password0'],
            $user->getSalt(),
            $user->getId()
        );

        if ($validPassword) {
            $password = $user_data['password1'];
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('CurrentPasswordEmptyOrIncorrect'),
                    'warning',
                    false
                )
            );
        }
    }

    $allow_users_to_change_email_with_no_password = true;
    if (is_platform_authentication() &&
        api_get_setting('allow_users_to_change_email_with_no_password') == 'false'
    ) {
        $allow_users_to_change_email_with_no_password = false;
    }

    // If user sending the email to be changed (input available and not frozen )
    if (api_get_setting('profile', 'email') == 'true') {
        if ($allow_users_to_change_email_with_no_password) {
            if (!check_user_email($user_data['email'])) {
                $changeemail = $user_data['email'];
            }
        } else {
            // Normal behaviour
            if (!check_user_email($user_data['email']) && $validPassword) {
                $changeemail = $user_data['email'];
            }

            if (!check_user_email($user_data['email']) && empty($user_data['password0'])) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('ToChangeYourEmailMustTypeYourPassword'),
                        'error',
                        false
                    )
                );
            }
        }
    }

    // Upload picture if a new one is provided
    if ($_FILES['picture']['size']) {
        $new_picture = UserManager::update_user_picture(
            api_get_user_id(),
            $_FILES['picture']['name'],
            $_FILES['picture']['tmp_name'],
            $user_data['picture_crop_result']
        );

        if ($new_picture) {
            $user_data['picture_uri'] = $new_picture;

            Display::addFlash(
                Display::return_message(
                    get_lang('PictureUploaded'),
                    'normal',
                    false
                )
            );
        }
    } elseif (!empty($user_data['remove_picture'])) {
        // remove existing picture if asked
        UserManager::deleteUserPicture(api_get_user_id());
        $user_data['picture_uri'] = '';
    }

    // Remove production.
    if (isset($user_data['remove_production']) &&
        is_array($user_data['remove_production'])
    ) {
        foreach (array_keys($user_data['remove_production']) as $production) {
            UserManager::remove_user_production(api_get_user_id(), urldecode($production));
        }
        if ($production_list = UserManager::build_production_list(api_get_user_id(), true, true)) {
            $form->insertElementBefore(
                $form->createElement('static', null, null, $production_list),
                'productions_list'
            );
        }
        $form->removeElement('productions_list');
        Display::addFlash(
            Display::return_message(get_lang('FileDeleted'), 'normal', false)
        );
    }

    // upload production if a new one is provided
    if (isset($_FILES['production']) && $_FILES['production']['size']) {
        $res = upload_user_production(api_get_user_id());
        if (!$res) {
            //it's a bit excessive to assume the extension is the reason why
            // upload_user_production() returned false, but it's true in most cases
            $filtered_extension = true;
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('ProductionUploaded'),
                    'normal',
                    false
                )
            );
        }
    }

    // remove values that shouldn't go in the database
    unset(
        $user_data['password0'],
        $user_data['password1'],
        $user_data['password2'],
        $user_data['MAX_FILE_SIZE'],
        $user_data['remove_picture'],
        $user_data['apply_change'],
        $user_data['email']
    );

    // Following RFC2396 (http://www.faqs.org/rfcs/rfc2396.html), a URI uses ':' as a reserved character
    // we can thus ensure the URL doesn't contain any scheme name by searching for ':' in the string
    $my_user_openid = isset($user_data['openid']) ? $user_data['openid'] : '';
    if (!preg_match('/^[^:]*:\/\/.*$/', $my_user_openid)) {
        //ensure there is at least a http:// scheme in the URI provided
        $user_data['openid'] = 'http://'.$my_user_openid;
    }
    $extras = [];

    //Checking the user language
    $languages = api_get_languages();
    if (!in_array($user_data['language'], $languages['folder'])) {
        $user_data['language'] = api_get_setting('platformLanguage');
    }
    $_SESSION['_user']['language'] = $user_data['language'];

    //Only update values that are request by the "profile" setting
    $profile_list = api_get_setting('profile');
    //Adding missing variables

    $available_values_to_modify = [];
    foreach ($profile_list as $key => $status) {
        if ($status == 'true') {
            switch ($key) {
                case 'login':
                    $available_values_to_modify[] = 'username';
                    break;
                case 'name':
                    $available_values_to_modify[] = 'firstname';
                    $available_values_to_modify[] = 'lastname';
                    break;
                case 'picture':
                    $available_values_to_modify[] = 'picture_uri';
                    break;
                default:
                    $available_values_to_modify[] = $key;
                    break;
            }
        }
    }

    //Fixing missing variables
    $available_values_to_modify = array_merge(
        $available_values_to_modify,
        ['competences', 'diplomas', 'openarea', 'teach', 'openid', 'address']
    );

    // build SQL query
    $sql = "UPDATE $table_user SET";
    unset($user_data['api_key_generate']);

    foreach ($user_data as $key => $value) {
        if (substr($key, 0, 6) === 'extra_') { //an extra field
            continue;
        } elseif (strpos($key, 'remove_extra_') !== false) {
        } else {
            if (in_array($key, $available_values_to_modify)) {
                $sql .= " $key = '".Database::escape_string($value)."',";
            }
        }
    }

    $changePassword = false;
    // Change email
    if ($allow_users_to_change_email_with_no_password) {
        if (isset($changeemail) && in_array('email', $available_values_to_modify)) {
            $sql .= " email = '".Database::escape_string($changeemail)."' ";
        }
        if (isset($password) && in_array('password', $available_values_to_modify)) {
            $changePassword = true;
        }
    } else {
        if (isset($changeemail) && !isset($password) && in_array('email', $available_values_to_modify)) {
            $sql .= " email = '".Database::escape_string($changeemail)."'";
        } else {
            if (isset($password) && in_array('password', $available_values_to_modify)) {
                if (isset($changeemail) && in_array('email', $available_values_to_modify)) {
                    $sql .= " email = '".Database::escape_string($changeemail)."' ";
                }
                $changePassword = true;
            }
        }
    }

    $sql = rtrim($sql, ',');
    if ($changePassword && !empty($password)) {
        UserManager::updatePassword(api_get_user_id(), $password);
    }

    if (api_get_setting('profile', 'officialcode') === 'true' &&
        isset($user_data['official_code'])
    ) {
        $sql .= ", official_code = '".Database::escape_string($user_data['official_code'])."'";
    }

    $sql .= " WHERE id  = '".api_get_user_id()."'";
    Database::query($sql);

    if ($passwordWasChecked == false) {
        Display::addFlash(
            Display::return_message(get_lang('ProfileReg'), 'normal', false)
        );
    } else {
        if ($validPassword) {
            Display::addFlash(
                Display::return_message(get_lang('ProfileReg'), 'normal', false)
            );
        }
    }

    $extraField = new ExtraFieldValue('user');
    $extraField->saveFieldValues($user_data);

    $userInfo = api_get_user_info(
        api_get_user_id(),
        false,
        false,
        false,
        false,
        true,
        true
    );
    Session::write('_user', $userInfo);

    $notification = api_get_configuration_value('user_notification_settings');
    if (!empty($notification)) {
        foreach ($notification as $label => $notificationSettings) {
            $sendMessage = false;
            if (isset($notificationSettings['if_field_changes'])) {
                foreach ($notificationSettings['if_field_changes'] as $field) {
                    if ($originalUserInfo[$field] != $userInfo[$field]) {
                        $sendMessage = true;
                        break;
                    }
                }
            }

            if ($sendMessage) {
                $subject = $notificationSettings['subject'];
                $content = $notificationSettings['content'];
                $userInfo['extra_fields'] = UserManager::get_extra_user_data(api_get_user_id());
                $template = new Template();
                $template->assign('old', $originalUserInfo);
                $template->assign('new', $userInfo);
                $content = $template->fetch($template->get_template($content));

                $emails = explode(',', $notificationSettings['email']);
                foreach ($emails as $email) {
                    api_mail_html(
                        '',
                        $email,
                        $subject,
                        $content,
                        $userInfo['complete_name'],
                        $notificationSettings['sender_email'],
                        [
                            'reply_to' => [
                                'mail' => $userInfo['mail'],
                                'name' => $userInfo['complete_name'],
                            ],
                        ]
                    );
                }
            }
        }
    }

    if ($hook) {
        Database::getManager()->clear(User::class); // Avoid cache issue (user entity is used before)
        $user = api_get_user_entity(api_get_user_id()); // Get updated user info for hook event
        $hook->setEventData(['user' => $user]);
        $hook->notifyUpdateUser(HOOK_EVENT_TYPE_POST);
    }

    Session::erase('system_timezone');

    $url = api_get_self();
    header("Location: $url");
    exit;
}

$actions = '';
if ($allowSocialTool) {
    if (api_get_setting('extended_profile') === 'true') {
        if (api_get_setting('allow_message_tool') === 'true') {
            $actions .= '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.
                Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'</a>';
            $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
                Display::return_icon('inbox.png', get_lang('Messages')).'</a>';
        }
        $show = isset($_GET['show']) ? '&show='.(int) $_GET['show'] : '';

        if (isset($_GET['type']) && $_GET['type'] === 'extended') {
            $actions .= '<a href="profile.php?type=reduced'.$show.'">'.
                Display::return_icon('edit.png', get_lang('EditNormalProfile'), '', 16).'</a>';
        } else {
            $actions .= '<a href="profile.php?type=extended'.$show.'">'.
                Display::return_icon('edit.png', get_lang('EditExtendProfile'), '', 16).'</a>';
        }
    }
}

$show_delete_account_button = api_get_setting('platform_unsubscribe_allowed') === 'true' ? true : false;

$tpl = new Template(get_lang('ModifyProfile'));

if ($actions) {
    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actions])
    );
}

SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
$tabs = SocialManager::getHomeProfileTabs('profile');

if ($allowSocialTool) {
    SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'home');
    $menu = SocialManager::show_social_menu(
        'home',
        null,
        api_get_user_id(),
        false,
        $show_delete_account_button
    );
    $tpl->assign('social_menu_block', $menu);
    $tpl->assign('social_right_content', $tabs.$form->returnForm());
    $social_layout = $tpl->get_template('social/edit_profile.tpl');

    $tpl->display($social_layout);
} else {
    $bigImage = UserManager::getUserPicture(api_get_user_id(), USER_IMAGE_SIZE_BIG);
    $normalImage = UserManager::getUserPicture(api_get_user_id(), USER_IMAGE_SIZE_ORIGINAL);

    $imageToShow = '<div id="image-message-container">';
    $imageToShow .= '<a class="expand-image pull-right" href="'.$bigImage.'" /><img src="'.$normalImage.'"></a>';
    $imageToShow .= '</div>';

    $content = $imageToShow.$form->returnForm().$tabs;

    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}

// Helper functions defined below this point

/**
 * Is user auth_source is platform ?
 *
 * @return bool Whether auth_source is 'platform' or not
 */
function is_platform_authentication()
{
    $tabUserInfo = api_get_user_info();

    return $tabUserInfo['auth_source'] == PLATFORM_AUTH_SOURCE;
}

/**
 * Can a user edit his/her profile?
 *
 * @return bool Whether the profile can be edited by the user or not
 */
function is_profile_editable()
{
    if (isset($GLOBALS['profileIsEditable'])) {
        return (bool) $GLOBALS['profileIsEditable'];
    }

    return true;
}

/**
 * Upload a submitted user production.
 *
 * @param int $userId User id
 *
 * @return mixed The filename of the new production or FALSE if the upload has failed
 */
function upload_user_production($userId)
{
    $productionRepository = UserManager::getUserPathById($userId, 'system');

    if (!file_exists($productionRepository)) {
        @mkdir($productionRepository, api_get_permissions_for_new_directories(), true);
    }
    $filename = api_replace_dangerous_char($_FILES['production']['name']);
    $filename = disable_dangerous_file($filename);

    if (filter_extension($filename)) {
        if (@move_uploaded_file($_FILES['production']['tmp_name'], $productionRepository.$filename)) {
            return $filename;
        }
    }

    return false; // this should be returned if anything went wrong with the upload
}

/**
 * Check current user's current password.
 *
 * @param string $email E-mail
 *
 * @return bool Whether this e-mail is already in use or not
 */
function check_user_email($email)
{
    $userId = api_get_user_id();
    if ($userId != strval(intval($userId)) || empty($email)) {
        return false;
    }
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);
    $email = Database::escape_string($email);
    $sql = "SELECT * FROM $tableUser WHERE user_id = $userId AND email = '$email'";
    $result = Database::query($sql);

    return Database::num_rows($result) != 0;
}
